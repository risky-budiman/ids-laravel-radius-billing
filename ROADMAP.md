# Blueprint & Roadmap Pengembangan Lanjutan ISP

Dokumen ini merupakan kerangka kerja (blueprint) tingkat tinggi untuk pengembangan aplikasi manajemen ISP di masa mendatang. Anda dapat menandai kotak centang di bawah ini dengan tanda `x` (menjadi `[x]`) untuk melacak progres pengerjaan.

---

## FASE 1: Modul Administrasi Keuangan & Akuntansi (Mini ERP)
Tujuan utama fase ini adalah mengubah sistem dari sekadar penagihan (Billing) menjadi sistem yang mampu melacak kesehatan finansial, aliran dana (Cash Flow), dan kewajiban pajak perusahaan.

### 1.1. Manajemen Kas & Bank (Treasury Management)
- [x] Membuat struktur database untuk Master Rekening (Bank, Kas Kecil, Giro, Payment Gateway)
- [x] Membuat fitur CRUD Master Rekening di Web UI
- [x] Mengembangkan fitur Mutasi & Transfer antar kas/bank
- [x] Membuat sistem Rekonsiliasi Bank
- [x] **Manajemen Saldo Operasional Staff:** Menghubungkan rekening kas dengan User (Teknisi/Admin)
- [x] **Modul Pengeluaran Biaya (Expenses):** Fitur mencatat pengeluaran (Bensin, Makan, dll) yang memotong saldo staff
- [x] **Hak Akses Saldo (Privacy):** Staff hanya bisa melihat saldo miliknya sendiri, Administrator melihat total.
- [x] **Manajemen Kasir & Setor Dana:** Kasir menampung dana tunai dan memiliki fitur setor ke perusahaan.

### 1.2. Buku Besar & Jurnal (General Ledger)
- [x] **Merancang standarisasi Chart of Accounts (CoA):** Pembuatan master akun (Aset, Modal, Beban, dll)
- [x] Membuat fitur pencatatan Jurnal Umum Manual (Double-Entry Bookkeeping)
- [x] Mengembangkan algoritma Auto-Journal untuk Invoice yang dibayar pelanggan
- [x] Mengembangkan algoritma Auto-Journal untuk pengeluaran Kas Kecil/Inventory
- [x] **Laporan Keuangan Dasar:** Pembuatan Neraca (Balance Sheet) & Laba Rugi (Profit & Loss)
- [x] **Fitur Tutup Buku:** Proses closing bulanan/tahunan dan penguncian periode transaksi.


### 1.3. Perpajakan (Taxation)
- [x] **Master Data Pajak:** Pembuatan Master Data Pajak Dinamis (Misal: PPN 11%)
- [x] **Tax Output Calculation:** Mengimplementasikan penghitungan Tax Output (Pajak Keluaran) otomatis pada tagihan
- [x] **Tax Input Implementation:** Mengimplementasikan form *Tax Input* (Pajak Masukan) untuk pembelian inventaris
- [x] **Tax Report Summary:** Membuat rangkuman Laporan Pajak Masa bulanan
- [x] **Flexible Tax Rules:** Implementasi aturan PPN Global vs Per-Pelanggan (Optional PPN)

### 1.4. Laporan Keuangan Finansial (Financial Reports)
- [x] Membuat halaman Laporan Buku Besar (Ledger Report) per CoA
- [x] Membuat Laporan Arus Kas (Cash Flow) - Metode Langsung
- [x] Implementasi ekspor laporan ke Excel / PDF

---

## FASE 2: Integrasi Jaringan Terpusat (SNMP & OLT ZTE)
Fase ini bertujuan untuk mencapai status *Zero Touch Provisioning*, di mana sistem akan langsung melakukan konfigurasi ke perangkat OLT secara otomatis saat pendaftaran pelanggan.

### 2.1. Infrastruktur Komunikasi & Master Data OLT
- [ ] Menyiapkan library Telnet/SSH/SNMP pada backend Laravel (atau integrasi Python microservice)
- [ ] Membuat form Pendaftaran Perangkat OLT (IP, Port, SNMP Community, Login Credentials)
- [ ] Membuat pemetaan ketersediaan port fisik PON pada OLT

### 2.2. Otomatisasi Provisioning (ZTE Workflow)
- [ ] Script otomatisasi pembuatan DBA Profile & T-CONT sesuai paket speed
- [ ] Script otomatisasi pembuatan Traffic Profile (SIR, PIR, CBS, PBS)
- [ ] Script otomatisasi registrasi SN Modem ONU/ONT ke port PON
- [ ] Script pemetaan GEM Port & vPort (VLAN binding)
- [ ] Menggabungkan semua script di atas ke dalam *Background Job* (Horizon) agar berjalan berurutan saat klik "Tambah Pelanggan"

### 2.3. Dashboard Diagnostik OLT
- [ ] Fitur deteksi otomatis *Unconfigured ONU* (Modem baru yang siap diregistrasi)
- [ ] Fitur *Reboot ONT* pelanggan secara remote langsung dari UI
- [ ] Membaca metrik Redaman / Optical Power (Rx/Tx) via SNMP secara real-time
