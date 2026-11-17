# Pengaturan

> **Lokasi Menu:** `Pengaturan & Keamanan` (khusus administrator)

| Menu | Isi |
|---|---|
| **Kelola Staf** | Akun staf, role, penanda "juga sales", nonaktifkan & reset sesi (lihat `docs/users.md`). |
| **Hak Akses per Role** | Tabel fitur × role (Penuh / Lihat / Sebagian) yang **bisa diubah**: per fitur atau per aksi, dengan tombol kembali ke bawaan (lihat `docs/users.md`). |
| **Pengaturan Aplikasi** | Identitas perusahaan (nama, alamat, kontak, logo — tampil di invoice, laporan, BA, halaman login & WhatsApp), **modul tambahan** (menu & perhitungan modul hanya muncul bila diaktifkan), **notifikasi tiket ke grup WhatsApp** (ID grup per jenis tiket). |
| **Invoice & Portal** | Format & nomor invoice, NPWP, catatan kaki invoice, **portal pelanggan** dan **login OTP portal**. |
| **Pengaturan Isolir** | Isolir lewat RADIUS (grup & IP isolir, mode rentang/pool), masa tenggang, dismantle otomatis, skrip MikroTik, daftar pelanggan terisolir (lihat `docs/billing.md`). |
| **Pengaturan Instalasi** | Berita Acara instalasi & invoice biaya instalasi (batas bayar sebelum isolir). |
| **Data Master** | Wilayah, STO, STB, ODC, ODP (dipakai kode pelanggan & lokasi). |
| **Integrasi** | Payment Gateway, WhatsApp Gateway, Server GenieACS (dengan login NBI), Bot NOC (peringatan OLT/ONU). |
| **Template / Broadcast / Riwayat Pesan** | Template WhatsApp (tagihan, pengingat, isolir, OTP, …), kirim broadcast, riwayat pengiriman. |
| **Log Aktivitas** | Siapa mengubah apa & kapan (nilai lama → baru; data rahasia disamarkan). **Masa simpan log** di halaman yang sama (lihat di bawah). |
| **Sistem & Log** | Log server dan pembaruan sistem. |
| **Catatan Rilis** | Perubahan per versi (disusun otomatis dari riwayat rilis). |
| **Backup & Restore** | Backup database harian (02:30) dan unduh/pulihkan. Simpan salinan di luar server. |

## Pajak & Tutup Buku

- **PPN**: `Keuangan › Pusat Pajak PPN` — tarif & mode (per pelanggan atau global).
- **Tutup Buku**: `Keuangan › Laporan Keuangan › Tutup Buku` — transaksi sebelum tanggal tutup buku tidak bisa ditambah, diubah atau dihapus.

## Masa Simpan Log

> `Pengaturan & Keamanan › Log Aktivitas › Masa simpan log` (administrator)

Bawaan **semua log disimpan selamanya**. Bila tabel log sudah terlalu besar, atur masa simpan; log yang melewati batas dihapus otomatis setiap hari pukul **03:45** (`logs:prune`, setelah backup harian), sedikit demi sedikit agar tabel tidak terkunci.

| Log | Pilihan | Batas minimum |
|---|---|---|
| Log aktivitas **keuangan** — invoice, pembayaran, jurnal, kas & bank, COA, pajak, gateway, komisi, pengaturan | selamanya / 5 / 7 / 10 tahun | tidak bisa kurang dari **5 tahun** |
| Log aktivitas **keamanan** — login/logout, akun & role staf, hak akses, backup, ekspor/impor data | mengikuti log operasional | minimal **1 tahun** |
| Log aktivitas **operasional** lainnya | selamanya / 90 / 180 / 365 / 730 hari | — |
| Log WhatsApp, log login PPPoE (RADIUS, menu Log Autentikasi), notifikasi staf yang **sudah dibaca** | selamanya / 30–365 hari | notifikasi yang belum dibaca tidak dihapus |

**Pratinjau** menghitung berapa baris yang akan dihapus tanpa menyimpan. Penghapusan tidak bisa dibatalkan (backup harian tetap menyimpan salinannya); setiap perubahan setelan dan setiap penghapusan tercatat di Log Aktivitas (`logs_retention_updated`, `logs_pruned`). Cek manual: `php artisan logs:prune --dry-run`.

---
*Keamanan: kredensial (gateway, OLT, GenieACS, SNMP) disimpan terenkripsi dan tidak pernah ditampilkan lagi setelah disimpan. Jangan mengganti `APP_KEY` di server — kredensial terenkripsi tidak akan terbaca.*
