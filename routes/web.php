<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PublicQuotationController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallerController::class, 'show'])->name('install');
Route::post('/install', [InstallerController::class, 'store'])->middleware('throttle:10,1');
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->middleware(['auth', 'verified', 'throttle:10,1'])->name('invitations.accept');
Route::prefix('q/{token}')->middleware(['throttle:public-quotation', 'public.document'])->group(function (): void {
    Route::get('/', [PublicQuotationController::class, 'show'])->name('public.quotation.show');
    Route::get('/pdf', [PublicQuotationController::class, 'pdf'])->name('public.quotation.pdf');
    Route::post('/selections', [PublicQuotationController::class, 'select'])->name('public.quotation.select');
    Route::post('/comments', [PublicQuotationController::class, 'comment'])->name('public.quotation.comment');
    Route::post('/revision', [PublicQuotationController::class, 'requestRevision'])->name('public.quotation.revision');
    Route::post('/decision', [PublicQuotationController::class, 'decide'])->name('public.quotation.decision');
});

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified', 'workspace'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/workspaces/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::get('/settings/business', [WorkspaceSettingsController::class, 'edit'])->middleware('workspace.permission:workspace.settings')->name('settings.business');
    Route::put('/settings/business', [WorkspaceSettingsController::class, 'update'])->middleware('workspace.permission:workspace.settings');
    Route::get('/team', [TeamController::class, 'index'])->middleware('workspace.permission:team.view')->name('team.index');
    Route::post('/team/invitations', [TeamController::class, 'invite'])->middleware('workspace.permission:team.manage')->name('team.invite');
    Route::patch('/team/members/{membership}/deactivate', [TeamController::class, 'deactivate'])->middleware('workspace.permission:team.manage')->name('team.deactivate');
    Route::get('/customers/export', [CustomerController::class, 'export'])->middleware('workspace.permission:customers.view')->name('customers.export');
    Route::post('/customers/import', [CustomerController::class, 'import'])->middleware('workspace.permission:customers.manage')->name('customers.import');
    Route::resource('customers', CustomerController::class)->except('show')->middleware('workspace.permission:customers.manage');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('workspace.permission:customers.view')->name('customers.show');
    Route::post('/customers/{customer}/contacts', [CustomerController::class, 'storeContact'])->middleware('workspace.permission:customers.manage')->name('customers.contacts.store');
    Route::delete('/customers/{customer}/contacts/{contact}', [CustomerController::class, 'destroyContact'])->middleware('workspace.permission:customers.manage')->name('customers.contacts.destroy');
    Route::get('/services', [ServiceController::class, 'index'])->middleware('workspace.permission:services.view')->name('services.index');
    Route::resource('services', ServiceController::class)->except(['index', 'show'])->middleware('workspace.permission:services.manage');
    Route::get('/quotations', [QuotationController::class, 'index'])->middleware('workspace.permission:quotations.view')->name('quotations.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->middleware('workspace.permission:quotations.manage')->name('quotations.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->middleware('workspace.permission:quotations.manage')->name('quotations.store');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->middleware('workspace.permission:quotations.view')->name('quotations.show');
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->middleware('workspace.permission:quotations.view')->name('quotations.pdf');
    Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->middleware('workspace.permission:quotations.manage')->name('quotations.edit');
    Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])->middleware('workspace.permission:quotations.manage')->name('quotations.update');
    Route::post('/quotations/{quotation}/transition', [QuotationController::class, 'transition'])->middleware('workspace.permission:quotations.manage')->name('quotations.transition');
    Route::post('/quotations/{quotation}/revise', [QuotationController::class, 'revise'])->middleware('workspace.permission:quotations.manage')->name('quotations.revise');
    Route::post('/quotations/{quotation}/share', [QuotationController::class, 'share'])->middleware('workspace.permission:quotations.manage')->name('quotations.share');
    Route::delete('/quotations/{quotation}/public-links', [QuotationController::class, 'revokePublicLinks'])->middleware('workspace.permission:quotations.manage')->name('quotations.public-links.revoke');
    Route::get('/templates', [TemplateController::class, 'index'])->middleware('workspace.permission:quotations.view')->name('templates.index');
    Route::get('/templates/{template}', [TemplateController::class, 'preview'])->middleware('workspace.permission:quotations.view')->name('templates.preview');
    Route::post('/templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->middleware('workspace.permission:quotations.manage')->name('templates.duplicate');
});
