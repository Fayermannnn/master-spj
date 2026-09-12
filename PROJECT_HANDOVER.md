# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 5 — Document Requirement Engine: SELESAI.** Siap lanjut ke
**Phase 6 — Document Template** (upload template DOCX, deteksi
placeholder, versioning).

## Completed Features

### Phase 1-4 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen), PersonnelAssignment, TaxType, CostCategory, CostItem, Payment.

### Phase 5 (baru) — Document Requirement Engine

- **DocumentRequirement** — master data GLOBAL, opsional terikat ke satu
  Project Type (`project_type_id` nullable — null berarti berlaku untuk
  SEMUA jenis project). CRUD Livewire dengan rule bersarang (pola sama
  seperti Client+Contact), hanya super_admin
  (`document_requirements.manage`) yang mengelola.
- **RequirementRule** — kondisi tambahan pada satu requirement. Field &
  operator berasal dari VOCABULARY TERTUTUP (enum PHP), BUKAN expression
  bebas — lihat `PROJECT_DECISIONS.md` D-015 untuk alasan lengkap.
  Field yang tersedia sekarang: `has_personnel_assignments`,
  `has_payments`, `has_travel_cost`, `personnel_category_codes`,
  `payment_count`, `project_type_code`. Semua rule aktif pada satu
  requirement digabung dengan AND; requirement tanpa rule selalu
  berlaku.
- **RequirementRuleEvaluator** — service kecil & stateless yang
  mengevaluasi rule terhadap sebuah Project. **Kalau butuh field baru di
  masa depan:** tambah 1 case di `RequirementRuleField` + 1 cabang
  `match` di `resolveFieldValue()` — jangan pernah menambah reflection/
  dot-path bebas ke model, itu akan melanggar keputusan D-015.
- **ProjectChecklistItem** — status kelengkapan checklist per project,
  disinkronkan lazim oleh `ChecklistService::sync()` (menambah baris untuk
  requirement yang applicable, TIDAK menghapus baris lama meski
  requirement itu belakangan tidak lagi cocok — supaya status yang sudah
  diisi tidak hilang). Status (`ChecklistStatus`: Missing/Fulfilled/
  NotApplicable) masih ditoggle manual — **Phase 7/8 (Document
  Generator/SPJ Package) yang nanti akan mengisi status ini otomatis**
  begitu Document/Evidence benar-benar ada.
- Tab baru "Checklist" di halaman detail Project (pola sama dengan
  Kontrak/Personel/Biaya/Termin — gerbang akses lewat `ProjectPolicy`,
  tanpa policy terpisah untuk ProjectChecklistItem).
- Seed data: `DocumentRequirementSeeder` — 9 requirement universal wajib
  (Kontrak, SPMK, Surat Pernyataan, Invoice, Kwitansi, Faktur Pajak,
  Berita Acara, Laporan, Dokumentasi) + 3 requirement kondisional dengan
  rule nyata (Daftar Personel & Timesheet jika ada penugasan personel;
  Bukti Perjalanan jika ada biaya kategori TRAVEL). Diverifikasi manual
  di browser: project reference RSPNDD (yang punya personel & biaya
  perjalanan) menampilkan ke-12 requirement itu dengan benar, dan
  menandai satu item "Lengkap" langsung memperbarui progress bar.
- 13 test baru (66 total): unit test `RequirementRuleEvaluator` (7 test —
  tiap field/operator, AND semantics, rule tidak aktif diabaikan),
  feature test DocumentRequirement CRUD + delete-guard, feature test
  ChecklistService (universal selalu muncul, kondisional muncul/tidak
  sesuai data, toggle status). Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- Checklist status (`Missing`/`Fulfilled`/`NotApplicable`) MASIH manual
  — belum otomatis dari keberadaan Document/Evidence sungguhan (itu
  belum ada sampai Phase 7-8). **Saat Phase 7/8 dibangun, wire
  `ChecklistService::updateStatus()` dipanggil otomatis saat dokumen
  digenerate/diunggah, jangan hanya andalkan toggle manual.**
- `RequirementRuleField` vocabulary sengaja kecil (6 field). §18 master
  prompt menyebut lebih banyak kondisi contoh (personnel_has_certificate,
  payment_type=personnel, dst) yang BELUM diimplementasikan — tambahkan
  hanya kalau ada kebutuhan nyata, ikuti pola yang sama (1 enum case + 1
  match branch), jangan generalisasi jadi expression engine.
- Requirement per Project Type vs universal: kalau dua requirement (satu
  universal, satu spesifik project type) punya `code` yang sama secara
  konseptual (mis. dua "Laporan" berbeda kontennya per jenis project),
  itu HARUS jadi dua row terpisah dengan code berbeda (mis.
  `LAPORAN_SURVEY` vs `LAPORAN_STANDAR`) — tidak ada mekanisme
  "override" otomatis satu code yang sama di dua project type.

## Database Changes (Phase 5)

- `document_requirements`: ulid PK, project_type_id (FK nullable), code
  (unique), name, category, description, is_active, sort_order, soft
  delete. Global.
- `requirement_rules`: ulid PK, document_requirement_id (FK cascade),
  field, operator, value (nullable string), is_active.
- `project_checklist_items`: ulid PK, project_id/document_requirement_id
  (FK cascade), status, notes. Unique (project_id,
  document_requirement_id).

## Environment

Tidak berubah dari Phase 1-4.

## Next Task

**Phase 6 — Document Template**: Upload template DOCX (validasi MIME
`.docx` saja, simpan lewat `FileStorageService` yang sudah ada dari
Phase 3 — reuse, jangan bikin service upload baru), scan placeholder
`{{variable}}` di dalam file (butuh library baca isi DOCX — evaluasi
`phpoffice/phpword` di sini, bukan ditunda lagi ke Phase 7, karena
deteksi placeholder adalah bagian dari Phase 6 menurut roadmap §64
master prompt), tampilkan "Detected Variables" ke admin dengan warning
kalau variable tidak dikenal, versioning template (v1/v2/dst, project
pakai versi tertentu, dokumen lama tetap pakai versi lama — §63 master
prompt).

Sebelum mulai, putuskan:
1. Struktur `template_variables` — apakah tetap tabel master data
   terpisah (daftar variable yang "dikenal" sistem, mis.
   `project.name`, `client.name`, `payment.amount`) untuk validasi
   silang terhadap placeholder yang terdeteksi, atau cukup deteksi
   on-the-fly tanpa master data? Rekomendasi: BUTUH tabel master
   (§20 master prompt eksplisit minta sistem "mendukung" tipe variable
   text/number/currency/date/boolean/array/table) — tanpa itu tidak ada
   cara memvalidasi placeholder yang di-scan.
2. Apakah template terikat ke Project Type (seperti DocumentRequirement)
   atau ke DocumentRequirement langsung (satu requirement punya satu
   template default)? Rekomendasi: terikat ke DocumentRequirement,
   karena itu yang benar-benar dipakai saat generate dokumen di Phase 7.

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 66
test, 147 assertion, 0 error phpstan, 0 pint diff.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-015). Baru di Phase 5: D-015
(Document Requirement Engine — vocabulary field/operator tertutup, AND
semantics, checklist idempotent-append). Ini keputusan arsitektur
terbesar sejak Phase 0 — didiskusikan dengan user sebelum implementasi
sesuai RULE 9/10 CLAUDE.md.

## Security Notes

Tidak berubah dari Phase 1-4.
