<?php

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Опознание гостя, подавшего заявку без входа в систему.
 *
 * Посетителю кладётся долгоживущая cookie со случайной меткой, та же метка
 * пишется в заявку. По ней человек потом видит свои обращения и может их
 * поправить.
 *
 * Метка — это секрет на предъявителя: у кого cookie, тот и видит заявки.
 * Поэтому она длинная и случайная, cookie ставится httpOnly (недоступна
 * скриптам) и шифруется штатной защитой Laravel.
 */
class GuestTicketOwner
{
    public const COOKIE = "ticket_owner";

    /** Cookie::forever — пять лет; заявку могут вспомнить и через год. */
    private const LIFETIME_MINUTES = 60 * 24 * 365 * 5;

    /**
     * Метка текущего посетителя, если он уже подавал заявки.
     */
    public static function token(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        // Мусор в cookie игнорируем: метку выдаём только мы.
        return is_string($token) && strlen($token) === 64 ? $token : null;
    }

    /**
     * Метка для новой заявки: продолжаем уже выданную или заводим новую.
     */
    public static function tokenForNewTicket(Request $request): string
    {
        return self::token($request) ?? Str::random(64);
    }

    /**
     * Cookie, которую нужно вернуть вместе с ответом.
     */
    public static function cookie(string $token): SymfonyCookie
    {
        return Cookie::make(
            name: self::COOKIE,
            value: $token,
            minutes: self::LIFETIME_MINUTES,
            httpOnly: true,
            sameSite: "Lax",
        );
    }

    /**
     * Заявка принадлежит текущему гостю?
     */
    public static function owns(Request $request, Ticket $ticket): bool
    {
        $token = self::token($request);

        return $token !== null &&
            $ticket->guest_token !== null &&
            hash_equals($ticket->guest_token, $token);
    }
}
