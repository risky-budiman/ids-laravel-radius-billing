<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageChangeRequest extends Model
{
    public const STATUS_LABELS = ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'canceled' => 'Dibatalkan'];

    protected $fillable = ['customer_id', 'from_package_id', 'to_package_id', 'reason', 'status', 'requested_by', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function fromPackage()
    {
        return $this->belongsTo(Package::class, 'from_package_id');
    }

    public function toPackage()
    {
        return $this->belongsTo(Package::class, 'to_package_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
