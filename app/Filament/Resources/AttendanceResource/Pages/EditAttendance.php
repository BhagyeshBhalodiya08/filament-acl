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
        if (isset($data['attendance_date']) && is_string($data['attendance_date'])) {
            [$startDateString, $endDateString] = explode(' - ', $data['attendance_date']);
            $data['attendances_start_date'] = Carbon::createFromFormat('d/m/Y', trim($startDateString))->toDateString();
            $data['attendances_end_date'] = Carbon::createFromFormat('d/m/Y', trim($endDateString))->toDateString();
            unset($data['attendance_date']);
        }
        return $data;
    }
}
