# Integrasi Jaringan & Radius

> **Lokasi Menu:** `Network`

Bagian ini menjelaskan bagaimana sistem Radius Billing berkomunikasi dengan infrastruktur jaringan (Server Radius, MikroTik, dan OLT).

## Konsep Radius Server

Sistem ini bertindak sebagai database pusat untuk Server Radius (FreeRadius).
- Saat pelanggan mencoba terkoneksi, router akan bertanya ke database apakah pelanggan tersebut aktif.
- Jika invoice lunas (Status: Active), maka akses diizinkan.
- Jika invoice menunggak (Status: Isolated), maka akses diblokir atau diarahkan ke halaman isolir.

## Konfigurasi NAS (MikroTik)

Anda dapat menambahkan router di menu **Network > NAS**.
- **Shortname**: Nama pengenal router (contoh: "Core-Olt-01").
- **IP Address**: IP router yang dapat dijangkau oleh server radius.
- **Secret Key**: Kode rahasia yang harus sama antara sistem dan konfigurasi Radius di MikroTik.

## Integrasi OLT (Optical Line Terminal)

Sistem mendukung integrasi ke OLT ZTE (C300, C320, C600) dan HSGQ. Pemantauan memakai **SNMP**; registrasi, hapus, isolir dan ganti profil ONU di OLT ZTE memakai **telnet** (isi Username, Password & Enable Password OLT):

- **Monitoring Real-Time**: Status perangkat (Uptime, CPU, Temperatur).
- **Auto-Discovery Port PON**: Deteksi otomatis seluruh port PON dan status aktif/kosong tanpa input manual.
- **Data Pelanggan (ONU)**: Menampilkan Serial Number, Nama, Status Online/Offline, dan Daya Optik (Rx Power dalam satuan dBm) secara langsung via SNMP walk.
- **Auto-Scan Modem Baru (Discovery)**: Memindai modem/ONU baru yang belum terkonfigurasi.

### Pemantauan otomatis & peringatan NOC

> **Lokasi:** `Pengaturan > Integrasi > NOC Bot` → *Pemantauan otomatis OLT & peringatan*

**Bawaan: Nonaktif.** Bila diaktifkan (setiap 5/10/15/30/60 menit; butuh cron `schedule:run`):
- status & sinyal semua ONU dibaca lewat SNMP dan grafik riwayat sinyal terisi;
- peringatan dikirim ke **Telegram** dan/atau **WhatsApp** (nomor atau grup) yang diisi di halaman yang sama:
  - **OLT mati** — sekali, lalu **OLT kembali online** saat pulih;
  - **ONU putus** (LOS / dying-gasp / offline) — hanya pelanggan **aktif** yang sebelumnya online;
  - **sinyal kritis** di bawah **-27 dBm**;
  - per pelanggan paling sering **sekali per 6 jam**, digabung dalam **satu pesan per OLT**;
- riwayat sinyal lebih dari 30 hari dibersihkan setiap hari (`noc:prune`).

### Dasbor NOC & halaman port

- Status OLT dan jumlah **ONU belum terdaftar** di dasbor NOC (web & aplikasi) disimpan **5 menit**. Klik **Refresh** untuk memindai ulang.
- OLT yang **offline tidak dipindai** (dasbor tidak menunggu timeout). OLT yang offline atau gagal dipindai ditampilkan sebagai *"Tidak terpindai: …"*, sehingga angka 0 tidak disalahartikan.
- **Halaman port** langsung tampil; daftar ONU diambil di latar. Hasil scan disimpan 10 menit; tombol sinkron memindai ulang.
- Bila OLT tidak merespons SNMP, halaman menampilkan pesan gagal dan **daftar terakhir tetap ditampilkan** — port tidak lagi dianggap kosong dan statusnya tidak diubah.
- Deteksi ulang port PON berjalan dalam satu transaksi: bila gagal di tengah, status port tidak berubah.

### Metode akses OLT: SNMP + Telnet / SNMP saja

Di **Ubah OLT → Metode Akses**:
- **SNMP + Telnet** (bawaan): pemantauan lewat SNMP; registrasi, hapus, isolir dan ganti profil ONU otomatis lewat telnet.
- **SNMP saja**: tidak ada perintah telnet ke OLT. Pemantauan, sinyal dan daftar ONU (ZTE) tetap berjalan, sedangkan:
  - **registrasi, hapus dan ganti profil ONU tidak dijalankan otomatis**. Setiap kejadian dicatat di Log Aktivitas (`onu_provision_skipped`, `onu_delete_skipped`, `onu_action_skipped`) agar dikerjakan manual di OLT;
  - isolir/buka cukup lewat RADIUS (ONU tidak di-*shutdown*);
  - tombol *Hapus ONU Manual* diganti keterangan;
  - **daftar ONU OLT HSGQ masih membutuhkan telnet**, karena OID tabel ONU HSGQ belum tersedia (butuh MIB vendor).

### Hak akses & kredensial OLT

- **Tambah, ubah dan hapus OLT hanya administrator.** Teknisi tetap bisa melihat OLT, tes koneksi, sinkron port dan melihat ONU.
- **Password, enable password, read community dan write community disimpan terenkripsi** memakai `APP_KEY`. Jangan mengganti `APP_KEY` di server produksi: kredensial OLT tidak bisa dibaca lagi dan harus diisi ulang.
- Form **Ubah OLT** tidak menampilkan community maupun password. Kosongkan kolomnya untuk tetap memakai nilai lama; isi hanya bila ingin menggantinya.
- **OLT yang masih dipakai pelanggan tidak bisa dihapus.** Pindahkan pelanggan ke OLT lain terlebih dahulu.
- Tambah, ubah dan hapus OLT tercatat di **Log Aktivitas** (`olt_created`, `olt_updated`, `olt_deleted`).

### Keamanan aksi tulis ke ONU

Hapus ONU (dismantle, hapus pelanggan, hapus manual), isolir/buka ONU dan ganti profil kecepatan **hanya dijalankan bila SN di posisi ONU tersebut sama dengan SN pelanggan**. Sistem membaca SN di posisi itu lewat SNMP terlebih dahulu:

- SN cocok → perintah dijalankan.
- SN berbeda (posisi sudah dipakai ONU pelanggan lain), posisi kosong, atau SNMP tidak bisa dibaca → **dibatalkan**, tidak ada yang dihapus.

Hasil setiap aksi (berhasil, dibatalkan beserta alasannya, atau gagal) tercatat di **Log Aktivitas** dengan aksi `onu_deleted`, `onu_delete_aborted`, `onu_delete_failed`, `onu_action_aborted`, `onu_action_failed`.

**Hapus ONU manual** (halaman OLT → Hapus ONU): isi **indeks** dan **SN ONU**. Format indeks yang diterima: `1/1/7:5`, `gpon-onu_1/1/7:5`, `1.1.7.5` atau `.1.1.7.5`.

**Balasan OLT diperiksa.** Untuk registrasi, hapus, isolir/buka dan ganti profil ONU lewat telnet, sistem memastikan login sampai prompt `ZXAN#`. Setiap perintah juga harus dibalas prompt dan tidak berisi pesan error ZTE (`%Error …`, `%Code …`, `Invalid input …`). Bila ada yang gagal, perintah berikutnya tidak dijalankan. Alasannya tercatat di **Log Aktivitas**:
- *login telnet ditolak* — periksa username/password OLT;
- *mode enable ditolak* — periksa Enable Password;
- *perintah '…' ditolak OLT: %Error …*;
- *OLT tidak membalas perintah '…' (timeout)*.

Registrasi ONU yang berhasil maupun gagal tercatat (`onu_provisioned` / `onu_provision_failed`).

Hapus ONU otomatis belum didukung untuk OLT HSGQ — sistem mencatatnya di Log Aktivitas agar dihapus manual di OLT.

### Registrasi ONU & profil kecepatan

- **VLAN** diambil dari isian *VLAN* pada wizard aktivasi (bawaan 100 bila kosong).
- **Profil T-CONT** diambil dari paket pelanggan (*Paket → Profil T-CONT OLT*); bawaan `UP-100M` bila kosong.
- Nama ONU di OLT memakai ID pelanggan (tanpa spasi), agar perintah ZTE tidak gagal.
- SN harus 8–16 huruf/angka; selain itu registrasi ditolak.
- Saat paket pelanggan diganti, profil T-CONT ONU ikut diganti **bila** paket baru punya Profil T-CONT. Bila kosong, langkah ini dilewati — kecepatan internet tetap diatur RADIUS/MikroTik.

## Manajemen Modem Pelanggan (GenieACS / TR-069)

> **Lokasi Menu:** `Operasional Jaringan > GenieACS` (server ACS: `Pengaturan > Integrasi > Server GenieACS`)

- **Hak akses:** daftar & detail modem, refresh, reboot, ubah PPPoE/WiFi dan tag — **administrator & teknisi** (web dan aplikasi staf sama). **Factory reset** dan menambah/mengubah **server ACS** — administrator saja.
- **Pencarian** mencocokkan sebagian teks (tidak peka huruf besar/kecil) pada ID perangkat, SN, tag, username PPPoE (boleh dengan atau tanpa `@domain`) dan IP WAN. Karakter seperti `.` atau `(` dicari apa adanya.
- ID perangkat yang memuat spasi/`%20` (mis. `00259E-ZXHN%20F670L-…`) bisa dibuka.
- **Jejak perubahan:** ubah konfigurasi (`acs_config_updated`, berisi parameter yang diubah; password/kunci WiFi disamarkan), reboot (`acs_rebooted`), factory reset (`acs_factory_reset`) dan ubah tag (`acs_tags_updated`) tercatat di **Log Aktivitas** beserta pelakunya.

### Kebutuhan Server untuk OLT SNMP
Pastikan paket SNMP terinstall di server Ubuntu/Debian:
```bash
sudo apt install -y php8.3-snmp snmp
```

### Parameter Konfigurasi OLT di Web UI:
1. **IP Address**: IP Management OLT yang dapat dijangkau oleh server billing.
2. **SNMP Port**: Port default `161` (UDP).
3. **SNMP Version**: `v2c` (Rekomendasi).
4. **Read Community (RO)**: Community string untuk membaca status (contoh: `public` atau `yabero`).
5. **Write Community (RW)**: Community string untuk perintah modifikasi (contoh: `private` atau `yaberw`).

Community dan password disimpan terenkripsi dan tidak ditampilkan lagi setelah disimpan (lihat *Hak akses & kredensial OLT*).

---
*Teknis: Pastikan port 1812/1813 UDP (Radius) dan port 161 UDP (SNMP) pada firewall/routing jaringan Anda dapat berkomunikasi dengan lancar.*
