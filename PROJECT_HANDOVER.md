# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 6 — Document Template: SELESAI.** Siap lanjut ke **Phase 7 —
Document Generator** (mengisi template dari data project, generate
DOCX/PDF sungguhan, versioning dokumen hasil generate).

## Completed Features

### Phase 1-5 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment,
DocumentRequirement + RequirementRule + RequirementRuleEvaluator +
ProjectChecklistItem (Document Requirement Engine).

### Phase 6 (baru) — Document Template

- **TemplateVariable** — master data GLOBAL (pola sama seperti
  ProjectType/CostCategory), berisi key (mis. `project.name`), label,
  data_type (text/number/currency/date/boolean/array/table — §20 master
  prompt). Hanya super_admin (`template_variables.manage`) yang
  mengelola. Seed baseline: 19 variable dari §20 (project.*, client.*,
  ppk.name, provider.*, personnel.*, payment.*, deliverable.name,
  today).
- **DocumentTemplate** — satu baris = satu VERSI template untuk satu
  `DocumentRequirement` (BUKAN untuk ProjectType langsung — lihat
  `PROJECT_DECISIONS.md` D-016). Versi bertambah otomatis per
  requirement (`version` unique per document_requirement_id). Status
  siklus Draft → Active → Archived; `DocumentTemplateService::activate()`
  otomatis meng-arsipkan versi lain yang sebelumnya Active pada
  requirement yang sama (hanya satu versi "berlaku" per requirement).
  Delete diblokir untuk versi yang sedang Active.
- **DocxPlaceholderScanner** — memindai placeholder `{{variable}}` di
  dalam file .docx (arsip ZIP) memakai `ZipArchive` BAWAAN PHP — TIDAK
  ada dependency composer baru ditambahkan. Placeholder yang terpecah
  jadi beberapa run XML oleh Word (lazim terjadi) ditangani dengan
  strip seluruh tag XML dulu sebelum regex.
- Halaman "Kelola Template" per DocumentRequirement (`/document-
  requirements/{id}/templates`) — upload versi baru, lihat "Variabel
  Terdeteksi" dengan badge hijau (dikenal) / kuning ⚠ (tidak dikenal di
  `template_variables`), aktifkan/arsipkan/hapus/unduh per versi.
  Sengaja BUKAN halaman terpisah bergaya "Documents" Phase 3 — mengikuti
  pola gerbang akses `document_requirements.manage` yang sama dengan
  DocumentRequirement sendiri (template = sub-resource-nya).
- Seed data: `TemplateVariableSeeder` (19 variable baseline).
- **Bug nyata ditemukan & diperbaiki (lewat test yang gagal, bukan
  review manual):** menulis placeholder literal `{{...}}` langsung di
  dalam tag echo Blade (`{{ '{{'.$key.'}}' }}`) merusak hasil kompilasi
  PHP — Blade compiler mencari `}}` PERTAMA secara tekstual, yang
  ternyata ada DI DALAM string literal `'}}'`, bukan di akhir tag.
  Diperbaiki dengan membungkus nilai literal itu di blok `@php` dulu
  sebagai variabel biasa, baru di-echo. Lihat `PROJECT_DECISIONS.md`
  D-017 — **pola ini kemungkinan muncul lagi di Phase 7** (Document
  Generator kemungkinan besar perlu menampilkan contoh placeholder
  serupa di UI), jangan ulangi pola yang sama.
- 13 test baru (79 total): unit test `DocxPlaceholderScanner` (5 test —
  single-run, split-run, dedup, kosong, file bukan ZIP valid, memakai
  file .docx SUNGGUHAN yang dibuat lewat ZipArchive di dalam test, bukan
  file fake tanpa isi), feature test TemplateVariable CRUD, feature test
  DocumentTemplate (upload + deteksi placeholder, version increment,
  activate mengarsipkan versi lain, delete-guard versi Active, otorisasi
  non-super-admin). Semua hijau (`composer ci`). Diverifikasi juga di
  browser (halaman Kelola Template & Variabel Template render benar
  setelah perbaikan bug Blade).

## Known Issues / Deferred (sengaja, bukan bug)

- **Upload file lewat UI belum diuji lewat browser otomatis** — file
  picker native OS tidak bisa didorong oleh tooling browser-automation
  yang tersedia di sesi ini. Alur upload SUDAH diverifikasi penuh lewat
  Pest (file .docx sungguhan, bukan mock) dan halaman-nya diverifikasi
  render dengan benar; hanya interaksi "klik → pilih file dari OS" yang
  belum dicoba manual. Kalau ragu, minta user mencoba upload sekali
  secara manual sebelum deploy.
- Library untuk MENGISI template (bukan sekadar deteksi placeholder)
  BELUM dipilih — sengaja ditunda ke Phase 7 supaya keputusan dibuat
  dengan konteks penuh (table variable berulang §21, format currency/
  date, dst). Jangan asumsikan `phpoffice/phpword` otomatis dipakai;
  evaluasi ulang di awal Phase 7.
- Template belum terhubung ke Payment/CostItem/Personnel secara nyata
  untuk pengisian data — itu memang pekerjaan Phase 7 (Document
  Generator), bukan Phase 6.
- `document_templates` tidak divalidasi bahwa SEMUA placeholder yang
  terdeteksi punya padanan di `template_variables` (hanya warning visual
  di UI, tidak memblokir upload) — sesuai §64 master prompt eksplisit
  ("Jika variable tidak valid: warning"), BUKAN celah yang perlu
  ditutup.

## Database Changes (Phase 6)

- `template_variables`: ulid PK, key (unique), label, data_type,
  description, is_active, soft delete. Global.
- `document_templates`: ulid PK, document_requirement_id (FK cascade),
  name, version (unsigned int), status, disk, path, original_filename,
  mime_type, size, detected_variables (json), description, uploaded_by
  (FK users nullable), soft delete. Unique (document_requirement_id,
  version).

## Environment

Tidak berubah dari Phase 1-5.

## Next Task

**Phase 7 — Document Generator**: Mengisi DocumentTemplate dengan data
Project sungguhan (project/client/ppk/personnel/payment via
`template_variables` yang sudah divalidasi) dan menghasilkan file
DOCX/PDF nyata, disimpan sebagai `documents` dengan versioning &
**data snapshot** (§61-62 master prompt — kalau data project berubah
SETELAH dokumen final dibuat, dokumen lama TIDAK BOLEH berubah diam-diam;
simpan snapshot data yang dipakai saat generate).

Sebelum mulai, putuskan (baru, bukan dari fase sebelumnya):
1. **Library pengisian DOCX** — evaluasi `phpoffice/phpword`
   (`TemplateProcessor` — perhatikan default delimiter `${...}` bukan
   `{{...}}`, perlu `setMacroOpeningChars`/`setMacroClosingChars` atau
   custom regex replace) vs alternatif lain. Table variable (§21 —
   `{{#personnel}}...{{/personnel}}` atau pendekatan clone-row) adalah
   kebutuhan paling menentukan pemilihan ini — uji dengan itu dulu
   sebelum memutuskan.
2. **PDF conversion** — §22 master prompt: "Generate DOCX → Convert PDF
   → Save both" — perlu converter (LibreOffice headless via `shell_exec`/
   proses terpisah, atau layanan lain). Ini keputusan infrastruktur
   (butuh binary terinstal di server) — cek dulu ketersediaan LibreOffice
   di lingkungan Herd lokal user sebelum berkomitmen ke pendekatan ini,
   atau siapkan fallback "DOCX saja dulu, PDF menyusul" kalau LibreOffice
   tidak tersedia.
3. **Struktur tabel `documents`** — per project, per document_requirement,
   per document_template (versi mana yang dipakai), field data snapshot
   (JSON), path file hasil generate, generated_by, generated_at. Perlu
   diputuskan apakah `documents` ini SAMA dengan konsep "checklist
   fulfillment" dari Phase 5 (`project_checklist_items`) atau terpisah
   dengan relasi. Rekomendasi: `documents` terpisah (karena satu
   requirement checklist bisa punya banyak dokumen — versi revisi),
   tapi `ProjectChecklistItem.status` otomatis di-update jadi Fulfilled
   saat `documents` baru dibuat untuk requirement itu — WIRE INI, jangan
   biarkan checklist tetap manual selamanya (lihat catatan Phase 5).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 79
test, 167 assertion, 0 error phpstan, 0 pint diff.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-017). Baru di Phase 6: D-016
(Document Template diikat ke DocumentRequirement, scan placeholder tanpa
dependency baru, keputusan library generate ditunda ke Phase 7),
D-017 (bug Blade literal `{{`/`}}` dalam tag echo — jangan ulangi
polanya).

## Security Notes

Tidak berubah dari Phase 1-5. Template disimpan di disk `local` (private)
sama seperti dokumen personel — download hanya lewat route berpolicy.
