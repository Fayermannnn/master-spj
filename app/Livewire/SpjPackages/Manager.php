<?php

declare(strict_types=1);

namespace App\Livewire\SpjPackages;

use App\Domain\Shared\Exceptions\DomainActionException;
use App\Domain\Spj\Services\SpjPackageService;
use App\Models\Project;
use App\Models\SpjPackage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Paket SPJ" pada Projects\Show.
 * Akses diatur lewat ProjectPolicy, sama seperti manager lain di project
 * (D-014/D-018/D-019). Mode list vs detail diatur lewat
 * `$selectedPackageId`, pola sama dengan GeneratedDocuments\Manager.
 */
class Manager extends Component
{
    public Project $project;

    public ?string $selectedPackageId = null;

    public string $newPackageName = '';

    public string $newPackagePaymentId = '';

    public string $newPackageNotes = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function createPackage(SpjPackageService $service): void
    {
        $this->authorize('update', $this->project);

        $this->validate([
            'newPackageName' => 'required|string|max:255',
            'newPackageNotes' => 'nullable|string|max:1000',
        ]);

        /** @var User $creator */
        $creator = Auth::user();

        $payment = $this->newPackagePaymentId !== ''
            ? $this->project->payments()->find($this->newPackagePaymentId)
            : null;

        $package = $service->create($this->project, $this->newPackageName, $payment, $this->newPackageNotes ?: null, $creator);

        $this->reset(['newPackageName', 'newPackagePaymentId', 'newPackageNotes']);
        $this->selectedPackageId = $package->id;
        session()->flash('status', "Paket \"{$package->name}\" berhasil dibuat.");
    }

    public function selectPackage(string $packageId): void
    {
        $this->selectedPackageId = $packageId;
    }

    public function backToList(): void
    {
        $this->selectedPackageId = null;
    }

    public function addDocument(string $documentId, SpjPackageService $service): void
    {
        $this->authorize('update', $this->project);

        $package = $this->findPackage();
        $document = $this->project->documents()->findOrFail($documentId);

        try {
            $service->addDocument($package, $document);
        } catch (DomainActionException $exception) {
            $this->addError('manifest', $exception->getMessage());
        }
    }

    public function addEvidence(string $evidenceId, SpjPackageService $service): void
    {
        $this->authorize('update', $this->project);

        $package = $this->findPackage();
        $evidence = $this->project->evidences()->findOrFail($evidenceId);

        try {
            $service->addEvidence($package, $evidence);
        } catch (DomainActionException $exception) {
            $this->addError('manifest', $exception->getMessage());
        }
    }

    public function removeItem(string $itemId, SpjPackageService $service): void
    {
        $this->authorize('update', $this->project);

        $package = $this->findPackage();
        $item = $package->items()->findOrFail($itemId);

        try {
            $service->removeItem($item);
        } catch (DomainActionException $exception) {
            $this->addError('manifest', $exception->getMessage());
        }
    }

    public function finalize(SpjPackageService $service): void
    {
        $this->authorize('update', $this->project);

        $package = $this->findPackage();

        try {
            $service->finalize($package);
            session()->flash('status', "Paket \"{$package->name}\" berhasil difinalisasi.");
        } catch (DomainActionException $exception) {
            $this->addError('manifest', $exception->getMessage());
        }
    }

    public function deletePackage(string $packageId, SpjPackageService $service): void
    {
        $this->authorize('update', $this->project);

        $package = $this->project->spjPackages()->findOrFail($packageId);
        $service->delete($package);

        if ($this->selectedPackageId === $packageId) {
            $this->selectedPackageId = null;
        }

        session()->flash('status', 'Paket berhasil dihapus.');
    }

    private function findPackage(): SpjPackage
    {
        return $this->project->spjPackages()->findOrFail($this->selectedPackageId);
    }

    public function render(SpjPackageService $service): View
    {
        $selectedPackage = null;
        $coverage = null;
        $availableDocuments = collect();
        $availableEvidences = collect();

        if ($this->selectedPackageId !== null) {
            $selectedPackage = $this->project->spjPackages()
                ->with(['items.document.documentRequirement', 'items.evidence.documentRequirement', 'payment'])
                ->find($this->selectedPackageId);
        }

        if ($selectedPackage !== null) {
            $coverage = $service->coverage($selectedPackage);

            $includedDocumentIds = $selectedPackage->items->pluck('document_id')->filter();
            $includedEvidenceIds = $selectedPackage->items->pluck('evidence_id')->filter();

            $availableDocuments = $this->project->documents()
                ->whereNotIn('id', $includedDocumentIds)
                ->with('documentRequirement')
                ->latest('version')
                ->get();

            $availableEvidences = $this->project->evidences()
                ->whereNotIn('id', $includedEvidenceIds)
                ->latest()
                ->get();
        } else {
            $this->selectedPackageId = null;
        }

        return view('livewire.spj-packages.manager', [
            'packages' => $this->project->spjPackages()->with('payment')->latest()->get(),
            'payments' => $this->project->payments,
            'selectedPackage' => $selectedPackage,
            'coverage' => $coverage,
            'availableDocuments' => $availableDocuments,
            'availableEvidences' => $availableEvidences,
        ]);
    }
}
