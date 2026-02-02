<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Equipment;
use App\Models\EquipmentLocationHistory;
use Illuminate\Support\Facades\DB;

class MigrateEquipmentLocationHistory extends Command
{
    
     * Имя и сигнатура консольной команды.
     *
     * @var string

    protected $signature = 'equipment:migrate-location-history';

    
     * Описание консольной команды.
     *
     * @var string

    protected $description = 'Создание записей в истории перемещений для существующего оборудования';

    
     * Выполнение консольной команды.

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
                
                $hasHistory = EquipmentLocationHistory::where('equipment_id', $item->id)->exists();

                if (!$hasHistory) {
                    
                    $initialRoomId = $item->initial_room_id ?? $item->room_id;

                    
                    if ($initialRoomId) {
                        DB::transaction(function () use ($item, $initialRoomId) {
                            
                            EquipmentLocationHistory::create([
                                'equipment_id' => $item->id,
                                'from_room_id' => null,
                                'to_room_id' => $initialRoomId,
                                'moved_by_user_id' => null, 
                                'move_date' => $item->created_at ?? now(),
                                'comment' => 'Первоначальное размещение (автоматически создано при миграции)',
                                'is_initial_location' => true,
                            ]);

                            
                            if ($item->room_id && $item->room_id != $initialRoomId) {
                                EquipmentLocationHistory::create([
                                    'equipment_id' => $item->id,
                                    'from_room_id' => $initialRoomId,
                                    'to_room_id' => $item->room_id,
                                    'moved_by_user_id' => null, 
                                    'move_date' => $item->updated_at ?? now(),
                                    'comment' => 'Перемещение в текущий кабинет (автоматически создано при миграции)',
                                    'is_initial_location' => false,
                                ]);
                            }

                            
                            if (empty($item->initial_room_id) && $initialRoomId) {
                                $item->initial_room_id = $initialRoomId;
                                $item->save();
                            }
                        });

                        $successCount++;
                    } else {
                        
                        $this->comment("Пропущено оборудование 
                        $skippedCount++;
                    }
                } else {
                    
                    $this->comment("Пропущено оборудование 
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Ошибка при обработке оборудования 
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
