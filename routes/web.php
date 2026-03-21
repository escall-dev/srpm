<?php

use App\Enums\Role;
use App\Http\Controllers\HomeRouteController;
use App\Livewire\Actions\Logout;
// Tenant
use App\Livewire\Tenant\Auth\Login as TenantLogin;
use App\Livewire\Tenant\Auth\Register as TenantRegister;
use App\Livewire\Tenant\Pages\Dashboard as TenantDashboard;
use App\Livewire\Tenant\Common\Settings as TenantSettings;
use App\Livewire\Tenant\Pages\Leases as TenantLeases;
use App\Livewire\Tenant\Pages\ViewLeaseDetails as TenantViewLeaseDetails;
use App\Livewire\Tenant\Pages\Payments as TenantPayments;
use App\Livewire\Tenant\Pages\Requests as TenantRequests;
// Owner
use App\Http\Controllers\FilePreviewController;
use App\Livewire\Owner\Common\Settings as OwnerSettings;
use App\Livewire\Owner\Auth\Login as OwnerLogin;
use App\Livewire\Owner\Auth\Register as OwnerRegister;
use App\Livewire\Owner\Pages\Dashboard as OwnerDashboard;
use App\Livewire\Owner\Pages\AutomationLogs as OwnerAutomationLogs;
use App\Livewire\Owner\Pages\Leases;
use App\Livewire\Owner\Pages\ViewLeaseDetails;
use App\Livewire\Owner\Pages\Units as OwnerUnits;
use App\Livewire\Owner\Pages\Expenses;
use App\Livewire\Owner\Pages\Properties as OwnerProperties;
use App\Livewire\Owner\Pages\Payments as OwnerPayments;
use App\Livewire\Owner\Pages\Requests as OwnerRequests;
use App\Livewire\Owner\Pages\ViewTenantPayments;
use Illuminate\Support\Facades\Route;

/*
*-----------------------------------------
*             COMMON ROUTES
*-----------------------------------------
*/

Route::get('home', HomeRouteController::class)
    ->name('home');

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', function () {
    return view('welcome');
})->name('login');

// TERMS AND CONDITIONS
Route::get('/terms-and-conditions', function () {
    return view('common.terms-and-conditions');
})->name('terms-and-conditions');

// LOGOUT
Route::post('logout', Logout::class)->name('logout');



/*
*-----------------------------------------
*           OWNER ROUTES
*-----------------------------------------
*/

Route::group([
    'middleware' => ['web'],
    'prefix' => 'owner',
    'as' => 'owner.',
], function () {

    /*
    *-----------------------------------------
    *           OWNER GUEST ROUTES
    *-----------------------------------------
    */

    Route::group([
        'middleware' => ['guest', 'throttle:10,1'],
        'prefix' => 'auth',
        'as' => 'auth.',
    ], function () {
        Route::get('login', OwnerLogin::class)->middleware('has_no_owner')->name('login');
        Route::get('register', OwnerRegister::class)->middleware('has_owner')->name('register');
    });

    /*
    *-----------------------------------------
    *       OWNER AUTHENTICATED ROUTES
    *-----------------------------------------
    */
    Route::group([
        'middleware' => [
            'auth',
            'has_no_owner',
            'auth.timeout',
            'role:'.Role::Owner->value,
        ],
    ], function () {

        /*
        *-----------------------------------------
        *             SIDEBAR ROUTES
        *-----------------------------------------
        */

        // DASHBOARD
        Route::get('dashboard', OwnerDashboard::class)
            ->name('dashboard');
        // LEASES
        Route::get('leases', Leases::class)
            ->name('leases');
        // LEASE DETAILS
        Route::get('leases/{lease}', ViewLeaseDetails::class)
            ->name('lease.details');
        // EXPENSES
        Route::get('expenses', Expenses::class)
            ->name('expenses');
        // UNITS
        Route::get('units', OwnerUnits::class)
            ->name('units');
        // PAYMENTS
        Route::get('payments', OwnerPayments::class)
            ->name('payments');
        // TENANT PAYMENTS
        Route::get('payments/tenant/{tenant}', ViewTenantPayments::class)
            ->name('tenant.payments');
        // REQUESTS
        Route::get('requests', OwnerRequests::class)
            ->name('requests');
        // AUTOMATION LOGS
        Route::get('automation-logs', OwnerAutomationLogs::class)
            ->name('automation.logs');
        // PROPERTIES
        Route::get('properties', OwnerProperties::class)
            ->name('properties');
        // SETTINGS
        Route::get('settings', action: OwnerSettings::class)
            ->name('settings');

        // FILE PREVIEW
        Route::get('file-preview/{encrypted}', FilePreviewController::class)
            ->where('encrypted', '.*')
            ->middleware('signed')
            ->name('file.preview');
    });
});



/*
*-----------------------------------------
*             TENANT ROUTES
*-----------------------------------------
*/

Route::group([
    'middleware' => ['web'],
    'prefix' => 'tenant',
    'as' => 'tenant.',
], function () {

    /*
    *-----------------------------------------
    *           TENANT GUEST ROUTES
    *-----------------------------------------
    */

    Route::group([
        'middleware' => ['guest', 'throttle:10,1'],
        'prefix' => 'auth',
        'as' => 'auth.',
    ], function () {
        Route::get('login', TenantLogin::class)->name('login');
        Route::get('register', TenantRegister::class)->name('register');
    });

    /*
    *-----------------------------------------
    *       TENANT AUTHENTICATED ROUTES
    *-----------------------------------------
    */
    Route::group([
        'middleware' => [
            'auth',
            'role:'.Role::Tenant->value,
        ],
    ], function () {

        /*
        *-----------------------------------------
        *             SIDEBAR ROUTES
        *-----------------------------------------
        */

        // DASHBOARD
        Route::get('dashboard', TenantDashboard::class)
            ->name('dashboard');

        // LEASES
        Route::get('leases', TenantLeases::class)
            ->name('leases');
        // LEASE DETAILS
        Route::get('leases/{lease}', TenantViewLeaseDetails::class)
            ->name('lease.details');

        // PAYMENTS
        Route::get('payments', TenantPayments::class)
            ->name('payments');

        // REQUESTS
        Route::get('requests', TenantRequests::class)
            ->name('requests');

        // SETTINGS
        Route::get('settings', TenantSettings::class)
            ->name('settings');

        // FILE PREVIEW
        Route::get('file-preview/{encrypted}', FilePreviewController::class)
            ->where('encrypted', '.*')
            ->middleware('signed')
            ->name('file.preview');
    });
});
