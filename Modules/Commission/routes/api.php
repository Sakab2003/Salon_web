<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Backend\API\CommissionController;

// Route publique — liste filtrée pour le dropdown (formulaire ajout employé)
Route::get('commission-list', [CommissionController::class, 'commissionList']);

// Routes CRUD — gestion des commissions depuis l'app mobile (managers uniquement)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('commissions', [CommissionController::class, 'allCommissions']);
    Route::post('commissions', [CommissionController::class, 'store']);
    Route::post('commissions/{id}', [CommissionController::class, 'update']);
    Route::delete('commissions/{id}', [CommissionController::class, 'destroy']);
});
