<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu proses impor pelanggan di latar belakang (pratinjau atau impor). */
class ImportRun extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'mode', 'file_name', 'path', 'duplicate_action', 'status', 'total_rows',
        'processed_rows', 'result', 'error', 'started_at', 'finished_at'];

    protected $casts = ['result' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['done', 'failed'], true);
    }

    public function percent(): int
    {
        if ($this->status === 'done') {
            return 100;
        }

        return $this->total_rows ? (int) min(99, floor($this->processed_rows / $this->total_rows * 100)) : 0;
    }
}
