<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AuthenticatedSessionController::class, 'create']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/renewal', [DashboardController::class, 'renewal'])->name('renewal');
    Route::post('/subplanbyplanid', [DashboardController::class, 'subPlanByPlanId'])->name('subplanbyplanid');
    Route::post('/getsubplanbyidfordata', [DashboardController::class, 'getSubplanbyIdfordata'])->name('getsubplanbyidfordata');
    Route::post('/razorpaypg-checkout', [DashboardController::class, 'razorpaypgCheckout'])->name('razorpaypg-checkout');
    Route::get('/session-history', [DashboardController::class, 'sessionHistory'])->name('sessionhistory');
    Route::get('/auth-logs', [DashboardController::class, 'authLogs'])->name('authlogs');
    Route::get('/show-invoices', [DashboardController::class, 'showInvoices'])->name('showinvoices');
    Route::get('/print-invoice/{id}', [DashboardController::class, 'printInvoice'])->name('printinvoice');
    Route::get('/complaint', [DashboardController::class, 'complaint'])->name('complaint');
    Route::post('/createcomplaint', [DashboardController::class, 'createcomplaint'])->name('createcomplaint');
    Route::get('setting', [DashboardController::class, 'setting'])->name('setting');
    Route::post('setting', [DashboardController::class, 'settingUpdate'])->name('setting');
});
Route::post('/razorpaypg-status', [DashboardController::class, 'razorpaypgStatus'])->name('razorpaypg-status');

require __DIR__ . '/auth.php';
