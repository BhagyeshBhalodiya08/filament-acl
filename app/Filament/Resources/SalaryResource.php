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
                        Forms\Components\TextInput::make('salary_per_day')->numeric()->required()->reactive()
                            ->afterStateUpdated(fn ($state, $set, $get) => self::recalculateSalary($get, $set)),
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
                                if ($state && $get('total_due_loan')) {
                                    $set('total_due_loan', max(0, (float)($get('total_due_loan') ?? 0) - (float)($state ?? 0)));
                                }
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
        if (!$employeeId) {
            return;
        }

        $employee = Employee::select('id', 'industry_id', 'salary_per_day', 'regular_expense', 'food_expense')
            ->where('id', $employeeId)
            ->first();

        if (!$employee || !$salaryMonth) {
            return;
        }

        $industryId = $employee->industry_id;

        if (!$industryId) {
            $set('error_message', 'Employee has no associated industry');
            return;
        }

        try {
            $attendanceDetails = self::calculateAttendanceDetails($salaryMonth, $employeeId);
            if (!$attendanceDetails) {
                $set('error_message', 'No attendance records found');
                return;
            }

            $loanAndAdvance = self::calculateLoanAndAdvanceDetails($employeeId, $industryId, $salaryMonth, $set);

            // Set attendance details
            $set('total_working_days', $attendanceDetails['workingDays']);
            $set('days_present', $attendanceDetails['daysPresent']);
            $set('days_absent', $attendanceDetails['daysAbsent']);
            $set('total_hours_worked', $attendanceDetails['totalHoursWorked']);
            $set('overtime_hours', $attendanceDetails['overtimeHours']);
            $set('salary_per_day', $employee->salary_per_day);

            // Set loan and advance details
            $set('loan_installment', $loanAndAdvance['monthly_loan_installment'] ?: 0);
            $set('advance_salary', $loanAndAdvance['total_advance_salary'] ?: 0);
            $set('total_due_loan', $loanAndAdvance['remaining_loan'] ?: 0);
            $set('loan_details', json_encode($loanAndAdvance['loan_details']));
            $set('advance_details', json_encode($loanAndAdvance['advance_details']));

            // Calculate initial salary details
            self::recalculateSalary($get, $set);
        } catch (\Exception $e) {
            $set('error_message', 'Error calculating salary details: ' . $e->getMessage());
        }
    }

    private static function calculateLoanAndAdvanceDetails($employeeId, $industryId, $salaryMonth, callable $set): array
    {
        try {
            $salaryDate = Carbon::parse($salaryMonth)->startOfMonth();
            $salaryMonthFormatted = $salaryDate->format('Y-m-d');

            // Get active loans for the employee
            $loans = DB::table('loans')
                ->where('employee_id', $employeeId)
                ->where('industry_id', $industryId)
                ->where('loan_status', 'Approved')
                ->where('loan_start_date', '<=', $salaryMonthFormatted)
                ->where(function ($query) use ($salaryMonthFormatted) {
                    $query->whereNull('loan_end_date')
                          ->orWhere('loan_end_date', '>=', $salaryMonthFormatted);
                })
                ->get();

            // Calculate loan totals
            $totalLoanAmount = $loans->sum('loan_amount') ?? 0;
            $monthlyLoanInstallment = $loans->sum('installment_amount_per_month') ?? 0;
            $totalPaid = $loans->sum('amount_paid') ?? 0;
            $remainingLoan = max(0, $totalLoanAmount - $totalPaid);

            // Prepare loan details
            $loanDetails = $loans->map(function ($loan) {
                $remainingAmount = max(0, $loan->loan_amount - $loan->amount_paid);
                $installmentAmount = min($loan->installment_amount_per_month, $remainingAmount);
                
                // Only update if there's a valid installment amount
                if ($remainingAmount > 0 && $loan->installment_amount_per_month > 0) {
                    $monthsRemaining = ceil($remainingAmount / $loan->installment_amount_per_month);
                    $paidMonths = floor($loan->amount_paid / $loan->installment_amount_per_month);
                    $newEndDate = Carbon::parse($loan->loan_start_date)
                        ->addMonths($monthsRemaining + $paidMonths)
                        ->toDateString();
                    
                    DB::table('loans')
                        ->where('id', $loan->id)
                        ->update(['dynamic_loan_end_date' => $newEndDate]);
                }
                
                return [
                    'loan_id' => $loan->id,
                    'amount' => $installmentAmount,
                ];
            })->toArray();

            // Get advance salary details (placeholder - actual implementation would be added here)
            $advanceDetails = [];
            $totalAdvanceSalary = 0;

            return [
                'monthly_loan_installment' => $monthlyLoanInstallment,
                'remaining_loan' => $remainingLoan,
                'total_loan_amount' => $totalLoanAmount,
                'total_advance_salary' => $totalAdvanceSalary,
                'loan_details' => $loanDetails,
                'advance_details' => $advanceDetails,
            ];
        } catch (\Exception $e) {
            $set('error_message', 'Error calculating loans and advances: ' . $e->getMessage());
            return [
                'monthly_loan_installment' => 0,
                'remaining_loan' => 0,
                'total_loan_amount' => 0,
                'total_advance_salary' => 0,
                'loan_details' => [],
                'advance_details' => [],
            ];
        }
    }

    /**
     * Recalculate salary based on editable fields
     */
    private static function recalculateSalary(callable $get, callable $set): void
    {
        try {
            $employeeId = $get('employee_id');
            if (!$employeeId) {
                return;
            }

            $totalHoursWorked = max(0, (float)($get('total_hours_worked') ?? 0));
            $salaryPerDay = max(0, (float)($get('salary_per_day') ?? 0));
            $daysPresent = max(0, (float)($get('days_present') ?? 0));
            $overtimeHours = max(0, (float)($get('overtime_hours') ?? 0));

            $employee = Employee::select('regular_expense', 'food_expense')
                ->where('id', $employeeId)
                ->first();

            if (!$employee) {
                return;
            }

            // Calculate basic salary based on hours worked and daily rate
            // 8 hours is standard workday
            $basicSalary = 0;
            if ($salaryPerDay > 0) {
                if ($totalHoursWorked > 0) {
                    // Calculate based on hours worked
                    $basicSalary = round(($totalHoursWorked / 8) * $salaryPerDay, 2);
                } else if ($daysPresent > 0) {
                    // Fallback to days present if hours not available
                    $basicSalary = round($daysPresent * $salaryPerDay, 2);
                }
            }

            // Calculate allowances based on days present
            $otherAllowances = $daysPresent > 0 && $employee->regular_expense ? 
                round($employee->regular_expense * $daysPresent, 2) : 
                (float)($get('other_allowances') ?? 0);

            $foodAllowance = $daysPresent > 0 && $employee->food_expense ? 
                round($employee->food_expense * $daysPresent, 2) : 
                (float)($get('food_allowance') ?? 0);

            // Set calculated values
            $set('basic_salary', $basicSalary);
            $set('other_allowances', $otherAllowances);
            $set('food_allowance', $foodAllowance);

            // Update gross salary and payable amounts
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
            // Calculate gross salary (all earnings)
            $basicSalary = max(0, (float)($get('basic_salary') ?? 0));
            $otherAllowances = max(0, (float)($get('other_allowances') ?? 0));
            $foodAllowance = max(0, (float)($get('food_allowance') ?? 0));
            
            $gross = $basicSalary + $otherAllowances + $foodAllowance;
            
            // Calculate deductions
            $loanInstallment = max(0, (float)($get('loan_installment') ?? 0));
            $advanceSalary = max(0, (float)($get('advance_salary') ?? 0));
            $pfAmount = max(0, (float)($get('pf_amount') ?? 0));
            $dueLoanAmount = max(0, (float)($get('total_due_loan') ?? 0));
            
            $totalDeductions = $loanInstallment + $advanceSalary + $pfAmount;
            
            // Ensure deductions don't exceed gross salary
            if ($totalDeductions > $gross) {
                // Prioritize deductions: first advance salary, then PF, then loan
                $availableForDeductions = $gross;
                
                // First allocate for advance salary
                $adjustedAdvance = min($advanceSalary, $availableForDeductions);
                $availableForDeductions -= $adjustedAdvance;
                
                // Then allocate for PF
                $adjustedPF = min($pfAmount, $availableForDeductions);
                $availableForDeductions -= $adjustedPF;
                
                // Finally allocate for loan installment
                $adjustedLoan = min($loanInstallment, $availableForDeductions);
                
                // Update the fields with adjusted values
                $set('advance_salary', $adjustedAdvance);
                $set('pf_amount', $adjustedPF);
                $set('loan_installment', $adjustedLoan);
                
                // Recalculate total deductions
                $totalDeductions = $adjustedAdvance + $adjustedPF + $adjustedLoan;
            }
            
            // Calculate payable amount
            $payable = max(0, $gross - $totalDeductions);
            
            // Update due loan amount after cutting installment
            if ($loanInstallment > 0) {
                $newDueLoan = max(0, $dueLoanAmount - $loanInstallment);
                $set('total_due_loan', $newDueLoan);
            }
            
            // Set final values
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
        if (!$record || $record->salary_status !== 'Paid') {
            return;
        }

        try {
            $employeeId = $record->employee_id;
            $industryId = $record->industry_id;
            $loanInstallment = (float)($record->loan_installment ?? 0);
            $advanceSalary = (float)($record->advance_salary ?? 0);

            // Process loan installments
            if ($loanInstallment > 0) {
                $loanDetails = json_decode($record->loan_details, true) ?? [];
                $remainingInstallment = $loanInstallment;

                foreach ($loanDetails as $detail) {
                    if ($remainingInstallment <= 0) break;

                    $loanId = $detail['loan_id'];
                    $amount = (float)($detail['amount'] ?? 0);
                    
                    if ($amount <= 0) continue;
                    
                    // Calculate how much to pay for this loan
                    $deduction = min($amount, $remainingInstallment);
                    $remainingInstallment -= $deduction;
                    
                    // Update loan record
                    DB::table('loans')
                        ->where('id', $loanId)
                        ->increment('amount_paid', $deduction);
                    
                    // Check if loan is fully paid
                    $loan = DB::table('loans')->where('id', $loanId)->first();
                    if ($loan && $loan->amount_paid >= $loan->loan_amount) {
                        DB::table('loans')
                            ->where('id', $loanId)
                            ->update([
                                'loan_status' => 'Completed',
                                'loan_end_date' => now()->toDateString()
                            ]);
                    }
                }
            }

            // Process advance salary (placeholder - actual implementation would be added here)
            if ($advanceSalary > 0 && !empty($record->advance_details)) {
                $advanceDetails = json_decode($record->advance_details, true) ?? [];
                $remainingDeduction = $advanceSalary;
                
                foreach ($advanceDetails as $detail) {
                    if ($remainingDeduction <= 0) break;
                    
                    // Process advance salary settlement
                    // (Implementation would go here)
                }
            }
        } catch (\Exception $e) {
            // Log error (you might want to implement proper logging)
            // This ensures the process doesn't break even if there's an error
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
     * Calculate attendance details
     */
    public static function calculateAttendanceDetails($salaryMonth, $employeeId)
    {
        if (!$salaryMonth || !$employeeId) return null;
        
        try {
            $startDate = Carbon::parse("$salaryMonth-01")->startOfMonth()->toDateString();
            $endDate = Carbon::parse("$salaryMonth-01")->endOfMonth()->toDateString();
            
            // Get working days
            $workingDays = DB::table('working_days')
                ->where('type', 'Working Day')
                ->whereBetween('date', [$startDate, $endDate])
                ->count();
            
            // Default to calendar days if no working days defined
            if ($workingDays == 0) {
                $workingDays = Carbon::parse($endDate)->day;
            }
            
            // Get all attendance records for the employee in the given month
            $attendances = DB::table('attendances')
                ->where('employee_id', $employeeId)
                ->where(function($query) use ($startDate, $endDate) {
                    // Records that overlap with the month
                    $query->where(function($q) use ($startDate, $endDate) {
                        $q->where('attendances_start_date', '<=', $endDate)
                          ->where('attendances_end_date', '>=', $startDate);
                    });
                })
                ->get();
            
            $daysPresent = 0;
            $daysAbsent = 0;
            $totalHoursWorked = 0;
            $overtimeHours = 0;
            
            // Process each attendance record
            foreach ($attendances as $attendance) {
                // Calculate the actual days in the period that fall within this month
                $recordStart = max(Carbon::parse($attendance->attendances_start_date), Carbon::parse($startDate));
                $recordEnd = min(Carbon::parse($attendance->attendances_end_date), Carbon::parse($endDate));
                
                // Calculate days in this attendance record (+1 because diff doesn't include end date)
                $periodDays = $recordStart->diffInDays($recordEnd) + 1;
                
                // Calculate presence based on attendance type
                if ($attendance->attendance_type == 'Full Day') {
                    $daysPresent += $periodDays;
                    $totalHoursWorked += $periodDays * 8; // Standard workday
                } elseif ($attendance->attendance_type == 'Half Day') {
                    $daysPresent += $periodDays * 0.5;
                    $totalHoursWorked += $periodDays * 4; // Half day
                } elseif ($attendance->attendance_type == 'Custom Hours') {
                    $daysPresent += $periodDays;
                    $hoursPerDay = $attendance->worked_hours ?? 8;
                    $totalHoursWorked += $periodDays * $hoursPerDay;
                } elseif ($attendance->attendance_type == 'Absent') {
                    $daysAbsent += $periodDays;
                }
                
                // Process overtime
                if ($attendance->extra_hours) {
                    $overtimeHours += $attendance->extra_hours;
                }
                
                // Adjust for shortfall hours
                if ($attendance->shortfall_hours) {
                    $totalHoursWorked = max(0, $totalHoursWorked - $attendance->shortfall_hours);
                }
            }
            
            // Round values for cleaner display
            $daysPresent = round($daysPresent, 1);
            $totalHoursWorked = round($totalHoursWorked, 1);
            $overtimeHours = round($overtimeHours, 1);
            
            // Calculate days absent from working days if not already calculated
            if ($daysPresent + $daysAbsent < $workingDays) {
                $daysAbsent = $workingDays - $daysPresent;
            }
            
            return [
                'workingDays' => $workingDays,
                'daysPresent' => $daysPresent,
                'daysAbsent' => $daysAbsent,
                'totalHoursWorked' => $totalHoursWorked,
                'overtimeHours' => $overtimeHours,
            ];
        } catch (\Exception $e) {
            // Return default values in case of error
            return [
                'workingDays' => 0,
                'daysPresent' => 0,
                'daysAbsent' => 0,
                'totalHoursWorked' => 0,
                'overtimeHours' => 0,
            ];
        }
    }

    /**
     * Populate salary rate from employee data
     */
    protected static function populateSalaryRate(callable $get, callable $set): void
    {
        $employeeId = $get('employee_id');
        
        if (!$employeeId) {
            return;
        }
        
        try {
            $employee = Employee::find($employeeId);
            if ($employee && $employee->salary_per_day) {
                $set('salary_per_day', $employee->salary_per_day);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}