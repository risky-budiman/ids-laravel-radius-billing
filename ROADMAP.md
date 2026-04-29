# Blueprint & Roadmap Pengembangan Lanjutan ISP

Dokumen ini merupakan kerangka kerja (blueprint) tingkat tinggi untuk pengembangan aplikasi manajemen ISP di masa mendatang. 

### Standar & Referensi Teknis:
- **Finance Standard:** Mengacu pada [ISP_COA_Billing.md](file:///d:/AI%20Code/laravel-radius/ISP_COA_Billing.md) untuk struktur akuntansi produksi.
- **Network Standard:** Zero Touch Provisioning (ZTE/Mikrotik).
- **Security:** Advanced RBAC & Audit Trail.

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
- [x] CRUD Lengkap (Create, Read, Update, Delete) untuk Master Data Lokasi (Regional, STO, STB)
- [x] Fitur penambahan Titik Koordinat (Latitude/Longitude) pada data Pelanggan (Selesai: Diperhalus di Wizard Aktivasi)
- [x] Menampilkan peta sebaran pelanggan secara interaktif (Leaflet/Google Maps)
- [x] Fitur Manajemen & Pemetaan infrastruktur ODC (Optical Distribution Cabinet)
- [x] Fitur Manajemen & Pemetaan infrastruktur ODP (Optical Distribution Point)
- [x] Sinkronisasi data ODC & ODP dengan Master Data Lokasi (Regional, STO, STB) yang sudah ada

---

## FASE 4: Enterprise Subscriber Management (Pengayaan Data Pelanggan)
Fase ini berfokus untuk melengkapi atribut *Subscriber* agar setara dengan standar ISP Enterprise, mencakup kelengkapan data infrastruktur fisik, KYC (Know Your Customer), dan manajemen CPE (Customer Premises Equipment).

### 4.1. Pemetaan Infrastruktur Fisik Pelanggan
- [x] Penambahan field `odc_id`, `odp_id`, dan `odp_port` pada data pelanggan untuk melacak letak port fisik di lapangan. (Selesai: Terintegrasi di Wizard Aktivasi)
- [x] Penambahan field `cable_length` (Panjang Kabel Drop Core) untuk manajemen aset dan penghitungan estimasi redaman (Loss). (Selesai: Terintegrasi di Wizard Aktivasi)
- [x] Penambahan field `vlan_id` dan `static_ip` untuk pelanggan tipe Corporate/Dedicated. (Selesai: Terintegrasi di Wizard Aktivasi)

### 4.2. Dokumen KYC & Verifikasi
- [x] Upload foto identitas (KTP/NPWP) dan foto rumah/lokasi pemasangan (House Photo) saat teknisi melakukan aktivasi. (Selesai: Terintegrasi di Wizard Aktivasi)
- [x] Upload foto fisik Modem/Router (CPE). (Selesai: Terintegrasi di Wizard Aktivasi)
- [x] Fitur **OCR (Optical Character Recognition)** untuk mengekstrak Serial Number (SN) dan MAC Address secara otomatis dari foto modem yang diupload. (Selesai: Terintegrasi dengan Tesseract.js di Wizard Aktivasi)
- [x] Penambahan tipe pelanggan (Personal, Corporate, VIP) untuk prioritas penanganan tiket (SLA).
- [x] **Kolom Deskripsi Tambahan:** Penambahan *field* `description` (catatan khusus) pada data subscriber untuk menyimpan keterangan detail/kronologi khusus yang tidak tercakup pada *field* standar. (Selesai: Terintegrasi di Wizard Aktivasi)

### 4.3. Manajemen Perangkat Pelanggan (CPE)
- [x] Pencatatan detail Router/Access Point milik pelanggan (Merk, Tipe, MAC Address Router). (Selesai: Terintegrasi di Wizard Aktivasi)
- [x] Sistem *Binding MAC Address* terintegrasi antara data pelanggan dan RADIUS untuk keamanan tambahan.

---

## FASE 5: Mobile & Camera Integration (Inventory & Aktivasi)
- [x] Fitur akses kamera langsung via Web/Android (WebRTC/HTML5 Camera) untuk scan Barcode/QR Code. (Selesai: Komponen BarcodeScanner Terintegrasi)
- [x] **Scan SN Inventory:** Penginputan data barang masuk (Stock In) dan keluar (Stock Out) di gudang cukup dengan scan Serial Number menggunakan kamera HP.
- [x] Proses *Zero Touch Provisioning* lapangan: Teknisi scan SN modem di rumah pelanggan -> otomatis mendaftarkan SN ke OLT tanpa ketik manual.

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
- [x] **Bugfix Pembuatan Tiket:** Saat ini aksi `requestDismantle` hanya mengubah status pelanggan, tetapi **belum ada kode untuk membuat Tiket Pekerjaan (Ticket)** di database. Kode pembuatan tiket otomatis harus ditambahkan.

### 7.2. Perbaikan Logika "Process Dismantle" (Eksekusi Cabut)
- [x] **Otomatisasi Deprovisioning OLT:** Saat teknisi mengeksekusi Dismantle (menarik barang), sistem harus otomatis memanggil `DeprovisionOnuJob` untuk menghapus konfigurasi modem di sisi OLT ZTE (agar port OLT tidak penuh dengan status LOS/Offline).
- [x] **Pembersihan Data RADIUS:** Sistem harus otomatis menghapus/menonaktifkan akun PPPoE pelanggan di tabel `radcheck` dan memutuskan koneksi aktif (Kick via CoA) agar akun tidak bisa digunakan kembali meski kabel belum terpotong fisik.
- [x] **Penanganan Bad Debt (Tagihan Menunggak):** Sesuai instruksi, tagihan *Unpaid* dibiarkan tetap ada sebagai tunggakan.

---

## FASE 8: Refactoring & Perbaikan Logika Tiketing (Helpdesk)
Setelah melakukan pengecekan pada `TicketController`, ditemukan beberapa celah logika (Bypass/Loophole) yang berpotensi merusak integritas data Aktivasi, Inventory, dan Billing jika teknisi salah klik.

### 8.1. Penutupan Celah Bypass Status (Ticket Controller)
- [x] **Mencegah Bypass Aktivasi:** Saat teknisi menutup *(close)* tiket `Aktivasi` dari halaman Manajemen Tiket, sistem saat ini langsung merubah status pelanggan menjadi *Active* **TANPA** melalui *Wizard* Pemasangan Modem dan **TANPA** men-generate tagihan *Prorata*. Tombol Close untuk tipe tiket Aktivasi harus **di-disable/di-redirect** agar mewajibkan lewat halaman *Wizard Activation* (`CustomerActivationController`).
- [x] **Mencegah Bypass Dismantle:** Serupa dengan aktivasi, penutupan tiket `Dismantle` secara manual dari Manajemen Tiket hanya merubah status pelanggan, tetapi **TIDAK** mengembalikan barang ke gudang (Inventory IN). Aksi ini harus dicegah dan diarahkan ke *Wizard Dismantle*.

### 8.2. Pengayaan Fitur Helpdesk Enterprise
- [x] **Sistem Pengambilan Tiket (Claim/Assign):** Admin dapat menugaskan *(assign)* tiket langsung ke teknisi tertentu. Sebaliknya, tiket yang belum bertuan *(Unassigned)* akan masuk ke dalam *Pool* sehingga teknisi mana pun yang sedang *standby* bisa secara proaktif mengambil/klaim *(Take Ticket)* tiket tersebut.
- [x] **Sistem Komentar/Log Aktivitas (Ticket Replies):** Menambahkan fitur percakapan/komentar *(Thread)* di dalam halaman detail Tiket agar Admin dan Teknisi bisa saling bertukar laporan progres (lengkap dengan lampiran foto perbaikan).
- [x] **Notifikasi Tertarget:** Memperbaiki logika Notifikasi pembuatan tiket yang saat ini dikirim ke *semua* pengguna sistem (termasuk kasir/sales). Notifikasi seharusnya hanya dikirim ke role *Admin* dan *Teknisi* yang di-assign.
- [x] **SLA & Escalation:** Penambahan timer SLA (Service Level Agreement). Jika tiket gangguan tidak diselesaikan dalam 1x24 jam, tiket akan berstatus *Overdue* dan muncul peringatan merah ke Admin.

### 8.3. Standardisasi Penomoran & Kode Tiket (Prefix Management)
- [x] **Dynamic Ticket Prefixing:** Menambahkan sistem prefix (kode depan) otomatis untuk setiap tipe tiket (`TT`, `AO`, `DO`, `RL`, `MT`).
- [x] **Configurable Prefix Settings:** Membuat halaman pengaturan global agar Admin bisa mengubah atau mengaktifkan/menonaktifkan kode prefix ini secara dinamis sesuai kebutuhan operasional.
- [x] **Ticket Numbering Logic:** Mengubah algoritma penomoran tiket agar menggunakan format profesional (Misal: `TT/20260427/001`) agar lebih mudah diidentifikasi dan diarsipkan.

### 8.4. Manajemen Estimasi Waktu & SLA Dinamis (Dynamic SLA)
- [ ] **Target Estimasi Pengerjaan (ETA):** Menambahkan field `estimated_completion_date` pada tiket. Penentuan SLA tidak lagi kaku (misal fix 1x24 jam), melainkan berdasarkan estimasi waktu yang disepakati atau ditentukan saat tiket diklaim.
- [ ] **Revisi Estimasi (Pending Reason):** Fitur bagi teknisi untuk memodifikasi/memperpanjang estimasi penyelesaian jika tiket harus berstatus `pending` (tertunda) dengan syarat harus memasukkan alasan yang valid (misal: "Menunggu perangkat pengganti" atau "Pelanggan sedang tidak di rumah").
- [ ] **Perhitungan SLA Berbasis Estimasi:** Indikator *Overdue* (Keterlambatan) dihitung berdasarkan `estimated_completion_date` terbaru, bukan murni dari waktu tiket dibuat.
- [ ] **Otomatisasi Tiket Aktivasi (Pasang Baru):** Saat mendaftarkan *Subscriber* baru, Admin wajib memasukkan "Estimasi Tanggal Aktivasi". Setelah teknisi menyelesaikan proses di *Wizard Aktivasi*, sistem otomatis mengubah status tiket menjadi *Solved/Closed*.
- [ ] **Otomatisasi Tiket Dismantle (Cabut):** Saat melakukan *Request Dismantle*, Admin wajib menunjuk rujukan teknisi (Assignee) dan "Estimasi Waktu Pencabutan". Saat teknisi menyelesaikan proses penarikan alat, tiket Dismantle otomatis diselesaikan (*Solved/Closed*).

---

## FASE 9: Enterprise ERP & Advanced Finance
Berdasarkan evaluasi modul `AccountingService` saat ini, sistem *Double-Entry Bookkeeping* dasar sudah berjalan dengan sangat baik (sudah mendukung integrasi jurnal tagihan, inventory, dan fitur Tutup Buku). Namun, untuk menunjang skala keuangan yang lebih besar, diperlukan beberapa modul tambahan tingkat *Enterprise*.

### 9.1. Otomasi Rekonsiliasi Bank (Mutasi)
- [ ] **API Integrasi Mutasi Bank:** Mengintegrasikan mutasi bank otomatis (seperti Moota, Xendit, atau Midtrans) untuk *Auto-Matching* pembayaran pelanggan dengan *Invoice* sehingga kasir tidak perlu memverifikasi transfer secara manual.
- [ ] **CSV Bank Statement Import:** Fitur untuk meng-upload mutasi rekening bank dari Excel/CSV dan melakukan proses rekonsiliasi manual/semi-otomatis untuk mencocokkan saldo sistem dengan saldo riil bank.

### 9.2. Manajemen Aset Tetap & Penyusutan (Fixed Assets & Depreciation)
- [x] **Master Aset Tetap (Fixed Assets):** Pencatatan barang modal bernilai tinggi (Server, OLT, Tiang, Kendaraan Kantor) beserta umur ekonomisnya.
- [ ] **Auto-Jurnal Penyusutan (Depreciation):** Sistem otomatis membuat jurnal Penyusutan Aset setiap akhir bulan (metode *Straight-Line* / Garis Lurus) untuk memotong nilai buku aset dan mencatat beban penyusutan secara otomatis.

### 9.3. Hutang Usaha & Pembelian Kredit (Accounts Payable)
- [ ] **Purchase Order (PO) & Vendor Bills:** Saat ini pembelian Inventory di sistem selalu dianggap tunai (memotong *Kas Utama*). Perlu ditambahkan sistem PO (*Term of Payment*, misal Net 30) agar sistem bisa menjurnalnya sebagai **Hutang Usaha (Accounts Payable)** terlebih dahulu sebelum dibayar lunas.
- [ ] **Multi-Currency (Selisih Kurs):** Fitur untuk menghitung Laba/Rugi Selisih Kurs *(Foreign Exchange Gain/Loss)* secara otomatis apabila ada pembelian *Bandwidth* atau perangkat dari luar negeri menggunakan satuan USD.

### 9.4. Perbaikan Tampilan Bagan Akun (CoA Bugfix) [SELESAI]
- [x] **Manajemen Akun (CRUD):** Implementasi fitur Update dan Delete untuk memperbaiki kesalahan input (Dilengkapi validasi pencegahan hapus jika sudah ada transaksi/anak akun).
- [x] **Fix Recursive Account Display:** Berhasil memperbaiki masalah tampilan list akun yang tidak muncul semua jika memiliki hirarki lebih dari 2 level (Grandchildren) menggunakan recursive rendering.
  - [x] Update `ChartOfAccountController` untuk memuat data secara rekursif dengan pengelompokan parent.
  - [x] Refactor `index.blade.php` menggunakan Blade partial rekursif `_account_row.blade.php`.
  - [x] Sinkronisasi jumlah akun di header (Badge Summary) dengan jumlah riil di database.

### 9.5. Sinkronisasi Standar CoA Produksi (ISP_COA_Billing.md) [SELESAI]
Tujuan: Menyelaraskan seluruh struktur akun sistem dengan standar "Production Ready" yang telah didefinisikan di dokumen `ISP_COA_Billing.md`.
- [x] **Update ChartOfAccountSeeder:** Berhasil menyesuaikan seluruh daftar akun, kode, dan hirarki sesuai dokumen standar.
- [x] **Refactor Accounting Logic:** Berhasil memperbarui `AccountingService` agar merujuk pada kode akun baru (Piutang 1104, Persediaan 1105, PPN 1106).
- [x] **Data Migration Script:** Telah membuat migration script `2026_04_29_000000_sync_coa_to_production_standard.php` untuk pembaruan data secara aman (preserving history).
- [x] **Integrasi Biaya Payment Gateway:** Akun 5109 telah siap digunakan untuk pencatatan otomatis beban MDR.

---

## FASE 10: Enterprise NOC & Network Monitoring
Setelah melakukan pengecekan pada logika `NocController`, ditemukan **Celah Performa (N+1 Query Timeout)** yang sangat fatal jika jumlah pelanggan sudah mencapai ratusan/ribuan. Fase ini bertujuan mengubah NOC menjadi sistem *Monitoring* yang asinkron, cepat, dan proaktif.

### 10.1. Refactoring SNMP Polling (Mencegah Timeout)
- [x] **Pemindahan Polling ke Background Job:** Berhasil diubah menjadi sistem **Background Polling** via perintah `noc:poll` yang melakukan *SNMP Bulk Walk* dan menyimpannya ke *Database Cache*. (Selesai: Performa NOC meningkat drastis)
- [x] **Auto-Update Dashboard Stats:** Statistik `unconfigured` dan `critical_signals` pada Dashboard NOC kini membaca data real-time dari cache.

### 10.2. Sistem Peringatan Dini (Proactive Alerting)
- [x] **Telegram/WA NOC Bot Integration:** Berhasil diintegrasikan. Sistem akan otomatis mengirim peringatan ke Grup NOC jika redaman pelanggan kritis (< -27 dBm) dengan fitur *Throttling* untuk mencegah spam. (Selesai: Bot Proaktif Aktif)
- [x] **Grafik Kualitas Jaringan (RRD/Grafana-like):** Berhasil diimplementasikan menggunakan **Chart.js**. Sistem kini menyimpan histori sinyal (per jam) dan menampilkan tren redaman pelanggan selama 30 hari terakhir dengan fitur pembersihan otomatis (`noc:prune`). (Selesai: Analisis Tren Aktif)

---

## FASE 11: Customer Self-Service Portal (Client Area) [SELESAI]
Sistem *Enterprise* kini memiliki portal mandiri yang premium. Pelanggan dapat mengelola layanan mereka secara independen.
- [x] **Dashboard Pelanggan (Web/Mobile App):** Berhasil diimplementasikan. Pelanggan dapat melihat status internet real-time, sisa FUP, info paket, dan kualitas sinyal NOC.
- [x] **Self-Payment & Billing History:** Pelanggan dapat melihat riwayat pembayaran, mendownload invoice, dan melakukan pembayaran mandiri.
- [x] **Pembelian Add-on Booster (FUP Reset):** Fitur booster aktif. Pembelian otomatis mereset statistik RADIUS via CoA Disconnect.
- [x] **Open Ticket (Lapor Gangguan):** Pelanggan dapat membuat tiket gangguan dari portal, yang akan langsung masuk ke sistem antrean teknisi *(Helpdesk)* tanpa perlu *chat* manual ke WA Admin.


---

## FASE 12: Manajemen Kemitraan & Reseller (B2B)
Banyak ISP melakukan ekspansi wilayah dengan cara menggandeng mitra lokal (RT/RW Net atau Agen).
- [ ] **Sistem Komisi Mitra:** Menambahkan level *User Role* baru (Mitra/Agen). Sistem akan otomatis menghitung pembagian komisi (misal: 15% dari tagihan) setiap kali pelanggan yang berada di bawah naungan mitra tersebut membayar tagihan.
- [ ] **Pencairan Saldo (Withdrawal):** Fitur untuk mencatat dan menjurnal proses pencairan komisi bulanan dari kas utama perusahaan ke rekening agen/mitra.

---

## FASE 13: Fair Usage Policy (FUP) & Manajemen Kuota
- [ ] **Data Usage Tracking:** Mengumpulkan data pemakaian *Bandwidth* harian pelanggan (Download/Upload bytes) dari RADIUS *Accounting* (`radacct`) untuk ditampilkan di Dashboard Admin dan Dashboard Pelanggan.
- [ ] **Auto-Downgrade Speed (FUP):** Logika otomatis untuk menurunkan *Speed Profile* di OLT/Mikrotik (misal dari 50Mbps menjadi 10Mbps) ketika pelanggan telah melewati batas kuota FUP (misal 1 Terabyte) dalam bulan tersebut. Kecepatan akan di-*reset* normal kembali setiap tanggal 1.
- [ ] **Manual FUP Reset:** Tombol khusus untuk Admin agar dapat mereset kuota FUP pelanggan secara manual ke 0 di pertengahan bulan (misalnya jika pelanggan komplain atau membeli *add-on booster*).


---

## FASE 14: System Audit, Security & Compliance
Fase ini berfokus pada transparansi aktivitas sistem, pelacakan perubahan data secara mendalam, dan pemeliharaan integritas log untuk kebutuhan audit skala enterprise.

### 14.1. Pengayaan Fitur Activity Log
- [ ] **Advanced Filtering & Search:** Menambahkan fitur pencarian log berdasarkan User, Tipe Aksi (Create/Update/Delete), rentang tanggal, dan pencarian teks pada deskripsi perubahan.
- [ ] **Human-Readable Diffs:** Meningkatkan tampilan perbandingan data "Sebelum" dan "Sesudah" di UI agar lebih mudah dibaca oleh Admin non-teknis (menyembunyikan field teknis seperti ID/Timestamps secara cerdas).
- [ ] **System-Level Logging:** Memastikan setiap aksi yang dijalankan oleh Background Job (Automated Billing, OLT Polling) tercatat sebagai user "System" agar history perubahan data tetap utuh.

### 14.2. Manajemen Retensi & Keamanan Log
- [ ] **Log Retention Policy:** Fitur untuk mengatur berapa lama log aktivitas disimpan (misal: 90 hari). Sistem akan otomatis menghapus log yang sudah kedaluwarsa untuk menjaga performa database.
- [ ] **Audit Trail Export:** Fitur untuk mengekspor log aktivitas ke format Excel atau PDF sebagai laporan audit resmi perusahaan.
- [ ] **Tamper-Evident Logs:** Implementasi tanda tangan digital (Hash) sederhana pada setiap entry log untuk memastikan bahwa log tidak dimodifikasi secara manual di database.

---

## FASE 15: Fine-Grained Access Control & Dashboard Optimization
Fase ini bertujuan untuk meningkatkan keamanan dan relevansi data bagi setiap pengguna melalui sistem hak akses yang lebih detail (Permission-based) dan visualisasi Dashboard yang dipersonalisasi.

### 15.1. Sistem Hak Akses (Advanced RBAC/ACL)
- [ ] **Segregation of Duties (Finance vs Kasir):** Memisahkan role Kasir (hanya transaksi loket/pembayaran) dengan role Finance (jurnal, laporan keuangan, & audit) untuk meningkatkan integritas data keuangan.
- [ ] **Permission-Based Authorization:** Migrasi dari pengecekan Role *hardcode* ke sistem Permission (ACL). Contoh: User bisa memiliki role "Teknisi" tapi diberikan permission khusus edit_billing jika diperlukan.
- [ ] **Role & Permission Management UI:** Membuat antarmuka untuk Admin Utama dalam menentukan menu dan aksi apa saja yang boleh diakses oleh role tertentu (Checklist Permission).
- [ ] **Middleware Security Audit:** Menstandarisasi Middleware pada setiap Route agar sesuai dengan Matrix Hak Akses yang baru.

### 15.2. Personalisasi Dashboard (Role-Based Widgets)
- [ ] **Finance Dashboard:** Menampilkan widget khusus keuangan (Pendapatan hari ini, Invoice menunggak, Saldo Kas/Bank) untuk Role Kasir/Finance.
- [ ] **NOC & Technical Dashboard:** Menampilkan widget teknis (Status OLT, Jumlah Pelanggan Online, Tiket Gangguan yang belum selesai) untuk Role Teknisi/NOC.
- [ ] **Sales Dashboard:** Menampilkan statistik pertumbuhan pelanggan baru dan peta potensi wilayah untuk Role Sales.
- [ ] **Dashboard Widget Management:** Fitur bagi Administrator untuk menyusun (Drag & Drop) widget apa saja yang muncul di halaman depan secara global per role.

### 15.3. Audit & Security Enhancement
- [ ] **Action Authorization Logs:** Mencatat setiap kali ada percobaan akses ke menu yang tidak diizinkan (Unauthorized Access attempts) ke dalam log keamanan.
- [ ] **Sensitive Data Masking:** Fitur untuk menyembunyikan data sensitif (misal: nomor telepon lengkap, saldo bank tertentu) bagi role yang tidak memiliki otoritas tinggi.

---

## FASE 16: Advanced Accounting & Financial Integrity
Fase ini bertujuan untuk menstandarisasi modul keuangan agar setara dengan software akuntansi profesional (ERP) dan memudahkan audit keuangan eksternal.

### 16.1. Konfigurasi Akun Sistem (System Accounts Mapping)
- [ ] **Dynamic Account Mapping:** Menghilangkan hardcode kode akun (seperti 1103, 4101) di dalam kode program dan memindahkannya ke halaman pengaturan. Admin bisa menentukan akun mana yang bertindak sebagai "Piutang Pelanggan", "Pendapatan", dll.
- [ ] **Multi-Currency Baseline:** Persiapan struktur database untuk mendukung transaksi dalam mata uang asing (USD/SGD) dan perhitungan selisih kurs.

### 16.2. Otomatisasi Biaya Admin & MDR
- [ ] **Payment Gateway MDR Handling:** Fitur untuk memisahkan otomatis biaya admin (MDR) saat pembayaran diterima via Payment Gateway (Misal: Bayar 100rb, masuk Bank 98rb, Beban Admin 2rb) dalam satu jurnal.
- [ ] **Bank Fee Reconciliation:** Modul untuk mencatat beban administrasi bank bulanan secara kolektif saat proses rekonsiliasi.

### 16.3. Penomoran Voucher Jurnal (Voucher Numbering System)
- [ ] **Sequential Voucher Codes:** Implementasi nomor bukti jurnal otomatis dengan prefix yang bisa diatur (Misal: BKM untuk Bukti Kas Masuk, BKK untuk Bukti Kas Keluar, JVM untuk Jurnal Umum).
- [ ] **Digital Signature & Approval:** Alur persetujuan (Approval) jurnal manual oleh Manager Keuangan sebelum jurnal tersebut memposting saldo ke Buku Besar.

### 16.4. Budgeting & Reporting Lanjutan
- [ ] **Budget vs Actual Report:** Fitur untuk memasukkan target anggaran (Budget) per kategori biaya dan membandingkannya dengan realisasi pengeluaran bulanan.
- [ ] **Statement of Retained Earnings:** Menambahkan Laporan Perubahan Ekuitas untuk melengkapi 3 laporan keuangan utama yang sudah ada.

---

## FASE 17: Technical Documentation & Knowledge Base
Fase ini memastikan bahwa setiap fitur yang dikembangkan memiliki panduan teknis dan operasional yang lengkap untuk menjamin keberlanjutan sistem (Sustainability).

### 17.1. Dokumentasi Teknis (Developer Focused)
- [ ] **API Documentation (Swagger/OpenAPI):** Implementasi dokumentasi API otomatis untuk memudahkan integrasi dengan aplikasi mobile atau pihak ketiga.
- [ ] **Expansion of Markdown Docs:** Melanjutkan dan memperbarui dokumentasi `.md` yang sudah ada di folder `docs/` agar mencakup fitur terbaru seperti Activity Logs, Accounting Integrity, dan Roadmap Fase.
- [ ] **Database Schema & ERD Update:** Pembuatan dan pembaruan diagram hubungan antar tabel (ERD) serta Kamus Data (Data Dictionary) setiap kali ada perubahan struktur database.
- [ ] **Coding Standards & Guide:** Pembuatan panduan standar penulisan kode dan dokumentasi internal untuk developer baru.

### 17.2. Panduan Operasional (User Focused)
- [ ] **Internal Wiki / Knowledge Base:** Membangun modul Help Center di dalam aplikasi untuk panduan penggunaan fitur bagi Staff Admin, Teknisi, dan Kasir.
- [ ] **Video Tutorial Series:** Pembuatan dokumentasi berupa video singkat untuk alur kerja kritikal (seperti Aktivasi OLT atau Tutup Buku Akuntansi).

### 17.3. Prosedur Pembaruan Dokumentasi (Recurring Task)
- [ ] **Phase Completion Review:** Mewajibkan pembaruan dokumentasi teknis dan manual user setiap kali satu FASE di dalam roadmap ini selesai dikerjakan ("Done").
- [ ] **Changelog Management:** Standardisasi penulisan log perubahan (Release Notes) agar Admin/Owner mengetahui detail update di setiap versi.

---

## FASE 18: Integrated Dismantle & Hardware Lifecycle
Fase ini bertujuan untuk mengelola siklus hidup perangkat keras (ONT/STB/Router) dari mulai pemasangan hingga penarikan kembali (Dismantle), serta memastikan aset perusahaan terlacak dengan baik.

### 18.1. Alur Kerja Bongkaran & Tukar Alat (DO & MT Swap)
- [ ] **Maintenance Hardware Swap:** Fitur khusus pada tiket Maintenance (MT) untuk melakukan pergantian alat yang rusak secara instan. Sistem akan mencatat pengambilan barang baru dari gudang dan pengembalian barang rusak secara bersamaan.
- [ ] **Automated Deactivation & Return:** Saat tiket Dismantle (DO) selesai, sistem otomatis menonaktifkan akun RADIUS dan meminta input pengembalian perangkat ke gudang.
- [ ] **Hardware Status Categorization:** Memberikan opsi status saat pengembalian: "Ready" (Layak Pakai), "Repaired" (Sudah Diperbaiki), atau "Damaged" (Rusak Total).

### 18.2. Proteksi Barang Rusak (Inventory Protection)
- [ ] **Auto-Block Damaged Items:** Perangkat yang ditandai sebagai "Damaged" atau "Broken" akan secara otomatis disembunyikan dari pilihan stok saat Aktivasi pelanggan baru.
- [ ] **SN/MAC Validation Guard:** Mencegah input manual SN/MAC yang sudah masuk daftar hitam (Blacklist) barang rusak untuk menghindari kesalahan teknisi di lapangan.

### 18.3. Audit & Reporting Aset
- [ ] **Dismantle vs Inventory Audit:** Laporan rekonsiliasi untuk memastikan setiap perangkat dari pelanggan yang berhenti ("Dismantle") sudah benar-benar masuk kembali ke gudang.
- [ ] **Depreciation of Damaged Goods:** Integrasi ke modul Akuntansi untuk menjurnal kerugian aset (Write-off) ketika barang dinyatakan rusak total dan tidak bisa diperbaiki lagi.

---

## FASE 19: System Stability & Quality Assurance
Fase ini adalah lapisan keamanan untuk memastikan setiap pengembangan fitur baru tidak merusak fitur yang sudah berjalan (*Zero Regression Policy*).

### 19.1. Automated Testing (Backend Integrity)
- [ ] **Critical Path Testing:** Membuat automated test (PHPUnit) untuk modul paling sensitif: Kalkulasi Invoice, Posting Jurnal Akuntansi, dan Sinkronisasi RADIUS.
- [ ] **Data Integrity Check:** Script rutin untuk memverifikasi bahwa total saldo di Buku Besar selalu sinkron dengan total transaksi di Bank dan Invoice.

### 19.2. Deployment Safety & Monitoring
- [ ] **Error Monitoring Integration:** Implementasi alat monitor error (seperti Sentry atau Log viewer internal) untuk menangkap bug secara real-time.
- [ ] **Migration Safety Protocol:** Prosedur pengecekan ulang setiap script SQL/Migration agar tidak ada data lama yang terhapus saat update fitur.

### 19.3. Backup & Disaster Recovery
- [ ] **Automated Database Backup:** Konfigurasi backup database otomatis ke storage eksternal setiap hari.
- [ ] **System Snapshot Guide:** Panduan bagi Admin untuk melakukan snapshot/backup manual sebelum memulai eksekusi FASE besar di roadmap ini.

---

## FASE 20: Regulatory Compliance & Tax Reporting (BHP & USO)
Fase ini memastikan ISP mematuhi aturan regulasi pemerintah Indonesia terkait pajak dan biaya hak penyelenggaraan jasa telekomunikasi.

---

## FASE 21: DevOps & Versioning Automation [SELESAI]
Mengotomatiskan alur kerja pengembangan agar lebih efisien dan terukur secara profesional.
- [x] **Automated Semantic Versioning:** Implementasi script otomatis (Git Hooks) yang akan menaikkan nomor versi aplikasi (misal: v1.0.5 ke v1.0.6) setiap kali melakukan `git push` atau `commit`.
- [x] **Version Display Integration:** Menampilkan nomor versi aktif secara dinamis di footer Dashboard Admin dan halaman Login untuk mempermudah tracking update.
- [x] **Changelog Generator:** Otomatisasi pembuatan catatan perubahan berdasarkan pesan commit git.

### 20.1. Perhitungan Otomatis BHP & USO
- [ ] **BHP & USO Calculation Engine:** Menambahkan logika perhitungan otomatis untuk Biaya Hak Penyelenggaraan (BHP) dan Kontribusi Universal Service Obligation (USO) berdasarkan persentase pendapatan kotor dari akun pendapatan yang relevan.
- [ ] **Excluded Revenue Filtering:** Fitur untuk memisahkan pendapatan non-telekomunikasi (seperti penjualan perangkat atau biaya instalasi) yang tidak dikenakan BHP/USO.

### 20.2. Integrasi Akuntansi (Accrual & Payment)
- [ ] **Tax & Regulatory Journaling:** Otomatisasi jurnal akrual di akhir bulan (Debit: Beban Pajak/BHP, Credit: Hutang Pajak/BHP) agar laporan laba rugi mencerminkan beban yang sebenarnya.
- [ ] **Payment Settlement Tracking:** Mencatat pembayaran BHP/USO ke negara dan melakukan rekonsiliasi dengan hutang pajak di sistem.

### 20.3. Dashboard Pelaporan Pemerintah
- [ ] **Regulatory Compliance Dashboard:** Dashboard ringkasan kewajiban PPN, PPh, BHP, dan USO per kuartal atau per tahun.
- [ ] **Report Export for Kominfo:** Fitur ekspor data transaksi dan pendapatan ke format Excel yang sesuai dengan kebutuhan pelaporan di portal e-LPP Kominfo.

---

## FASE 22: Customer Mobile Experience & PWA [SELESAI]
Pengembangan *User Interface* (UI) portal pelanggan agar 100% *mobile-friendly* dan terasa seperti aplikasi *Native* kekinian.
- [x] **Mobile-First UI Redesign:** Mendesain ulang antarmuka portal dengan konsep aplikasi mobile sungguhan (misal: menambahkan *bottom navigation bar*, tombol yang *touch-friendly*, dan elemen *glassmorphism* atau animasi *swipe*).
- [x] **Progressive Web App (PWA):** Mengimplementasikan *manifest.json* and *Service Worker* agar pelanggan dapat menginstal portal langsung ke layar utama (*homescreen*) *smartphone* mereka tanpa perlu mendownload dari App Store / Play Store.
- [x] **Push Notifications:** Mengintegrasikan *Web Push Notification* (misal: Firebase Cloud Messaging) agar pelanggan bisa menerima notifikasi pop-up di HP mereka (terkait tagihan baru, status tiket, atau promo) secara *real-time*.
