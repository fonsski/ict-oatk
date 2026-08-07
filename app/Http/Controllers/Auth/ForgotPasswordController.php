<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /** Ключ, под которым в сессии живут данные сброса. */
    private const SESSION_KEY = "password_reset";

    /** Сколько живёт код. Полчаса для шестизначного кода — многовато. */
    private const CODE_TTL_MINUTES = 15;

    /** Сколько раз можно ошибиться в коде, прежде чем он аннулируется. */
    private const MAX_ATTEMPTS = 5;

    /**
     * Показать форму для запроса сброса пароля
     */
    public function showLinkRequestForm()
    {
        return view("auth.passwords.request");
    }

    /**
     * Отправить код для сброса пароля
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            "email" => "required|string|email",
        ]);

        $user = User::where("email", $request->email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            $request->session()->put(self::SESSION_KEY, [
                // В сессии держим только хеш: даже если её содержимое
                // куда-то утечёт, восстановить код по нему нельзя.
                "code_hash" => Hash::make($code),
                "user_id" => $user->id,
                "email" => $user->email,
                "created_at" => now(),
                "attempts" => 0,
                // Ключевой признак: пароль меняем только после того, как
                // код действительно введён и проверен.
                "verified" => false,
            ]);

            try {
                $user->notify(new PasswordResetNotification($code));

                // Сам код в журнал не пишем: у кого доступ к логам, тот
                // иначе может сменить пароль любому сотруднику.
                Log::info("Отправлен код сброса пароля", [
                    "user_id" => $user->id,
                ]);
            } catch (\Throwable $e) {
                Log::error("Не удалось отправить код сброса пароля", [
                    "user_id" => $user->id,
                    "error" => $e->getMessage(),
                ]);
            }
        } else {
            // Отвечаем одинаково независимо от того, есть такой адрес или
            // нет: иначе форма превращается в проверку «а кто у вас есть».
            Log::info("Запрошен сброс пароля для неизвестного адреса");
        }

        return redirect()
            ->route("password.code")
            ->with(
                "status",
                "Если такой адрес зарегистрирован, код подтверждения отправлен на почту.",
            );
    }

    /**
     * Показать форму для ввода кода сброса пароля
     */
    public function showResetCodeForm()
    {
        // Форму показываем всегда: скрывать её при отсутствии сессии значило
        // бы подсказывать, существует ли введённый адрес.
        return view("auth.passwords.code");
    }

    /**
     * Проверить код сброса пароля
     */
    public function validateResetCode(Request $request)
    {
        $request->validate([
            "code" => "required|numeric|digits:6",
        ]);

        $reset = $this->activeReset($request);

        if (!$reset) {
            return $this->expired();
        }

        // Считаем попытки: шестизначный код без ограничения перебирается.
        $reset["attempts"]++;

        if ($reset["attempts"] > self::MAX_ATTEMPTS) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route("password.request")
                ->withErrors([
                    "code" => "Слишком много неверных попыток. Запросите новый код.",
                ]);
        }

        if (!Hash::check($request->code, $reset["code_hash"])) {
            $request->session()->put(self::SESSION_KEY, $reset);

            return back()->withErrors([
                "code" => "Неверный код подтверждения.",
            ]);
        }

        $reset["verified"] = true;
        $request->session()->put(self::SESSION_KEY, $reset);

        return redirect()->route("password.reset");
    }

    /**
     * Показать форму для создания нового пароля
     */
    public function showResetForm(Request $request)
    {
        $reset = $this->activeReset($request);

        if (!$reset || !$reset["verified"]) {
            return redirect()->route("password.request");
        }

        return view("auth.passwords.reset");
    }

    /**
     * Сбросить пароль
     */
    public function reset(Request $request)
    {
        $request->validate([
            "password" => "required|string|min:8|confirmed",
        ]);

        $reset = $this->activeReset($request);

        if (!$reset) {
            return $this->expired();
        }

        // Без этой проверки код можно было просто пропустить: достаточно
        // было запросить сброс на чужой адрес и сразу отправить новый
        // пароль на этот маршрут.
        if (!$reset["verified"]) {
            Log::warning("Попытка сменить пароль без подтверждения кодом", [
                "user_id" => $reset["user_id"],
            ]);

            return redirect()
                ->route("password.request")
                ->withErrors([
                    "general" => "Сначала подтвердите код из письма.",
                ]);
        }

        $user = User::find($reset["user_id"]);

        // Адрес мог смениться, пока код был на руках.
        if (!$user || $user->email !== $reset["email"]) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route("password.request")
                ->withErrors([
                    "general" => "Данные для сброса пароля недействительны.",
                ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        Log::info("Пароль сброшен", ["user_id" => $user->id]);

        // Код одноразовый: второй раз им воспользоваться нельзя.
        $request->session()->forget(self::SESSION_KEY);

        auth()->login($user);

        // Новый идентификатор сессии после смены пароля — иначе выданный
        // ранее идентификатор остался бы годным (session fixation).
        $request->session()->regenerate();

        return redirect()
            ->route("home")
            ->with("status", "Ваш пароль был успешно изменен.");
    }

    /**
     * Данные сброса, если они есть и не просрочены.
     */
    private function activeReset(Request $request): ?array
    {
        $reset = $request->session()->get(self::SESSION_KEY);

        if (!is_array($reset)) {
            return null;
        }

        if ($reset["created_at"]->lt(now()->subMinutes(self::CODE_TTL_MINUTES))) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $reset;
    }

    private function expired()
    {
        return redirect()
            ->route("password.request")
            ->withErrors([
                "code" => "Срок действия кода истёк. Запросите новый код.",
            ]);
    }
}
