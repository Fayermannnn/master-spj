# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 1 — Foundation: SELESAI.** Siap lanjut ke **Phase 2 — Project**
(Project, Client, Contract, Project Type).

## Completed Features

- Repo git independen (lihat `PROJECT_DECISIONS.md` D-001), Laravel 13.31 +
  Livewire 4 + Tailwind 4 + Pest 4 + Larastan (level 8) + Pint, terhubung ke
  PostgreSQL (`master_spj` dev / `master_spj_test` test).
- Struktur modular monolith: 19 domain di `app/Domain/` (README per domain).
- **Identity domain:** autentikasi (login/logout, rate limiting 5x/menit,
  akun nonaktif diblokir tanpa membocorkan alasan), RBAC via
  `spatie/laravel-permission` (5 role: super_admin, admin_perusahaan,
  project_admin, staff, viewer; permission per modul, lihat
  `App\Domain\Identity\Enums\PermissionName`), `Gate::before` bypass untuk
  super_admin.
- **Organization domain:** CRUD organisasi (Livewire, policy, service,
  audit log), super_admin kelola semua organisasi, admin_perusahaan hanya
  bisa update organisasi sendiri, delete diblokir kalau organisasi masih
  punya user aktif.
- **User Management:** CRUD user dengan role assignment via checkbox,
  organisasi otomatis di-scope ke organisasi actor untuk non-super-admin
  (dan dipaksa ulang di server saat save — bukan cuma disembunyikan di UI),
  admin_perusahaan tidak bisa assign role `super_admin`, user tidak bisa
  menghapus akun sendiri.
- **Audit log:** append-only (`update()`/`delete()` di model melempar
  `LogicException`), tercatat otomatis dari `OrganizationService` dan
  `UserService` untuk create/update/delete.
- **Layout:** sidebar + topbar + breadcrumb slot govtech-style (Tailwind),
  menu sidebar menyesuaikan permission user, responsive (drawer mobile
  teruji manual).
- Seed data: role/permission baseline, 1 organisasi demo ("PT Cipta Rencana
  Konsultan"), 3 user demo (super admin, admin perusahaan, staff) — lihat
  `database/seeders/`. Kredensial: email di atas, password `password`
  (LOKAL/DEMO SAJA — lihat §Security Notes).
- 13 Pest test (Feature): login (sukses/salah/nonaktif), CRUD organisasi +
  audit log + append-only guard, CRUD user + RBAC scoping + anti-tampering
  + anti-self-delete + anti role-escalation. Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- Password reset / "lupa password" belum ada — dianggap di luar MVP Phase 1
  (§73 master prompt: reasonable default, dicatat sebagai asumsi).
- Role & Permission CRUD (UI untuk menambah role/permission baru) belum
  ada — Phase 1 pakai 5 role seeded tetap. Kalau dibutuhkan lebih awal dari
  jadwal, bisa masuk modul Settings (Phase mendatang) alih-alih ditambah
  ad-hoc sekarang.
- Dashboard baru placeholder (jumlah organisasi/user) — KPI project/SPJ
  menyusul Phase 9.
- Belum ada API/rate-limit global di luar login; tidak relevan sampai ada
  endpoint publik/API di fase mendatang.

## Database Changes (Phase 1)

- `organizations` (baru): ulid PK, code/name/npwp/address/phone/email,
  is_active, soft delete.
- `users`: PK diubah ke ulid, tambah `organization_id` (FK, nullable),
  `is_active`, `last_login_at`, soft delete.
- `sessions.user_id`: disesuaikan ke ulid agar konsisten dengan `users.id`.
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`,
  `role_has_permissions` (spatie/laravel-permission) — morph key
  `model_id` diubah ke ulid (lihat `PROJECT_DECISIONS.md` D-006).
- `audit_logs` (baru): ulid PK, append-only, index pada
  `auditable_type+auditable_id`, `module+action`, `created_at`.

## Environment

- PHP 8.4.23 (Herd), Composer 2.10.2, Node v25.2.1/npm 11.6.2, PostgreSQL
  16.15 lokal (akses tanpa password untuk user OS `firmansyah`).
- `.env`: `DB_DATABASE=master_spj`, test env (`phpunit.xml`) pakai
  `master_spj_test`. `APP_URL=http://master-spj.test`,
  `APP_LOCALE=id`.
- Dev server lokal: `.claude/launch.json` → `php artisan serve --port=8123`
  (dipakai untuk uji manual di Browser pane; build asset dulu dengan
  `npm run build` kalau tidak menjalankan `npm run dev`/vite terpisah).

## Next Task

**Phase 2 — Project Management**: entitas Project inti (kode, nama, tipe,
status siklus, tanggal, ringkasan), Client/PPK/PPTK, Contract, Project Type
sebagai master data configurable (lihat `PROJECT_BLUEPRINT.md` §6). Ikuti
siklus per modul di `CLAUDE.md` (migration → model → service → policy →
Livewire UI → test → jalankan test → review).

Sebelum mulai: putuskan struktur status project (§9 master prompt: Draft,
Preparation, Active, Payment Processing, Completed, Closed, Archived) —
apakah butuh state machine formal seperti proyek lain di direktori induk,
atau cukup enum + validasi transisi sederhana di service layer (MVP-nya
cenderung ke opsi kedua, tapi konfirmasi dulu kalau ada keraguan).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 13 test,
35 assertion, 0 error phpstan, 0 pint diff.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-007). Ringkasan: repo git
terpisah dari proyek lain; Livewire 4 (bukan 3); test terhadap PostgreSQL
asli; locale `id`; domain `Identity` ditambah terpisah dari `Organization`;
RBAC pakai `spatie/laravel-permission` dengan ULID morph key; validasi
Livewire pakai `rules()` komponen (bukan `FormRequest` terpisah) untuk
modul yang murni Livewire-driven.

## Security Notes

Kredensial demo (`password`) HANYA untuk lokal/demo. Sebelum deploy ke
lingkungan manapun yang bisa diakses orang lain, ganti seluruh password
seed dan pertimbangkan menonaktifkan `UserSeeder`/`OrganizationSeeder` di
environment non-lokal.
