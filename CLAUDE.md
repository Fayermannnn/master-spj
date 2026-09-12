# CLAUDE.md — Panduan Agen untuk Repo Ini

Sistem **SPJ Otomatis**. Baca `PROJECT_BLUEPRINT.md` dan `PROJECT_DECISIONS.md`
sebelum bekerja. Reference project (KAK RSPNDD) hanya **seed/demo data**,
bukan konstanta aplikasi — lihat `PROJECT_BLUEPRINT.md` §3.

## Aturan wajib

1. **Modular monolith.** Kode domain di `app/Domain/<Domain>/`. Jangan
   hard-code logic untuk satu jenis pekerjaan/project tertentu (mis. jangan
   `if ($project->name === 'Master Plan RSPNDD')`). Business rule → data
   (project type, document requirement, template), bukan `if` statement.
2. **Document Requirement Engine berbasis data.** Kebutuhan dokumen SPJ
   ditentukan oleh Project Type + rule di database, dapat
   ditambah/diedit/dinonaktifkan admin tanpa deploy kode baru.
3. **RBAC via Policy, bukan hard-code role check di controller.** Role dan
   permission adalah master data yang dapat diperluas admin.
4. **Audit log append-only.** Tidak ada route/aksi update/delete pada
   `audit_logs`.
5. **AI & e-signature TIDAK diimplementasikan di MVP.** Siapkan interface
   (`SignatureProviderInterface`, dsb.) sebagai extension point saja.
6. **Uang selalu DECIMAL, tidak pernah float.** Currency: IDR.
7. **Migrasi non-destruktif.** Drop kolom/tabel berdata butuh konfirmasi
   user eksplisit.
8. **Dokumen final immutable.** Jika data project berubah setelah dokumen
   digenerate, dokumen lama tidak berubah — simpan data snapshot saat
   generate (lihat blueprint §"Data Snapshot").
9. **DoD per modul:** migration + model + FormRequest + Policy + service +
   Livewire UI + validation + audit + test (unit/feature) + dokumentasi.
   "UI jadi" ≠ "selesai".
10. Jika ambiguity mempengaruhi arsitektur → berhenti, jelaskan opsi ke
    user (jangan asumsikan sendiri untuk keputusan besar).

## Stack

PHP 8.4 (Herd) · Laravel 13 · PostgreSQL 16 · Livewire 4 · Alpine.js ·
Tailwind 4 · Pest 4 · Larastan (level 8) · Pint (strict types).

## Perintah

```bash
composer ci          # pint --test + phpstan + pest   (gate sebelum commit)
composer test        # pest saja
composer stan         # phpstan (level 8)
composer lint         # pint (perbaiki format)
php artisan migrate:fresh --seed
npm run dev
```

- Database dev: `master_spj`. Database test: `master_spj_test` (PostgreSQL
  asli, bukan SQLite — lihat `PROJECT_DECISIONS.md` D-003).
- Working directory bash mengikuti `cd` terakhir — selalu
  `cd "/Users/firmansyah/CLAUDE CODE/MASTER SPJ"` di awal (jangan bekerja di
  direktori induk `CLAUDE CODE`, itu proyek lain).

## Commit

`type(domain): ringkas` — mis. `feat(project): create project CRUD`.
Akhiri commit message dengan `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
Satu fase selesai → tag `phaseN-complete`.

## Status

Lihat tabel fase di `PROJECT_BLUEPRINT.md` §9. Keputusan arsitektur di
`PROJECT_DECISIONS.md`. Progres & next task di `PROJECT_HANDOVER.md` (dibuat
mulai akhir Phase 1).
