<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class InitializeStorageDirectories extends Command
{
    
     * The name and signature of the console command.
     *
     * @var string

    protected $signature = 'storage:init {--force : Force recreation of directories}';

    
     * The console command description.
     *
     * @var string

    protected $description = 'Initialize storage directories for uploads';

    
     * Required directories for application
     *
     * @var array

    protected $directories = [
        'public/knowledge/images',
        'public/knowledge/images/thumbnails',
        'public/faq/images',
        'public/faq/images/thumbnails',
        'public/uploads',
        'public/uploads/temp',
    ];

    
     * Execute the console command.

    public function handle()
    {
        $this->info('Initializing storage directories...');

        
        if (!file_exists(public_path('storage'))) {
            $this->warn('Storage symlink not found. Creating it now...');
            $this->call('storage:link');
        }

        
        $force = $this->option('force');
        $count = 0;

        foreach ($this->directories as $directory) {
            $path = storage_path('app/' . $directory);

            if (File::exists($path) && !$force) {
                $this->line("✓ <fg=green>{$directory}</> already exists");
                continue;
            }

            if (File::exists($path) && $force) {
                $this->line("♻ <fg=yellow>Recreating {$directory}</>");
                File::deleteDirectory($path);
            } else {
                $this->line("+ <fg=blue>Creating {$directory}</>");
            }

            File::makeDirectory($path, 0755, true, true);
            $count++;
        }

        
        $this->info('Setting proper permissions...');
        $storagePath = storage_path('app/public');

        if (PHP_OS_FAMILY !== 'Windows') {
            $this->exec("chmod -R 755 {$storagePath}");
            $this->exec("find {$storagePath} -type d -exec chmod 755 {} \\;");
            $this->exec("find {$storagePath} -type f -exec chmod 644 {} \\;");
        }

        if ($count > 0) {
            $this->info("✓ Created {$count} directories successfully!");
        } else {
            $this->info('✓ All directories already exist. Use --force to recreate them.');
        }

        $this->info('Storage directories initialized successfully!');
        $this->line('');
        $this->line('Public URL paths:');
        $this->line('  - Knowledge images: ' . url('storage/knowledge/images'));
        $this->line('  - FAQ images: ' . url('storage/faq/images'));
        $this->line('  - General uploads: ' . url('storage/uploads'));
    }

    
     * Execute a shell command
     *
     * @param string $command
     * @return void

    protected function exec($command)
    {
        $this->line(" > <fg=gray>{$command}</>");
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->warn('  Command failed with code ' . $returnVar);
        }
    }
}
