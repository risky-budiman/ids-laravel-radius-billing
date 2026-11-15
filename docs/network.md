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

### Keamanan aksi tulis ke ONU

Hapus ONU (dismantle, hapus pelanggan, hapus manual), isolir/buka ONU dan ganti profil kecepatan **hanya dijalankan bila SN di posisi ONU tersebut sama dengan SN pelanggan**. Sistem membaca SN di posisi itu lewat SNMP terlebih dahulu:

- SN cocok → perintah dijalankan.
- SN berbeda (posisi sudah dipakai ONU pelanggan lain), posisi kosong, atau SNMP tidak bisa dibaca → **dibatalkan**, tidak ada yang dihapus.

Hasil setiap aksi (berhasil, dibatalkan beserta alasannya, atau gagal) tercatat di **Log Aktivitas** dengan aksi `onu_deleted`, `onu_delete_aborted`, `onu_delete_failed`, `onu_action_aborted`, `onu_action_failed`.

**Hapus ONU manual** (halaman OLT → Hapus ONU): isi **indeks** dan **SN ONU**. Format indeks yang diterima: `1/1/7:5`, `gpon-onu_1/1/7:5`, `1.1.7.5` atau `.1.1.7.5`.

Hapus ONU otomatis belum didukung untuk OLT HSGQ — sistem mencatatnya di Log Aktivitas agar dihapus manual di OLT.

### Registrasi ONU & profil kecepatan

- **VLAN** diambil dari isian *VLAN* pada wizard aktivasi (bawaan 100 bila kosong).
- **Profil T-CONT** diambil dari paket pelanggan (*Paket → Profil T-CONT OLT*); bawaan `UP-100M` bila kosong.
- Nama ONU di OLT memakai ID pelanggan (tanpa spasi), agar perintah ZTE tidak gagal.
- SN harus 8–16 huruf/angka; selain itu registrasi ditolak.
- Saat paket pelanggan diganti, profil T-CONT ONU ikut diganti **bila** paket baru punya Profil T-CONT. Bila kosong, langkah ini dilewati — kecepatan internet tetap diatur RADIUS/MikroTik.

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

---
*Teknis: Pastikan port 1812/1813 UDP (Radius) dan port 161 UDP (SNMP) pada firewall/routing jaringan Anda dapat berkomunikasi dengan lancar.*
