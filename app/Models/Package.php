<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mikrotik_group',
        'price',
        'upload_speed',
        'download_speed',
        'burst_limit_up',
        'burst_limit_down',
        'burst_threshold_up',
        'burst_threshold_down',
        'burst_time_up',
        'burst_time_down',
        'limit_at_up',
        'limit_at_down',
        'priority',
        'description',
        'is_active',
        'enable_fup',
        'fup_limit_gb',
        'fup_speed_limit',
    ];

    public function getMikrotikRateLimitAttribute()
    {
        // RX/TX [BurstRX/BurstTX [ThresholdRX/ThresholdTX [TimeRX/TimeTX [Priority [LimitAtRX/LimitAtTX]]]]]
        $rx = $this->upload_speed;
        $tx = $this->download_speed;
        
        if (empty($rx) || empty($tx)) return null;

        $rate = "{$rx}/{$tx}";
        
        // Burst
        if ($this->burst_limit_up && $this->burst_limit_down) {
            $burst = " {$this->burst_limit_up}/{$this->burst_limit_down}";
            
            // Threshold
            if ($this->burst_threshold_up && $this->burst_threshold_down) {
                $threshold = " {$this->burst_threshold_up}/{$this->burst_threshold_down}";
                
                // Time
                if ($this->burst_time_up && $this->burst_time_down) {
                    $time = " {$this->burst_time_up}/{$this->burst_time_down}";
                    
                    // Priority
                    $priority = " " . ($this->priority ?? 8);
                    
                    // Limit At
                    if ($this->limit_at_up && $this->limit_at_down) {
                        $limitAt = " {$this->limit_at_up}/{$this->limit_at_down}";
                        return $rate . $burst . $threshold . $time . $priority . $limitAt;
                    }
                    
                    return $rate . $burst . $threshold . $time . $priority;
                }
                
                return $rate . $burst . $threshold;
            }
            
            return $rate . $burst;
        }

        return $rate;
    }
}
