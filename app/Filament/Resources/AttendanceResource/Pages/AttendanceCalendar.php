<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\WorkingDayResource;
use Filament\Resources\Pages\Page;

class AttendanceCalendar extends Page
{
    protected static string $resource = WorkingDayResource::class;

    protected static ?string $navigationLabel = "Calendar";

    protected static string $view = 'filament.resources.attendance-resource.pages.attendance-calendar';

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('Calendar');
    }

}
