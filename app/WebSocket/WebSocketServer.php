<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $httpServer;

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Broadcast message to all connected clients
     */
    public function broadcast($message)
    {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    /**
     * Start the WebSocket server
     */
    public static function start($port = 8080)
    {
        $loop = Loop::get();
        $socket = new SocketServer("0.0.0.0:$port", [], $loop);
        
        $wsServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new self()
                )
            ),
            $socket,
            $loop
        );

        // Создаем HTTP сервер для broadcast endpoint
        $httpServer = new ReactHttpServer($loop, function (ServerRequestInterface $request) {
            $path = $request->getUri()->getPath();
            $method = $request->getMethod();

            if ($path === '/broadcast' && $method === 'POST') {
                $body = $request->getBody()->getContents();
                $data = json_decode($body, true);
                
                if ($data && isset($data['message'])) {
                    // Broadcast сообщение всем подключенным клиентам
                    $wsInstance = self::getInstance();
                    if ($wsInstance) {
                        $wsInstance->broadcast(json_encode($data['message']));
                    }
                    
                    return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
                }
                
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid message format']));
            }
            
            return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Not found']));
        });

        // Запускаем HTTP сервер на том же порту
        $httpServer->listen($socket);

        echo "WebSocket server started on port $port\n";
        echo "HTTP broadcast endpoint available at http://localhost:$port/broadcast\n";
        
        $wsServer->run();
    }

    /**
     * Get singleton instance for broadcast
     */
    private static $instance;
    
    public static function getInstance()
    {
        return self::$instance;
    }
    
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        self::$instance = $this;
    }
}
