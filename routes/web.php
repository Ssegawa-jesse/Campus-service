<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\FinePaymentController;
use App\Http\Controllers\Member\LoanController;
use App\Http\Controllers\Member\ReservationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SuggestionController;
use Illuminate\Support\Facades\Route;

// Public pages: anyone (guests included) can search the catalogue, browse resources and see recommendations.
Route::get('/', HomeController::class)->name('home');
Route::get('/catalogue', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalogue/{book}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
Route::get('/recommendations', [SuggestionController::class, 'index'])->name('suggestions.index');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});

// Flutterwave redirects here after checkout.
Route::get('/payments/callback', [FinePaymentController::class, 'callback'])->name('payments.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.destroy');

    Route::get('/resources/{resource}/download', [ResourceController::class, 'download'])->name('resources.download');

    // Recommendations: any logged-in user can recommend a book and support others' recommendations.
    Route::get('/recommendations/create', [SuggestionController::class, 'create'])->name('suggestions.create');
    Route::post('/recommendations', [SuggestionController::class, 'store'])->name('suggestions.store');
    Route::post('/recommendations/{suggestion}/vote', [SuggestionController::class, 'vote'])->name('suggestions.vote');
    Route::delete('/recommendations/{suggestion}', [SuggestionController::class, 'destroy'])->name('suggestions.destroy');

    // Member self-service.
    Route::middleware('role:member')->prefix('my')->name('my.')->group(function () {
        Route::get('/loans', [LoanController::class, 'index'])->name('loans');
        Route::post('/loans/{loan}/renew', [LoanController::class, 'renew'])->name('loans.renew');
        Route::get('/card', [LoanController::class, 'card'])->name('card');
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations');
        Route::post('/reservations/{book}', [ReservationController::class, 'store'])->name('reservations.store');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
        Route::get('/fines', [FinePaymentController::class, 'index'])->name('fines');
        Route::post('/fines/{fine}/pay', [FinePaymentController::class, 'pay'])->name('fines.pay');
        Route::get('/payments/{payment}/receipt', [FinePaymentController::class, 'receipt'])->name('payments.receipt');
    });
    Route::get('/payments/{payment}/sandbox', [FinePaymentController::class, 'sandbox'])->name('payments.sandbox');
    Route::post('/payments/{payment}/sandbox', [FinePaymentController::class, 'sandboxComplete'])->name('payments.sandbox.complete');

    // Library staff (librarians and administrators).
    Route::middleware('role:admin,librarian')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('books', Admin\BookController::class)->except('show');
        Route::get('books/{book}/copies', [Admin\CopyController::class, 'index'])->name('copies.index');
        Route::post('books/{book}/copies', [Admin\CopyController::class, 'store'])->name('copies.store');
        Route::post('books/{book}/fetch-cover', [Admin\BookController::class, 'fetchCover'])->name('books.fetch-cover');
        Route::put('copies/{copy}', [Admin\CopyController::class, 'update'])->name('copies.update');
        Route::delete('copies/{copy}', [Admin\CopyController::class, 'destroy'])->name('copies.destroy');
        Route::get('copies/labels', [Admin\CopyController::class, 'labels'])->name('copies.labels');
        Route::resource('categories', Admin\CategoryController::class)->except(['show', 'create', 'edit']);

        Route::resource('members', Admin\MemberController::class)->except('destroy');
        Route::get('members/{member}/card', [Admin\MemberController::class, 'card'])->name('members.card');
        Route::post('members/{member}/status', [Admin\MemberController::class, 'toggleStatus'])->name('members.status');

        Route::get('circulation', [Admin\CirculationController::class, 'index'])->name('circulation.index');
        Route::post('circulation/issue', [Admin\CirculationController::class, 'issue'])->name('circulation.issue');
        Route::post('circulation/return', [Admin\CirculationController::class, 'return'])->name('circulation.return');
        Route::get('circulation/lookup', [Admin\CirculationController::class, 'lookup'])->name('circulation.lookup');
        Route::get('loans', [Admin\CirculationController::class, 'loans'])->name('loans.index');
        Route::post('loans/{loan}/renew', [Admin\CirculationController::class, 'renew'])->name('loans.renew');

        Route::get('reservations', [Admin\ReservationController::class, 'index'])->name('reservations.index');
        Route::delete('reservations/{reservation}', [Admin\ReservationController::class, 'destroy'])->name('reservations.destroy');

        Route::get('fines', [Admin\FineController::class, 'index'])->name('fines.index');
        Route::post('fines/{fine}/waive', [Admin\FineController::class, 'waive'])->name('fines.waive');
        Route::post('fines/{fine}/cash', [Admin\FineController::class, 'cash'])->name('fines.cash');

        Route::get('resources', [Admin\ResourceController::class, 'index'])->name('resources.index');
        Route::post('resources', [Admin\ResourceController::class, 'store'])->name('resources.store');
        Route::delete('resources/{resource}', [Admin\ResourceController::class, 'destroy'])->name('resources.destroy');

        Route::get('recommendations', [Admin\SuggestionController::class, 'index'])->name('suggestions.index');
        Route::put('recommendations/{suggestion}', [Admin\SuggestionController::class, 'update'])->name('suggestions.update');

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/{report}', [Admin\ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/print/{report}', [Admin\ReportController::class, 'print'])->name('reports.print');

        // Administrator only.
        Route::middleware('role:admin')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::resource('staff', Admin\StaffController::class)->except('show')->parameters(['staff' => 'staff']);
            Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit.index');
            Route::get('notifications', [Admin\AuditLogController::class, 'notifications'])->name('notifications.index');
        });
    });
});
