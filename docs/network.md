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

Sistem mendukung integrasi langsung ke perangkat OLT (seperti ZTE C300, C320, C600) menggunakan protokol **Pure SNMP**:

- **Monitoring Real-Time**: Status perangkat (Uptime, CPU, Temperatur).
- **Auto-Discovery Port PON**: Deteksi otomatis seluruh port PON dan status aktif/kosong tanpa input manual.
- **Data Pelanggan (ONU)**: Menampilkan Serial Number, Nama, Status Online/Offline, dan Daya Optik (Rx Power dalam satuan dBm) secara langsung via SNMP walk.
- **Auto-Scan Modem Baru (Discovery)**: Memindai modem/ONU baru yang belum terkonfigurasi.

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
