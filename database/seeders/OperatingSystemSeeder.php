<?php

namespace Database\Seeders;

use App\Models\OperatingSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Стартовый справочник операционных систем. Идемпотентен: повторный запуск
 * не плодит дубли, названия уже заведённых ОС не перетирает.
 */
class OperatingSystemSeeder extends Seeder
{
    public function run(): void
    {
        $systems = [
            ["Windows 11 Pro", "Windows"],
            ["Windows 10 Pro", "Windows"],
            ["Windows 10 Home", "Windows"],
            ["Windows 8.1", "Windows"],
            ["Windows 7", "Windows"],
            ["Windows Server 2019", "Windows"],
            ["Windows Server 2022", "Windows"],
            ["Astra Linux Special Edition", "Linux"],
            ["Astra Linux Common Edition", "Linux"],
            ["ALT Linux", "Linux"],
            ["РЕД ОС", "Linux"],
            ["Ubuntu 22.04 LTS", "Linux"],
            ["Ubuntu 24.04 LTS", "Linux"],
            ["Debian 12", "Linux"],
            ["macOS", "macOS"],
            ["Без ОС", null],
        ];

        foreach ($systems as $index => [$name, $family]) {
            OperatingSystem::firstOrCreate(
                ["name" => $name],
                [
                    "slug" => Str::slug($name) ?: Str::slug(Str::ascii($name)),
                    "family" => $family,
                    "is_active" => true,
                    "sort_order" => $index,
                ],
            );
        }
    }
}
