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

## D-015 — Document Requirement Engine: rule field/operator/value tertutup, bukan expression bebas

**Konteks:** §17-18 master prompt eksplisit meminta Document Requirement
Engine berbasis rule yang bisa ditambah/diedit admin, TAPI juga eksplisit
melarang expression engine yang kompleks di MVP. Ini keputusan arsitektur
paling besar sejak Phase 0 (RULE 9/10 CLAUDE.md) — didiskusikan dengan
user sebelum implementasi (lihat ringkasan opsi di `PROJECT_HANDOVER.md`
akhir Phase 4), dikonfirmasi lanjut.

**Keputusan:**
1. `RequirementRuleField` — enum PHP TERTUTUP berisi field yang boleh
   dipakai rule (`has_personnel_assignments`, `has_payments`,
   `has_travel_cost`, `personnel_category_codes`, `payment_count`,
   `project_type_code`). BUKAN dot-path bebas ke model manapun lewat
   reflection — menambah field baru berarti menambah satu enum case +
   satu cabang `match` di `RequirementRuleEvaluator::resolveFieldValue()`,
   bukan mengekspos seluruh graph model ke rule engine.
2. `RequirementRuleOperator` — enum tertutup (equals, not_equals,
   contains, is_true, is_false, greater_than, less_than) dengan
   `validFor(valueType)` yang membatasi kombinasi field↔operator yang
   masuk akal di level validasi form.
3. Semua rule AKTIF pada satu `DocumentRequirement` digabung dengan AND
   (`RequirementRuleEvaluator::passes()`); requirement tanpa rule aktif
   dianggap SELALU berlaku (untuk project type yang cocok).
4. `document_requirements.project_type_id` nullable = requirement
   universal (berlaku semua jenis project) — filter dasar SEBELUM rule
   dievaluasi, bukan bagian dari rule itu sendiri.
5. `project_checklist_items` bersifat idempotent-append: `ChecklistService::sync()`
   MENAMBAH baris untuk requirement yang baru applicable, TIDAK MENGHAPUS
   baris yang sudah ada meski requirement itu belakangan tidak lagi
   cocok (supaya status yang sudah diisi user tidak hilang diam-diam
   kalau rule/data project berubah).

**Alasan:** Vocabulary tertutup + evaluator kecil memenuhi maksud §18
("rule engine sederhana yang dapat dikembangkan") tanpa risiko keamanan/
kompleksitas dari expression parser bebas (mis. tidak ada cara bagi rule
untuk memanggil method sembarang atau membaca kolom sensitif). Menambah
kondisi baru di masa depan = menambah 1 enum case + 1 baris match, bukan
mengubah arsitektur.

## D-016 — Document Template: diikat ke DocumentRequirement; scan placeholder tanpa dependency baru

**Konteks:** §19-21 dan §63-64 master prompt: template dokumen dengan
versioning, deteksi placeholder `{{variable}}`, dan master data
`template_variables` untuk memvalidasi placeholder yang terdeteksi.
Direncanakan & dikonfirmasi user di akhir Phase 5.

**Keputusan:**
1. `document_templates.document_requirement_id` — template diikat
   LANGSUNG ke DocumentRequirement (bukan ke ProjectType), karena itu
   yang benar-benar dipakai saat generate dokumen (Phase 7). Satu
   requirement bisa punya banyak versi template (`version` unsigned int,
   unique per requirement).
2. `template_variables` — master data GLOBAL terpisah (pola sama dengan
   ProjectType/CostCategory/dll), berisi key/label/data_type
   (text/number/currency/date/boolean/array/table sesuai §20). Dipakai
   HANYA untuk membandingkan/menandai placeholder yang terdeteksi
   sebagai "dikenal" vs "tidak dikenal" (warning, bukan error blocking —
   sesuai §64: template tetap tersimpan meski ada placeholder tak
   dikenal).
3. `DocxPlaceholderScanner` — memindai `word/document.xml` di dalam
   arsip ZIP docx pakai `ZipArchive` BAWAAN PHP, tanpa dependency
   composer baru. `phpoffice/phpword` (atau alternatif lain) SENGAJA
   BELUM ditambahkan di Phase 6 — pemilihan library untuk MENGISI
   template (bukan sekadar mendeteksi placeholder) ditunda ke Phase 7
   (Document Generator) supaya keputusan itu dibuat dengan konteks
   penuh kebutuhan generate (termasuk table variable §21), bukan
   diputuskan prematur di sini.
4. Placeholder yang terpecah jadi beberapa run XML oleh Word (hal lazim
   karena formatting internal) ditangani dengan men-strip SELURUH tag
   XML dulu sebelum regex — bukan hanya tag `<w:t>` — sehingga run yang
   terpecah otomatis tergabung kembali secara tekstual.
5. Satu-satunya versi berstatus Active per requirement dijaga oleh
   `DocumentTemplateService::activate()` (meng-arsipkan versi lain),
   BUKAN oleh constraint database (partial unique index tidak portable
   antar driver).

**Alasan:** Memisahkan "deteksi placeholder" (Phase 6, kebutuhan
sederhana) dari "mengisi & generate DOCX" (Phase 7, kebutuhan jauh lebih
kompleks: table variable berulang, format currency/date, dst) mencegah
komitmen dini ke satu library sebelum kebutuhan generate benar-benar
jelas — sejalan RULE 67 (jangan over-engineer) tanpa mengorbankan
kualitas Phase 6 itu sendiri.

## D-017 — Bug Blade: literal `{{`/`}}` di dalam tag echo membingungkan compiler

**Konteks:** Ditemukan lewat test yang gagal (bukan lewat review manual)
— `resources/views/livewire/document-templates/manager.blade.php` dan
`template-variables/index.blade.php` menulis
`{{ '{{'.$variableKey.'}}' }}` untuk menampilkan placeholder literal
apa adanya. Blade compiler mencari `}}` PERTAMA yang ditemui secara
tekstual (bukan aware terhadap string literal PHP di dalamnya) — pada
pola itu, `}}` pertama yang ketemu ada DI DALAM string `'}}'`, bukan di
akhir tag, sehingga hasil kompilasi PHP-nya rusak ("Unclosed '('").

**Keputusan:** Bungkus nilai literal `{{...}}` di dalam blok `@php ... @endphp`
menjadi variabel biasa dulu (mis. `$wrappedVariable = '{{'.$key.'}}';`),
baru di-echo dengan `{{ $wrappedVariable }}` — tag echo yang isinya HANYA
nama variabel, tidak ada karakter `{{`/`}}` literal di dalam tag itu
sendiri.

**Alasan:** Ini bug nyata (bukan gaya penulisan) yang membuat halaman
500 di production kalau tidak ditemukan — dicatat di sini supaya pola
"menampilkan contoh placeholder Handlebars-style di UI" tidak
terulang di fase mendatang (Phase 7 Document Generator kemungkinan
besar butuh menampilkan hal serupa).

## D-018 — Document Generator: phpword TemplateProcessor, LibreOffice wajib, VariableResolver tertutup, `documents` terpisah dari checklist

**Konteks:** Phase 6 (D-016) sengaja menunda 3 keputusan ke Phase 7:
library pengisian DOCX, strategi konversi PDF, dan struktur tabel
`documents`. Ini keputusan arsitektur besar (RULE 10 CLAUDE.md) —
didiskusikan dengan user di awal Phase 7 lewat `AskUserQuestion`
sebelum implementasi dimulai.

**Keputusan:**

1. **Library DOCX**: `phpoffice/phpword` (`TemplateProcessor`), sesuai
   tabel stack di `PROJECT_BLUEPRINT.md` §4. Delimiter diubah dari
   default `${...}` ke `{{...}}` lewat `setMacroChars('{{', '}}')` agar
   konsisten dengan `DocxPlaceholderScanner` (Phase 6) dan dengan
   template yang sudah diunggah user. Table variable berulang (§21)
   dites LEBIH DULU sebelum keputusan final — terbukti didukung penuh
   oleh `TemplateProcessor::cloneRowAndSetValues()` (mengisi satu baris
   tabel berulang dari array 2 dimensi, mengindeks SEMUA placeholder
   dalam baris itu sekaligus, bukan cuma satu kolom) dan
   `deleteRow()` untuk kasus nol baris (mis. project tanpa personel
   ditugaskan) — tidak perlu workaround manual.
2. **PDF wajib, bukan fallback**: Ditanya eksplisit ke user karena
   LibreOffice TIDAK terpasang di lingkungan Herd lokal saat itu — user
   memilih **memasang LibreOffice dulu** (`brew install --cask
   libreoffice`) alih-alih opsi fallback "DOCX dulu, PDF menyusul".
   Akibatnya `DocumentGeneratorService::generate()` bersifat ATOMIK:
   DOCX dibuat di temp file, PDF WAJIB berhasil dikonversi (lewat
   `PdfConverterInterface` -> `LibreOfficePdfConverter`, menjalankan
   `soffice --headless --convert-to pdf` via
   `Illuminate\Support\Facades\Process` — bawaan framework, BUKAN
   dependency baru) sebelum kedua file dan baris `Document` disimpan;
   kalau konversi gagal (mis. binary hilang), tidak ada file/baris DB
   yang tersisa (dibersihkan), exception dibungkus jadi
   `DomainActionException` dengan pesan yang aman ditampilkan ke user.
   Binary dikonfigurasi lewat `config('services.libreoffice.binary')`
   (env `LIBREOFFICE_BINARY`, default `soffice`, resolve via PATH —
   Homebrew cask menaruh command wrapper di `/opt/homebrew/bin/soffice`
   yang otomatis ada di PATH setelah instalasi). Diverifikasi end-to-end
   sungguhan (bukan cuma test dengan fake) lewat browser terhadap
   `ReferenceProjectSeeder` — generate menghasilkan DOCX + PDF nyata
   yang bisa diunduh.
3. **Test TIDAK menjalankan LibreOffice sungguhan**: `PdfConverterInterface`
   di-bind ke `FakePdfConverter` (didefinisikan di file test) dalam
   Pest — proses eksternal nyata terlalu lambat/rapuh untuk dijalankan
   di setiap test run, beda kelas dengan alasan "no SQLite" (D-003,
   soal paritas skema DB, bukan soal menjalankan binary asli). Produksi
   tetap SELALU pakai `LibreOfficePdfConverter` nyata (dibind di
   `AppServiceProvider::register()`).
4. **`documents` tabel terpisah dari `project_checklist_items`**:
   Dikonfirmasi user (opsi "documents terpisah + payment_id opsional").
   Satu baris `documents` = satu HASIL generate (versi bertambah per
   pasangan project+requirement, unique constraint
   `[project_id, document_requirement_id, version]`), berisi
   `data_snapshot` (JSON nilai variable yang dipakai — §61-62, dokumen
   lama TIDAK berubah walau data project berubah setelahnya karena
   snapshot dan file sudah final), `document_template_id` (versi
   template yang dipakai, `restrictOnDelete` — histori generate tidak
   boleh kehilangan jejak provenance-nya), `payment_id` NULLABLE
   (konteks termin opsional untuk variable `payment.*`).
   `DocumentGeneratorService::generate()` otomatis meng-update
   `ProjectChecklistItem` terkait jadi `Fulfilled` lewat
   `ChecklistService::updateStatus()` yang sudah ada (bukan menulis
   langsung ke kolom status) — wiring yang sejak Phase 5 memang
   didokumentasikan sebagai pekerjaan Phase 7 (lihat docblock
   `ChecklistStatus`).
5. **`VariableResolver` — kosakata TERTUTUP**, pola yang identik dengan
   `RequirementRuleEvaluator` (D-15): `match()` atas key yang dikenal
   (`project.*`, `client.*`, `ppk.name`, `provider.*` = organisasi
   pemilik project sesuai D-008, `payment.*`, `today`), BUKAN dot-path
   bebas lewat reflection. Variable baru yang ditambah admin lewat UI
   `TemplateVariable` (mis. field kustom) TETAP BISA dipakai saat
   generate — setiap placeholder yang terdeteksi di template selalu
   dapat kotak input teks yang bisa diisi manual di form generate —
   tapi AUTO-RESOLVE (prefill otomatis dari data Project/Payment) baru
   aktif setelah ditambahkan satu cabang `match` baru di
   `VariableResolver::resolveScalar()`. Ini sengaja: fungsionalitas inti
   (isi & generate) tidak pernah terkunci menunggu deploy kode, hanya
   KENYAMANAN auto-fill yang butuh perubahan kode — lebih baik dari
   pola D-15 murni karena form tidak pernah "gagal" untuk variable yang
   belum dikenal kode.
6. **Tidak ada permission baru** — generate/hapus dokumen digerbangi
   `ProjectPolicy::update` yang sama dengan Payment/CostItem/Checklist
   (D-014), bukan permission `documents.generate` baru. Alasan sama
   dengan D-014: dokumen adalah sub-resource project, bukan master data
   independen.
7. **Bug Livewire ditemukan SEBELUM ditulis ke test** (lewat tinjauan
   manual, bukan test gagal seperti D-017): `wire:model` Livewire
   mengartikan SETIAP titik pada path sebagai array bersarang
   (`data_get`/`data_set`). Placeholder seperti `project.name`
   mengandung titik LITERAL sebagai bagian dari nama key array PHP
   (`$variableInputs['project.name']`), sehingga
   `wire:model="variableInputs.project.name"` akan salah diartikan
   Livewire sebagai `$variableInputs['project']['name']`. Diperbaiki
   dengan menyimpan `$variableInputs` sebagai LIST terindeks angka
   (sejajar urutan dengan `tablelessDetectedKeys()`), `wire:model`
   memakai indeks (`variableInputs.0`, dst — indeks murni angka, tidak
   ambigu), key placeholder aslinya di-`array_combine` kembali saat
   submit ke `DocumentGeneratorService::generate()`. Pola ini akan
   terulang untuk fitur Livewire manapun yang mem-bind array dengan key
   berisi titik — jangan pakai key string apa adanya di `wire:model`
   untuk kasus itu.

## D-019 — SPJ Package & Evidence: cakupan ganda (per-termin/project-level), isi Document+Evidence, kelengkapan dihitung dinamis

**Konteks:** Fase 8 (roadmap §9: "SPJ Package — checklist, evidence, ZIP
export") jauh lebih minim spesifikasi dibanding fase-fase sebelumnya —
blueprint hanya berisi satu baris per domain (`Evidence`: "bukti
pendukung/lampiran, metadata, smart linking ke project/payment/
personnel/requirement"; `Spj`: "pengelompokan dokumen per termin,
checklist kelengkapan, manifest, export ZIP") plus daftar entitas kasar
di §8 (`evidences, spj_packages, spj_items`). Dua fork arsitektur besar
ditanyakan ke user lewat `AskUserQuestion` sebelum implementasi (RULE
10 CLAUDE.md) — sisanya (lifecycle, format manifest, isi ZIP,
permission) diputuskan mengikuti pola yang sudah establish, didokumentasikan
di sini.

**Keputusan:**

1. **Cakupan SpjPackage GANDA** (dikonfirmasi user, bukan hanya
   per-termin): `spj_packages.payment_id` NULLABLE — diisi = paket per
   termin, null = paket level project ("SPJ Akhir"). Tidak ada tabel/
   enum terpisah untuk membedakan "jenis" paket — cukup nullability satu
   kolom (`SpjPackage::isProjectLevel()` helper), konsisten dengan pola
   nullable-FK-sebagai-context opsional yang sudah dipakai `Document.payment_id`
   (D-018) dan sekarang `Evidence.payment_id`.
2. **Isi package: Document DAN Evidence** (dikonfirmasi user, scope
   lebih besar dari opsi minimal). `Evidence` dibangun penuh sebagai
   domain baru — model+migration+FileStorageService (reuse method
   `store()` yang sudah ada, BUKAN `storeFromPath()` yang khusus file
   hasil-generate Phase 7, karena upload Evidence lewat form HTTP biasa
   seperti PersonnelDocument) + Livewire manager (pola identik
   `Personnel\Documents`) + download controller (pola identik
   `DownloadPersonnelDocumentController`). "Smart linking" diimplementasi
   sebagai FK NULLABLE EKSPLISIT (`payment_id`, `personnel_id`,
   `document_requirement_id`) — BUKAN polymorphic (`morphTo`) — karena
   aplikasi ini TIDAK PERNAH memakai polymorphic relation di manapun;
   menambahkannya di sini hanya untuk 3 target link yang sudah diketahui
   sejak awal akan menambah kompleksitas tanpa manfaat nyata (RULE 67).
3. **`SpjItem` — manifest pivot, menunjuk PERSIS SATU dari `document_id`/
   `evidence_id`** (nullable keduanya, divalidasi di service — bukan
   DB constraint, pola sama D-016 poin 5: partial unique/check
   constraint tidak portable). Duplikasi (dokumen/evidence yang sama
   ditambahkan dua kali ke package yang sama) dicegah di
   `SpjPackageService::addDocument()/addEvidence()` via query exists()
   check, melempar `DomainActionException`.
4. **Lifecycle SpjPackage: Draft -> Finalized, TIDAK ADA jalan balik**
   (`SpjPackageStatus`, pola sama `TemplateStatus`/`PaymentStatus`).
   Finalisasi mengunci manifest (`assertDraft()` melempar exception
   untuk add/remove item pada paket Finalized) — mencegah paket yang
   sudah "diserahkan"/diekspor disusupi item baru diam-diam. TIDAK ADA
   versioning paket seperti DocumentTemplate/Document (D-016/D-018) —
   revisi = buat paket BARU, sesuai kesederhanaan yang cukup untuk unit
   kerja ini (satu paket biasanya dibuat sekali per termin/akhir
   project, bukan berulang seperti dokumen individual).
5. **"Kelengkapan checklist" dihitung DINAMIS, bukan status tersimpan**
   — `SpjPackageService::coverage()` membandingkan isi package (via
   `SpjItem::documentRequirementId()`, dibaca dari `document`/`evidence`
   terkait) terhadap `ChecklistService::applicableRequirements($project)`
   yang SUDAH ADA sejak Phase 5. Ini SATU logika seragam untuk paket
   per-termin MAUPUN paket level-project — paket per-termin akan wajar
   menunjukkan cakupan sebagian (mis. hanya requirement yang relevan
   untuk termin itu) karena dibandingkan terhadap SELURUH checklist
   project, bukan sub-set khusus per-termin. Tidak dibangun konsep
   "requirement per termin" terpisah — di luar scope yang diminta
   (`Payment.required_items` sejak Phase 4 sudah menampung kebutuhan itu
   sebagai teks bebas untuk dibaca manual oleh user, bukan struktur baru).
6. **Export ZIP**: `ZipArchive` bawaan PHP (pola sama D-016), SINKRON
   (bukan queued job — jumlah item per paket kecil, RULE 67). Isi ZIP:
   PDF setiap Document (fallback DOCX kalau entah bagaimana tidak ada
   PDF — seharusnya tidak pernah terjadi karena generate wajib PDF sejak
   D-018) + file asli setiap Evidence, diberi nama `"NN - Nama.ext"`
   berurutan sesuai `sort_order`, plus `manifest.txt` (bukan PDF/DOCX —
   RULE 67, tidak perlu render dokumen untuk sekadar daftar isi) berisi
   metadata paket + daftar item dan requirement yang dipenuhinya.
7. **Tidak ada permission baru** — Evidence dan SpjPackage keduanya
   digerbangi `ProjectPolicy::update`/`view` yang sama dengan Payment/
   CostItem/Checklist/Document (D-014/D-018), karena keduanya
   sub-resource project, bukan master data independen.
8. **Bug nyata ditemukan lewat Larastan SEBELUM masuk test** (mirip pola
   D-012, bukan D-017/D-018 poin 7 yang ditemukan manual): `Evidence`
   adalah kata tak-berhitung dalam Bahasa Inggris — Eloquent secara
   otomatis meng-resolve nama tabelnya jadi `evidence` (singular),
   BUKAN `evidences` (nama tabel migrasi). Tanpa `protected $table =
   'evidences';` eksplisit, SETIAP query/insert lewat model ini akan
   gagal total di runtime (tabel `evidence` tidak ada) — nyaris identik
   dengan bug Personnel (D-012). Ditemukan dari phpstan level 8 yang
   melaporkan SEMUA kolom Evidence sebagai "undefined property" (karena
   Larastan mengintrospeksi tabel yang salah), bukan dari test yang
   gagal — **pelajaran berulang: SETIAP model baru dengan nama benda
   tak-berhitung/ambigu (evidence, information, equipment, series, dst.)
   harus dicek `(new Model())->getTable()` di tinker SEBELUM menulis
   query pertama**, jangan asumsikan pluralisasi Eloquent selalu benar.

**Alasan ringkas:** Menghormati keputusan user (cakupan ganda + Document+Evidence
sekaligus) sambil menahan diri dari over-engineering di setiap area yang
TIDAK diminta eksplisit (tanpa polymorphic, tanpa versioning paket, tanpa
queue, tanpa requirement-per-termin terpisah) — konsisten dengan RULE 67
dan pola keputusan yang sudah terbukti di fase-fase sebelumnya.

## D-020 — Dashboard: domain Workplan baru (milestone/deliverable), laporan Excel+PDF reuse infrastruktur Phase 7

**Konteks:** Roadmap Phase 9 ("Dashboard — dashboard, laporan, timeline")
sama minimnya dengan Phase 8 — hanya tiga kata kunci tanpa detail. Dua
ambiguitas besar ditanyakan ke user lewat `AskUserQuestion` sebelum
implementasi (RULE 10): (1) arti "timeline" — feed aktivitas dari
AuditLog yang sudah ada (ringan) vs domain Workplan baru untuk
milestone/deliverable (berat); (2) cakupan "laporan" — widget dashboard
saja vs halaman Laporan tersendiri + unduh PDF/Excel. User memilih
opsi yang LEBIH BESAR untuk keduanya.

**Keputusan:**

1. **Domain `Workplan` baru** — `Milestone` (titik pemeriksaan timeline,
   mis. "Laporan Pendahuluan Diserahkan") dan `Deliverable` (output
   project, mis. "DED Blok Plan" — cocok dengan istilah "Lingkup" di
   `PROJECT_BLUEPRINT.md` §3) sebagai model TERPISAH tapi terhubung
   (`Deliverable.milestone_id` NULLABLE — deliverable boleh berdiri
   sendiri atau terkait satu milestone). Status dipakai BERSAMA lewat
   satu enum `WorkplanStatus` (Pending/InProgress/Completed) — bukan
   FSM generik (pola sama D-010). "Terlambat" (`isOverdue()`) dihitung
   DINAMIS dari `target_date` vs hari ini, BUKAN status tersimpan —
   supaya tidak perlu job terjadwal.
2. **Visual "Gantt-lite"** pada tab Timeline — marker milestone
   diposisikan sebagai persentase dalam rentang `project.start_date`/
   `end_date` (`Manager::timelinePosition()`), warna berbeda untuk
   Completed (hijau) vs Terlambat (merah) vs Pending biasa (biru).
   BUKAN chart library (Chart.js dll.) — cukup `<div>` + `style="left:
   X%"` (RULE 67), diverifikasi via inspeksi DOM langsung (posisi
   persentase benar: 12.7%/53.6%/clamp ke 100% untuk tanggal di luar
   rentang project).
3. **Laporan: SATU laporan well-scoped ("Laporan Ringkasan Project"),
   BUKAN report builder generik** — tabel lintas-project (kode, nama,
   klien, status, nilai kontrak, total dibayar, kelengkapan checklist)
   dengan filter (cari, status, klien) yang SAMA persis dipakai baik
   oleh tampilan layar maupun ekspor (`ProjectSummaryReportService`
   satu sumber kebenaran) — mencegah angka yang beda antara yang
   dilihat di layar vs yang diunduh.
4. **Excel: `phpoffice/phpspreadsheet` LANGSUNG**, bukan wrapper
   `maatwebsite/laravel-excel` — konsisten dengan gaya aplikasi ini
   yang selalu memakai library inti langsung tanpa lapisan abstraksi
   tambahan (`ZipArchive`, `phpword` `TemplateProcessor` juga dipakai
   langsung, bukan lewat wrapper). Sekeluarga dengan `phpoffice/phpword`
   yang sudah terpasang sejak Phase 7, jadi footprint dependency
   tambahan minimal.
5. **PDF laporan: REUSE PENUH `PdfConverterInterface`/`LibreOfficePdfConverter`
   dari Phase 7** — bangun tabel laporan sebagai DOCX lewat `phpword`
   (bukan `TemplateProcessor`, cukup `addTable()`/`addText()` biasa
   karena tidak ada placeholder untuk diisi), konversi PDF lewat
   pipeline LibreOffice yang SAMA. TIDAK menambah library PDF baru
   (dompdf/snappy/dst) hanya untuk satu tabel laporan sederhana
   (RULE 67) — satu infrastruktur PDF untuk seluruh aplikasi.
6. **Domain `Reporting` baru** (bukan salah satu dari 19 domain awal
   D-005) — dibuat karena logika lintas-project/agregasi tidak cocok
   masuk `ProjectManagement` (yang fokus CRUD/siklus SATU project).
   Tidak ada `docs/domain-map.md` yang melarang penambahan domain baru
   di repo ini (CLAUDE.md RULE 1 mereferensikannya tapi filenya tidak
   pernah dibuat) — domain baru yang well-justified tetap sejalan
   dengan prinsip modular monolith selama scope-nya jelas.
7. **Scoping laporan & dashboard**: pola SAMA dengan `Projects\Index`
   yang sudah ada sejak Phase 2 — `when(!$viewer->hasRole('super_admin'),
   fn ($q) => $q->where('organization_id', $viewer->organization_id))`.
   Tidak ada permission baru — digerbangi `projects.viewAny` yang sudah
   ada (laporan pada dasarnya adalah tampilan agregat dari data Project
   yang sama).
8. **Dashboard**: total project, project aktif, total nilai kontrak
   (agregat, org-scoped), breakdown per status, dan "Milestone
   Terdekat" (5 milestone belum Completed terdekat lintas project,
   dari domain Workplan yang baru — koneksi alami antara widget
   dashboard dan timeline per-project).

**Alasan ringkas:** Menghormati pilihan user (Workplan penuh + halaman
laporan+unduh) sambil tetap menahan diri dari scope creep di detail
implementasi yang tidak diminta eksplisit — tanpa report builder
generik, tanpa chart library, tanpa dependency PDF baru, tanpa
permission baru — memakai kembali sebanyak mungkin infrastruktur yang
sudah terbukti dari fase-fase sebelumnya (RULE 67).

## D-021 — Phase 10 QA: audit menyeluruh (security/performance/test/UX), fix nyata + keterbatasan yang didokumentasikan sengaja

**Konteks:** Phase 10 (roadmap: "QA — test, security, performance, UX
polish") adalah fase PENUTUP, bukan fase fitur — mengaudit 9 fase kode
yang sudah terkumpul (~150 file). Dikonfirmasi ke user (AskUserQuestion)
untuk memilih "audit menyeluruh" dibanding pass tertarget cepat. Skill
`security-review` bawaan TIDAK bisa dipakai (berbasis `git diff
origin/HEAD` — repo ini tidak punya remote sejak D-001), jadi audit
dilakukan manual lewat 3 subagent riset paralel (security, performance,
test coverage) + tinjauan UX langsung oleh sesi ini via browser.

**Temuan & perbaikan (SEMUA diverifikasi lewat `composer ci` + browser,
bukan cuma dibaca):**

1. **[Security] Evidence menerima SEMUA jenis file** — `Evidence\Manager`
   hanya validasi `required|file|max:10240`, TIDAK ADA `mimes:` — beda
   dari `DocumentTemplates\Manager` (`mimes:docx`) dan
   `Personnel\Documents` (`mimes:pdf,jpg,jpeg,png,doc,docx`) yang
   sama-sama upload file di app ini. Diperbaiki: tambah
   `mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx` (cakupan wajar untuk
   "bukti pendukung" sesuai deskripsi domain Evidence).
2. **[Security] Hash password bocor ke `audit_logs`** — `UserService`
   mengirim `$user->getAttributes()`/`getChanges()` APA ADANYA ke
   `AuditLogService::record()` pada create/update/delete, termasuk
   kolom `password` (hash bcrypt) dan `remember_token`. `audit_logs`
   append-only dan bisa dibaca admin manapun yang berhak — hash tidak
   boleh ada di sana sama sekali (menambah permukaan serangan offline
   cracking kalau tabel ini pernah diekspos ke audiens admin yang lebih
   luas). Diperbaiki: `UserService::redact()` membuang `password`/
   `remember_token` sebelum dikirim ke audit log, di ketiga method
   (create/update/delete).
3. **[Performance] N+1 nyata di tab "Dokumen"** — `GeneratedDocuments\Manager`
   memanggil `activeTemplateFor()`/`generatedDocumentsFor()` (masing-
   masing 1 query) PER checklist item di dalam loop Blade — ~30-45
   query tambahan per render untuk project dengan ~15 requirement.
   Diperbaiki: `render()` membangun 2 peta (`whereIn` sekali) yang
   dicache di property PRIVATE (bukan state Livewire — tidak
   disinkronkan lewat wire, murni cache per-render), method publik
   membaca dari cache itu kalau sudah terisi.
4. **[Performance] Laporan Ringkasan Project tidak dipaginasi** — beda
   dari SEMUA halaman Index lain di app ini (Projects/Clients/Personnel/
   dst semua pakai `WithPagination`), `Reports\ProjectSummary` memuat
   SEMUA project yang cocok filter sekaligus, masing-masing lewat
   `ChecklistService::sync()` (mahal, lihat poin 5) — tumbuh O(project)
   tanpa batas. Diperbaiki: tabel di layar dipaginasi 15/halaman
   (`paginatedRows()`, `->through($service->toRow(...))`), TAPI kartu
   total (jumlah project/nilai kontrak/total dibayar) TETAP mencerminkan
   SELURUH hasil filter lewat method baru `aggregates()` yang memakai
   SQL `sum()` langsung (JOIN ke `contracts`/`payments`, tanpa
   `toRow()`/`sync()` sama sekali) — jadi murah dihitung berapa pun
   jumlah project yang cocok filter, sementara tabel detail tetap
   dibatasi per halaman. Ekspor Excel/PDF TETAP memakai `rows()` yang
   tidak dipaginasi (sengaja — unduhan harus berisi SEMUA baris yang
   cocok filter, bukan cuma satu halaman).
5. **[Performance] `ChecklistService::sync()` mahal, TIDAK diperbaiki
   (sengaja)** — audit performa menemukan tiap panggilan mengevaluasi
   ulang query yang identik untuk field rule yang sama di requirement
   berbeda (mis. beberapa requirement sama-sama punya rule
   `has_payments`) untuk project yang sama — sekitar 16 query per
   `sync()` untuk project dengan 15 requirement/10 rule aktif. SEMPAT
   dicoba perbaikan (memoize `RequirementRuleEvaluator::resolveFieldValue()`
   per instance), TAPI DIBATALKAN — `tests/Unit/DocumentRequirement/RequirementRuleEvaluatorTest.php`
   sengaja memanggil `passes()` berulang pada evaluator+project yang
   SAMA dengan DATA YANG BERUBAH di antaranya (mis. tambah
   PersonnelAssignment lalu evaluasi ulang, mengharapkan hasil
   berbeda) — pola yang sah dan bisa terjadi di alur nyata juga (data
   project berubah lalu checklist dievaluasi ulang dalam request yang
   sama). Cache per-instance mengembalikan hasil BASI pada kasus itu —
   4 test langsung gagal, membuktikan ini BUG KOREKTNES nyata, bukan
   cuma soal test yang perlu disesuaikan. **Dibiarkan tidak
   dioptimasi** — RULE 67 (jangan over-engineer demi masalah skala yang
   masih spekulatif; app ini realistis dipakai puluhan project per
   organisasi, bukan ribuan) lebih diutamakan daripada resiko bug
   silent-stale-data. Kalau skala nyata membuktikan ini bottleneck
   sungguhan di masa depan, perbaikan yang benar adalah memindahkan
   cache ke LEVEL BATCH (`ChecklistService::applicableRequirements()`
   menghitung semua field SEKALI di awal sebelum loop rule), bukan
   cache tersembunyi di evaluator.
6. **[UX] Bug overflow horizontal di SELURUH halaman pada mobile** — BUKAN
   di tab nav seperti dugaan awal (nav sendiri sudah benar), tapi
   classic flexbox trap: `<main>` adalah flex item (`flex-1`) dari
   container `flex-col` di `layouts/app.blade.php`, dan flex item
   defaultnya `min-width: auto` (tidak pernah menyusut di bawah lebar
   konten instrinsiknya). Nama project yang panjang di judul header
   sticky memaksa SELURUH halaman melebar mengikuti flex item terlebar,
   bukan wrapping/truncate. Diperbaiki: `min-w-0` pada `<main>` dan
   div flex-col pembungkusnya, `truncate` + `min-w-0` pada `<h1>` judul
   header. **Ditemukan & diverifikasi lewat browser sungguhan
   (viewport mobile 375px), bukan cuma baca kode** — dan sempat
   "gagal" berkali-kali sebelum ketahuan bahwa `npm run build` belum
   pernah dijalankan ulang setelah tiap perubahan class Tailwind (tidak
   ada proses Vite dev yang watch di `.claude/launch.json`, hanya
   `php artisan serve`) — pelajaran: SETIAP perubahan class Tailwind di
   sesi ini butuh `npm run build` manual sebelum diverifikasi di
   browser, beda dari perubahan PHP/Blade logic yang langsung
   ter-refresh.
7. **[Test] Bug nyata ditemukan lewat MENULIS test, bukan membaca kode**
   — `tests/Feature/Contracts/` tidak ada SAMA SEKALI sebelum QA
   (domain Contract, sejak Phase 2, nol test). Saat menulis test
   pertama untuk domain ini, `ContractService::save()` GAGAL di level
   database (`SQLSTATE[22007]: invalid input syntax for type date: ""`)
   ketika field opsional (`spmk_number`/`spmk_date`/`tax_amount`/
   `net_value`/`notes`) dibiarkan kosong — `Contracts\Form::save()`
   HANYA mengonversi `tax_type_id` dari string kosong ke `null` sebelum
   dikirim ke service, field opsional lain TIDAK — bug produksi nyata
   (user mengisi form kontrak tanpa nomor SPMK akan mengalami 500).
   Diperbaiki: `save()` sekarang mengonversi SEMUA field nullable dari
   `''` ke `null` secara seragam sebelum dikirim ke `ContractService`.
8. **[Test] Cross-organization authorization TIDAK PERNAH diverifikasi**
   untuk 3 domain yang sengaja tanpa Policy sendiri (D-014: Payment,
   CostItem, PersonnelAssignment — digerbangi `ProjectPolicy::update`).
   Klaim D-014 hanya diuji lewat jalur same-org happy-path sejak
   ditulis. Ditambahkan test hostile-org untuk ketiganya — SEMUA lolos
   (klaim D-014 terbukti benar), tapi sebelumnya memang tidak
   dibuktikan sama sekali. Juga ditambahkan: test denial `ClientPolicy`
   (satu-satunya Policy tanpa test jalur ditolak), test file-type/size
   rejection + akses pasca-hapus untuk Evidence, dan test bahwa hash
   password tidak pernah muncul di `audit_logs` (memverifikasi
   perbaikan poin 2).
9. **Tidak diperbaiki (dicatat, bukan diabaikan)** — dari audit test
   coverage: enum status lain (`ChecklistStatus`/`SpjPackageStatus`/
   `TemplateStatus`/`WorkplanStatus`) belum diuji jalur transisi
   tidak-valid; beberapa service (`VariableResolver`,
   `EvidenceService`, dst) hanya teruji TIDAK LANGSUNG lewat komponen
   Livewire-nya (dianggap cukup — bukan celah, sesuai audit); file
   generate/export (`GeneratedDocuments`/`SpjPackages`/`Reports`) belum
   diuji untuk kasus file sumber hilang dari disk pasca soft-delete.
   Ditinggalkan sebagai backlog QA lanjutan, bukan diperbaiki paksa
   dalam satu fase yang sudah menyentuh banyak domain sekaligus
   (RULE 67 — hindari scope creep tak berujung dalam satu fase).

**Hasil akhir:** 123 test (13 baru), `composer ci` bersih. Tidak ada
temuan security KRITIS (tidak ada path traversal/SQLi/XSS/command
injection/IDOR/mass-assignment nyata — semua kategori itu "No issue
found" di audit) — dua temuan yang ada (MIME Evidence, password di
audit log) bersifat hardening, sudah diperbaiki.
