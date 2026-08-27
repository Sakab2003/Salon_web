<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Backend\API\CommissionController;

Route::get('commission-list', [CommissionController::class, 'commissionList']);
