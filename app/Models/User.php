<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasDefaultTenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password', 'industry_id', 'super_user'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    //  // Relationship to Industry
    public function industry_for_form()
    {
        return $this->belongsTo(Industry::class);
    }

    public function industry(): BelongsToMany
    {
    return $this->belongsToMany(
        Industry::class,
        'industries_user',
        'user_id',
        'industry_id'
        )->withTimestamps();
    }
     
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        // return $this->industry->contains($tenant);
        return $this->isSuperAdmin() || $this->industry->contains($tenant);
    }

    public function getTenants(Panel $panel): array|Collection
    {

        return $this->industry ?? [];
        // return User::where('super_user', 'yes')
        //     ->whereDoesntHave('industry') // Ensure user has no tenant
        //     ->get();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->industry()->first();
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->super_user === 'yes';
    }

}
