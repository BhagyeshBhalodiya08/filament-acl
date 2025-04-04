<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Filament\Resources\SalaryResource\RelationManagers;
use App\Models\Salary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationGroup = 'Payments';
    
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Employee Information')->schema([
                    Select::make('employee_id')
                        ->relationship('worker', 'full_name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Employee')
                        ->live(),
                    Forms\Components\DatePicker::make('salary_month')
                        ->native(false)
                        ->displayFormat('F Y')
                        ->format('Y-m')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set, $get) => $set('total_working_days', function () use ($state, $set, $get) {

                            $employeeId = $get('employee_id');

                            $attendanceDetails = self::calculateAttendanceDetails($state, $employeeId);

                            if (!$attendanceDetails) return null;
                        
                            // Set the calculated fields
                            $set('days_present', $attendanceDetails['daysPresent']);
                            $set('days_absent', $attendanceDetails['daysAbsent']);
                            $set('total_hours_worked', $attendanceDetails['totalHoursWorked']);
                            $set('overtime_hours', $attendanceDetails['overtimeHours']);
                        
                            return $attendanceDetails['workingDays'];
                        })),
                ]),
                
                Section::make('Attendance Details')->schema([
                    TextInput::make('total_working_days')
                        ->numeric()
                        ->required()
                        ->reactive(),
                    TextInput::make('days_present')
                        ->numeric()
                        ->reactive()
                        ->required(),
                    TextInput::make('days_absent')
                        ->numeric()
                        ->reactive()
                        ->required(),
                    TextInput::make('overtime_hours')
                        ->numeric()
                        ->disabled(),
                    TextInput::make('total_hours_worked')
                        ->numeric()
                        ->disabled()
                ]),
                
                Section::make('Salary Breakdown')->schema([
                    TextInput::make('basic_salary')->numeric()->required()->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                        $set('gross_salary', ($get('basic_salary') / $get('total_working_days')) * $get('days_present') + $get('other_allowances') + $get('food_allowance'))
                    ),
                    TextInput::make('other_allowances')->numeric(),
                    TextInput::make('food_allowance')->numeric(),
                ])->columns(2),
                
                Section::make('Deductions')->schema([
                    TextInput::make('loan_installment')->numeric(),
                    TextInput::make('pf_amount')->numeric(),
                    TextInput::make('advance_salary')->numeric(),
                ])->columns(2),
                
                Section::make('Final Salary Calculation')->schema([
                    TextInput::make('gross_salary')->numeric()->required(),
                    TextInput::make('due_loan')->numeric()->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                        $set('due_loan', max(0, (int) ($get('due_loan') ?? 0) - (int) ($get('loan_installment') ?? 0)))
                    ),
                    TextInput::make('total_payable')->numeric()->required()->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => 
                        $set('total_payable', $get('gross_salary') - ($get('loan_installment') + $get('pf_amount') + $get('advance_salary')))
                    ),
                ])->columns(2),
                
                Section::make('Payment & Approval')->schema([
                    Select::make('payment_method')->options([
                        'Bank Transfer' => 'Bank Transfer',
                        'UPI' => 'UPI',
                        'Cash' => 'Cash'
                    ])->required(),
                    Select::make('salary_status')->options([
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Hold' => 'Hold'
                    ])->required(),
                    TextInput::make('remark'),
                    Select::make('approved_by')->relationship('approver', 'name')->nullable(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('worker.full_name')
                ->label('Worker')
                ->sortable()
                ->searchable(),

            TextColumn::make('salary_month')
                ->label('Salary Month')
                ->sortable(),

            TextColumn::make('total_working_days')
                ->label('Working Days')
                ->sortable(),

            TextColumn::make('days_present')
                ->label('Days Present')
                ->sortable(),

            TextColumn::make('days_absent')
                ->label('Days Absent')
                ->sortable(),

            // TextColumn::make('total_hours_worked')
            //     ->label('Total Hours')
            //     ->sortable(),

            TextColumn::make('overtime_hours')
                ->label('Overtime Hours')
                ->sortable(),

            TextColumn::make('basic_salary')
                ->label('Basic Salary')
                ->money('INR')
                ->sortable(),

            TextColumn::make('other_allowances')
                ->label('Other Allowances')
                ->money('INR')
                ->sortable(),

            TextColumn::make('food_allowance')
                ->label('Food Allowance')
                ->money('INR')
                ->sortable(),

            TextColumn::make('loan_installment')
                ->label('Loan Installment')
                ->money('INR')
                ->sortable(),

            TextColumn::make('pf_amount')
                ->label('PF Amount')
                ->money('INR')
                ->sortable(),

            TextColumn::make('advance_salary')
                ->label('Advance Salary')
                ->money('INR')
                ->sortable(),

            TextColumn::make('gross_salary')
                ->label('Gross Salary')
                ->money('INR')
                ->sortable(),

            TextColumn::make('due_loan')
                ->label('Due Loan')
                ->money('INR')
                ->sortable(),

            TextColumn::make('total_payable')
                ->label('Total Payable')
                ->money('INR')
                ->sortable(),

            TextColumn::make('payment_method')
                ->label('Payment Method')
                // ->options([
                //     'Bank Transfer' => 'Bank Transfer',
                //     'UPI' => 'UPI',
                //     'Cash' => 'Cash'
                // ])
                ->sortable(),

            BadgeColumn::make('salary_status')
                ->label('Salary Status')
                ->colors([
                    'Pending' => 'warning',
                    'Paid' => 'success',
                    'Hold' => 'danger'
                ])
                ->formatStateUsing(fn ($state) => ucfirst($state)),

            TextColumn::make('remark')
                ->label('Remark')
                ->wrap(),

            TextColumn::make('approved_by')
                ->label('Approved By')
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
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
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }
    public static function calculateAttendanceDetails($state, $employeeId)
    {
        if (!$state) return null; // Ensure salary_month is set
    
        // Get the start and end dates for the selected salary month
        $startDate = Carbon::parse("$state-01")->startOfMonth()->toDateString();
        $endDate = Carbon::parse("$state-01")->endOfMonth()->toDateString();
    
        // 1. Count Working Days (from 'working_days' table)
        $workingDays = DB::table('working_days')
            ->where('type', 'Working Day')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
    
        // 2. Get Attendance Counts for present (Full Day, Half Day, Custom Hours) and absent
        $attendanceCounts = DB::table('attendances')
            ->select(
                DB::raw("COUNT(CASE WHEN attendance_type IN ('Full Day', 'Half Day', 'Custom Hours') THEN 1 END) as days_present"),
                DB::raw("COUNT(CASE WHEN attendance_type = 'Absent' THEN 1 END) as days_absent")
            )
            ->where('employee_id', $employeeId) // Filter by employee_id
            ->whereBetween('attendances_start_date', [$startDate, $endDate])
            ->whereBetween('attendances_end_date', [$startDate, $endDate])
            ->first();
    
        // Get the values from the result
        $daysPresent = $attendanceCounts->days_present;
        $daysAbsent = $attendanceCounts->days_absent;
    
        // 3. Calculate Days Absent
        // Calculate absent days as working days minus the present days
        $daysAbsent = $workingDays - $daysPresent;
    
        // 4. Calculate Total Hours Worked and Overtime Hours
        $totalHoursWorked = 0;
        $overtimeHours = 0;

        // Retrieve all attendances to calculate total worked hours and overtime
        $attendances = DB::table('attendances')
            ->where('employee_id', $employeeId) // Filter by employee_id
            ->whereBetween('attendances_start_date', [$startDate, $endDate])
            ->whereBetween('attendances_end_date', [$startDate, $endDate])
            ->get();

        foreach ($attendances as $attendance) {
            $workedHours = 0;
            $baseHours = 0;

            // Get shortfall and extra hours
            $shortfall = $attendance->shortfall_hours ?? 0;
            $extra = $attendance->extra_hours ?? 0;

            // Initialize overtime for this day as extra hours
            $tempOvertime = $extra;
            $remainingShortfall = $shortfall;

            // Step 1: Subtract shortfall from overtime hours first
            if ($remainingShortfall > 0 && $tempOvertime > 0) {
                $deductFromOvertime = min($remainingShortfall, $tempOvertime);
                $tempOvertime -= $deductFromOvertime;
                $remainingShortfall -= $deductFromOvertime;
            }

            if ($attendance->attendance_type == 'Full Day') {
                // Full Day: 8 hours base
                $baseHours = 8;
                // Step 2: Subtract remaining shortfall from base hours
                $workedHours = $baseHours - $remainingShortfall;
                $workedHours = max(0, $workedHours); // Ensure worked hours is not negative
                // Step 3: Add remaining overtime to worked hours
                $workedHours += $tempOvertime;
                // Add to total overtime
                $overtimeHours += $tempOvertime;

            } elseif ($attendance->attendance_type == 'Half Day') {
                // Half Day: 4 hours base
                $baseHours = 4;
                // Step 2: Subtract remaining shortfall from base hours
                $workedHours = $baseHours - $remainingShortfall;
                $workedHours = max(0, $workedHours);
                // Step 3: Add remaining overtime to worked hours
                $workedHours += $tempOvertime;
                // Add to total overtime
                $overtimeHours += $tempOvertime;

            } elseif ($attendance->attendance_type == 'Custom Hours') {
                // Custom Hours: Assume 8 hours base unless specified
                $baseHours = ($attendance->worked_hours ?? 8);
                // Step 2: Subtract remaining shortfall from base hours
                $workedHours = $baseHours - $remainingShortfall;
                $workedHours = max(0, $workedHours);
                // Step 3: Add remaining overtime to worked hours
                $workedHours += $tempOvertime;
                // Add to total overtime
                $overtimeHours += $tempOvertime;
                // If total worked hours exceed 8, adjust overtime
                if ($workedHours > 8) {
                    $overtimeHours += $workedHours - 8;
                    $workedHours = 8; // Cap regular hours at 8
                }

            } elseif ($attendance->attendance_type == 'Absent') {
                // Absent: No hours worked
                $workedHours = 0;
            }
            // Add to total hours worked
            $totalHoursWorked += $workedHours;
        }
    
        return [
            'workingDays' => $workingDays,
            'daysPresent' => $daysPresent,
            'daysAbsent' => $daysAbsent,
            'totalHoursWorked' => $totalHoursWorked,
            'overtimeHours' => $overtimeHours,
        ];
    } 
}
