<?php

namespace App\Services\EquipmentImport;

use JsonException;

/**
 * Читает CSV и JSON с оборудованием и приводит оба формата к одному виду.
 *
 * CSV понимает то, что отдаёт русский Excel: разделитель «;», кодировку
 * Windows-1251 и BOM в начале файла. Названия колонок не зависят от
 * регистра и пробелов по краям. Пустая ячейка и отсутствующий ключ
 * одинаково превращаются в null.
 */
final class ImportFileReader
{
    public const MAX_ROWS = 2000;

    private const DELIMITERS = [";", ",", "\t"];

    public function read(string $path, string $extension): ImportFile
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new InvalidImportFile("Не удалось прочитать загруженный файл.");
        }

        $contents = $this->toUtf8($contents);

        $file = strtolower($extension) === "json"
            ? $this->readJson($contents)
            : $this->readCsv($contents);

        if ($file->rows === []) {
            throw new InvalidImportFile("В файле нет ни одной записи.");
        }

        if (count($file->rows) > self::MAX_ROWS) {
            throw new InvalidImportFile(
                "В файле больше " . self::MAX_ROWS . " записей. Разделите его на несколько частей.",
            );
        }

        return $file;
    }

    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        if (!mb_check_encoding($contents, "UTF-8")) {
            $contents = mb_convert_encoding($contents, "UTF-8", "Windows-1251");
        }

        return $contents;
    }

    private function readJson(string $contents): ImportFile
    {
        try {
            $decoded = json_decode($contents, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidImportFile(
                "Файл не является корректным JSON: " . $e->getMessage(),
                previous: $e,
            );
        }

        if (is_array($decoded) && is_array($decoded["equipment"] ?? null)) {
            $decoded = $decoded["equipment"];
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new InvalidImportFile(
                "Ожидается массив объектов: [{\"inventory_number\": \"...\"}, ...].",
            );
        }

        $columns = [];
        $rows = [];

        foreach ($decoded as $index => $item) {
            $number = $index + 1;

            if (!is_array($item) || ($item !== [] && array_is_list($item))) {
                throw new InvalidImportFile("Запись №{$number} должна быть объектом с полями.");
            }

            $data = [];

            foreach ($item as $key => $value) {
                $key = $this->normalizeKey((string) $key);

                if (is_array($value)) {
                    throw new InvalidImportFile(
                        "Поле «{$key}» в записи №{$number} должно быть значением, а не списком или объектом.",
                    );
                }

                $columns[$key] = true;
                $data[$key] = $this->cell($value);
            }

            if ($this->isBlank($data)) {
                continue;
            }

            $rows[] = ["line" => $number, "data" => $data];
        }

        return new ImportFile(array_keys($columns), $rows);
    }

    private function readCsv(string $contents): ImportFile
    {
        $stream = fopen("php://temp", "r+");
        fwrite($stream, $contents);
        rewind($stream);

        try {
            $delimiter = $this->detectDelimiter($contents);

            $header = fgetcsv($stream, 0, $delimiter, '"', "");

            if ($header === false || $header === [null]) {
                throw new InvalidImportFile("Файл пустой: в первой строке должны быть названия колонок.");
            }

            $names = array_map(fn ($name) => $this->normalizeKey((string) $name), $header);
            $columns = array_values(array_filter($names, fn ($name) => $name !== ""));

            if (count($columns) !== count(array_unique($columns))) {
                throw new InvalidImportFile("В заголовке файла есть повторяющиеся названия колонок.");
            }

            $rows = [];
            $line = 1;

            while (($record = fgetcsv($stream, 0, $delimiter, '"', "")) !== false) {
                $line++;

                $data = [];

                foreach ($names as $position => $name) {
                    if ($name !== "") {
                        $data[$name] = $this->cell($record[$position] ?? null);
                    }
                }

                if ($this->isBlank($data)) {
                    continue;
                }

                $rows[] = ["line" => $line, "data" => $data];
            }
        } finally {
            fclose($stream);
        }

        return new ImportFile($columns, $rows);
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\n") ?: "";

        $counts = [];

        foreach (self::DELIMITERS as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function normalizeKey(string $key): string
    {
        return mb_strtolower(trim($key));
    }

    /**
     * Приводит значение ячейки к строке без лишних пробелов; пустое — к null.
     * Логические значения из JSON оставляем как есть, их разберёт сервис.
     */
    private function cell(mixed $value): string|bool|null
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }

        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            $value = (int) $value;
        }

        $value = trim((string) $value);

        return $value === "" ? null : $value;
    }

    /**
     * @param array<string, string|bool|null> $data
     */
    private function isBlank(array $data): bool
    {
        return array_filter($data, fn ($value) => $value !== null) === [];
    }
}
