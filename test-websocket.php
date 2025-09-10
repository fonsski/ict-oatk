<?php

/**
 * Простой тест для проверки WebSocket сервера
 * Запускать: php test-websocket.php
 */

require_once 'vendor/autoload.php';

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

echo "🧪 Тестирование WebSocket сервера...\n";

$loop = Loop::get();
$socket = new SocketServer("0.0.0.0:8080", [], $loop);

// Создаем HTTP сервер для тестирования
$httpServer = new HttpServer($loop, function (ServerRequestInterface $request) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();

    echo "📡 Получен запрос: {$method} {$path}\n";

    if ($path === '/broadcast' && $method === 'POST') {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        
        if ($data && isset($data['message'])) {
            echo "✅ Broadcast сообщение получено: " . json_encode($data['message']) . "\n";
            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
        }
        
        echo "❌ Неверный формат сообщения\n";
        return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid message format']));
    }
    
    if ($path === '/test' && $method === 'GET') {
        echo "✅ Тестовый endpoint работает\n";
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'message' => 'WebSocket server is running']));
    }
    
    echo "❌ Неизвестный endpoint: {$path}\n";
    return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Not found']));
});

$httpServer->listen($socket);

echo "🚀 Тестовый WebSocket сервер запущен на порту 8080\n";
echo "📋 Доступные endpoints:\n";
echo "   GET  http://localhost:8080/test - тест сервера\n";
echo "   POST http://localhost:8080/broadcast - отправка сообщения\n";
echo "   WebSocket ws://localhost:8080 - WebSocket соединение\n";
echo "\n";
echo "💡 Для тестирования:\n";
echo "   curl http://localhost:8080/test\n";
echo "   curl -X POST http://localhost:8080/broadcast -H 'Content-Type: application/json' -d '{\"message\":{\"type\":\"test\",\"data\":{\"message\":\"Hello World\"}}}'\n";
echo "\n";
echo "⏹️  Для остановки нажмите Ctrl+C\n";

$loop->run();
