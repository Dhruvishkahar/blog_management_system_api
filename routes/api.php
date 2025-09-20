<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::group(['prefix' => 'blogs'], function () {
        Route::get('/', [BlogController::class, 'index']);
        Route::post('create', [BlogController::class, 'store']);
        Route::post('update/{id}', [BlogController::class, 'update']);
        Route::post('delete', [BlogController::class, 'delete']);

        Route::post('{blogId}/toggle-like', [BlogController::class, 'BlogLikeUnlikeToggle']);
    });
});



