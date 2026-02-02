<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

// Маршрут для обработки входящих сообщений от Telegram
Route::any("/telegram/webhook", [TelegramController::class, "webhook"]);

// Тестовый маршрут для проверки доступности API
Route::get("/telegram/test", [TelegramController::class, "test"]);
