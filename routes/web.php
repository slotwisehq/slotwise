<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

require __DIR__.'/booking.php';
require __DIR__.'/admin.php';
