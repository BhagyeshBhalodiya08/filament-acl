<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['attendance_date'])) {
            // Extract start and end dates
            $dates = explode(' - ', $data['attendance_date']);

            // Convert them to Carbon instances
            $data['attendances_start_date'] = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
            $data['attendances_end_date'] = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

            // Remove the temporary field
            unset($data['attendance_date']);
        }

        return $data;
    }
}
