<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|string[]  $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        $user = Auth::user();

        // اگر کاربر ادمین باشد، دسترسی به همه روت‌ها دارد
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // بررسی رول‌های دیگر
        $roles = is_array($roles) ? $roles : explode('|', $roles);
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        // اگر کاربر دسترسی نداشته باشد
        abort(403, 'شما اجازه دسترسی به این بخش را ندارید');
    }
}
