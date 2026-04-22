<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueFailedJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:failed-job
                            {id : ID неудачного задания}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показать подробную информацию о неудачном задании';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');

        $job = DB::table('failed_jobs')
            ->where('id', $id)
            ->first();

        if (!$job) {
            $this->error("Неудачное задание с ID {$id} не найдено.");
            return 1;
        }

        $this->displayJobDetails($job);

        return 0;
    }

    /**
     * Отображение подробностей о неудачном задании
     *
     * @param object $job
     * @return void
     */
    protected function displayJobDetails($job)
    {
        $payload = json_decode($job->payload, true);
        $command = $payload['data']['commandName'] ?? 'Unknown';
        $data = $payload['data']['command'] ?? null;

        if ($data) {
            $data = unserialize(stripslashes($data));
        }

        $this->info('=== Информация о неудачном задании ===');
        $this->line('');

        $this->line("<comment>ID:</comment> {$job->id}");
        $this->line("<comment>UUID:</comment> {$job->uuid}");
        $this->line("<comment>Подключение:</comment> {$job->connection}");
        $this->line("<comment>Очередь:</comment> {$job->queue}");
        $this->line("<comment>Команда:</comment> {$command}");
        $this->line("<comment>Дата сбоя:</comment> {$job->failed_at}");

        // Форматируем ошибку для лучшей читаемости
        $exception = str_replace("\n", "\n  ", $job->exception);

        $this->line('');
        $this->line("<comment>Ошибка:</comment>");
        $this->line("  {$exception}");

        // Отображаем данные задания, если возможно
        if ($data instanceof \Illuminate\Notifications\SendQueuedNotifications) {
            $this->displayNotificationDetails($data);
        } elseif (is_object($data)) {
            $this->displayGenericCommandDetails($data);
        }

        $this->line('');
        $this->line("<comment>Варианты действий:</comment>");
        $this->line("  php artisan queue:retry {$job->id}   # Повторить задание");
        $this->line("  php artisan queue:forget {$job->id}  # Удалить задание из очереди неудачных");
        $this->line("  php artisan queue:flush              # Удалить все неудачные задания");
    }

    /**
     * Отображение подробностей о неудачном уведомлении
     *
     * @param \Illuminate\Notifications\SendQueuedNotifications $notification
     * @return void
     */
    protected function displayNotificationDetails($notification)
    {
        $notifiables = $notification->notifiables;
        $notificationClass = get_class($notification->notification);

        $this->line('');
        $this->line("<comment>Тип уведомления:</comment> {$notificationClass}");

        if (count($notifiables) > 0) {
            $this->line("<comment>Получатели:</comment>");

            foreach ($notifiables as $notifiable) {
                if (method_exists($notifiable, 'getKey')) {
                    $identifier = $notifiable->getKey();
                } else {
                    $identifier = spl_object_hash($notifiable);
                }

                $this->line("  - " . get_class($notifiable) . " (ID: {$identifier})");

                if (property_exists($notifiable, 'email')) {
                    $this->line("    Email: {$notifiable->email}");
                }
            }
        }

        // Попытка отобразить данные уведомления
        if (method_exists($notification->notification, 'toArray')) {
            try {
                $notificationData = $notification->notification->toArray($notifiables[0] ?? null);

                if (!empty($notificationData)) {
                    $this->line("<comment>Данные уведомления:</comment>");

                    foreach ($notificationData as $key => $value) {
                        if (is_scalar($value)) {
                            $this->line("  {$key}: {$value}");
                        }
                    }
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки при получении данных уведомления
            }
        }
    }

    /**
     * Отображение подробностей о неудачной команде
     *
     * @param object $command
     * @return void
     */
    protected function displayGenericCommandDetails($command)
    {
        $this->line('');
        $this->line("<comment>Данные задания:</comment>");

        // Получаем публичные свойства класса
        $reflectionClass = new \ReflectionClass($command);
        $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($command);

            if (is_scalar($value) || is_null($value)) {
                $this->line("  {$name}: " . var_export($value, true));
            } elseif (is_array($value)) {
                $this->line("  {$name}: " . json_encode($value));
            } elseif (is_object($value)) {
                $this->line("  {$name}: " . get_class($value));
            }
        }
    }
}
