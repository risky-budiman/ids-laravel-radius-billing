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

### Hak Akses per Role (bisa diatur administrator)

Menu **Pengaturan & Keamanan › Hak Akses per Role** (khusus administrator) menampilkan tabel fitur × role: **Penuh** (semua halaman & aksi), **Lihat** (hanya halaman baca), **Sebagian**, atau **—**.

**Mengubah akses:**
- Klik sel fitur sebuah role lalu pilih **Penuh**, **Lihat saja** (halaman baca; form tambah/ubah & simpan ditolak) atau **Tidak ada**.
- Klik nama fitur untuk membuka rincian, lalu atur **per aksi** (✓ Boleh / · Tidak).
- Sel yang diubah dari bawaan diberi **titik biru**; pilih **Kembalikan ke bawaan** di sel itu, atau **Kembalikan semua ke bawaan** di kanan atas.

Perubahan langsung berlaku untuk menu sidebar, tombol di halaman dan pemeriksaan di server (pengguna terkait cukup memuat ulang halaman). Setiap perubahan tercatat di Log Aktivitas (`role_permissions_updated`).

**Pengaman:** role **Administrator** selalu penuh; fitur bertanda 🔒 — **Kelola Staf, Hak Akses, Backup & Restore, Pembaruan sistem** — selalu khusus administrator dan tidak bisa diberikan ke role lain. Beberapa aksi punya pemeriksaan tambahan di dalam halaman (mis. kolom pelanggan khusus administrator), dan aplikasi mobile (API) memakai aturan bawaan.

## Panduan Singkat per Role

**Administrator** — pemilik sistem
- Harian: cek Dashboard, Piutang (*akan diisolir*), Rekap Tiket, peringatan NOC.
- Mengatur staf & hak akses, Pengaturan Aplikasi/Isolir/Instalasi/Invoice & Portal, Integrasi (gateway, WhatsApp, GenieACS, Bot NOC), OLT (tambah/ubah/hapus), Backup.
- Menyetujui pengajuan ganti paket, hapus buku piutang macet, tutup buku.

**Admin** — operasional & keuangan
- Daftarkan & ubah pelanggan, invoice & pembayaran (manual/massal), cocokkan Mutasi Bank yang menunggu, impor/ekspor pelanggan.
- Kas & Bank, jurnal, laporan keuangan, KPI; menyetujui pengajuan ganti paket.

**Teknisi** — lapangan & jaringan
- Ambil tiket (*Tiket*), jalankan **wizard aktivasi** (pilih ONU dari OLT, modem & material, BA instalasi) dan **dismantle**.
- Pantau NOC, OLT (port, ONU, *Pekerjaan di OLT*, allow/reboot ONU HSGQ) dan modem di GenieACS (sinkron, Wi-Fi, PPPoE).
- Di data pelanggan hanya mengubah kontak & lokasi; ganti paket lewat pengajuan.

**Kasir** — penerimaan uang
- Terima pembayaran invoice, lalu **Setoran Kasir** ke rekening; lihat Piutang.

**Sales** — pendaftaran
- Daftarkan pelanggan baru, pantau *Referal Saya* dan *Dompet & Mutasi* komisi.

**Customer Service (CS)** — layanan pelanggan
- Tiket & keluhan, ubah kontak pelanggan, lihat tagihan & status, ajukan ganti paket (disetujui admin).

**Mitra** — hanya halaman mitranya (pelanggan yang dibawa, komisi, penarikan).

Rincian akses tiap halaman & aksi: **Hak Akses per Role**.

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

## Verifikasi Dua Langkah (2FA)

Selain password, masuk memerlukan **kode 6 digit** dari aplikasi authenticator di HP (Google Authenticator, Authy, Microsoft Authenticator). Akun tetap aman walau password bocor.

**Mengaktifkan (setiap staf, di Profil › Verifikasi dua langkah):**
1. Klik **Aktifkan verifikasi dua langkah**, lalu pindai **QR code** dengan aplikasi authenticator (atau ketik kode manualnya).
2. Masukkan kode 6 digit dari aplikasi → **Aktifkan**.
3. Simpan **8 kode cadangan** yang tampil (sekali saja). Masing-masing berlaku sekali untuk masuk bila HP hilang. Kode cadangan baru bisa dibuat kapan saja (dengan password); kode lama tidak berlaku lagi.

**Saat masuk:** setelah password benar, halaman meminta kode 6 digit (atau *HP hilang? Pakai kode cadangan*). Akun belum dianggap masuk sebelum kode benar. Kode berlaku ±30 detik dan **tidak bisa dipakai dua kali**; 5 kode salah → tunggu 1 menit; halaman kode berlaku 10 menit. Kode salah & pemakaian kode cadangan tercatat di Log Aktivitas.

**Administrator (Kelola Staf):**
- **Wajib untuk role** — centang role yang wajib 2FA (bawaan: tidak ada). Staf role itu yang belum mengaktifkan hanya bisa membuka **Profil** sampai mengaktifkannya, dan tidak bisa menonaktifkannya sendiri.
- Lencana **2FA** / **2FA belum** di daftar staf; tombol **2FA** mereset verifikasi staf yang kehilangan HP & kode cadangan (staf itu lalu mengaktifkan ulang).

**Aplikasi mobile staf:** login API (`POST /api/v1/admin/login`) untuk akun ber-2FA menjawab **202** `{"two_factor_required": true}` bila belum mengirim kode; kirim ulang dengan field `code` (kode 6 digit atau kode cadangan). Kode salah → 422. Akun tanpa 2FA tidak berubah. Perbarui aplikasi mobile sebelum mewajibkan 2FA untuk role yang memakai aplikasi.

Secret disimpan terenkripsi (APP_KEY — jangan diganti), kode cadangan hanya sebagai hash; keduanya tidak pernah tertulis di Log Aktivitas.

## Keamanan Data

- **Log Aktivitas**: administrator memantau perubahan penting (keuangan, pengaturan, OLT, role) di menu **Log Aktivitas**.
- **Verifikasi dua langkah**: lihat bagian di atas; disarankan wajib untuk Administrator & Admin.
- **Blokir akses**: bila staf berhenti bekerja, **nonaktifkan** akunnya daripada menghapus, agar riwayat transaksi tetap terjaga. Tombol **Reset sesi** mengeluarkan akun dari semua perangkat.

---
*Peringatan: jangan membagikan satu akun Administrator ke lebih dari satu orang — buat akun terpisah agar setiap perubahan bisa ditelusuri.*
