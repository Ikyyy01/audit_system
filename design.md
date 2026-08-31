# Design System — Audit Management System (KAP MGN)

> **Catatan:** Palet inti (oranye & hijau) diambil dari logo resmi KAP MGN. Di bawah ini warna brand tersebut disaring jadi sistem yang aman dipakai di UI kerja sehari-hari — bukan ditempel mentah ke semua elemen, karena oranye solid di banyak tempat sekaligus akan melelahkan mata dan mengaburkan hierarki status.

---

## 1. Prinsip Desain

Sistem ini dipakai auditor & partner untuk kerja presisi — bukan produk konsumen yang butuh "wow factor". Tiga prinsip pegangan:

1. **Tenang, bukan ramai** — auditor buka sistem ini berjam-jam per hari. Warna & kontras harus nyaman di mata, bukan mencolok.
2. **Status selalu jelas** — di setiap layar (dokumen, progress, timesheet), warna status (pending/approved/overdue) harus konsisten dan bisa dikenali sekilas tanpa baca teks.
3. **Rapi seperti kertas kerja audit** — grid rapat, alignment presisi, spacing konsisten. Ini mencerminkan sifat pekerjaan itu sendiri: teliti dan terstruktur.

---

## 2. Warna

### Palet Inti

| Token | Hex | Kegunaan |
|---|---|---|
| `--ink-900` | `#2C3E50` | Warna gelap utama — header, sidebar, teks judul. Dipakai sebagai "berat" visual utama, bukan oranye, supaya kesan tetap tenang & profesional |
| `--orange-600` | `#D96B00` | Aksen brand — turunan dari oranye logo, sedikit diredam dari `#FF7F00` asli supaya nyaman dipakai sebagai warna tombol tanpa berlebihan mencolok di layar yang dibuka berjam-jam |
| `--green-700` | `#1F6B3A` | Aksen kedua brand — turunan dari hijau logo, dipakai untuk status "approved/selesai", diredam dari `#008000` asli agar tidak bentrok dengan status warna lain |
| `--paper` | `#F7F5F1` | Background utama — kesan kertas kerja, lebih hangat dari putih steril |
| `--surface` | `#F4F7F6` | Background kartu/panel di atas `--paper`, dari brief asli |

**Kenapa oranye & hijau logo diredam, bukan dipakai persis:** warna logo (`#FF7F00`, `#008000`) didesain untuk tampil kecil di atas putih (favicon, kop surat) — kalau dipakai solid di area luas (tombol besar, header) atau berdekatan satu sama lain, dua warna komplementer ini saling "berteriak" dan melelahkan mata untuk dipakai kerja berjam-jam. Versi yang diredam (`--orange-600`, `--green-700`) tetap terasa sebagai warna yang sama, tapi lebih tenang dipakai di UI.

### Warna Status (dipakai konsisten di semua modul)

| Token | Hex | Arti |
|---|---|---|
| `--status-pending` | `#B8935A` | Pending / belum direview |
| `--status-progress` | `#D96B00` | Sedang dikerjakan / on progress — pakai `--orange-600` brand, karena "sedang berjalan" adalah state paling sering dilihat & paling berkaitan dengan aksi brand |
| `--status-review` | `#5E7A94` | Menunggu review |
| `--status-approved` | `#1F6B3A` | Approved / completed — pakai `--green-700` brand, konsisten dengan makna hijau = selesai/aman di logo |
| `--status-overdue` | `#B0503F` | Terlambat / melewati deadline |

**Aturan pemakaian warna status:** selalu sama artinya di modul manapun — `--status-approved` yang hijau-zaitun ini dipakai baik di status dokumen, status engagement, maupun status timesheet. Jangan pakai warna berbeda untuk konsep status yang sama di modul berbeda.

---

## 3. Tipografi

| Peran | Font | Alasan |
|---|---|---|
| **Display / Judul** | Source Serif 4 | Serif memberi kesan formal & terpercaya — cocok untuk judul halaman, nama laporan, cover dokumen yang di-generate ke Word |
| **UI / Body** | Inter | Sans-serif netral, sangat legible di ukuran kecil — dipakai untuk semua teks interface: tabel, form, label, tombol |

**Type scale:**
- Judul halaman: 28px / 700 / Source Serif 4
- Judul section: 18px / 600 / Inter
- Body / isi tabel: 14px / 400 / Inter
- Label kecil (status, tanggal): 12px / 500 / Inter

**Yang dihindari:**
- Jangan pakai huruf kapital semua (ALL CAPS) untuk label — cukup sentence case, lebih mudah dibaca cepat oleh auditor yang scanning banyak baris data
- Jangan campur lebih dari 2 keluarga font

---

## 4. Layout

### Prinsip Grid

Sistem ini berbasis **tabel dan form**, bukan halaman marketing — jadi layout mengikuti disiplin kertas kerja:
- Konten utama rata kiri (left-aligned), bukan center — memudahkan mata scan baris demi baris seperti membaca spreadsheet
- Sidebar kiri tetap (fixed) untuk navigasi antar modul (Dokumen, Progress, Timesheet, AI Assistant)
- Lebar maksimum konten form: ~720px agar form panjang (seperti Form 5100 - Working Balance Sheet) tetap nyaman dibaca per baris

### Wireframe Konsep — Halaman Dashboard

```
┌──────────┬────────────────────────────────────────┐
│          │  Nama Klien · Engagement · Deadline     │
│ Sidebar  ├────────────────────────────────────────┤
│          │  ┌─────────┐ ┌─────────┐ ┌─────────┐   │
│ Dokumen  │  │ Progress│ │ Dokumen │ │Timesheet│   │
│ Progress │  │   75%   │ │  5/6    │ │ 108 jam │   │
│ Timesheet│  └─────────┘ └─────────┘ └─────────┘   │
│ AI Chat  │                                         │
│          │  Tabel Engagement Aktif                 │
│          │  ────────────────────────────────────   │
│          │  Klien       Status      Deadline        │
│          │  PT A        On Progress  31 Agu          │
│          │  PT B        Review       03 Sep          │
└──────────┴────────────────────────────────────────┘
```

### Wireframe Konsep — Form Digital (misal Form 1100)

```
┌────────────────────────────────────────┐
│  Form 1100 — Survey Klien               │
│  ────────────────────────────────────   │
│  Nama Klien        [________________]   │
│  Periode Audit     [________________]   │
│  Bidang Usaha      [________________]   │
│                                          │
│  Checklist Independensi                 │
│  ☐ Tidak ada hubungan keluarga          │
│  ☐ Tidak ada kepemilikan saham          │
│                                          │
│              [ Simpan ]  [ Generate Word ]│
└────────────────────────────────────────┘
```

Alignment form: label rata kiri, input sejajar mengikuti kolom yang sama — konsisten dari form ke form, karena user akan mengisi puluhan form berbeda dan harus terasa seperti "sistem yang sama", bukan form yang desainnya beda-beda tiap halaman.

---

## 5. Komponen Kunci

- **Header** — background `--ink-900` (gelap), teks putih, logo KAP MGN diletakkan di sudut kiri atas pada setiap layout utama (Dashboard, Login, dll). Header gelap dipilih daripada oranye/hijau brand supaya warna brand tetap terasa istimewa saat muncul di tombol/status, bukan tercampur jadi warna latar yang dilihat terus-menerus
- **Tombol utama (primary action)** — `--orange-600`, dipakai untuk aksi utama per layar (submit, simpan, generate). Tombol sekunder (batal, kembali) tetap netral (outline/abu-abu) supaya oranye tidak bersaing dengan dirinya sendiri dalam satu layar
- **Status badge** — pill kecil dengan warna dari tabel status di atas, teks singkat (bukan ikon saja, karena warna + teks lebih aman untuk aksesibilitas)
- **Kartu ringkasan (summary card)** — background putih dengan shadow lembut (`0 2px 6px rgba(0,0,0,0.05)`), dipakai di dashboard untuk angka besar (progress %, jumlah dokumen, total jam) — 1 angka besar + 1 label kecil, tanpa dekorasi berlebih
- **Tabel** — garis pemisah tipis (hairline), bukan card bervolume per baris — supaya terasa seperti kertas kerja, bukan aplikasi konsumen
- **Tombol generate dokumen (Word)** — selalu pakai `--orange-600`, satu-satunya aksi selain tombol utama form yang boleh pakai warna brand, supaya aksi "menghasilkan dokumen resmi" terasa berbeda dari aksi biasa (simpan, batal)

---

## 6. Aksesibilitas & Konsistensi

- Kontras teks minimum 4.5:1 terhadap background (`--ink-900` di atas `--paper` sudah memenuhi ini)
- Status tidak boleh mengandalkan warna saja — selalu sertai label teks (misal "Approved", bukan cuma titik hijau)
- Semua interaktif (tombol, link, input) punya focus state yang terlihat jelas saat navigasi keyboard

---

**Ringkasan perubahan dari draft awal:** warna inti sistem sekarang diturunkan langsung dari logo KAP MGN (oranye `#FF7F00` → diredam jadi `--orange-600`, hijau `#008000` → diredam jadi `--green-700`), dengan `--ink-900` sebagai warna gelap utama di header/sidebar supaya kedua warna brand tetap terasa istimewa saat dipakai di tombol dan status, bukan tercampur jadi warna latar yang dilihat terus-menerus sepanjang hari kerja.