# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 8 — SPJ Package: SELESAI.** Siap lanjut ke **Phase 9 — Dashboard**
(dashboard, laporan, timeline) sesuai roadmap (`PROJECT_BLUEPRINT.md` §9).

## Completed Features

### Phase 1-7 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment,
DocumentRequirement + RequirementRule + RequirementRuleEvaluator +
ProjectChecklistItem, TemplateVariable + DocumentTemplate +
DocxPlaceholderScanner, VariableResolver + DocumentGeneratorService
(generate DOCX+PDF sungguhan lewat phpword + LibreOffice, tabel
`documents` dengan data snapshot).

### Phase 8 (baru) — SPJ Package

- **Evidence** (`app/Domain/Evidence`) — domain BARU untuk bukti
  pendukung yang diunggah MANUAL (foto, nota, scan tanda tangan), beda
  dari `Document` (Phase 7) yang selalu hasil GENERATE. "Smart linking"
  opsional ke Payment/Personnel/DocumentRequirement lewat FK NULLABLE
  eksplisit (bukan polymorphic — aplikasi ini tidak pernah pakai
  polymorphic relation). Upload/hapus lewat `EvidenceService` (pola
  identik `PersonnelDocumentService`), UI di tab "Bukti Pendukung" pada
  halaman Project (`app/Livewire/Evidence/Manager.php`), download lewat
  `DownloadEvidenceController` (`Gate::authorize('view', $evidence->project)`).
- **SpjPackage** (`app/Domain/Spj`) — satu baris = satu paket SPJ untuk
  Project. `payment_id` NULLABLE menentukan cakupan: diisi = per
  termin, null = level project ("SPJ Akhir") — KEDUANYA didukung
  (dikonfirmasi user lewat AskUserQuestion di awal fase). Lifecycle
  `SpjPackageStatus`: Draft -> Finalized, TIDAK ADA jalan balik —
  Finalized mengunci manifest, revisi = buat paket baru (tidak ada
  versioning seperti DocumentTemplate/Document).
- **SpjItem** — manifest pivot, menunjuk PERSIS SATU dari `document_id`/
  `evidence_id` (divalidasi di service, bukan DB constraint). Duplikasi
  dicegah otomatis.
- **`SpjPackageService::coverage()`** — kelengkapan checklist per paket
  dihitung DINAMIS (bukan status tersimpan) dengan membandingkan isi
  manifest terhadap `ChecklistService::applicableRequirements()` yang
  sudah ada sejak Phase 5. Satu logika seragam untuk paket per-termin
  maupun level-project.
- **`SpjExportService`** — menyusun ZIP (`ZipArchive` bawaan PHP, tanpa
  dependency baru) berisi PDF tiap Document (fallback DOCX kalau tanpa
  PDF) + file asli tiap Evidence + `manifest.txt` (metadata paket +
  daftar isi + requirement yang dipenuhi tiap item). Sinkron (bukan
  queued job — jumlah item per paket kecil).
- **Tab "Bukti Pendukung" & "Paket SPJ"** pada halaman Project — list
  paket, buat paket baru (nama + cakupan opsional), kelola manifest
  (tambah Document/Evidence yang belum termasuk, keluarkan item),
  progress bar kelengkapan, tombol Finalisasi & Ekspor ZIP.
- **Tidak ada permission baru** — Evidence & SpjPackage digerbangi
  `ProjectPolicy::update`/`view`, sama seperti Payment/CostItem/
  Checklist/Document (D-014/D-018).
- **Bug nyata ditemukan lewat phpstan level 8 SEBELUM ditulis ke test**
  (bukan test gagal): `Evidence` kata tak-berhitung dalam Bahasa
  Inggris — Eloquent meng-resolve nama tabelnya jadi `evidence`
  (singular), BUKAN `evidences` (nama tabel migrasi) — TANPA fix ini,
  setiap query/insert lewat model ini gagal total di runtime. Nyaris
  identik dengan bug Personnel (D-012). Diperbaiki dengan
  `protected $table = 'evidences';` eksplisit. **Pelajaran untuk model
  baru ke depan: cek `(new Model())->getTable()` di tinker untuk setiap
  nama model yang berpotensi kata tak-berhitung** (evidence,
  information, equipment, series, dst.) SEBELUM menulis query pertama.
- Diverifikasi END-TO-END SUNGGUHAN lewat browser: generate dokumen
  Kontrak (Phase 7) + upload Evidence sungguhan + buat SpjPackage "SPJ
  Akhir" (level project) + tambah Document dan Evidence ke manifest +
  cek coverage checklist (1 dari 12 setelah 1 item, benar) + ekspor ZIP
  sungguhan dan diperiksa isinya (`01 - Kontrak.pdf`,
  `02 - Foto Serah Terima Laporan.jpg`, `manifest.txt` dengan konten
  yang benar).
- 12 test baru (98 total) — `tests/Feature/Evidence/EvidenceManagementTest.php`
  (upload dengan smart-link, hapus+file terhapus, tolak lintas
  organisasi, tolak download lintas organisasi) dan
  `tests/Feature/SpjPackages/SpjPackageManagementTest.php` (buat paket
  per-termin & level-project, tambah Document+Evidence & hitung
  coverage, tolak duplikasi item, blokir edit manifest setelah
  Finalized, tolak finalisasi paket kosong, hapus item, ekspor ZIP
  berisi PDF+manifest, tolak akses lintas organisasi). Semua hijau
  (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- **Tidak ada "requirement per termin" terpisah** — kelengkapan paket
  per-termin dibandingkan terhadap SELURUH checklist project (lewat
  `ChecklistService::applicableRequirements()`), bukan sub-set khusus
  untuk termin itu. `Payment.required_items` (teks bebas sejak Phase 4)
  tetap jadi cara user mencatat kebutuhan spesifik per termin secara
  manual — kalau di masa depan perlu struktur formal ("requirement X
  wajib ada di termin Y"), itu perluasan tersendiri, BUKAN yang diminta
  di fase ini.
- **Tidak ada versioning SpjPackage** — beda dari DocumentTemplate/
  Document yang punya `version` bertambah otomatis. Revisi paket =
  buat paket baru manual. Kalau kebutuhan revisi-berkelanjutan muncul
  nyata di masa depan, pertimbangkan pola yang sama.
- **`wire:confirm` (dialog konfirmasi browser) tidak bisa diuji lewat
  tooling browser-automation di sesi ini** — percobaan klik tombol
  "Finalisasi" (yang pakai `wire:confirm`) lewat automated browser
  malah menavigasi ke halaman lain tanpa efek (state paket tetap
  Draft, tidak ada error). Logika finalize() SUDAH diverifikasi benar
  lewat Pest (`it('blocks manifest changes once a package is
  finalized')` dan `it('rejects finalizing an empty package')`), hanya
  interaksi UI dialog konfirmasi yang belum dicoba manual — sama
  seperti keterbatasan file-picker native OS di Phase 6. Kalau ragu,
  minta user mencoba klik Finalisasi sekali secara manual.
- **Reference project (RSPNDD) tidak diberi SpjPackage/Evidence
  permanen di seeder** — data demo yang dipakai untuk verifikasi
  browser dibuat lewat script sementara lalu database di-reset
  (`migrate:fresh --seed`) supaya tetap bersih untuk sesi berikutnya.
  Kalau perlu data SPJ contoh yang persisten untuk demo, tambahkan ke
  `ReferenceProjectSeeder` secara sengaja di fase mendatang.

## Database Changes (Phase 8)

- `evidences`: ulid PK, project_id (FK cascade), payment_id/personnel_id/
  document_requirement_id (semua nullable FK, nullOnDelete), name,
  category nullable, description nullable, disk/path/original_filename/
  mime_type/size, uploaded_by nullable, notes nullable, soft delete.
  **PENTING**: model punya `protected $table = 'evidences';` eksplisit
  — jangan dihapus (lihat D-019 poin 8).
- `spj_packages`: ulid PK, project_id (FK cascade), payment_id (nullable
  FK, nullOnDelete — null = level project), name, status (draft/
  finalized), notes nullable, created_by nullable, finalized_at
  nullable, soft delete.
- `spj_items`: ulid PK, spj_package_id (FK cascade), document_id
  (nullable FK, cascadeOnDelete), evidence_id (nullable FK,
  cascadeOnDelete), sort_order, TANPA soft delete (pivot murni, hapus
  = hard delete).

## Environment

Tidak berubah dari Phase 7 — LibreOffice tetap wajib terpasang untuk
generate Document (dipakai lewat `SpjExportService` secara tidak
langsung, karena ZIP membundel PDF hasil generate Phase 7). Tidak ada
dependency composer baru di Phase 8.

## Next Task

**Phase 9 — Dashboard**: dashboard, laporan, timeline (lihat
`PROJECT_BLUEPRINT.md` §9). Belum ada keputusan arsitektur besar yang
tertunda dari Phase 8.

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 98
test, `composer ci` lulus bersih.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-019). Baru di Phase 8: D-019
(SpjPackage cakupan ganda per-termin/project-level, isi Document+Evidence
sekaligus, Evidence smart-linking via FK nullable eksplisit bukan
polymorphic, kelengkapan dihitung dinamis, lifecycle Draft->Finalized
tanpa versioning, export ZIP sinkron, tidak ada permission baru, bug
tabel Evidence mirip D-012).

## Security Notes

Tidak berubah dari Phase 1-7. Evidence disimpan di disk `local`
(private) sama seperti Document/DocumentTemplate/PersonnelDocument —
download hanya lewat route berpolicy. Export ZIP paket SPJ juga
digerbangi `Gate::authorize('view', $spjPackage->project)`.
