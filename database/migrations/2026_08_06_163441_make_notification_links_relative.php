<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Уже разосланные уведомления хранят полный URL с хостом, который был
     * актуален в момент создания (обычно из APP_URL обработчика очереди).
     * Если система открывается по другому имени или IP, такая ссылка ведёт
     * на чужой адрес и переход заканчивается ошибкой.
     *
     * Обрезаем схему и хост, оставляя путь: относительная ссылка всегда
     * открывается на том домене, где пользователь сейчас находится.
     */
    public function up(): void
    {
        DB::table('notifications')
            ->orderBy('id')
            ->chunkById(200, function ($notifications) {
                foreach ($notifications as $notification) {
                    $data = json_decode($notification->data, true);

                    if (!is_array($data)) {
                        continue;
                    }

                    $changed = false;

                    foreach (['link', 'url'] as $key) {
                        $value = $data[$key] ?? null;

                        if (!is_string($value) || !preg_match('~^https?://~i', $value)) {
                            continue;
                        }

                        $path = parse_url($value, PHP_URL_PATH) ?: '/';
                        $query = parse_url($value, PHP_URL_QUERY);
                        $data[$key] = $path . ($query ? '?' . $query : '');
                        $changed = true;
                    }

                    if ($changed) {
                        DB::table('notifications')
                            ->where('id', $notification->id)
                            ->update(['data' => json_encode($data)]);
                    }
                }
            });
    }

    /**
     * Обратно не разворачиваем: исходный хост не сохранён, а относительная
     * ссылка работает в любом окружении.
     */
    public function down(): void
    {
    }
};
