<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\WhatsappTemplate;

class WhatsappTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Tagihan Baru (Invoice Generated)',
                'type' => 'invoice_generated',
                'message' => "Halo *{name}* (ID: {id_pelanggan}),\n\nTagihan internet Anda untuk periode *{periode}* sebesar *Rp {amount}* telah terbit dengan nomor tagihan *{invoice_number}*.\nJatuh tempo pada: *{due_date}*.\n\nSilakan segera lakukan pembayaran melalui portal pelanggan atau klik link berikut: {payment_link}\n\nTerima kasih.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{periode}', '{amount}', '{invoice_number}', '{due_date}', '{payment_link}']),
                'is_active' => true,
            ],
            [
                'name' => 'Penangguhan Layanan (Suspension)',
                'type' => 'suspension',
                'message' => "Halo *{name}* (ID: {id_pelanggan}),\n\nLayanan internet Anda sementara ditangguhkan (SUSPENDED).\nAlasan: {reason}\n\nSilakan melakukan pembayaran tagihan Anda sebesar *Rp {amount}* untuk mengaktifkan kembali layanan. Jika sudah membayar, abaikan pesan ini.\n\nLink pembayaran: {payment_link}\n\nTerima kasih.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{reason}', '{amount}', '{payment_link}']),
                'is_active' => true,
            ],
            [
                'name' => 'Tagihan Instalasi',
                'type' => 'invoice_instalasi',
                'message' => "Halo *{name}*,\n\nProses pendaftaran pemasangan internet Anda telah kami terima. Berikut adalah rincian tagihan instalasi Anda:\nNomor Tagihan: *{invoice_number}*\nBiaya Instalasi: *Rp {amount}*\n\nSilakan lakukan pembayaran melalui link berikut: {payment_link}\n\nJadwal pemasangan akan segera kami konfirmasi setelah pembayaran diterima. Terima kasih.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{invoice_number}', '{amount}', '{payment_link}']),
                'is_active' => true,
            ],
            [
                'name' => 'Status Pembayaran (Invoice Status User)',
                'type' => 'invoice_status_user',
                'message' => "Halo *{name}*,\n\nPembayaran Anda untuk tagihan *{invoice_number}* sebesar *Rp {amount}* telah berhasil dikonfirmasi. Status tagihan Anda saat ini adalah: *{status}*.\n\nTerima kasih atas pembayaran Anda.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{invoice_number}', '{amount}', '{status}']),
                'is_active' => true,
            ],
            [
                'name' => 'Pengingat Tagihan (Invoice Reminder)',
                'type' => 'invoice_reminder',
                'message' => "Halo *{name}*,\n\nIni adalah pengingat ramah bahwa tagihan internet Anda (*{invoice_number}*) sebesar *Rp {amount}* untuk periode *{periode}* akan segera jatuh tempo pada *{due_date}*.\n\nMohon abaikan pesan ini jika Anda sudah melakukan pembayaran. Link pembayaran: {payment_link}\n\nTerima kasih.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{invoice_number}', '{amount}', '{periode}', '{due_date}', '{payment_link}']),
                'is_active' => true,
            ],
            [
                'name' => 'Broadcast Umum',
                'type' => 'broadcast',
                'message' => "Halo *{name}*,\n\n[Pesan Anda di sini]\n\nTerima kasih.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}']),
                'is_active' => true,
            ],
            [
                'name' => 'Gangguan Massal',
                'type' => 'gangguan_masal',
                'message' => "Pemberitahuan Gangguan Massal\n\nHalo *{name}*,\n\nKami menginformasikan bahwa saat ini sedang terjadi gangguan jaringan massal di wilayah Anda yang menyebabkan penurunan kualitas layanan internet.\n\nEstimasi perbaikan: *{tanggal}*\n\nTim teknis kami sedang berada di lapangan dan berusaha memperbaikinya secepat mungkin. Kami mohon maaf yang sebesar-besarnya atas ketidaknyamanan ini.\n\nTerima kasih atas pengertian dan kesabaran Anda.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{tanggal}']),
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance Jaringan',
                'type' => 'gangguan_maintenance',
                'message' => "Pemberitahuan Pemeliharaan Jaringan\n\nHalo *{name}*,\n\nUntuk meningkatkan kualitas layanan, kami akan melakukan pemeliharaan jaringan (Maintenance) terjadwal di wilayah Anda pada:\n\nWaktu: *{tanggal}*\n\nSelama proses ini, layanan internet Anda mungkin akan mengalami gangguan sementara.\n\nMohon maaf atas ketidaknyamanan ini. Terima kasih.",
                'variables_description' => json_encode(['{id_pelanggan}', '{name}', '{tanggal}']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            WhatsappTemplate::updateOrCreate(
                ['type' => $template['type']],
                $template
            );
        }
    }
}
