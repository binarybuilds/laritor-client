<?php


use Illuminate\Support\Facades\Route;

Route::get('/hc/{check_type}', [\BinaryBuilds\LaritorClient\Controllers\HealthCheckController::class, 'check'])->name('laritor.health-check');