<?php

use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('send-email', [AccountController::class, 'sendmail']);

Route::middleware('auth:api')->group(function () {
    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Routes for accounts
    Route::get('/accounts', [AccountController::class, 'allAccounts']);
    Route::post('/accounts/create', [AccountController::class, 'createAccount']);
    Route::get('/accounts/view/{accountId}', [AccountController::class, 'viewAccount']);
    Route::put('/accounts/update/{accountId}', [AccountController::class, 'updateAccount']);
    Route::delete('/accounts/delete/{accountId}', [AccountController::class, 'deleteAccount']);

    // Routes for transactions
    Route::get('/transactions', [AccountController::class, 'allTransactions']);
    Route::post('/transactions/create', [AccountController::class, 'createTransaction']);
    Route::get('/transactions/view/{transactionId}', [AccountController::class, 'viewTransaction']);
    Route::delete('/transactions/delete/{transactionId}', [AccountController::class, 'deleteTransaction']);

});
