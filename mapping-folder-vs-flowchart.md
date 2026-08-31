# Mapping Folder vs Flowchart RBA

Struktur ini memetakan folder di `C:\KAP-MGN\audit_system\audit_system_backend\PT Indo American Seafoods Tbk` dengan kode form di flowchart.

## Fase 1: 1000 - Risk Assessment
| Folder | Kode Form | Nama Form |
| :--- | :--- | :--- |
| 1000 Risk Assesment | 1000 | Penilaian Risiko |
| ... | 1100 | Memo Penerimaan/Kelanjutan |
| ... | 1110 | Survey Klien |
| ... | 1130 | Evaluasi Independensi (1130A-D) |

## Fase 2: 2000 - Risk Response
| Folder | Kode Form | Nama Form |
| :--- | :--- | :--- |
| 2000 Risk Response | 2000 | Respons Risiko |
| ... | 2100 | Strategi Audit |
| ... | 2400 | Pemeriksaan IT |

## Fase 3: 3000 - Audit Evidence & Documentation
| Folder | Kode Form | Nama Form |
| :--- | :--- | :--- |
| 3000 Audit Evidence | 3000 | Audit Evidence |
| 3100 Balance Sheet | 3100 | Kas, Piutang, Persediaan, Aset Tetap, Utang |

## Fase 4: 4000 - Representation and Consultation
| Folder | Kode Form | Nama Form |
| :--- | :--- | :--- |
| 4000 Representation | 4000 | Representation & Consultation |
| ... | 4100 | Konsultasi |
| ... | 4200 | Surat Representasi |

## Fase 5: 5000 - Reporting
| Folder | Kode Form | Nama Form |
| :--- | :--- | :--- |
| 5000 Reporting | 5000 | Laporan Akhir |
| ... | 5100 | Laporan Arus Kas |
| ... | 5200 | CaLK |
| ... | 5903 | Finalisasi |

---

## Catatan Implementasi untuk Developer:
- **Folder sebagai modul**: Setiap folder utama (1000, 2000, dst) sebaiknya menjadi `module` atau `namespace` di sistem.
- **File sebagai Form**: Setiap file `.xlsx`/`.docx` di dalam sub-folder harus diubah menjadi `form_id` yang unik di database.
- **Mapping Path**: Gunakan mapping ini untuk generate menu navigasi aplikasi secara otomatis berdasarkan folder.
