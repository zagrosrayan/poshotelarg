<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $role
     * @param  string|null  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null, $permission = null)
    {
        $user = Auth::user();

        // اگر کاربر لاگین نکرده باشد
        if (!$user) {
            abort(403, 'شما به این بخش دسترسی ندارید.');
        }

        // بررسی نقش
        if ($role && !$user->hasRole($role)) {
            abort(403, 'شما به این بخش دسترسی ندارید.');
        }

        // بررسی مجوز
        if ($permission && !$user->can($permission)) {
            abort(403, 'شما به این بخش دسترسی ندارید.');
        }

        return $next($request);
    }
}
