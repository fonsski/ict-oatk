<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view("auth.register");
    }

    public function register(Request $request)
    {
        $request->validate(
            [
                "name" => "required|string|max:255",
                "phone" => [
                    "required",
                    "string",
                    "max:20",
                    "unique:users",
                    'regex:/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/',
                ],
                "password" => "required|string|min:8|confirmed",
            ],
            [
                "phone.regex" =>
                    "Номер телефона должен быть в формате: +7 (999) 999-99-99",
            ],
        );

        $userRole = Role::where("slug", "user")->first();

        // Генерируем случайный email для совместимости с системой
        $randomEmail = $request->email ?? "user_" . time() . "@example.com";

        $user = User::create([
            "name" => $request->name,
            "phone" => $request->phone,
            "email" => $randomEmail,
            "password" => Hash::make($request->password),
            "role_id" => $userRole->id,
        ]);

        Auth::login($user);

        return redirect()->route("home");
    }
}
