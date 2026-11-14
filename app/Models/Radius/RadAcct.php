<?php

namespace App\Models\Radius;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
    // Memakai koneksi default (sama seperti model Radius lainnya). Sebelumnya di-hardcode 'mysql',
    // yang identik di produksi (DB_CONNECTION=mysql) tetapi membuat test SQLite gagal.
    protected $table = 'radacct';
    protected $primaryKey = 'radacctid';
    public $timestamps = false;

    protected $fillable = [
        'acctsessionid', 'acctuniqueid', 'username', 'groupname', 'realm',
        'nasipaddress', 'nasportid', 'nasporttype', 'acctstarttime',
        'acctupdatetime', 'acctstoptime', 'acctinterval', 'acctsessiontime',
        'acctauthentic', 'connectinfo_start', 'connectinfo_stop',
        'acctinputoctets', 'acctoutputoctets', 'calledstationid',
        'callingstationid', 'acctterminatecause', 'servicetype',
        'framedprotocol', 'framedipaddress'
    ];
    
    protected $casts = [
        'acctstarttime' => 'datetime',
        'acctstoptime' => 'datetime',
    ];
    public function scopeOnline($query)
    {
        return $query->whereNull('acctstoptime');
    }
}
