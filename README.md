# Sistem SPJ Otomatis

Aplikasi web untuk mengelola administrasi dan Surat Pertanggungjawaban (SPJ)
pekerjaan konsultansi/jasa secara otomatis berdasarkan data proyek dan
kontrak, dengan pendekatan **Data → Rule → Template → Document**.

Baca lebih dulu:

- [`PROJECT_BLUEPRINT.md`](PROJECT_BLUEPRINT.md) — visi produk, arsitektur, roadmap fase.
- [`PROJECT_DECISIONS.md`](PROJECT_DECISIONS.md) — log keputusan arsitektur.
- [`CLAUDE.md`](CLAUDE.md) — aturan wajib untuk siapa pun (manusia atau agent) yang mengerjakan repo ini.

## Stack

PHP 8.4 · Laravel 13 · PostgreSQL 16 · Livewire 4 · Alpine.js · Tailwind CSS 4 · Pest 4 · Larastan · Pint.

## Setup lokal

```bash
composer install
npm install
cp .env.example .env   # sudah ada .env, sesuaikan kredensial DB bila perlu
php artisan key:generate
php artisan migrate --seed
npm run dev
```

Database PostgreSQL yang dipakai: `master_spj` (dev) dan `master_spj_test` (test).

## Perintah pengembangan

```bash
composer ci     # pint --test + phpstan (level 8) + pest — jalankan sebelum commit
composer test   # pest saja
composer stan    # phpstan saja
composer lint    # pint (auto-fix format)
```

## Status pengembangan

Lihat tabel fase di `PROJECT_BLUEPRINT.md` §9/§12 dan `PROJECT_HANDOVER.md`.
Saat ini: **Phase 1 (Foundation) selesai**, lanjut ke **Phase 2 — Project**.

## Login demo (lokal saja)

| Role | Email | Password |
|---|---|---|
| Super Admin | superadmin@master-spj.test | password |
| Admin Perusahaan | admin@ciptarencana.example | password |
| Staff | staff@ciptarencana.example | password |
