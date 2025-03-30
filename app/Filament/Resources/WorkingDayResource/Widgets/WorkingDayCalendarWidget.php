<?php

namespace App\Filament\Resources\WorkingDayResource\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Filament\Widgets\Widget;
use App\Models\Attendance;
use App\Models\WorkingDay;
use Saade\FilamentFullCalendar\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Carbon\Carbon;

class WorkingDayCalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = WorkingDay::class;

    // protected function headerActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make()->label("Create Attendance")
    //             ->mutateFormDataUsing(function (array $data): array {
    //             // Check if event_date_range exists
    //             if (isset($data['event_date_range'])) {
    //                 // Extract start and end dates
    //                 $dates = explode(' - ', $data['event_date_range']);

    //                 // Convert them to Carbon instances
    //                 $data['start_time'] = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
    //                 $data['end_time'] = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();

    //                 // Remove the temporary field
    //                 unset($data['event_date_range']);
    //             }

    //             return $data;
    //         }),
    //     ];
    // }

    // protected function modalActions(): array
    // {
    //     return [
    //         Actions\EditAction::make()
    //             ->label('Edit Attendance')
    //             ->form($this->getFormSchema()),

    //         Actions\DeleteAction::make()
    //             ->label('Delete Attendance'),
    //     ];
    // }
    public function fetchEvents(array $fetchInfo): array
    {

        $colors = [
            'Working Day' => '#28a745', // Green
            'Holiday'     => '#dc3545', // Red
            'Weekend'     => '#ffc107', // Yellow
        ];

        $abcd = WorkingDay::whereBetween('date', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(function (WorkingDay $day) use ($colors) {
                return [
                    'start' => $day->date,
                    'title' => $day->type,
                    'remark' => $day->remark,
                    'color' => $colors[$day->type] ?? '#6c757d',
                ];
            })
            ->toArray();

            return $abcd;
    }

    // public static function getCreateModalHeading(): string
    // {
    //     return 'Create Attendance';
    // }
    // public function getFormSchema(): array
    // {
    //     return [
    //         Forms\Components\TextInput::make('title') // ✅ Change 'name' to 'title' (matches DB)
    //             ->required(),
    //         DateRangePicker::make('event_date_range')
    //         ->label('Attendance Date Range')
    //         // ->startDate(fn ($record) => optional($record->start_time)->format('Y-m-d H:i:s'))
    //         ->required(),
    //     ];
    // }
}
    