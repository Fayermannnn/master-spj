# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Roadmap 10-fase awal SELESAI (Phase 1-10).** Fase tambahan pertama di
luar roadmap: **Notification — SELESAI.** Repo di-push ke GitHub
(`https://github.com/Fayermannnn/master-spj`, Public — dikonfirmasi user).

## Completed Features

### Phase 1-10 (ringkas — detail di git log / PROJECT_DECISIONS.md D-001 s/d D-021)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment,
DocumentRequirement + RequirementRule + RequirementRuleEvaluator +
ProjectChecklistItem, TemplateVariable + DocumentTemplate +
DocxPlaceholderScanner, VariableResolver + DocumentGeneratorService,
Evidence + SpjPackage/SpjItem, Workplan (Milestone/Deliverable) +
Reporting (Laporan Ringkasan Project), Dashboard, dan Phase 10 QA
(security/performance/test/UX hardening menyeluruh — lihat D-021).

### Notification (baru, di luar roadmap awal) — D-022

- **`app/Domain/Notification/Services/NotificationService.php`** —
  menghitung alert LIVE (bukan menyimpan isi notifikasi) dari 4 sumber:
  - Sertifikat Personnel akan kadaluarsa (≤30 hari) atau sudah lewat
    (`certificate_expiry_date`), di-scope per organisasi personel
    langsung (tidak bergantung status project).
  - Milestone terlambat (`target_date` lewat, status bukan Completed),
    hanya untuk project berstatus Preparation/Active/PaymentProcessing.
  - Payment/termin terlambat (`target_date` lewat, status bukan
    Paid/Rejected), scope project sama seperti milestone.
  - Checklist belum lengkap — SATU alert PER PROJECT (agregat jumlah
    item Missing, bukan satu per item), hanya project Active/
    PaymentProcessing. **Sengaja TIDAK memanggil `ChecklistService::sync()`**
    di jalur ini (method ini jalan di SETIAP request lewat bell
    global — memanggil sync() di sana akan mengulang masalah performa
    D-021 tapi jauh lebih parah, di semua halaman bukan cuma Laporan).
    Konsekuensi: project yang checklist-nya belum pernah dibuka sama
    sekali tidak akan muncul sampai seseorang membuka tab Checklist-nya
    minimal sekali.
- **`NotificationDismissal`** (model+migration) — satu-satunya yang
  dipersist: penanda "sudah ditutup" per user per `dismissal_key`
  string (mis. `"milestone:{id}"`), BUKAN foreign key relasional ke
  4 jenis sumber berbeda.
- **Bell notifikasi global** (`app/Livewire/Notifications/Bell.php`,
  dirender di `layouts/app.blade.php` pada SETIAP halaman) — badge
  jumlah alert, dropdown Alpine.js daftar alert dengan link ke halaman
  terkait dan tombol dismiss per item.
- **Cache 5 menit per user** (`Cache::remember`, driver `database`) —
  supaya bell yang jalan di setiap halaman tidak N+1 di setiap request.
  `dismiss()` memanggil `Cache::forget()` supaya penutupan terasa
  instan.
- **Bug nyata ditemukan lewat reload browser (BUKAN test otomatis)**:
  percobaan pertama meng-cache objek `Collection` PHP yang isinya
  instance enum `NotificationType` LANGSUNG — reload KEDUA (setelah
  cache tersimpan) menghasilkan 500
  `"tried to call a method on an incomplete object... Collection...
  was loaded before unserialize()"`. Cache driver `database`
  men-serialize lewat `serialize()` PHP native — objek di dalamnya
  rapuh terhadap pergeseran bentuk class antar iterasi kode. Diperbaiki:
  cache ARRAY MENTAH (`->all()`) dengan `type` sebagai string `->value`,
  bukan Collection+enum. **PELAJARAN PENTING untuk fitur mendatang**:
  JANGAN PERNAH `Cache::remember()` sebuah Collection/objek/enum secara
  langsung — selalu ubah ke array/scalar murni dulu, berlaku untuk
  SEMUA cache driver (bukan cuma `database`), karena akar masalahnya
  adalah fragilitas `serialize()`/`unserialize()` PHP native terhadap
  perubahan bentuk class, bukan sesuatu yang spesifik ke satu driver.
- Tidak ada permission baru — scoping organisasi dilakukan di dalam
  `NotificationService` sendiri (pola sama Dashboard/Reports).
- 7 test baru (130 total) — `tests/Feature/Notifications/NotificationServiceTest.php`:
  4 jenis alert masing-masing, agregasi checklist per project, scoping
  organisasi (super_admin vs organisasi sendiri), dismiss langsung
  menyembunyikan alert meski masih dalam window cache, dismiss lewat
  komponen Bell. Diverifikasi juga end-to-end di browser (termasuk
  reload berkali-kali setelah perbaikan bug cache, untuk memastikan
  tidak berulang).

## Repository

Di-push ke GitHub: `https://github.com/Fayermannnn/master-spj`
(**Public** — dikonfirmasi eksplisit oleh user, bukan default yang
disarankan). Branch `main` + semua tag `phase0-complete` s/d
`phase10-complete`. Remote `origin` sudah dikonfigurasi di repo lokal;
push berikutnya tinggal `git push` / `git push --tags` seperti biasa.

## Known Issues / Deferred

Lihat bagian yang sama di riwayat git `PROJECT_HANDOVER.md` sebelum
fase Notification (Phase 10 QA) untuk backlog QA yang belum dikerjakan
(test transisi status enum lain, test file hilang pasca soft-delete,
dst — semuanya masih berlaku, belum dikerjakan di fase Notification
ini).

Tambahan dari fase Notification:
- Alert checklist tidak akan muncul untuk project yang checklist-nya
  belum pernah disinkronkan sama sekali (lihat penjelasan di atas) —
  pembatasan yang disengaja demi performa, bukan bug.
- Belum ada channel notifikasi selain in-app (tidak ada email/push) —
  sesuai deskripsi domain asli ("Notifikasi in-app"), bukan kekurangan.
- Domain `Settings` dan `Workflow` (dari 19 domain awal) masih belum
  diimplementasikan — tidak ada kebutuhan konkret yang mendorongnya
  sejauh ini (lihat alasan pemilihan Notification di D-022).

## Environment

Dependency baru: tidak ada (Notification tidak menambah composer
package). **Catatan yang masih berlaku dari Phase 10**: jalankan
`npm run build` setelah mengubah class Tailwind di Blade manapun —
`.claude/launch.json` tidak menjalankan Vite dev server yang watch.

## Next Task

Tidak ada fase terjadwal berikutnya. Kemungkinan arah:
1. Fitur baru lain dari 19 domain yang belum terisi (`Settings`,
   `Workflow`) — HANYA kalau ada kebutuhan konkret, jangan
   diimplementasikan tanpa alasan jelas (lihat RULE 9 CLAUDE.md).
2. Backlog QA Phase 10 (lihat Known Issues di atas).
3. Permintaan fitur baru dari user.
4. Production/deployment readiness — belum pernah dibahas, kalau
   diminta ini keputusan arsitektur besar baru (RULE 10).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 130
test, `composer ci` lulus bersih.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-022). Baru: D-022 (domain
Notification baru, alert dihitung live tanpa tabel/scheduler, cache
5 menit untuk bell global, bug cache Collection+enum ditemukan &
diperbaiki — pelajaran berlaku untuk semua fitur mendatang yang
memakai `Cache::remember()`).

## Security Notes

Tidak berubah dari Phase 10. Notification tidak menambah permukaan
serangan baru — bell hanya membaca data yang sudah di-scope organisasi
di service-nya sendiri, `NotificationDismissal` hanya berisi string
key + user_id (tidak ada data sensitif).
