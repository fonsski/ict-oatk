<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerformanceAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:analyze 
                            {--table= : Analyze specific table}
                            {--show-queries : Show slow queries}
                            {--show-indexes : Show table indexes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database performance and suggest optimizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Database Performance Analysis');
        $this->line('');

        if ($this->option('show-indexes')) {
            $this->showTableIndexes();
        }

        if ($this->option('show-queries')) {
            $this->showSlowQueries();
        }

        if ($table = $this->option('table')) {
            $this->analyzeTable($table);
        } else {
            $this->analyzeAllTables();
        }

        $this->line('');
        $this->info('Performance analysis completed!');
    }

    /**
     * Analyze all tables
     */
    private function analyzeAllTables(): void
    {
        $tables = [
            'users',
            'tickets',
            'ticket_comments',
            'equipment',
            'rooms',
            'locations',
            'equipment_categories',
            'roles',
            'notifications'
        ];

        foreach ($tables as $table) {
            $this->analyzeTable($table);
        }
    }

    /**
     * Analyze specific table
     */
    private function analyzeTable(string $table): void
    {
        if (!Schema::hasTable($table)) {
            $this->error("Table {$table} does not exist");
            return;
        }

        $this->info("Analyzing table: {$table}");
        $this->line('');

        // Get table statistics
        $stats = $this->getTableStats($table);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Rows', number_format($stats['rows'])],
                ['Data Size', $this->formatBytes($stats['data_size'])],
                ['Index Size', $this->formatBytes($stats['index_size'])],
                ['Total Size', $this->formatBytes($stats['total_size'])],
            ]
        );

        // Check for missing indexes
        $this->checkMissingIndexes($table);

        $this->line('');
    }

    /**
     * Get table statistics
     */
    private function getTableStats(string $table): array
    {
        $result = DB::select("
            SELECT 
                table_rows as rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) as total_size,
                ROUND((data_length / 1024 / 1024), 2) as data_size,
                ROUND((index_length / 1024 / 1024), 2) as index_size
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ", [$table]);

        return $result[0] ?? [
            'rows' => 0,
            'total_size' => 0,
            'data_size' => 0,
            'index_size' => 0
        ];
    }

    /**
     * Check for missing indexes
     */
    private function checkMissingIndexes(string $table): void
    {
        $this->info('Checking for missing indexes...');

        $suggestions = [];

        switch ($table) {
            case 'users':
                $suggestions = $this->getUserIndexSuggestions();
                break;
            case 'tickets':
                $suggestions = $this->getTicketIndexSuggestions();
                break;
            case 'ticket_comments':
                $suggestions = $this->getTicketCommentIndexSuggestions();
                break;
            case 'equipment':
                $suggestions = $this->getEquipmentIndexSuggestions();
                break;
            case 'rooms':
                $suggestions = $this->getRoomIndexSuggestions();
                break;
        }

        if (!empty($suggestions)) {
            $this->table(['Field', 'Suggested Index', 'Reason'], $suggestions);
        } else {
            $this->info('No missing indexes detected for this table.');
        }
    }

    /**
     * Get user table index suggestions
     */
    private function getUserIndexSuggestions(): array
    {
        return [
            ['name', 'INDEX(name)', 'Search by user name'],
            ['phone', 'INDEX(phone)', 'Search by phone number'],
            ['is_active', 'INDEX(is_active)', 'Filter by active status'],
            ['role_id, is_active', 'INDEX(role_id, is_active)', 'Filter by role and status'],
        ];
    }

    /**
     * Get ticket table index suggestions
     */
    private function getTicketIndexSuggestions(): array
    {
        return [
            ['status', 'INDEX(status)', 'Filter by ticket status'],
            ['priority', 'INDEX(priority)', 'Filter by priority'],
            ['category', 'INDEX(category)', 'Filter by category'],
            ['user_id', 'INDEX(user_id)', 'Find tickets by user'],
            ['assigned_to_id', 'INDEX(assigned_to_id)', 'Find assigned tickets'],
            ['status, priority', 'INDEX(status, priority)', 'Complex filtering'],
            ['created_at', 'INDEX(created_at)', 'Sort by creation date'],
        ];
    }

    /**
     * Get ticket comment index suggestions
     */
    private function getTicketCommentIndexSuggestions(): array
    {
        return [
            ['ticket_id', 'INDEX(ticket_id)', 'Find comments by ticket'],
            ['user_id', 'INDEX(user_id)', 'Find comments by user'],
            ['ticket_id, created_at', 'INDEX(ticket_id, created_at)', 'Sort comments by date'],
        ];
    }

    /**
     * Get equipment index suggestions
     */
    private function getEquipmentIndexSuggestions(): array
    {
        return [
            ['name', 'INDEX(name)', 'Search by equipment name'],
            ['model', 'INDEX(model)', 'Search by model'],
            ['serial_number', 'INDEX(serial_number)', 'Search by serial number'],
            ['room_id', 'INDEX(room_id)', 'Find equipment by room'],
            ['status', 'INDEX(status)', 'Filter by status'],
        ];
    }

    /**
     * Get room index suggestions
     */
    private function getRoomIndexSuggestions(): array
    {
        return [
            ['number', 'INDEX(number)', 'Search by room number'],
            ['name', 'INDEX(name)', 'Search by room name'],
            ['building', 'INDEX(building)', 'Filter by building'],
            ['floor', 'INDEX(floor)', 'Filter by floor'],
            ['building, floor', 'INDEX(building, floor)', 'Complex filtering'],
        ];
    }

    /**
     * Show table indexes
     */
    private function showTableIndexes(): void
    {
        $this->info('Table Indexes:');
        $this->line('');

        $tables = ['users', 'tickets', 'ticket_comments', 'equipment', 'rooms'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $this->info("Indexes for table: {$table}");
            
            $indexes = DB::select("
                SELECT 
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    INDEX_TYPE
                FROM information_schema.STATISTICS 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ", [$table]);

            if (empty($indexes)) {
                $this->warn('No indexes found');
            } else {
                $this->table(
                    ['Index Name', 'Column', 'Unique', 'Type'],
                    array_map(function ($index) {
                        return [
                            $index->INDEX_NAME,
                            $index->COLUMN_NAME,
                            $index->NON_UNIQUE ? 'No' : 'Yes',
                            $index->INDEX_TYPE
                        ];
                    }, $indexes)
                );
            }
            $this->line('');
        }
    }

    /**
     * Show slow queries
     */
    private function showSlowQueries(): void
    {
        $this->info('Slow Queries Analysis:');
        $this->line('');

        try {
            $slowQueries = DB::select("
                SELECT 
                    query_time,
                    lock_time,
                    rows_sent,
                    rows_examined,
                    sql_text
                FROM mysql.slow_log 
                WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY query_time DESC 
                LIMIT 10
            ");

            if (empty($slowQueries)) {
                $this->info('No slow queries found in the last 24 hours');
            } else {
                $this->table(
                    ['Query Time', 'Lock Time', 'Rows Sent', 'Rows Examined', 'Query'],
                    array_map(function ($query) {
                        return [
                            $query->query_time,
                            $query->lock_time,
                            $query->rows_sent,
                            $query->rows_examined,
                            substr($query->sql_text, 0, 100) . '...'
                        ];
                    }, $slowQueries)
                );
            }
        } catch (\Exception $e) {
            $this->warn('Could not retrieve slow queries: ' . $e->getMessage());
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
