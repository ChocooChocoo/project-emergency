<?php

use Illuminate\Support\Facades\Route;

Route::get('/login',           [\App\Http\Controllers\Auth\LoginController::class,    'showForm'])->name('login');
Route::get('/register',        [\App\Http\Controllers\Auth\RegisterController::class, 'showForm'])->name('register');
Route::get('/forgot-password', [\App\Http\Controllers\Auth\LoginController::class,    'showForm'])->name('password.request');

Route::get('/dashboard',       fn () => view('dashboard'))->name('dashboard');
