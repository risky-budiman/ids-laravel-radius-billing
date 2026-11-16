# Penagihan, Isolir & Piutang

> **Lokasi Menu:** `Tagihan & Status › Invoice`, `Piutang`, `Desain Invoice` · pengaturan: `Pengaturan & Keamanan › Pengaturan Isolir`, `Invoice & Portal`

## Siklus Penagihan Otomatis

Setiap hari pukul **00:01** server menjalankan `app:process-billing`:

1. **Terbitkan invoice** untuk pelanggan yang tanggal tagih berikutnya sudah tiba. Hanya pelanggan **Aktif** dan **Terisolir** yang ditagih; *menunggu dismantle*, *dicabut* dan *batal* tidak.
2. **Isolir** pelanggan yang invoice langganannya belum lunas melewati **masa tenggang** (lihat *Isolir*).

> Kedua langkah dijalankan lewat **antrean (queue)** — server produksi wajib menjalankan worker antrean (lihat `docs/deploy.md`). Tanpa worker, invoice dan isolir tidak diproses.

**Pengingat WhatsApp** dikirim pukul 09:00 sebelum jatuh tempo (bawaan **H-3, H-1 dan hari H**, diatur lewat setting `invoice_reminder_days`). Pengingat hanya dikirim bila template *invoice_reminder* aktif, dan sekali per hari pengingat per invoice.

## Status Invoice

| Status | Arti |
|---|---|
| **Belum lunas** (`unpaid`) | Tagihan berjalan; masuk Piutang & dasar isolir. |
| **Lunas** (`paid`) | Sudah dibayar (manual, payment gateway, atau mutasi bank otomatis). |
| **Dihapusbukukan** (`written_off`) | Piutang macet yang dihapus buku; keluar dari piutang, portal, pengingat & dasar isolir. |

Invoice lunas tidak bisa diubah. Nomor `INV-INST…` = invoice biaya instalasi, `INV-BOOST…` = pembelian booster/add-on.

## Mencatat Pembayaran

- **Manual** (tunai/transfer): buka invoice → **Bayar**, pilih rekening Kas & Bank penerima. Jurnal penerimaan dibuat otomatis.
- **Massal**: centang beberapa invoice → **Bayar**.
- **Payment gateway**: lunas otomatis lewat webhook.
- **Mutasi bank (Moota)**: transfer masuk dicocokkan otomatis ke invoice (nomor invoice di berita transfer, atau nominal yang unik). Yang ragu menunggu dicocokkan manual di `Keuangan › Mutasi Bank` — sistem tidak pernah menebak.

Pelanggan **terisolir** otomatis dibuka kembali saat invoice dilunasi **dan tidak ada tunggakan langganan lain yang lewat jatuh tempo** (grup paket dipulihkan & sesi diputus agar IP normal). Pelanggan yang sudah dicabut/batal tidak dihidupkan lagi oleh pelunasan; pembelian booster tidak mengubah status langganan.

## Isolir

> `Pengaturan & Keamanan › Pengaturan Isolir`

- Pelanggan terisolir dipindah ke **grup RADIUS ISOLIR** dan mendapat **IP isolir** (mode *rentang*: satu IP per pelanggan dari rentang yang Anda atur; mode *pool*: IP dari pool MikroTik). Halaman isolir menampilkan pemberitahuan tagihan.
- **Masa tenggang**: berapa hari setelah jatuh tempo pelanggan yang belum bayar diisolir.
- **Skrip MikroTik** dibuat otomatis dari data sistem (address list, redirect ke halaman isolir).
- **Pengajuan dismantle otomatis** (bawaan *mati*): pelanggan terisolir lebih dari N hari (bawaan 60) diubah menjadi *menunggu dismantle* — tagihan berhenti dan tiket penarikan perangkat dibuat.

## Biaya Instalasi

> `Pengaturan & Keamanan › Pengaturan Instalasi`

Setiap aktivasi dengan biaya instalasi menerbitkan **invoice instalasi**. Dibayar di lokasi (tunai/transfer) → langsung lunas. Belum dibayar → internet tetap aktif sampai **batas bayar**, lalu diisolir otomatis. Aktivasi juga membuat **Berita Acara (BA) Instalasi** bernomor yang ditandatangani pelanggan di HP teknisi atau lewat tautan WhatsApp.

## Piutang

> `Tagihan & Status › Piutang`

- **Umur piutang** per tanggal (belum jatuh tempo, 1–30, 31–60, 61–90, > 90 hari) dan **per pelanggan**, diurutkan dari yang paling lama menunggak.
- **Akan diisolir pada run billing berikutnya**: daftar pelanggan yang akan terisolir pukul 00:01.
- Pembelian booster yang belum dibayar tidak dihitung (itu checkout yang ditinggalkan, bukan tagihan).

### Hapus buku (write-off)

Administrator/admin dapat menghapusbukukan invoice macet (wajib alasan). Karena pendapatan baru diakui saat dibayar, write-off hanya membalik jurnal penagihan (Debit Pendapatan Ditangguhkan 2102 / Kredit Piutang 1104) — **laba rugi tidak berubah**. Bila pelanggan ternyata membayar, write-off dibatalkan otomatis lalu pembayaran dicatat seperti biasa.

## PPN

PPN dikenakan per pelanggan (centang *Kenakan PPN* di data pelanggan) atau global, diatur di `Keuangan › Pusat Pajak PPN`. Nomor & format invoice serta NPWP diatur di **Invoice & Portal**.

## Portal Pelanggan

Pelanggan melihat tagihan, membayar, membeli booster dan melapor gangguan di portal. Login portal memakai **kode OTP WhatsApp** (bila diaktifkan di Invoice & Portal): 6 digit, berlaku 5 menit, maksimal 5 kali salah, kirim ulang paling cepat 60 detik. Uji pengiriman OTP lewat gateway produksi sebelum mengaktifkannya.
