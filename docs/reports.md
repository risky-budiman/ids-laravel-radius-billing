# Laporan & KPI

> **Lokasi Menu:** `Keuangan & Akuntansi › KPI Bisnis`, `Laporan Keuangan` (Jurnal Umum, Laba Rugi, Neraca, Arus Kas, Pusat Pajak PPN, Tutup Buku), `Rekening Koran` · `Tagihan & Status › Piutang` · `Manajemen › Rekap Tiket`

## KPI Bisnis (administrator & admin)

| Ukuran | Definisi |
|---|---|
| **MRR** | Jumlah harga paket bulanan (setelah diskon, tanpa PPN) pelanggan yang berlangganan di akhir bulan. Memakai harga paket saat ini. |
| **ARPU** | MRR ÷ jumlah pelanggan berlangganan di akhir bulan. |
| **Churn** | Pelanggan berhenti dalam bulan ÷ pelanggan berlangganan di awal bulan. |
| **Ditagih / Diterima** | Invoice langganan (tanpa booster & instalasi), tanpa PPN; ditagih menurut tanggal terbit, diterima menurut tanggal lunas. |

Pelanggan dihitung berlangganan sejak tanggal aktivasi sampai tanggal berhenti; pelanggan terisolir tetap dihitung karena tetap ditagih. Grafik *MRR per bulan* dan *Pelanggan baru vs berhenti* disertai rincian per bulan.

## Laporan Keuangan

- **Laba Rugi**: pendapatan (diakui saat invoice dibayar) dikurangi beban.
- **Neraca**: Aset = Kewajiban + Ekuitas pada tanggal tertentu.
- **Arus Kas** (metode langsung): dihitung dari jurnal akun Kas & Bank (1001 dan turunannya). Setiap penerimaan/pengeluaran diklasifikasikan menurut **akun lawannya**: pendapatan → operasional masuk, beban → operasional keluar, ekuitas/kewajiban → pendanaan. Klasifikasi per kode akun bisa diatur di **Pengaturan klasifikasi** pada laporan; yang tidak masuk mana pun tampil sebagai *lainnya* agar saldo tetap cocok. Penerimaan dari pelanggan termasuk aktivitas operasional.
- **Pusat Pajak PPN**: PPN keluaran (dari pelanggan) & masukan (pembelian) per periode, sebagai dasar SPT Masa PPN.
- **Rekening Koran**: mutasi dan saldo per rekening Kas & Bank.
- **Jurnal Umum / Buku Besar**: semua jurnal otomatis & manual, bisa difilter per akun.

**Ekspor Excel** tersedia untuk KPI Bisnis, Buku Besar, Arus Kas, Rekening Koran dan Rekap Tiket (tombol di halaman masing-masing).

## Piutang

Umur piutang & daftar yang akan diisolir — lihat `docs/billing.md` › *Piutang*.

## Rekap Tiket

Kinerja petugas & SLA — lihat `docs/ticketing.md` › *Rekap Tiket*.
