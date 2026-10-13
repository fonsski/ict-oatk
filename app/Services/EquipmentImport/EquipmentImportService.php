<?php

namespace App\Services\EquipmentImport;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\Room;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Импорт оборудования из файла в два шага: plan() только проверяет файл и
 * ничего не пишет, apply() выполняет проверенный план одной транзакцией.
 *
 * Правила:
 *  - оборудование ищется по inventory_number;
 *  - пустая ячейка означает «не менять», очистить поле импортом нельзя;
 *  - достаточно одной ошибки в файле, чтобы не записать ничего.
 */
final class EquipmentImportService
{
    public const MODE_SKIP = "skip";
    public const MODE_UPDATE = "update";

    /**
     * Все колонки, которые понимает импорт.
     */
    public const FIELDS = [
        "inventory_number",
        "accounting_number",
        "name",
        "model",
        "serial_number",
        "category",
        "status",
        "room_number",
        "room_name",
        "operating_system",
        "has_warranty",
        "warranty_end_date",
        "last_service_date",
        "service_comment",
        "known_issues",
    ];

    private const DEFAULT_STATUS = "working";

    private const ACCOUNTING_PATTERN =
        '/^[АБ][1-5]-(студент|преподаватель|администрация|сотрудник)-[0-9]{3}$/u';

    private const TRUE_VALUES = ["1", "true", "yes", "y", "да", "+"];
    private const FALSE_VALUES = ["0", "false", "no", "n", "нет", "-"];

    private const DATE_FORMATS = ["Y-m-d", "d.m.Y", "d/m/Y"];

    public function plan(ImportFile $file, string $mode, bool $createMissing): ImportPlan
    {
        if (!in_array("inventory_number", $file->columns, true)) {
            throw new InvalidImportFile(
                "В файле нет колонки inventory_number — по ней система находит оборудование.",
            );
        }

        $lookups = new ImportLookups();
        $seen = ["inventory" => [], "accounting" => []];
        $rows = [];

        foreach ($file->rows as $row) {
            $rows[] = $this->planRow($row, $lookups, $mode, $createMissing, $seen);
        }

        return new ImportPlan(
            $rows,
            array_values(array_diff($file->columns, self::FIELDS)),
        );
    }

    /**
     * Записывает проверенный план. Возвращает счётчики для сообщения человеку.
     *
     * @return array{created: int, updated: int, skipped: int, categories: int, rooms: int}
     */
    public function apply(ImportPlan $plan): array
    {
        if ($plan->hasErrors()) {
            throw new LogicException("Нельзя выполнить импорт, пока в плане есть ошибки.");
        }

        return DB::transaction(function () use ($plan) {
            $result = ["created" => 0, "updated" => 0, "skipped" => 0, "categories" => 0, "rooms" => 0];
            $categoryIds = [];
            $roomIds = [];

            foreach ($plan->rows as $row) {
                if ($row["action"] === ImportPlan::SKIP) {
                    $result["skipped"]++;
                    continue;
                }

                $attributes = $row["attributes"];

                if ($row["new_category"] !== null) {
                    $key = ImportLookups::key($row["new_category"]);

                    if (!isset($categoryIds[$key])) {
                        $categoryIds[$key] = $this->createCategory($row["new_category"])->id;
                        $result["categories"]++;
                    }

                    $attributes["category_id"] = $categoryIds[$key];
                }

                if ($row["new_room"] !== null) {
                    $key = ImportLookups::key($row["new_room"]["number"]);

                    if (!isset($roomIds[$key])) {
                        $roomIds[$key] = $this->createRoom($row["new_room"])->id;
                        $result["rooms"]++;
                    }

                    $attributes["room_id"] = $roomIds[$key];
                }

                if ($row["action"] === ImportPlan::CREATE) {
                    $this->createEquipment($row["inventory_number"], $attributes);
                    $result["created"]++;
                } else {
                    $this->updateEquipment($row["equipment_id"], $attributes);
                    $result["updated"]++;
                }
            }

            return $result;
        });
    }

    /**
     * @param array{line: int, data: array<string, string|bool|null>} $row
     * @param array{inventory: array<string, int>, accounting: array<string, int>} $seen
     * @return array<string, mixed>
     */
    private function planRow(
        array $row,
        ImportLookups $lookups,
        string $mode,
        bool $createMissing,
        array &$seen,
    ): array {
        $data = $row["data"];
        $errors = [];
        $warnings = [];
        $attributes = [];
        $newCategory = null;
        $newRoom = null;

        $inventory = $this->text($data["inventory_number"] ?? null);

        if ($inventory === null) {
            $errors[] = "Не указан инвентарный номер (inventory_number).";
        } elseif (!preg_match('/^\d{1,20}$/', $inventory)) {
            $errors[] = "Инвентарный номер «{$inventory}» должен состоять только из цифр (не больше 20). "
                . "Если в Excel он превратился во что-то вроде «2,1E+09», сделайте колонку текстовой.";
        } elseif (isset($seen["inventory"][$inventory])) {
            $errors[] = "Инвентарный номер {$inventory} уже встречается в строке {$seen["inventory"][$inventory]}.";
        } else {
            $seen["inventory"][$inventory] = $row["line"];
        }

        $existing = $inventory !== null ? $lookups->equipment($inventory) : null;

        $action = match (true) {
            $existing === null => ImportPlan::CREATE,
            $mode === self::MODE_UPDATE => ImportPlan::UPDATE,
            default => ImportPlan::SKIP,
        };

        if ($existing?->trashed() && $action === ImportPlan::UPDATE) {
            $errors[] = "Оборудование с номером {$inventory} удалено. Восстановите его в системе или укажите другой номер.";
        }

        if ($action !== ImportPlan::SKIP && $errors === []) {
            $this->fillAttributes(
                $data,
                $row["line"],
                $existing,
                $action,
                $lookups,
                $createMissing,
                $seen,
                $attributes,
                $newCategory,
                $newRoom,
                $errors,
                $warnings,
            );
        }

        return [
            "line" => $row["line"],
            "inventory_number" => $inventory,
            "name" => $this->text($data["name"] ?? null),
            "action" => $action,
            "equipment_id" => $existing?->id,
            "attributes" => $attributes,
            "new_category" => $newCategory,
            "new_room" => $newRoom,
            "errors" => $errors,
            "warnings" => $warnings,
        ];
    }

    /**
     * Разбирает поля записи в значения колонок. Всё, что не так, попадает в
     * $errors (блокирует импорт) или $warnings (не блокирует).
     *
     * @param array<string, string|bool|null> $data
     * @param array{inventory: array<string, int>, accounting: array<string, int>} $seen
     * @param array<string, mixed> $attributes
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    private function fillAttributes(
        array $data,
        int $line,
        ?Equipment $existing,
        string $action,
        ImportLookups $lookups,
        bool $createMissing,
        array &$seen,
        array &$attributes,
        ?string &$newCategory,
        ?array &$newRoom,
        array &$errors,
        array &$warnings,
    ): void {
        foreach ([
            "name" => [2, 255],
            "model" => [1, 255],
            "serial_number" => [1, 255],
            "service_comment" => [5, 500],
            "known_issues" => [5, 500],
        ] as $field => [$min, $max]) {
            $this->readText($data, $field, $min, $max, $attributes, $errors);
        }

        $this->readAccountingNumber($data, $existing, $lookups, $seen, $line, $attributes, $errors);

        // Категория определяет, можно ли хранить операционную систему.
        $categoryName = $this->text($data["category"] ?? null);
        $category = null;

        if ($categoryName === null) {
            $category = $lookups->categoryById($existing?->category_id);
        } elseif ($found = $lookups->category($categoryName)) {
            $category = $found;
            $attributes["category_id"] = $found->id;
        } elseif ($createMissing) {
            $newCategory = $categoryName;
        } else {
            $errors[] = "Категория «{$categoryName}» не найдена в системе. "
                . "Добавьте её заранее или включите создание недостающих категорий и кабинетов.";
        }

        $statusName = $this->text($data["status"] ?? null);

        if ($statusName !== null) {
            if ($status = $lookups->status($statusName)) {
                $attributes["status_id"] = $status->id;
            } else {
                $errors[] = "Неизвестный статус «{$statusName}». Допустимо: "
                    . implode(", ", $lookups->statusSlugs()) . ".";
            }
        } elseif ($action === ImportPlan::CREATE) {
            if ($status = $lookups->status(self::DEFAULT_STATUS)) {
                $attributes["status_id"] = $status->id;
            } else {
                $errors[] = "Статус не указан, а в системе нет статуса по умолчанию «" . self::DEFAULT_STATUS . "».";
            }
        }

        $roomNumber = $this->text($data["room_number"] ?? null);

        if ($roomNumber !== null) {
            if ($room = $lookups->room($roomNumber)) {
                $attributes["room_id"] = $room->id;
            } elseif ($createMissing) {
                $newRoom = [
                    "number" => $roomNumber,
                    "name" => $this->text($data["room_name"] ?? null) ?? $roomNumber,
                ];
            } else {
                $errors[] = "Кабинет «{$roomNumber}» не найден в системе. "
                    . "Добавьте его заранее или включите создание недостающих категорий и кабинетов.";
            }
        }

        $this->readOperatingSystem($data, $existing, $category, $categoryName !== null, $lookups, $attributes, $errors, $warnings);
        $this->readWarranty($data, $existing, $action, $attributes, $errors);

        $serviceDate = $this->readDate($data, "last_service_date", $errors);

        if ($serviceDate !== null) {
            if ($serviceDate > now()->toDateString()) {
                $errors[] = "Дата обслуживания (last_service_date) {$serviceDate} находится в будущем.";
            } else {
                $attributes["last_service_date"] = $serviceDate;
            }
        }
    }

    /**
     * @param array<string, string|bool|null> $data
     * @param array<string, mixed> $attributes
     * @param list<string> $errors
     */
    private function readText(
        array $data,
        string $field,
        int $min,
        int $max,
        array &$attributes,
        array &$errors,
    ): void {
        $raw = $data[$field] ?? null;

        if ($raw === null) {
            return;
        }

        $value = $this->text($raw);

        if ($value === null) {
            $errors[] = "Поле {$field} должно быть текстом.";

            return;
        }

        $length = mb_strlen($value);

        if ($length < $min || $length > $max) {
            $errors[] = "Поле {$field}: длина должна быть от {$min} до {$max} символов (сейчас {$length}).";

            return;
        }

        $attributes[$field] = $value;
    }

    /**
     * @param array<string, string|bool|null> $data
     * @param array{inventory: array<string, int>, accounting: array<string, int>} $seen
     * @param array<string, mixed> $attributes
     * @param list<string> $errors
     */
    private function readAccountingNumber(
        array $data,
        ?Equipment $existing,
        ImportLookups $lookups,
        array &$seen,
        int $line,
        array &$attributes,
        array &$errors,
    ): void {
        $number = $this->text($data["accounting_number"] ?? null);

        if ($number === null) {
            return;
        }

        $key = ImportLookups::key($number);
        $owner = $lookups->accountingOwner($number);

        if (!preg_match(self::ACCOUNTING_PATTERN, $number)) {
            $errors[] = "Учётный номер «{$number}» не соответствует формату А1-сотрудник-001: "
                . "корпус А или Б, этаж 1–5, группа (студент, преподаватель, администрация, сотрудник), три цифры.";
        } elseif ($owner !== null && $owner !== $existing?->id) {
            $errors[] = "Учётный номер {$number} уже закреплён за другим оборудованием.";
        } elseif (isset($seen["accounting"][$key])) {
            $errors[] = "Учётный номер {$number} уже встречается в строке {$seen["accounting"][$key]}.";
        } else {
            $seen["accounting"][$key] = $line;
            $attributes["accounting_number"] = $number;
        }
    }

    /**
     * @param array<string, string|bool|null> $data
     * @param array<string, mixed> $attributes
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    private function readOperatingSystem(
        array $data,
        ?Equipment $existing,
        ?EquipmentCategory $category,
        bool $categoryGiven,
        ImportLookups $lookups,
        array &$attributes,
        array &$errors,
        array &$warnings,
    ): void {
        $supported = (bool) $category?->has_operating_system;

        // Категорию сменили на ту, где ОС не бывает: снимаем привязку,
        // как это делает форма редактирования.
        if ($categoryGiven && !$supported && $existing?->operating_system_id !== null) {
            $attributes["operating_system_id"] = null;
            $warnings[] = "Категория не поддерживает операционную систему — прежняя ОС будет снята.";
        }

        $name = $this->text($data["operating_system"] ?? null);

        if ($name === null) {
            return;
        }

        $system = $lookups->operatingSystem($name);

        if ($system === null) {
            $errors[] = "Операционная система «{$name}» не найдена в системе.";
        } elseif (!$supported) {
            $warnings[] = "ОС «{$name}» не сохранена: категория этого оборудования не поддерживает операционную систему.";
        } else {
            $attributes["operating_system_id"] = $system->id;
        }
    }

    /**
     * @param array<string, string|bool|null> $data
     * @param array<string, mixed> $attributes
     * @param list<string> $errors
     */
    private function readWarranty(
        array $data,
        ?Equipment $existing,
        string $action,
        array &$attributes,
        array &$errors,
    ): void {
        $hasWarranty = null;
        $raw = $data["has_warranty"] ?? null;

        if ($raw !== null) {
            $hasWarranty = $this->parseBool($raw);

            if ($hasWarranty === null) {
                $errors[] = "Поле has_warranty: «{$raw}» не похоже на да/нет. Используйте true/false или да/нет.";
            }
        }

        $endDate = $this->readDate($data, "warranty_end_date", $errors);

        // Дата окончания без отметки означает, что гарантия есть.
        if ($endDate !== null && $raw === null) {
            $hasWarranty = true;
        }

        if ($hasWarranty !== null) {
            $attributes["has_warranty"] = $hasWarranty;
        }

        if ($endDate !== null) {
            $attributes["warranty_end_date"] = $endDate;
        }

        $dateKnown = $endDate !== null
            || ($action === ImportPlan::UPDATE && $existing?->warranty_end_date !== null);

        if ($hasWarranty === true && !$dateKnown) {
            $errors[] = "Гарантия отмечена (has_warranty), но не указана дата её окончания (warranty_end_date).";
        }
    }

    /**
     * @param array<string, string|bool|null> $data
     * @param list<string> $errors
     */
    private function readDate(array $data, string $field, array &$errors): ?string
    {
        $value = $this->text($data[$field] ?? null);

        if ($value === null) {
            return null;
        }

        $date = $this->parseDate($value);

        if ($date === null) {
            $errors[] = "Поле {$field}: дата «{$value}» не распознана. Используйте 2026-12-31 или 31.12.2026.";
        }

        return $date;
    }

    private function parseDate(string $value): ?string
    {
        // JS и многие выгрузки отдают дату с временем: 2026-12-31T00:00:00.000Z
        if (preg_match('/^(\d{4}-\d{2}-\d{2})T/', $value, $matches)) {
            $value = $matches[1];
        }

        foreach (self::DATE_FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat("!" . $format, $value);
            $problems = DateTimeImmutable::getLastErrors();

            // Без проверки PHP молча превращает 31.02 в 03.03.
            if ($date !== false && ($problems === false || $problems["warning_count"] + $problems["error_count"] === 0)) {
                return $date->format("Y-m-d");
            }
        }

        return null;
    }

    private function parseBool(string|bool $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = mb_strtolower(trim($value));

        return match (true) {
            in_array($value, self::TRUE_VALUES, true) => true,
            in_array($value, self::FALSE_VALUES, true) => false,
            default => null,
        };
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && $value !== "" ? $value : null;
    }

    private function createCategory(string $name): EquipmentCategory
    {
        $base = Str::slug($name) ?: Str::slug(Str::ascii($name)) ?: "kategoriya-" . substr(md5($name), 0, 8);
        $slug = $base;

        for ($suffix = 2; EquipmentCategory::where("slug", $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return EquipmentCategory::create(["name" => $name, "slug" => $slug]);
    }

    /**
     * @param array{number: string, name: string} $room
     */
    private function createRoom(array $room): Room
    {
        return Room::create([
            "number" => $room["number"],
            "name" => $room["name"],
            "type" => "other",
            "status" => "available",
            "is_active" => true,
            "notes" => "Создан при импорте оборудования",
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createEquipment(string $inventoryNumber, array $attributes): void
    {
        $attributes["inventory_number"] = $inventoryNumber;

        if (!empty($attributes["room_id"])) {
            $attributes["initial_room_id"] = $attributes["room_id"];
        }

        $equipment = Equipment::create($attributes);

        if ($equipment->room_id !== null) {
            $equipment->recordInitialLocation(
                $equipment->room_id,
                "Первоначальное размещение при импорте оборудования",
            );
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function updateEquipment(int $id, array $attributes): void
    {
        $equipment = Equipment::findOrFail($id);
        $previousRoomId = $equipment->room_id;

        $equipment->update($attributes);

        if (isset($attributes["room_id"]) && $attributes["room_id"] !== $previousRoomId) {
            $equipment->recordLocationChange(
                $previousRoomId,
                $attributes["room_id"],
                "Перемещение при импорте оборудования",
            );
        }
    }
}
