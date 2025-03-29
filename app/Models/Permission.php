<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id'); // Industry is the tenant
    }
}
