# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Roadmap 10-fase awal SELESAI (Phase 1-10).** Fase tambahan di luar
roadmap: **Notification — SELESAI**, **Audit Log viewer & self-service
Profile — SELESAI**, **Backlog QA Phase 10 — SELESAI** (lihat D-024).
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
- Domain `Settings` dan `Workflow` masih belum diimplementasikan —
  tidak ada kebutuhan konkret yang mendorongnya.

## Environment

Dependency baru: tidak ada. Tidak ada perubahan Tailwind class baru di
fitur ini yang butuh `npm run build` ulang di luar yang sudah dilakukan
sesi ini — tapi **aturan Phase 10 tetap berlaku**: selalu `npm run
build` setelah mengubah class Tailwind di Blade manapun sebelum
verifikasi browser (`.claude/launch.json` tidak menjalankan Vite dev
server yang watch).

## Next Task

Tidak ada fase terjadwal berikutnya. Backlog QA Phase 10 (D-021 poin 9)
SELESAI. Kemungkinan arah (lihat juga opsi yang sempat diajukan tapi
belum dipilih user: SPJ submission tracking/`deliverable.name` wiring
ke Deliverable model — lihat riwayat chat/D-022 konteks):
1. Fitur baru lain sesuai kebutuhan konkret yang muncul.
2. Aturan transisi status baru untuk ChecklistStatus/TemplateStatus/
   WorkplanStatus — HANYA kalau ada requirement bisnis konkret (lihat
   D-024), jangan diasumsikan sendiri.
3. Permintaan fitur baru dari user.
4. Production/deployment readiness — keputusan arsitektur besar baru
   kalau diminta (RULE 10).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 151
test, `composer ci` lulus bersih.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-024). Baru: D-024 (backlog QA
Phase 10 — hanya SpjPackageStatus punya aturan transisi nyata, 3 enum
lain sengaja dibiarkan tanpa validasi karena tidak ada invarian bisnis;
2 bug file-hilang-dari-disk ditemukan & diperbaiki: crash 500 di
GeneratedDocuments download, ZIP/manifest tidak sinkron di SpjPackages
export; `FileStorageService::download()` baru dipakai di 4 controller
download).

## Security Notes

D-021/D-022 tetap berlaku, ditambah D-024: perbaikan file-hilang adalah
hardening (mencegah stack trace Flysystem mentah bocor ke response 500
saat DEBUG aktif), bukan celah otorisasi baru — `Gate::authorize()`
tetap dipanggil LEBIH DULU di semua controller download sebelum
`FileStorageService::download()` dipanggil, urutan ini tidak berubah
dan tetap diverifikasi test (kasus cross-organization tetap
`assertForbidden()`, bukan `assertNotFound()`).
