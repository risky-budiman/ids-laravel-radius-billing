# Tiket & Gangguan

> **Lokasi Menu:** `Manajemen › Tiket`, `Rekap Tiket` · notifikasi: `Pengaturan Aplikasi › Notifikasi tiket ke grup WhatsApp`

## Jenis Tiket

| Jenis | Kode nomor | Keterangan |
|---|---|---|
| Gangguan | `TT` | Internet mati/lambat — dari staf atau laporan pelanggan di portal. |
| Aktivasi | `AO` | Pemasangan pelanggan baru; dibuat saat pendaftaran, ditutup lewat **wizard aktivasi**. |
| Dismantle | `DO` | Penarikan perangkat (pelanggan berhenti atau dismantle otomatis). |
| Relokasi | `RL` | Pindah alamat. |
| Maintenance | `MT` | Pekerjaan jaringan terjadwal. |

## Status

**Open** → **Dikerjakan** (diambil petugas) → **Resolved** (selesai, menunggu konfirmasi) → **Closed**. Tiket *Resolved* ditutup otomatis setelah 24 jam. Tiket tertentu bisa berstatus *Menunggu persetujuan*.

- Membatalkan tiket **aktivasi** membatalkan pelanggan yang menunggu aktivasi.
- Membatalkan tiket **dismantle** mengembalikan pelanggan berlangganan (aktif bila tidak menunggak, atau tetap terisolir).

## Peringatan ke Grup WhatsApp

Setiap tiket baru dikirim ke grup WhatsApp teknisi (grup per jenis tiket, atau grup cadangan). Selain itu, **sekali per tiket**:

- **Belum diambil**: tiket *open* tanpa petugas lebih dari 2 jam (setting `ticket_unclaimed_alert_hours`).
- **Lewat SLA**: belum selesai lebih dari **24 jam** sejak dibuat, atau melewati tanggal jadwal (mis. aktivasi terjadwal).

## Rekap Tiket

> `Manajemen › Rekap Tiket`

- **Kinerja per petugas**: tiket selesai, dibatalkan, masih terbuka, **rata-rata durasi**, **sesuai / lewat SLA** dan **% SLA** (SLA = selesai ≤ 24 jam sejak dibuat); teknisi aktif tanpa tiket tetap tampil.
- **Per tipe tiket** dan **per bulan** (klik bulan untuk rincian tiketnya), serta tabel tiket diselesaikan per petugas per bulan.
- **Ekspor Excel** untuk periode yang dipilih.

---
*Tips: lampirkan foto hasil pekerjaan di balasan tiket sebagai arsip teknis.*
