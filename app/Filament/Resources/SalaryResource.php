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
use Filament\Forms\Components\Textarea;
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
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('total_working_days', null);
                            $set('days_present', null);
                            $set('days_absent', null);
                            $set('total_hours_worked', null);
                            $set('overtime_hours', null);
                            $set('basic_salary', null);
                            $set('gross_salary', null);
                            $set('total_payable', null);
                        }),
                    Forms\Components\DatePicker::make('salary_month')
                        ->native(false)
                        ->displayFormat('F Y')
                        ->format('Y-m')
                        // ->type('month')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            try {
                                self::calculateSalaryDetails($state, $get, $set);
                            } catch (\Exception $e) {
                                $set('error_message', 'Error calculating salary: ' . $e->getMessage());
                            }
                        }),
                ]),
                
                Section::make('Attendance Details')->schema([
                    TextInput::make('total_working_days')
                        ->numeric()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::recalculateSalary($get, $set);
                        }),
                    TextInput::make('days_present')
                        ->numeric()
                        ->reactive()
                        ->required()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::recalculateSalary($get, $set);
                        }),
                    TextInput::make('days_absent')
                        ->numeric()
                        ->reactive()
                        ->required()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::recalculateSalary($get, $set);
                        }),
                    TextInput::make('overtime_hours')
                        ->numeric()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        self::recalculateSalary($get, $set);
                    }),
                    TextInput::make('total_hours_worked')
                        ->numeric()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        self::recalculateSalary($get, $set);
                    })
                ]),
                
                Section::make('Salary Breakdown')->schema([
                    TextInput::make('salary_per_day')->numeric()->required()->reactive(),
                    TextInput::make('basic_salary')->numeric()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::recalculateSalary($get, $set);
                        }),
                    TextInput::make('other_allowances')->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        self::updateGrossAndPayable($get, $set);
                    }),
                    TextInput::make('food_allowance')->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        self::updateGrossAndPayable($get, $set);
                    }),
                ])->columns(2),
                
                Section::make('Deductions')->schema([
                    TextInput::make('loan_installment')->numeric()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateGrossAndPayable($get, $set);
                            $set('due_loan', max(0, (int)($get('due_loan') ?? 0) - (int)($state ?? 0)));
                        }),
                    TextInput::make('pf_amount')->numeric()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateGrossAndPayable($get, $set);
                        }),
                    TextInput::make('advance_salary')->numeric()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateGrossAndPayable($get, $set);
                        }),
                ])->columns(2),
                
                Section::make('Final Salary Calculation')->schema([
                    TextInput::make('gross_salary')->numeric()->required()->reactive(),
                    TextInput::make('due_loan')->numeric()->reactive(),
                    TextInput::make('total_payable')->numeric()->required()->reactive(),
                ])->columns(2),
                
                Section::make('Payment & Approval')->schema([
                    Select::make('payment_method')->options([
                        'Bank Transfer' => 'Bank Transfer',
                        'UPI' => 'UPI',
                        'Cash' => 'Cash'
                    ])->required()->searchable()->preload(),
                    Select::make('salary_status')->options([
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Hold' => 'Hold'
                    ])->required()->searchable()->preload(),
                    Select::make('approved_by')->relationship('approver', 'name')->nullable()->searchable()->preload(),
                    Textarea::make('remark')->columnSpanFull(),
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

            TextColumn::make('salary_status')
                ->label('Salary Status')
                ->badge()
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
    public static function getEmployeeSalary($employeeId)
    {
        return ;
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

    /**
     * Calculate initial salary details
     */
    private static function calculateSalaryDetails($salaryMonth, callable $get, callable $set): void
    {
        $employeeId = $get('employee_id');
        
        if (!$salaryMonth || !$employeeId) {
            return;
        }

        $attendanceDetails = self::calculateAttendanceDetails($salaryMonth, $employeeId);
        
        if (!$attendanceDetails) {
            throw new \Exception('Unable to calculate attendance details');
        }

        $employee = \App\Models\Employee::select('salary_per_day', 'regular_expense', 'food_expense')
            ->where('id', $employeeId)
            ->first();

        if (!$employee) {
            throw new \Exception('Employee not found');
        }

        $set('total_working_days', $attendanceDetails['workingDays']);
        $set('days_present', $attendanceDetails['daysPresent']);
        $set('days_absent', $attendanceDetails['daysAbsent']);
        $set('total_hours_worked', $attendanceDetails['totalHoursWorked']);
        $set('overtime_hours', $attendanceDetails['overtimeHours']);
        $set('salary_per_day', $employee->salary_per_day);
        
        self::recalculateSalary($get, $set);
    }

    /**
     * Recalculate salary based on editable fields
     */
    private static function recalculateSalary(callable $get, callable $set): void
    {
        try {
            $totalHoursWorked = (float)($get('total_hours_worked') ?? 0);
            $salaryPerDay = (float)($get('salary_per_day') ?? 0);
            $daysPresent = (float)($get('days_present') ?? 0);
            
            $employee = \App\Models\Employee::select('regular_expense', 'food_expense')
                ->where('id', $get('employee_id'))
                ->first();

            $basicSalary = $salaryPerDay > 0 && $totalHoursWorked > 0 
                ? round(($totalHoursWorked / 8) * $salaryPerDay, 2)
                : 0;
                
            $otherAllowances = $employee && $daysPresent > 0 
                ? round($employee->regular_expense * $daysPresent, 2)
                : (float)($get('other_allowances') ?? 0);
                
            $foodAllowance = $employee && $daysPresent > 0 
                ? round($employee->food_expense * $daysPresent, 2)
                : (float)($get('food_allowance') ?? 0);

            $set('basic_salary', $basicSalary);
            $set('other_allowances', $otherAllowances);
            $set('food_allowance', $foodAllowance);

            self::updateGrossAndPayable($get, $set);
        } catch (\Exception $e) {
            $set('error_message', 'Calculation error: ' . $e->getMessage());
        }
    }

    /**
     * Update gross salary and total payable
     */
    private static function updateGrossAndPayable(callable $get, callable $set): void
    {
        try {
            $basicSalary = (float)($get('basic_salary') ?? 0);
            $otherAllowances = (float)($get('other_allowances') ?? 0);
            $foodAllowance = (float)($get('food_allowance') ?? 0);
            
            $grossSalary = round($basicSalary + $otherAllowances + $foodAllowance, 2);
            $set('gross_salary', $grossSalary);

            $loanInstallment = (float)($get('loan_installment') ?? 0);
            $pfAmount = (float)($get('pf_amount') ?? 0);
            $advanceSalary = (float)($get('advance_salary') ?? 0);
            
            $totalDeductions = $loanInstallment + $pfAmount + $advanceSalary;
            $totalPayable = round(max(0, $grossSalary - $totalDeductions), 2);
            
            $set('total_payable', $totalPayable);
        } catch (\Exception $e) {
            $set('error_message', 'Error updating totals: ' . $e->getMessage());
        }
    }

    // /**
    //  * Calculate attendance details (placeholder)
    //  */
    // private static function calculateAttendanceDetails($salaryMonth, $employeeId): ?array
    // {
    //     // Implement your actual attendance calculation logic here
    //     return [
    //         'workingDays' => 30,
    //         'daysPresent' => 25,
    //         'daysAbsent' => 5,
    //         'totalHoursWorked' => 200,
    //         'overtimeHours' => 10,
    //     ];
    // }
    
}
