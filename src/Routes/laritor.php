<?php


use Illuminate\Support\Facades\Route;

Route::get('/hc/{check_type}', [\Laritor\LaravelClient\Controllers\HealthCheckController::class, 'check'])->name('laritor.health-check');