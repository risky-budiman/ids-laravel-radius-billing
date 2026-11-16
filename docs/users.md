# Manajemen Pengguna & Hak Akses

> **Lokasi Menu:** `Sistem > Pengguna` (khusus **Administrator**)

Keamanan sistem bergantung pada pengelolaan akun staf dan hak aksesnya.

## Peran Pengguna (Roles)

| Role | Ringkasan akses |
|---|---|
| **Administrator** | Akses penuh: pengaturan aplikasi, pengguna, NAS, log, NOC & OLT (termasuk tambah/ubah/hapus OLT). |
| **Admin** | Operasional & keuangan: pelanggan, tagihan, keuangan, impor/ekspor pelanggan, menyetujui pengajuan ganti paket. Tidak bisa mengubah pengaturan aplikasi & akun staf. |
| **Teknisi** | Aktivasi (wizard), dismantle, tiket, NOC & pemantauan OLT (tanpa mengubah konfigurasi OLT). Di data pelanggan hanya mengubah kontak & wilayah/STO/STB; ganti paket lewat pengajuan. |
| **Kasir** | Penerimaan pembayaran & setoran kas. |
| **Sales** | Mendaftarkan pelanggan baru. Di data pelanggan hanya mengubah kontak; ganti paket lewat pengajuan; tidak melihat password PPPoE. |
| **Customer Service (CS)** | Tiket, ubah kontak pelanggan, melihat tagihan & status; ganti paket hanya lewat **pengajuan** yang disetujui admin. |
| **Partner / Mitra** | Hanya halaman mitranya sendiri: pelanggan yang dibawa, komisi, saldo & pengajuan penarikan, serta profil. |

Dasbor, data keuangan dan Log Aktivitas hanya tampil untuk role yang berhak (lihat *Dasbor*).

### Hak Akses per Role (rincian)

Menu **Pengaturan & Keamanan › Hak Akses per Role** (khusus administrator) menampilkan tabel fitur × role: **Penuh** (semua halaman & aksi), **Lihat** (hanya melihat), **Sebagian** (klik fitur untuk melihat aksi mana saja yang boleh), atau **—**. Tabel ini dibaca langsung dari aturan yang ditegakkan aplikasi, jadi selalu sesuai kenyataan.

Menu di sidebar dan tombol di halaman (mis. *Paket Baru*, *Ubah*, *Aksi Massal*, *Catat Pemasukan*) mengikuti aturan yang sama: role yang tidak berhak tidak lagi melihat tombol yang ujungnya "403 Unauthorized". Aturan akses tidak bisa diubah dari layar agar tidak ada celah tidak sengaja; perubahan dilakukan oleh pengembang dan langsung terlihat di tabel ini.

## Akun Mitra (Partner / Reseller)

Admin membuat akun mitra di **Manajemen > Mitra & Reseller** (menu muncul bila modul mitra diaktifkan di pengaturan), lengkap dengan besaran komisi (persen atau nominal tetap) dan rekening bank.

Setelah login, mitra langsung masuk ke **halaman mitranya** (bukan dasbor staf):
- Daftar pelanggan yang terhubung ke mitra tersebut dan riwayat komisi.
- Saldo komisi; tombol **Ajukan Penarikan** (minimal Rp 10.000, maksimal sebesar saldo).
- **Riwayat penarikan** dan **Profil** (ganti password).

Mitra tidak bisa membuka halaman mitra lain, daftar pelanggan, tagihan maupun menu staf lainnya. Pengajuan penarikan diproses admin (setujui, tandai dibayar dari rekening kas/bank, atau tolak).

## Membuat & Mengubah Akun Staf

Buka **Pengguna > Tambah**:
- Gunakan email aktif; password minimal **8 karakter** (berlaku juga untuk API aplikasi staf).
- Pilih role sesuai tanggung jawab. Role yang tersedia sama di web dan API.

Aturan saat mengubah akun:
- **Mengedit administrator lain tidak mengubah role-nya** kecuali Anda memilih role lain (dulu tersimpan sebagai Admin).
- **Role akun sendiri tidak bisa diubah**, dan akun sendiri tidak bisa dinonaktifkan atau dihapus.
- **Administrator aktif terakhir** tidak bisa diturunkan, dinonaktifkan atau dihapus.
- Perubahan role tercatat di **Log Aktivitas** (`user_role_changed`).

### Penanda "Juga Sales"

Sakelar **Aktifkan Fungsi Sales** memberi staf internal (administrator, admin, teknisi, kasir, CS) akses sales tambahan: dashboard sales, tambah pelanggan, dan bisa dipilih sebagai referal yang mendapat komisi. Akun seperti ini ditandai label **+ Sales** di daftar staf. Penanda diabaikan untuk role Sales (sudah otomatis) dan **Mitra**.

### Menghapus Akun

Akun yang **sudah punya riwayat** — tiket, balasan tiket, komisi, jurnal, transaksi kas, mutasi gudang, pelanggan referal, Log Aktivitas, dll. — **tidak bisa dihapus**. Sistem menampilkan riwayat yang ditemukan; **nonaktifkan** akun tersebut. Hanya akun yang belum pernah dipakai (mis. salah buat) yang bisa dihapus, dan penghapusannya tercatat (`user_deleted`).

## Keamanan Data

- **Log Aktivitas**: administrator memantau perubahan penting (keuangan, pengaturan, OLT, role) di menu **Log Aktivitas**.
- **Blokir akses**: bila staf berhenti bekerja, **nonaktifkan** akunnya daripada menghapus, agar riwayat transaksi tetap terjaga. Tombol **Reset sesi** mengeluarkan akun dari semua perangkat.

---
*Peringatan: jangan membagikan satu akun Administrator ke lebih dari satu orang — buat akun terpisah agar setiap perubahan bisa ditelusuri.*
