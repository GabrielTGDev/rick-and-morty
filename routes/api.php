<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\FavoriteCharacterController;
use App\Http\Middleware\AuthenticateWithApiToken;
use Illuminate\Support\Facades\Route;

// Rutas Públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/characters', [CharacterController::class, 'index']);
Route::get('/characters/{id}', [CharacterController::class, 'show']);

// Rutas Protegidas por Token Propio
Route::middleware(AuthenticateWithApiToken::class)->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user/favorites', [FavoriteCharacterController::class, 'index']);
    Route::post('/characters/{id}/favorite', [FavoriteCharacterController::class, 'store']);
    Route::delete('/characters/{id}/favorite', [FavoriteCharacterController::class, 'destroy']);
});