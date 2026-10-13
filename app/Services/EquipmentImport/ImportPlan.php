<?php

namespace App\Services\EquipmentImport;

/**
 * Что произойдёт при импорте: результат проверки каждой строки файла.
 *
 * Строка плана:
 *  - line, inventory_number, name — для показа человеку;
 *  - action — create | update | skip;
 *  - attributes — готовые значения колонок таблицы equipment (пустые ячейки
 *    сюда не попадают, поэтому не затирают то, что уже есть в системе);
 *  - new_category, new_room — что нужно создать до записи оборудования;
 *  - errors, warnings — сообщения для человека. Есть хоть одна ошибка —
 *    импорт не выполняется вообще.
 */
final class ImportPlan
{
    public const CREATE = "create";
    public const UPDATE = "update";
    public const SKIP = "skip";

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $unknownColumns Колонки файла, которые система не использует.
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $unknownColumns = [],
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->errorRows() !== [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function errorRows(): array
    {
        return array_values(array_filter($this->rows, fn ($row) => $row["errors"] !== []));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function warningRows(): array
    {
        return array_values(array_filter($this->rows, fn ($row) => $row["warnings"] !== []));
    }

    /**
     * Сколько корректных строк с таким действием.
     */
    public function count(string $action): int
    {
        return count(array_filter(
            $this->rows,
            fn ($row) => $row["action"] === $action && $row["errors"] === [],
        ));
    }
}
