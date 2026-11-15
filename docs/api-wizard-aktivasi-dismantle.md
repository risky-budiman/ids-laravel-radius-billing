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
| `payment_method` | `cash` / `transfer` (biaya instalasi langsung masuk Kas & Bank) atau `pg` (dibuatkan invoice instalasi + masa tenggang 1 jam) |
| `bank_account_id` | rekening tujuan untuk `transfer` (opsional untuk `cash`: default Kas Kantor Utama) |
| `activated_at` | opsional, default sekarang |
| `odc_id`, `odp_id`, `odp_port`, `vlan_id`, `static_ip`, `region_code`, `sto_code`, `stb_code` | data jaringan |
| `latitude`, `longitude`, `description` | lokasi & catatan |
| `olt_id`, `onu_sn`, `onu_index`, `onu_type` | provisioning OLT |
| `identity_photo`, `house_photo`, `cpe_photo` | gambar, maks. 2 MB |

Respons 200: `message`, `customer`, `installation_invoice` (`invoice_number`, `amount`, `payment_link`)
bila memakai `pg`, selain itu `null`. Respons 422: status pelanggan tidak bisa diaktivasi, atau modem
sudah terpasang / tidak siap.

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
| `notes` | catatan, disimpan di catatan penyelesaian tiket |

Efek: status `dismantled`, perangkat kembali ke gudang (kondisi `used`), tiket dismantle selesai, akun
RADIUS dihapus, sesi diputus, ONU di-deprovision dari OLT. 422 bila ada perangkat milik pelanggan lain
atau pelanggan sudah dicabut.
