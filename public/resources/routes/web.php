<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\UserList;
use Illuminate\Support\Facades\Schema;

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
    $users = User::all(); // دریافت همه کاربران
    foreach ($users as $user) {
        // چک کنید آیا رمز عبور هش نشده است
        if (!Hash::needsRehash($user->password)) {
            continue; // اگر هش است، نیازی به تغییر نیست
        }

        // هش کردن رمز عبور و به‌روزرسانی
        $user->password = Hash::make($user->password);
        $user->save();
    }

    return "Passwords updated successfully!";
});
Route::get('/', function () {
    return 'ok';
});
