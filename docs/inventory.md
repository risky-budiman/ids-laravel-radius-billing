# Inventaris & Aset

> **Lokasi Menu:** `Inventory & Assets`

Modul Inventaris memungkinkan Anda melacak stok perangkat keras dan aset perusahaan yang digunakan dalam operasional ISP.

## Manajemen Stok (Items)

Anda dapat mengelola barang di menu **Inventory > Items**.

### 1. Data Barang
- **Item Name**: Nama perangkat (contoh: "ZTE F609 ONT", "Kabel Dropcore 1 Core").
- **Category**: Kelompokkan berdasarkan jenis (Hardware, Cables, Tools).
- **Unit**: Satuan barang (Pcs, Meter, Roll).

### 2. Monitoring Stok
Sistem akan memberikan peringatan jika stok sudah mencapai batas minimum (**Low Stock Alert**). Segera lakukan pengadaan (Procurement) jika stok mulai menipis agar proses instalasi pelanggan tidak terhambat.

## Purchase Orders (Pengadaan Barang)

Untuk melakukan pengadaan barang dari supplier, Anda harus menggunakan menu **Warehouse > Purchase Orders**.

1. **Pembuatan PO**: Saat membuat PO, Anda dapat memasukkan daftar barang yang dipesan beserta kuantitas dan harga belinya.
2. **Penerimaan & Integrasi Otomatis**: Saat PO disimpan (status *Received*), sistem secara **otomatis** akan:
   - Menambahkan stok fisik barang ke dalam sistem Inventory.
   - Mengirim notifikasi otomatis ke tim Finance (Admin/Kasir) untuk memberitahukan adanya pengadaan barang baru.
   - Mencatat otomatis ke dalam Jurnal Akuntansi (Debit: Persediaan Gudang, Kredit: Hutang Usaha / *Accounts Payable*).

## Serial Number (SN) Tracking

Untuk perangkat elektronik seperti ONT atau Router, sangat disarankan untuk mencatat **Serial Number (SN)** saat barang masuk.
- Memudahkan proses klaim garansi.
- Memudahkan pelacakan perangkat mana yang terpasang di rumah pelanggan tertentu.

## Audit & Laporan

Lakukan audit berkala melalui menu **Inventory > Audit** untuk mencocokkan stok fisik di gudang dengan stok yang ada di sistem. Setiap pengambilan barang oleh teknisi harus tercatat melalui fitur **Stock Out**.

---
*Tips: Gunakan barcode scanner (jika tersedia) untuk mempercepat input Serial Number perangkat baru.*
