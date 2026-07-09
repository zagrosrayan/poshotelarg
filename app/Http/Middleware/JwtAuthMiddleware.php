<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class JwtAuthMiddleware
{
    private $secretKey = "hotel_arg_yazd_256_made_by_zagrosrayanco.com_1234567890!@#$%^&*()";

    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return Response::json(['error' => 'Token not provided'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $request->user = $decoded->sub;
        } catch (ExpiredException $e) {
            return Response::json(['error' => 'Token has expired'], 401);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
