<?php

use App\Http\Controllers\TelegramBotController;
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
Route::any("/telegram/webhook", [TelegramBotController::class, "handle"]);

// Маршрут для исправленной версии контроллера
Route::any("/telegram/webhook-fixed", [
    TelegramBotControllerFixed::class,
    "handle",
]);

// Тестовый маршрут для проверки доступности API
Route::get("/telegram/test", function () {
    return response()->json([
        "status" => "ok",
        "message" => "Telegram API test route is working",
    ]);
});
