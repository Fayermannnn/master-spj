# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Roadmap 10-fase awal SELESAI (Phase 1-10).** Fase tambahan di luar
roadmap: **Notification — SELESAI**, **Audit Log viewer & self-service
Profile — SELESAI**, **Backlog QA Phase 10 — SELESAI** (D-024), **5
fitur baru (Payment Allocation, Contract Addendum, Deliverable wiring,
SPJ Review Workflow, Document Numbering) — SELESAI** (D-025 s/d D-029).
Repo di-push ke GitHub (`https://github.com/Fayermannnn/master-spj`,
Public — dikonfirmasi user).

## Completed Features

### Phase 1-10 (ringkas — detail di git log / PROJECT_DECISIONS.md D-001 s/d D-021)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project, Contract, PersonnelCategory, Personnel, PersonnelAssignment,
TaxType, CostCategory, CostItem, Payment, DocumentRequirement Engine,
TemplateVariable + DocumentTemplate, DocumentGenerator (DOCX+PDF via
phpword+LibreOffice), Evidence + SpjPackage/SpjItem, Workplan +
Reporting, Dashboard, Phase 10 QA (security/performance/test/UX
hardening menyeluruh).

### Notification (D-022)
`NotificationService` menghitung alert LIVE (sertifikat personel akan/
sudah kadaluarsa, milestone terlambat, termin terlambat, checklist
belum lengkap) dari data yang sudah ada — TIDAK ADA tabel isi
notifikasi/scheduler. Bell global di setiap halaman, cache 5 menit per
user, dismiss dipersist lewat `NotificationDismissal`. **Pelajaran
penting**: JANGAN PERNAH `Cache::remember()` sebuah Collection/objek/
enum langsung — selalu array/scalar murni (bug nyata ditemukan &
diperbaiki, lihat D-022).

### Audit Log viewer & self-service Profile (baru — D-023)

- **`/audit-logs`** (`app/Livewire/AuditLogs/Index.php`) — permission
  baru `audit_logs.viewAny` (super_admin: semua; admin_perusahaan:
  hanya aksi anggota organisasinya sendiri via `user_id`, karena
  `audit_logs` tidak punya kolom organisasi sendiri dan subjek yang
  diaudit bisa berupa master data global). Filter modul/pengguna/
  tanggal, detail before/after expand-inline per baris. `AuditLogPolicy`
  hanya punya `viewAny()` — append-only, tidak ada update/delete untuk
  digerbangi.
- **`/profile`** (`app/Livewire/Profile/Edit.php`) — self-service ubah
  nama/email + password MILIK SENDIRI. TIDAK ADA `authorize()` sama
  sekali (selalu beroperasi ke `Auth::user()`, tidak pernah menerima ID
  dari luar — tidak ada celah IDOR). Reuse penuh `UserService::update()`
  dari Phase 1. Password pakai rule `current_password` bawaan Laravel.
  Link dari header (nama user yang bisa diklik).
- **Bug UX nyata ditemukan & diperbaiki** (lewat verifikasi browser):
  `session()->flash('status')` TIDAK PERNAH muncul di halaman Profil —
  flash banner global ada di `layouts/app.blade.php`, DI LUAR boundary
  render Livewire komponen `Profile\Edit`, dan halaman ini sengaja
  tidak redirect setelah submit (beda dari `Users\Form` yang redirect
  ke index). Diperbaiki dengan DUA property Livewire biasa
  (`$profileStatus`/`$passwordStatus`, terpisah supaya tidak saling
  menimpa) yang dirender langsung di blade komponen. **Pelajaran untuk
  komponen top-level lain yang tidak redirect setelah submit**: pesan
  sukses harus jadi property komponen, BUKAN session flash.
- Diverifikasi end-to-end sungguhan di browser — termasuk melihat aksi
  ubah-password-sendiri muncul benar di Audit Log dengan
  password/remember_token TETAP tidak muncul di JSON before/after
  (mengonfirmasi ulang redaksi D-021 masih berfungsi).
- 11 test baru (141 total) — `tests/Feature/AuditLogs/AuditLogViewingTest.php`
  (5: super_admin lihat semua, admin_perusahaan discope ke organisasi
  sendiri, filter modul, toggle detail, tolak role tanpa permission) dan
  `tests/Feature/Profile/ProfileManagementTest.php` (6: update nama/
  email, tolak email duplikat, ubah password sukses, tolak password
  saat ini salah, tolak password baru terlalu pendek, semua role bisa
  akses halaman sendiri).

### Backlog QA Phase 10 (D-024)

Dua item backlog dari D-021 poin 9 dikerjakan:

- **Transisi status enum**: riset menemukan hanya `SpjPackageStatus`
  yang punya aturan transisi nyata di service; `ChecklistStatus`/
  `TemplateStatus`/`WorkplanStatus` TIDAK punya aturan transisi sama
  sekali dan TIDAK ADA invarian bisnis alami untuk dijadikan dasar
  aturan baru (dicek satu-satu, lihat D-024) — diputuskan bersama user
  untuk TIDAK mengarang FSM tanpa requirement (RULE 9), dibiarkan
  sebagai keterbatasan sengaja. Test transisi tidak-valid yang
  DITAMBAHKAN hanya untuk `SpjPackageStatus` (finalize ganda,
  addDocument/removeItem pasca-final).
- **File hilang dari disk pasca soft-delete**: `Reports` tidak relevan
  (tidak ada file persisten). Ditemukan & diperbaiki 2 bug nyata:
  `GeneratedDocuments` download crash 500 mentah saat file fisik hilang
  tapi row masih ada; `SpjPackages` export ZIP/manifest tidak sinkron
  (manifest mengklaim file yang sebenarnya tidak ada di ZIP). Perbaikan:
  `FileStorageService::download()` baru (exists-check + 404 terkontrol)
  dipakai di SEMUA 4 controller download file (GeneratedDocument,
  DocumentTemplate, Evidence, PersonnelDocument — 2 yang terakhir
  ditemukan punya bug identik di luar cakupan awal, diperbaiki sekaligus
  atas persetujuan user); `SpjExportService` skip item dari ZIP+manifest
  sekaligus kalau file sumbernya hilang.
- 10 test baru (151 total).

### 5 fitur baru (D-025 s/d D-029)

User meminta "buatkan fitur baru minimal 5, tentukan sendiri, elaborasi"
— kelima fitur DIPILIH berdasarkan gap nyata di blueprint/kode (bukan
ide acak), dielaborasi ke user sebelum dikerjakan, satu commit per
fitur, `composer ci` bersih di tiap langkah, 4 dari 5 diverifikasi
sungguhan di browser.

1. **Payment Item Allocation + Realisasi Anggaran (D-025)** — `payment_items`
   (baru, menghubungkan `Payment`↔`CostItem`, entitas yang sudah disebut
   blueprint §8 sejak Phase 0 tapi tidak pernah dibuat). Tab baru
   "Realisasi Anggaran" di project menampilkan anggaran vs realisasi per
   kategori biaya. Realisasi >100% SENGAJA tidak diblokir (sinyal, bukan
   pelanggaran).
2. **Riwayat Amandemen Kontrak (D-026)** — `ContractAddendum` mencatat
   snapshot nilai lama→baru + alasan. `Contract.contract_value` TIDAK
   BISA lagi diedit bebas lewat form utama setelah kontrak ada — HANYA
   lewat adendum (dicek server-side, bukan cuma `disabled` HTML). Hanya
   adendum TERAKHIR (urutan ULID) yang boleh dihapus.
3. **Wiring Deliverable ke Document Generator (D-027)** — `documents.deliverable_id`
   (opsional). `deliverable.name` bisa auto-fill dari `Deliverable` asli
   saat generate dokumen, TETAP bisa diedit manual (tidak dipaksakan).
4. **Alur Review/Approval Paket SPJ (D-028)** — `SpjPackageStatus`
   bertambah `Submitted` (Draft→Submitted→Finalized, atau
   Submitted→Draft kalau ditolak). Permission BARU `spj_packages.review`
   TERPISAH dari `projects.update` — HANYA admin_perusahaan/super_admin,
   BUKAN project_admin — supaya ada pemisahan peran nyata antara
   penyusun dan penyetuju paket. `finalize()` diganti total oleh
   `submit()`/`approve()`/`reject()` (`reject()` wajib alasan).
5. **Penomoran Dokumen per Organisasi (D-029)** — `NumberingSetting`
   (satu per organisasi, bukan per jenis dokumen). Placeholder BARU
   `document.number`, RESERVED (tidak pernah jadi input bebas di form
   generate, beda dari `payment.*`/`deliverable.*`) — hanya diisi
   `DocumentGeneratorService` sendiri lewat `NumberingService::nextNumber()`
   (transaksional, `lockForUpdate()`). Halaman pengaturan self-service
   di `/settings/document-numbering` (pola sama `Profile\Edit`).

Ini FINALLY mengisi domain `Workflow` (D-028) dan `Settings` (D-029)
yang sejak Phase 0 cuma README — lihat "Known Issues" untuk apa yang
masih TIDAK dibangun di dua domain ini (jangan asumsikan sudah lengkap).

Total: 41 test baru (151 → 192), `composer ci` bersih di setiap commit.
Diverifikasi sungguhan di browser: Fitur 1 (alokasi + laporan realisasi
143% over-realization), Fitur 2 (adendum mengubah nilai kontrak di
layar), Fitur 5 (live preview format nomor + simpan). Fitur 3 TIDAK
diverifikasi ulang di browser (pola UI identik dengan Fitur 1 yang
sudah terbukti). Fitur 4 hanya SEBAGIAN — tombol/badge baru ter-render
benar tapi transisi status tidak bisa diklik via automasi karena
`wire:confirm` (native browser dialog) memblokir klik scripted —
keterbatasan tooling, bukan kode (9 test Pest mencakup penuh logikanya).

## Repository

`https://github.com/Fayermannnn/master-spj` (**Public**, dikonfirmasi
user). Branch `main` + tag `phase0-complete` s/d `phase10-complete` +
`notification-feature-complete`. Remote `origin` sudah dikonfigurasi —
push berikutnya tinggal `git push` / `git push --tags`.

## Known Issues / Deferred

Backlog QA Phase 10 SELESAI (lihat D-024). Sisa keterbatasan yang
sengaja tidak ditutup:
- `ChecklistStatus`/`TemplateStatus`/`WorkplanStatus` tidak punya
  validasi transisi status — bisa diubah bebas ke status apapun. Bukan
  bug (tidak ada invarian bisnis yang ditemukan untuk dijadikan dasar
  aturan), tapi kalau requirement konkret muncul nanti (mis. approval
  sebelum unmark checklist), itu perlu dirancang sebagai fitur baru.

Tambahan:
- Audit Log admin_perusahaan TIDAK melihat aksi yang dilakukan
  super_admin terhadap data organisasinya (mis. kalau super_admin
  pernah mengedit sesuatu di organisasi mereka) — scoping murni lewat
  organisasi PELAKU, bukan organisasi SUBJEK (lihat alasan di D-023).
  Ini keterbatasan yang disengaja, bukan bug.
- Tidak ada halaman "riwayat login" terpisah dari Audit Log umum.

Dari 5 fitur baru sesi ini (D-025 s/d D-029):
- **`Workflow`**: HANYA berisi review paket SPJ (D-028). TIDAK ADA
  workflow generik lain (mis. approval untuk aksi domain lain) — kalau
  dibutuhkan, itu perluasan terpisah dengan requirement konkretnya
  sendiri, bukan generalisasi dari yang sudah ada.
- **`Settings`**: HANYA berisi Penomoran Dokumen (D-029). Belum ada
  pengaturan organisasi lain di sini (mis. preferensi notifikasi,
  branding dokumen) — sama, tunggu kebutuhan konkret.
- Realisasi anggaran (D-025) dihitung HANYA dari alokasi manual
  (`payment_items`) — kalau tim lupa mengalokasikan pembayaran ke item
  biaya, angka realisasi akan under-report tanpa peringatan. Tidak ada
  validasi "termin ini sudah Paid tapi belum ada alokasi sama sekali".
- SPJ review (D-028) tidak mencegah admin_perusahaan tunggal me-review
  paket yang dia susun sendiri (disengaja, lihat D-028 poin 5) — kalau
  organisasi butuh pemisahan peran yang lebih ketat, itu kebutuhan
  operasional (rekrut/tunjuk reviewer terpisah), bukan sesuatu yang
  bisa dipaksa sistem.
- Penomoran dokumen (D-029) satu skema PER ORGANISASI, bukan per jenis
  dokumen — kalau organisasi butuh format berbeda untuk SPJ vs surat
  lain, itu perluasan model data terpisah.

## Environment

Dependency baru: tidak ada. Migrasi baru sesi ini SUDAH dijalankan di
DB dev (`master_spj`) — `php artisan migrate` tetap perlu dijalankan
manual di lingkungan lain yang belum migrate. `RolePermissionSeeder`
juga PERLU dijalankan ulang (`php artisan db:seed --class=RolePermissionSeeder`,
idempotent — aman diulang) di lingkungan manapun yang datanya sudah ada
dari SEBELUM D-028, supaya permission baru `spj_packages.review`
ter-assign ke role admin_perusahaan/super_admin. Tailwind: **aturan
Phase 10 tetap berlaku** — selalu `npm run build` setelah mengubah
class Tailwind di Blade manapun sebelum verifikasi browser
(`.claude/launch.json` tidak menjalankan Vite dev server yang watch).

## Next Task

Tidak ada fase terjadwal berikutnya. Backlog QA Phase 10 SELESAI (D-024),
5 fitur baru SELESAI (D-025 s/d D-029). Kemungkinan arah:
1. Fitur baru lain sesuai kebutuhan konkret yang muncul — lihat "Known
   Issues" untuk batas cakupan yang SENGAJA belum dibangun di kelima
   fitur baru (jangan diasumsikan sudah lengkap).
2. Aturan transisi status baru untuk ChecklistStatus/TemplateStatus/
   WorkplanStatus — HANYA kalau ada requirement bisnis konkret (lihat
   D-024), jangan diasumsikan sendiri.
3. Verifikasi browser lanjutan untuk Fitur 4 (SPJ review) — transisi
   status belum sempat diklik-verifikasi karena `wire:confirm`
   memblokir automasi; kalau sesi mendatang punya cara menangani native
   dialog di tooling verifikasi, ini bisa dituntaskan.
4. Permintaan fitur baru dari user.
5. Production/deployment readiness — keputusan arsitektur besar baru
   kalau diminta (RULE 10).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 192
test, `composer ci` lulus bersih.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-029). Baru sesi ini:
- **D-024**: backlog QA Phase 10 — hanya SpjPackageStatus punya aturan
  transisi nyata, 3 enum lain sengaja dibiarkan tanpa validasi; 2 bug
  file-hilang-dari-disk diperbaiki (`FileStorageService::download()`
  dipakai di 4 controller download).
- **D-025**: `payment_items` (entitas blueprint §8 yang belum pernah
  dibuat) + `BudgetRealizationService`, realisasi >100% sengaja tidak
  diblokir.
- **D-026**: `ContractAddendum` — nilai kontrak HANYA berubah lewat
  adendum (dicek server-side), hanya adendum terakhir (urutan ULID)
  boleh dihapus.
- **D-027**: `documents.deliverable_id` opsional, `deliverable.name`
  auto-fill TAPI tetap bisa diedit manual.
- **D-028**: `SpjPackageStatus` + `Submitted`, permission
  `spj_packages.review` TERPISAH dari `projects.update` (segregasi
  peran nyata: project_admin tidak punya, admin_perusahaan/super_admin
  punya), `finalize()` diganti `submit()`/`approve()`/`reject()`.
- **D-029**: `NumberingSetting` per organisasi, `document.number`
  placeholder RESERVED (tidak pernah input bebas), halaman self-service
  reuse `OrganizationPolicy::update`.

## Security Notes

D-021/D-022/D-024 tetap berlaku. Tambahan dari 5 fitur baru:
- **D-026**: `Contract.contract_value` sekarang dijaga di SERVER (bukan
  cuma `disabled` HTML) — submit langsung ke form utama saat kontrak
  sudah ada akan diabaikan diam-diam (di-`unset()` sebelum masuk
  service), bukan celah baru — justru menutup celah lama (nilai bisa
  diubah bebas tanpa jejak).
- **D-028**: permission `spj_packages.review` adalah kontrol akses
  BARU — diverifikasi test bahwa `project_admin` (tidak punya
  permission ini) mendapat 403 saat mencoba `approve`/`reject`,
  walaupun mereka punya `projects.update` dan bisa mengelola manifest
  paket yang sama.
- **D-029**: halaman pengaturan penomoran SELALU beroperasi ke
  `Auth::user()->organization` sendiri — tidak menerima organization ID
  dari route/request manapun, tidak ada celah IDOR (pola sama D-023
  Profile). `NumberingService::nextNumber()` transaksional
  (`lockForUpdate()`) — dua request generate dokumen bersamaan tidak
  bisa mendapat nomor urut yang sama (race condition ditutup di level
  DB lock, bukan cuma asumsi).
