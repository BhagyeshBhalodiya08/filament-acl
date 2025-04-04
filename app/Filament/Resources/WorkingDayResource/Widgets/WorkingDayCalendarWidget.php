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

    protected ?string $modelLabel = 'ss';
    
    protected function viewAction()
    {
        return Actions\ViewAction::make();
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()->mountUsing(
                function (WorkingDay $record, Forms\Form $form, array $arguments) {
                    $form->fill([
                        'date' => $record->date,
                        'type' => $record->type,
                        'remark' => $record->remark
                    ]);
                }
            ),
            Actions\DeleteAction::make(),
        ];
    }

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
                    'id' => $day->id,
                    'start' => $day->date,
                    'title' => $day->type . (empty($day->remark) ? '' : ' (' . $day->remark . ')'),
                    'remark' => $day->remark,
                    'color' => $colors[$day->type] ?? '#6c757d',
                ];
            })
            ->toArray();

            return $abcd;
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('date')
                ->required()->native(false)
                ->label('Select Date') 
                ->default(now()),
            Forms\Components\Radio::make('type')
                ->options([
                    'Working Day' => 'Working Day',
                    'Holiday' => 'Holiday',
                    'Weekend' => 'Weekend',
                ])
                ->default('Working Day')
                ->required()
                ->inline()
                ->label('Attendance Type'),
            Forms\Components\Textarea::make('remark')
                ->label('Remark')
                ->inlineLabel(true),
        ];
    }
}
    