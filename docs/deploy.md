# Checklist Deploy & Operasional Server

Untuk Admin IT. Jalankan berurutan setiap kali memasang versi baru di server produksi.

## 1. Sebelum Deploy

- [ ] **Backup database** dan simpan salinannya di luar server (`BACKUP_REMOTE_DISK` / `BACKUP_REMOTE_PATH`).
- [ ] Catat versi yang sedang berjalan (pojok kanan bawah aplikasi, mis. `v1.5.164`).
- [ ] **Update dari versi lama (mis. v1.5.65)** — baca dulu bagian *Update dari Versi Lama* di bawah.

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

## Update dari Versi Lama (mis. v1.5.65 → versi terbaru)

Versi lama tidak pernah mengisolir pelanggan yang menunggak (bug), webhook pembayaran belum diverifikasi, dan belum ada pengingat WhatsApp. Data transaksi lama **tidak** diubah, tetapi perilaku billing, isolir, pembayaran & WhatsApp berubah begitu versi baru berjalan. Pakai SSH (bukan menu Pembaruan Sistem versi lama, yang tidak menjalankan `npm run build`), pada pagi/siang hari kerja.

**Sebelum** — backup database, `.env` dan `storage/app`; catat commit lama (`git log -1 --oneline`); cek `php -m | grep -i snmp`, `which mysqldump ping`, `node -v`, `upload_max_filesize` ≥ 10M; jalankan query pengecekan:

```sql
SELECT status, COUNT(*) FROM invoices GROUP BY status;            -- hanya unpaid / paid / overdue
SHOW TABLES LIKE 'bank_mutations';                                  -- harus kosong
SELECT code FROM chart_of_accounts WHERE code IN ('1104','1105','1106','1202','2101','2102','4199','5105','5106');  -- 9 baris
SELECT COUNT(*), FROM_UNIXTIME(MIN(created_at)) FROM jobs;          -- job lama yang menumpuk
```

**Pasang** — `php artisan down --retry=60` → langkah 2 di atas (pull, composer, `migrate --force` = 28 migrasi, `npm ci && npm run build`, `.env`, `optimize`, `storage:link`, reload PHP-FPM) → `php artisan up`.

Migrasi yang mengisi/mengubah data lama: template invoice bawaan yang belum diubah diganti template baru; tiket selesai, pelanggan dicabut/terisolir diberi tanggalnya dari riwayat; kredensial OLT dienkripsi (`APP_KEY`); kolom telnet OLT dihapus; **isolir otomatis = nonaktif** (server sudah berisi pelanggan); template WA "Gangguan Teratasi"; akun COA **1290 Akumulasi Penyusutan**.

**Hari itu juga (sebelum 00:01 & 09:00):**

1. Cek halaman utama tanpa error, versi tampil benar, `storage/logs/laravel.log` bersih.
2. *Pengaturan Isolir › Isolir otomatis* = **Nonaktif**; `php artisan app:process-billing --dry-run`.
3. *Integrasi*: kredensial webhook Moota (Webhook Secret Token / api_token), Midtrans, Xendit, Duitku terisi; **satu pembayaran percobaan**. Transfer Moota yang tidak jelas kini menunggu di *Keuangan › Mutasi Bank*.
4. Pengingat tagihan WA H-3 / H-1 / hari H mulai 09:00 — periksa *Template Pesan › Pengingat Tagihan*.
5. Antrean: buang job basi (`php artisan queue:clear`) **sebelum** worker berjalan; bila `QUEUE_CONNECTION` = `database`/`redis`, worker (supervisor, bagian 3) wajib hidup.
6. Uji login satu staf per role; aktifkan 2FA di akun Administrator.

**Minggu pertama (bertahap):** aktifkan isolir otomatis setelah daftar penunggak dibereskan (+ mode isolir & skrip MikroTik, masa tenggang); sesuaikan *Hak Akses per Role*; OLT HSGQ (akses web), Sinkron ONU + *Penautan ONU*; Bot NOC (OLT/ONU/router); GenieACS NBI; rekonsiliasi bank; penyusutan (akun akumulasi ke 1290, lalu aktifkan); opsional: dismantle otomatis, BA instalasi, masa simpan log, backup luar server, OSRM peta; NPWP; `customers:recover-edit-bug`. Wajibkan 2FA per role **setelah** aplikasi mobile staf mendukung field `code`.

**Yang dirasakan pengguna:** tampilan baru & menu mengikuti hak akses role; Arus Kas periode lama tampil berbeda (perbaikan hitung ganda & klasifikasi, jurnal tidak berubah); Rekening Koran memberi peringatan selisih saldo tersimpan; pelanggan menerima pengingat tagihan, dan setelah isolir otomatis aktif benar-benar terisolir lewat RADIUS.

**Kembali ke versi lama** hanya dengan memulihkan backup database (migrasi mengenkripsi kredensial OLT & menghapus kolom telnet): `php artisan down` → `git checkout <commit lama>` → composer & `npm run build` → `mysql … < backup.sql` → `optimize` → `php artisan up`. Transaksi setelah update ikut hilang — catat dulu.

## Bila Ada Masalah

- Halaman error 500: `storage/logs/laravel.log` atau `Pengaturan & Keamanan › Sistem & Log`.
- Kembali ke versi sebelumnya: `git checkout <versi-lama>` lalu ulangi langkah 2 (migrasi baru tidak di-rollback otomatis — pulihkan dari backup bila perlu).
