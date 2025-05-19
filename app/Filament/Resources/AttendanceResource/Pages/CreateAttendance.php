<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['attendance_date']) && is_string($data['attendance_date'])) {
            [$startDateString, $endDateString] = explode(' - ', $data['attendance_date']);
            $data['attendances_start_date'] = Carbon::createFromFormat('d/m/Y', trim($startDateString))->toDateString();
            $data['attendances_end_date'] = Carbon::createFromFormat('d/m/Y', trim($endDateString))->toDateString();
            unset($data['attendance_date']);
        }
        return $data;
    }
    
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         \Filament\Actions\Action::make('createAnother')
    //         ->label(__('filament-panels::resources/pages/create-record.form.actions.create_another.label'))
    //         ->action('createAnother')
    //         ->keyBindings(['mod+shift+s'])
    //         ->color('gray')
    //     ];
    // }
}
