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

Sistem mendukung integrasi ke OLT ZTE (C300, C320, C600) dan HSGQ. Akses ke OLT **hanya lewat SNMP** (telnet tidak dipakai sama sekali):

- **Monitoring Real-Time**: Status perangkat (Uptime, CPU, Temperatur).
- **Auto-Discovery Port PON**: Deteksi otomatis seluruh port PON dan status aktif/kosong tanpa input manual.
- **Data Pelanggan (ONU)**: Menampilkan Serial Number, Nama, Status Online/Offline, dan Daya Optik (Rx Power dalam satuan dBm) secara langsung via SNMP walk.
- **Auto-Scan Modem Baru (Discovery)**: Memindai modem/ONU baru yang belum terkonfigurasi.

### Halaman daftar OLT

> **Lokasi Menu:** `Operasional Jaringan > Data OLT`

- **Ringkasan** di atas: total OLT, OLT online, pelanggan terhubung, dan ONU bersinyal kritis.
- Setiap kartu OLT menampilkan status (**Online** / **Tidak merespons** / **Belum dicek** / **Nonaktif**), tipe & teknologi (EPON/GPON), versi SNMP, jumlah port PON, CPU, suhu, uptime, serta jumlah pelanggan dan ONU online/offline/kritis.
- Status diambil dari hasil pengecekan terakhir (disimpan 5 menit), jadi halaman langsung tampil tanpa menunggu SNMP. **Perbarui status** memeriksa semua OLT saat itu juga; **Tes koneksi** memeriksa satu OLT dan hasilnya tampil di kartunya.
- Angka ONU online/offline/kritis berasal dari pemantauan otomatis atau halaman port; bila belum ada data, tampil `—`.
- Kotak pencarian menyaring OLT berdasarkan nama, IP, lokasi atau tipe.

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
- **Halaman port** langsung tampil; daftar ONU diambil di latar. Hasil scan disimpan 10 menit; **Sinkron langsung** memindai ulang. Isinya:
  - ringkasan port: total ONU, online, offline, sinyal kritis, rata-rata Rx;
  - tabel ONU: posisi, nama & MAC/SN, **deskripsi**, **pelanggan yang tertaut** (klik untuk membuka data pelanggan), sinyal Rx berwarna (≥ -24 baik, -24 s/d -27 waspada, < -27 dBm kritis), jarak, status;
  - cari (MAC/SN, nama, deskripsi, pelanggan), filter (Online, Offline, Kritis, Tanpa pelanggan), urutkan menurut posisi/sinyal/jarak, pindah port;
  - panel **Detail** per ONU: salin MAC/SN, buka pelanggan, **Reboot ONU**, ubah nama/deskripsi.
- **Ketersediaan aksi ONU:** OLT **ZTE** — reboot lewat SNMP (SN di posisi itu dicocokkan dulu; tercatat `onu_rebooted`). OLT **HSGQ** — SNMP-nya tidak menyediakan reboot, ubah nama/deskripsi maupun deskripsi ONU; deskripsi dibaca lewat **API web OLT** bila login web diisi, sedangkan reboot & ubah ONU HSGQ menyusul setelah API web OLT diuji.
- Bila OLT tidak merespons SNMP, halaman menampilkan pesan gagal dan **daftar terakhir tetap ditampilkan** — port tidak lagi dianggap kosong dan statusnya tidak diubah.
- Deteksi ulang port PON berjalan dalam satu transaksi: bila gagal di tengah, status port tidak berubah.

### Pekerjaan ONU dikerjakan di OLT (sistem hanya SNMP)

Sistem tidak lagi mengirim perintah telnet ke OLT; form OLT juga tidak punya isian telnet (username/password/enable). Akibatnya:
- **Registrasi ONU** (pelanggan baru), **hapus ONU** (dismantle/hapus pelanggan) dan **ganti profil T-CONT** (ganti paket yang punya profil) **dikerjakan teknisi langsung di OLT**. Sistem mencatat pekerjaannya di **Log Aktivitas** lengkap dengan OLT, posisi/port, SN, VLAN dan profil:
  - `onu_provision_manual` — daftarkan ONU;
  - `onu_delete_manual` — hapus ONU;
  - `onu_profile_manual` — ganti profil T-CONT.
- **Isolir & buka isolir** cukup lewat RADIUS (pelanggan diarahkan ke halaman isolir); ONU tidak perlu di-*shutdown*.
- Kecepatan internet tetap diatur RADIUS/MikroTik; profil T-CONT di OLT hanya batas fisik ONU.

### Daftar ONU OLT HSGQ (EPON) lewat SNMP

Daftar ONU per port PON HSGQ (MAC, nama, Rx power, jarak) dibaca lewat **SNMP**.
- OID dipetakan langsung dari OLT **HSGQ-E04ID** (firmware IR_V3.3.3ID_Rel) dan dicocokkan dengan CLI pada 238 ONU: MAC, nama dan posisi 100% sama, selisih Rx ≤ 0,01 dBm.
- **Status:** ONU dengan nilai optik dianggap *online*, tanpa nilai optik dianggap *offline*. Saat pemetaan dibuat semua ONU sedang online, jadi tampilan ONU yang benar-benar offline perlu dicek sekali (cabut satu ONU uji lalu buka halaman port).
- Deskripsi ONU (mis. nama pelanggan) tidak tersedia di SNMP HSGQ; yang tampil adalah *nama* ONU.
- Model/firmware HSGQ lain mungkin memakai OID berbeda. Bila daftar kosong, laporkan model & firmware-nya.

### Hak akses & kredensial OLT

- **Tambah, ubah dan hapus OLT hanya administrator.** Teknisi tetap bisa melihat OLT, tes koneksi, sinkron port dan melihat ONU.
- **Read/write community dan password SNMPv3 disimpan terenkripsi** memakai `APP_KEY`. Jangan mengganti `APP_KEY` di server produksi: kredensial OLT tidak bisa dibaca lagi dan harus diisi ulang.
- Form **Ubah OLT** tidak menampilkan community maupun password SNMPv3. Kosongkan kolomnya untuk tetap memakai nilai lama; isi hanya bila ingin menggantinya.
- **OLT yang masih dipakai pelanggan tidak bisa dihapus.** Pindahkan pelanggan ke OLT lain terlebih dahulu.
- Tambah, ubah dan hapus OLT tercatat di **Log Aktivitas** (`olt_created`, `olt_updated`, `olt_deleted`).

### Memilih ONU dari OLT saat aktivasi

Di **wizard aktivasi** (bagian OLT), setelah memilih *Source OLT* klik **Pilih dari OLT**:
- daftar semua ONU di OLT tersebut dibaca lewat SNMP (posisi, MAC/SN, nama ONU, sinyal) dan bisa dicari;
- klik satu ONU untuk mengisi **SN/MAC** dan **posisi** otomatis (format `1/1/2:5`);
- ONU yang sudah dipakai pelanggan lain ditandai dan tidak bisa dipilih; ONU milik pelanggan yang sedang diaktifkan ditandai *Pelanggan ini*;
- hasil baca disimpan 10 menit (sama dengan halaman port); **Baca ulang** membaca langsung dari OLT.

Dipakai bila ONU sudah di-*allow* di OLT.

### Akses web OLT HSGQ (persiapan registrasi ONU dari aplikasi)

SNMP OLT HSGQ tidak menyediakan registrasi/allow ONU, sehingga registrasi dari aplikasi memakai **API web OLT**. Di **Ubah OLT → Akses web OLT** (tampil untuk tipe HSGQ) isi username & password halaman web OLT, port (bawaan 80) dan HTTPS bila dipakai, lalu klik **Tes login web**. Password disimpan terenkripsi dan tidak ditampilkan lagi.

Saat ini sistem baru **membaca** lewat API web (login, info perangkat, daftar izin ONU). Tombol *Daftarkan/allow ONU* akan ditambahkan setelah format perintahnya dipastikan di OLT asli dengan ONU uji. Hindari salah password berulang: OLT bisa mengunci akun web.

### Data registrasi ONU (untuk dikerjakan di OLT)

- **VLAN** diambil dari isian *VLAN* pada wizard aktivasi (bawaan 100 bila kosong).
- **Profil T-CONT** diambil dari paket pelanggan (*Paket → Profil T-CONT OLT*).
- Nama ONU di OLT sebaiknya memakai ID pelanggan agar mudah dicocokkan.

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
3. **SNMP Version**: `v2c`, atau **`v3`** bila OLT mendukungnya (disarankan untuk keamanan — lihat di bawah).
4. **Read Community (RO)**: Community string untuk membaca status (contoh: `public` atau `yabero`).
5. **Write Community (RW)**: Community string untuk perintah modifikasi (contoh: `private` atau `yaberw`).

Community dan password disimpan terenkripsi dan tidak ditampilkan lagi setelah disimpan (lihat *Hak akses & kredensial OLT*).

### SNMPv3 (disarankan)

SNMP v1/v2c mengirim community — termasuk community *write* — **tanpa enkripsi** di jaringan. Dengan **Version 3**, OLT diakses memakai user SNMPv3:
- **Tingkat keamanan**: `authPriv` (autentikasi + enkripsi, disarankan), `authNoPriv`, atau `noAuthNoPriv`.
- **Autentikasi**: SHA, SHA-256 atau MD5. **Enkripsi**: AES-128 atau DES.
- Password autentikasi & enkripsi minimal **8 karakter**, disimpan terenkripsi, dan tidak ditampilkan lagi (kosongkan saat ubah untuk tetap memakai yang lama).
- User yang sama dipakai untuk membaca dan menulis; hak tulisnya diatur di OLT.
- Buat dulu user SNMPv3 di OLT dengan protokol & password yang sama, lalu klik **Tes Koneksi**.

Pesan *Tes Koneksi* dan log tidak lagi menampilkan community string.

---
*Teknis: Pastikan port 1812/1813 UDP (Radius) dan port 161 UDP (SNMP) pada firewall/routing jaringan Anda dapat berkomunikasi dengan lancar.*
