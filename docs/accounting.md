# Akuntansi & Keuangan

> **Lokasi Menu:** `Keuangan & Akuntansi` — Kas & Bank, Rekening Koran, Mutasi Bank, KPI Bisnis, Bagan Akun (COA), Laporan Keuangan, Komisi Sales

## Bagan Akun (COA)

Tipe akun: **Aset** (Kas & Bank 1001…, Piutang Pelanggan 1104, Persediaan 1105, PPN Masukan 1106), **Kewajiban** (Pendapatan Ditangguhkan 2102, hutang, PPN Keluaran), **Ekuitas**, **Pendapatan**, **Beban** (mis. material terpasang 5105). Menambah akun hanya administrator.

## Kapan Pendapatan Diakui

Sistem memakai **basis kas untuk pendapatan**:

| Kejadian | Jurnal otomatis |
|---|---|
| Invoice terbit | Dr Piutang Pelanggan (1104) / Cr Pendapatan Ditangguhkan (2102) |
| Invoice dibayar | Dr Kas/Bank / Cr Piutang; dan Dr Pendapatan Ditangguhkan / Cr Pendapatan (+ PPN Keluaran) |
| Piutang dihapus buku | Dr Pendapatan Ditangguhkan (2102) / Cr Piutang (1104) — laba rugi tidak berubah |

Jadi Laba Rugi hanya memuat tagihan yang benar-benar dibayar.

## Jurnal Otomatis Lainnya

- **Barang masuk** (dibayar dari rekening / hutang supplier), **pemasangan modem & material**, **penyusutan aset tetap** — lihat `docs/inventory.md`.
- **Transaksi Kas & Bank**: pemasukan, pengeluaran, transfer antar rekening dan **setoran kasir**.
- **Komisi sales/mitra** & penarikannya.

Jurnal manual dibuat di **Jurnal Umum** untuk transaksi lain (penyesuaian, biaya yang tidak lewat Kas & Bank).

## Kas & Bank

- **Kas & Bank**: saldo per rekening; catat pemasukan, pengeluaran & transfer. Kasir menyetor kas yang diterima lewat **Setoran Kasir**.
- **Rekening Koran**: mutasi & saldo berjalan per rekening (ekspor Excel).
- **Mutasi Bank (Moota)**: transfer masuk dari bank dicocokkan ke invoice — nomor invoice di berita transfer, atau nominal yang unik, atau nominal sama + kode/nama pelanggan yang hanya cocok ke satu invoice. Bila ragu, mutasi **menunggu dicocokkan manual**; setiap mutasi hanya dipakai sekali.

### Rekonsiliasi Bank

> **Lokasi:** `Keuangan › Rekonsiliasi Bank` (administrator & admin)

Mencocokkan mutasi rekening dari bank dengan catatan Kas & Bank, untuk menemukan transaksi yang belum dicatat, salah catat, atau tercatat padahal tidak terjadi.

1. Pilih rekening, unggah **CSV / Excel mutasi** dari internet banking (bank apa pun).
2. **Petakan kolom**: tanggal, keterangan, nominal (satu kolom bertanda +/− atau `CR`/`DB` seperti BCA, atau kolom **Debit & Kredit** terpisah seperti Mandiri), saldo (opsional), format tanggal, tahun (untuk tanggal tanpa tahun). Kolom ditebak otomatis dari judulnya dan **diingat per rekening** untuk import berikutnya. Angka format Indonesia (`1.234.567,00`) dan internasional (`1,234,567.00`) sama-sama terbaca; baris judul, saldo awal & total dilewati. Pratinjau hasil baca tampil sebelum import.
3. Baris yang **sudah pernah diimport dilewati** (aman mengimport file yang tumpang tindih).
4. **Cocok otomatis** 1:1: arah (masuk/keluar) & nominal sama, tanggal selisih paling banyak **3 hari**, yang terdekat dipilih.
5. Halaman rekonsiliasi per periode:
   - **saldo menurut bank** (dari kolom saldo) vs **saldo menurut aplikasi**, dan **selisih saldo akhir**;
   - **Ada di bank, belum tercatat di aplikasi** — *Catat* (form pemasukan/pengeluaran terisi dari mutasi; setelah disimpan klik *Cocokkan otomatis lagi*), *Cocokkan* manual dengan transaksi yang nominal/tanggalnya berbeda, atau *Abaikan* dengan alasan (mis. biaya admin);
   - **Tercatat di aplikasi, tidak ada di mutasi bank** — kemungkinan salah rekening/nominal/tanggal atau tercatat dua kali; periksa di Kas & Bank;
   - daftar yang sudah cocok / diabaikan (bisa dibatalkan) dan riwayat import (bisa dihapus; transaksi Kas & Bank tidak ikut terhapus).

Import, cocok manual & abaikan tercatat di Log Aktivitas. Rekening Koran menautkan ke halaman ini bila saldo tersimpan berbeda dari mutasinya.

## Laporan

Laba Rugi, Neraca, **Arus Kas** (metode langsung dengan klasifikasi per akun lawan), Buku Besar, Pusat Pajak PPN — lihat `docs/reports.md`.

## Tutup Buku

Transaksi sebelum tanggal tutup buku tidak bisa ditambah, diubah atau dihapus oleh siapa pun.

---
*Penting: isi saldo awal Kas & Bank dengan benar saat pertama memakai sistem.*
