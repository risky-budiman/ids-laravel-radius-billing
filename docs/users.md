# Manajemen Pengguna & Hak Akses

> **Lokasi Menu:** `Sistem > Pengguna` (khusus **Administrator**)

Keamanan sistem bergantung pada pengelolaan akun staf dan hak aksesnya.

## Peran Pengguna (Roles)

| Role | Ringkasan akses |
|---|---|
| **Administrator** | Akses penuh: pengaturan aplikasi, pengguna, NAS, log, NOC & OLT (termasuk tambah/ubah/hapus OLT). |
| **Admin** | Operasional & keuangan: pelanggan, tagihan, keuangan, impor/ekspor pelanggan, menyetujui pengajuan ganti paket. Tidak bisa mengubah pengaturan aplikasi & akun staf. |
| **Teknisi** | Aktivasi (wizard), dismantle, tiket, NOC & pemantauan OLT (tanpa mengubah konfigurasi OLT). |
| **Kasir** | Penerimaan pembayaran & setoran kas. |
| **Sales** | Mendaftarkan pelanggan baru. |
| **Customer Service (CS)** | Tiket, ubah kontak pelanggan, melihat tagihan & status; ganti paket hanya lewat **pengajuan** yang disetujui admin. |
| **Partner / Mitra** | Data komisi mitra. *(Portal mitra masih dalam pengerjaan.)* |

Dasbor, data keuangan dan Log Aktivitas hanya tampil untuk role yang berhak (lihat *Dasbor*).

## Membuat & Mengubah Akun Staf

Buka **Pengguna > Tambah**:
- Gunakan email aktif; password minimal **8 karakter** (berlaku juga untuk API aplikasi staf).
- Pilih role sesuai tanggung jawab. Role yang tersedia sama di web dan API.

Aturan saat mengubah akun:
- **Mengedit administrator lain tidak mengubah role-nya** kecuali Anda memilih role lain (dulu tersimpan sebagai Admin).
- **Role akun sendiri tidak bisa diubah**, dan akun sendiri tidak bisa dinonaktifkan atau dihapus.
- **Administrator aktif terakhir** tidak bisa diturunkan, dinonaktifkan atau dihapus.
- Perubahan role tercatat di **Log Aktivitas** (`user_role_changed`).

## Keamanan Data

- **Log Aktivitas**: administrator memantau perubahan penting (keuangan, pengaturan, OLT, role) di menu **Log Aktivitas**.
- **Blokir akses**: bila staf berhenti bekerja, **nonaktifkan** akunnya daripada menghapus, agar riwayat transaksi tetap terjaga. Tombol **Reset sesi** mengeluarkan akun dari semua perangkat.

---
*Peringatan: jangan membagikan satu akun Administrator ke lebih dari satu orang — buat akun terpisah agar setiap perubahan bisa ditelusuri.*
