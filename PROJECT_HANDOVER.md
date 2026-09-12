# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 3 — Personnel: SELESAI.** Siap lanjut ke **Phase 4 — Cost &
Payment** (cost category/item, budget, payment/termin).

## Completed Features

### Phase 1 & 2 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract.

### Phase 3 (baru)

- **PersonnelCategory** — master data GLOBAL (pola sama seperti
  ProjectType, D-009/D-011), hanya super_admin (`personnel_categories.manage`)
  yang mengelola. Seed baseline: Tenaga Ahli, Tenaga Pendukung, Surveyor,
  Operator, Administrasi.
- **Personnel** — roster tenaga ahli/pendukung per-organisasi. Field:
  kategori, posisi, pendidikan, keahlian, KTP, NPWP, no. SKA/SKK + masa
  berlaku, telepon, email, tarif default. CRUD Livewire + halaman detail
  bertab (Overview/Dokumen).
- **PersonnelDocument** — upload dokumen (KTP/NPWP/CV/Ijazah/SKA-SKK/
  Surat Pernyataan/Surat Penugasan/Lainnya) lewat
  `App\Domain\Shared\Services\FileStorageService` (disk `local`/private,
  validasi MIME `pdf,jpg,jpeg,png,doc,docx` + maks 5MB di validation
  rules). Download HANYA lewat route berpolicy
  (`DownloadPersonnelDocumentController`), bukan link statis — diuji
  eksplisit bahwa user organisasi lain mendapat 403.
- **PersonnelAssignment** — penugasan Personnel ke Project. Satuan
  billing (`unit`) sengaja berupa string bebas (OB/OH/OM/LS/Hari/Jam/HM/
  custom — datalist sebagai saran, bukan pilihan tertutup), bukan enum
  atau tabel master, sesuai §14 master prompt. `subtotal` dihitung &
  disimpan (snapshot) oleh `PersonnelAssignmentService`, bukan dihitung
  on-the-fly di view. Constraint unik (`project_id`,`personnel_id`) —
  satu baris penugasan per orang per project. Dikelola lewat komponen
  nested `ProjectPersonnel\Manager` di tab "Personel" pada halaman detail
  Project (pola sama dengan Contract di Phase 2 — tidak ada policy
  terpisah, gerbang akses lewat `ProjectPolicy::update`).
- **Project.project_manager_personnel_id** — FK opsional ke Personnel,
  MENDAMPINGI (bukan menggantikan) `project_manager_name` yang lama.
  Form Project sekarang punya dropdown Personnel + field teks fallback;
  halaman detail menampilkan nama dari Personnel kalau FK terisi.
- **FileStorageService** (`App\Domain\Shared\Services`) — wrapper tipis
  generik di atas Laravel Filesystem, dipakai personnel_documents
  sekarang, disiapkan untuk Evidence/DocumentGenerator di fase mendatang
  tanpa perlu berubah.
- **Bug nyata ditemukan & diperbaiki:** Eloquent otomatis menebak nama
  tabel `Personnel` sebagai `personnels` (salah — "personnel" sudah kata
  plural/tak-berhitung). Ketahuan dari migrasi FK yang gagal DAN dari
  Larastan yang melaporkan seluruh properti model sebagai "undefined"
  (karena skema yang diintrospeksi salah). Diperbaiki dengan
  `protected $table = 'personnel';` eksplisit — lihat
  `PROJECT_DECISIONS.md` D-012. **Kalau menambah model baru dengan nama
  yang berpotensi tidak beraturan secara pluralisasi (data non-hitung,
  singkatan, dll), cek `(new Model)->getTable()` lebih dulu.**
- Seed data: `PersonnelCategorySeeder` (5 kategori baseline) dan
  `ReferenceProjectSeeder` diperluas dengan 2 personel contoh (Ketua Tim,
  Tenaga Ahli Teknik Arsitektur) + penugasan ke project reference RSPNDD.
- 38 test Pest (26 Phase 1-2 + 12 Phase 3 baru): PersonnelCategory CRUD +
  delete-guard, Personnel CRUD + tamper test + delete-guard + upload/
  download/delete dokumen + cross-org download denial, PersonnelAssignment
  create/update dengan subtotal computation + cross-org rejection +
  unique constraint. Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- Tax handling Contract masih sederhana (dari Phase 2) — ditunda ke
  Phase 4, lihat catatan di situ.
- Kontrak addendum belum didukung (dari Phase 2).
- **Personnel belum bisa "dipakai ulang" lintas organisasi** — setiap
  organisasi membangun roster personel sendiri (by design, D-009: data
  bisnis per-tenant). Kalau nanti ada kebutuhan sub-kontrak personel
  antar-perusahaan konsultan, itu di luar cakupan MVP dan perlu didesain
  ulang, bukan hack cepat.
- `PersonnelAssignment.role_on_project` di-default dari
  `Personnel.position` saat memilih personel di form (JS-less, lewat
  `updatedPersonnelId()` Livewire), tapi TIDAK otomatis sinkron kalau
  `Personnel.position` diubah belakangan — assignment menyimpan snapshot
  peran saat itu, bukan referensi live. Ini disengaja (histori penugasan
  tidak boleh berubah diam-diam kalau data personel di-update — sejalan
  dengan prinsip "Data Snapshot" §62 master prompt), TAPI perlu
  diperhatikan kalau nanti dianggap membingungkan user.
- Halaman Personnel/Show untuk role staff/viewer (read-only) belum diuji
  manual di browser — hanya lewat Pest policy test.

## Database Changes (Phase 3)

- `personnel_categories`: ulid PK, code (unique), name, description,
  is_active, soft delete. Global.
- `personnel` (nama tabel singular sengaja — lihat D-012): ulid PK,
  organization_id/personnel_category_id (FK), name, position, education,
  expertise, id_number, npwp, certificate_number, certificate_expiry_date,
  phone, email, default_rate, is_active, notes, soft delete.
- `personnel_documents`: ulid PK, personnel_id (FK cascade), document_type
  (string/enum), disk, path, original_filename, mime_type, size,
  uploaded_by (FK users nullable), notes, soft delete.
- `personnel_assignments`: ulid PK, project_id/personnel_id (FK),
  role_on_project, quantity, unit (string bebas), unit_price, subtotal,
  start_date, end_date, notes, soft delete. Unique (project_id,
  personnel_id).
- `projects`: tambah kolom `project_manager_personnel_id` (FK personnel,
  nullable, nullOnDelete) — migration terpisah, additive.

## Environment

Tidak berubah dari Phase 1/2.

## Next Task

**Phase 4 — Cost & Payment Management**: CostCategory (master data —
kemungkinan global seperti ProjectType/PersonnelCategory: PERSONNEL/
NON_PERSONNEL/TRAVEL/dll §13 master prompt), CostItem (per project,
terhubung opsional ke PersonnelAssignment untuk item biaya personel),
Payment/Termin (§15: nomor termin, persentase, nominal, tanggal target,
required output/dokumen, status). **Ini juga saat yang tepat membangun
Tax Type/Tax Rate configurable** (§59 master prompt, ditunda dari Phase
2 — lihat `PROJECT_DECISIONS.md` D-008) karena kalkulasi pajak paling
sering dipakai berulang di sini (cost item + payment + contract).

Sebelum mulai, putuskan:
1. Apakah `contracts.tax_amount`/`net_value` (kolom manual dari Phase 2)
   diganti jadi dihitung dari Tax Type/Rate yang baru, atau dibiarkan
   sebagai override manual dengan Tax Type sebagai default/starting
   point? Rekomendasi: Tax Type sebagai default yang bisa di-override
   manual per kontrak (fleksibel, sesuai §59 "tidak mengklaim legal
   compliance otomatis").
2. Termin: apakah `payments` punya `organization_id` sendiri atau cukup
   diturunkan dari `project_id` (project sudah organization-scoped)?
   Rekomendasi: cukup dari project (hindari duplikasi kolom scoping).

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 38
test, 88 assertion, 0 error phpstan, 0 pint diff.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-012). Baru di Phase 3: D-011
(PersonnelCategory global + FK PM mendampingi string lama +
FileStorageService generik), D-012 (nama tabel `personnel`, bukan
`personnels` — cek pluralisasi Eloquent untuk model baru bernama kata
tak-berhitung).

## Security Notes

Tidak berubah dari Phase 1 — kredensial demo (`password`) hanya untuk
lokal/demo. Dokumen personel disimpan di disk `local` (private,
`storage/app/private`) dan hanya bisa diunduh lewat route yang
memverifikasi Policy — jangan pernah expose path file secara langsung
lewat disk `public` untuk data personel/dokumen sensitif di fase
mendatang (Evidence, dsb.).
