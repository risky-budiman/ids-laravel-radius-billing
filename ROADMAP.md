# 🚀 OLT Management System Roadmap (Smart OLT Vision)

Tujuan utama adalah membangun sistem manajemen ISP yang terintegrasi penuh: OLT Lifecycle, Billing Otomatis, GIS Infrastruktur, dan Enterprise ERP.

---

## 📅 FASE 1: Modul Administrasi Keuangan & Akuntansi (DONE)
*Transformasi billing menjadi sistem keuangan yang komprehensif.*
- [x] **Manajemen Kas & Bank**: Master Rekening, Mutasi, Rekonsiliasi, Saldo Staff, Pengeluaran, dan Kasir.
- [x] **Buku Besar & Jurnal (General Ledger)**: Chart of Accounts (CoA), Jurnal Umum, Auto-Journal dari Billing, Laporan Laba Rugi, dan Neraca.
- [x] **Perpajakan (Taxation)**: Master Pajak, PPN 11%, Pajak Masukan/Keluaran, dan Laporan Pajak Masa.
- [x] **Laporan Finansial**: Buku Besar, Arus Kas, dan Ekspor Laporan (Excel/PDF).
- [x] **Tutup Buku & Kunci Periode**: History penutupan bulanan, Snapshot Laba Rugi/Neraca per periode, dan proteksi transaksi masa lalu.
- [x] **Core Engine**: Non-blocking Telnet Client, ANSI Filtering, dan Smart Data Parsing untuk OLT ZTE.

---

## 🛠️ FASE 2: Integrasi Jaringan Terpusat (SNMP & OLT ZTE)
*Fokus pada otomatisasi perangkat (Zero Touch Provisioning).*
- [x] **Infrastruktur Komunikasi**: Library Telnet/SSH/SNMP (Pure PHP SNMP & phpseclib).
- [x] **Master Data OLT**: Pendaftaran OLT (IP, SNMP Community, Login Credentials).
- [x] **Provisioning Otomatis**: Script pemetaan DBA Profile, T-CONT, Traffic Profile, dan vPort (VLAN binding).
- [x] **Background Job**: Integrasi Horizon agar registrasi ONU berjalan di latar belakang saat klik "Tambah Pelanggan".
- [x] **SNMP Diagnostics**: Deteksi otomatis Unconfigured ONU, Reboot ONT remote, dan pembacaan metrik Redaman (Rx/Tx) real-time.
- [x] **Advanced Management**: Script Deprovisioning (Hapus ONU), Suspend/Resume (Isolir), dan Update Speed Profile.
- [ ] **HSGQ Support**: Integrasi otomatisasi Provisioning & Diagnostik untuk OLT merk HSGQ.

---

## 📊 FASE 3: Pemetaan Geospasial & GIS
*Pemetaan aset fisik dan lokasi infrastruktur.*
- [ ] **Location Master CRUD**: CRUD Lengkap untuk data Regional, STO, dan STB.
- [ ] **Titik Koordinat**: Penambahan Latitude/Longitude pada data pelanggan dan infrastruktur.
- [ ] **ODC & ODP Mapping**: Manajemen dan pemetaan Optical Distribution Cabinet & Cabinet.
- [ ] **Interactive Maps**: Menampilkan peta sebaran pelanggan & aset via Leaflet/Google Maps.
- [ ] **Sync Regional**: Integrasi data infrastruktur fisik dengan master data wilayah.

---

## 🛡️ FASE 4: Enterprise Subscriber Management
*Pengayaan data pelanggan dan dokumentasi aktivasi.*
- [ ] **Field Deskripsi Tambahan**: Kolom `description` pada data subscriber untuk catatan kronologi/keterangan detail.
- [ ] **Infrastruktur Detail**: Field `odc_id`, `odp_id`, `odp_port`, dan `cable_length` (panjang kabel drop core).
- [ ] **Enterprise Connectivity**: Penambahan field `vlan_id` dan `static_ip` untuk pelanggan Corporate/Dedicated.
- [ ] **Dokumen KYC**: Upload foto identitas (KTP/NPWP), foto rumah, dan foto modem saat aktivasi.
- [ ] **Customer Priority**: Penambahan tipe pelanggan (Personal, Corporate, VIP) untuk SLA penanganan tiket.

---

## 📱 FASE 5: Mobile & Camera Integration (Inventory)
*Alat bantu teknisi lapangan dan efisiensi input data.*
- [ ] **Web/Android Camera Access**: Scan Barcode/QR Code langsung dari browser.
- [ ] **Scan SN Inventory**: Input data barang masuk/keluar gudang cukup dengan scan Serial Number.
- [ ] **OCR SN Modem**: Otomatis membaca angka SN dari foto modem agar teknisi tidak perlu mengetik manual.

---

## 💳 FASE 6: Advanced Billing Automation & Collection (Optional)
*Fitur tambahan untuk kontrol keuangan yang lebih ketat.*
- [ ] **Advanced Billing Settings**: Satu halaman pengaturan terpusat untuk me-manage fitur optional di bawah.
- [ ] **Grace Period**: Penambahan masa tenggang toleransi isolir (H+X dari Due Date).
- [ ] **Merge Invoice**: [Optional] Menggabungkan tagihan menunggak bulan lalu ke dalam invoice bulan ini.
- [ ] **Prorata Lanjutan**: Perhitungan prorata pembayaran pertama (IndiHome Style) & ganti paket tengah bulan.
- [ ] **Late Fees**: Penambahan denda keterlambatan otomatis (Flat atau Persentase).
- [ ] **Auto-Stop Billing**: Tidak men-generate invoice baru jika pelanggan masih status Suspended/Isolir.

---

## 🔄 FASE 7: Refactoring & Perbaikan Logika Sistem
- [ ] **Perbaikan Dismantle**: Otomatisasi pembuatan tiket penarikan barang, deprovisioning OLT, dan pembersihan data RADIUS (Kick CoA).
- [ ] **Bad Debt Management**: Penanganan invoice yang masih Unpaid saat pelanggan dicabut (Write-Off).
- [x] **Radius Integrity Rule**: Jika CoA gagal, dilarang melakukan force-close/delete session di database jika status user masih online (mencegah data rancu).
- [ ] **Session Lock**: User yang dilakukan force delete/kick tidak boleh masuk sesi online kembali sebelum modem di-restart (mencegah auto-reconnect tanpa power cycle).

---

## 🎫 FASE 8: Helpdesk & Ticketing (Enterprise)
- [ ] **Claim/Assign System**: Alur penugasan tiket dari Admin ke Teknisi atau teknisi mengambil tiket dari pool antrean.
- [ ] **Ticket Replies**: Sistem komentar/thread di dalam tiket lengkap dengan lampiran foto progres.
- [ ] **Logika Aktivasi**: Menutup celah di mana tiket closed tanpa melalui Wizard Activation (mencegah data stok/billing tidak sinkron).

---

## 🏦 FASE 9: Enterprise ERP & Advanced Finance
- [ ] **Auto-Reconciliation**: Integrasi API Mutasi Bank (Moota/Xendit) untuk auto-match pembayaran.
- [ ] **Asset Depreciation**: Jurnal penyusutan aset otomatis (metode Straight-Line).
- [ ] **Accounts Payable**: Manajemen hutang vendor dan Purchase Order (PO).
- [ ] **Commission Engine**: Hitung otomatis komisi reseller/agen.

---

## 📡 FASE 10: NOC Monitoring & Performance
- [ ] **Background Polling**: Memindahkan penarikan data sinyal ke background job (SNMP Bulk) untuk menghindari timeout/hang.
- [ ] **Telegram/WA Alerts**: Notifikasi otomatis ke tim NOC jika OLT Down atau redaman memburuk.

---

## 🌐 FASE 11: Client Portal & FUP
- [ ] **Customer Self-Service**: Portal login pelanggan (via OTP WA/Email) untuk cek tagihan & sisa kuota.
- [ ] **Add-on Booster**: Fitur beli paket tambahan untuk reset kuota FUP otomatis.
- [ ] **FUP Management**: Data usage tracking dan tombol Reset Manual FUP dari sisi Admin.

---

## 🛡️ Visi Teknis & Workflow
- **Vendor Agnostic**: Dukungan ZTE, HSGQ, Huawei, Nokia, Fiberhome.
- **Workflow Aktivasi**: `Input Subscriber` ➡️ `Scan SN (OCR)` ➡️ `Plot Map` ➡️ `Auto-Config OLT` ➡️ `Internet Aktif`.
- **Workflow Dismantle**: `Request Dismantle` ➡️ `Auto-Ticket` ➡️ `Field Work` ➡️ `No-Onu CLI` ➡️ `Radius Cleanup`.
