<?php

use App\Http\Controllers\Api\TrocaPosteController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpTaskController;
use App\Http\Controllers\Api\RompimentoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::apiResource('op-tasks', OpTaskController::class);
    Route::apiResource('rompimentos', RompimentoController::class);
    Route::get('rompimentos/{id}/os', [RompimentoController::class, 'listarOS']);
    Route::apiResource('tecnicos', TecnicoController::class);
    Route::put('os/{id}', [OpTaskController::class, 'update']);
    Route::get('troca-poste/coordenada', [TrocaPosteController::class, 'buscarEndereco']);
    Route::get('troca-poste/{id}/os', [TrocaPosteController::class, 'listarOS']);
    Route::apiResource('troca-poste', TrocaPosteController::class);
});