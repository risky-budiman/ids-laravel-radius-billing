# Manajemen Pengguna & Hak Akses

> **Lokasi Menu:** `User Management`

Keamanan sistem sangat bergantung pada bagaimana Anda mengelola akun pengguna dan hak akses di dalam aplikasi.

## Peran Pengguna (Roles)

Sistem memiliki 3 tingkatan akses utama:

### 1. Admin / Administrator
- Memiliki akses penuh ke seluruh menu (termasuk Pengaturan dan Akuntansi).
- Dapat membuat, mengubah, atau menghapus data pengguna lain.
- Bertanggung jawab atas konfigurasi sistem.

### 2. Billing / Finance
- Memiliki akses ke menu Pelanggan, Tagihan, dan Akuntansi.
- Tidak dapat mengubah pengaturan sistem atau infrastruktur jaringan.
- Fokus pada pengelolaan invoice dan penerimaan pembayaran.

### 3. Teknisi (Technician)
- Memiliki akses ke menu Pelanggan (hanya untuk melihat lokasi dan data teknis).
- Memiliki akses penuh ke modul **Tiket & Support**.
- Tidak dapat melihat laporan keuangan atau data billing.

## Membuat Akun Staf Baru

Akses menu **User Management > Users > Create**.
- Gunakan alamat email aktif perusahaan.
- Pilih **Role** yang sesuai dengan tanggung jawab pekerjaan mereka.
- Pastikan staf mengganti password mereka secara berkala melalui menu Profile.

## Keamanan Data

- **Log Aktivitas**: Admin dapat memantau kapan setiap pengguna login dan apa saja yang mereka ubah melalui menu **System Logs**.
- **Blokir Akses**: Jika staf sudah tidak bekerja, segera non-aktifkan akun mereka daripada menghapusnya, agar riwayat transaksi yang mereka buat tetap terjaga.

---
*Peringatan: Jangan pernah membagikan akun Administrator utama kepada lebih dari satu orang demi alasan keamanan dan akuntabilitas.*
