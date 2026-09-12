<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Domain\Identity\Services\UserService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman self-service untuk user yang sedang login mengubah profil &
 * password MEREKA SENDIRI — sengaja TERPISAH dari `Users\Form`
 * (mengelola user LAIN, butuh permission `users.update`). Tidak ada
 * `authorize()` di sini: operasinya SELALU terhadap `Auth::user()`
 * sendiri, tidak pernah menerima ID user dari luar, jadi tidak ada
 * celah IDOR untuk digerbangi — cukup middleware `auth` di route.
 */
#[Layout('layouts.app', ['title' => 'Profil Saya'])]
class Edit extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Properti komponen biasa (BUKAN `session()->flash()`) — halaman ini
     * tidak pernah redirect setelah submit (form profil & password ada
     * di halaman yang sama), dan flash banner global di
     * `layouts/app.blade.php` hanya ter-render ulang lewat navigasi
     * penuh, bukan update Livewire di tempat. Dua pesan status TERPISAH
     * (bukan satu) supaya submit form profil tidak menimpa pesan sukses
     * form password yang sedang tampil, atau sebaliknya.
     */
    public ?string $profileStatus = null;

    public ?string $passwordStatus = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile(UserService $service): void
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $service->update($user, $data);

        $this->passwordStatus = null;
        $this->profileStatus = 'Profil berhasil diperbarui.';
    }

    public function updatePassword(UserService $service): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $service->update($user, ['password' => $this->password]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->profileStatus = null;
        $this->passwordStatus = 'Password berhasil diubah.';
    }

    public function render(): View
    {
        return view('livewire.profile.edit');
    }
}
