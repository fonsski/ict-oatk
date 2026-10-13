<?php

namespace App\Services\EquipmentImport;

/**
 * Содержимое загруженного файла после разбора, независимо от формата.
 */
final readonly class ImportFile
{
    /**
     * @param list<string> $columns Названия колонок (ключей) в порядке появления.
     * @param list<array{line: int, data: array<string, string|bool|null>}> $rows
     *        Непустые записи. `line` — номер строки для CSV (как в Excel,
     *        заголовок — строка 1) или порядковый номер записи для JSON.
     */
    public function __construct(
        public array $columns,
        public array $rows,
    ) {
    }
}
