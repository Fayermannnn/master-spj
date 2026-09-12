# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 10 — QA: SELESAI.** Ini adalah fase TERAKHIR dari 10 fase roadmap
awal (`PROJECT_BLUEPRINT.md` §9) — **MVP Sistem SPJ Otomatis dianggap
LENGKAP** dari Foundation (Phase 1) sampai QA (Phase 10). Tidak ada fase
fitur baru yang direncanakan setelah ini di roadmap awal; sesi berikutnya
kemungkinan besar akan berupa permintaan fitur BARU di luar roadmap awal,
atau perbaikan/perluasan dari backlog yang tercatat di bawah.

## Completed Features

### Phase 1-9 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment,
DocumentRequirement + RequirementRule + RequirementRuleEvaluator +
ProjectChecklistItem, TemplateVariable + DocumentTemplate +
DocxPlaceholderScanner, VariableResolver + DocumentGeneratorService
(generate DOCX+PDF via phpword + LibreOffice), Evidence + SpjPackage/
SpjItem (manifest + export ZIP), Workplan (Milestone/Deliverable +
timeline Gantt-lite) + Reporting (Laporan Ringkasan Project + unduh
Excel/PDF), Dashboard dengan widget agregat nyata.

### Phase 10 (baru) — QA menyeluruh

**Metodologi**: skill `security-review` bawaan TIDAK bisa dipakai (butuh
`git diff origin/HEAD`, repo ini tidak punya remote). Dilakukan manual
lewat 3 subagent riset paralel (security/performance/test-coverage) +
tinjauan UX langsung oleh sesi ini via browser (viewport mobile 375px).
Semua temuan diverifikasi lewat `composer ci` dan/atau browser sungguhan
sebelum dianggap selesai — bukan cuma dibaca dari laporan agent.

**Perbaikan security (2):**
- Evidence upload TIDAK punya validasi `mimes:` sama sekali (beda dari
  DocumentTemplate/Personnel yang sudah benar) — ditambahkan
  `mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx`.
- Hash password & `remember_token` User BOCOR ke `audit_logs` (append-
  only, bisa dibaca admin manapun) via `UserService::create/update/delete`
  yang mengirim `getAttributes()`/`getChanges()` mentah — ditambahkan
  `UserService::redact()`.

**Perbaikan performance (2 diperbaiki, 1 sengaja TIDAK):**
- N+1 nyata di tab "Dokumen" (`GeneratedDocuments\Manager`, ~30-45
  query tambahan per render) — diperbaiki via cache per-render
  (property PRIVATE, bukan state Livewire).
- `Reports\ProjectSummary` tidak dipaginasi (satu-satunya listing di
  app ini yang begitu) — diperbaiki: tabel dipaginasi 15/halaman,
  kartu total tetap akurat lewat `ProjectSummaryReportService::aggregates()`
  (SQL `sum()` langsung, terpisah dari `toRow()`/`sync()` yang mahal).
  Ekspor Excel/PDF TETAP tidak dipaginasi (sengaja — unduhan harus
  berisi semua baris yang cocok filter).
- `ChecklistService::sync()` mahal (~16 query/call untuk project
  dengan banyak requirement/rule) — **SENGAJA TIDAK diperbaiki**.
  Optimasi memoization SEMPAT dicoba di `RequirementRuleEvaluator`,
  lalu DIBATALKAN karena `tests/Unit/DocumentRequirement/RequirementRuleEvaluatorTest.php`
  membuktikan itu bug korektnes nyata (cache basi kalau data project
  berubah lalu dievaluasi ulang dalam request yang sama — pola sah
  yang test-nya sengaja menguji). Lihat `PROJECT_DECISIONS.md` D-021
  poin 5 untuk arah perbaikan yang BENAR kalau ini terbukti jadi
  bottleneck sungguhan di masa depan (cache di level batch
  `applicableRequirements()`, bukan di evaluator).

**Perbaikan UX (1, tapi berdampak luas):**
- Bug overflow horizontal di SELURUH halaman pada mobile — BUKAN di
  tab nav (dugaan awal salah), tapi classic flexbox `min-width:auto`
  trap: `<main>` adalah `flex-1` child dari container `flex-col` di
  `layouts/app.blade.php`. Judul project yang panjang di header sticky
  memaksa seluruh halaman melebar. Diperbaiki: `min-w-0` di `<main>`
  dan div flex-col pembungkusnya, `truncate`+`min-w-0` di `<h1>` judul.
  **PENTING untuk sesi berikutnya**: `.claude/launch.json` HANYA
  menjalankan `php artisan serve`, TIDAK ADA proses Vite dev yang
  watch — setiap perubahan class Tailwind butuh `npm run build` manual
  sebelum terlihat di browser (perubahan PHP/Blade logic biasa
  langsung ter-refresh, tapi CSS TIDAK).

**Perbaikan & penambahan test (13 test baru, 123 total):**
- Domain `Contract` (sejak Phase 2) TIDAK PUNYA test sama sekali —
  ditulis `tests/Feature/Contracts/ContractManagementTest.php` dari
  nol. Saat menulis test PERTAMA untuk domain ini, ditemukan BUG
  PRODUKSI NYATA: `Contracts\Form::save()` hanya mengonversi
  `tax_type_id` dari string kosong ke `null`, field opsional lain
  (spmk_number/spmk_date/tax_amount/net_value/notes) TIDAK — kalau
  user submit form kontrak dengan field itu kosong, `ContractService::save()`
  crash 500 di level database Postgres. Diperbaiki di `Form::save()`.
- Cross-organization authorization untuk Payment/CostItem/
  PersonnelAssignment (D-014: sengaja tanpa Policy sendiri, digerbangi
  `ProjectPolicy::update`) TIDAK PERNAH diverifikasi lewat aktor
  hostile-org sejak fase-fase itu ditulis — ditambahkan test untuk
  ketiganya, SEMUA lolos (klaim D-014 terbukti benar, tapi sebelumnya
  memang belum dibuktikan).
- `ClientPolicy` — satu-satunya Policy tanpa test jalur "ditolak" —
  ditambahkan.
- Evidence — ditambahkan test tolak tipe file salah, tolak ukuran
  lebih besar dari limit, dan 404 saat mengunduh file yang sudah
  dihapus.
- User — ditambahkan test yang memverifikasi hash password TIDAK
  PERNAH muncul di `audit_logs` (memverifikasi perbaikan security di
  atas).

## Known Issues / Deferred (dicatat sengaja, backlog QA lanjutan — BUKAN untuk dikerjakan otomatis di sesi berikutnya kecuali diminta)

- Enum status lain (`ChecklistStatus`/`SpjPackageStatus`/`TemplateStatus`/
  `WorkplanStatus`) belum diuji jalur transisi tidak-valid (hanya
  `ProjectStatus`/`PaymentStatus` yang sudah, dari fase sebelumnya).
- Beberapa service (`VariableResolver`, `EvidenceService`, dst) hanya
  teruji TIDAK LANGSUNG lewat komponen Livewire-nya — dianggap cukup,
  bukan celah, tapi dicatat untuk transparansi.
- File generate/export (`GeneratedDocuments`/`SpjPackages`/`Reports`)
  belum diuji untuk kasus file sumber hilang dari disk pasca
  soft-delete (edge case jarang terjadi, bukan alur normal).
- `ChecklistService::sync()` tetap O(requirement × rule) per panggilan
  — lihat perbaikan performance di atas untuk arah yang benar kalau
  perlu dioptimasi nanti.

## Environment

Tidak ada dependency baru di Phase 10. **Catatan penting**: jalankan
`npm run build` setelah mengubah class Tailwind di file Blade manapun
sebelum memverifikasi di browser — `.claude/launch.json` tidak
menjalankan Vite dev server yang watch.

## Next Task

**Roadmap 10-fase awal SELESAI.** Tidak ada Phase 11 yang direncanakan.
Sesi berikutnya kemungkinan besar:
1. Permintaan fitur baru dari user yang di luar roadmap awal — perlakukan
   sebagai fase baru, ikuti pola yang sama (baca blueprint/decisions dulu,
   AskUserQuestion untuk ambiguitas arsitektur besar, verifikasi
   end-to-end sungguhan, dokumentasikan keputusan).
2. Mengerjakan backlog "Known Issues/Deferred" di atas kalau diminta.
3. Deployment/production readiness (belum pernah dibahas eksplisit di
   roadmap awal — kalau muncul, ini keputusan arsitektur besar baru,
   ikuti RULE 10).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 123
test, `composer ci` lulus bersih.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-021). Baru di Phase 10: D-021
(audit menyeluruh, 2 perbaikan security, 2 perbaikan + 1 pembatalan
optimasi performance, 1 perbaikan UX berdampak luas + catatan build
Tailwind, 1 bug produksi nyata ditemukan lewat test Contract, cross-org
authorization diverifikasi untuk domain D-014).

## Security Notes

Lihat perbaikan security Phase 10 di atas (mimes Evidence, redaksi
password di audit log). Selebihnya tidak berubah dari Phase 1-9. Audit
security menyeluruh Phase 10 TIDAK menemukan celah kritis (path
traversal/SQL injection/XSS/command injection/IDOR/mass assignment
semua "No issue found").
