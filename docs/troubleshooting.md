# Troubleshooting (Panduan Masalah Umum)

Berikut adalah daftar masalah yang sering ditemui dan langkah-langkah cepat untuk memperbaikinya.

## 1. Pelanggan Tidak Bisa Login (Internet Mati)
- **Cek Status**: Pastikan status pelanggan di dashboard adalah **Active**.
- **Cek Invoice**: Periksa apakah ada invoice yang belum dibayar melewati jatuh tempo.
- **Cek Radius**: Pastikan server radius berjalan normal dan router dapat berkomunikasi dengan server.

## 2. Invoice Tidak Ter-generate Otomatis
- **Cek Cron Job**: Pastikan penjadwalan (Task Scheduling) di server berjalan. Jalankan perintah `php artisan schedule:run` secara manual untuk mengetes.
- **Cek Due Date**: Pastikan tanggal jatuh tempo pada profil pelanggan sudah diisi.

## 3. Laporan Keuangan Tidak Seimbang (Balance)
- **Cek Jurnal Manual**: Periksa apakah ada input jurnal manual yang tidak berpasangan (debit/kredit tidak sama).
- **Cek Saldo Awal**: Pastikan saldo awal saat pertama kali setup sudah benar.

## 4. Halaman Blank atau Error 500
- **Cek Log Aplikasi**: Lihat di menu **System Logs** atau cek file `storage/logs/system.log`.
- **Clear Cache**: Terkadang cache browser atau server perlu dibersihkan. Hubungi Admin IT untuk menjalankan `php artisan optimize:clear`.

---
*Dukungan Lanjutan: Jika masalah belum teratasi, silakan buat tiket dukungan kepada tim pengembang sistem.*
