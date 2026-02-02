<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\WebSocketServer;

class WebSocketServerCommand extends Command
{
    
     * The name and signature of the console command.
     *
     * @var string

    protected $signature = 'websocket:serve {--port=8080 : Port to run the WebSocket server on}';

    
     * The console command description.
     *
     * @var string

    protected $description = 'Start the WebSocket server for real-time updates';

    
     * Execute the console command.

    public function handle()
    {
        $port = $this->option('port');
        
        $this->info("Starting WebSocket server on port $port...");
        $this->info("Press Ctrl+C to stop the server");
        
        try {
            WebSocketServer::start($port);
        } catch (\Exception $e) {
            $this->error("Failed to start WebSocket server: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
