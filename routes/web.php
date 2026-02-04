<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PromptController;
use App\Http\Controllers\Admin\ChatGptModelController;
use App\Http\Controllers\Admin\SiteProjectController;
use App\Http\Controllers\Admin\PalladiumConfigController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\DomainController;
use App\Http\Controllers\Buyer\SiteController;
use App\Http\Controllers\Buyer\DomainDeploymentController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Buyers management
    Route::resource('buyers', BuyerController::class);
    Route::post('/buyers/{buyer}/toggle-status', [BuyerController::class, 'toggleStatus'])->name('buyers.toggle-status');

    // Servers management
    Route::resource('servers', ServerController::class);
    Route::post('/servers/{server}/toggle-status', [ServerController::class, 'toggleStatus'])->name('servers.toggle-status');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Prompts management
    Route::resource('prompts', PromptController::class);
    Route::post('/prompts/{prompt}/toggle-status', [PromptController::class, 'toggleStatus'])->name('prompts.toggle-status');

    // ChatGPT Models management
    Route::resource('chatgpt-models', ChatGptModelController::class);
    Route::post('/chatgpt-models/{chatgpt_model}/toggle-status', [ChatGptModelController::class, 'toggleStatus'])->name('chatgpt-models.toggle-status');

    // Site Projects management (white sites)
    Route::get('/site-projects', [SiteProjectController::class, 'index'])->name('site-projects.index');
    Route::get('/site-projects/{project}', [SiteProjectController::class, 'show'])->name('site-projects.show');
    Route::delete('/site-projects/{project}/files', [SiteProjectController::class, 'deleteFiles'])->name('site-projects.delete-files');
    Route::delete('/site-projects/{project}', [SiteProjectController::class, 'destroy'])->name('site-projects.destroy');

    // Palladium Configs management
    Route::get('/palladium-configs', [PalladiumConfigController::class, 'index'])->name('palladium-configs.index');
    Route::get('/palladium-configs/create', [PalladiumConfigController::class, 'create'])->name('palladium-configs.create');
    Route::post('/palladium-configs', [PalladiumConfigController::class, 'store'])->name('palladium-configs.store');
    Route::delete('/palladium-configs/{palladiumConfig}', [PalladiumConfigController::class, 'destroy'])->name('palladium-configs.destroy');

    // Offers management
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/create', [OfferController::class, 'create'])->name('offers.create');
    Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
    Route::delete('/offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
});

// Buyer routes
Route::prefix('buyer')->name('buyer.')->middleware(['auth', 'buyer'])->group(function () {
    Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->name('dashboard');

    // Domains
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::get('/domains/create', [DomainController::class, 'create'])->name('domains.create');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
    Route::get('/domains/batches', [DomainController::class, 'batches'])->name('domains.batches');
    Route::get('/domains/batch/{batch}', [DomainController::class, 'batch'])->name('domains.batch');
    Route::get('/domains/batch/{batch}/status', [DomainController::class, 'batchStatus'])->name('domains.batch.status');
    Route::get('/domains/pending-dns', [DomainController::class, 'pendingDns'])->name('domains.pending-dns');
    Route::post('/domains/retry-dns', [DomainController::class, 'retryDns'])->name('domains.retry-dns');

    // Sites generation
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites/select-prompt', [SiteController::class, 'selectPrompt'])->name('sites.select-prompt');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{project}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('/sites/{project}/status', [SiteController::class, 'status'])->name('sites.status');
    Route::delete('/sites/{project}', [SiteController::class, 'destroy'])->name('sites.destroy');
    Route::delete('/sites/deployment/{deployment}', [SiteController::class, 'destroyDeployment'])->name('sites.deployment.destroy');
    Route::post('/sites/deployment/{deployment}/retry-ssl', [SiteController::class, 'retrySsl'])->name('sites.deployment.retry-ssl');

    // Domain Deployments (Black/White sites)
    Route::get('/domain-deployments', [DomainDeploymentController::class, 'index'])->name('domain-deployments.index');
    Route::get('/domain-deployments/create', [DomainDeploymentController::class, 'create'])->name('domain-deployments.create');
    Route::post('/domain-deployments', [DomainDeploymentController::class, 'store'])->name('domain-deployments.store');
    Route::get('/domain-deployments/{deployment}', [DomainDeploymentController::class, 'show'])->name('domain-deployments.show');
    Route::get('/domain-deployments/{deployment}/status', [DomainDeploymentController::class, 'status'])->name('domain-deployments.status');
    Route::get('/domain-deployments/{deployment}/logs', [DomainDeploymentController::class, 'getLogs'])->name('domain-deployments.logs');
    Route::get('/domain-deployments/{deployment}/attach-black-site', [DomainDeploymentController::class, 'attachBlackSiteForm'])->name('domain-deployments.attach-black-site');
    Route::post('/domain-deployments/{deployment}/attach-black-site', [DomainDeploymentController::class, 'attachBlackSite'])->name('domain-deployments.attach-black-site.store');
    Route::get('/domain-deployments/{deployment}/edit', [DomainDeploymentController::class, 'edit'])->name('domain-deployments.edit');
    Route::put('/domain-deployments/{deployment}', [DomainDeploymentController::class, 'update'])->name('domain-deployments.update');
    Route::delete('/domain-deployments/{deployment}', [DomainDeploymentController::class, 'destroy'])->name('domain-deployments.destroy');
});
