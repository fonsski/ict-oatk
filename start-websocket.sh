#!/bin/bash

# WebSocket Server Startup Script
# This script starts the WebSocket server for real-time notifications

echo "🚀 Starting WebSocket Server for ICT System"
echo "=============================================="

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed or not in PATH"
    exit 1
fi

# Check if Laravel is available
if [ ! -f "artisan" ]; then
    echo "❌ Laravel artisan file not found. Please run this script from the project root."
    exit 1
fi

# Get port from environment or use default
PORT=${WEBSOCKET_PORT:-8080}

echo "📡 Starting WebSocket server on port $PORT..."
echo "🔗 WebSocket URL: ws://localhost:$PORT"
echo "🌐 HTTP endpoints:"
echo "   - Test: http://localhost:$PORT/test"
echo "   - Status: http://localhost:$PORT/status"
echo "   - Broadcast: http://localhost:$PORT/broadcast"
echo ""
echo "Press Ctrl+C to stop the server"
echo "=============================================="

# Start the WebSocket server
php artisan websocket:serve --port=$PORT
