# Analisis Requirement Lengkap Sistem Audit
## Hasil Breakdown Sticky Note Meeting

---

## 1. Catatan Pink — Requirement Utama Aplikasi

### A. "Form Template Dijadikan 1 dalam Aplikasi"

Maksudnya template-template audit yang sekarang **terpisah-pisah ingin disatukan dalam satu aplikasi**.

#### Kondisi Sekarang (Manual):
```
Template Word
Template Excel
Template lainnya
        ↓
Auditor isi manual
        ↓
Save file
        ↓
Upload / kirim
```

#### Target (Sistem):
```
Aplikasi
   ↓
Pilih Engagement
   ↓
Pilih Form Audit
   ↓
Isi form langsung
   ↓
Save / Submit
```

**Kesimpulan**: Aplikasi bukan cuma **tempat menyimpan file**, tapi menjadi tempat auditor **mengerjakan working paper/form secara langsung**.

---

### B. "Form RCO Template Word → Dijadikan Form Aplikasi Saja"

Ada template tertentu (Form RCO) yang masih berbentuk **Word**, dan perlu **ditransformasikan menjadi form digital**.

#### Contoh Transformasi:

**Sebelumnya (Word)**:
```
Nama Client : __________
Periode     : __________
Auditor     : __________

Risk:
____________________

Conclusion:
____________________
```

**Sesudahnya (Sistem)**:
```
Client       [ PT ABC ▼ ]
Periode      [ 2026 ]
Auditor      [ Risky ▼ ]

Risk
[______________________]

Conclusion
[______________________]

        [ SAVE ] [ SUBMIT ]
```

**Proses**: `Word → dianalisis field-nya → dibuat menjadi form aplikasi`

Bukan hanya `Word → upload`, tapi benar-benar **digitalisasi form**.

---

## 2. "Upload Auditor → Review Manager → Approval Partner"

Ini workflow approval yang **sangat jelas dan berjenjang**.

### Diagram Workflow:
```
                 AUDITOR
                    │
                    │ Submit / Upload
                    ▼
              PENDING REVIEW
                    │
                    ▼
                MANAGER
                    │
              ┌─────┴─────┐
              │           │
           Reject       Approve
              │           │
              ▼           ▼
           Auditor     Partner
           Revisi        │
                         ▼
                     APPROVED
```

### Penting:
- **Role bukan cuma menentukan siapa boleh login**
- **Role menentukan siapa boleh melakukan action terhadap form**

### Tabel Action per Role:
| Role    | Action                                |
|---------|---------------------------------------|
| Junior  | Isi form, upload/submit               |
| Senior  | Review / monitoring                   |
| Manager | Review & approval sesuai workflow     |
| Partner | Final approval                        |

**Catatan dari meeting**: `Role: Partner, Manager, Senior, Junior` memperkuat bahwa aplikasi membutuhkan **role-based access control (RBAC)** yang detail.

---

## 3. "Sistem Bisa Copy Log ke Periode ..."

**Konteks**: Copy pekerjaan/data dari periode audit sebelumnya.

Ini penting karena audit adalah **proses periodik**.

### Contoh Skenario:

**Audit 2025 PT ABC:**
```
PT ABC
Audit 2025
   │
   ├── Kas & Bank
   ├── Piutang
   ├── Persediaan
   └── Aset Tetap
```

**Audit 2026 PT ABC:**
Tidak perlu membuat seluruh struktur dari awal.

```
Audit 2025
     │
     │ COPY
     ▼
Audit 2026
```

### Yang Boleh Dicopy:
- ✓ Struktur form
- ✓ Daftar task
- ✓ Akun/siklus
- ✓ Template
- ✓ Konfigurasi engagement

### Yang TIDAK Boleh Dicopy:
- ✗ Status dan approval hasil periode lama
- ✗ Data hasil audit akhir (tetap sebagai historical record)

---

## 4. "Perbedaan antara TBK dan Non-TBK Ada di Form 5000 → 5500"

**Status**: ⚠️ **MASIH SAMAR - PERLU KONFIRMASI**

### Yang Terlihat:
Catatan membahas perbedaan antara **TBK vs Non-TBK** memengaruhi form atau prosedur yang digunakan.

### Implikasi Sistem:
Jangan biarkan aplikasi menganggap semua client sama.

```
Kondisi Salah:
Semua Client → Semua Form

Kondisi Benar:
Client
  │
  ├── Type TBK
  │      ↓
  │   Form tertentu
  │
  └── Type Non-TBK
         ↓
      Form berbeda
```

### Action:
**Ini wajib dikonfirmasi ke orang audit**, karena kita tidak boleh menebak arti TBK atau aturan pemakaiannya hanya dari catatan.

### Status: ❓ TBD (To Be Determined)

---

## 5. Catatan Hijau Tengah — Mapping Form dan Assignment

Berikut yang terbaca dari catatan:

```
1900 = 2110
Konfirmasi

2400 = Penugasan nama role by:
CRUD: nama akun

FORM 3000: ? → harus diskusikan
```

### 5.1. "1900 = 2110"

Dari daftar form sebelumnya:
- **1900** = Komunikasi Tim Perikatan
- **2110** = Komunikasi Tim Audit

**Kemungkinan**: Ada **kemiripan/relasi antara form 1900 dan 2110**, atau ada proses yang perlu dihubungkan.

**Status**: ⚠️ **PERLU DIKONFIRMASI**

Apakah hubungannya:
```
Sequential:        Parallel:
1900 → 2110        1900 ↔ 2110
```

Karena tulisan "1900 = 2110" belum cukup untuk menyimpulkan jenis relasinya.

---

### 5.2. "2400 = Penugasan nama role by:"

**Penting untuk Sistem**.

Form 2400 sebelumnya adalah: **Pemeriksaan Informasi Teknologi**

Catatan baru menunjukkan: **Penugasan nama role by** (assignment user ke pekerjaan berdasarkan role)

### Konsep yang Dibutuhkan:

#### Skenario Assignment:
```
Engagement PT ABC Audit 2026
       │
       ├── Partner : Budi
       ├── Manager : Andi
       ├── Senior  : Rian
       └── Junior  : Risky
```

#### Struktur Assignment:
```
User: Risky
Role: Junior
Engagement: PT ABC - Audit 2026
Assignment: Risky → Junior → PT ABC Audit 2026
```

### Mengapa Ini Penting:
- ✗ Salah: `users.role = junior` (statis, tidak fleksibel)
- ✓ Benar: Assignment dinamis per engagement (seseorang bisa multi-engagement)

### Database Schema (Contoh):
```
users
├── id
├── name
└── role

assignments
├── id
├── user_id (FK to users)
├── engagement_id (FK to engagements)
├── role_id (FK ke role spesifik di engagement ini)
└── assigned_at
```

---

### 5.3. "CRUD: Nama Akun"

**Catatan**: Sebelumnya tertulis "CRUP" tapi yang benar adalah **"CRUD"** (Create, Read, Update, Delete)

### Konteks:
Ada kebutuhan untuk menentukan **account/akun yang diperiksa** dalam setiap engagement.

### Daftar Akun Contoh:
```
Kas & Bank
Piutang
Persediaan
Aset Tetap
Pendapatan
Beban
```

### Assignment Akun ke Auditor:
```
PT ABC Audit 2026
│
├── Kas & Bank
│      └── Junior A
│
├── Piutang
│      └── Junior B
│
├── Persediaan
│      └── Senior A
│
├── Aset Tetap
│      └── Junior A
│
├── Utang Usaha
│      └── Senior B
│
└── Beban
       └── Manager
```

### Database Schema (Contoh):
```
audit_accounts
├── id
├── engagement_id
├── account_code
├── account_name
└── assigned_to_user_id

Contoh:
| engagement_id | account_code | account_name | assigned_to_user_id |
|---|---|---|---|
| 1 | 1100 | Kas & Bank | 5 |
| 1 | 1200 | Piutang | 6 |
| 1 | 1300 | Persediaan | 7 |
```

---

### 5.4. "FORM 3000: ? → Harus Diskusikan"

**Status**: ⚠️ **TBD - BELUM FINAL**

Dari daftar form sebelumnya, Form 3000 adalah:
> **Reporting** (dengan sub-form 3100, 3200, 3300, dst)

**Tapi**: Catatan menunjukkan masih ada pertanyaan tentang Form 3000.

### Action:
**Jangan langsung coding berdasarkan asumsi.** Requirement untuk bagian Reporting belum final dan perlu dikonfirmasi.

---

## 6. Catatan Hijau Kanan — Role Definition

Tulisan: `Role: Partner, Manager, Senior, Junior`

### Struktur Hierarki:
```
Partner (Level 1)
   ↓
Manager (Level 2)
   ↓
Senior (Level 3)
   ↓
Junior (Level 4)
```

### ⚠️ PERHATIAN:
**Senior belum tentu hanya "melihat".**

Hak akses harus ditentukan **per fitur**, bukan hanya per role.

### Tabel Komprehensif Hak Akses:
| Fitur | Junior | Senior | Manager | Partner |
|---|:---:|:---:|:---:|:---:|
| Isi form | ✓ | ✓ | ✓ | ✓ |
| Submit | ✓ | ✓ | ✓ | - |
| Review | - | ✓ | ✓ | ✓ |
| Request Revision | - | ✓ | ✓ | ✓ |
| Approval | - | ? | ✓ | ✓ |
| Lihat dashboard | ✓ | ✓ | ✓ | ✓ |
| Lihat audit trail | Terbatas | Terbatas | Lengkap | Lengkap |
| Kelola user | - | - | Terbatas | Ya |

**Tanda `?`** artinya perlu dikonfirmasi dengan orang audit.

---

## 7. Bagian "1120" — Alur Client Baru

**Status**: ⚠️ **TIDAK KONSISTEN - PERLU KONFIRMASI**

### Yang Terlihat:
```
1120: 1. Client baru 2. ...
```

### Masalah:
Dari daftar form sebelumnya, Form 1120 adalah:
> **Surat Keberatan Klien**

Tapi catatan membahas "Client baru", yang tidak konsisten.

### Kemungkinan:
1. Kode form yang tertulis bukan 1120
2. Mereka membahas proses lain
3. Definisi form di sistem internal KAP berbeda

### Action:
**Ini wajib ditanyakan** di meeting berikutnya.

### Status: ❓ TBD

---

## 8. Bagian "1130" — Subform

**Status**: ⚠️ **SAMAR - PERLU KONFIRMASI DETAIL**

### Yang Terlihat:
```
1130: ✓ → ...
8 ...
9 ...
```

Tulisan terlalu samar untuk ditranskrip dengan pasti.

### Dari Daftar RBA Sebelumnya:
Form 1130 punya subform:
```
1130A
1130B
1130C
1130D
```

### Rekomendasi Sistem:
Buat struktur hierarki dalam aplikasi:

```
1130 (Parent Form)
│
├── 1130A (Sub-form)
├── 1130B (Sub-form)
├── 1130C (Sub-form)
└── 1130D (Sub-form)
```

**Lebih baik** daripada membuat 1130 sebagai satu form besar yang isinya campur semua.

### Database Schema (Contoh):
```
audit_forms
├── id | code | name | parent_form_id | type
├── 1  | 1130 | Form 1130 | NULL | parent
├── 2  | 1130A| Sub-form A | 1 | child
├── 3  | 1130B| Sub-form B | 1 | child
├── 4  | 1130C| Sub-form C | 1 | child
└── 5  | 1130D| Sub-form D | 1 | child
```

---

## 9. "1600 EBIT / Revenue / Equity" — Perhitungan Materialitas

**Status**: ✓ **JELAS dan PENTING**

### Sebelumnya:
> Form 1600 = Penentuan Materialitas

### Catatan Baru:
> Form 1600 → EBIT / Revenue / Equity

### Maksud:
Perhitungan materialitas di Form 1600 menggunakan **basis tertentu**:
- **EBIT** (Earnings Before Interest and Taxes)
- **Revenue** (Pendapatan)
- **Equity** (Ekuitas)

### Konsep Sistem:
```
Form 1600
Materiality Calculation
        │
        ├── Basis: EBIT
        │     ↓
        │  Input nilai EBIT
        │     ↓
        │  Kalikan persentase
        │     ↓
        │  Materialitas = hasil
        │
        ├── Basis: Revenue
        │     ↓ (proses sama)
        │
        └── Basis: Equity
              ↓ (proses sama)
```

### Alur Perhitungan:
```
Pilih Basis
   ↓
Input Nilai Basis
   ↓
Sistem ambil Persentase (dari KAP standard)
   ↓
Kalkulasi Otomatis
   ↓
Tampilkan Materialitas Value
```

### ⚠️ PENTING:
**Jangan tentukan persentasenya sendiri.** Itu harus mengikuti **metodologi KAP**.

### Database Schema (Contoh):
```
materialitycalculations
├── id
├── engagement_id
├── basis_type (enum: EBIT, Revenue, Equity)
├── basis_value (nilai EBIT/Revenue/Equity)
├── percentage (dari KAP standard)
├── materiality_value (calculated)
└── created_at

kap_standards
├── id
├── client_type (TBK, Non-TBK, dll)
├── basis_type
├── percentage
└── description
```

---

## 10. Struktur Aplikasi Audit (Big Picture)

Dengan semua catatan digabung, bentuk aplikasi terlihat seperti:

```
                         SISTEM AUDIT
                              │
               ┌──────────────┴──────────────┐
               │                             │
            CLIENT                       ENGAGEMENT
               │                             │
               └──────────────┬──────────────┘
                              │
                         AUDIT PERIOD
                              │
                              ▼
                       RISK BASED AUDIT
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
   RISK ASSESSMENT       RISK RESPONSE         REPORTING
      (Form 1000)         (Form 2000)          (Form 3000)
          │                   │                   │
          └───────────────────┼───────────────────┘
                              │
                              ▼
                         WORKING PAPER
                              │
                              ▼
                     AUDITOR SUBMISSION
                              │
                              ▼
                       MANAGER REVIEW
                              │
                    ┌─────────┴─────────┐
                    ▼                   ▼
              REVISION          FORWARD TO PARTNER
                  ↓                     ▼
            RESUBMIT              PARTNER APPROVAL
                                       │
                                       ▼
                                    FINAL
```

### User/Role Hierarchy:
```
                  USER / ROLE
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
     Partner       Manager       Senior/Junior
```

---

## 11. Lima Requirement Besar yang Muncul

Dari semua catatan, ada **5 kebutuhan utama**:

### 1. Template Word → Form Digital
- Perlu **Form Builder** atau minimal form yang sudah didefinisikan
- Template audit ditransformasikan menjadi form digital
- Bukan hanya upload, tapi digitalisasi penuh

### 2. Form Saling Terintegrasi
- Data tidak diinput berulang-ulang
- Contoh: Form 1400 → 1450 → 1440 → 2200 (linked)
- Ada dependency dan relationship antar form

### 3. Workflow Approval Berjenjang
```
Junior/Senior
     ↓
Submit
     ↓
Manager Review
     ↓
Partner Approval
     ↓
Final
```

### 4. Assignment Dinamis
```
Engagement
   ↓
Account / Form
   ↓
Assigned Auditor
   ↓
Role dalam Context tersebut
```

### 5. Periode Audit Multi-Tahun
```
PT ABC
 ├── Audit 2025
 ├── Audit 2026
 └── Audit 2027

Dengan kemampuan copy struktur dari periode sebelumnya.
```

---

## 12. Database Design (High Level)

Kalau nanti coding Laravel, **jangan bikin tabel per form** seperti:
```
❌ form_1100
❌ form_1110
❌ form_1120
❌ form_1130
... (puluhan tabel)
```

Ini bakal berantakan.

### Design yang Benar (Generic):
```
users
├── id
├── name
├── email
└── role_id

roles
├── id
├── name (Admin, Auditor, Manager, Partner)
└── description

clients
├── id
├── name
├── client_type (TBK, Non-TBK, dll)
└── address

engagements
├── id
├── client_id
├── engagement_code
├── engagement_year
└── status

audit_periods
├── id
├── engagement_id
├── period_start
├── period_end
└── status

audit_forms
├── id
├── code (1100, 1110, 1120, dst)
├── name
├── parent_form_id (untuk subform)
└── form_type

audit_form_sections
├── id
├── form_id
├── section_name
└── order

audit_form_fields
├── id
├── section_id
├── field_name
├── field_type (text, number, date, dropdown, checkbox, textarea)
└── required

audit_form_responses
├── id
├── form_id
├── engagement_id
├── field_id
├── response_value
└── created_at

audit_accounts
├── id
├── engagement_id
├── account_code
├── account_name
└── assigned_to_user_id

audit_assignments
├── id
├── engagement_id
├── user_id
├── role_id (role dalam context engagement ini)
└── assigned_at

audit_reviews
├── id
├── form_response_id
├── reviewed_by_user_id
├── review_status (pending, approved, revision_required)
└── comments

audit_approvals
├── id
├── form_response_id
├── approved_by_user_id
├── approval_status (pending, approved, rejected)
└── approval_date

documents
├── id
├── engagement_id
├── document_name
├── file_path
└── uploaded_at

document_versions
├── id
├── document_id
├── version_number
├── file_path
└── created_at

activity_logs
├── id
├── user_id
├── action (created, updated, submitted, reviewed, approved)
├── entity_type (form, document, engagement)
├── entity_id
└── timestamp

form_relations
├── id
├── source_form_id
├── target_form_id
├── relationship_type (depends_on, linked_to, updates)
└── description

materialitystandards
├── id
├── client_type
├── basis_type (EBIT, Revenue, Equity)
├── percentage
└── description
```

### Mengapa Design Ini Lebih Baik:

1. **Scalable**: Tambah form baru cukup insert di `audit_forms`, tidak perlu buat tabel baru
2. **Fleksibel**: Field bisa dinamis tergantung definisi di `audit_form_fields`
3. **Auditable**: Semua action tercatat di `activity_logs`
4. **Relational**: Form bisa punya hubungan via `form_relations`
5. **Assignment**: Mendukung multi-role per engagement via `audit_assignments`

---

## 13. Daftar Pertanyaan untuk Meeting Berikutnya

Hal-hal yang masih **samar atau belum final**:

### TBD (To Be Determined):
- ❓ **TBK vs Non-TBK**: Bentuk form, field, atau prosedur audit berbeda?
- ❓ **CRUD Akun**: Detail bagaimana auditor melakukan Create, Read, Update, Delete akun?
- ❓ **Detail Form 1120**: Apakah benar berhubungan dengan "Client baru" atau ada bentuk lain?
- ❓ **Detail Form 1130 & subform**: Apa saja requirement detail untuk 1130A, 1130B, 1130C, 1130D?
- ❓ **Form 3000**: Requirement apa yang sudah final untuk bagian Reporting?
- ❓ **Form 1900 ↔ 2110**: Apakah hubungannya sequential (1900→2110) atau parallel (1900↔2110)?
- ❓ **Senior Role**: Apakah Senior punya hak untuk melakukan approval atau hanya review?
- ❓ **Copy Periode**: Apakah ada data yang perlu di-"reset" sebelum copy ke periode baru?

---

## 14. Lifecycle Form 1100: Auditor, Manager, dan Partner

Requirement ini berlaku untuk Form 1100 Memo Penerimaan & Keberlanjutan Klien dan menjadi pola untuk form audit lain yang memiliki workflow review serta approval.

### 14.1. Saat Auditor Membuat Form

1. Auditor login ke sistem dan membuka engagement yang ditugaskan kepadanya.
2. Auditor memilih Form 1100.
3. Sistem membuat draft Form 1100 apabila belum ada form aktif untuk engagement tersebut.
4. Informasi pembuat terisi otomatis dari akun login auditor:
   - `Dibuat oleh`: nama/inisial auditor
   - `Tanggal dibuat`: waktu sistem saat draft pertama dibuat
5. Auditor mengisi seluruh jawaban, komentar, dan lampiran/dokumen pendukung yang diperlukan.
6. Auditor menyimpan sebagai draft atau submit ke Manager.
7. Setelah submit, status form berubah menjadi `Pending Review` dan form tidak dapat diubah oleh Auditor kecuali Manager meminta revisi.

### 14.2. Saat Manager Melakukan Review

1. Manager login dan membuka daftar Form 1100 dengan status `Pending Review` pada engagement yang menjadi tanggung jawabnya.
2. Sistem menampilkan seluruh isi form, jawaban, komentar, dan lampiran yang diinput Auditor.
3. Manager dapat memberikan komentar review pada pertanyaan tertentu atau pada form secara keseluruhan.
4. Manager memilih salah satu tindakan:
   - `Request Revision`: status berubah menjadi `Revision Required`; form kembali ke Auditor untuk diperbaiki.
   - `Review Selesai / Forward to Partner`: status berubah menjadi `Reviewed` atau `Pending Approval` dan form diteruskan ke Partner.
5. Ketika Manager menyelesaikan review, sistem mengisi otomatis:
   - `Direview oleh`: nama/inisial Manager yang login
   - `Tanggal review`: waktu sistem saat Manager meneruskan form ke Partner

### 14.3. Saat Partner Memberikan Approval

1. Partner login dan membuka Form 1100 dengan status `Pending Approval`.
2. Partner melihat form, riwayat perubahan, komentar Auditor/Manager, dan lampiran termasuk file tanda tangan apabila sudah diunggah.
3. Partner memilih salah satu tindakan:
   - `Reject / Request Revision`: form dikembalikan ke Auditor atau Manager sesuai catatan Partner.
   - `Approve`: form menjadi final.
4. Ketika Partner menyetujui form, sistem mengisi otomatis:
   - `Disetujui oleh`: nama/inisial Partner yang login
   - `Tanggal approval`: waktu sistem saat Partner menekan tombol approval
   - `Status`: `Approved`
5. Form yang sudah `Approved` menjadi read-only dan perubahan hanya dapat dilakukan melalui proses revisi resmi yang tercatat pada audit trail.

### 14.4. Tanda Tangan Partner

1. Field `Tanda Tangan` bukan input teks.
2. Partner mengunggah file tanda tangan berupa gambar `PNG`/`JPG` atau dokumen `PDF`.
3. File tanda tangan disimpan sebagai dokumen/lampiran yang berhubungan dengan Form 1100 dan engagement terkait.
4. Saat Partner mengunggah tanda tangan, sistem otomatis mencatat:
   - pengguna pengunggah
   - nama file dan lokasi file
   - waktu unggah tanda tangan
5. Tanggal tanda tangan pada Form 1100 diisi otomatis menggunakan waktu unggah tanda tangan tersebut.
6. Jika Partner mengganti file tanda tangan sebelum approval, sistem menyimpan versi sebelumnya pada `document_versions` dan mencatat aktivitasnya pada `activity_logs`.

### 14.5. Field Header yang Terisi Otomatis

| Field Form 1100 | Pengisi | Waktu Pengisian |
|---|---|---|
| Dibuat oleh | Auditor login | Saat draft dibuat pertama kali |
| Tanggal dibuat | Sistem | Saat draft dibuat pertama kali |
| Direview oleh | Manager login | Saat review selesai / diteruskan ke Partner |
| Tanggal review | Sistem | Saat review selesai / diteruskan ke Partner |
| Disetujui oleh | Partner login | Saat form di-approve |
| Tanggal approval | Sistem | Saat form di-approve |
| Tanda tangan | Partner | Saat Partner mengunggah file tanda tangan |
| Tanggal tanda tangan | Sistem | Saat file tanda tangan diunggah |

### 14.6. Status Form 1100

```text
Draft
  ↓ Auditor submit
Pending Review
  ├── Manager request revision → Revision Required → Auditor revisi → Pending Review
  └── Manager forward to Partner → Pending Approval
                                      ├── Partner request revision → Revision Required
                                      └── Partner approve → Approved (final/read-only)
```

## 15. Kesimpulan

Sistem Audit yang dibutuhkan adalah **sistem digital terintegrasi** yang mengubah cara kerja audit dari manual file-based menjadi form-based dengan:

1. ✓ **Digitalisasi lengkap** template Word/Excel → form sistem
2. ✓ **Workflow berjenjang** Auditor → Manager → Partner dengan approval bertingkat
3. ✓ **Role-based access** dengan 4 role: Admin, Auditor, Manager, Partner
4. ✓ **Assignment fleksibel** user ke engagement dan akun dengan role context
5. ✓ **Version control** dan audit trail untuk semua aktivitas
6. ✓ **Form relationships** agar data tidak duplikat dan terintegrasi
7. ✓ **Multi-periode** dengan kemampuan copy struktur dari periode sebelumnya
8. ✓ **Dynamic form fields** agar form bisa dikonfigurasi per klien atau audit type

**Sistem ini akan menghilangkan proses manual berbasis folder/Excel dan membuat audit lebih terstruktur, terdokumentasi, dan mudah dipantau.**
