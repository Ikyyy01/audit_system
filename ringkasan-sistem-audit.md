# Ringkasan Pembahasan Sistem Audit

## 1. Sistem Manajemen Dokumen Audit
Seluruh file pada folder `C:\KAP-MGN\audit_system\audit_system_backend\PT Indo American Seafoods Tbk` dijadikan sistem digital.

File Word dan Excel yang selama ini dipakai untuk kerja audit diubah menjadi form di dalam sistem, sehingga proses audit bisa berjalan full system dari input, review, approval, sampai arsip dokumen.

## 2. Role dalam Sistem
Ada 4 role:

- **Admin**: kelola user, data, dan pengaturan sistem
- **Auditor**: input, upload, dan kerjakan form/dokumen audit
- **Manajer**: review, koreksi, dan approval awal
- **Partner**: approval final dan pemantauan keseluruhan

## 3. Konsep Utama Sistem
Sistem terpusat untuk upload, review, dan approval dokumen kertas kerja audit, lengkap dengan version control dan audit trail.

Tujuannya menggantikan proses manual berbasis folder/Excel yang rawan hilang atau tidak terlacak.

Fitur utama:

- Upload dokumen klien
- Kategori dokumen berdasarkan klien dan periode audit
- Status dokumen: pending, reviewed, approved
- Hak akses berdasarkan role
- Riwayat aktivitas dokumen
- Version control dokumen
- Review dan approval berjenjang

## 4. Contoh Folder 3100 Balance Sheet
Folder yang dicek:

`C:\KAP-MGN\audit_system\audit_system_backend\PT Indo American Seafoods Tbk\3000 Audit Evidence and Documentation\3100 Balance Sheet`

Isi folder berupa file Excel audit:

- A. Cash and Cash Equivalents PT IAS Tbk dan Entitas Anak 31 Desember 2024.xlsx
- B. Piutang Usaha PT IAS Tbk dan Entitas Anak_2024.xlsx
- C. Piutang Lain lain PT IAS Tbk dan Entitas Anak 31 Desember 2024.xlsx
- D. Persediaan PT IAS Tbk 2024.xlsx
- E. Biaya Dibayar Dimuka dan Uang Muka_2024.xlsx
- F. Aset Tetap PT IAS Tbk _2024.xlsx
- G. Utang Usaha PT IAS Tbk _2024.xlsx
- G1. Aset Hak Guna PT IAS Tbk 2024.xlsx
- H.Perpajakan PT IAS Tbk 2024.xlsx
- I. Utang Bank PT IAS Tbk  2024.xlsx
- K. Utang Pembelian Aset Tetap2024.xls
- P.Modal PT IAS Tbk dan Entitas Anak.xlsx

## 5. Kesimpulan Folder 3100 Balance Sheet
Folder tersebut sangat memungkinkan dijadikan full system.

Alasannya:

- Struktur file sudah jelas berdasarkan akun laporan keuangan
- Tiap file Excel bisa dijadikan satu form digital
- Data angka, checklist, dan catatan audit bisa diinput langsung dari sistem
- Review dan approval bisa dibuat berdasarkan role
- Hasil kerja tersimpan rapi per klien, periode audit, dan akun

## 6. Konsep Full System
Konsep perubahan dari file manual ke sistem:

- Tiap file Word/Excel menjadi form digital
- Tiap akun audit menjadi modul/form tersendiri
- Auditor mengisi form langsung di sistem
- Manajer melakukan review di sistem
- Partner melakukan approval final di sistem
- Semua aktivitas tercatat dalam audit trail
- Perubahan data tersimpan sebagai version history

Contoh mapping:

| File Lama | Bentuk di Sistem |
|---|---|
| Excel Cash and Cash Equivalents | Form Kas dan Setara Kas |
| Excel Piutang Usaha | Form Piutang Usaha |
| Excel Persediaan | Form Persediaan |
| Excel Aset Tetap | Form Aset Tetap |
| Excel Utang Usaha | Form Utang Usaha |
| Excel Perpajakan | Form Perpajakan |

## 7. Workflow Sistem
Alur kerja sistem:

```text
Auditor
  ↓
Input / Upload / Submit Form Audit
  ↓
Manajer Review
  ↓
Jika salah: Return ke Auditor untuk revisi
Jika benar: Approve ke Partner
  ↓
Partner Final Approval
  ↓
Dokumen Approved dan tersimpan sebagai arsip audit
```

## 8. Status Dokumen/Form
Status yang digunakan:

- **Draft**: masih dikerjakan auditor
- **Pending Review**: sudah disubmit ke manajer
- **Reviewed**: sudah direview manajer
- **Revision Required**: perlu revisi auditor
- **Approved**: sudah disetujui final

## 9. Hak Akses Role
| Fitur | Admin | Auditor | Manajer | Partner |
|---|---|---|---|---|
| Kelola user | Ya | Tidak | Tidak | Tidak |
| Kelola master data | Ya | Tidak | Terbatas | Terbatas |
| Input form audit | Ya | Ya | Ya | Terbatas |
| Upload dokumen | Ya | Ya | Ya | Terbatas |
| Submit pekerjaan | Ya | Ya | Ya | Tidak |
| Review pekerjaan | Ya | Tidak | Ya | Ya |
| Request revisi | Ya | Tidak | Ya | Ya |
| Approval final | Ya | Tidak | Tidak | Ya |
| Lihat audit trail | Ya | Terbatas | Ya | Ya |

## 10. Inti untuk PPT
Kalimat singkat untuk PPT:

Sistem Manajemen Dokumen Audit adalah sistem digital untuk mengubah file kerja audit berbasis Word dan Excel menjadi form audit terintegrasi. Sistem ini mendukung proses input, upload, review, approval, version control, dan audit trail berdasarkan role Admin, Auditor, Manajer, dan Partner.

Dengan sistem ini, seluruh pekerjaan audit dapat dilakukan secara full system, lebih rapi, terdokumentasi, dan mudah dipantau per klien serta periode audit.
