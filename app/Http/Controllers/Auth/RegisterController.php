<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view("auth.register");
    }

    public function register(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255",
            "phone" => "required|string|max:20|regex:/^[0-9+\-\s\(\)]+$/",
            "password" => "required|string|min:8|confirmed",
        ]);

        // Проверка на пустой телефон после очистки от форматирования
        $cleanPhoneForCheck = preg_replace("/[^0-9+]/", "", $request->phone);
        if (empty($cleanPhoneForCheck)) {
            return back()
                ->withErrors([
                    "phone" =>
                        "Номер телефона не может быть пустым. Введите действительный номер телефона.",
                ])
                ->withInput();
        }

        // Очищаем номер телефона от форматирования
        $phone = preg_replace("/[^0-9+]/", "", $request->phone);

        // Нормализуем номер телефона в формат +7XXXXXXXXXX
        if (strlen($phone) >= 10) {
            if (substr($phone, 0, 1) === "8" && strlen($phone) === 11) {
                // Преобразуем 8XXXXXXXXXX в +7XXXXXXXXXX
                $phone = "+7" . substr($phone, 1);
            } elseif (
                substr($phone, 0, 1) === "7" &&
                strlen($phone) === 11 &&
                substr($phone, 0, 1) !== "+"
            ) {
                // Преобразуем 7XXXXXXXXXX в +7XXXXXXXXXX
                $phone = "+7" . substr($phone, 1);
            } elseif (
                strlen($phone) === 10 &&
                preg_match('/^9\d{9}$/', $phone)
            ) {
                // Преобразуем 9XXXXXXXXX в +79XXXXXXXXX
                $phone = "+7" . $phone;
            } elseif (substr($phone, 0, 1) !== "+" && strlen($phone) >= 10) {
                // Если нет +, добавляем +7
                $phone = "+7" . substr($phone, -10);
            }
        }

        // Проверяем, существует ли пользователь с таким телефоном
        $existingUser = User::where(function ($query) use ($phone) {
            $query->where("phone", $phone);

            // Проверка альтернативных форматов телефона
            if (substr($phone, 0, 2) === "+7") {
                $query->orWhere("phone", "8" . substr($phone, 2));
                $query->orWhere("phone", "7" . substr($phone, 2));
                $query->orWhere("phone", substr($phone, 1)); // без +
            }
        })->first();

        if ($existingUser) {
            return back()
                ->withErrors([
                    "phone" =>
                        "Этот номер телефона уже зарегистрирован в системе.",
                ])
                ->withInput();
        }

        // Находим роль пользователя
        $userRole = Role::where("slug", "user")->first();
        if (!$userRole) {
            // Если роль не найдена, используем первую доступную
            $userRole = Role::first();
            if (!$userRole) {
                return back()
                    ->withErrors([
                        "error" =>
                            "Ошибка при регистрации: роли пользователей не настроены",
                    ])
                    ->withInput();
            }
        }

        // Генерируем случайный email для совместимости с системой
        $randomEmail = $request->email ?? "user_" . time() . "@example.com";

        try {
            // Повторная проверка номера телефона перед созданием пользователя
            if (empty($phone) || !preg_match('/^[0-9+\-\s\(\)]+$/', $phone)) {
                return back()
                    ->withErrors([
                        "phone" =>
                            "Некорректный формат номера телефона. Пожалуйста, введите действительный номер телефона.",
                    ])
                    ->withInput();
            }

            $user = User::create([
                "name" => $request->name,
                "phone" => $phone,
                "email" => $randomEmail,
                "password" => Hash::make($request->password),
                "role_id" => $userRole->id,
                "is_active" => true,
            ]);

            Auth::login($user);

            return redirect()->route("home");
        } catch (\Exception $e) {
            Log::error(
                "Ошибка при регистрации пользователя: " . $e->getMessage(),
                [
                    "phone" => $phone,
                    "name" => $request->name,
                ],
            );

            return back()
                ->withErrors([
                    "error" =>
                        "Ошибка при регистрации. Пожалуйста, попробуйте еще раз или обратитесь к администратору.",
                ])
                ->withInput();
        }
    }
}
