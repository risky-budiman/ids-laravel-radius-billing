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
            $model->logAction('created', "Created " . class_basename($model) . $label);
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at'], $changes['remember_token']);
            if (empty($changes)) return;

            $identifier = $model->getModelIdentifier();
            $label = $identifier ? ": " . $identifier : "";

            $model->logAction('updated', "Updated " . class_basename($model) . $label, [
                'old' => array_intersect_key($model->getOriginal(), $changes),
                'new' => $changes
            ]);
        });

        static::deleted(function ($model) {
            $identifier = $model->getModelIdentifier();
            $label = $identifier ? ": " . $identifier : "";
            $model->logAction('deleted', "Deleted " . class_basename($model) . $label, [
                'old' => $model->getOriginal() // Keep full record for deleted items
            ]);
        });
    }

    public function getModelIdentifier()
    {
        $possibleFields = ['name', 'invoice_number', 'ticket_number', 'customer_code', 'username', 'code', 'email', 'id'];
        
        foreach ($possibleFields as $field) {
            if (isset($this->{$field})) {
                return $this->{$field};
            }
        }
        
        return null;
    }

    protected function logAction($action, $description, $properties = null)
    {
        // Filter sensitive data from properties (including ID)
        $sensitiveFields = ['id', 'password', 'remember_token', 'secret', 'created_at', 'updated_at', 'deleted_at'];
        
        if ($properties && isset($properties['old'])) {
            $properties['old'] = array_diff_key($properties['old'], array_flip($sensitiveFields));
        }
        if ($properties && isset($properties['new'])) {
            $properties['new'] = array_diff_key($properties['new'], array_flip($sensitiveFields));
        }

        ActivityLog::create([
            'user_id' => Auth::id(), // Will be null for background jobs
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
