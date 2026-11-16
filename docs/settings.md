# Pengaturan

> **Lokasi Menu:** `Pengaturan & Keamanan` (khusus administrator)

| Menu | Isi |
|---|---|
| **Kelola Staf** | Akun staf, role, penanda "juga sales", nonaktifkan & reset sesi (lihat `docs/users.md`). |
| **Hak Akses per Role** | Tabel fitur × role (Penuh / Lihat / Sebagian), dibaca langsung dari aturan yang ditegakkan aplikasi. |
| **Pengaturan Aplikasi** | Identitas perusahaan (nama, alamat, kontak, logo — tampil di invoice, laporan, BA, halaman login & WhatsApp), **modul tambahan** (menu & perhitungan modul hanya muncul bila diaktifkan), **notifikasi tiket ke grup WhatsApp** (ID grup per jenis tiket). |
| **Invoice & Portal** | Format & nomor invoice, NPWP, catatan kaki invoice, **portal pelanggan** dan **login OTP portal**. |
| **Pengaturan Isolir** | Isolir lewat RADIUS (grup & IP isolir, mode rentang/pool), masa tenggang, dismantle otomatis, skrip MikroTik, daftar pelanggan terisolir (lihat `docs/billing.md`). |
| **Pengaturan Instalasi** | Berita Acara instalasi & invoice biaya instalasi (batas bayar sebelum isolir). |
| **Data Master** | Wilayah, STO, STB, ODC, ODP (dipakai kode pelanggan & lokasi). |
| **Integrasi** | Payment Gateway, WhatsApp Gateway, Server GenieACS (dengan login NBI), Bot NOC (peringatan OLT/ONU). |
| **Template / Broadcast / Riwayat Pesan** | Template WhatsApp (tagihan, pengingat, isolir, OTP, …), kirim broadcast, riwayat pengiriman. |
| **Log Aktivitas** | Siapa mengubah apa & kapan (nilai lama → baru; data rahasia disamarkan). |
| **Sistem & Log** | Log server dan pembaruan sistem. |
| **Catatan Rilis** | Perubahan per versi (disusun otomatis dari riwayat rilis). |
| **Backup & Restore** | Backup database harian (02:30) dan unduh/pulihkan. Simpan salinan di luar server. |

## Pajak & Tutup Buku

- **PPN**: `Keuangan › Pusat Pajak PPN` — tarif & mode (per pelanggan atau global).
- **Tutup Buku**: `Keuangan › Laporan Keuangan › Tutup Buku` — transaksi sebelum tanggal tutup buku tidak bisa ditambah, diubah atau dihapus.

---
*Keamanan: kredensial (gateway, OLT, GenieACS, SNMP) disimpan terenkripsi dan tidak pernah ditampilkan lagi setelah disimpan. Jangan mengganti `APP_KEY` di server — kredensial terenkripsi tidak akan terbaca.*
