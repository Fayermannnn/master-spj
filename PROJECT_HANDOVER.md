# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 7 — Document Generator: SELESAI.** Siap lanjut ke fase berikutnya
sesuai roadmap (`PROJECT_BLUEPRINT.md` §9/§12) — kemungkinan Evidence/SPJ
Package atau Notification, cek tabel fase untuk urutan pastinya.

## Completed Features

### Phase 1-6 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment,
DocumentRequirement + RequirementRule + RequirementRuleEvaluator +
ProjectChecklistItem (Document Requirement Engine), TemplateVariable +
DocumentTemplate + DocxPlaceholderScanner (Document Template versioning
& deteksi placeholder).

### Phase 7 (baru) — Document Generator

- **VariableResolver** (`app/Domain/DocumentGenerator/Services`) —
  kosakata TERTUTUP (pola sama dengan `RequirementRuleEvaluator`, D-015)
  yang menerjemahkan `template_variables.key` jadi nilai nyata dari
  Project/Contract/Client/Contact(PPK)/Organization(Provider)/Payment.
  `resolveScalar()` untuk nilai tunggal (format Rupiah/tanggal Indonesia
  otomatis), `resolveTableRows()` untuk grup tabel berulang (baru
  `personnel` yang didukung — nama/posisi/NPWP per
  `PersonnelAssignment`). Variable baru yang ditambah admin lewat UI
  TemplateVariable tetap BISA diisi manual di form generate walau belum
  ada auto-resolve untuknya — lihat D-018 poin 5.
- **DocumentGeneratorService** — orkestrasi penuh: validasi template
  Active, isi `phpoffice/phpword` `TemplateProcessor` (delimiter
  `{{ }}`, table variable lewat `cloneRowAndSetValues()`/`deleteRow()`),
  simpan DOCX, WAJIB konversi PDF (`PdfConverterInterface` ->
  `LibreOfficePdfConverter`, LibreOffice headless), baru setelah SEMUA
  berhasil simpan baris `Document` + snapshot data (§61-62) + update
  `ProjectChecklistItem` jadi Fulfilled. Atomik — kalau PDF gagal
  konversi, tidak ada file/baris DB yang tertinggal.
- **`documents` table** — terpisah dari `project_checklist_items`
  (rekomendasi Phase 6 dikonfirmasi user), versi bertambah per
  (project, requirement), `payment_id` nullable untuk konteks termin
  (variable `payment.*`), `document_template_id` (`restrictOnDelete` —
  histori generate tidak boleh kehilangan jejak versi template yang
  dipakai).
- **Tab "Dokumen"** pada halaman Project (`GeneratedDocuments\Manager`,
  nested seperti tab Termin/Checklist) — form generate per
  DocumentRequirement (hanya muncul kalau ada versi template Active),
  prefill otomatis semua placeholder yang bisa di-auto-resolve, field
  Termin/Pembayaran muncul otomatis kalau template mengandung variable
  `payment.*` (live re-resolve saat termin dipilih), riwayat versi
  dokumen dengan link unduh DOCX/PDF terpisah + hapus.
- **Download controller** — `DownloadGeneratedDocumentController`
  (`/generated-documents/{document}/download/{type}`), pola sama
  dengan `DownloadDocumentTemplateController`/`DownloadPersonnelDocumentController`
  (`Gate::authorize('view', $document->project)`, bukan permission
  baru).
- **Tidak ada permission baru** — generate/hapus dokumen digerbangi
  `ProjectPolicy::update`, sama seperti Payment/CostItem/Checklist
  (D-014), karena `Document` adalah sub-resource project.
- **Bug Livewire ditemukan & diperbaiki SEBELUM masuk test** (lewat
  tinjauan manual, D-018 poin 7): `wire:model` mengartikan setiap titik
  pada path sebagai array bersarang — placeholder seperti
  `project.name` mengandung titik LITERAL sebagai bagian nama key
  array, jadi tidak bisa dipakai langsung sebagai path Livewire.
  Diperbaiki dengan menyimpan `$variableInputs` sebagai list terindeks
  angka, bukan dikunci nama placeholder — lihat `PROJECT_DECISIONS.md`
  D-018 untuk detail lengkap, **pola ini bisa terulang di fitur lain
  yang mem-bind array dengan key mengandung titik.**
- Diverifikasi END-TO-END SUNGGUHAN: dev server dijalankan, login
  sebagai `admin@ciptarencana.example`, generate dokumen "Kontrak"
  sungguhan untuk project referensi (`ReferenceProjectSeeder`) — hasil
  DOCX & PDF diverifikasi berisi SEMUA nilai yang benar (nilai kontrak
  Rp 1.980.610.920 sesuai KAK, PPK, provider, dua baris tabel personel
  dengan nama/posisi benar, konteks termin ter-resolve otomatis saat
  dipilih), file diunduh lewat browser dan dicek header PDF/DOCX asli.
  Checklist item "Kontrak" otomatis berubah dari "Belum Lengkap" ke
  "Lengkap" tanpa aksi manual.
- 7 test baru (86 total) — `tests/Feature/GeneratedDocuments/DocumentGenerationTest.php`:
  isi placeholder skalar+tabel dan tandai checklist Fulfilled, versi
  bertambah per generate berikutnya, tolak generate dari template
  non-Active, hapus file DOCX+PDF+baris bersama, alur Livewire generate
  penuh, tolak akses lintas organisasi, resolve konteks Payment. Test
  memakai `FakePdfConverter` (bind interface) — TIDAK menjalankan
  LibreOffice sungguhan di test suite (proses eksternal, lambat/rapuh
  untuk dijalankan tiap test run) — hanya diverifikasi manual sungguhan
  di browser (lihat poin di atas). Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- **Grup tabel baru selain `personnel`** belum didukung —
  `VariableResolver::tableGroups()`/`resolveTableRows()` hanya punya
  satu cabang (`personnel`). Menambah grup tabel baru (mis. daftar
  deliverable, daftar biaya) = menambah satu entri di `tableGroups()` +
  satu cabang `match` di `resolveTableRows()`, konsisten dengan pola
  D-015/D-018.
- **Auto-resolve variable baru butuh kode**, hanya kenyamanan (bukan
  blocker fungsional) — lihat D-018 poin 5. Variable yang ditambah
  admin lewat UI TemplateVariable tanpa cabang `match` di
  `VariableResolver::resolveScalar()` tetap bisa diisi MANUAL di form
  generate (kotak input selalu muncul untuk setiap placeholder
  terdeteksi), hanya tidak ter-prefill otomatis.
- **Regenerasi tidak menandai versi lama sebagai "usang"** — versi
  DOCUMENT lama (bukan template) tetap bisa diunduh setelah versi baru
  dibuat (riwayat lengkap, sesuai prinsip "banyak dokumen per
  requirement" dari rekomendasi Phase 6), tidak ada status
  aktif/nonaktif seperti pada DocumentTemplate. Kalau di masa depan
  perlu "versi resmi yang berlaku" per requirement (mirip
  TemplateStatus::Active), itu perluasan tersendiri.
- **Belum ada halaman preview isi dokumen sebelum download** — user
  harus unduh DOCX/PDF dulu untuk melihat hasilnya. Preview inline
  (mis. render PDF di browser) bisa jadi peningkatan UX di fase
  mendatang, bukan kebutuhan MVP.

## Database Changes (Phase 7)

- `documents`: ulid PK, project_id (FK cascade), document_requirement_id
  (FK cascade), document_template_id (FK restrictOnDelete), payment_id
  (FK nullable, nullOnDelete), version (unsigned int), name,
  data_snapshot (json), disk/path/original_filename/mime_type/size
  (DOCX), pdf_disk/pdf_path/pdf_original_filename/pdf_size (nullable —
  secara desain SELALU diisi bersama karena generate atomik, kolom
  nullable murni untuk keluwesan skema), generated_by (FK users
  nullable), generated_at, notes, soft delete. Unique
  `[project_id, document_requirement_id, version]`.

## Environment

- **LibreOffice WAJIB terpasang** untuk generate dokumen (konversi
  PDF) — `brew install --cask libreoffice` di macOS. Binary
  dikonfigurasi lewat `LIBREOFFICE_BINARY` di `.env` (default
  `soffice`, resolve via PATH — Homebrew cask menaruh command wrapper
  di `/opt/homebrew/bin/soffice` secara otomatis setelah instalasi,
  tidak perlu path absolut manual).
- Composer dependency baru: `phpoffice/phpword` (^1.4). Tidak ada
  dependency baru untuk PDF — `Illuminate\Support\Facades\Process`
  bawaan Laravel framework.

## Next Task

Cek `PROJECT_BLUEPRINT.md` §9/§12 untuk urutan fase berikutnya (mis.
Evidence/SPJ Package yang menggabungkan banyak `Document` jadi satu
paket SPJ, atau Notification). Belum ada keputusan arsitektur besar yang
tertunda dari Phase 7 — 3 keputusan yang ditunda dari Phase 6 (library
DOCX, strategi PDF, struktur `documents`) semua sudah diputuskan &
diimplementasikan (lihat D-018).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 86
test, `composer ci` lulus bersih. LibreOffice PDF conversion diverifikasi
NYATA (bukan mock) lewat smoke test manual + browser end-to-end, bukan
lewat automated test suite (lihat alasan di atas).

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-018). Baru di Phase 7: D-018
(library DOCX phpword+TemplateProcessor, LibreOffice wajib untuk PDF,
`documents` terpisah dengan payment_id opsional, VariableResolver
kosakata tertutup dengan fallback manual-input, tidak ada permission
baru, bug binding Livewire dengan key array berisi titik).

## Security Notes

Tidak berubah dari Phase 1-6. Dokumen hasil generate disimpan di disk
`local` (private) sama seperti template/dokumen personel — download
hanya lewat route berpolicy (`Gate::authorize('view', $document->project)`).
