<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvanceSalaries extends Model
{
    protected $fillable = [
        'employee_id',
        'requested_date',
        'advance_salary_amount',
        'advance_salary_month',
        'reason',
        'payment_method',
        'advance_salary_status',
        'approved_by',
        'industry_id'
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
