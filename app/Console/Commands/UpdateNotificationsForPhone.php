<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UpdateNotificationsForPhone extends Command
{
    
     * The name and signature of the console command.
     *
     * @var string

    protected $signature = 'notifications:update-for-phone';

    
     * The console command description.
     *
     * @var string

    protected $description = 'Обновляет настройки системных уведомлений для использования телефона вместо email';

    
     * Execute the console command.

    public function handle()
    {
        $this->info('Начало обновления настроек уведомлений...');

        try {
            
            $this->updateNotificationChannels();

            
            $this->updateNotificationRoutes();

            
            $this->updateNotificationTemplates();

            $this->info('Обновление настроек уведомлений завершено успешно!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Произошла ошибка: ' . $e->getMessage());
            Log::error('Ошибка при обновлении настроек уведомлений: ' . $e->getMessage());
            return 1;
        }
    }

    
     * Обновление каналов для уведомлений

    protected function updateNotificationChannels()
    {
        $this->info('Обновление каналов для уведомлений...');

        
        if (Schema::hasTable('notification_channels')) {
            $this->info('Обновление таблицы notification_channels...');

            
            DB::beginTransaction();

            try {
                
                $updated = DB::table('notification_channels')
                    ->where('channel', 'mail')
                    ->update(['active' => false]);

                $this->info("Деактивировано {$updated} каналов электронной почты.");

                
                $smsExists = DB::table('notification_channels')
                    ->where('channel', 'sms')
                    ->exists();

                if (!$smsExists) {
                    
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

    
     * Обновление маршрутов для уведомлений

    protected function updateNotificationRoutes()
    {
        $this->info('Обновление маршрутов для уведомлений...');

        
        if (Schema::hasTable('notification_routes')) {
            $this->info('Обновление таблицы notification_routes...');

            
            DB::beginTransaction();

            try {
                
                $emailRoutes = DB::table('notification_routes')
                    ->where('route_value', 'like', '%@%')
                    ->get();

                $count = 0;

                
                foreach ($emailRoutes as $route) {
                    $email = $route->route_value;

                    
                    $user = DB::table('users')
                        ->where('email', $email)
                        ->first();

                    if ($user && !empty($user->phone)) {
                        
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

    
     * Обновление шаблонов уведомлений

    protected function updateNotificationTemplates()
    {
        $this->info('Обновление шаблонов уведомлений...');

        
        if (Schema::hasTable('notification_templates')) {
            $this->info('Обновление таблицы notification_templates...');

            
            DB::beginTransaction();

            try {
                
                $emailTemplates = DB::table('notification_templates')
                    ->where('channel', 'mail')
                    ->get();

                $count = 0;

                
                foreach ($emailTemplates as $template) {
                    
                    $smsExists = DB::table('notification_templates')
                        ->where('notification_type', $template->notification_type)
                        ->where('channel', 'sms')
                        ->exists();

                    if (!$smsExists) {
                        
                        DB::table('notification_templates')->insert([
                            'notification_type' => $template->notification_type,
                            'channel' => 'sms',
                            'subject' => substr($template->subject, 0, 50), 
                            'body' => $this->convertEmailBodyToSms($template->body), 
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

    
     * Конвертирует HTML-тело email в простой текст для SMS

    protected function convertEmailBodyToSms($htmlBody)
    {
        
        $text = strip_tags($htmlBody);

        
        $text = preg_replace('/\s+/', ' ', $text);

        
        $text = substr(trim($text), 0, 160);

        
        if (strlen(trim($htmlBody)) > 160) {
            $text .= '...';
        }

        return $text;
    }
}
