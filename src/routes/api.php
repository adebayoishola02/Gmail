<?php

use App\Http\Controllers\GmailAccountController;
use App\Http\Controllers\GmailMessagesController;
use App\Http\Controllers\GoogleOAuthController;
use Illuminate\Support\Facades\Route;

//import auth controller
use App\Http\Middleware\AuthenticateWithAuthServer;

/*
****************************************************
AUTH MIDDLEWARE
****************************************************
*/

Route::prefix('/v1')->group(function () {
    Route::middleware(AuthenticateWithAuthServer::class)->group(function () {

        // User Details
        Route::get('/user/details', ['App\Http\Controllers\UserController', 'index']);

        // Account
        Route::get('/accounts', [GmailAccountController::class, 'index']);
        Route::put('/account', [GmailAccountController::class, 'store']);
        Route::patch('/account/{uuid}', [GmailAccountController::class, 'update'])->whereUuid('uuid');
        Route::get('/account/{uuid}', [GmailAccountController::class, 'show'])->whereUuid('uuid');
        Route::delete('/account/{uuid}', [GmailAccountController::class, 'destroy'])->whereUuid('uuid');

        // Messages
        Route::get('/messages', [GmailMessagesController::class, 'index']);
        Route::put('/message', [GmailMessagesController::class, 'store']);
        Route::get('/message-details', [GmailMessagesController::class, 'show']);

        Route::prefix('/admin')
            ->middleware(\App\Http\Middleware\CheckAdminUser::class)
            ->group(function () {});
    });
});
