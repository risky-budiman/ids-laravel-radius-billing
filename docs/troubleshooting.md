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

## 5. Pelanggan Terdampak Bug Form Edit (sebelum v1.5.117)

Sebelum v1.5.117, setiap simpan di form edit pelanggan:
- memaksa status menjadi aktif/terisolir, sehingga pelanggan **menunggu aktivasi** menjadi terisolir (tidak bisa diaktifkan lewat wizard) dan pelanggan **dicabut/batal** kembali tertagih, bahkan login RADIUS-nya dibuat ulang;
- menghapus **koordinat** pelanggan.

Pemulihan dari Log Aktivitas (dijalankan Admin IT di server):
1. `php artisan customers:recover-edit-bug` — **hanya laporan**: daftar pelanggan yang akan dipulihkan, yang dilewati beserta alasannya, dan invoice belum lunas yang perlu diperiksa.
2. Periksa daftarnya, lalu `php artisan customers:recover-edit-bug --apply`.

Opsi: `--customer=<kode>` (satu pelanggan), `--before="2026-11-15 14:40"` (hanya kejadian sebelum waktu deploy perbaikan), `--skip-status`, `--skip-coordinates`.

Yang dipulihkan: status, aktif/tidak, tanggal isolir & tanggal berhenti asli, dan koordinat. Pelanggan yang kembali **menunggu aktivasi** dilepas dari isolir (aktivasi tetap lewat wizard). Pelanggan **dicabut/batal** dihapus lagi login RADIUS-nya dan sesinya diputus. Pelanggan yang setelah kejadian sudah diubah lagi (misalnya sudah diaktifkan resmi atau koordinat diisi ulang) **dilewati**. Invoice belum lunas yang terbit selama status salah hanya dilaporkan; batalkan atau sesuaikan secara manual. Setiap pemulihan tercatat di Log Aktivitas (`customer_recovered`).

---
*Dukungan Lanjutan: Jika masalah belum teratasi, silakan buat tiket dukungan kepada tim pengembang sistem.*

## 6. Pesan Galat GenieACS & OLT

| Pesan | Arti & langkah |
|---|---|
| *Tidak bisa terhubung ke GenieACS di …* | Server aplikasi tidak bisa membuka alamat NBI (biasanya port 7557). Periksa alamat & port di **Server ACS**, firewall dan routing ke server GenieACS. |
| *GenieACS tidak menjawab dalam N detik* | Server sibuk atau modem sedang diproses. Untuk ubah Wi-Fi/PPPoE ini berarti **perintah sudah masuk antrean** — cek tab Antrean di detail modem. |
| *GenieACS meminta login* / *Login NBI GenieACS ditolak* | NBI dilindungi Basic Auth: isi/perbaiki username & password NBI di **Server ACS**. |
| *Data tidak ditemukan di GenieACS (HTTP 404)* | Modem sudah dihapus dari GenieACS atau ID perangkat berubah (ganti modem). Muat ulang daftar perangkat. |
| *GenieACS mengalami galat (HTTP 5xx)* | Galat di server GenieACS; periksa log layanan `genieacs-nbi`. |
| *Pembacaan SNMP gagal* / *OLT tidak menjawab SNMP* | Periksa IP & port SNMP OLT, community / user SNMPv3, dan ACL SNMP di OLT yang mengizinkan IP server aplikasi. |
| *Web OLT menolak …* / *OLT menolak perintah* | Akses web OLT HSGQ salah atau firmware tidak mendukung perintah itu. Periksa username/password web di pengaturan OLT; hindari mencoba berulang (akun bisa terkunci). |
