# 🚀 OLT Management System Roadmap (Smart OLT Vision)

Tujuan utama adalah membangun sistem manajemen OLT yang terintegrasi penuh dengan Billing, memiliki fitur setara dengan **NetNumen** dan **Smart OLT**.

---

## 📅 Phase 1: Stabilization & Communication Engine (Current)
*Fokus pada reliabilitas koneksi dan akurasi data.*
- [x] **Non-blocking Telnet Client**: Menghindari sistem hang saat OLT lambat merespon.
- [x] **ANSI Character Filtering**: Pembersihan otomatis karakter sampah dari OLT.
- [x] **Smart Data Parsing**: Menggunakan fallback command (`base-info`, `detail-info`) untuk mendapatkan nama pelanggan.
- [x] **Background Processing**: Integrasi dengan Laravel Queue agar UI tetap responsif.
- [x] **Auto-Polling UI**: Update data secara real-time di browser tanpa reload manual.

---

## 🛠️ Phase 2: Advanced Provisioning & Automation
*Fokus pada kemudahan operasional (Zero-Touch Provisioning).*
- [ ] **One-Click Authorization**: Deteksi otomatis ONU baru (Unconfigured ONU) dan aktivasi dalam satu klik.
- [ ] **Service Profile Templates**: Template otomatis untuk VLAN, Speed Limit, dan T-CONT.
- [ ] **Bulk Configuration**: Mengirim konfigurasi ke banyak ONU sekaligus.
- [ ] **ONU Migration Tool**: Memindahkan ONU antar port dengan konfigurasi yang tetap terjaga.

---

## 📊 Phase 3: Deep Diagnostics & Monitoring (NetNumen Style)
*Fokus pada visibilitas jaringan dan troubleshooting.*
- [ ] **Optical Power Dashboard**: Grafik histori redaman (RX/TX Power) untuk memantau kualitas kabel.
- [ ] **Uptime & Flap History**: Log histori kapan ONU mati/hidup untuk mendeteksi gangguan listrik/kabel.
- [ ] **PON Port Analytics**: Monitoring kapasitas bandwidth per port PON.
- [ ] **Real-time Alerts**: Notifikasi otomatis via WhatsApp/Telegram jika ada port down atau OLT offline.

---

## 💳 Phase 4: Full Billing & RADIUS Integration
*Fokus pada monetisasi dan kontrol akses otomatis.*
- [ ] **Automatic Suspend**: Mematikan akses internet ONU secara otomatis via OLT jika pelanggan menunggak.
- [ ] **Bandwidth Control mapping**: Sinkronisasi profil kecepatan antara RADIUS dan OLT.
- [ ] **ONU Inventory Mapping**: Menghubungkan Serial Number ONU langsung ke akun pelanggan di Billing.
- [ ] **Self-Service Portal**: Pelanggan bisa melihat status sinyal ONU mereka sendiri dari dashboard portal.

---

## 🛡️ Visi Teknis
- **Vendor Agnostic**: Kedepannya mendukung Huawei, Nokia, dan Fiberhome selain ZTE.
- **Micro-service Ready**: Engine Telnet/SNMP yang bisa dipisah untuk skalabilitas besar.
- **High Security**: Enkripsi password OLT dan audit log untuk setiap perubahan konfigurasi.
