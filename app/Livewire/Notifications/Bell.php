<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Domain\Notification\Services\NotificationService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Bell notifikasi global — dirender di `layouts/app.blade.php` pada
 * SETIAP halaman (bukan nested di satu project). Tidak butuh
 * `authorize()` khusus: `NotificationService` sudah scoping alert-nya
 * sendiri berdasarkan organisasi user yang sedang login.
 */
class Bell extends Component
{
    public function dismiss(string $key, NotificationService $service): void
    {
        /** @var User $user */
        $user = Auth::user();

        $service->dismiss($user, $key);
    }

    public function render(NotificationService $service): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.notifications.bell', [
            'alerts' => $service->pending($user),
        ]);
    }
}
