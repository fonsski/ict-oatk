<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UpdateNotificationsForPhone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:update-for-phone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет настройки системных уведомлений для использования телефона вместо email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начало обновления настроек уведомлений...');

        try {
            // Обновляем настройки уведомлений
            $this->updateNotificationChannels();

            // Обновляем маршруты для уведомлений
            $this->updateNotificationRoutes();

            // Обновляем шаблоны уведомлений
            $this->updateNotificationTemplates();

            $this->info('Обновление настроек уведомлений завершено успешно!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Произошла ошибка: ' . $e->getMessage());
            Log::error('Ошибка при обновлении настроек уведомлений: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Обновление каналов для уведомлений
     */
    protected function updateNotificationChannels()
    {
        $this->info('Обновление каналов для уведомлений...');

        // Проверяем существование таблицы notification_channels
        if (Schema::hasTable('notification_channels')) {
            $this->info('Обновление таблицы notification_channels...');

            // Начинаем транзакцию
            DB::beginTransaction();

            try {
                // Обновляем настройки каналов
                $updated = DB::table('notification_channels')
                    ->where('channel', 'mail')
                    ->update(['active' => false]);

                $this->info("Деактивировано {$updated} каналов электронной почты.");

                // Проверяем наличие канала SMS
                $smsExists = DB::table('notification_channels')
                    ->where('channel', 'sms')
                    ->exists();

                if (!$smsExists) {
                    // Добавляем канал SMS, если его нет
                    DB::table('notification_channels')->insert([
                        'channel' => 'sms',
                        'name' => 'SMS уведомления',
                        'description' => 'Отправка уведомлений через SMS',
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->info('Добавлен канал SMS уведомлений.');
                } else {
                    // Активируем канал SMS, если он существует
                    DB::table('notification_channels')
                        ->where('channel', 'sms')
                        ->update(['active' => true]);
                    $this->info('Активирован существующий канал SMS уведомлений.');
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            $this->warn('Таблица notification_channels не найдена. Пропускаем обновление каналов.');
        }
    }

    /**
     * Обновление маршрутов для уведомлений
     */
    protected function updateNotificationRoutes()
    {
        $this->info('Обновление маршрутов для уведомлений...');

        // Проверяем существование таблицы notification_routes
        if (Schema::hasTable('notification_routes')) {
            $this->info('Обновление таблицы notification_routes...');

            // Начинаем транзакцию
            DB::beginTransaction();

            try {
                // Получаем все маршруты, использующие email
                $emailRoutes = DB::table('notification_routes')
                    ->where('route_value', 'like', '%@%')
                    ->get();

                $count = 0;

                // Для каждого маршрута находим соответствующего пользователя и обновляем на телефон
                foreach ($emailRoutes as $route) {
                    $email = $route->route_value;

                    // Находим пользователя по email
                    $user = DB::table('users')
                        ->where('email', $email)
                        ->first();

                    if ($user && !empty($user->phone)) {
                        // Обновляем маршрут на телефон
                        DB::table('notification_routes')
                            ->where('id', $route->id)
                            ->update([
                                'route_type' => 'phone',
                                'route_value' => $user->phone,
                                'updated_at' => now(),
                            ]);

                        $count++;
                    }
                }

                $this->info("Обновлено {$count} маршрутов уведомлений с email на телефон.");

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            $this->warn('Таблица notification_routes не найдена. Пропускаем обновление маршрутов.');
        }
    }

    /**
     * Обновление шаблонов уведомлений
     */
    protected function updateNotificationTemplates()
    {
        $this->info('Обновление шаблонов уведомлений...');

        // Проверяем существование таблицы notification_templates
        if (Schema::hasTable('notification_templates')) {
            $this->info('Обновление таблицы notification_templates...');

            // Начинаем транзакцию
            DB::beginTransaction();

            try {
                // Получаем все шаблоны для email
                $emailTemplates = DB::table('notification_templates')
                    ->where('channel', 'mail')
                    ->get();

                $count = 0;

                // Для каждого email шаблона создаем аналогичный SMS шаблон
                foreach ($emailTemplates as $template) {
                    // Проверяем, существует ли уже SMS шаблон для этого типа уведомления
                    $smsExists = DB::table('notification_templates')
                        ->where('notification_type', $template->notification_type)
                        ->where('channel', 'sms')
                        ->exists();

                    if (!$smsExists) {
                        // Создаем SMS версию шаблона
                        DB::table('notification_templates')->insert([
                            'notification_type' => $template->notification_type,
                            'channel' => 'sms',
                            'subject' => substr($template->subject, 0, 50), // Сокращаем тему для SMS
                            'body' => $this->convertEmailBodyToSms($template->body), // Конвертируем тело в SMS формат
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $count++;
                    }
                }

                $this->info("Создано {$count} новых SMS шаблонов на основе email шаблонов.");

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            $this->warn('Таблица notification_templates не найдена. Пропускаем обновление шаблонов.');
        }
    }

    /**
     * Конвертирует HTML-тело email в простой текст для SMS
     */
    protected function convertEmailBodyToSms($htmlBody)
    {
        // Удаляем HTML-теги
        $text = strip_tags($htmlBody);

        // Заменяем несколько пробелов на один
        $text = preg_replace('/\s+/', ' ', $text);

        // Обрезаем текст до 160 символов (стандартная длина SMS)
        $text = substr(trim($text), 0, 160);

        // Добавляем многоточие, если текст был обрезан
        if (strlen(trim($htmlBody)) > 160) {
            $text .= '...';
        }

        return $text;
    }
}
