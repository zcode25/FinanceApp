<?php

use Illuminate\Support\Facades\Auth;
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
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\MidtransController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    $plans = \App\Models\Plan::all();

    return Inertia::render('Landing/Index', [
        'plans' => $plans,
    ]);
})->name('landing');

Route::get('/privacy-policy', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy.policy');

Route::get('/terms-of-service', function () {
    return Inertia::render('Legal/TermsOfService');
})->name('terms.service');

Route::get('/cookies-policy', function () {
    return Inertia::render('Legal/CookiesPolicy');
})->name('cookies.policy');

Route::post('/locale', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'locale' => 'required|in:en,id',
    ]);

    session(['locale' => $validated['locale']]);

    if (Auth::check()) {
        Auth::user()->update(['locale' => $validated['locale']]);
    }

    return back();
})->name('locale.update');


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLinkEmail'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('email/verify', [\App\Http\Controllers\EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [\App\Http\Controllers\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'starter.verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::post('/wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::put('/wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::patch('/wallets/{wallet}/toggle', [WalletController::class, 'toggle'])->name('wallets.toggle');
    Route::post('/wallets/reorder', [WalletController::class, 'reorder'])->name('wallets.reorder');

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

    Route::get('/tracker', [App\Http\Controllers\TrackerController::class, 'index'])->name('tracker');

    Route::get('/goals', [App\Http\Controllers\GoalController::class, 'index'])->name('goals.index');
    Route::post('/goals', [App\Http\Controllers\GoalController::class, 'store'])->name('goals.store');
    Route::put('/goals/{goal}', [App\Http\Controllers\GoalController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{goal}', [App\Http\Controllers\GoalController::class, 'destroy'])->name('goals.destroy');

    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);

    Route::post('/user/complete-tour', [App\Http\Controllers\UserController::class, 'completeTour'])->name('user.complete-tour');
    Route::post('/user/reset-tour', [App\Http\Controllers\UserController::class, 'resetTour'])->name('user.reset-tour');
});

// Subscription Routes (Accessible without email verification to maximize conversion)
Route::middleware(['auth'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout.index');
    Route::post('/subscription/checkout', [MidtransController::class, 'getSnapToken'])->name('subscription.checkout');
    Route::post('/subscription/promo/validate', [SubscriptionController::class, 'validatePromo'])->name('subscription.promo.validate');
});

// Midtrans Webhook
Route::post('/midtrans/notification', [MidtransController::class, 'handleNotification'])->name('midtrans.notification');

// Diagnostic route - Remove after fixing 403 issue
Route::get('storage-test', function () {
    return response()->json([
        'message' => 'Laravel is receiving requests!',
        'environment' => app()->environment(),
        'storage_path' => storage_path('app/public'),
        'sample_file_check' => file_exists(storage_path('app/public/avatars/cq4i0PqdWRjtDJvJz39DZ4dIt3ooHpTviaQ1X8Qx.jpg')) ? 'EXISTS' : 'NOT FOUND',
        'public_path' => public_path('storage'),
        'public_storage_exists' => file_exists(public_path('storage')) ? 'YES' : 'NO',
    ]);
});

// Alternative Media Proxy - Bypasses potential Apache restrictions on "storage" keyword
Route::get('media/{path}', function ($path) {
    try {
        $filePath = storage_path('app/public/' . $path);
        
        if (!file_exists($filePath)) {
            \Illuminate\Support\Facades\Log::warning("Media Proxy 404: File not found at {$filePath}");
            abort(404);
        }

        return response()->file($filePath);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Media Proxy 500: " . $e->getMessage());
        abort(500);
    }
})->where('path', '.*');

// Smart Storage Proxy - Fallback for missing symlink on shared hosting
Route::get('storage/{path}', function ($path) {
    try {
        $filePath = storage_path('app/public/' . $path);
        
        // Final fallback diagnosis
        if (!file_exists($filePath)) {
            \Illuminate\Support\Facades\Log::warning("Proxy 404: File not found at {$filePath}");
            abort(404);
        }

        return response()->file($filePath);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Proxy 500: " . $e->getMessage());
        abort(500);
    }
})->where('path', '.*');
