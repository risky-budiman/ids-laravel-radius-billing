<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $identifier = $model->getModelIdentifier();
            $label = $identifier ? ": " . $identifier : "";
            $model->logAction('created', "Menambah " . $model->activityModelLabel() . $label);
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at'], $changes['remember_token']);
            if (empty($changes)) return;

            $identifier = $model->getModelIdentifier();
            $label = $identifier ? ": " . $identifier : "";

            $model->logAction('updated', "Mengubah " . $model->activityModelLabel() . $label, [
                'old' => array_intersect_key($model->getOriginal(), $changes),
                'new' => $changes
            ]);
        });

        static::deleted(function ($model) {
            $identifier = $model->getModelIdentifier();
            $label = $identifier ? ": " . $identifier : "";
            $model->logAction('deleted', "Menghapus " . $model->activityModelLabel() . $label, [
                'old' => $model->getOriginal() // Keep full record for deleted items
            ]);
        });
    }

    /** Nama jenis data yang tampil di Activity Log (bahasa Indonesia). */
    public function activityModelLabel(): string
    {
        return [
            'Customer' => 'pelanggan', 'Invoice' => 'invoice', 'Ticket' => 'tiket', 'Journal' => 'jurnal', 'Payment' => 'pembayaran',
            'Nas' => 'NAS', 'Region' => 'wilayah', 'Sto' => 'STO', 'Stb' => 'STB', 'Supplier' => 'pemasok', 'User' => 'pengguna',
            'InventoryItem' => 'barang', 'InventoryCategory' => 'kategori barang', 'BankAccount' => 'rekening',
            'BankTransaction' => 'transaksi kas', 'ChartOfAccount' => 'akun COA', 'Package' => 'paket', 'Gateway' => 'gateway',
            'Setting' => 'pengaturan', 'Tax' => 'pajak', 'FixedAsset' => 'aset tetap', 'PartnerWithdrawal' => 'penarikan mitra',
            'SalesWithdrawal' => 'penarikan sales',
        ][class_basename($this)] ?? class_basename($this);
    }

    public function getModelIdentifier()
    {
        if (method_exists($this, 'activityIdentifier')) {
            return $this->activityIdentifier();
        }

        $possibleFields = ['name', 'invoice_number', 'reference', 'reference_number', 'ticket_number', 'customer_code', 'username', 'code', 'account_name', 'key', 'email', 'id'];
        
        foreach ($possibleFields as $field) {
            if (isset($this->{$field})) {
                return $this->{$field};
            }
        }
        
        return null;
    }

    protected function logAction($action, $description, $properties = null)
    {
        // Model boleh melewati pencatatan untuk perubahan rutin (mis. status backup harian)
        if (method_exists($this, 'shouldLogActivity') && ! $this->shouldLogActivity()) {
            return;
        }

        // Filter sensitive data from properties (including ID)
        $sensitiveFields = ['id', 'password', 'remember_token', 'secret', 'created_at', 'updated_at', 'deleted_at'];

        if ($properties && isset($properties['old'])) {
            $properties['old'] = array_diff_key($properties['old'], array_flip($sensitiveFields));
        }
        if ($properties && isset($properties['new'])) {
            $properties['new'] = array_diff_key($properties['new'], array_flip($sensitiveFields));
        }

        // Rahasia (kredensial gateway, token, API key) tidak boleh tersimpan di log: hanya
        // dicatat BAHWA nilainya berubah.
        $redact = array_merge(['credentials', 'api_key', 'token', 'server_key', 'callback_token', 'secret_key'],
            method_exists($this, 'activityRedactedFields') ? $this->activityRedactedFields() : []);
        foreach (['old', 'new'] as $side) {
            foreach ($redact as $field) {
                if ($properties && array_key_exists($side, $properties) && array_key_exists($field, (array) $properties[$side])) {
                    $properties[$side][$field] = '[disembunyikan]';
                }
            }
        }

        $userId = null;
        if (Auth::check() && Auth::user() instanceof \App\Models\User) {
            $userId = Auth::id();
        }

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($this),
            'subject_id' => $this->id,
            'properties' => $properties,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'System/CLI',
        ]);
    }
}
