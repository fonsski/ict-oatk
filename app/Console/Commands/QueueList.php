<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:list
                            {--limit=20 : Максимальное количество заданий для отображения}
                            {--all : Показать все задания, включая зарезервированные}
                            {--failed : Показать только неудачные задания}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показать список заданий в очереди';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int)$this->option('limit');
        $showAll = $this->option('all');
        $showFailed = $this->option('failed');

        if ($showFailed) {
            $this->showFailedJobs($limit);
        } else {
            $this->showPendingJobs($limit, $showAll);
        }

        return 0;
    }

    /**
     * Показать список ожидающих заданий
     *
     * @param int $limit
     * @param bool $showAll
     * @return void
     */
    protected function showPendingJobs($limit, $showAll)
    {
        $query = DB::table('jobs')
            ->select([
                'id',
                'queue',
                'payload',
                'attempts',
                'reserved_at',
                'available_at',
                'created_at'
            ]);

        if (!$showAll) {
            $query->whereNull('reserved_at');
        }

        $jobs = $query->orderBy('id')->limit($limit)->get();

        if ($jobs->isEmpty()) {
            $this->info('Заданий в очереди не найдено.');
            return;
        }

        $this->info('Список заданий в очереди:');
        $headers = ['ID', 'Очередь', 'Тип', 'Попытки', 'Статус', 'Доступно с', 'Создано'];

        $rows = [];
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $commandName = $payload['data']['commandName'] ?? 'Unknown';

            // Извлекаем имя класса
            $classParts = explode('\\', $commandName);
            $shortName = end($classParts);

            $status = $job->reserved_at
                ? 'В обработке'
                : 'Ожидает';

            $availableAt = Carbon::createFromTimestamp($job->available_at)
                ->format('Y-m-d H:i:s');

            $createdAt = Carbon::createFromTimestamp($job->created_at)
                ->format('Y-m-d H:i:s');

            $rows[] = [
                $job->id,
                $job->queue,
                $shortName,
                $job->attempts,
                $status,
                $availableAt,
                $createdAt
            ];
        }

        $this->table($headers, $rows);

        // Показываем общее количество заданий
        $totalCount = DB::table('jobs')->count();
        $pendingCount = DB::table('jobs')->whereNull('reserved_at')->count();
        $reservedCount = DB::table('jobs')->whereNotNull('reserved_at')->count();

        $this->line('');
        $this->line('Всего заданий: ' . $totalCount);
        $this->line('Ожидающих: ' . $pendingCount);
        $this->line('В обработке: ' . $reservedCount);
    }

    /**
     * Показать список неудачных заданий
     *
     * @param int $limit
     * @return void
     */
    protected function showFailedJobs($limit)
    {
        $jobs = DB::table('failed_jobs')
            ->select([
                'id',
                'uuid',
                'connection',
                'queue',
                'payload',
                'exception',
                'failed_at'
            ])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('Неудачных заданий не найдено.');
            return;
        }

        $this->info('Список неудачных заданий:');
        $headers = ['ID', 'UUID', 'Очередь', 'Тип', 'Ошибка', 'Дата сбоя'];

        $rows = [];
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $commandName = $payload['data']['commandName'] ?? 'Unknown';

            // Извлекаем имя класса
            $classParts = explode('\\', $commandName);
            $shortName = end($classParts);

            // Сокращаем текст ошибки
            $errorMessage = substr(str_replace("\n", " ", $job->exception), 0, 50);
            if (strlen($job->exception) > 50) {
                $errorMessage .= '...';
            }

            $rows[] = [
                $job->id,
                substr($job->uuid, 0, 8),
                $job->queue,
                $shortName,
                $errorMessage,
                $job->failed_at
            ];
        }

        $this->table($headers, $rows);

        // Показываем общее количество неудачных заданий
        $totalCount = DB::table('failed_jobs')->count();
        $this->line('');
        $this->line('Всего неудачных заданий: ' . $totalCount);

        if ($totalCount > 0) {
            $this->line('');
            $this->line('Для просмотра деталей неудачного задания:');
            $this->line('php artisan queue:failed-job {id}');
            $this->line('');
            $this->line('Для повторной попытки выполнения неудачного задания:');
            $this->line('php artisan queue:retry {id}');
        }
    }
}
