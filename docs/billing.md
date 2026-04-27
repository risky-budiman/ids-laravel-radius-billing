# Penagihan & Invoice

> **Lokasi Menu:** `Billing`

Radius Billing mengintegrasikan modul akuntansi standar profesional untuk memastikan setiap transaksi keuangan (pendapatan, pengeluaran, mutasi kas) tercatat dengan benar dan tepat waktu.

## Siklus Billing Otomatis

Sistem akan melakukan hal berikut setiap bulannya secara otomatis:
1. **Generate Invoice**: Setiap tanggal yang ditentukan (due date), sistem akan membuat invoice baru untuk pelanggan aktif.
2. **Notifikasi**: Invoice akan dikirimkan melalui email/WA (jika modul aktif) kepada pelanggan.
3. **Grace Period**: Memberikan waktu tambahan (toleransi) bagi pelanggan untuk melakukan pembayaran.

## Manajemen Invoice

Anda dapat mengelola invoice melalui menu **Billing > Invoices**.

### 1. Pembayaran Manual
Jika pelanggan membayar secara tunai atau transfer manual:
- Cari invoice terkait.
- Klik tombol **Mark as Paid**.
- Pilih akun kas/bank tempat dana diterima.
- Sistem akan otomatis membuat jurnal akuntansi.

### 2. Pengaturan Pajak (PPN)
Sesuai dengan regulasi terbaru, Anda dapat mengaktifkan PPN secara:
- **Global**: Seluruh pelanggan dikenakan PPN (misal 11%).
- **Per-Pelanggan**: Hanya pelanggan tertentu (seperti korporat) yang dikenakan PPN.

## Status Invoice
- **Unpaid**: Tagihan baru yang belum dibayar.
- **Paid**: Tagihan sudah lunas.
- **Overdue**: Tagihan melewati jatuh tempo dan belum dibayar.
- **Cancelled**: Tagihan yang dibatalkan oleh admin.

---
*Catatan: Invoice yang sudah lunas tidak dapat diubah kembali untuk menjaga integritas data keuangan.*
