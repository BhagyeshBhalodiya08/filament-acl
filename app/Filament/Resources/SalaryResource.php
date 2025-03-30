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
                    Select::make('worker_id')
                        ->relationship('worker', 'full_name')
                        ->required()
                        ->label('Employee')
                        ->live(),
                    Forms\Components\DatePicker::make('salary_month')
                        ->native(false)
                        ->displayFormat('F Y')
                        ->format('Y-m')
                        ->required()
                        ->live(),
                ]),
                
                Section::make('Attendance Details')->schema([
                    TextInput::make('total_working_days')->numeric()->required(),
                    TextInput::make('days_present')->numeric()->required(),
                    TextInput::make('days_absent')->numeric()->required(),
                    TextInput::make('total_hours_worked')->numeric()->disabled(),
                    TextInput::make('overtime_hours')->numeric()->disabled(),
                    TextInput::make('half_day_count')->numeric(),
                ])->columns(2)->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                    $workerId = $get('worker_id');
                    $salaryMonth = $get('salary_month');
                
                    if ($workerId && $salaryMonth) {
                        // Fetch attendance data for the selected worker and month
                        $attendance = DB::table('attendances')
                            ->where('worker_id', $workerId)
                            ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$salaryMonth])
                            ->selectRaw("
                                COUNT(*) as total_working_days,
                                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as days_present,
                                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as days_absent,
                                SUM(total_hours) as total_hours_worked,
                                SUM(overtime_hours) as overtime_hours,
                                SUM(CASE WHEN status = 'half-day' THEN 1 ELSE 0 END) as half_day_count
                            ")
                            ->first();
                
                        // Set values in form fields
                        $set('total_working_days', $attendance->total_working_days ?? 0);
                        $set('days_present', $attendance->days_present ?? 0);
                        $set('days_absent', $attendance->days_absent ?? 0);
                        $set('total_hours_worked', $attendance->total_hours_worked ?? 0);
                        $set('overtime_hours', $attendance->overtime_hours ?? 0);
                        $set('half_day_count', $attendance->half_day_count ?? 0);
                    }
                }),
                
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
}
