<?php

declare(strict_types=1);

namespace App\Livewire\Personnel;

use App\Models\Personnel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Personnel $personnel;

    public string $activeTab = 'overview';

    public function mount(Personnel $personnel): void
    {
        $this->authorize('view', $personnel);

        $this->personnel = $personnel->load(['organization', 'category']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(): View
    {
        return view('livewire.personnel.show')->title($this->personnel->name);
    }
}
