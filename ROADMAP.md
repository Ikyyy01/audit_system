# Roadmap — Audit System KAP MGN

> Disusun berdasarkan review menyeluruh terhadap kondisi project per 1 September 2026 (backend Laravel + frontend Vue 3). Diurutkan dari yang paling ngeblok pekerjaan harian sampai yang sifatnya penyempurnaan.

---

## 🔴 Prioritas 1 — Ngeblok progress kerja

### 1. Seed Form 1110 (Survey Klien) & 1130 (Evaluasi Independensi A–D)
- `DynamicForm.vue` di frontend sudah general-purpose (baca section/field dari database via `/api/v1/audit-forms`), tapi data section/field untuk form 1110 & 1130 **belum di-seed** ke `audit_form_sections` / `audit_form_fields`.
- Source dokumen aslinya sudah tersedia di folder:
  - `1000 Risk Assesment/1110 Survey Klien IAS 2024 Rev 1.docx`
  - `1130 A. Cek Latar Belakang - Independence Checklist....doc`, `1130 B....xlsx`, `1130 C....docx`, `1130 D. Entities Tree....docx`
- Begitu seeder-nya dibikin (ikut pola `Form1100FieldsSeeder.php`), auditor sudah bisa langsung kerja di 2 form ini tanpa tambahan kode frontend.

### 2. CRUD Engagement
- Sekarang hanya ada CRUD **Client**. Belum ada halaman untuk bikin/edit **Engagement** (kode engagement, tahun perikatan, status, assign tim per role).
- Tanpa ini, engagement baru kemungkinan besar cuma bisa dibuat manual lewat seeder/DB langsung — nggak sustainable untuk operasional harian.

### 3. Kelola User (Junior / Senior / Manager / Partner)
- **Belum ada `UserController`** sama sekali di backend, dan belum ada halaman admin untuk bikin akun baru.
- Kalau ada karyawan baru masuk, satu-satunya cara nambahin akun sekarang lewat seeder manual — perlu endpoint + halaman admin.

---

## 🟠 Prioritas 2 — Fitur inti yang masih kosong

### 4. Upload dokumen / bukti audit
- Model `Document` & `DocumentVersion` sudah ada di skema database, tapi **belum dipakai sama sekali** di controller maupun frontend.
- Sebagian besar folder `3000 Audit Evidence and Documentation` isinya bukti fisik/scan (bukan Q&A form) — butuh fitur upload file dengan versioning, bukan `DynamicForm`.

### 5. Activity log ditampilkan ke UI
- Model `ActivityLog` sudah aktif mencatat setiap aksi submit/review/approve/upload signature, tapi **belum ada halaman untuk melihatnya**.
- Penting sebagai audit trail — siapa melakukan apa, kapan.

### 6. Lanjutkan digitalisasi form-form sisanya
Sesuai `mapping-folder-vs-flowchart.md`, sebagian besar tinggal seed data + pakai `DynamicForm.vue` yang sudah general-purpose:

| Kelompok | Kode Form |
|---|---|
| Risk Assessment (sisa) | 1120, 1200, 1210, 1400, 1430, 1440, 1500, 1600, 1610, 1700, 1800, 1900 |
| Risk Response | 2100, 2110, 2200, 2300, 2400 |
| Audit Evidence | 3100 Balance Sheet (per akun: Kas & Bank, Utang, Modal, Aset Tetap) |
| Representation & Consultation | 4100, 4300, 4400 |
| Reporting | 5100, 5200, 5300, 5610, 5700, 5900–5908 |
| PMPJ | 1, 3, 4, approval letter |

> Catatan: ada beberapa file di folder sumber asli yang belum masuk daftar TODO di `PROGRESS.md` — misalnya `1410a`, `1410b`, `1420`, `1441`, `1450`, `1460`, dan folder `Revisi Form 1000 Inspeksi`. Perlu ditambahkan ke mapping biar nggak kelewat.

### 7. Dashboard progress % per klien/engagement
- Terinspirasi dari fitur "Analisa Project" di ATLAS (kompetitor/pembanding sejenis) — nunjukin progress pengerjaan per klien dalam persentase (mis. "PT ABC 100%, PT DEF 90%, PT FGH 75%").
- Dashboard saat ini baru nampilkan angka **global** per status (Draft/Menunggu Review/dst), belum ada breakdown per klien.

---

## 🟡 Prioritas 3 — Kualitas & konsistensi

### 8. Unifikasi warna/design token
- `DashboardLayout.vue` (sidebar) pakai warna hex hardcode (`#DC2626` crimson, `#0F172A` navy), **tidak konsisten** dengan token di `design.md` (`--orange-600`, `--ink-900`, `--green-700`) yang dipakai di `Login.vue`, `Dashboard.vue`, dan `Form1100.vue`.
- Perlu disatukan supaya nggak terasa seperti dua aplikasi dengan tema berbeda.

### 9. Penanganan token expired / 401 secara global
- Tiap komponen melakukan `fetch()` manual sendiri-sendiri dengan Bearer token.
- Kalau token expired, **tidak ada auto-redirect** ke halaman login — request cuma gagal diam-diam tanpa feedback ke user.
- Solusi: bikin wrapper `apiFetch()` terpusat yang handle 401 → clear localStorage → redirect ke `/`.

### 10. Testing nyaris nol
- Folder `tests/` isinya masih boilerplate default Laravel (`ExampleTest.php` di `Feature/` dan `Unit/`).
- Belum ada test untuk alur krusial: submit → review → approve, validasi role middleware, dan penyimpanan jawaban form.

---

## 🟢 Prioritas 4 — Nice to have

### 11. Notifikasi
- In-app atau email, dikirim saat ada form baru masuk antrian review (ke Manager) atau approval (ke Partner).

### 12. Lupa password / reset password
- Belum ada flow reset password sama sekali — kalau lupa, satu-satunya cara reset lewat DB langsung.

### 13. Uji export ke form lain
- Endpoint `/export` sekarang sudah generik (baca struktur section/field dari database, bukan hardcode Form 1100), tapi baru pernah dites ke Form 1100. Perlu divalidasi ulang begitu Form 1110/1130/dst sudah ke-seed.

---

### 14. Dukungan tampilan mobile/responsive
- Saat ini `style.css` sengaja pakai `min-width: 1024px` — aplikasi didesain desktop-only, belum responsive ke layar HP.
- Rencana ke depan: dukung keduanya (desktop tetap prioritas utama karena user KAP kerja di layar besar, tapi versi mobile juga dibutuhkan untuk akses cepat/on-the-go).
- Perlu kerjaan cukup besar: sidebar collapsible/hamburger menu, breakpoint responsive di semua halaman (Login, Dashboard, Form1100, DynamicForm, AdminFolderDrive, ClientManagement), bukan sekadar fix CSS kecil.
- **Belum dikerjakan — fokus saat ini masih ke penyempurnaan desktop UI dulu.**

---

## Riwayat perbaikan yang sudah selesai (per 1 September 2026)

Sebagai konteks, berikut yang sudah dibenerin dalam sesi-sesi sebelumnya — supaya nggak dikerjain ulang:

- ✅ Login page & Dashboard didesain ulang sesuai `design.md`
- ✅ Auth guard global di router + role guard untuk halaman Admin-only
- ✅ **Bug kritis:** jawaban Form 1100 sekarang benar-benar tersimpan ke database (sebelumnya cuma numpang di memory browser dan hilang saat reload)
- ✅ Badge status & editable-state form dibenerin (konsisten snake_case, bisa diedit lagi saat status `revision_required`)
- ✅ `AdminFolderDrive.vue` dibenerin — data API yang tadinya nested sekarang di-flatten dengan benar, tombol "Buka hasil form" yang tadinya mati sekarang berfungsi
- ✅ Endpoint export DOCX dibuat dari nol (pakai `phpoffice/phpword`), termasuk tabel Q&A per section, riwayat review/approval, catatan rekan, keputusan penerimaan klien, dan gambar tanda tangan Partner yang di-embed langsung
- ✅ Kolom `partner_notes`, `engagement_decision`, `signature_path` ditambahkan ke `audit_form_responses` + endpoint `savePartnerNotes` & `uploadSignature`
- ✅ Rute `/export` yang sempat terdaftar dobel (nubruk sama controller lama yang belum lengkap) dibersihkan
