<?php

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/user', function () {
  $guest = \App\Models\GuestUser::all();
  return [$guest];
});

Route::get('/test-logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (!file_exists($logFile)) {
        return 'Log file not found.';
    }
    $content = file_get_contents($logFile);
    return response($content)->header('Content-Type', 'text/plain; charset=utf-8');
});

