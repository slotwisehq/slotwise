<?php

use App\Http\Controllers\Booking\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('book/{tenant:slug}')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'show'])->name('show');
    Route::get('/confirmation/{appointment}', [BookingController::class, 'confirmation'])->name('confirmation');
    Route::get('/{service}', [BookingController::class, 'selectStaff'])->name('staff');
    Route::get('/{service}/{staff}', [BookingController::class, 'selectSlot'])->name('slots');
    Route::get('/{service}/{staff}/book', [BookingController::class, 'showCustomerForm'])->name('form');
    Route::post('/{service}/{staff}/book', [BookingController::class, 'store'])->name('store');
});
