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
Route::post('logout',[AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::apiResource('op-tasks',OpTaskController::class);
Route::get('op-tasks/{opTask}',[OpTaskController::class, 'show'])->middleware('auth:sanctum');
Route::put('op-tasks/{opTask}',[OpTaskController::class, 'update'])->middleware('auth:sanctum');
Route::delete('op-tasks/{opTask}',[OpTaskController::class, 'destroy'])->middleware('auth:sanctum');