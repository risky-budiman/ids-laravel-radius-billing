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

## Laporan

Laba Rugi, Neraca, **Arus Kas** (metode langsung dengan klasifikasi per akun lawan), Buku Besar, Pusat Pajak PPN — lihat `docs/reports.md`.

## Tutup Buku

Transaksi sebelum tanggal tutup buku tidak bisa ditambah, diubah atau dihapus oleh siapa pun.

---
*Penting: isi saldo awal Kas & Bank dengan benar saat pertama memakai sistem.*
