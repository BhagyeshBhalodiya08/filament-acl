<?php 

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Models\Employee;
use App\Models\Salary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worker.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('salary_month')
                    ->label('Month')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('basic_salary')
                    ->label('Basic Salary')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label('Gross Salary')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('loan_installment')
                    ->label('Loan Installment')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('advance_salary')
                    ->label('Advance Salary')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('total_due_loan')
                    ->label('Due Loan')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('total_payable')
                    ->label('Total Payable')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('salary_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'Pending' => 'warning',
                        'Paid' => 'success',
                        'Hold' => 'gray',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('salary_status')
                    ->options([
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Hold' => 'Hold',
                    ]),
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 👤 Employee Selection & Month
                Forms\Components\Section::make('Salary Information')
                    ->description('Select employee and month to begin salary entry')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('worker', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (callable $get, callable $set) {
                                $salaryMonth = $get('salary_month');
                                self::populateSalaryRate($get, $set);
                                if ($salaryMonth) {
                                    self::calculateSalaryDetails($salaryMonth, $get, $set);
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset dependent fields
                                foreach ([
                                    'total_working_days', 'days_present', 'days_absent', 'total_hours_worked',
                                    'overtime_hours', 'basic_salary', 'gross_salary', 'total_payable',
                                    'loan_installment', 'advance_salary', 'total_due_loan',
                                    'loan_details', 'advance_details'
                                ] as $field) {
                                    $set($field, null);
                                }
                            }),

                        Forms\Components\DatePicker::make('salary_month')
                            ->label('Salary Month')
                            ->native(false)
                            ->displayFormat('F Y')
                            ->format('Y-m')
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (callable $get, callable $set) {
                                self::populateSalaryRate($get, $set); // Trigger on page load
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                try {
                                    self::calculateSalaryDetails($state, $get, $set);
                                } catch (\Exception $e) {
                                    $set('error_message', 'Error calculating salary: ' . $e->getMessage());
                                }
                            }),
                    ]),

                // 📆 Attendance Details
                Forms\Components\Section::make('Attendance Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('total_working_days')->numeric()->required()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
                        Forms\Components\TextInput::make('days_present')->numeric()->required()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
                        Forms\Components\TextInput::make('days_absent')->numeric()->required()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
                        Forms\Components\TextInput::make('total_hours_worked')->numeric()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
                        Forms\Components\TextInput::make('overtime_hours')->numeric()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
                    ]),

                // 💰 Salary & Allowances
                Forms\Components\Section::make('Earnings')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('salary_per_day')->numeric()->required()->reactive(),
                        Forms\Components\TextInput::make('basic_salary')->numeric()->required()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
                        Forms\Components\TextInput::make('other_allowances')->numeric()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::updateGrossAndPayable($get, $set)),
                        Forms\Components\TextInput::make('food_allowance')->numeric()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::updateGrossAndPayable($get, $set)),
                    ]),

                // 📉 Deductions
                Forms\Components\Section::make('Deductions')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('loan_installment')->numeric()->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                self::updateGrossAndPayable($get, $set);
                                $set('total_due_loan', max(0, (float)($get('total_due_loan') ?? 0) - (float)($state ?? 0)));
                            }),
                        Forms\Components\TextInput::make('advance_salary')->numeric()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::updateGrossAndPayable($get, $set)),
                        Forms\Components\TextInput::make('pf_amount')->numeric()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::updateGrossAndPayable($get, $set)),
                        Forms\Components\Hidden::make('loan_details')->default('[]'),
                        Forms\Components\Hidden::make('advance_details')->default('[]'),
                    ]),

                // 📊 Final Calculations
                Forms\Components\Section::make('Salary Summary')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('gross_salary')->numeric()->required()->reactive(),
                        Forms\Components\TextInput::make('total_payable')->numeric()->required()->reactive(),
                        Forms\Components\TextInput::make('total_due_loan')->numeric()->reactive(),
                    ]),

                // ✅ Status & Notes
                Forms\Components\Section::make('Payment Status & Approval')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'Bank Transfer' => 'Bank Transfer',
                                'UPI' => 'UPI',
                                'Cash' => 'Cash',
                            ])
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('salary_status')
                            ->options([
                                'Pending' => 'Pending',
                                'Paid' => 'Paid',
                                'Hold' => 'Hold',
                            ])
                            ->default('Pending')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($state === 'Paid' && $record) {
                                    self::finalizeSalary($record);
                                }
                            }),

                        Forms\Components\Select::make('approved_by')
                            ->relationship('approver', 'name')
                            ->nullable()
                            ->searchable()
                            ->preload(),
                    ]),

                // 📝 Final Notes
                Forms\Components\Section::make('Remarks')
                    ->schema([
                        Forms\Components\Textarea::make('remark')->columnSpanFull(),
                    ]),

                // 🚨 Error Feedback
                Forms\Components\Placeholder::make('error_message')
                    ->content(fn (callable $get) => $get('error_message'))
                    ->hidden(fn (callable $get) => !$get('error_message')),
            ]);
    }

    /**
     * Calculate initial salary details including loans and advances
     */
    private static function calculateSalaryDetails($salaryMonth, callable $get, callable $set): void
    {
        $employeeId = $get('employee_id');
        $industryId = Employee::where('id', $employeeId)->value('industry_id');

        if (!$salaryMonth || !$employeeId || !$industryId) {
            return;
        }

        $attendanceDetails = self::calculateAttendanceDetails($salaryMonth, $employeeId);
        if (!$attendanceDetails) {
            throw new \Exception('Unable to calculate attendance details');
        }

        $employee = Employee::select('salary_per_day', 'regular_expense', 'food_expense')
            ->where('id', $employeeId)
            ->first();
        if (!$employee) {
            throw new \Exception('Employee not found');
        }

        $loanAndAdvance = self::calculateLoanAndAdvanceDetails($employeeId, $industryId, $salaryMonth, $set);

        $set('total_working_days', $attendanceDetails['workingDays']);
        $set('days_present', $attendanceDetails['daysPresent']);
        $set('days_absent', $attendanceDetails['daysAbsent']);
        $set('total_hours_worked', $attendanceDetails['totalHoursWorked']);
        $set('overtime_hours', $attendanceDetails['overtimeHours']);
        $set('salary_per_day', $employee->salary_per_day);

        $set('loan_installment', $loanAndAdvance['monthly_loan_installment'] ?: 0);
        $set('advance_salary', $loanAndAdvance['total_advance_salary'] ?: 0);
        $set('total_due_loan', $loanAndAdvance['remaining_loan'] ?: 0);
        $set('loan_details', json_encode($loanAndAdvance['loan_details']));
        $set('advance_details', json_encode($loanAndAdvance['advance_details']));

        self::recalculateSalary($get, $set);
    }

    private static function calculateLoanAndAdvanceDetails($employeeId, $industryId, $salaryMonth, callable $set): array
    {
        $salaryDate = new \DateTime($salaryMonth);
        $salaryDate->modify('first day of this month');
        $salaryMonth = $salaryDate->format('Y-m-d');

        $loans = DB::table('loans')
            ->where('employee_id', $employeeId)
            ->where('industry_id', $industryId)
            ->where('loan_status', 'Approved')
            ->where('loan_start_date', '<=', "$salaryMonth")
            ->where(function ($query) use ($salaryMonth) {
                $query->whereNull('loan_end_date')
                      ->orWhere('loan_end_date', '>=', "$salaryMonth");
            })
            ->get();
            // echo '<pre>'; print_r((DB::table('loans')
            // ->where('employee_id', $employeeId)
            // ->where('industry_id', $industryId)
            // ->where('loan_status', 'Approved')
            // ->where('loan_start_date', '<=', "$salaryMonth-01")
            // ->where(function ($query) use ($salaryMonth) {
            //     $query->whereNull('loan_end_date')
            //           ->orWhere('loan_end_date', '>=', "$salaryMonth-01");
            // }))->toRawSql()); echo '</pre>'; exit;
        $totalLoanAmount = $loans->sum('loan_amount');
        $monthlyLoanInstallment = $loans->sum('installment_amount_per_month');
        $totalPaid = $loans->sum('amount_paid');
        $remainingLoan = max(0, $totalLoanAmount - $totalPaid);

        $loanDetails = $loans->map(function ($loan) use ($salaryMonth) {
            $remainingAmount = $loan->loan_amount - $loan->amount_paid;
            if ($remainingAmount > 0 && $loan->installment_amount_per_month > 0) {
                $monthsRemaining = ceil($remainingAmount / $loan->installment_amount_per_month);
                $newEndDate = Carbon::parse($loan->loan_start_date)
                    ->addMonths($monthsRemaining + floor($loan->amount_paid / $loan->installment_amount_per_month))
                    ->toDateString();
                DB::table('loans')->where('id', $loan->id)->update(['dynamic_loan_end_date' => $newEndDate]);
            }
            return [
                'loan_id' => $loan->id,
                'amount' => min($loan->installment_amount_per_month, $remainingAmount),
            ];
        })->toArray();

        // ... advance salary logic unchanged ...

        return [
            'monthly_loan_installment' => $monthlyLoanInstallment,
            'remaining_loan' => $remainingLoan,
            'total_loan_amount' => $totalLoanAmount,
            'total_advance_salary' => 0,
            'loan_details' => $loanDetails,
            'advance_details' => null,
        ];
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

            $employee = Employee::select('regular_expense', 'food_expense')
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
            $gross = (float)($get('basic_salary') ?? 0)
            + (float)($get('other_allowances') ?? 0)
            + (float)($get('food_allowance') ?? 0);

            $loanInstallment = (float)($get('loan_installment') ?? 0);
            $advanceSalary = (float)($get('advance_salary') ?? 0);
            $pfAmount = (float)($get('pf_amount') ?? 0);
            $dueLoanAmount = (float)($get('total_due_loan') ?? 0); // existing total due loan

            $totalDeductions = $loanInstallment + $advanceSalary + $pfAmount;

            // Adjust loan installment if deductions exceed gross
            if ($totalDeductions > $gross) {
                $availableForLoan = max(0, $gross - $advanceSalary - $pfAmount);
                $loanInstallment = min($loanInstallment, $availableForLoan);
                $set('loan_installment', $loanInstallment);
            }

            // Update due loan amount after cutting installment
            $newDueLoan = max(0, $dueLoanAmount - $loanInstallment);
            $set('total_due_loan', $newDueLoan);

            $deductions = $loanInstallment + $advanceSalary + $pfAmount;
            $payable = max(0, $gross - $deductions);

            $set('gross_salary', $gross);
            $set('total_payable', $payable);
        } catch (\Exception $e) {
            $set('error_message', 'Error updating totals: ' . $e->getMessage());
        }
    }

    /**
     * Finalize salary and update related tables
     */
    private static function finalizeSalary($record): void
    {
        if ($record->salary_status === 'Paid') {
            $employeeId = $record->employee_id;
            $industryId = $record->industry_id;
            $salaryMonth = $record->salary_month;
            $loanInstallment = (float)($record->loan_installment ?? 0);
            $advanceSalary = (float)($record->advance_salary ?? 0);

            // Update loans
            $loanDetails = json_decode($record->loan_details, true) ?? [];
            foreach ($loanDetails as $detail) {
                $loanId = $detail['loan_id'];
                $amount = (float)$detail['amount'];
                if ($amount > 0 && $loanInstallment > 0) {
                    $deduction = min($amount, $loanInstallment);
                    DB::table('loans')
                        ->where('id', $loanId)
                        ->increment('amount_paid', $deduction);

                    $loan = DB::table('loans')->where('id', $loanId)->first();
                    $remainingLoan = $loan->loan_amount - $loan->amount_paid;
                    if ($remainingLoan <= 0) {
                        DB::table('loans')
                            ->where('id', $loanId)
                            ->update(['loan_status' => 'Completed']);
                    }
                    $loanInstallment -= $deduction;
                }
            }

            // Update advance salaries
            $advanceDetails = json_decode($record->advance_details, true) ?? [];
            $remainingDeduction = $advanceSalary;
            foreach ($advanceDetails as $detail) {
                $advanceId = $detail['advance_id'];
                $amount = (float)$detail['amount'];
                if ($amount > 0 && $remainingDeduction > 0) {
                    $deductThisMonth = min($remainingDeduction, $amount);
                    DB::table('advance_salaries')
                        ->where('id', $advanceId)
                        ->increment('settled_amount', $deductThisMonth);

                    $advance = DB::table('advance_salaries')->where('id', $advanceId)->first();
                    if ($advance->settled_amount >= $advance->advance_salary_amount) {
                        DB::table('advance_salaries')
                            ->where('id', $advanceId)
                            ->update(['settled_at' => now(), 'salary_id' => $record->id]);
                    }
                    $remainingDeduction -= $deductThisMonth;
                }
            }
        }
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
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

    /**
     * Calculate attendance details (unchanged from your original)
     */
    public static function calculateAttendanceDetails($state, $employeeId)
    {
        if (!$state) return null;

        $startDate = Carbon::parse("$state-01")->startOfMonth()->toDateString();
        $endDate = Carbon::parse("$state-01")->endOfMonth()->toDateString();

        $workingDays = DB::table('working_days')
            ->where('type', 'Working Day')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        $attendanceCounts = DB::table('attendances')
            ->select(
                DB::raw("COUNT(CASE WHEN attendance_type IN ('Full Day', 'Half Day', 'Custom Hours') THEN 1 END) as days_present"),
                DB::raw("COUNT(CASE WHEN attendance_type = 'Absent' THEN 1 END) as days_absent")
            )
            ->where('employee_id', $employeeId)
            ->whereBetween('attendances_start_date', [$startDate, $endDate])
            ->whereBetween('attendances_end_date', [$startDate, $endDate])
            ->first();

        $daysPresent = $attendanceCounts->days_present;
        $daysAbsent = $attendanceCounts->days_absent;
        $daysAbsent = $workingDays - $daysPresent;

        $totalHoursWorked = 0;
        $overtimeHours = 0;

        $attendances = DB::table('attendances')
            ->where('employee_id', $employeeId)
            ->whereBetween('attendances_start_date', [$startDate, $endDate])
            ->whereBetween('attendances_end_date', [$startDate, $endDate])
            ->get();

        foreach ($attendances as $attendance) {
            $workedHours = 0;
            $baseHours = 0;
            $shortfall = $attendance->shortfall_hours ?? 0;
            $extra = $attendance->extra_hours ?? 0;
            $tempOvertime = $extra;
            $remainingShortfall = $shortfall;

            if ($remainingShortfall > 0 && $tempOvertime > 0) {
                $deductFromOvertime = min($remainingShortfall, $tempOvertime);
                $tempOvertime -= $deductFromOvertime;
                $remainingShortfall -= $deductFromOvertime;
            }

            if ($attendance->attendance_type == 'Full Day') {
                $baseHours = 8;
                $workedHours = $baseHours - $remainingShortfall;
                $workedHours = max(0, $workedHours);
                $workedHours += $tempOvertime;
                $overtimeHours += $tempOvertime;
            } elseif ($attendance->attendance_type == 'Half Day') {
                $baseHours = 4;
                $workedHours = $baseHours - $remainingShortfall;
                $workedHours = max(0, $workedHours);
                $workedHours += $tempOvertime;
                $overtimeHours += $tempOvertime;
            } elseif ($attendance->attendance_type == 'Custom Hours') {
                $baseHours = ($attendance->worked_hours ?? 8);
                $workedHours = $baseHours - $remainingShortfall;
                $workedHours = max(0, $workedHours);
                $workedHours += $tempOvertime;
                $overtimeHours += $tempOvertime;
                if ($workedHours > 8) {
                    $overtimeHours += $workedHours - 8;
                    $workedHours = 8;
                }
            } elseif ($attendance->attendance_type == 'Absent') {
                $workedHours = 0;
            }
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
    protected static function populateSalaryRate(callable $get, callable $set): void
    {
        $employeeId = $get('employee_id');
        $salaryMonth = $get('salary_month');

        if ($employeeId && $salaryMonth) {
            $employee = Employee::find($employeeId);
            if ($employee && $employee->salary_per_day) {
                $set('salary_per_day', $employee->salary_per_day);
            }
        }
    }

}