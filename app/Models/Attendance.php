<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 
        'attendance_type', 
        'shortfall_hours',
        'extra_hours',
        'compensated_hours',
        'days_count',
        'attendance_date',
        'remark',
        'industry_id',
        'approved_by',
        'attendances_start_date',
        'attendances_end_date'
    ];
    
    protected $casts = [
        'attendances_start_date' => 'date',
        'attendances_end_date' => 'date',
    ];

    public function industry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id'); // Industry is the tenant
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver() {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
