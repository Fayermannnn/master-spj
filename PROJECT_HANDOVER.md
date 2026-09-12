# PROJECT HANDOVER — Sistem SPJ Otomatis

Diperbarui setiap akhir fase. Tujuan: agar sesi Claude Code berikutnya bisa
lanjut tanpa kehilangan konteks.

## Current Phase

**Phase 4 — Cost & Payment: SELESAI.** Siap lanjut ke **Phase 5 —
Document Requirement Engine** (kebutuhan dokumen SPJ berbasis rule).

## Completed Features

### Phase 1-3 (ringkas — detail di git log / PROJECT_DECISIONS.md)
Auth, RBAC, Organization, User Management, ProjectType, Client+Contact,
Project (status siklus), Contract, PersonnelCategory, Personnel (+
dokumen via FileStorageService), PersonnelAssignment.

### Phase 4 (baru)

- **TaxType** — master data GLOBAL (pola sama seperti ProjectType/
  PersonnelCategory/CostCategory), hanya super_admin
  (`tax_types.manage`) yang mengelola. Seed baseline: PPN 11%, PPh 21 5%,
  PPh 23 2%, Tidak Kena Pajak 0%.
- **CostCategory** — master data GLOBAL, 12 kategori baseline sesuai §13
  master prompt (Personil, Non-Personil, Perjalanan, Akomodasi, dst).
- **CostItem** — item biaya per project. `CostItemService` menghitung
  subtotal/pajak/total otomatis dari quantity × unit_price + TaxType.rate,
  dengan dua mode: **exclusive** (pajak ditambahkan di atas harga) dan
  **inclusive** (pajak diekstrak dari harga yang sudah termasuk pajak) —
  dipilih lewat checkbox `is_tax_inclusive`. Hasil hitung DISIMPAN
  (snapshot), bukan dihitung ulang saat tampil. Dikelola inline di tab
  "Biaya" pada halaman detail Project (pola sama dengan Contract/
  PersonnelAssignment — tidak ada policy terpisah, gerbang lewat
  `ProjectPolicy::update`).
- **Payment (Termin)** — siklus status 5 tahap (`PaymentStatus`: Pending
  → Submitted → Approved/Rejected → Paid) dengan transisi tervalidasi,
  pola sama seperti `ProjectStatus` (D-010). **Validasi RULE 57 master
  prompt**: total pembayaran (termasuk termin yang sedang diedit) tidak
  boleh melebihi `contract_value`, kecuali user mencentang "izinkan
  melebihi nilai kontrak" — diuji eksplisit lewat Pest DAN diverifikasi
  manual di browser (pesan error jelas menyebut nilai kontrak dalam
  Rupiah). Constraint unik (`project_id`,`termin_number`).
- **Contract.tax_type_id** (additive) — memilih jenis pajak di form
  Kontrak otomatis mengisi `tax_amount`/`net_value` sebagai DEFAULT
  (asumsi nilai kontrak tax-inclusive), tapi kedua kolom tetap bisa
  diedit manual — tidak dipaksakan mengikuti hasil hitung.
- Seed data: `TaxTypeSeeder`, `CostCategorySeeder`, dan
  `ReferenceProjectSeeder` diperluas dengan 2 item biaya contoh
  (Perjalanan Survey, Pencetakan Laporan) + 1 termin (Termin 1 — Uang
  Muka 20% = Rp396.122.184, sesuai pagu KAK RSPNDD).
- 53 test Pest (38 Phase 1-3 + 15 Phase 4 baru): TaxType/CostCategory CRUD
  + delete-guard, CostItem kalkulasi (no-tax, exclusive, inclusive,
  recompute saat update), Payment create/update dalam & melebihi limit
  kontrak (dengan & tanpa override), status transition valid/invalid,
  unique termin_number. Semua hijau (`composer ci`).

## Known Issues / Deferred (sengaja, bukan bug)

- **Percentage kosong → error Postgres** (bug nyata yang ditemukan &
  diperbaiki saat implementasi, pola SAMA seperti bug tanggal kosong di
  Phase 2): field numeric nullable yang dikosongkan user mengirim string
  kosong `''`, bukan `null`, ke database. **Kalau menambah field numeric/
  date nullable baru di Livewire component manapun, selalu normalisasi
  `$data['field'] = $data['field'] !== '' ? $data['field'] : null;`
  sebelum simpan — jangan berasumsi validasi `nullable` saja cukup.**
- CostItem belum ter-link otomatis ke PersonnelAssignment meski kolom
  `personnel_assignment_id` sudah ada di skema — link ini disiapkan untuk
  laporan/rekonsiliasi biaya personel vs assignment di fase mendatang,
  belum ada UI untuk mengisinya. Bukan bug, hanya belum dipakai.
- Payment belum terhubung ke Document Requirement (checklist dokumen
  yang harus lengkap sebelum termin bisa diajukan) — `required_items`
  masih kolom teks bebas. Ini akan diwire dengan benar begitu Document
  Requirement Engine (Phase 5) ada.
- Total nilai project (dari CostItem) belum direkonsiliasi dengan
  Contract.contract_value di UI manapun — keduanya independen untuk saat
  ini. Kalau nanti dibutuhkan dashboard "budget vs actual", itu pekerjaan
  Phase 9 (Dashboard/Reporting).

## Database Changes (Phase 4)

- `tax_types`: ulid PK, code (unique), name, rate (decimal 5,2),
  is_active, description, soft delete. Global.
- `cost_categories`: ulid PK, code (unique), name, description,
  is_active, soft delete. Global.
- `cost_items`: ulid PK, project_id/cost_category_id (FK),
  personnel_assignment_id (FK nullable, belum dipakai UI), tax_type_id
  (FK nullable), description, quantity, unit, unit_price,
  is_tax_inclusive, subtotal, tax_amount, total, notes, soft delete.
- `payments`: ulid PK, project_id (FK — TIDAK ADA organization_id
  sendiri, lihat D-014), termin_number, name, percentage, amount,
  target_date, trigger, required_items, status, submission_date,
  approval_date, payment_date, notes, soft delete. Unique (project_id,
  termin_number).
- `contracts`: tambah kolom `tax_type_id` (FK tax_types, nullable,
  nullOnDelete) — migration terpisah, additive.

## Environment

Tidak berubah dari Phase 1-3.

## Next Task

**Phase 5 — Document Requirement Engine**: ini adalah fase paling teknis
dan penting menurut master prompt (§17-18) — Document Requirement Engine
berbasis rule, BUKAN checklist statis. Entitas: `document_requirements`
(per Project Type — jenis dokumen apa saja yang wajib), `requirement_rules`
(kondisi: mis. "jika personnel.category = tenaga_ahli maka CV wajib").

Sebelum mulai coding, WAJIB desain dulu skema rule yang cukup sederhana
(§18 master prompt eksplisit: "Jangan membuat expression engine yang
terlalu kompleks pada MVP"). Rekomendasi pendekatan:
1. `requirement_rules` sebagai baris data dengan kolom `condition_field`,
   `condition_operator`, `condition_value` (bukan expression string bebas
   yang di-eval) — mis. field=`personnel_category.code`,
   operator=`equals`, value=`TENAGA_AHLI`.
2. Evaluasi rule dilakukan oleh satu class kecil (`RequirementRuleEvaluator`)
   yang menerima "subject" (Project/PersonnelAssignment/dll) dan mengecek
   kondisi field=operator=value secara generik — bukan per-kasus hard-code.
3. Jangan coba mendukung SEMUA kombinasi kondisi dari §18 sekaligus di
   MVP — mulai dari 3-4 kondisi konkret yang disebutkan (project_type,
   personnel_category, has_certificate, payment_type) dan perluas kalau
   memang dibutuhkan.

Ini keputusan arsitektur yang cukup besar — kalau ada keraguan soal
desain rule engine, berhenti dan diskusikan opsi ke user dulu sebelum
menulis migration (RULE 9/10 master prompt), jangan berasumsi sendiri.

## Test Status

`composer ci` (pint --test + phpstan level 8 + pest): **PASSED** — 53
test, 127 assertion, 0 error phpstan, 0 pint diff.

## Important Decisions

Lihat `PROJECT_DECISIONS.md` (D-001 s/d D-014). Baru di Phase 4: D-013
(Tax Type configurable, Contract.tax_type_id sebagai default bukan
sumber kebenaran dipaksakan, CostItem inclusive/exclusive tax), D-014
(Payment tidak punya organization_id sendiri, scoping dari project).

## Security Notes

Tidak berubah dari Phase 1-3.
