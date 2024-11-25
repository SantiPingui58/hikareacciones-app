<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleDriveController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AuthController::class, 'index'])->name('index');
Route::get('auth/twitch', [AuthController::class, 'twitchLogin']);
Route::get('auth/twitch/callback', [AuthController::class, 'twitchCallback']);
Route::get('/panel', [AuthController::class, 'showPanel'])->name('panel');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/request-access', [GoogleDriveController::class, 'accessFromForm']);

