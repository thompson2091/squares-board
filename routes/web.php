<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BoardAdminController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SquareController;
use App\Http\Controllers\WinnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/health', fn () => response('healthy', 200))->name('health');

/*
|--------------------------------------------------------------------------
| Auth Routes (from Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // === AGENT 1: Dashboard & Profile ===
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/winnings', [DashboardController::class, 'winnings'])->name('winnings');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === AGENT 2: Board & Grid Routes ===
    // Browse public boards (no auth required for viewing)
    Route::get('/boards/browse', [BoardController::class, 'browse'])->name('boards.browse')->withoutMiddleware(['auth', 'verified']);

    // Board creation (must be before {board:uuid} route)
    Route::get('/boards/create', [BoardController::class, 'create'])->name('boards.create');
    Route::post('/boards', [BoardController::class, 'store'])->name('boards.store');

    // View individual board (public boards viewable without auth)
    Route::get('/boards/{board:uuid}', [BoardController::class, 'show'])->name('boards.show')->withoutMiddleware(['auth', 'verified']);

    // Board management (authenticated users)
    Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');
    Route::get('/boards/{board:uuid}/edit', [BoardController::class, 'edit'])->name('boards.edit');
    Route::patch('/boards/{board:uuid}', [BoardController::class, 'update'])->name('boards.update');
    Route::delete('/boards/{board:uuid}', [BoardController::class, 'destroy'])->name('boards.destroy');
    Route::post('/boards/{board:uuid}/lock', [BoardController::class, 'lock'])->name('boards.lock');

    // Square claiming (AJAX)
    Route::post('/boards/{board:uuid}/squares/{row}/{col}/claim', [SquareController::class, 'claim'])->name('squares.claim');
    Route::delete('/boards/{board:uuid}/squares/{square}/release', [SquareController::class, 'release'])->name('squares.release');

    // === AGENT 3: Board Management Routes ===
    Route::prefix('manage/boards/{board:uuid}')->name('manage.boards.')->group(function () {
        Route::get('/', [BoardController::class, 'manage'])->name('show');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/squares/{square}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');
        Route::delete('/squares/{square}/mark-unpaid', [PaymentController::class, 'markUnpaid'])->name('payments.mark-unpaid');
        Route::post('/squares/bulk-mark-paid', [PaymentController::class, 'bulkMarkPaid'])->name('payments.bulk-mark-paid');
        Route::delete('/squares/bulk-release', [PaymentController::class, 'bulkRelease'])->name('payments.bulk-release');

        // Payouts
        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [PayoutController::class, 'store'])->name('payouts.store');
        Route::patch('/payouts/{payout}', [PayoutController::class, 'update'])->name('payouts.update');
        Route::delete('/payouts/{payout}', [PayoutController::class, 'destroy'])->name('payouts.destroy');

        // Scores & Winners
        Route::get('/scores', [ScoreController::class, 'index'])->name('scores.index');
        Route::post('/scores', [ScoreController::class, 'store'])->name('scores.store');
        Route::post('/scores/complete', [ScoreController::class, 'complete'])->name('scores.complete');
        Route::get('/winners', [WinnerController::class, 'index'])->name('winners.index');

        // Co-admins
        Route::get('/admins', [BoardAdminController::class, 'index'])->name('admins.index');
        Route::post('/admins', [BoardAdminController::class, 'store'])->name('admins.store');
        Route::delete('/admins/{admin}', [BoardAdminController::class, 'destroy'])->name('admins.destroy');

        // Number generation
        Route::post('/generate-numbers', [BoardController::class, 'generateNumbers'])->name('generate-numbers');
    });

});

/*
|--------------------------------------------------------------------------
| Platform Admin Routes (AGENT 1)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'platform.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/approve-creator', [Admin\UserController::class, 'approveCreator'])->name('users.approve-creator');
    Route::patch('/users/{user}/role', [Admin\UserController::class, 'updateRole'])->name('users.update-role');
    Route::get('/boards', [Admin\BoardController::class, 'index'])->name('boards.index');
});
