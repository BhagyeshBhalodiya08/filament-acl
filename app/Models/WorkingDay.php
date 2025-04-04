<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
 
    protected $fillable = [
        'date', 
        'type', 
        'remark',
        'industry_id'
    ];

    public function industry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id'); // Industry is the tenant
    }
}
