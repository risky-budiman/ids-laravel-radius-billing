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

## Sinkronisasi Layanan

### 1. Change of Authorization (CoA)
Fitur ini memungkinkan sistem untuk langsung memutuskan koneksi pelanggan (Disconnect) saat statusnya berubah di dashboard, tanpa harus menunggu pelanggan logout secara manual.

### 2. Monitoring Status
Sistem secara berkala akan mengambil data status koneksi (Uptime, IP Address, Traffic) untuk ditampilkan di profil pelanggan di dashboard.

---
*Teknis: Pastikan port 1812 (Auth) dan 1813 (Acct) pada firewall server Anda terbuka agar komunikasi radius berjalan lancar.*
