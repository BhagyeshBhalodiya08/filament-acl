<?php

namespace App\Filament\Resources\WorkingDayResource\Pages;

use App\Filament\Resources\WorkingDayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkingDay extends EditRecord
{
    protected static string $resource = WorkingDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
