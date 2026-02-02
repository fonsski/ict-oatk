<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class SettingsController extends Controller
{
    
     * Отображение страницы настроек профиля
     *
     * @return \Illuminate\View\View

    public function index()
    {
        $user = Auth::user();
        return view("settings.index", compact("user"));
    }

    
     * Отображение формы смены пароля
     *
     * @return \Illuminate\View\View

    public function showChangePasswordForm()
    {
        return view("settings.change-password");
    }

    
     * Обработка смены пароля
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse

    public function changePassword(Request $request)
    {
        $request->validate(
            [
                "current_password" => ["required", "string"],
                "new_password" => [
                    "required",
                    "string",
                    "confirmed",
                    Password::min(8),
                ],
            ],
            [
                "current_password.required" => "Введите текущий пароль",
                "new_password.required" => "Введите новый пароль",
                "new_password.confirmed" => "Пароли не совпадают",
                "new_password.min" =>
                    "Пароль должен содержать минимум :min символов",
            ],
        );

        $userId = Auth::id();
        $user = User::find($userId);

        if (!$user) {
            return back()->with("error", "Пользователь не найден");
        }

        
        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors([
                    "current_password" => "Неверный текущий пароль",
                ])
                ->withInput();
        }

        
        if (Hash::check($request->new_password, $user->password)) {
            return back()
                ->withErrors([
                    "new_password" =>
                        "Новый пароль должен отличаться от текущего",
                ])
                ->withInput();
        }

        
        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()
            ->route("settings.index")
            ->with("success", "Пароль успешно изменен");
    }

    
     * Отображение документации по исправлению ошибок OAuth
     *
     * @param string $docName Имя документации
     * @return \Illuminate\Http\Response

    public function viewDocumentation($docName)
    {
        $path = base_path("docs/{$docName}.md");

        if (!File::exists($path)) {
            abort(404, "Документация не найдена");
        }

        $content = File::get($path);

        
        return Response::make($content, 200, [
            "Content-Type" => "text/markdown",
            "Content-Disposition" => 'inline; filename="' . $docName . '.md"',
        ]);
    }
}
