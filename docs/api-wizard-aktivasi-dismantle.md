# API Wizard Aktivasi & Dismantle (aplikasi mobile teknisi)

Semua endpoint memakai token Sanctum staf (`Authorization: Bearer <token>`) dan hanya untuk peran
**administrator, admin, teknisi** (sales mendapat 403). Aturan dan efeknya sama persis dengan wizard
di web (`App\Services\CustomerActivationService`).

Tiket aktivasi/dismantle **tidak** bisa ditutup lewat `POST /tickets/{id}/close` (422): selesaikan
lewat endpoint di bawah, yang sekaligus mencatat gudang, kas, RADIUS, dan OLT.

## Aktivasi

### `GET /api/v1/admin/customers/{id}/activation`
Data untuk form: `customer`, `can_activate` (true hanya untuk status `new` / `waiting_activation`),
`modems[]` (`stock_id`, `item`, `serial_number`, `mac_address`, `status`, `condition`),
`consumables[]` (`item_id`, `name`, `unit`), `olts[]`, `odcs[]`, `odps[]` (`odc_id`),
`bank_accounts[]`, `payment_methods` (`cash`, `transfer`, `pg`).

### `POST /api/v1/admin/customers/{id}/activate`
JSON, atau `multipart/form-data` bila mengirim foto.

| Field | Keterangan |
|---|---|
| `modem_stock_id` | opsional; harus berstatus siap pakai di gudang. SN modem otomatis jadi `onu_sn` bila `onu_sn` kosong |
| `consumables[][item_id]`, `consumables[][quantity]` | material terpakai (mis. kabel dalam meter) |
| `payment_method` | `cash` / `transfer` (biaya instalasi dibayar di lokasi) atau `pg` (dibayar belakangan lewat gateway, dengan batas bayar). Lihat *Berita Acara & invoice instalasi* di bawah. |
| `bank_account_id` | rekening tujuan untuk `transfer` (opsional untuk `cash`: default Kas Kantor Utama) |
| `activated_at` | opsional, default sekarang |
| `odc_id`, `odp_id`, `odp_port`, `vlan_id`, `static_ip`, `region_code`, `sto_code`, `stb_code` | data jaringan |
| `latitude`, `longitude`, `description` | lokasi & catatan |
| `olt_id`, `onu_sn`, `onu_index`, `onu_type` | provisioning OLT |
| `identity_photo`, `house_photo`, `cpe_photo` | gambar, maks. 2 MB |

Respons 200: `message`, `customer`, `installation_invoice` (`id`, `invoice_number`, `amount`, `status`, `payment_link`, `grace_expires_at`) atau `null`, dan `installation_report` (`id`, `number`, `sign_url`, `customer_signed_at`, `technician_signed_at`, `fully_signed`) atau `null` bila BA nonaktif.
bila memakai `pg`, selain itu `null`. Respons 422: status pelanggan tidak bisa diaktivasi, atau modem
sudah terpasang / tidak siap.

## Berita Acara & invoice instalasi

Keduanya opsional, diatur administrator di **Pengaturan Instalasi** (`/admin/settings/installation`):

- **BA instalasi** (`activation_ba_enabled`): setelah aktivasi dibuat BA bernomor `BA/YYYY/MM/NNNN` berisi salinan data saat itu (pelanggan, paket, perangkat & material, foto, invoice). Pelanggan bisa tanda tangan di HP teknisi (endpoint di bawah) atau lewat `sign_url` yang dikirim ke WhatsApp-nya.
- **Invoice instalasi** (`installation_invoice_enabled`): setiap aktivasi dengan biaya instalasi menerbitkan invoice `INV-INST…`. `cash`/`transfer` → langsung lunas (masuk rekening `bank_account_id`, default KAS TUNAI). Selain itu → belum dibayar, internet tetap aktif sampai `grace_expires_at` (default 24 jam), pengingat WhatsApp N jam sebelumnya, lalu diisolir otomatis. Begitu invoice dibayar, isolir dicabut.
- Bila invoice instalasi nonaktif, perilaku lama berlaku (tunai/transfer langsung ke Kas & Bank; `pg` membuat invoice) dengan batas bayar dari pengaturan yang sama.

### `GET /api/v1/admin/customers/{id}/installation-report`

BA terakhir pelanggan, termasuk `snapshot` (isi BA) dan `terms`. 404 bila belum ada.

### `POST /api/v1/admin/installation-reports/{id}/sign`

| Field | Keterangan |
|---|---|
| `role` | wajib, `customer` atau `technician` |
| `signature` | wajib, PNG data-URL (`data:image/png;base64,...`), maks. 512 KB |
| `signer_name` | nama penanda tangan (untuk `customer`; default nama pelanggan) |
| `latitude`, `longitude` | opsional, lokasi saat tanda tangan |

Respons 200: `message`, `installation_report`. 422 bila sudah ditandatangani pihak tersebut atau gambar tidak valid.

## Dismantle

### `POST /api/v1/admin/customers/{id}/request-dismantle`
Mengajukan dismantle: status menjadi `waiting_dismantle`, satu tiket dismantle dibuat, tagihan bulanan
berhenti. Respons: `ticket_id`, `ticket_number`. 422 bila sudah dalam proses / selesai.

### `GET /api/v1/admin/customers/{id}/dismantle`
`customer`, `can_dismantle`, `installed_equipment[]` (`stock_id`, `item`, `serial_number`, `mac_address`).

### `POST /api/v1/admin/customers/{id}/dismantle`
| Field | Keterangan |
|---|---|
| `stock_ids[]` | perangkat yang berhasil ditarik; boleh kosong. Hanya perangkat yang terpasang di pelanggan ini |
| `broken_stock_ids[]` | bagian dari `stock_ids` yang ditarik dalam kondisi rusak: masuk gudang berstatus Rusak, tidak dihitung stok siap pakai |
| `notes` | catatan, disimpan di catatan penyelesaian tiket |

Efek: status `dismantled`, perangkat kembali ke gudang (kondisi `used`), tiket dismantle selesai, akun
RADIUS dihapus, sesi diputus, ONU di-deprovision dari OLT. 422 bila ada perangkat milik pelanggan lain
atau pelanggan sudah dicabut.
