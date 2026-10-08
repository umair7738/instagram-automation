<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AutomationRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstagramAccountController;
use App\Http\Controllers\MediaResourceController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\MetaOAuthController;
use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Public Meta app requirement pages. These must remain reachable without login.
Route::view('privacy-policy', 'legal.privacy-policy')->name('privacy-policy');
Route::view('data-deletion', 'legal.data-deletion')->name('data-deletion');
Route::view('terms-of-service', 'legal.terms-of-service')->name('terms-of-service');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('setup', [SetupController::class, 'index'])->name('setup.index');
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::resource('accounts', InstagramAccountController::class)->except('show');
    Route::get('resources/instagram-media', [MediaResourceController::class, 'instagramMedia'])->name('resources.instagram-media');
    Route::resource('resources', MediaResourceController::class)->except('show');
    Route::post('templates/examples/{key}', [MessageTemplateController::class, 'duplicateExample'])->name('templates.examples.duplicate');
    Route::resource('templates', MessageTemplateController::class)->except('show');
    Route::post('rules/examples/{key}', [AutomationRuleController::class, 'duplicateExample'])->name('rules.examples.duplicate');
    Route::resource('rules', AutomationRuleController::class)->except('show');
    Route::get('meta/connect', [MetaOAuthController::class, 'start'])->name('meta.oauth.start');
    Route::get('meta/callback', [MetaOAuthController::class, 'callback'])->name('meta.oauth.callback');
    Route::get('meta/connect/instagram', [MetaOAuthController::class, 'startInstagram'])->name('meta.instagram.oauth.start');
    Route::get('meta/callback/instagram', [MetaOAuthController::class, 'callbackInstagram'])->name('meta.instagram.oauth.callback');
});
Route::match(['GET', 'POST'], 'webhooks/meta', [MetaWebhookController::class, 'handle'])
    ->middleware('meta.signature')
    ->name('meta.webhook');
