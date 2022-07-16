<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LineController;

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
Route::get('/', function () {
    return view('welcome');
});

Route::post('line/webhook', [LineController::class, 'webhook'])->name('line.webhook');
Auth::routes(['register' => false]);


Route::group(['middleware' => 'auth'], function () {
    Route::get('/home', [App\Http\Controllers\MessageMstController::class, 'index'])->name('home');
    Route::post('/home', [App\Http\Controllers\MessageMstController::class, 'store'])->name('store');
});
