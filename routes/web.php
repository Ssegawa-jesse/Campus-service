<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Operator;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ParkController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\Tourist;
use App\Http\Controllers\TripPlannerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages (visitors)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/map', [HomeController::class, 'map'])->name('map');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.send')->middleware('throttle:5,1');

Route::get('/parks', [ParkController::class, 'index'])->name('parks.index');
Route::get('/parks/compare', [ParkController::class, 'compare'])->name('parks.compare');
Route::get('/parks/{park}', [ParkController::class, 'show'])->name('parks.show');
Route::get('/parks/{park}/tour', [TourController::class, 'show'])->name('tour.show');
Route::post('/scenes/{scene}/view', [TourController::class, 'logScene'])->name('tour.scene-view')->middleware('throttle:60,1');

Route::get('/planner', [TripPlannerController::class, 'index'])->name('planner.index');
Route::match(['get', 'post'], '/planner/results', [TripPlannerController::class, 'results'])->name('planner.results');

Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');
Route::get('/operators/{operator}', [PackageController::class, 'operator'])->name('operators.show');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->dashboardRoute()))->name('dashboard');
    Route::get('/profile', [AuthController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Payments (Mobile Money / Card)
|--------------------------------------------------------------------------
*/
Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
Route::middleware(['auth', 'role:tourist'])->group(function () {
    Route::get('/bookings/{booking}/pay', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::post('/bookings/{booking}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::get('/payments/simulate/{txRef}', [PaymentController::class, 'simulate'])->name('payments.simulate');
    Route::post('/payments/simulate/{txRef}', [PaymentController::class, 'simulateComplete'])->name('payments.simulate.complete');
});

/*
|--------------------------------------------------------------------------
| Tourist area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:tourist'])->prefix('my')->name('tourist.')->group(function () {
    Route::get('/', [Tourist\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/favourites/{park}', [Tourist\DashboardController::class, 'toggleFavourite'])->name('favourites.toggle');
    Route::post('/plans', [Tourist\DashboardController::class, 'savePlan'])->name('plans.store');
    Route::delete('/plans/{plan}', [Tourist\DashboardController::class, 'deletePlan'])->name('plans.destroy');

    Route::get('/bookings', [Tourist\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/new', [Tourist\BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [Tourist\BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [Tourist\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [Tourist\BookingController::class, 'cancel'])->name('bookings.cancel');

    Route::post('/reviews', [Tourist\ReviewController::class, 'store'])->name('reviews.store');
});

/*
|--------------------------------------------------------------------------
| Tour operator area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/', [Operator\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [Operator\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Operator\ProfileController::class, 'update'])->name('profile.update');
    Route::resource('packages', Operator\PackageController::class)->except('show');
    Route::get('/bookings', [Operator\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [Operator\BookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{booking}', [Operator\BookingController::class, 'update'])->name('bookings.update');
});

/*
|--------------------------------------------------------------------------
| Administrator (UWA) area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/export', [Admin\DashboardController::class, 'export'])->name('reports.export');

    Route::resource('parks', Admin\ParkController::class)->except('show');
    Route::post('/parks/{park}/activities', [Admin\ActivityController::class, 'store'])->name('activities.store');
    Route::put('/activities/{activity}', [Admin\ActivityController::class, 'update'])->name('activities.update');
    Route::delete('/activities/{activity}', [Admin\ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::get('/parks/{park}/media', [Admin\MediaController::class, 'index'])->name('media.index');
    Route::post('/parks/{park}/media', [Admin\MediaController::class, 'store'])->name('media.store');
    Route::put('/media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/parks/{park}/scenes', [Admin\SceneController::class, 'index'])->name('scenes.index');
    Route::post('/parks/{park}/scenes', [Admin\SceneController::class, 'store'])->name('scenes.store');
    Route::get('/scenes/{scene}/edit', [Admin\SceneController::class, 'edit'])->name('scenes.edit');
    Route::put('/scenes/{scene}', [Admin\SceneController::class, 'update'])->name('scenes.update');
    Route::delete('/scenes/{scene}', [Admin\SceneController::class, 'destroy'])->name('scenes.destroy');
    Route::post('/scenes/{scene}/hotspots', [Admin\SceneController::class, 'storeHotspot'])->name('hotspots.store');
    Route::delete('/hotspots/{hotspot}', [Admin\SceneController::class, 'destroyHotspot'])->name('hotspots.destroy');

    Route::get('/operators', [Admin\OperatorController::class, 'index'])->name('operators.index');
    Route::post('/operators/{operator}/verify', [Admin\OperatorController::class, 'toggleVerify'])->name('operators.verify');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/payments', [Admin\BookingController::class, 'payments'])->name('payments.index');

    Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/toggle', [Admin\ReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/sms', [Admin\SmsController::class, 'index'])->name('sms.index');
    Route::post('/sms', [Admin\SmsController::class, 'send'])->name('sms.send');

    Route::get('/messages', [Admin\SettingController::class, 'messages'])->name('messages.index');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
});
