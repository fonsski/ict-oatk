<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class SessionTimeout
{
    
     * Время неактивности в минутах, после которого пользователь будет автоматически разлогинен.
     * По умолчанию 30 минут, но может быть переопределено в .env файле.

    protected $timeout;

    public function __construct()
    {
        $this->timeout = config('session.lifetime', 30);
    }

    
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed

    public function handle(Request $request, Closure $next): Response
    {
        
        if (Auth::check()) {
            
            $lastActivity = Session::get('last_activity');

            
            if ($lastActivity) {
                
                $lastActivityTime = Carbon::createFromTimestamp($lastActivity);
                $currentTime = Carbon::now();

                
                if ($currentTime->diffInMinutes($lastActivityTime) >= $this->timeout) {
                    
                    Auth::logout();
                    Session::flush();
                    Session::regenerate();

                    
                    return redirect()->route('login.timeout');
                }
            }

            
            Session::put('last_activity', time());
        }

        return $next($request);
    }
}
