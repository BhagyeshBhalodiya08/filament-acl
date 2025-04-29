<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $fillable = [
        'loan_id',
        'salary_id',
        'amount',
        'paid_at',
        'salary_month'
    ];
}
