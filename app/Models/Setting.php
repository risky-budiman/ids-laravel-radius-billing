<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value', 'type'];

    /** Status yang ditulis otomatis tiap hari tidak perlu masuk log audit. */
    public function shouldLogActivity(): bool
    {
        return ! preg_match('/^(backup_last_|app_version)/', (string) $this->key);
    }

    /** Nilai setting rahasia (token, API key, password) disamarkan di log. */
    public function activityRedactedFields(): array
    {
        return preg_match('/(key|token|secret|password|credential)/i', (string) $this->key) ? ['value'] : [];
    }
}
