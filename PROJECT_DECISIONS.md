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
