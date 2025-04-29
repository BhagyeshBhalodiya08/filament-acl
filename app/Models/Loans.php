<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loans extends Model
{
    protected $fillable = [
        'employee_id',
        'application_date',
        'loan_amount',
        'loan_start_date',
        'loan_end_date',
        'total_installments',
        'installment_amount_per_month',
        'loan_status',
        'loan_purpose',
        'disbursement_method',
        'loan_approved_by',
        'remark',
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
        return $this->belongsTo(User::class, 'loan_approved_by');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class ,'loan_id');
    }

}
