<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Employee extends Model
{
    protected $fillable = [
        'full_name',
        'phone_number',
        'address',
        'joining_date',
        'department',
        'designation',
        'salary_per_day',
        'pf_amount',
        'regular_expense',
        'food_expense',
        'work_type',
        'manager_name',
        'emergency_contact',
        'bank_account_number',
        'industry_id',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id'); // Industry is the tenant
    }
}
