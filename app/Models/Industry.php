<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{

    protected $fillable = [
        'name',
        'number',
        'address'
    ];

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
    
    public function employees()
    {
        return $this->hasMany(Employee::class, 'industry_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'industry_id');
    }
    
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'industry_id');
    }
    
    public function loans()
    {
        return $this->hasMany(Loans::class, 'industry_id');
    }
    
    public function advance_salaries()
    {
        return $this->hasMany(AdvanceSalaries::class, 'industry_id');
    }

    public function working_day()
    {
        return $this->hasMany(WorkingDay::class, 'industry_id');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class, 'industry_id');
    }
}
