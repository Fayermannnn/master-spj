# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 2 — Project: SELESAI.** Siap lanjut ke **Phase 3 — Personnel**
(tenaga ahli/pendukung, penugasan ke project, dokumen personel).

## Completed Features

### Phase 1 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, layout, RBAC (`spatie/laravel-permission`), Organization CRUD, User
Management, audit log append-only.

### Phase 2 (baru)

- **ProjectType** — master data GLOBAL (bukan per-organisasi), hanya
  `super_admin` (permission `project_types.manage`) yang bisa
  create/update/delete; semua user bisa `viewAny`/`view` (dipakai sebagai
  picker saat membuat project). CRUD Livewire lengkap.
- **Client & Contact** — `Client` (Instansi) per-organisasi, punya banyak
  `Contact` (PPK/PPTK/PA-KPA/Lainnya — enum `ContactType`). Form Client
  mengelola contacts secara nested (tambah/ubah/hapus dalam satu form,
  di-sync oleh `ClientService::syncContacts()` berdasarkan `id` — baris
  tanpa `id` dibuat baru, yang hilang dari daftar dihapus). Delete client
  diblokir kalau masih ada project terkait.
- **Project** — entitas inti dengan status siklus 7 tahap (`ProjectStatus`
  enum: Draft, Preparation, Active, PaymentProcessing, Completed, Closed,
  Archived) dan transisi tervalidasi lewat
  `ProjectService::transitionStatus()` (peta transisi di
  `ProjectStatus::allowedTransitions()`, BUKAN state-machine generik —
  lihat `PROJECT_DECISIONS.md` D-010). Halaman detail (`Projects\Show`)
  bertab: Overview + Kontrak. Delete project diblokir kecuali status
  masih Draft. Organization-scoped sama seperti Client (D-009).
- **Contract** — 1:1 dengan Project, detail legal/finansial (nomor
  kontrak, tanggal, SPMK, nilai kontrak, pajak, nilai bersih). Dikelola
  via komponen Livewire nested `Contracts\Form` di dalam tab Kontrak
  Project (bukan halaman/route terpisah — lihat `ContractService::save()`,
  pola upsert). Diaudit lewat `AuditLogService` sama seperti domain lain.
- **Domain baru:** `ProjectManagement` (Project, ProjectType),
  `Client` (Client, Contact), `Contract` (Contract) sekarang berisi kode
  nyata (model, service, policy, enum).
- **Seed data referensi (RULE §44/45 master prompt):**
  `ProjectTypeSeeder` (8 jenis project baseline) dan
  `ReferenceProjectSeeder` — project sample "Jasa Konsultansi Perencanaan
  Master Plan RSPNDD" lengkap dengan client (Pemerintah Kabupaten Mahakam
  Ulu), PPK (dr. Josimar Hagusvaro Sinaga), dan contract (nilai
  Rp1.980.610.920, durasi 110 hari) — SEMUA lewat database seeder, bukan
  hard-code (lihat `PROJECT_BLUEPRINT.md` §3).
- **Keamanan yang diuji eksplisit:** tamper test untuk `organization_id`
  pada Project — perbaikan bug nyata di mana mengoreksi `organization_id`
  SETELAH validasi bisa membiarkan `client_id` dari organisasi lain lolos
  (client_id divalidasi relatif terhadap organization_id yang sudah
  benar SEBELUM `validate()` dipanggil, bukan sesudah).
- 26 test Pest (13 Phase 1 + 13 Phase 2 baru): ProjectType CRUD +
  permission, Client CRUD + nested contact sync + cross-org tamper +
  delete-guard, Project CRUD + status transition valid/invalid +
  delete-guard + cross-org view denial. Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- Tax handling di Contract masih sederhana (`tax_amount`/`net_value`
  sebagai kolom decimal manual) — belum ada Tax Type/Tax Rate master data
  configurable (§59 master prompt). Ditunda ke Phase 4 (Cost & Payment)
  karena situ tempat kalkulasi pajak paling sering dipakai berulang.
  **Perlu diperhatikan saat Phase 4 dimulai.**
- Kontrak addendum (revisi nilai kontrak di tengah jalan) belum
  didukung — relasi Project↔Contract masih murni 1:1. Kalau dibutuhkan,
  perlu migration tambahan (`contracts.is_current`/`version` atau tabel
  `contract_amendments`) — BUKAN pekerjaan trivial, diskusikan dulu kalau
  muncul kebutuhan nyata.
- `project_manager_name` masih kolom string biasa di `projects` (bukan FK
  ke Personnel) karena domain Personnel belum ada. **Phase 3 harus
  memutuskan:** tambah `personnel_id` nullable FK di `projects` yang
  menggantikan/mendampingi kolom string ini, atau biarkan keduanya
  (string untuk personil eksternal yang belum tercatat, FK untuk yang
  sudah). Ini keputusan arsitektur kecil yang perlu dikonfirmasi di awal
  Phase 3, bukan diasumsikan sendiri.
- Halaman "Lihat" project untuk role selain super_admin/admin_perusahaan
  (mis. staff/viewer) belum diuji end-to-end di browser (hanya diuji lewat
  Pest policy test) — layak dicek manual sebelum Phase 3 kalau ada waktu.

## Database Changes (Phase 2)

- `project_types`: ulid PK, code (unique), name, description, is_active,
  soft delete. Global (tidak ada organization_id).
- `clients`: ulid PK, organization_id (FK), name, address, phone, email,
  notes, is_active, soft delete.
- `contacts`: ulid PK, client_id (FK, cascade), type (string/enum
  ContactType), name, position, phone, email, notes, soft delete.
- `projects`: ulid PK, organization_id/project_type_id/client_id (FK),
  ppk_contact_id (FK ke contacts, nullable, nullOnDelete), code (unique),
  name, unit_work, project_manager_name, start_date, end_date,
  duration_days, status (string), description, notes, created_by (FK
  users, nullable), soft delete.
- `contracts`: ulid PK, project_id (FK, unique — 1:1), contract_number,
  contract_date, spmk_number, spmk_date, contract_value, tax_amount,
  net_value, notes, soft delete.

## Environment

Tidak berubah dari Phase 1 — lihat bagian ini di git history/versi
sebelumnya file ini kalau perlu, atau `PROJECT_BLUEPRINT.md` §2.

## Next Task

**Phase 3 — Personnel Management**: Personnel (tenaga ahli/tenaga
pendukung, kategori configurable), PersonnelAssignment (penugasan ke
project — quantity/durasi/unit billing sesuai §14 master prompt: OB/OH/
OM/LS/dll, configurable bukan hard-code), PersonnelDocument (KTP/NPWP/CV/
SKA/SKK dengan file upload — lihat §41 keamanan: validasi MIME, ukuran,
no arbitrary file execution).

Sebelum mulai, putuskan (lihat §Known Issues di atas):
1. Hubungan `project_manager_name` vs Personnel — pertahankan string,
   tambah FK personnel, atau keduanya?
2. Struktur "posisi/kategori personel" — hard tabel `personnel_categories`
   (master data mirip project_types) atau enum tertutup? Reference KAK
   RSPNDD punya ~20 posisi berbeda (§12 master prompt) yang mengisyaratkan
   kategori harus configurable, bukan enum PHP tertutup.
3. Evidence/dokumen personel: mulai bangun `FileStorageService` generik di
   Phase 3 ini (dipakai ulang di Phase 6-8 untuk Evidence/Document), atau
   tunda dan pakai `Storage::` langsung dulu? Rekomendasi: bangun sekarang
   sebagai service tipis, supaya konvensi path (`/projects/{id}/personnel/`
   dst per §42 master prompt) konsisten sejak awal.

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 26
test, 64 assertion, 0 error phpstan, 0 pint diff.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-010). Baru di Phase 2: D-008
(normalisasi Project vs Contract), D-009 (ProjectType global vs
Client/Project per-organisasi), D-010 (status project = enum + validasi
service, bukan FSM generik).

## Security Notes

Tidak berubah dari Phase 1 — kredensial demo (`password`) hanya untuk
lokal/demo.
