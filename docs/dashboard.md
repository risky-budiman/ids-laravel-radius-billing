# Dashboard Utama

> **Lokasi Menu:** `Dashboard`

Dashboard adalah pusat kendali di mana Anda dapat memantau kesehatan bisnis ISP Anda secara real-time dalam satu layar.

## Statistik Pelanggan (Real-Time)

Di bagian atas dashboard, terdapat kartu ringkasan status pelanggan:
- **Total Customers**: Seluruh pelanggan yang terdaftar di database.
- **Active**: Pelanggan yang saat ini memiliki akses internet aktif.
- **Isolated**: Pelanggan yang terputus koneksinya karena jatuh tempo pembayaran.
- **Waiting**: Pelanggan baru yang sedang dalam proses instalasi.

## Grafik Pendapatan

Grafik ini menampilkan tren keuangan ISP Anda selama 12 bulan terakhir.
- **Target**: Membantu Anda melihat apakah pertumbuhan pelanggan sejalan dengan target pendapatan.
- **Warna**: Biasanya membedakan antara pendapatan dari biaya bulanan vs biaya instalasi.

## Aktivitas Terbaru (Logs)

Panel ini menampilkan riwayat kejadian penting di sistem, seperti:
- Login staf/admin.
- Perubahan paket pelanggan.
- Pembayaran invoice yang berhasil.
- Error sistem (jika ada).

## Penggunaan & Traffic

Menampilkan ringkasan trafik penggunaan bandwidth global (jika terintegrasi dengan perangkat MikroTik/OLT). Data ini penting untuk memantau apakah penggunaan bandwidth sudah mendekati kapasitas maksimal limit Anda.

---
*Tips: Gunakan data di dashboard untuk melakukan follow-up kepada pelanggan yang masuk dalam status 'Isolated' agar mereka segera melakukan pembayaran.*

## Tampilan per Role

- **Administrator & Admin**: seluruh dasbor, termasuk pendapatan, laba, saldo kas, piutang, PPN dan *Aktivitas Terbaru*.
- **Role lain** (teknisi, kasir, sales, CS): dasbor operasional (pelanggan, sesi online, autentikasi, trafik, PSB). Angka keuangan dan log aktivitas tidak dihitung maupun ditampilkan.
- **API aplikasi** (`/api/v1/admin/dashboard`): `invoices` & `revenue` hanya untuk administrator, admin dan kasir (role lain menerima `null`); `recent_activities` hanya untuk administrator & admin.
