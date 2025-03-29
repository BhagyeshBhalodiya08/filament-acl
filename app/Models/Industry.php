<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{

    protected $fillable = ['name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'industries_user')
        ->withTimestamps();
    }
    
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'industry_id');
    }
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'industry_id');
    }
}
