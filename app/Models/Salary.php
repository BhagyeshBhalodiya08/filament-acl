<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id'); // Industry is the tenant
    }
    protected $fillable = [
        'worker_id', 'salary_month', 'total_working_days', 'days_present', 'days_absent',
        'total_hours_worked', 'overtime_hours', 'half_day_count', 'basic_salary',
        'other_allowances', 'food_allowance', 'loan_installment', 'pf_amount',
        'advance_salary', 'gross_salary', 'due_loan', 'total_payable',
        'payment_method', 'salary_status', 'remark', 'approved_by'
    ];

    public function worker() {
        return $this->belongsTo(Employee::class);
    }

    public function approver() {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
