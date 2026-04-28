# ISP Billing Chart of Accounts (COA) - Production Ready

## Overview
Dokumen ini adalah standar Chart of Accounts (COA) untuk sistem ISP berbasis:
- Laravel Billing
- FreeRADIUS
- MikroTik

Dirancang untuk:
- Mencegah double charge
- Sinkronisasi billing & jaringan
- Laporan keuangan akurat
- Siap production ISP

---

## ASET (1000)

### Kas & Bank
- 1001 Kas
- 1101 Kas Tunai
- 1102 Bank BCA
- 1103 Bank Mandiri

### Piutang & Pajak
- 1104 Piutang Pelanggan (WAJIB)
- 1105 Persediaan Barang
- 1106 PPN Masukan

### Aset Tetap
- 1201 Inventaris Kantor
- 1202 Peralatan Jaringan (OLT/Router)

---

## KEWAJIBAN (2000)

- 2101 Hutang Vendor
- 2102 Uang Muka Pelanggan (WAJIB)
- 2103 Hutang Pajak (PPN)

---

## EKUITAS (3000)

- 3100 Modal Pemilik
- 3200 Laba Ditahan

---

## PENDAPATAN (4000)

- 4101 Pendapatan Internet Bulanan (CORE BISNIS)
- 4102 Pendapatan Instalasi
- 4103 Pendapatan Voucher / Hotspot
- 4104 Pendapatan Denda Keterlambatan
- 4105 Diskon Penjualan (Contra Income)
- 4199 Pendapatan Lain-lain

---

## BEBAN (5000)

### Operasional Utama
- 5101 Beban Bandwidth (WAJIB)
- 5102 Beban Gaji
- 5103 Beban Listrik
- 5104 Beban Sewa

### Operasional Lapangan
- 5105 Beban Operasional Lapangan
- 5106 Beban Maintenance Jaringan (WAJIB)

### Biaya Bank & Payment
- 5107 Beban Admin Bank
- 5108 Beban Transfer Bank
- 5109 Beban Payment Gateway

### Akuntansi
- 5201 Beban Penyusutan

---

## FLOW AKUNTANSI ISP

### 1. Saat Invoice dibuat
Debit:
- Piutang Pelanggan

Kredit:
- Pendapatan Internet

---

### 2. Saat pelanggan bayar
Debit:
- Bank

Kredit:
- Piutang Pelanggan

---

### 3. Jika ada uang muka (deposit)
Debit:
- Bank

Kredit:
- Uang Muka Pelanggan

---

### 4. Saat pemakaian deposit
Debit:
- Uang Muka Pelanggan

Kredit:
- Pendapatan

---

## VALIDASI SISTEM

Pastikan:
- Tidak semua pendapatan masuk ke "Pendapatan Lain-lain"
- Beban bandwidth selalu tercatat
- Semua pembayaran masuk ke akun bank
- Piutang digunakan untuk tracking billing

---

## CATATAN PENTING

- Piutang Pelanggan = inti bisnis ISP
- Bandwidth = biaya terbesar
- Logging & audit trail wajib aktif
- Jangan gabungkan semua transaksi dalam satu akun

---

## STATUS

✔ Siap digunakan untuk:
- ISP kecil - menengah
- Integrasi Laravel + FreeRADIUS + MikroTik
- Production billing system

