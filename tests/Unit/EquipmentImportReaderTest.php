<?php

namespace Tests\Unit;

use App\Services\EquipmentImport\ImportFileReader;
use App\Services\EquipmentImport\InvalidImportFile;
use PHPUnit\Framework\TestCase;

class EquipmentImportReaderTest extends TestCase
{
    private function read(string $content, string $extension = "csv"): array
    {
        $path = tempnam(sys_get_temp_dir(), "imp");
        file_put_contents($path, $content);

        try {
            return (new ImportFileReader())->read($path, $extension)->rows;
        } finally {
            unlink($path);
        }
    }

    public function test_windows_1251_csv_with_semicolons_is_decoded(): void
    {
        $content = mb_convert_encoding("inventory_number;name\n1001;Принтер\n", "Windows-1251", "UTF-8");

        $this->assertSame("Принтер", $this->read($content)[0]["data"]["name"]);
    }

    public function test_bom_and_comma_delimiter_are_handled(): void
    {
        $rows = $this->read("\xEF\xBB\xBFInventory_Number, Name \n1001,Принтер\n");

        $this->assertSame(["inventory_number" => "1001", "name" => "Принтер"], $rows[0]["data"]);
    }

    public function test_blank_lines_are_skipped_and_line_numbers_follow_the_file(): void
    {
        $rows = $this->read("inventory_number\n1001\n\n;\n1002\n");

        $this->assertSame([2, 5], array_column($rows, "line"));
    }

    public function test_too_many_rows_are_rejected(): void
    {
        $content = "inventory_number\n" . implode("\n", range(1, ImportFileReader::MAX_ROWS + 1));

        $this->expectException(InvalidImportFile::class);
        $this->read($content);
    }

    public function test_nested_json_values_are_rejected(): void
    {
        $this->expectException(InvalidImportFile::class);
        $this->read(json_encode([["inventory_number" => ["1001"]]]), "json");
    }
}
