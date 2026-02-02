<?php

namespace App\WebSocket;

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

class WebSocketServer
{
    protected $clients = [];
    protected $loop;
    protected $startTime;

    public function __construct()
    {
        $this->loop = Loop::get();
    }

    
     * Handle client connection

    public function handleConnection($conn)
    {
        $clientId = uniqid();
        $this->clients[$clientId] = $conn;
        
        echo "New connection! (ID: $clientId)\n";

        $conn->on('data', function ($data) use ($clientId) {
            echo "Received data from client $clientId: $data\n";
            
            if (isset($this->clients[$clientId])) {
                $this->clients[$clientId]->write($data);
            }
        });

        $conn->on('close', function () use ($clientId) {
            echo "Connection $clientId has disconnected\n";
            unset($this->clients[$clientId]);
        });

        $conn->on('error', function ($error) use ($clientId) {
            echo "Error for client $clientId: " . $error->getMessage() . "\n";
            unset($this->clients[$clientId]);
        });
    }

    
     * Broadcast message to all connected clients

    public function broadcast($message)
    {
        $messageStr = is_string($message) ? $message : json_encode($message);
        echo "Broadcasting message to " . count($this->clients) . " clients: $messageStr\n";
        
        foreach ($this->clients as $clientId => $client) {
            try {
                $client->write($messageStr . "\n");
            } catch (\Exception $e) {
                echo "Error sending to client $clientId: " . $e->getMessage() . "\n";
                unset($this->clients[$clientId]);
            }
        }
    }

    
     * Broadcast message to specific user (if user tracking is implemented)

    public function broadcastToUser($userId, $message)
    {
        $messageStr = is_string($message) ? $message : json_encode($message);
        echo "Broadcasting message to user $userId: $messageStr\n";
        
        
        
        $this->broadcast($message);
    }

    
     * Get connected clients count

    public function getConnectedClientsCount()
    {
        return count($this->clients);
    }

    
     * Get server status

    public function getStatus()
    {
        return [
            'connected_clients' => count($this->clients),
            'server_time' => now()->toISOString(),
            'uptime' => time() - $this->startTime,
        ];
    }

    
     * Start the WebSocket server

    public static function start($port = 8080)
    {
        $loop = Loop::get();
        $socket = new SocketServer("0.0.0.0:$port", [], $loop);
        
        $wsInstance = new self();
        $wsInstance->startTime = time();
        self::$instance = $wsInstance;

        
        $httpServer = new HttpServer($loop, function (ServerRequestInterface $request) {
            $path = $request->getUri()->getPath();
            $method = $request->getMethod();

            if ($path === '/broadcast' && $method === 'POST') {
                $body = $request->getBody()->getContents();
                $data = json_decode($body, true);
                
                if ($data && isset($data['message'])) {
                    
                    $wsInstance = self::getInstance();
                    if ($wsInstance) {
                        $wsInstance->broadcast(json_encode($data['message']));
                    }
                    
                    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
                }
                
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid message format']));
            }
            
            if ($path === '/test' && $method === 'GET') {
                return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'message' => 'WebSocket server is running']));
            }
            
            if ($path === '/messages' && $method === 'GET') {
                
                return new Response(200, ['Content-Type' => 'application/json'], json_encode(['messages' => []]));
            }
            
            if ($path === '/status' && $method === 'GET') {
                $wsInstance = self::getInstance();
                $status = $wsInstance ? $wsInstance->getStatus() : ['error' => 'Server not initialized'];
                return new Response(200, ['Content-Type' => 'application/json'], json_encode($status));
            }
            
            if ($path === '/clients' && $method === 'GET') {
                $wsInstance = self::getInstance();
                $count = $wsInstance ? $wsInstance->getConnectedClientsCount() : 0;
                return new Response(200, ['Content-Type' => 'application/json'], json_encode(['connected_clients' => $count]));
            }
            
            return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Not found']));
        });

        
        $socket->on('connection', function ($conn) use ($wsInstance) {
            $wsInstance->handleConnection($conn);
        });

        
        $httpServer->listen($socket);

        echo "WebSocket server started on port $port\n";
        echo "HTTP endpoints available:\n";
        echo "  - Broadcast: http:
        echo "  - Test: http:
        echo "  - Status: http:
        echo "  - Clients: http:
        echo "  - Messages: http:
        
        $loop->run();
    }

    
     * Get singleton instance for broadcast

    private static $instance;
    
    public static function getInstance()
    {
        return self::$instance;
    }
}
