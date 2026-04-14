<?php

use App\Http\Controllers\Admin\ChatbotConfigController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WaInstanceController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.dashboard'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('chatbot', ChatbotConfigController::class)->except(['show']);
    Route::get('/chatbot/{chatbot}/embed-code', [ChatbotConfigController::class, 'embedCode'])->name('chatbot.embed-code');

    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::post('/knowledge/from-url', [KnowledgeController::class, 'storeFromUrl'])->name('knowledge.store-url');
    Route::get('/knowledge/{document}', [KnowledgeController::class, 'show'])->name('knowledge.show');
    Route::delete('/knowledge/{document}', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');
    Route::post('/knowledge/{document}/reindex', [KnowledgeController::class, 'reindex'])->name('knowledge.reindex');

    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/export', [ConversationController::class, 'export'])->name('conversations.export');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/message', [ConversationController::class, 'sendMessage'])->name('conversations.message');
    Route::patch('/conversations/{conversation}/status', [ConversationController::class, 'updateStatus'])->name('conversations.status');
    Route::post('/conversations/{conversation}/assign', [ConversationController::class, 'assign'])->name('conversations.assign');
    Route::post('/conversations/{conversation}/resume-ai', [ConversationController::class, 'resumeAI'])->name('conversations.resume-ai');

    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('wa', WaInstanceController::class)->except(['show'])->parameters(['wa' => 'waInstance']);
    Route::post('/wa/{waInstance}/test', [WaInstanceController::class, 'testConnection'])->name('wa.test');

    Route::middleware('super_admin')->group(function () {
        Route::resource('tenants', TenantController::class);
    });

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/global', [SettingsController::class, 'updateGlobal'])->name('settings.update-global')->middleware('super_admin');
    Route::get('/settings/test-ai', [SettingsController::class, 'testAI'])->name('settings.test-ai');
});
