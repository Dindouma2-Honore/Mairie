<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Civil;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HR;
use App\Http\Controllers\Offline\SyncController;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\Revenue;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Support\Facades\Route;

/* ---------- Public ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
});

// Page de repli du mode hors ligne (précachée par le service worker).
Route::view('/hors-ligne', 'offline.fallback')->name('offline');

// Contrôle d'authenticité d'un acte (code imprimé sur le document).
Route::get('/verifier/{code}', [PublicVerificationController::class, 'show'])
    ->middleware('throttle:30,1')->where('code', '[A-Za-z0-9]{6,16}')->name('verify.act');

/* ---------- Authentifié ---------- */
Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
    Route::get('/mot-de-passe', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/mot-de-passe', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::middleware(EnsurePasswordChanged::class)->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        /* Mode hors ligne */
        Route::get('/csrf', [SyncController::class, 'csrf'])->name('offline.csrf');
        Route::get('/offline/urls', [SyncController::class, 'urls'])->name('offline.urls');
        Route::post('/offline/blocks', [SyncController::class, 'allocate'])->name('offline.blocks');
        Route::post('/sync', [SyncController::class, 'store'])->name('offline.sync');
        Route::view('/synchronisation', 'offline.sync')->name('sync.index');

        /* Recettes & fiscalité */
        Route::prefix('recettes')->name('revenue.')->group(function () {
            Route::get('recus', [Revenue\ReceiptController::class, 'index'])->middleware('can:revenue.view')->name('receipts.index');
            Route::get('recus/nouveau', [Revenue\ReceiptController::class, 'create'])->middleware('can:revenue.create')->name('receipts.create');
            Route::post('recus', [Revenue\ReceiptController::class, 'store'])->middleware('can:revenue.create')->name('receipts.store');
            Route::get('recus/{receipt}', [Revenue\ReceiptController::class, 'show'])->middleware('can:revenue.view')->name('receipts.show');
            Route::get('recus/{receipt}/imprimer', [Revenue\ReceiptController::class, 'print'])->middleware('can:revenue.view')->name('receipts.print');
            Route::post('recus/{receipt}/annuler', [Revenue\ReceiptController::class, 'cancel'])->middleware('can:revenue.cancel')->name('receipts.cancel');

            Route::middleware('can:revenue.taxpayers')->group(function () {
                Route::get('contribuables', [Revenue\TaxpayerController::class, 'index'])->name('taxpayers.index');
                Route::get('contribuables/nouveau', [Revenue\TaxpayerController::class, 'create'])->name('taxpayers.create');
                Route::post('contribuables', [Revenue\TaxpayerController::class, 'store'])->name('taxpayers.store');
                Route::get('contribuables/{taxpayer}/modifier', [Revenue\TaxpayerController::class, 'edit'])->name('taxpayers.edit');
                Route::put('contribuables/{taxpayer}', [Revenue\TaxpayerController::class, 'update'])->name('taxpayers.update');
            });

            Route::middleware('can:revenue.configure')->group(function () {
                Route::get('categories', [Revenue\CategoryController::class, 'index'])->name('categories.index');
                Route::post('categories', [Revenue\CategoryController::class, 'storeCategory'])->name('categories.store');
                Route::post('categories/{category}/basculer', [Revenue\CategoryController::class, 'toggleCategory'])->name('categories.toggle');
                Route::post('categories/{category}/tarifs', [Revenue\CategoryController::class, 'storeTariff'])->name('tariffs.store');
                Route::post('tarifs/{tariff}/basculer', [Revenue\CategoryController::class, 'toggleTariff'])->name('tariffs.toggle');
            });
        });

        /* État civil */
        Route::prefix('etat-civil')->name('civil.')->group(function () {
            Route::get('naissances', [Civil\BirthActController::class, 'index'])->middleware('can:civil.birth.view')->name('births.index');
            Route::get('naissances/nouvelle', [Civil\BirthActController::class, 'create'])->middleware('can:civil.birth.create')->name('births.create');
            Route::post('naissances', [Civil\BirthActController::class, 'store'])->middleware('can:civil.birth.create')->name('births.store');
            Route::get('naissances/{birth}', [Civil\BirthActController::class, 'show'])->middleware('can:civil.birth.view')->name('births.show');
            Route::get('naissances/{birth}/modifier', [Civil\BirthActController::class, 'edit'])->middleware('can:civil.birth.create')->name('births.edit');
            Route::put('naissances/{birth}', [Civil\BirthActController::class, 'update'])->middleware('can:civil.birth.create')->name('births.update');
            Route::post('naissances/{birth}/valider', [Civil\BirthActController::class, 'validateAct'])->middleware('can:civil.birth.validate')->name('births.validate');
            Route::post('naissances/{birth}/delivrer', [Civil\BirthActController::class, 'deliver'])->middleware('can:civil.birth.print')->name('births.deliver');
            Route::get('delivrances/{delivery}/imprimer', [Civil\BirthActController::class, 'print'])->middleware('can:civil.birth.print')->name('deliveries.print');

            Route::middleware('can:civil.templates')->group(function () {
                Route::get('gabarits', [Civil\TemplateController::class, 'index'])->name('templates.index');
                Route::post('gabarits', [Civil\TemplateController::class, 'store'])->name('templates.store');
                Route::post('gabarits/{template}/activer', [Civil\TemplateController::class, 'activate'])->name('templates.activate');
                Route::post('gabarits/{template}/desactiver', [Civil\TemplateController::class, 'deactivate'])->name('templates.deactivate');
                Route::get('gabarits/{template}/apercu', [Civil\TemplateController::class, 'preview'])->name('templates.preview');
            });
        });

        /* Personnel */
        Route::prefix('personnel')->name('hr.')->group(function () {
            Route::get('agents', [HR\AgentController::class, 'index'])->middleware('can:hr.view')->name('agents.index');
            Route::get('agents/nouveau', [HR\AgentController::class, 'create'])->middleware('can:hr.manage')->name('agents.create');
            Route::post('agents', [HR\AgentController::class, 'store'])->middleware('can:hr.manage')->name('agents.store');
            Route::get('agents/{agent}', [HR\AgentController::class, 'show'])->middleware('can:hr.view')->name('agents.show');
            Route::get('agents/{agent}/modifier', [HR\AgentController::class, 'edit'])->middleware('can:hr.manage')->name('agents.edit');
            Route::put('agents/{agent}', [HR\AgentController::class, 'update'])->middleware('can:hr.manage')->name('agents.update');

            Route::get('conges', [HR\LeaveController::class, 'index'])->name('leaves.index');
            Route::get('conges/nouvelle', [HR\LeaveController::class, 'create'])->name('leaves.create');
            Route::post('conges', [HR\LeaveController::class, 'store'])->name('leaves.store');
            Route::post('conges/{leave}/decision', [HR\LeaveController::class, 'decide'])->middleware('can:hr.leave.decide')->name('leaves.decide');
        });

        /* Administration */
        Route::prefix('administration')->name('admin.')->group(function () {
            Route::middleware('can:users.manage')->group(function () {
                Route::get('utilisateurs', [Admin\UserController::class, 'index'])->name('users.index');
                Route::get('utilisateurs/nouveau', [Admin\UserController::class, 'create'])->name('users.create');
                Route::post('utilisateurs', [Admin\UserController::class, 'store'])->name('users.store');
                Route::get('utilisateurs/{user}/modifier', [Admin\UserController::class, 'edit'])->name('users.edit');
                Route::put('utilisateurs/{user}', [Admin\UserController::class, 'update'])->name('users.update');
                Route::post('utilisateurs/{user}/reinitialiser', [Admin\UserController::class, 'resetPassword'])->name('users.reset');
            });
            Route::get('roles', [Admin\RoleController::class, 'index'])->middleware('can:roles.view')->name('roles.index');
            Route::get('services', [Admin\ServiceController::class, 'index'])->middleware('can:services.manage')->name('services.index');
            Route::post('services', [Admin\ServiceController::class, 'store'])->middleware('can:services.manage')->name('services.store');
            Route::put('services/{service}', [Admin\ServiceController::class, 'update'])->middleware('can:services.manage')->name('services.update');
            Route::get('audit', [Admin\AuditLogController::class, 'index'])->middleware('can:audit.view')->name('audit.index');
        });
    });
});
