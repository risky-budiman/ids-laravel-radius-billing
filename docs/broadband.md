# Paket Layanan Broadband

> **Lokasi Menu:** `Broadband > Packages`

Modul ini digunakan untuk menentukan produk internet apa saja yang Anda jual kepada pelanggan.

## Membuat Paket Baru

Akses menu **Broadband > Packages > Add New**.

### 1. Nama & Kecepatan (Speed)
- **Package Name**: Contoh: "Home Basic 10Mbps", "Corporate Gold 50Mbps".
- **Download Limit**: Batas kecepatan unduh (dalam Mbps).
- **Upload Limit**: Batas kecepatan unggah (dalam Mbps).

### 2. Harga & Biaya
- **Monthly Fee**: Harga langganan bulanan yang akan muncul otomatis di invoice.
- **PPN**: Centang jika paket ini sudah termasuk pajak atau ingin dikenakan pajak tambahan.

### 3. Konfigurasi Radius (Teknis)
- **Profile**: Nama profil yang harus sama dengan yang ada di Router MikroTik (jika menggunakan integrasi API).
- **Shared Users**: Jumlah perangkat yang diizinkan menggunakan satu akun secara bersamaan (biasanya 1).

## Manajemen Paket

Anda dapat mengubah harga atau kecepatan paket yang sudah ada, namun perlu diingat:
- Perubahan harga hanya akan berdampak pada **Invoice Baru**.
- Perubahan kecepatan akan langsung berdampak pada pelanggan saat mereka melakukan *re-connect* (putus-nyambung) koneksi.

---
*Perhatian: Jangan menghapus paket yang masih memiliki pelanggan aktif. Sebaiknya gunakan fitur 'Disable' agar paket tersebut tidak bisa dipilih lagi untuk pelanggan baru.*
