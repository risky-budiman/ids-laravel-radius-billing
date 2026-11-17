# Manajemen Pelanggan

> **Lokasi Menu:** `Manajemen > Pelanggan`

Modul Pelanggan adalah inti operasional ISP: pendaftaran, aktivasi, perubahan layanan, hingga pencabutan.

## Pendaftaran Pelanggan Baru

Buka **Pelanggan > Tambah Pelanggan** (administrator, admin, teknisi, sales).

### 1. Data Identitas
- **Nama**, **Email & No. HP** — No. HP dipakai untuk tagihan, pengingat WhatsApp dan kode OTP portal.
- **NIK/KTP** — untuk verifikasi.

### 2. Lokasi & Peta
Tentukan titik koordinat rumah pelanggan di peta; membantu teknisi saat instalasi & perbaikan.

### 3. Layanan
- **Paket**, **jenis & metode tagihan**, **tanggal jatuh tempo**, **biaya instalasi**.

Pelanggan baru berstatus **Menunggu aktivasi** dan otomatis mendapat tiket aktivasi. Aktivasi dilakukan lewat **wizard aktivasi** (teknisi/admin).

## Status Pelanggan

| Status | Arti | Tertagih |
|---|---|---|
| Baru / Menunggu aktivasi | Belum dipasang | Tidak |
| Aktif | Internet berjalan | Ya |
| Terisolir (suspended) | Menunggak; internet diarahkan ke halaman isolir | Ya |
| Menunggu dismantle | Diajukan penarikan perangkat | Tidak |
| Dicabut (dismantled) / Batal | Berhenti berlangganan | Tidak |

## Siapa Bisa Mengubah Apa (Edit Pelanggan)

| Data | Admin / Administrator | Teknisi | Sales |
|---|---|---|---|
| No. HP, email, alamat | ✔ | ✔ | ✔ |
| Wilayah, STO, STB | ✔ | ✔ | – |
| Nama, NIK/NPWP, prioritas | ✔ | – | – |
| Username & password PPPoE | ✔ | lihat saja | tidak ditampilkan |
| Paket, jenis/metode tagihan, tanggal tagihan | ✔ | – | – |
| Diskon, PPN, biaya instalasi, status Aktif/Terisolir | ✔ | – | – |
| Referal sales & mitra (beserta komisinya) | ✔ | – | – |

Field yang tidak boleh diubah tetap tampil tapi terkunci, dan server mengabaikannya walau dikirim (berlaku juga untuk API aplikasi staf; respons API menyebut field yang diabaikan di `ignored_fields`). Teknisi dan sales yang perlu mengganti paket memakai **Ajukan ganti paket**.

Referal sales dan mitra hanya berubah bila bagiannya ada di form. Menyimpan form saat modul mitra/komisi sales nonaktif — atau oleh teknisi/sales — **tidak** menghapus tautan sales/mitra pelanggan.

Di **Edit Pelanggan**, status hanya bisa diubah antara **Aktif** dan **Terisolir**. Status lain berubah lewat alurnya sendiri (wizard aktivasi, dismantle) dan ditampilkan apa adanya di form. Menyimpan form edit tidak mengubah koordinat bila form tidak mengirimnya.

## Ubah Kontak

Tombol **Ubah kontak** di halaman pelanggan (admin & CS) hanya mengubah **No. HP, email dan alamat**. Perubahan tercatat di Log Aktivitas.

## Ganti Paket (lewat pengajuan)

1. Di halaman pelanggan klik **Ajukan ganti paket**, pilih paket baru dan tulis alasannya (admin, CS, teknisi & sales).
2. Pengajuan muncul di **Manajemen > Pengajuan Ganti Paket** dengan selisih harga per bulan.
3. Admin/administrator **Setujui** atau **Tolak** (alasan wajib).
4. Saat disetujui paket langsung berganti: grup RADIUS paket baru (tetap terisolir bila sedang diisolir), sesi diputus agar kecepatan baru berlaku, bila paket punya *Profil T-CONT OLT*, Log Aktivitas mencatat agar profil ONU diganti manual di OLT. Tagihan berikutnya memakai harga baru.

Aturan: satu pengajuan menunggu per pelanggan; admin tidak bisa menyetujui pengajuannya sendiri (administrator bisa); pengajuan ditolak bila paket pelanggan sudah berubah sejak diajukan.

## Peta Pelanggan

Menu **Peta Pelanggan** (administrator, admin, teknisi, sales, CS) menampilkan pelanggan yang punya koordinat.

**Lapisan status** (bisa dinyalakan/dimatikan, dengan jumlah per lapisan; tombol *Hanya masalah*):

| Lapisan | Arti |
|---|---|
| Gangguan | Pelanggan aktif dengan tiket **gangguan** terbuka, ONU putus (LOS/dying gasp/offline), atau sinyal di bawah -27 dBm (hasil Sinkron ONU terakhir) |
| Terisolir | Status terisolir |
| Menunggak | Ada invoice belum lunas yang **lewat jatuh tempo** (invoice booster tidak dihitung) |
| Normal | Aktif tanpa masalah |
| Belum aktif | Baru / menunggu aktivasi |
| Berhenti | Menunggu dismantle / dicabut / batal (bawaan disembunyikan) |

Cari pelanggan di kotak pencarian untuk langsung memperbesar ke lokasinya. Infrastruktur (OLT, wilayah, STO, STB, ODC, ODP) hanya tampil untuk admin & teknisi. Peta hanya memuat data yang ditampilkan (nama, kode, paket, status, tunggakan) — password, NIK, No. HP dan alamat tidak lagi ikut terkirim ke browser.

### Jalur kabel

Setiap pelanggan / ODP / ODC / STB / STO tersambung ke induknya (pelanggan→ODP, ODP→ODC, ODC→STB, STB→STO, STO→wilayah). Garis **putus-putus** = jalur belum digambar (garis lurus); garis **utuh** = jalur kabel tersimpan, dengan panjangnya.

Admin & teknisi menggambar jalur: klik titik lalu **Gambar jalur kabel** (atau klik garisnya).
1. Klik peta di sepanjang jalan / tiang untuk menambah titik belok — titik disisipkan di ruas terdekat. Seret titik untuk memindah, klik kanan untuk menghapus.
2. **Ikuti jalan** (opsional) membentuk jalur mengikuti jalan melewati titik-titik itu; ujungnya tetap tersambung ke tiang ODP dan rumah pelanggan. Tambah titik lalu klik lagi bila jalannya kurang tepat.
3. **Simpan jalur**. *Kembalikan garis lurus* menghapus jalur. Semua perubahan tercatat di Log Aktivitas.

**Ikuti jalan** membutuhkan server routing **OSRM** yang diatur administrator di *Peta Pelanggan › Pengaturan jalur* (alamat server & profil: kendaraan / jalan kaki / sepeda). Bawaan kosong = nonaktif; gambar manual tetap bisa. Yang dikirim ke server routing hanya koordinat titik jalur. Paling aman memakai server OSRM sendiri dengan data OpenStreetMap Indonesia; server demo publik `https://router.project-osrm.org` hanya profil kendaraan, tanpa jaminan layanan, dan koordinat terkirim ke pihak luar.

## Impor & Ekspor Data

Hanya **admin & administrator** (menu Pelanggan → *Impor Data* / *Ekspor Data*).

### Ekspor
- **Tanpa password PPPoE** — disarankan untuk arsip/dibagikan. Kolom password dikosongkan.
- **Dengan password PPPoE** — untuk migrasi ke sistem lain. File berisi password semua pelanggan; simpan dengan aman. Tercatat di Log Aktivitas.
- **CSV (tanpa password)** — paling cepat & hemat memori untuk ribuan pelanggan; pemisah `;`, dibuka di Excel / Google Sheets.

Ekspor mengikuti **filter** di daftar pelanggan (status, paket, tipe tagihan, pencarian). Isi file:
- **Kolom A–T** sama dengan template impor, jadi file bisa diimpor ulang (mode *Perbarui*) atau ke sistem lain.
- **Kolom `info_…`** (abu-abu) hanya informasi dan diabaikan saat impor: tipe pelanggan, biaya instalasi, wilayah/STO/STB, ODP & port, OLT, posisi ONU, **tunggakan** (jumlah invoice belum lunas, tanpa invoice booster) & jumlah invoice belum lunas, sales, mitra, tanggal daftar, tanggal berhenti.
- Sheet **Ringkasan Export**: jumlah pelanggan per **semua** status (termasuk dicabut & batal).

### Impor
1. Unduh **template**, isi sheet *Format Import* (lihat sheet *Contoh & Panduan*). Kolom username, password, No. HP, ID pelanggan, NIK dan SN ONU sudah berformat **teks** — jangan diubah ke angka, karena Excel menghapus angka 0 di depan dan membulatkan NIK 16 digit.
2. Pilih file lalu klik **Cek dulu (pratinjau)**. Belum ada data yang disimpan; sistem menampilkan rencana per baris (*dibuat* / *diperbarui*), baris yang akan dilewati beserta alasannya, dan catatan. Bila sudah sesuai klik **Impor sekarang** (file tidak perlu diunggah ulang). Tombol **Langsung impor** melewati pratinjau.
   - **File besar (lebih dari 300 baris)** diproses di **latar belakang**: halaman menampilkan bilah kemajuan ("Baris 125 dari 2.000") dan hasilnya muncul sendiri saat selesai. Halaman boleh ditutup; buka lagi lewat tautan yang sama. Proses dimulai paling lambat ±1 menit oleh jadwal server (butuh cron `schedule:run`). Bila impor gagal di tengah jalan, baris yang sudah masuk tetap tersimpan; unggah ulang dengan mode *Lewati* untuk melanjutkan sisanya.
3. Pilih cara menangani username yang sudah ada:
   - **Lewati** — baris dengan username yang sudah ada tidak diproses.
   - **Perbarui** — hanya kolom yang **diisi** di file yang diubah. Kolom kosong — termasuk password — **tidak** menghapus data lama, jadi file hasil ekspor tanpa password aman diimpor ulang.
4. Aturan mode *Perbarui*:
   - **Status pelanggan lama tidak diubah** lewat impor; perbedaan status ditampilkan sebagai catatan.
   - **Ganti paket** memakai alur resmi (grup RADIUS, putus sesi, profil ONU).
   - ID pelanggan yang sudah dipakai pelanggan lain ditolak per baris.
5. Aturan isi kolom (baris yang melanggar **dilewati dengan alasan**, tidak lagi diganti nilai bawaan diam-diam):
   - **No. HP** wajib untuk pelanggan baru. Nomor yang kehilangan angka 0 di depan (mis. `81234…`) ditambahkan otomatis.
   - **tipe_tagihan, metode_tagihan, status, kenakan_ppn, tipe_diskon**: kosong = nilai bawaan; nilai yang tidak dikenal ditolak.
   - **nilai_diskon** boleh berformat Indonesia: `20.000` = Rp 20.000. Diskon persen maksimal 100; diskon nominal tidak boleh melebihi harga paket.
   - **tgl_aktivasi**: `YYYY-MM-DD` atau `DD/MM/YYYY`; tanggal yang tidak dikenali ditolak.
   - **latitude/longitude**: titik atau koma desimal (`-6,2088`) diterima; di luar rentang ditolak.
   - **ID pelanggan** kosong dibuat otomatis tanpa bentrok. ID yang sudah dipakai pelanggan lain, username atau NIK yang muncul dua kali di file, dan NIK milik pelanggan lain ditolak.
   - **NIK** dari kolom berformat angka diabaikan (dengan catatan) karena digit terakhirnya sudah dibulatkan Excel.
6. Pelanggan **baru** berstatus *suspended* tetap terisolir (tidak menjadi "menunggu aktivasi"); berstatus *waiting_activation* mendapat tiket aktivasi.
7. Hasil impor menampilkan baris yang dilewati/gagal beserta alasannya, dan **catatan** untuk baris yang tetap diproses.

---
*Tips: isi koordinat dengan benar untuk memudahkan teknisi dan pemetaan jaringan.*
