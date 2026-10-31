<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImporController;
use App\Http\Controllers\PemohonController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/data', [PemohonController::class, 'index'])->name('data.index');
Route::get('/data/{pemohon}', [PemohonController::class, 'show'])->name('data.show');

// TAHAP LOGIN NANTI: pindahkan semua route di bawah ini ke dalam grup
// Route::middleware('auth')->group(function () { ... });
// (impor mengubah data, jadi harus di balik login)
Route::get('/impor', [ImporController::class, 'form'])->name('impor.form');
Route::post('/impor', [ImporController::class, 'proses'])->name('impor.proses');
