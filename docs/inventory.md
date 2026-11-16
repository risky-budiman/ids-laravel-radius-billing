# Gudang & Aset

> **Lokasi Menu:** `Inventaris & Aset › Gudang` (Stok Barang, Pemasok, Pesanan Pembelian, Kategori, Barang Masuk, Barang Keluar, Audit Barang Keluar) · `Aset Tetap`

## Barang & Stok

- Setiap barang punya kategori, satuan dan **stok minimum** (peringatan stok menipis).
- **Barang ber-SN** (ONT, router): setiap unit masuk/keluar wajib punya **Serial Number** unik; unit yang dikeluarkan harus milik barang itu dan berstatus tersedia. **Barang tanpa SN** (kabel, konektor) dihitung per jumlah dan tidak bisa keluar melebihi saldo.
- Setiap perubahan stok tercatat sebagai **mutasi** (masuk / keluar / penyesuaian) di halaman barang.

### Status unit (barang ber-SN)

| Status | Arti |
|---|---|
| Siap pakai · Retur · Hasil tarik | Tersedia untuk dipasang (kecuali kondisinya *Rusak*). |
| Terpasang | Di rumah pelanggan (tertaut ke pelanggan). |
| Rusak · Hilang | Tidak tersedia. |

Kondisi unit: Baru, Bekas, Rekondisi, Rusak. Status unit bisa diubah dari halaman barang.

## Barang Masuk

Sumber pembayaran dipilih saat barang masuk:
- **Rekening Kas & Bank**: saldo rekening berkurang; jurnal Dr Persediaan (1105) & PPN Masukan (1106) / Cr rekening.
- **Hutang supplier** (bawaan): Dr Persediaan / Cr Hutang Usaha.
- **Tanpa pembelian** (stok awal, hibah, perangkat lama di pelanggan): tanpa jurnal kas.

**Pesanan Pembelian (PO)**: daftar barang, jumlah & harga per pemasok; saat diterima, stok bertambah lewat jalur Barang Masuk yang sama.

## Barang Keluar & Pemasangan

- **Wizard aktivasi** mengeluarkan modem & material ke pelanggan: modem tetap milik perusahaan dan pindah dari Persediaan ke *Peralatan Jaringan terpasang*; material habis pakai dibebankan (akun 5105).
- **Dismantle**: perangkat yang ditarik kembali ke stok sebagai *Hasil tarik*; yang ditandai rusak menjadi *Rusak* (tidak masuk stok siap pakai).
- **Audit Barang Keluar**: riwayat semua pengeluaran barang (siapa, kapan, ke mana).

## Stock Opname

Dari halaman barang → **Stock opname**:
- **Barang ber-SN**: centang unit yang **ada** di gudang — unit tersedia yang tidak dicentang ditandai **Hilang**; SN fisik yang belum tercatat bisa ditempel di kolom *SN ditemukan* untuk didaftarkan.
- **Barang tanpa SN**: isi **jumlah fisik**; selisihnya dicatat sebagai mutasi **penyesuaian** (+/−).

Hasilnya diringkas (mis. "2 SN ditandai hilang, 1 SN didaftarkan"). Halaman barang menampilkan peringatan bila catatan mutasi tidak cocok dengan jumlah unit SN — lakukan stock opname untuk merapikannya.

## Aset Tetap

Aset jangka panjang (OLT, server, kendaraan) dengan harga beli & umur ekonomis; penyusutan garis lurus dijurnal otomatis setiap akhir bulan sampai nilai sisa.

**Aset lama** (dibeli sebelum memakai sistem): isi tanggal beli asli dan **akumulasi penyusutan** sampai hari ini. Pendaftaran aset tetap tidak memotong saldo kas (pembeliannya dianggap sudah terjadi di masa lalu).

---
*Tips: gunakan pemindai barcode untuk mempercepat input SN.*
