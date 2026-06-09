<?php

use App\Http\Controllers\Admin\ChatbotConfigController;
use App\Http\Controllers\Admin\PersonaTemplateController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WaInstanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/privacy', fn () => inertia('Legal/Privacy'))->name('privacy');
Route::get('/terms', fn () => inertia('Legal/Terms'))->name('terms');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/chatbot', [ChatbotConfigController::class, 'index'])->name('chatbot.index');
    Route::get('/chatbot/create', [ChatbotConfigController::class, 'create'])->name('chatbot.create');
    Route::get('/chatbot/{chatbot}/edit', [ChatbotConfigController::class, 'edit'])->name('chatbot.edit');
    Route::get('/chatbot/{chatbot}/embed-code', [ChatbotConfigController::class, 'embedCode'])->name('chatbot.embed-code');
    Route::get('/chatbot/{chatbot}/persona', [ChatbotConfigController::class, 'persona'])->name('chatbot.persona');

    Route::middleware('role:super_admin,admin')->group(function () {
        Route::post('/chatbot', [ChatbotConfigController::class, 'store'])->name('chatbot.store');
        Route::put('/chatbot/{chatbot}', [ChatbotConfigController::class, 'update'])->name('chatbot.update');
        Route::delete('/chatbot/{chatbot}', [ChatbotConfigController::class, 'destroy'])->name('chatbot.destroy');
        Route::put('/chatbot/{chatbot}/persona', [ChatbotConfigController::class, 'updatePersona'])->name('chatbot.persona.update');
        Route::post('/chatbot/{chatbot}/persona/generate', [ChatbotConfigController::class, 'generatePersona'])->name('chatbot.persona.generate');
        Route::post('/persona-templates', [PersonaTemplateController::class, 'store'])->name('persona-templates.store');
        Route::delete('/persona-templates/{personaTemplate}', [PersonaTemplateController::class, 'destroy'])->name('persona-templates.destroy');

        Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
        Route::post('/knowledge/from-url', [KnowledgeController::class, 'storeFromUrl'])->name('knowledge.store-url');
        Route::delete('/knowledge/{document}', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');
        Route::post('/knowledge/{document}/reindex', [KnowledgeController::class, 'reindex'])->name('knowledge.reindex');

        Route::resource('users', UserController::class)->except(['show']);

        Route::resource('wa', WaInstanceController::class)->except(['show'])->parameters(['wa' => 'waInstance']);
        Route::get('/wa/{waInstance}/connect', [WaInstanceController::class, 'connect'])->name('wa.connect');
        Route::post('/wa/{waInstance}/connect', [WaInstanceController::class, 'initiateConnect'])->name('wa.connect.initiate');
        Route::get('/wa/{waInstance}/qr', [WaInstanceController::class, 'qr'])->name('wa.qr');
        Route::get('/wa/{waInstance}/status', [WaInstanceController::class, 'status'])->name('wa.status');
        Route::post('/wa/{waInstance}/test', [WaInstanceController::class, 'testConnection'])->name('wa.test');

        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-ai', [SettingsController::class, 'testAI'])->name('settings.test-ai');
    });

    Route::middleware('role:super_admin,admin,operator')->group(function () {
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/export', [ConversationController::class, 'export'])->name('conversations.export');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/message', [ConversationController::class, 'sendMessage'])->name('conversations.message');
        Route::post('/conversations/{conversation}/image', [ConversationController::class, 'sendImage'])->name('conversations.image');
        Route::patch('/conversations/{conversation}/status', [ConversationController::class, 'updateStatus'])->name('conversations.status');
        Route::post('/conversations/{conversation}/assign', [ConversationController::class, 'assign'])->name('conversations.assign');
        Route::post('/conversations/{conversation}/take-over', [ConversationController::class, 'takeOver'])->name('conversations.take-over');
        Route::post('/conversations/{conversation}/resume-ai', [ConversationController::class, 'resumeAI'])->name('conversations.resume-ai');
    });

    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::get('/knowledge/{document}', [KnowledgeController::class, 'show'])->name('knowledge.show');

    Route::middleware('super_admin')->group(function () {
        Route::resource('tenants', TenantController::class);
        Route::post('/settings/global', [SettingsController::class, 'updateGlobal'])->name('settings.update-global');
        Route::get('/marketing', [\App\Http\Controllers\Admin\MarketingCmsController::class, 'index'])->name('marketing.index');
        Route::put('/marketing', [\App\Http\Controllers\Admin\MarketingCmsController::class, 'update'])->name('marketing.update');
    });

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});
