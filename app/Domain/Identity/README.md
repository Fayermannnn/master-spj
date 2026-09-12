# Domain: Identity

Autentikasi, User Management, Role & Permission (RBAC). Terpisah dari domain
`Organization` — `Organization` mengelola entitas perusahaan/tenant itu
sendiri, `Identity` mengelola *siapa* yang login dan *apa* yang boleh mereka
lakukan.

RBAC dibangun di atas `spatie/laravel-permission` (lihat
`PROJECT_DECISIONS.md` D-006). Role & permission adalah master data — dapat
ditambah/diedit lewat Settings tanpa deploy kode baru.

> Lihat `docs/MODULES.md` dan `docs/domain-map.md` untuk aturan impor lintas domain.
