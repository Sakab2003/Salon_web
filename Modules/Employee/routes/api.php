<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Backend\API\EmployeeController;
use Modules\Employee\Http\Controllers\Backend\API\MobileStaffController;

Route::get('employee-list', [EmployeeController::class, 'employeeList']);
Route::get('employee-detail', [EmployeeController::class, 'employeeDetail']);
Route::get('get-rating', [EmployeeController::class, 'getRating']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('employee-reviews', [EmployeeController::class, 'employeeList']);
    Route::post('save-rating', [EmployeeController::class, 'saveRating']);
    Route::post('delete-rating', [EmployeeController::class, 'deleteRating']);

    Route::prefix('staff-manage')->group(function () {
        Route::get('list', [MobileStaffController::class, 'staffList']);
        Route::post('store', [MobileStaffController::class, 'store']);
        Route::post('update/{id}', [MobileStaffController::class, 'update']);
        Route::delete('delete/{id}', [MobileStaffController::class, 'destroy']);
        Route::post('toggle-status/{id}', [MobileStaffController::class, 'toggleStatus']);
    });
});
