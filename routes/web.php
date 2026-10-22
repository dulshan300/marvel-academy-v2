<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Admin\EmailPreviewController;
use App\Http\Controllers\Admin\NewsletterExportController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterUnsubscribeController;
use App\Http\Controllers\Parent\BillingPortalController;
use App\Http\Controllers\Parent\CheckoutSuccessController;
use App\Http\Controllers\Student\MaterialDownloadController;
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
    Route::get('/materials/{material}', MaterialDownloadController::class)
        ->middleware('throttle:60,1')
        ->name('materials.download');

    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

        Route::middleware('permission:'.Permission::ManageCourses->value)->group(function () {
            Route::livewire('/courses', 'pages::admin.courses.index')->name('courses.index');
            Route::livewire('/courses/create', 'pages::admin.courses.edit')->name('courses.create');
            Route::livewire('/courses/{course}/edit', 'pages::admin.courses.edit')->name('courses.edit');
            Route::livewire('/topics/{topic}', 'pages::admin.topics.edit')->name('topics.edit');
            Route::livewire('/quizzes/{quiz}', 'pages::admin.quizzes.edit')->name('quizzes.edit');
        });

        Route::middleware('permission:'.Permission::ManageLiveSessions->value)->group(function () {
            Route::livewire('/sessions', 'pages::admin.sessions.index')->name('sessions.index');
            Route::livewire('/sessions/create', 'pages::admin.sessions.edit')->name('sessions.create');
            Route::livewire('/sessions/{session}/edit', 'pages::admin.sessions.edit')->name('sessions.edit');
        });

        Route::middleware('permission:'.Permission::ManageFamilies->value)->group(function () {
            Route::livewire('/families', 'pages::admin.families.index')->name('families.index');
            Route::livewire('/families/{parent}', 'pages::admin.families.show')->name('families.show');
        });

        Route::middleware('permission:'.Permission::ManageTeachers->value)->group(function () {
            Route::livewire('/teachers', 'pages::admin.teachers.index')->name('teachers.index');
            Route::livewire('/teachers/create', 'pages::admin.teachers.edit')->name('teachers.create');
            Route::livewire('/teachers/{teacher}/edit', 'pages::admin.teachers.edit')->name('teachers.edit');
        });

        Route::livewire('/payments', 'pages::admin.payments.index')
            ->middleware('permission:'.Permission::ManagePayments->value)
            ->name('payments.index');

        Route::middleware('permission:'.Permission::ManageContent->value)->group(function () {
            Route::livewire('/content', 'pages::admin.content.index')->name('content.index');
            Route::get('/newsletter/export', NewsletterExportController::class)->name('newsletter.export');
        });

        Route::livewire('/roles', 'pages::admin.roles.index')
            ->middleware('permission:'.Permission::ManageRoles->value)
            ->name('roles.index');

        Route::middleware('permission:'.Permission::ManageEmails->value)->group(function () {
            Route::livewire('/emails', 'pages::admin.emails.index')->name('emails.index');
            Route::get('/emails/preview/{template}', EmailPreviewController::class)->name('emails.preview');
        });

        Route::middleware('permission:'.Permission::ManageBackups->value)->group(function () {
            Route::livewire('/backups', 'pages::admin.backups.index')->name('backups.index');
            Route::get('/backups/download', BackupDownloadController::class)->middleware('throttle:10,1')->name('backups.download');
        });
    });

    Route::prefix('teacher')->name('teacher.')->middleware('role:'.Role::Teacher->value)->group(function () {
        Route::livewire('/', 'pages::teacher.dashboard')->name('dashboard');
        Route::livewire('/schedule', 'pages::teacher.schedule')->name('schedule');
        Route::livewire('/sessions/{session}', 'pages::teacher.sessions.show')->name('sessions.show');
        Route::livewire('/students', 'pages::teacher.students')->name('students');
        Route::livewire('/profile', 'pages::teacher.profile')->name('profile');
    });

    Route::prefix('parent')->name('parent.')->middleware('role:'.Role::Parent->value)->group(function () {
        Route::livewire('/', 'pages::parent.dashboard')->name('dashboard');
        Route::livewire('/children/create', 'pages::parent.children.create')->name('children.create');
        Route::livewire('/children/{child}/edit', 'pages::parent.children.edit')->name('children.edit');
        Route::livewire('/children/{child}/subscribe', 'pages::parent.children.subscribe')->name('children.subscribe');
        Route::livewire('/children/{child}/courses', 'pages::parent.children.courses')->name('children.courses');
        Route::livewire('/billing', 'pages::parent.billing')->name('billing');
        Route::get('/billing/portal', BillingPortalController::class)->name('billing.portal');
        Route::get('/checkout/{child}/success', CheckoutSuccessController::class)->name('checkout.success');
    });

    Route::prefix('student')->name('student.')->middleware('role:'.Role::Student->value)->group(function () {
        Route::livewire('/', 'pages::student.dashboard')->name('dashboard');
        Route::livewire('/timetable', 'pages::student.timetable')->name('timetable');
        Route::livewire('/courses/{course}', 'pages::student.courses.show')->name('courses.show');
        Route::livewire('/quizzes/{quiz}', 'pages::student.quizzes.take')->name('quizzes.take');
        Route::livewire('/quizzes/{quiz}/attempts/{attempt}', 'pages::student.quizzes.result')->name('quizzes.result');
        Route::livewire('/sessions/{session}', 'pages::student.sessions.show')->name('sessions.show');
    });
});
