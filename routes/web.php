<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DocumentTemplate\DownloadDocumentTemplateController;
use App\Http\Controllers\Evidence\DownloadEvidenceController;
use App\Http\Controllers\GeneratedDocument\DownloadGeneratedDocumentController;
use App\Http\Controllers\Personnel\DownloadPersonnelDocumentController;
use App\Http\Controllers\Spj\ExportSpjPackageController;
use App\Livewire\Auth\Login;
use App\Livewire\Clients;
use App\Livewire\CostCategories;
use App\Livewire\Dashboard;
use App\Livewire\DocumentRequirements;
use App\Livewire\DocumentTemplates;
use App\Livewire\Organizations;
use App\Livewire\Personnel;
use App\Livewire\PersonnelCategories;
use App\Livewire\Projects;
use App\Livewire\ProjectTypes;
use App\Livewire\TaxTypes;
use App\Livewire\TemplateVariables;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/organizations', Organizations\Index::class)->name('organizations.index');
    Route::get('/organizations/create', Organizations\Form::class)->name('organizations.create');
    Route::get('/organizations/{organization}/edit', Organizations\Form::class)->name('organizations.edit');

    Route::get('/users', Users\Index::class)->name('users.index');
    Route::get('/users/create', Users\Form::class)->name('users.create');
    Route::get('/users/{user}/edit', Users\Form::class)->name('users.edit');

    Route::get('/project-types', ProjectTypes\Index::class)->name('project-types.index');
    Route::get('/project-types/create', ProjectTypes\Form::class)->name('project-types.create');
    Route::get('/project-types/{projectType}/edit', ProjectTypes\Form::class)->name('project-types.edit');

    Route::get('/clients', Clients\Index::class)->name('clients.index');
    Route::get('/clients/create', Clients\Form::class)->name('clients.create');
    Route::get('/clients/{client}/edit', Clients\Form::class)->name('clients.edit');

    Route::get('/projects', Projects\Index::class)->name('projects.index');
    Route::get('/projects/create', Projects\Form::class)->name('projects.create');
    Route::get('/projects/{project}', Projects\Show::class)->name('projects.show');
    Route::get('/projects/{project}/edit', Projects\Form::class)->name('projects.edit');

    Route::get('/personnel-categories', PersonnelCategories\Index::class)->name('personnel-categories.index');
    Route::get('/personnel-categories/create', PersonnelCategories\Form::class)->name('personnel-categories.create');
    Route::get('/personnel-categories/{personnelCategory}/edit', PersonnelCategories\Form::class)->name('personnel-categories.edit');

    Route::get('/personnel', Personnel\Index::class)->name('personnel.index');
    Route::get('/personnel/create', Personnel\Form::class)->name('personnel.create');
    Route::get('/personnel/{personnel}', Personnel\Show::class)->name('personnel.show');
    Route::get('/personnel/{personnel}/edit', Personnel\Form::class)->name('personnel.edit');

    Route::get('/personnel-documents/{personnelDocument}/download', DownloadPersonnelDocumentController::class)
        ->name('personnel-documents.download');

    Route::get('/tax-types', TaxTypes\Index::class)->name('tax-types.index');
    Route::get('/tax-types/create', TaxTypes\Form::class)->name('tax-types.create');
    Route::get('/tax-types/{taxType}/edit', TaxTypes\Form::class)->name('tax-types.edit');

    Route::get('/cost-categories', CostCategories\Index::class)->name('cost-categories.index');
    Route::get('/cost-categories/create', CostCategories\Form::class)->name('cost-categories.create');
    Route::get('/cost-categories/{costCategory}/edit', CostCategories\Form::class)->name('cost-categories.edit');

    Route::get('/document-requirements', DocumentRequirements\Index::class)->name('document-requirements.index');
    Route::get('/document-requirements/create', DocumentRequirements\Form::class)->name('document-requirements.create');
    Route::get('/document-requirements/{documentRequirement}/edit', DocumentRequirements\Form::class)->name('document-requirements.edit');
    Route::get('/document-requirements/{documentRequirement}/templates', DocumentTemplates\Manager::class)->name('document-templates.manage');

    Route::get('/document-templates/{documentTemplate}/download', DownloadDocumentTemplateController::class)
        ->name('document-templates.download');

    Route::get('/generated-documents/{document}/download/{type}', DownloadGeneratedDocumentController::class)
        ->name('generated-documents.download');

    Route::get('/evidences/{evidence}/download', DownloadEvidenceController::class)
        ->name('evidences.download');

    Route::get('/spj-packages/{spjPackage}/export', ExportSpjPackageController::class)
        ->name('spj-packages.export');

    Route::get('/template-variables', TemplateVariables\Index::class)->name('template-variables.index');
    Route::get('/template-variables/create', TemplateVariables\Form::class)->name('template-variables.create');
    Route::get('/template-variables/{templateVariable}/edit', TemplateVariables\Form::class)->name('template-variables.edit');
});
