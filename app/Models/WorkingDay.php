<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
 
    protected $fillable = [
        'employee_id', 
        'attendance_type', 
        'working_day_type',
        'shortfall_hours',
        'extra_hours',
        'compensated_hours',
        'attendance_date',
        'remark',
        'industry_id',
        'approved_by',
        'attendances_start_date',
        'attendances_end_date'
    ];

    public function industry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id'); // Industry is the tenant
    }
}
