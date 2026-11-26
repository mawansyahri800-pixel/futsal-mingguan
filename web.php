<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('members', MemberController::class)->except('show');
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');
    Route::resource('cash', CashTransactionController::class)->parameters(['cash' => 'cash'])->except('show');
    Route::resource('schedules', ScheduleController::class)->except('show');
    Route::get('/reports/ai', [ReportController::class, 'ai'])->name('reports.ai');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});

require __DIR__.'/auth.php';
