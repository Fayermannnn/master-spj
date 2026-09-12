# PROJECT BLUEPRINT — Sistem SPJ Otomatis

Status: Phase 0 (Discovery & Blueprint) — living document, diperbarui setiap fase.

## 1. Product Vision

Aplikasi web yang membantu perusahaan/konsultan mengelola administrasi dan
Surat Pertanggungjawaban (SPJ) pekerjaan **secara otomatis** berdasarkan data
proyek/kontrak yang sudah diinput, dengan pendekatan:

```
DATA → RULE → TEMPLATE → DOCUMENT
```

Aplikasi bersifat **generik dan configurable** — tidak dibangun khusus untuk
satu jenis pekerjaan. Proyek "Master Plan RSPNDD" (lihat §3) hanya dipakai
sebagai **reference/seed project** untuk menguji desain, bukan sebagai
konstanta di dalam kode.

## 2. Repository & Environment

- Repo git independen: `/Users/firmansyah/CLAUDE CODE/MASTER SPJ` (dipisah
  secara sengaja dari repo proyek lain — E-Supervisi Klinis Pendidikan —
  yang kebetulan berada di direktori induk `CLAUDE CODE`. Lihat
  `PROJECT_DECISIONS.md` §D-001).
- PHP 8.4.23 (Laravel Herd), Composer 2.10.2, Node v25.2.1 / npm 11.6.2,
  PostgreSQL 16.15 (lokal, akses tanpa password untuk user OS `firmansyah`).
- Database: `master_spj` (dev), `master_spj_test` (test — Pest jalan
  langsung ke Postgres, bukan sqlite in-memory, agar ada paritas
  jsonb/enum/constraint dengan production).

## 3. Reference Project (bukan hard-code)

KAK "Revisi Final KAK Jasa Konsultansi Perencanaan Master Plan RSPNDD" dipakai
sebagai contoh struktur data untuk seeding & pengujian desain:

- Instansi: Pemerintah Kabupaten Mahakam Ulu
- Unit Kerja: Rumah Sakit Pratama Nawacita Datah Dave
- Pekerjaan: Belanja Jasa Konsultansi Perencanaan Master Plan RSPNDD
- Lingkup: Feasibility Study, Master Plan, Konsepsi Perancangan, DED Blok Plan
- Durasi: 110 hari kalender, Pagu: Rp1.980.610.920, Tahun: 2026
- PPK: dr. Josimar Hagusvaro Sinaga
- ±20 posisi tenaga ahli/pendukung (lihat seeder `ReferencePersonnelSeeder`)

Data ini masuk lewat **database seeder**, bukan konstanta di source code.
Lihat §17 "Project Requirements vs SPJ Requirements" — tidak semua isi KAK
otomatis menjadi requirement SPJ.

## 4. Tech Stack

| Layer | Pilihan |
|---|---|
| Backend | Laravel 13.31 |
| Database | PostgreSQL 16 |
| Frontend | Blade + Livewire 4* + Alpine.js (bundled Livewire) |
| Styling | Tailwind CSS 4 |
| Auth | Laravel built-in auth (session) |
| Authorization | Spatie-style Policy per model + role/permission tabel sendiri |
| DOCX | `phpoffice/phpword` `TemplateProcessor`, delimiter `{{ }}` (Phase 7, D-018) |
| PDF | LibreOffice headless (`soffice --convert-to pdf`) via `Illuminate\Support\Facades\Process`, wajib terpasang (Phase 7, D-018) |
| Queue | Laravel Queue (database driver awal) |
| Testing | Pest 4 + pest-plugin-laravel |
| Static analysis | Larastan (level 8) |
| Formatting | Laravel Pint (strict_types diaktifkan) |
| Architecture | Modular Monolith (bukan microservices) |

\* Master prompt awal menyebut "Livewire 3"; per September 2026 versi stabil
terbaru adalah Livewire 4 (lihat `PROJECT_DECISIONS.md` §D-002).

## 5. Architecture — Modular Monolith

Kode domain di `app/Domain/<Domain>/`. Setiap domain punya folder sendiri
berisi (bertahap, tidak semua langsung ada di Phase 1):

```
app/Domain/<Domain>/
  Models/            (atau model tetap di app/Models dengan docblock @domain)
  Actions/            (business logic satuan / service tipis)
  Policies/
  Livewire/
  Http/Requests/
  Enums/
  DTO/
  README.md
```

19 domain (lihat README di masing-masing folder untuk detail):
Identity, Organization, ProjectManagement, Contract, Client, Personnel,
Cost, Payment, Workplan, DocumentRequirement, DocumentTemplate,
DocumentGenerator, Spj, Evidence, Workflow, Notification, AuditLog,
Settings, Shared.

`Identity` (User, Role, Permission, Auth) ditambahkan di Phase 1 — terpisah
dari `Organization` (entitas perusahaan/tenant). Lihat
`PROJECT_DECISIONS.md` D-006.

Aturan impor lintas domain akan didokumentasikan di `docs/domain-map.md`
begitu domain kedua mulai saling berelasi (Phase 2).

## 6. Core Concept — Project Type Driven

```
Project Type → Document Requirement Profile → Workflow → Templates → Variables → Rules
```

Project Type adalah master data yang dapat ditambah admin (bukan enum PHP
tertutup). Contoh awal: CONSULTANCY_PLANNING, CONSULTANCY_SUPERVISION,
SURVEY, STUDY, IT_DEVELOPMENT, CONSTRUCTION, NON_CONSTRUCTION, CUSTOM.

## 7. User Roles (awal, configurable)

SUPER_ADMIN, ADMIN_PERUSAHAAN, PROJECT_ADMIN, STAFF, VIEWER — permission
disimpan sebagai data (tabel `roles` + `permissions` + pivot), bukan
hard-coded di middleware, sehingga admin dapat menambah role/permission baru.

## 8. Database Entities (ringkas, akan detail di `docs/DATABASE.md` per fase)

`users, roles, permissions, organizations, clients, contacts, project_types,
projects, contracts, personnel, personnel_documents, personnel_assignments,
cost_categories, cost_items, payments, payment_items, deliverables,
document_requirements, requirement_rules, document_templates,
template_variables, documents, document_versions, evidences, spj_packages,
spj_items, workflows, workflow_steps, notifications, audit_logs, settings`

Prinsip: ULID untuk entity utama, soft delete untuk data penting, index pada
semua foreign key + `status` + `created_at`, JSON hanya untuk konfigurasi
fleksibel (bukan pengganti kolom relasional).

## 9. Development Roadmap (10 Fase)

| Fase | Fokus |
|---|---|
| 0 | Discovery & Blueprint (dokumen ini) |
| 1 | Foundation — auth, layout, user, roles, organization |
| 2 | Project — project, client, contract, project type |
| 3 | Personnel — personnel, assignment, personnel documents |
| 4 | Cost & Payment — cost, budget, payment, termin |
| 5 | Document Requirement — requirement engine + rule + checklist |
| 6 | Template — template upload, variable detection |
| 7 | Document Generator — DOCX generation, PDF conversion, versioning |
| 8 | SPJ Package — checklist, evidence, ZIP export |
| 9 | Dashboard — dashboard, laporan, timeline |
| 10 | QA — test, security, performance, UX polish |

Setiap fase mengikuti siklus §51 master prompt: jelaskan → migration →
model → service → policy → controller/Livewire → UI → test → jalankan test
→ perbaiki → review → lanjut. Fase berikutnya tidak dimulai sebelum fase
sebelumnya stabil (test hijau, `composer ci` lulus).

## 10. Assumptions (dicatat, bukan pertanyaan ke user)

- Locale aplikasi: Indonesian (`id`) sebagai default, fallback `en`.
- Currency: IDR, disimpan sebagai `decimal(15,2)`, ditampilkan `Rp
  1.980.610.920`.
- Livewire 4 dipakai (bukan 3) karena itu versi stabil saat ini — lihat
  `PROJECT_DECISIONS.md` §D-002.
- Test suite jalan ke PostgreSQL asli (`master_spj_test`), bukan sqlite.
- AI/e-signature TIDAK diimplementasikan di MVP — hanya extension point
  (interface) disiapkan di fase-fase terkait.

## 11. Risks (awal)

- Document Requirement Engine dan DOCX table-variable rendering adalah
  bagian paling teknis — akan dipecah jadi iterasi kecil di Phase 5–7.
- Modular monolith dapat tergoda menjadi kopling erat antar domain seiring
  bertambahnya fase — perlu `docs/domain-map.md` dijaga sejak Phase 2.
- Reference KAK RSPNDD berisi banyak detail proyek konstruksi/perencanaan
  yang TIDAK semuanya relevan sebagai SPJ requirement — harus dipilah saat
  seeding (lihat §17 master prompt).

## 12. Status Fase

- **Phase 0 — Discovery & Blueprint: SELESAI.**
- **Phase 1 — Foundation: SELESAI.** Auth, layout, User Management, RBAC
  (`spatie/laravel-permission`), Organization CRUD, audit log append-only.
  13 test hijau, `composer ci` lulus. Detail: `PROJECT_HANDOVER.md`.
- **Phase 2 — Project: SELESAI.** ProjectType (master data global),
  Client + Contact (PPK/PPTK/PA-KPA, per-organisasi), Project (status
  siklus + transisi tervalidasi), Contract (1:1, detail legal/finansial).
  26 test hijau, `composer ci` lulus. Detail & keputusan normalisasi:
  `PROJECT_HANDOVER.md`, `PROJECT_DECISIONS.md` D-008 s/d D-010.
- **Phase 3 — Personnel: SELESAI.** PersonnelCategory (master data
  global), Personnel (roster per-organisasi + dokumen via
  `FileStorageService`), PersonnelAssignment (penugasan ke project,
  billing unit bebas OB/OH/OM/LS/dll), Project.project_manager_personnel_id
  (FK opsional mendampingi kolom string lama). 38 test hijau,
  `composer ci` lulus. Detail: `PROJECT_HANDOVER.md`,
  `PROJECT_DECISIONS.md` D-011/D-012.
- **Phase 4 — Cost & Payment: SELESAI.** TaxType (master data global,
  §59), CostCategory + CostItem (perhitungan subtotal/pajak/total
  otomatis, inclusive/exclusive), Payment/Termin (status siklus +
  validasi tidak melebihi nilai kontrak kecuali override eksplisit —
  §57 master prompt). 53 test hijau, `composer ci` lulus. Detail:
  `PROJECT_HANDOVER.md`, `PROJECT_DECISIONS.md` D-013/D-014.
- **Phase 5 — Document Requirement: SELESAI.** Document Requirement
  Engine berbasis rule field/operator/value tertutup (§17-18 master
  prompt) — DocumentRequirement (master data per Project Type atau
  universal), RequirementRule (kondisi AND), RequirementRuleEvaluator,
  ProjectChecklistItem (status per project, idempotent). 66 test hijau,
  `composer ci` lulus. Detail: `PROJECT_HANDOVER.md`,
  `PROJECT_DECISIONS.md` D-015.
- **Phase 6 — Document Template: SELESAI.** TemplateVariable (master
  data global untuk validasi placeholder), DocumentTemplate (versioning
  per DocumentRequirement, satu versi Active pada satu waktu),
  DocxPlaceholderScanner (scan `{{variable}}` di DOCX pakai ZipArchive
  bawaan, tanpa dependency baru). 79 test hijau, `composer ci` lulus.
  Detail: `PROJECT_HANDOVER.md`, `PROJECT_DECISIONS.md` D-016/D-017.
- **Phase 7 — Document Generator: SELESAI.** VariableResolver (kosakata
  tertutup Project/Client/Contract/Personnel/Payment/Organization),
  DocumentGeneratorService memakai `phpoffice/phpword`
  `TemplateProcessor` (delimiter `{{ }}`, `cloneRowAndSetValues` untuk
  tabel personel berulang §21) + `LibreOfficePdfConverter` (LibreOffice
  headless wajib terpasang, dijalankan lewat `Illuminate\Support\Facades\Process`
  bawaan framework), tabel `documents` terpisah dari checklist dengan
  `data_snapshot` (§61-62) dan versi per requirement, otomatis
  meng-update `ProjectChecklistItem` jadi Fulfilled. Tab "Dokumen" pada
  halaman Project. Diverifikasi end-to-end sungguhan (generate DOCX+PDF
  nyata via browser terhadap `ReferenceProjectSeeder`, bukan cuma unit
  test). 86 test hijau, `composer ci` lulus. Detail:
  `PROJECT_HANDOVER.md`, `PROJECT_DECISIONS.md` D-018.
- **Phase 8 — SPJ Package: SELESAI.** Domain `Evidence` baru (upload
  bukti pendukung manual, smart-linking FK nullable eksplisit ke
  payment/personnel/document_requirement, pola sama PersonnelDocument).
  Domain `Spj`: `SpjPackage` (cakupan ganda — per termin via
  `payment_id` nullable, atau level project/"SPJ Akhir"; lifecycle
  Draft->Finalized tanpa jalan balik) + `SpjItem` (manifest, menunjuk
  satu Document ATAU satu Evidence) + `SpjExportService` (ZIP berisi
  PDF/file asli tiap item + `manifest.txt`, `ZipArchive` bawaan, tanpa
  dependency baru). Kelengkapan checklist per paket dihitung DINAMIS
  terhadap `ChecklistService::applicableRequirements()` yang sudah ada
  sejak Phase 5 — bukan struktur baru. Tab "Bukti Pendukung" & "Paket
  SPJ" pada halaman Project. Bug nyata ditemukan lewat phpstan level 8
  (bukan test gagal): "Evidence" kata tak-berhitung, Eloquent
  meng-resolve tabelnya jadi "evidence" (singular) bukan "evidences" —
  sama persis dengan bug Personnel (D-012). Diverifikasi end-to-end
  sungguhan via browser (generate dokumen + upload evidence + buat
  paket + tambah manifest + export ZIP asli, isi ZIP diperiksa manual).
  98 test hijau, `composer ci` lulus. Detail: `PROJECT_HANDOVER.md`,
  `PROJECT_DECISIONS.md` D-019.
