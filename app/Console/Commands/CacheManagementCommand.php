<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage 
                            {action : Action to perform (warm|clear|stats|clear-all)}
                            {--type= : Type of cache to clear (roles|locations|rooms|equipment_categories|active_equipment|assignable_users|ticket_categories|ticket_priorities|ticket_statuses)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application cache (warm up, clear, show stats)';

    protected CacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'warm':
                $this->warmUpCache();
                break;
            case 'clear':
                $this->clearCache();
                break;
            case 'stats':
                $this->showCacheStats();
                break;
            case 'clear-all':
                $this->clearAllCache();
                break;
            default:
                $this->error('Invalid action. Available actions: warm, clear, stats, clear-all');
                return 1;
        }

        return 0;
    }

    /**
     * Warm up cache
     */
    private function warmUpCache(): void
    {
        $this->info('Warming up cache...');
        
        try {
            $this->cacheService->warmUpCache();
            $this->info('Cache warmed up successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to warm up cache: ' . $e->getMessage());
        }
    }

    /**
     * Clear specific cache type
     */
    private function clearCache(): void
    {
        $type = $this->option('type');
        
        if (!$type) {
            $this->error('Please specify cache type with --type option');
            $this->info('Available types: roles, locations, rooms, equipment_categories, active_equipment, assignable_users, ticket_categories, ticket_priorities, ticket_statuses');
            return;
        }

        $this->info("Clearing cache for type: {$type}");
        
        try {
            $this->cacheService->clearCache($type);
            $this->info("Cache cleared for type: {$type}");
        } catch (\Exception $e) {
            $this->error('Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Clear all cache
     */
    private function clearAllCache(): void
    {
        $this->info('Clearing all cache...');
        
        try {
            $this->cacheService->clearAllCache();
            $this->info('All cache cleared successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to clear all cache: ' . $e->getMessage());
        }
    }

    /**
     * Show cache statistics
     */
    private function showCacheStats(): void
    {
        $this->info('Cache Statistics:');
        $this->line('');

        try {
            $stats = $this->cacheService->getCacheStats();
            
            $headers = ['Type', 'Key', 'TTL (seconds)', 'Exists'];
            $rows = [];

            foreach ($stats as $type => $stat) {
                $rows[] = [
                    $type,
                    $stat['key'],
                    $stat['ttl'],
                    $stat['exists'] ? 'Yes' : 'No'
                ];
            }

            $this->table($headers, $rows);
        } catch (\Exception $e) {
            $this->error('Failed to get cache stats: ' . $e->getMessage());
        }
    }
}
