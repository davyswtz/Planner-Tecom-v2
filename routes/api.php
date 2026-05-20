<?php

use App\Models\OpTask;
use App\Http\Controllers\Api\OpTaskController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login',[AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('logout',[AuthController::class, 'logout']);
    Route::apiResource('op-tasks',OpTaskController::class);
});