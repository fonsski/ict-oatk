<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Equipment;
use App\Models\EquipmentLocationHistory;
use Illuminate\Support\Facades\DB;

class MigrateEquipmentLocationHistory extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'equipment:migrate-location-history';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Создание записей в истории перемещений для существующего оборудования';

    /**
     * Выполнение консольной команды.
     */
    public function handle()
    {
        $this->info('Начало миграции истории перемещений оборудования...');

        $equipment = Equipment::all();
        $bar = $this->output->createProgressBar(count($equipment));
        $bar->start();

        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($equipment as $item) {
            try {
                // Проверяем, есть ли уже записи в истории перемещений для этого оборудования
                $hasHistory = EquipmentLocationHistory::where('equipment_id', $item->id)->exists();

                if (!$hasHistory) {
                    // Определяем начальный кабинет
                    $initialRoomId = $item->initial_room_id ?? $item->room_id;

                    // Если у оборудования указан кабинет, создаем запись о первоначальном размещении
                    if ($initialRoomId) {
                        DB::transaction(function () use ($item, $initialRoomId) {
                            // Создаем запись о первоначальном размещении
                            EquipmentLocationHistory::create([
                                'equipment_id' => $item->id,
                                'from_room_id' => null,
                                'to_room_id' => $initialRoomId,
                                'moved_by_user_id' => null, // Так как это историческая запись
                                'move_date' => $item->created_at ?? now(),
                                'comment' => 'Первоначальное размещение (автоматически создано при миграции)',
                                'is_initial_location' => true,
                            ]);

                            // Если текущий кабинет отличается от начального, добавляем запись о перемещении
                            if ($item->room_id && $item->room_id != $initialRoomId) {
                                EquipmentLocationHistory::create([
                                    'equipment_id' => $item->id,
                                    'from_room_id' => $initialRoomId,
                                    'to_room_id' => $item->room_id,
                                    'moved_by_user_id' => null, // Так как это историческая запись
                                    'move_date' => $item->updated_at ?? now(),
                                    'comment' => 'Перемещение в текущий кабинет (автоматически создано при миграции)',
                                    'is_initial_location' => false,
                                ]);
                            }

                            // Обновляем initial_room_id, если он не был установлен
                            if (empty($item->initial_room_id) && $initialRoomId) {
                                $item->initial_room_id = $initialRoomId;
                                $item->save();
                            }
                        });

                        $successCount++;
                    } else {
                        // Нет информации о кабинете
                        $this->comment("Пропущено оборудование #{$item->id}: нет информации о кабинете");
                        $skippedCount++;
                    }
                } else {
                    // У оборудования уже есть записи в истории
                    $this->comment("Пропущено оборудование #{$item->id}: уже имеет записи в истории");
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Ошибка при обработке оборудования #{$item->id}: {$e->getMessage()}");
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Миграция завершена:");
        $this->info("- Успешно обработано: {$successCount}");
        $this->info("- Пропущено: {$skippedCount}");
        $this->info("- Ошибок: {$errorCount}");

        return Command::SUCCESS;
    }
}
