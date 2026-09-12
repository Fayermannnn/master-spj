# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 9 — Dashboard: SELESAI.** Ini adalah fase TERAKHIR dari 10 fase
roadmap awal (`PROJECT_BLUEPRINT.md` §9) sebelum **Phase 10 — QA** (test,
security, performance, UX polish) — fase penutup, bukan fase fitur baru.

## Completed Features

### Phase 1-8 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment,
DocumentRequirement + RequirementRule + RequirementRuleEvaluator +
ProjectChecklistItem, TemplateVariable + DocumentTemplate +
DocxPlaceholderScanner, VariableResolver + DocumentGeneratorService
(generate DOCX+PDF via phpword + LibreOffice), Evidence (upload bukti
manual) + SpjPackage/SpjItem (manifest + export ZIP).

### Phase 9 (baru) — Dashboard

- **Domain `Workplan` baru** (`app/Domain/Workplan`) — `Milestone`
  (titik pemeriksaan timeline project) dan `Deliverable` (output/
  keluaran, mis. "DED Blok Plan" sesuai istilah §3 blueprint), boleh
  berdiri sendiri atau terkait satu Milestone (`milestone_id`
  nullable). Status dipakai BERSAMA lewat `WorkplanStatus` (Pending/
  InProgress/Completed) — "Terlambat" dihitung dinamis
  (`Milestone::isOverdue()`/`Deliverable::isOverdue()`), bukan status
  tersimpan.
- **Tab "Timeline"** pada halaman Project (`app/Livewire/Workplan/Manager.php`)
  — kelola Milestone & Deliverable (CRUD + ubah status inline), plus
  visual "Gantt-lite": marker milestone diposisikan sebagai persentase
  dalam rentang `project.start_date`/`end_date`
  (`Manager::timelinePosition()`), warna beda untuk Completed/
  Terlambat/Pending.
- **Domain `Reporting` baru** (`app/Domain/Reporting`) — domain KE-20
  yang tidak ada di daftar 19 domain awal (D-005), dibuat karena logika
  agregasi lintas-project tidak cocok masuk `ProjectManagement`.
  `ProjectSummaryReportService` sebagai SATU sumber kebenaran filter+
  angka (dipakai baik tampilan layar maupun ekspor, supaya tidak ada
  angka beda antara yang dilihat vs diunduh).
- **`ProjectSummaryExcelExporter`** — `phpoffice/phpspreadsheet`
  LANGSUNG (bukan wrapper laravel-excel), dependency composer baru
  (sekeluarga dengan `phpoffice/phpword` yang sudah ada).
- **`ProjectSummaryPdfExporter`** — REUSE PENUH `PdfConverterInterface`/
  `LibreOfficePdfConverter` dari Phase 7 (bangun tabel laporan sebagai
  DOCX via `phpword` `addTable()`, konversi PDF lewat pipeline
  LibreOffice yang sama). TIDAK ADA dependency PDF baru.
- **Halaman "Laporan Ringkasan Project"** (`/reports/project-summary`,
  `app/Livewire/Reports/ProjectSummary.php`) — tabel lintas-project
  (kode, nama, klien, status, nilai kontrak, total dibayar, kelengkapan
  checklist) dengan filter (cari/status/klien) dan tombol unduh Excel
  & PDF. Digerbangi permission `projects.viewAny` yang SUDAH ADA (tidak
  ada permission baru), scoping organisasi memakai pola SAMA seperti
  `Projects\Index` sejak Phase 2.
- **Dashboard** (`app/Livewire/Dashboard.php`) — placeholder "coming
  soon" DIGANTI widget agregat nyata: total project, project aktif,
  total nilai kontrak, breakdown per status, dan "Milestone Terdekat"
  (5 milestone belum Completed terdekat lintas project, org-scoped).
- Diverifikasi END-TO-END SUNGGUHAN via browser: dashboard menampilkan
  angka nyata (nilai kontrak Rp 1.980.610.920 sesuai KAK), tab Timeline
  menampilkan marker Gantt-lite dengan posisi persentase BENAR
  (diperiksa lewat inspeksi DOM: 12.7%/53.6%/clamp 100%), halaman
  Laporan menampilkan data yang sama, unduh Excel (dibuka ulang lewat
  PhpSpreadsheet, isi sel diverifikasi) dan PDF (header `%PDF-1.7` asli
  dari LibreOffice, 42KB) keduanya sukses.
- 12 test baru (110 total) — `tests/Feature/Workplan/WorkplanManagementTest.php`
  (buat milestone + hitung posisi timeline, tandai selesai + stempel
  tanggal aktual, deteksi terlambat, buat deliverable terkait milestone,
  hapus milestone, tolak akses lintas organisasi) dan
  `tests/Feature/Reports/ProjectSummaryReportTest.php` (scoping
  organisasi, hitung nilai kontrak/total dibayar/kelengkapan checklist,
  ekspor XLSX sungguhan dibaca ulang PhpSpreadsheet, ekspor PDF
  sungguhan via fake converter, render halaman + filter, tolak akses
  tanpa permission). Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- **Hanya SATU laporan** ("Laporan Ringkasan Project") — bukan report
  builder generik. Kalau di masa depan perlu laporan lain (mis. laporan
  personel/utilisasi, laporan keuangan detail per termin), tambahkan
  service+exporter baru di domain `Reporting` mengikuti pola yang sama
  (satu `*ReportService` sebagai sumber kebenaran + exporter per
  format), BUKAN generalisasi jadi query builder dinamis.
- **Timeline "Gantt-lite" hanya menampilkan Milestone**, bukan
  Deliverable — Deliverable ditampilkan sebagai tabel biasa di bawahnya
  (dengan tanggal & status), tidak ikut jadi marker di garis waktu
  visual. Ini pembatasan scope yang disengaja (RULE 67) — kalau
  dibutuhkan, tambahkan marker kedua dengan bentuk berbeda (mis. kotak
  vs lingkaran) di `timelinePosition()`/blade yang sama.
- **Tidak ada "requirement per termin" atau "milestone wajib per
  requirement"** — Milestone/Deliverable BUKAN terhubung ke
  DocumentRequirement (beda dari Evidence yang punya
  `document_requirement_id`). Kalau di masa depan perlu "milestone ini
  = requirement itu harus terpenuhi", itu perluasan tersendiri.
- **`deliverable.name` template variable (Phase 7) MASIH manual-input**
  — TIDAK dihubungkan ke `Deliverable` model baru ini. Menghubungkannya
  (mis. dropdown pilih Deliverable saat generate dokumen, mirip
  `payment_id` context) adalah peningkatan yang masuk akal tapi TIDAK
  diminta di fase ini — dicatat sebagai pintu terbuka, bukan kekurangan
  yang harus segera ditutup.

## Database Changes (Phase 9)

- `milestones`: ulid PK, project_id (FK cascade), name, description
  nullable, target_date (NOT NULL), actual_date nullable, status
  (pending/in_progress/completed), sort_order, created_by nullable,
  notes nullable, soft delete.
- `deliverables`: ulid PK, project_id (FK cascade), milestone_id
  (nullable FK, nullOnDelete), name, description nullable, target_date
  nullable, completed_date nullable, status, sort_order, created_by
  nullable, notes nullable, soft delete.

## Environment

- Composer dependency baru: `phpoffice/phpspreadsheet` (^5.9). Tidak
  ada dependency baru untuk PDF laporan (reuse LibreOffice dari
  Phase 7 — tetap wajib terpasang).

## Next Task

**Phase 10 — QA**: test, security, performance, UX polish (lihat
`PROJECT_BLUEPRINT.md` §9). Ini fase PENUTUP dari 10 fase roadmap awal —
kemungkinan besar TIDAK butuh domain/fitur baru, fokus pada: audit
keamanan (`security-review` skill tersedia), performance check (N+1
query — beberapa tempat sudah sengaja memanggil `ChecklistService::sync()`
berulang, cek dampak nyatanya), UX polish (mis. sidebar nav yang makin
panjang setelah 9 fase, form validasi, responsif mobile), dan cakupan
test tambahan untuk celah yang mungkin terlewat. Sebelum mulai, cek
`README.md`/`PROJECT_BLUEPRINT.md` §9-10 untuk konfirmasi ini benar
fase terakhir sebelum dianggap "MVP selesai".

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 110
test, `composer ci` lulus bersih.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-020). Baru di Phase 9: D-020
(domain Workplan baru untuk milestone/deliverable dengan visual
Gantt-lite, domain Reporting baru untuk laporan lintas-project, Excel
via phpspreadsheet langsung, PDF reuse infrastruktur LibreOffice
Phase 7, tidak ada permission baru, scoping organisasi pola sama
Projects\Index).

## Security Notes

Tidak berubah dari Phase 1-8. Laporan (Excel/PDF) dibuat sinkron ke
file temp dan langsung dihapus setelah dikirim
(`deleteFileAfterSend()`), tidak pernah disimpan permanen di disk
`local`. Akses laporan & dashboard mengikuti scoping organisasi yang
sama dengan halaman Project (super_admin melihat semua, role lain
hanya organisasinya sendiri).
