<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
abstract class BaseResource extends Resource
{
    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();

    //     if (Filament::auth()->user()?->isSuperAdmin()) {
    //         // echo '<pre>'; print_r(auth()->user()?->isSuperAdmin()); echo '</pre>'; exit;
    //         // echo '<pre>'; print_r(get_class_methods($query)); echo '</pre>'; exit;
    //         return $query->withoutGlobalScopes();
    //     }
    //     return $query;
    // }

    public static function scopeEloquentQueryToTenant(Builder $query, ?\Illuminate\Database\Eloquent\Model $tenant): Builder
    {
        // Remove Tenant For Super User
        if (Filament::auth()->user()?->isSuperAdmin()) {
            return $query;
        }else{
            return parent::scopeEloquentQueryToTenant($query, $tenant);
        }
        
    }
}