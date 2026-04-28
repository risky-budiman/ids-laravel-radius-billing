# Sistem Akuntansi

> **Lokasi Menu:** `Accounting`

Radius Billing mengintegrasikan modul akuntansi standar profesional untuk memastikan setiap transaksi keuangan (pendapatan, pengeluaran, mutasi kas) tercatat dengan benar.

## Bagan Akun (Chart of Accounts)

Bagan Akun atau CoA adalah pondasi laporan keuangan Anda. Anda dapat mengelolanya di menu **Accounting > CoA**.

### Tipe Akun
1. **Asset**: Kas, Bank, Piutang, Inventaris.
2. **Liability**: Hutang operasional, Hutang pajak.
3. **Equity**: Modal usaha.
4. **Income**: Pendapatan langganan internet, Biaya instalasi.
5. **Expense**: Biaya listrik, Gaji karyawan, Biaya bandwidth.

## Jurnal Umum (Journals)

Meskipun sistem membuat jurnal otomatis untuk setiap invoice, Anda dapat membuat jurnal manual di menu **Accounting > Journals** untuk transaksi seperti:
- Pembelian inventaris kantor.
- Pembayaran biaya operasional bulanan.
- Penyesuaian saldo.

## Laporan Keuangan

Sistem menyediakan 4 laporan utama yang dapat dipantau kapan saja:

### 1. General Ledger (Buku Besar)
Menampilkan detail mutasi setiap akun CoA dalam periode tertentu. Anda dapat memfilter berdasarkan satu akun atau melihat **Seluruh Akun** sekaligus untuk keperluan audit.

### 2. Profit & Loss (Laba Rugi)
Menampilkan rangkuman pendapatan dikurangi biaya untuk mengetahui keuntungan bersih ISP Anda dalam periode bulanan atau tahunan.

### 3. Balance Sheet (Neraca)
Menampilkan posisi keuangan Anda (Harta = Hutang + Modal).

### 4. Closing Period (Tutup Buku)
Fitur untuk mengunci transaksi pada bulan sebelumnya agar tidak dapat diubah kembali, guna menjaga validitas laporan yang sudah dilaporkan.

## Manajemen Aset Tetap (Fixed Assets)

Modul Aset Tetap berada di bawah kategori `Inventory & Assets` di bilah sisi. Modul ini digunakan untuk melacak aset jangka panjang seperti OLT, Server, Kendaraan, atau Bangunan.

### Pencatatan Aset Baru & Penyusutan Otomatis
- Saat mendaftarkan aset, Anda akan mendefinisikan *Purchase Price* dan *Useful Life* (Umur Ekonomis).
- Sistem akan secara **otomatis** menghitung penyusutan menggunakan metode garis lurus (*straight-line*).
- Setiap akhir bulan, sistem akan **menjurnal otomatis** beban penyusutan dan memotong Nilai Buku Bersih (*Net Book Value*) aset tersebut hingga mencapai 0 atau *Salvage Value*.

### Kasus Khusus: Aset Lama (Existing Assets)
Jika Anda mulai menggunakan sistem ini saat perusahaan sudah berjalan dan memiliki aset yang sudah dibeli beberapa waktu lalu, Anda **tidak perlu khawatir** aset tersebut akan memotong kas saat ini. 
Pendaftaran Aset Tetap **tidak** memotong saldo kas/bank Anda (karena transaksi pengeluaran dianggap sudah terjadi di masa lalu).

Untuk mendaftarkan aset lama:
1. Isi tanggal pembelian sesuai faktur aslinya di masa lalu.
2. Masukkan **Penyusutan Berjalan / Accumulated Depreciation**. Ini adalah total nilai penyusutan yang sudah terjadi sejak tanggal beli hingga hari ini.
   - Contoh: OLT seharga Rp 12.000.000 dengan umur 5 tahun menyusut Rp 2.400.000 per tahun. Jika sudah dipakai 1 tahun, isi kolom penyusutan berjalan dengan angka `2400000`.
3. Simpan data. Nilai Buku Bersih (NBV) aset Anda akan langsung akurat dan sistem hanya akan meneruskan sisa penyusutannya.

---
*Penting: Pastikan saldo awal Kas & Bank diinput dengan benar saat pertama kali menggunakan sistem.*
