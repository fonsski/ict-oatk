<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldNotifications extends Command
{
    
     * The name and signature of the console command.
     *
     * @var string

    protected $signature = 'notifications:cleanup {--days=30 : Number of days to keep notifications}';

    
     * The console command description.
     *
     * @var string

    protected $description = 'Clean up old notifications from the database';

    
     * Execute the console command.

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $deletedCount = DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Cleaned up {$deletedCount} notifications older than {$days} days.");

        return 0;
    }
}
