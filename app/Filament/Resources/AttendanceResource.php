<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Filament\Tables\Columns\TextareaColumn;
use Filament\Tables\Columns\BadgeColumn;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationGroup = 'Employee Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2) // Creates a grid with two equal-width columns
                ->schema([
                    Section::make('Employee Details')
                        ->description('Select the employee and specify the attendance date.')
                        ->schema([
                            Select::make('employee_id')
                                ->label('Employees')
                                ->searchable()
                                ->preload()
                                ->default(2)
                                ->relationship('employee', 'full_name')
                                ->required(),
                            DateRangePicker::make('attendance_date')
                                ->label('Attendance Date')
                                ->required()
                                ->formatStateUsing(function (?string $state, ?Model $record): ?string {
                                    if ($record && $record->attendances_start_date && $record->attendances_end_date) {
                                        $startDate = Carbon::parse($record->attendances_start_date)->format('d/m/Y');
                                        $endDate = Carbon::parse($record->attendances_end_date)->format('d/m/Y');
                                        return "$startDate - $endDate";
                                    }else{
                                        $currentDate = Carbon::parse(now())->format('d/m/Y');
                                        return "$currentDate - $currentDate";
                                    }
                                    return null;
                                })->afterStateUpdated(function (callable $set, $state) {
                                    // Check if state is empty or not a string
                                        if (empty($state) || !is_string($state)) {
                                            $set('days_count', null);
                                            return;
                                        }

                                        try {
                                            // Split the string into start and end dates
                                            [$startDateString, $endDateString] = explode(' - ', $state);

                                            // Parse dates using Carbon
                                            $startDate = Carbon::createFromFormat('d/m/Y', trim($startDateString));
                                            $endDate = Carbon::createFromFormat('d/m/Y', trim($endDateString));

                                            // Validate parsed dates
                                            if (!$startDate || !$endDate) {
                                                $set('days_count', null);
                                                return;
                                            }

                                            // Check if end date is before start date
                                            if ($endDate->lt($startDate)) {
                                                $set('days_count', null);
                                                return;
                                            }

                                            // Calculate days (inclusive)
                                            $daysCount = $startDate->diffInDays($endDate) + 1;
                                            $set('days_count', $daysCount);
                                        } catch (\Exception $e) {
                                            // Handle parsing errors
                                            $set('days_count', null);
                                        }
                                }),
                                TextInput::make('days_count')
                                ->label('Number of Days')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(true)
                                ->helperText('Automatically calculated based on the selected date range.'),
                        ])->columnSpan(2),
                    Section::make('Attendance Information')->collapsible()
                        ->description('Specify the attendance type and working day details.')
                        ->schema([
                            Select::make('attendance_type')
                                ->options([
                                    'Half Day' => 'Half Day',
                                    'Full Day' => 'Full Day',
                                    'Absent' => 'Absent',
                                    'Custom Hours' => 'Custom Hours',
                                ])
                                ->required(),
                            // Select::make('working_day_type')
                            //     ->options([
                            //         'Half Day' => 'Half Day',
                            //         'Full Day' => 'Full Day',
                            //     ])->default("Full Day"),
                        ])->columnSpan(1),
                    Section::make('Hours Details')->collapsible()
                        ->description('Provide details about shortfall, extra, and compensated hours.')
                        ->schema([
                            TextInput::make('shortfall_hours')
                                ->numeric()
                                ->step(0.01)
                                ->nullable(),
                            TextInput::make('extra_hours')
                                ->numeric()
                                ->step(0.01)
                                ->nullable(),
                        ])->columnSpan(1),
                    Section::make('Additional Information')->collapsible()
                        ->description('Add remarks and specify the approver.')
                        ->schema([
                            Textarea::make('remark')
                                ->nullable(),
                            // Select::make('industry_id')
                            //     ->label('Industry')
                            //     ->relationship('industry', 'name')
                            //     ->nullable(),
                            Select::make('approved_by')
                                ->label('Approved By')
                                ->relationship('approver', 'name')
                                ->nullable()
                                ->required(),
                        ])->columnSpan(2)
                ])->columns(2), // Occupies one column,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('employee.full_name')
                ->label('Employee Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('attendances_start_date')
                ->label('Attendance Start Date')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('attendances_end_date')
                ->label('Attendance End Date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('days_count')
                ->label('Days Count')
                ->sortable()
                ->alignCenter()
                ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
            Tables\Columns\TextColumn::make('attendance_type')
                ->label('Attendance Type')->badge()
                ->colors([
                    'warning' => 'Half Day',
                    'success' => 'Full Day',
                    'danger' => 'Absent',
                    'info' => 'Custom Hours',
                ])
                ->sortable(),

            // Tables\Columns\TextColumn::make('working_day_type')
            //     ->label('Working Day Type')->badge()
            //     ->colors([
            //         'warning' => 'Half Day',
            //         'success' => 'Full Day',
            //     ])
            //     ->sortable(),

            Tables\Columns\TextColumn::make('shortfall_hours')
                ->label('Shortfall Hours')
                ->sortable()
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00'),

            Tables\Columns\TextColumn::make('extra_hours')
                ->label('Extra Hours')
                ->sortable()
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00'),

            Tables\Columns\TextColumn::make('remark')
                ->label('Remark')
                ->limit(50)
                ->tooltip(fn ($state) => $state),

            Tables\Columns\TextColumn::make('industry.name')
                ->label('Industry')
                ->sortable()->hidden(fn () => auth()->user()->super_user !== 'yes'),

            Tables\Columns\TextColumn::make('approver.name')
                ->label('Approved By')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Created At')->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Updated At')->date()
                ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['attendance_date'])) {
            $data['attendances_start_date'] = Carbon::parse($data['attendance_date']['from'])->toDateString();
            $data['attendances_end_date'] = Carbon::parse($data['attendance_date']['to'])->toDateString();
            unset($data['attendance_date']);
        }
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
    \Log::info("Sds");
        if (isset($data['attendance_date'])) {
            $data['attendances_start_date'] = Carbon::parse($data['attendance_date']['from'])->toDateString();
            $data['attendances_end_date'] = Carbon::parse($data['attendance_date']['to'])->toDateString();
            unset($data['attendance_date']);
        }
        return $data;
    }
}
