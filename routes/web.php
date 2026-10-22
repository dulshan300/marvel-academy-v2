<?php

use App\Enums\Role;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterUnsubscribeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::livewire('/courses', 'pages::courses.index')->name('courses.index');
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

Route::view('/how-it-works', 'how-it-works')->name('how-it-works');
Route::view('/about-us', 'about')->name('about');
Route::livewire('/contact-us', 'pages::contact')->name('contact');

Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');

Route::get('/newsletter/unsubscribe/{token}', NewsletterUnsubscribeController::class)
    ->middleware('throttle:10,1')
    ->name('newsletter.unsubscribe');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Portals
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:'.Role::Admin->value)->group(function () {
        Route::view('/', 'portal.placeholder', ['portal' => 'Admin'])->name('dashboard');
    });

    Route::prefix('teacher')->name('teacher.')->middleware('role:'.Role::Teacher->value)->group(function () {
        Route::view('/', 'portal.placeholder', ['portal' => 'Teacher'])->name('dashboard');
    });

    Route::prefix('parent')->name('parent.')->middleware('role:'.Role::Parent->value)->group(function () {
        Route::view('/', 'portal.placeholder', ['portal' => 'Parent'])->name('dashboard');
    });

    Route::prefix('student')->name('student.')->middleware('role:'.Role::Student->value)->group(function () {
        Route::view('/', 'portal.placeholder', ['portal' => 'Student'])->name('dashboard');
    });
});
