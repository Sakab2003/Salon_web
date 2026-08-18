<?php

use Illuminate\Support\Facades\Route;
use Modules\QuickBooking\Http\Controllers\Backend\QuickBookingsController;

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
Route::get('reservation-rapide', [QuickBookingsController::class, 'index'])->where('vue_capture', '^(?!storage).*$')->name('app.quick-booking');
Route::get('quick-booking', function() { return redirect()->route('app.quick-booking'); });
