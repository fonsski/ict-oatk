<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\RegisterRequest;
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

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        
        $cleanPhoneForCheck = preg_replace("/[^0-9+]/", "", $data['phone']);
        if (empty($cleanPhoneForCheck)) {
            return back()
                ->withErrors([
                    "phone" =>
                        "Номер телефона не может быть пустым. Введите действительный номер телефона.",
                ])
                ->withInput();
        }

        
        $phone = preg_replace("/[^0-9+]/", "", $data['phone']);

        
        if (strlen($phone) >= 10) {
            if (substr($phone, 0, 1) === "8" && strlen($phone) === 11) {
                
                $phone = "+7" . substr($phone, 1);
            } elseif (
                substr($phone, 0, 1) === "7" &&
                strlen($phone) === 11 &&
                substr($phone, 0, 1) !== "+"
            ) {
                
                $phone = "+7" . substr($phone, 1);
            } elseif (
                strlen($phone) === 10 &&
                preg_match('/^9\d{9}$/', $phone)
            ) {
                
                $phone = "+7" . $phone;
            } elseif (substr($phone, 0, 1) !== "+" && strlen($phone) >= 10) {
                
                $phone = "+7" . substr($phone, -10);
            }
        }

        
        $existingUser = User::where(function ($query) use ($phone) {
            $query->where("phone", $phone);

            
            if (substr($phone, 0, 2) === "+7") {
                $query->orWhere("phone", "8" . substr($phone, 2));
                $query->orWhere("phone", "7" . substr($phone, 2));
                $query->orWhere("phone", substr($phone, 1)); 
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

        
        $userRole = Role::where("slug", "user")->first();
        if (!$userRole) {
            
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

        
        $randomEmail = $request->email ?? "user_" . time() . "@example.com";

        try {
            
            if (empty($phone) || !preg_match('/^[0-9+\-\s\(\)]+$/', $phone)) {
                return back()
                    ->withErrors([
                        "phone" =>
                            "Некорректный формат номера телефона. Пожалуйста, введите действительный номер телефона.",
                    ])
                    ->withInput();
            }

            $user = User::create([
                "name" => $data['name'],
                "phone" => $phone,
                "email" => $randomEmail,
                "password" => Hash::make($data['password']),
                "role_id" => $userRole->id,
                "is_active" => false,
            ]);

            

            return redirect()
                ->route("login")
                ->with(
                    "success",
                    "Регистрация успешно завершена. Ваш аккаунт ожидает активации администратором системы.",
                );
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
