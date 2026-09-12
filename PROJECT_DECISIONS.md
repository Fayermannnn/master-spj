# PROJECT DECISIONS — Sistem SPJ Otomatis

Log keputusan arsitektur. Setiap entri: konteks, opsi, keputusan, alasan.
Tambah entri baru di bagian bawah, jangan edit entri lama (append-only,
kecuali memperbaiki typo).

## D-001 — Repo git terpisah dari E-Supervisi Klinis

**Konteks:** Folder `MASTER SPJ` awalnya kosong, berada sebagai subfolder di
dalam repo git proyek lain (`/Users/firmansyah/CLAUDE CODE`, proyek
"E-Supervisi Klinis Pendidikan v2.0") — proyek tak terkait dengan aturan
domain, RBAC, dan state machine yang sama sekali berbeda.

**Opsi:**
1. `git init` baru di dalam `MASTER SPJ`, repo independen.
2. Pindah lokasi kerja ke luar folder `CLAUDE CODE` sepenuhnya.
3. Tetap sebagai subfolder/monorepo di repo E-Supervisi.

**Keputusan:** Opsi 1 — repo git terpisah di dalam `MASTER SPJ`, tetap di
lokasi folder saat ini secara fisik di disk.

**Alasan:** Dikonfirmasi user. Menghindari pencampuran dua produk tak
terkait dan mewarisi `CLAUDE.md` yang salah domain, sambil tidak perlu
memindahkan lokasi folder.

## D-002 — Livewire 4, bukan Livewire 3

**Konteks:** Master prompt awal menyebut stack "Livewire 3". Saat scaffold
dijalankan (September 2026), `composer require livewire/livewire` resolve
ke `^4.4` sebagai versi stable terbaru.

**Opsi:**
1. Pin ke `livewire/livewire:^3.0` sesuai teks master prompt secara literal.
2. Pakai versi stable terbaru yang di-resolve Composer (4.x).

**Keputusan:** Opsi 2 — Livewire 4.

**Alasan:** Referensi versi di master prompt kemungkinan besar hanya
mencerminkan versi yang umum dikenal saat prompt ditulis, bukan pin yang
disengaja. Memaksa versi 3 yang lebih lama berisiko kehilangan dukungan
keamanan/fitur tanpa manfaat nyata. Dicatat di sini sebagai asumsi, dapat
di-downgrade jika user secara eksplisit memerlukan Livewire 3.

## D-003 — Test suite terhadap PostgreSQL asli, bukan SQLite in-memory

**Konteks:** Scaffold default Laravel 13 mengatur test env ke
`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.

**Opsi:**
1. Biarkan default sqlite in-memory (test lebih cepat).
2. Arahkan ke database `master_spj_test` di PostgreSQL lokal yang sama
   dengan production.

**Keputusan:** Opsi 2.

**Alasan:** Sistem ini akan memakai kolom JSON (untuk konfigurasi
fleksibel), enum/constraint Postgres, dan kemungkinan fitur spesifik
Postgres lain. SQLite tidak punya paritas penuh untuk hal-hal itu — bug
bisa lolos di test tapi muncul di production. Trade-off: test sedikit lebih
lambat, dianggap sepadan.

## D-004 — Locale default Indonesian (`id`)

**Konteks:** Seluruh master prompt ditulis dalam Bahasa Indonesia, domain
adalah administrasi pemerintah/konsultan Indonesia (SPJ, PPK, PPTK, NPWP,
SKA/SKK).

**Keputusan:** `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=en`,
`APP_FAKER_LOCALE=id_ID`.

**Alasan:** Reasonable default mengikuti konteks domain; dicatat sebagai
asumsi, bukan pertanyaan ke user (§73 master prompt).

## D-005 — Struktur folder domain: README per domain, bukan file kosong

**Konteks:** 18 folder domain modular monolith dibuat kosong di Phase 0
sebelum ada model/logic apa pun untuk domain sebagian besar dari mereka.

**Keputusan:** Setiap `app/Domain/<Domain>/README.md` berisi deskripsi
singkat scope domain tersebut, supaya folder ter-track git dan
terdokumentasi sejak awal, alih-alih `.gitkeep` kosong.

**Alasan:** Dokumentasi minimal tanpa menunda pembuatan struktur folder;
akan diperluas jadi `docs/MODULES.md` yang lebih lengkap saat domain mulai
diisi kode nyata.

## D-006 — RBAC pakai `spatie/laravel-permission`; tambah domain `Identity`

**Konteks:** Phase 1 butuh Role/Permission yang configurable (master data,
bukan hard-code). Saat implementasi juga ketahuan 18 domain Phase 0 tidak
punya tempat untuk User/Role/Permission/Auth — `Organization` domain khusus
entitas perusahaan/tenant, bukan manajemen user.

**Opsi RBAC:**
1. Bangun RBAC custom dari nol (tabel roles/permissions/pivot manual).
2. Pakai `spatie/laravel-permission` — package matang, tabel & pattern
   sudah persis sesuai kebutuhan (roles, permissions, model_has_roles,
   model_has_permissions, role_has_permissions, Blade directive `@can`,
   kompatibel dengan Laravel Policy).

**Keputusan RBAC:** Opsi 2. Migration morph key (`model_id` di
`model_has_roles`/`model_has_permissions`) diubah dari `unsignedBigInteger`
ke `ulid` agar konsisten dengan primary key `users.id` (lihat §D-006a di
bawah soal ULID). `SUPER_ADMIN` mendapat semua permission via
`Gate::before` (bypass otomatis) supaya permission baru di fase-fase
berikutnya tidak perlu di-reseed manual ke role ini.

**Keputusan domain:** Tambah domain ke-19: `Identity` — User, Role,
Permission, Auth. `Organization` tetap khusus entitas perusahaan/tenant.

**Alasan:** Package battle-tested menghindari reinventing wheel (RULE 67:
jangan over-engineer) sekaligus memenuhi "permission harus configurable"
(§7 master prompt). Pemisahan domain `Identity` vs `Organization` menjaga
batas domain tetap jelas sesuai prinsip modular monolith.

### D-006a — Primary key: ULID untuk `users` & `organizations`, bigint untuk `roles`/`permissions`

**Konteks:** RULE 34 master prompt: "Gunakan UUID atau ULID untuk entity
utama." Perlu ditentukan mana yang termasuk "entity utama".

**Keputusan:** `users` dan `organizations` pakai ULID (dapat muncul di URL,
di-reference lintas domain sejak Phase 2). Tabel `roles`/`permissions` milik
package `spatie/laravel-permission` tetap bigint auto-increment default —
keduanya adalah master data tertutup yang dikelola admin, bukan resource
yang dibuka lewat URL publik/di-enumerate pihak luar, dan mengubah tipe PK
package pihak ketiga menambah friksi tanpa manfaat keamanan/desain nyata.

**Alasan:** Konsisten dengan semangat RULE 34 (menyamarkan ID resource
domain utama dari enumerasi) tanpa memaksakan ULID ke tabel lookup kecil
yang sudah punya konvensi package sendiri.

## D-007 — Validasi: `rules()` di komponen Livewire, bukan `FormRequest` terpisah, untuk modul yang murni Livewire-driven

**Konteks:** DoD (§8/§51 master prompt) menyebut "FormRequest" sebagai
bagian definition-of-done tiap modul. Modul Phase 1 (Organization, User)
sepenuhnya di-drive Livewire (tidak ada route POST/PUT controller
tradisional) — `Illuminate\Foundation\Http\FormRequest` dirancang untuk
siklus HTTP request (menerima `$this->route()`, `$this->user()` dari
request nyata), dan memaksakannya ke aksi Livewire (dipanggil lewat
`wire:submit`, bukan HTTP form POST biasa) hanya menambah indirection tanpa
manfaat.

**Keputusan:** Untuk modul yang aksinya 100% lewat Livewire, aturan
validasi didefinisikan di method `rules()` komponen Livewire itu sendiri
(idiomatic Livewire, setara FormRequest untuk konteks ini). `FormRequest`
sungguhan dipakai kalau ada endpoint HTTP asli (mis. API/import di fase
mendatang).

**Alasan:** Menghindari over-engineering (RULE 67) sambil tetap memenuhi
maksud DoD: sumber kebenaran validasi tunggal, testable, terpisah dari
markup.

## D-008 — Project vs Contract: normalisasi field sesuai §33, bukan §9 literal

**Konteks:** §9 master prompt menyebutkan Contract Number/Date, SPMK
Number/Date, Contract Value, dst sebagai bagian dari field "Project", tapi
§33 (Database Design) memisahkan tabel `projects` dan `contracts`.

**Keputusan:** `projects` menyimpan identitas/status/relasi/tanggal
eksekusi (start_date, end_date, duration_days); `contracts` menyimpan
detail legal/finansial (contract_number, contract_date, spmk_number,
spmk_date, contract_value, tax_amount, net_value), relasi 1:1 ke project.
"Provider" = `projects.organization_id` (organisasi/tenant itu SENDIRI
yang menjadi penyedia jasa) — tidak ada tabel `providers` terpisah.
"Unit Kerja" disimpan sebagai kolom string di `projects` (bukan tabel
tersendiri) karena §33 tidak mencantumkan tabel unit kerja.

**Alasan:** §9 kemungkinan menjelaskan field-field dari sudut pandang
form/UI (satu halaman input mencakup semua), sementara §33 adalah
normalisasi database yang eksplisit. Mengikuti §33 untuk skema fisik,
sambil form Project UI Phase 2 tetap mengekspos semua field via halaman
detail bertab (Overview/Kontrak) supaya sudut pandang §9 tetap terpenuhi
di level UX.

## D-009 — Project Type global, Client/Project organization-scoped

**Konteks:** Perlu diputuskan mana master data yang dibagi lintas
organisasi (tenant) dan mana yang terisolasi per organisasi.

**Keputusan:** `project_types` bersifat GLOBAL — dikelola hanya oleh
super_admin (`project_types.manage`), dapat dipakai/dipilih oleh semua
organisasi. `clients`, `contacts`, `projects`, `contracts` bersifat
PER-ORGANISASI (kolom `organization_id`, sama seperti `users`) — setiap
organisasi punya data klien/project sendiri, tidak saling terlihat
(kecuali super_admin).

**Alasan:** Project Type adalah taksonomi generik (§10: "Administrator
dapat membuat project type baru") yang masuk akal dipakai bersama lintas
perusahaan konsultan. Client/Project adalah data bisnis nyata milik
masing-masing perusahaan — membaginya lintas tenant akan jadi kebocoran
data antar kompetitor bisnis yang memakai sistem yang sama.

## D-010 — Status project: enum + validasi transisi di service (bukan FSM generik)

**Konteks:** Disebutkan sebagai keputusan tertunda di akhir Phase 1
(`PROJECT_HANDOVER.md`). User mengonfirmasi lanjut ke Phase 2 tanpa
mengoreksi opsi yang diusulkan.

**Keputusan:** `App\Domain\ProjectManagement\Enums\ProjectStatus`
(7 status sesuai §9) + method `allowedTransitions()`/`canTransitionTo()`
pada enum itu sendiri, divalidasi di `ProjectService::transitionStatus()`.
Tidak ada state-machine class/library generik terpisah.

**Alasan:** RULE 67 (jangan over-engineer) — 7 status dengan aturan
transisi statis tidak butuh mesin FSM. Jika kompleksitas bertambah
signifikan di fase mendatang (mis. transisi bersyarat per project type),
baru dipertimbangkan ekstraksi ke kelas terpisah.

## D-011 — Personnel: kategori master data + FK PM + FileStorageService generik

**Konteks:** Tiga keputusan kecil ditandai tertunda di akhir Phase 2
(`PROJECT_HANDOVER.md`). User mengonfirmasi memakai default yang
diusulkan pada awal Phase 3.

**Keputusan:**
1. `personnel_categories` — master data GLOBAL (pola sama dengan
   `project_types`, lihat D-009), bukan enum PHP tertutup, karena §39
   master prompt eksplisit meminta "Jenis Personel" configurable meski
   §33 tidak mencantumkannya sebagai tabel terpisah — diperlakukan
   sebagai tabel implisit yang diperlukan, bukan pelanggaran §33.
2. `projects.project_manager_personnel_id` (FK nullable ke `personnel`)
   ditambahkan MENDAMPINGI `project_manager_name` (kolom string lama),
   bukan menggantikannya — PM yang belum tercatat di roster Personnel
   organisasi tetap bisa diisi sebagai teks bebas. UI menampilkan nama
   dari Personnel jika FK terisi, fallback ke kolom string.
3. `App\Domain\Shared\Services\FileStorageService` dibangun sebagai
   wrapper tipis di atas Laravel Filesystem (disk `local`/private,
   bukan `public`) — dipakai personnel_documents sekarang, disiapkan
   untuk dipakai ulang oleh Evidence/DocumentGenerator di fase
   mendatang tanpa mengubah kontraknya.

**Alasan:** Konsisten dengan pola yang sudah terbukti di Phase 2
(D-009) untuk kategori master data; FK+string ganda pada PM menghindari
migrasi data yang merepotkan sekaligus tetap memungkinkan pencatatan
rapi begitu Personnel diisi; service file storage generik mencegah
duplikasi logika penyimpanan/keamanan file di setiap domain yang
butuh upload (RULE 41/42 master prompt: MIME/size validation di
validation rules form, path tidak publik, download lewat route
berpolicy).

## D-012 — Nama tabel Eloquent: `personnel` (bukan `personnels`)

**Konteks:** Konvensi pluralisasi otomatis Eloquent mengubah
`Personnel` (nama model) menjadi tabel `personnels` — salah, karena
"personnel" adalah kata baku yang sudah plural/tak-berhitung dalam
Bahasa Inggris. Ditemukan lewat error migrasi (FK ke tabel yang tidak
ada) dan lewat Larastan yang melaporkan SEMUA properti model
`Personnel` sebagai "undefined" karena skema yang diintrospeksi salah.

**Keputusan:** `Personnel::$table` dideklarasikan eksplisit sebagai
`'personnel'`. Semua migrasi yang mereferensikan tabel ini
menggunakan `->constrained('personnel')` eksplisit, tidak mengandalkan
inferensi otomatis dari nama kolom.

**Alasan:** Bug nyata (bukan gaya penulisan) — tanpa ini, foreign key
migrasi gagal total dan analisis statis tidak dapat memverifikasi kode
yang menyentuh model ini sama sekali.

## D-013 — Tax Type configurable; Contract.tax_type_id sebagai default, bukan sumber kebenaran yang dipaksakan

**Konteks:** §59 master prompt meminta Tax Type/Tax Rate configurable,
ditunda dari Phase 2 (lihat catatan di `PROJECT_HANDOVER.md` Phase 2).
Perlu diputuskan apakah `contracts.tax_amount`/`net_value` (kolom manual
sejak Phase 2) diganti sepenuhnya oleh perhitungan dari Tax Type.

**Keputusan:** `tax_types` sebagai master data GLOBAL (pola sama dengan
ProjectType/PersonnelCategory/CostCategory — D-009/D-011). Kolom
`contracts.tax_type_id` (nullable) ditambahkan sebagai ADDITIVE — ketika
dipilih di form, `tax_amount`/`net_value` diisi otomatis sebagai
**default/starting point** (asumsi nilai kontrak bersifat tax-inclusive,
konsisten dengan cara `ReferenceProjectSeeder` menghitung), tapi kedua
kolom itu TETAP bisa diedit manual sesudahnya — tidak divalidasi harus
persis sama dengan hasil hitung otomatis.

**Keputusan serupa untuk CostItem:** `cost_items.tax_type_id` (nullable)
+ `is_tax_inclusive` (boolean) menentukan arah kalkulasi
(`CostItemService::applyCalculation()`): tidak ada tax_type → tidak kena
pajak; ada tax_type + exclusive → pajak ditambahkan di atas harga; ada
tax_type + inclusive → pajak diekstrak dari harga yang sudah termasuk
pajak. `subtotal`/`tax_amount`/`total` disimpan sebagai snapshot hasil
hitung (bukan dihitung ulang saat tampil), konsisten dengan prinsip
"Data Snapshot" §62 master prompt.

**Alasan:** §60 master prompt eksplisit: sistem "mengotomatisasi
berdasarkan rule/template yang dikonfigurasi", bukan alat kepatuhan
pajak. Memaksa tax_amount mengikuti Tax Type secara ketat akan
bertentangan dengan itu — user tetap harus bisa override untuk kasus
nyata yang tidak persis mengikuti rumus sederhana (potongan pajak
majemuk, pembulatan sesuai faktur pajak asli, dst).

## D-014 — Payment tidak punya organization_id sendiri

**Konteks:** Perlu diputuskan apakah tabel `payments` (termin) butuh
kolom `organization_id` sendiri untuk RBAC scoping, seperti `clients`/
`projects`/`personnel`.

**Keputusan:** Tidak. `payments.project_id` sudah cukup — scoping
organisasi diturunkan dari `project.organization_id` (lewat
`ProjectPolicy::update` yang menjadi gerbang akses satu-satunya untuk
Payment, sama seperti Contract/CostItem/PersonnelAssignment — tidak ada
Policy terpisah untuk keempatnya).

**Alasan:** Menghindari duplikasi kolom scoping yang bisa jadi tidak
sinkron (mis. kalau project dipindah organisasi — meski itu sendiri
belum didukung — kolom organization_id di Payment bisa basi). Satu
sumber kebenaran (project) lebih aman daripada dua kolom yang harus
selalu dijaga konsisten.
