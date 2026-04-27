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
- [x] Menyiapkan library Telnet/SSH/SNMP pada backend Laravel (Pure PHP SNMP & phpseclib)
- [x] Membuat form Pendaftaran Perangkat OLT (IP, Port, SNMP Community, Login Credentials)
- [x] Membuat pemetaan ketersediaan port fisik PON pada OLT

### 2.2. Otomatisasi Provisioning (ZTE Workflow)
- [x] Script otomatisasi pembuatan DBA Profile & T-CONT sesuai paket speed
- [x] Script otomatisasi pembuatan Traffic Profile (SIR, PIR, CBS, PBS)
- [x] Script otomatisasi registrasi SN Modem ONU/ONT ke port PON
- [x] Script pemetaan GEM Port & vPort (VLAN binding)
- [x] Menggabungkan semua script di atas ke dalam Background Job (Horizon) agar berjalan berurutan saat klik "Tambah Pelanggan"
- [x] Script otomatisasi penghapusan ONU (Deprovisioning) dari OLT saat pelanggan dihapus
- [x] Script otomatisasi Suspend/Resume ONU (Isolir) saat jatuh tempo
- [x] Script otomatisasi Update Speed Profile (DBA) saat pelanggan ganti paket
- [ ] Proses otomatisasi penuh saat Aktivasi Pelanggan (Zero Touch Activation dari sisi sistem)

### 2.3. Dashboard Diagnostik OLT
- [x] Fitur deteksi otomatis Unconfigured ONU (Modem baru yang siap diregistrasi via SNMP)
- [x] Fitur Reboot ONT pelanggan secara remote langsung dari UI (SNMP SET)
- [x] Membaca metrik Redaman / Optical Power (Rx/Tx) via SNMP secara real-time

### 2.4. Ekstensi Vendor OLT Lain
- [ ] Integrasi otomatisasi Provisioning & Diagnostik untuk OLT merk HSGQ

---

## FASE 3: Pemetaan Geospasial & GIS
- [ ] CRUD Lengkap (Create, Read, Update, Delete) untuk Master Data Lokasi (Regional, STO, STB)
- [ ] Fitur penambahan Titik Koordinat (Latitude/Longitude) pada data Pelanggan
- [ ] Menampilkan peta sebaran pelanggan secara interaktif (Leaflet/Google Maps)
- [ ] Fitur Manajemen & Pemetaan infrastruktur ODC (Optical Distribution Cabinet)
- [ ] Fitur Manajemen & Pemetaan infrastruktur ODP (Optical Distribution Point)
- [ ] Sinkronisasi data ODC & ODP dengan Master Data Lokasi (Regional, STO, STB) yang sudah ada

---

## FASE 4: Enterprise Subscriber Management (Pengayaan Data Pelanggan)
Fase ini berfokus untuk melengkapi atribut *Subscriber* agar setara dengan standar ISP Enterprise, mencakup kelengkapan data infrastruktur fisik, KYC (Know Your Customer), dan manajemen CPE (Customer Premises Equipment).

### 4.1. Pemetaan Infrastruktur Fisik Pelanggan
- [ ] Penambahan field `odc_id`, `odp_id`, dan `odp_port` pada data pelanggan untuk melacak letak port fisik di lapangan.
- [ ] Penambahan field `cable_length` (Panjang Kabel Drop Core) untuk manajemen aset dan penghitungan estimasi redaman (Loss).
- [ ] Penambahan field `vlan_id` dan `static_ip` untuk pelanggan tipe Corporate/Dedicated.

### 4.2. Dokumen KYC & Verifikasi
- [ ] Upload foto identitas (KTP/NPWP) dan foto rumah/lokasi pemasangan (House Photo) saat teknisi melakukan aktivasi.
- [ ] Upload foto fisik Modem/Router (CPE).
- [ ] Fitur **OCR (Optical Character Recognition)** untuk mengekstrak Serial Number (SN) dan MAC Address secara otomatis dari foto modem yang diupload.
- [ ] Penambahan tipe pelanggan (Personal, Corporate, VIP) untuk prioritas penanganan tiket (SLA).

### 4.3. Manajemen Perangkat Pelanggan (CPE)
- [ ] Pencatatan detail Router/Access Point milik pelanggan (Merk, Tipe, MAC Address Router).
- [ ] Sistem *Binding MAC Address* terintegrasi antara data pelanggan dan RADIUS untuk keamanan tambahan.

---

## FASE 5: Mobile & Camera Integration (Inventory & Aktivasi)
- [ ] Fitur akses kamera langsung via Web/Android (WebRTC/HTML5 Camera) untuk scan Barcode/QR Code.
- [ ] **Scan SN Inventory:** Penginputan data barang masuk (Stock In) dan keluar (Stock Out) di gudang cukup dengan scan Serial Number menggunakan kamera HP.
- [ ] Proses *Zero Touch Provisioning* lapangan: Teknisi scan SN modem di rumah pelanggan -> otomatis mendaftarkan SN ke OLT tanpa ketik manual.

---

## FASE 6: Advanced Billing Automation & Collection
Setelah mengevaluasi sistem *automated billing* saat ini (`ProcessAutomatedBilling`), proses generasi tagihan dan isolir (suspend) sudah berjalan baik. Namun, untuk sekala *Enterprise*, prosedur penagihan perlu ditambahkan mekanisme perlindungan dan kenyamanan (*collection procedure*). 

**PENTING: Seluruh fitur aturan di Fase 6 ini bersifat OPTIONAL dan akan disatukan ke dalam 1 (satu) halaman Pengaturan Khusus (Advanced Billing Settings). Melalui halaman tunggal ini, Anda bisa meng-ON/OFF-kan semua aturan di bawah secara terpusat.**

### 6.1. Mekanisme Denda, Toleransi & Penggabungan Tagihan - *[Optional Toggle]*
- [ ] **Toleransi Keterlambatan (Grace Period):** Penambahan setting toleransi isolir (misal: H+3 dari Due Date baru diisolir, tidak langsung H+1). Bisa di ON/OFF.
- [ ] **Denda Keterlambatan (Late Fees):** Generate *invoice item* tambahan (denda) secara otomatis jika pembayaran melewati batas *Grace Period*. Bisa di ON/OFF.
- [ ] **Penundaan Pembayaran (Promise to Pay):** Fitur untuk pelanggan (via Portal/WA) untuk meminta perpanjangan waktu isolir (misal 3 hari) dengan alasan tertentu, yang jika dilanggar akan langsung diisolir tanpa ampun. Bisa di ON/OFF.
- [ ] **Merge Tagihan Menunggak (Invoice Consolidation):** Jika ada tagihan bulan sebelumnya yang belum dibayar, sistem dapat menggabungkan (marge) sisa tagihan tersebut ke dalam 1 lembar Invoice bulan berikutnya (akumulasi) atau tetap dibiarkan menjadi 2 lembar Invoice terpisah. Bisa di ON/OFF.

### 6.2. Notifikasi Penagihan Berjenjang (Dunning Process) - *[Optional Toggle]*
- [ ] **Auto-Reminder H-3 dan H-1:** Sistem otomatis mengirim pesan WhatsApp peringatan sebelum jatuh tempo.
- [ ] **Auto-Reminder H+1 (Overdue):** Sistem otomatis mengirim pesan peringatan bahwa layanan akan segera diisolir.

### 6.3. Penanganan Pelanggan Bandel (Auto-Dismantle) - *[Optional Toggle]*
- [ ] **Auto-Stop Billing:** Logika untuk **TIDAK** men-generate tagihan baru di bulan berikutnya jika status pelanggan masih *Suspended/Isolir*. (Bisa diatur di Setting Global).
- [ ] **Auto-Dismantle (Cabut Otomatis):** Jika pelanggan berada dalam status *Suspended* lebih dari X hari (misal 30 hari) tanpa pembayaran, sistem otomatis mengubah status menjadi `waiting_dismantle` dan **membuat Tiket Pekerjaan (Dismantle)** untuk teknisi lapangan menarik perangkat (CPE/Kabel).

### 6.4. Perhitungan Prorata Lanjutan (Advanced Proration) - *[Optional Toggle]*
- [ ] **Prorata Aktivasi Awal (Postpaid Cycle):** Penyempurnaan logika penghitungan prorata khusus untuk pembayaran pertama pelanggan dengan tipe tagihan *Cycle* (menghitung sisa hari di bulan pertama secara akurat hingga akhir bulan / tanggal 30 atau 31, layaknya sistem IndiHome). Bisa di ON/OFF.
- [ ] **Prorata Pergantian Paket (Upgrade/Downgrade):** Otomatisasi perhitungan selisih biaya (Prorata) saat pelanggan mengganti paket internet di tengah-tengah siklus tagihan berjalan, berlaku untuk semua tipe *Billing* (Prepaid maupun Postpaid). Bisa di ON/OFF.

---

## FASE 7: Refactoring & Perbaikan Logika Sistem (Dismantle & RADIUS)
Setelah melakukan audit pada sistem `CustomerActivationController`, ditemukan beberapa *logical flaw* (celah logika) pada proses Dismantle yang harus diperbaiki agar sistem benar-benar terintegrasi (End-to-End).

### 7.1. Perbaikan Logika "Request Dismantle"
- [ ] **Bugfix Pembuatan Tiket:** Saat ini aksi `requestDismantle` hanya mengubah status pelanggan, tetapi **belum ada kode untuk membuat Tiket Pekerjaan (Ticket)** di database. Kode pembuatan tiket otomatis harus ditambahkan.

### 7.2. Perbaikan Logika "Process Dismantle" (Eksekusi Cabut)
- [ ] **Otomatisasi Deprovisioning OLT:** Saat teknisi mengeksekusi Dismantle (menarik barang), sistem harus otomatis memanggil `DeprovisionOnuJob` untuk menghapus konfigurasi modem di sisi OLT ZTE (agar port OLT tidak penuh dengan status LOS/Offline).
- [ ] **Pembersihan Data RADIUS:** Sistem harus otomatis menghapus/menonaktifkan akun PPPoE pelanggan di tabel `radcheck` dan memutuskan koneksi aktif (Kick via CoA) agar akun tidak bisa digunakan kembali meski kabel belum terpotong fisik.
- [ ] **Penanganan Bad Debt (Tagihan Menunggak):** Logika untuk menangani *Invoice* yang masih *Unpaid* saat pelanggan di-dismantle (apakah dihapus, dibiarkan, atau diubah statusnya menjadi *Bad Debt/Write-Off*).

---

## FASE 8: Refactoring & Perbaikan Logika Tiketing (Helpdesk)
Setelah melakukan pengecekan pada `TicketController`, ditemukan beberapa celah logika (Bypass/Loophole) yang berpotensi merusak integritas data Aktivasi, Inventory, dan Billing jika teknisi salah klik.

### 8.1. Penutupan Celah Bypass Status (Ticket Controller)
- [ ] **Mencegah Bypass Aktivasi:** Saat teknisi menutup *(close)* tiket `Aktivasi` dari halaman Manajemen Tiket, sistem saat ini langsung merubah status pelanggan menjadi *Active* **TANPA** melalui *Wizard* Pemasangan Modem dan **TANPA** men-generate tagihan *Prorata*. Tombol Close untuk tipe tiket Aktivasi harus **di-disable/di-redirect** agar mewajibkan lewat halaman *Wizard Activation* (`CustomerActivationController`).
- [ ] **Mencegah Bypass Dismantle:** Serupa dengan aktivasi, penutupan tiket `Dismantle` secara manual dari Manajemen Tiket hanya merubah status pelanggan, tetapi **TIDAK** mengembalikan barang ke gudang (Inventory IN). Aksi ini harus dicegah dan diarahkan ke *Wizard Dismantle*.

### 8.2. Pengayaan Fitur Helpdesk Enterprise
- [ ] **Sistem Pengambilan Tiket (Claim/Assign):** Admin dapat menugaskan *(assign)* tiket langsung ke teknisi tertentu. Sebaliknya, tiket yang belum bertuan *(Unassigned)* akan masuk ke dalam *Pool* sehingga teknisi mana pun yang sedang *standby* bisa secara proaktif mengambil/klaim *(Take Ticket)* tiket tersebut.
- [ ] **Sistem Komentar/Log Aktivitas (Ticket Replies):** Menambahkan fitur percakapan/komentar *(Thread)* di dalam halaman detail Tiket agar Admin dan Teknisi bisa saling bertukar laporan progres (lengkap dengan lampiran foto perbaikan).
- [ ] **Notifikasi Tertarget:** Memperbaiki logika Notifikasi pembuatan tiket yang saat ini dikirim ke *semua* pengguna sistem (termasuk kasir/sales). Notifikasi seharusnya hanya dikirim ke role *Admin* dan *Teknisi* yang di-assign.
- [ ] **SLA & Escalation:** Penambahan timer SLA (Service Level Agreement). Jika tiket gangguan tidak diselesaikan dalam 1x24 jam, tiket akan berstatus *Overdue* dan muncul peringatan merah ke Admin.

---

## FASE 9: Enterprise ERP & Advanced Finance
Berdasarkan evaluasi modul `AccountingService` saat ini, sistem *Double-Entry Bookkeeping* dasar sudah berjalan dengan sangat baik (sudah mendukung integrasi jurnal tagihan, inventory, dan fitur Tutup Buku). Namun, untuk menunjang skala keuangan yang lebih besar, diperlukan beberapa modul tambahan tingkat *Enterprise*.

### 9.1. Otomasi Rekonsiliasi Bank (Mutasi)
- [ ] **API Integrasi Mutasi Bank:** Mengintegrasikan mutasi bank otomatis (seperti Moota, Xendit, atau Midtrans) untuk *Auto-Matching* pembayaran pelanggan dengan *Invoice* sehingga kasir tidak perlu memverifikasi transfer secara manual.
- [ ] **CSV Bank Statement Import:** Fitur untuk meng-upload mutasi rekening bank dari Excel/CSV dan melakukan proses rekonsiliasi manual/semi-otomatis untuk mencocokkan saldo sistem dengan saldo riil bank.

### 9.2. Manajemen Aset Tetap & Penyusutan (Fixed Assets & Depreciation)
- [ ] **Master Aset Tetap (Fixed Assets):** Pencatatan barang modal bernilai tinggi (Server, OLT, Tiang, Kendaraan Kantor) beserta umur ekonomisnya.
- [ ] **Auto-Jurnal Penyusutan (Depreciation):** Sistem otomatis membuat jurnal Penyusutan Aset setiap akhir bulan (metode *Straight-Line* / Garis Lurus) untuk memotong nilai buku aset dan mencatat beban penyusutan secara otomatis.

### 9.3. Hutang Usaha & Pembelian Kredit (Accounts Payable)
- [ ] **Purchase Order (PO) & Vendor Bills:** Saat ini pembelian Inventory di sistem selalu dianggap tunai (memotong *Kas Utama*). Perlu ditambahkan sistem PO (*Term of Payment*, misal Net 30) agar sistem bisa menjurnalnya sebagai **Hutang Usaha (Accounts Payable)** terlebih dahulu sebelum dibayar lunas.
- [ ] **Multi-Currency (Selisih Kurs):** Fitur untuk menghitung Laba/Rugi Selisih Kurs *(Foreign Exchange Gain/Loss)* secara otomatis apabila ada pembelian *Bandwidth* atau perangkat dari luar negeri menggunakan satuan USD.

---

## FASE 10: Enterprise NOC & Network Monitoring
Setelah melakukan pengecekan pada logika `NocController`, ditemukan **Celah Performa (N+1 Query Timeout)** yang sangat fatal jika jumlah pelanggan sudah mencapai ratusan/ribuan. Fase ini bertujuan mengubah NOC menjadi sistem *Monitoring* yang asinkron, cepat, dan proaktif.

### 10.1. Refactoring SNMP Polling (Mencegah Timeout)
- [ ] **Pemindahan Polling ke Background Job:** Saat ini halaman `NOC Signals` memanggil data Redaman (Optical Power) secara sinkron (satu-per-satu) ke OLT via SNMP saat halaman dimuat. Jika ada 1000 pelanggan, halaman akan *Timeout/Crash*. Ini harus diubah menjadi sistem **Background Polling** (berjalan tiap 5/10 menit via cron) yang melakukan *SNMP Bulk Walk* dan menyimpannya ke *Database Cache*, sehingga halaman web hanya melakukan *Read Database*.
- [ ] **Auto-Update Dashboard Stats:** Memperbaiki angka statistik `unconfigured` dan `critical_signals` pada Dashboard NOC yang saat ini masih *hardcode* 0 agar membaca dari *Database Cache*.

### 10.2. Sistem Peringatan Dini (Proactive Alerting)
- [ ] **Telegram/WA NOC Bot Integration:** Sistem akan secara otomatis mengirim peringatan *(Alert)* ke Grup Telegram/WA NOC jika terdeteksi OLT *Offline* (Down), port PON *Down* massal, atau redaman pelanggan tiba-tiba memburuk melewati ambang batas kritis (misal < -27 dBm).
- [ ] **Grafik Kualitas Jaringan (RRD/Grafana-like):** Menyimpan histori fluktuasi sinyal OLT Rx/Tx setiap hari untuk digambarkan menjadi grafik tren redaman pelanggan, sehingga teknisi bisa melihat kapan kabel mulai rusak sebelum putus sepenuhnya.

