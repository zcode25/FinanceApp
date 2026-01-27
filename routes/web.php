<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::post('/wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::put('/wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::patch('/wallets/{wallet}/toggle', [WalletController::class, 'toggle'])->name('wallets.toggle');

    Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');

    Route::get('/budget', [BudgetController::class, 'index'])->name('budget');
    Route::post('/budget', [BudgetController::class, 'store'])->name('budget.store');
    Route::put('/budget/{budget}', [BudgetController::class, 'update'])->name('budget.update');
    Route::post('/budget/auto-setup', [BudgetController::class, 'autoSetup'])->name('budget.auto-setup');
    Route::delete('/budget/{budget}', [BudgetController::class, 'destroy'])->name('budget.destroy');

    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::post('/profile', [App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::put('/password', [App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::patch('/preferences', [App\Http\Controllers\SettingsController::class, 'updatePreferences'])->name('settings.preferences');
        Route::get('/export', [App\Http\Controllers\SettingsController::class, 'exportData'])->name('settings.export');
        Route::post('/reset', [App\Http\Controllers\SettingsController::class, 'resetData'])->name('settings.reset');
        Route::delete('/account', [App\Http\Controllers\SettingsController::class, 'destroyAccount'])->name('settings.destroy');
    });

    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
});
