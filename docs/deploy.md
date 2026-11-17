# Checklist Deploy & Operasional Server

Untuk Admin IT. Jalankan berurutan setiap kali memasang versi baru di server produksi.

## 1. Sebelum Deploy

- [ ] **Backup database** dan simpan salinannya di luar server (`BACKUP_REMOTE_DISK` / `BACKUP_REMOTE_PATH`).
- [ ] Catat versi yang sedang berjalan (pojok kanan bawah aplikasi, mis. `v1.5.164`).

## 2. Pasang Versi Baru

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize:clear && php artisan optimize
```

- [ ] **Jangan mengganti `APP_KEY`** — semua kredensial (OLT, GenieACS, gateway, SNMP) tersimpan terenkripsi dengan kunci ini.
- [ ] `.env`: `APP_LOCALE=id`, `APP_ENV=production`, `APP_DEBUG=false`, `TRUSTED_PROXIES` (bila di belakang proxy/Cloudflare), `WA_GATEWAY_URL` & `WA_GATEWAY_KEY`.
- [ ] Paket SNMP untuk OLT: `sudo apt install -y php8.3-snmp snmp`.

## 3. Proses Latar Belakang (wajib)

| Proses | Cara | Kegunaan |
|---|---|---|
| **Jadwal** | cron tiap menit: `* * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1` | Billing 00:01, pengingat 09:00, backup 02:30, SLA tiket, masa tenggang aktivasi, sinkron pemakaian, NOC, **impor pelanggan besar** (antrean `imports`). |
| **Worker antrean** | supervisor: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` | Pembuatan invoice & isolir dari billing harian, pesan WhatsApp (tagihan, tiket ke grup), pindai OLT, catatan Pekerjaan di OLT. |

> **Sebelum menyalakan worker pertama kali**, cek tumpukan job lama: `SELECT COUNT(*), FROM_UNIXTIME(MIN(created_at)) FROM jobs;`. Bila ada job lama (mis. pesan WhatsApp berminggu-minggu lalu), putuskan dulu — hapus job yang sudah basi agar pelanggan tidak menerima pesan lama — baru jalankan worker.

## 4. Setelah Deploy

- [ ] `php artisan app:process-billing --dry-run` — pastikan daftar yang akan ditagih/diisolir masuk akal **sebelum** billing harian berikutnya.
- [ ] Buka Dashboard, Pelanggan, Invoice, Data OLT & GenieACS — tidak ada error.
- [ ] **Catatan Rilis** menampilkan versi baru.

## 5. Sekali Saja (bila belum)

- [ ] **Pemulihan bug form edit lama**: `php artisan customers:recover-edit-bug` (laporan), periksa, lalu `--apply` — lihat `docs/troubleshooting.md`.
- [ ] **OTP portal**: uji kirim kode lewat gateway produksi sebelum mengaktifkannya (`Invoice & Portal`).
- [ ] **OLT HSGQ**: isi *Akses web OLT* di Ubah OLT agar allow/reboot/ubah ONU tersedia; ganti community SNMP bawaan dan batasi SNMP ke IP server billing.
- [ ] **GenieACS**: pasang login NBI lewat nginx + batasi IP (contoh di `docs/network.md`), lalu isi di `Integrasi › Server GenieACS` dan klik **Tes**.
- [ ] **Bot NOC**: atur interval pemantauan di `Integrasi › Bot NOC` bila peringatan OLT/ONU diinginkan.
- [ ] **Data perusahaan**: NPWP & catatan kaki invoice di `Invoice & Portal`.
- [ ] **Penautan ONU**: NOC Center › Sinkron ONU, lalu periksa *Penautan ONU* (usulan) agar pelanggan lama tertaut ke OLT.
- [ ] **Jalur kabel (opsional)**: isi server OSRM di `Peta Pelanggan › Pengaturan jalur` bila ingin tombol *Ikuti jalan*; tanpa itu jalur digambar manual.

## Bila Ada Masalah

- Halaman error 500: `storage/logs/laravel.log` atau `Pengaturan & Keamanan › Sistem & Log`.
- Kembali ke versi sebelumnya: `git checkout <versi-lama>` lalu ulangi langkah 2 (migrasi baru tidak di-rollback otomatis — pulihkan dari backup bila perlu).
