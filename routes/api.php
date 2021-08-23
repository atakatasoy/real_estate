<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;

use App\Http\Middleware\JWTAuthenticate;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware([JWTAuthenticate::class])->group(function() {
    Route::group(['prefix' => 'appointment'], function() {
        Route::post('/create', [AppointmentController::class, 'create']);
        Route::post('/update', [AppointmentController::class, 'update']);
        Route::get( '/remove', [AppointmentController::class, 'remove']);
    });
});
