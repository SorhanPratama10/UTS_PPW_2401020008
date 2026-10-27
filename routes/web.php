<?php

use App\Http\Controllers\BukuController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('buku.index'));

Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');
Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
Route::get('/buku/{id}/edit', [BukuController::class, 'edit'])->whereNumber('id')->name('buku.edit');
Route::put('/buku/{id}', [BukuController::class, 'update'])->whereNumber('id')->name('buku.update');
Route::delete('/buku/{id}', [BukuController::class, 'destroy'])->whereNumber('id')->name('buku.destroy');