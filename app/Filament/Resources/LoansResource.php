<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoansResource\Pages;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Loans;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class LoansResource extends Resource
{
    protected static ?string $model = Loans::class;

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2) // Creates a grid with two equal-width columns
                ->schema([
                    // Section 1: Applicant Information
                    Forms\Components\Section::make('Applicant Information')->collapsible()
                        ->schema([
                            Forms\Components\Select::make('employee_id')
                                ->label('Employee')
                                ->options(Employee::pluck('full_name', 'id'))
                                ->searchable()
                                ->required(),
                            Forms\Components\DatePicker::make('application_date')
                                ->label('Application Date')
                                ->default(Carbon::today())
                                ->required(),
                        ])->columnSpan(2),

                    // Section 2: Loan Details
                    Forms\Components\Section::make('Loan Details')->collapsible()
                        ->schema([
                            Forms\Components\TextInput::make('loan_amount')
                                ->label('Loan Amount (₹)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->live(debounce: 700)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateInstallmentDetails($set, $get);
                                }),
                            Forms\Components\DatePicker::make('loan_start_date')
                                ->label('Loan Start Date')
                                ->default(Carbon::today()->addMonth()->startOfMonth())
                                ->required()
                                ->afterOrEqual('application_date'),
                            Forms\Components\DatePicker::make('loan_end_date')
                                ->label('Loan End Date')
                                ->disabled() // Auto-calculated, not editable
                                ->hint('Calculated based on total installments'),
                            Forms\Components\TextInput::make('installment_amount_per_month')
                                ->label('Installment Amount per Month (₹)')
                                ->numeric()
                                ->live(debounce: 700)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateInstallmentDetails($set, $get);
                                }),
                            Forms\Components\TextInput::make('total_installments')
                                ->label('Total Installments')
                                ->numeric()
                                ->minValue(1)
                                ->live(debounce: 700)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateInstallmentDetails($set, $get);
                                }),
                        ])->columnSpan(1),

                    // Section 3: Additional Information
                    Forms\Components\Section::make('Additional Information')->collapsible()
                        ->schema([
                            Forms\Components\Select::make('loan_status')
                                ->label('Loan Status')
                                ->options([
                                    'Pending' => 'Pending',
                                    'Approved' => 'Approved',
                                    'Rejected' => 'Rejected',
                                    'Completed' => 'Completed',
                                ])
                                ->default('Pending')
                                ->required(),
                            Forms\Components\Textarea::make('loan_purpose')
                                ->label('Loan Purpose')
                                ->rows(2)
                                ->maxLength(255),
                            Forms\Components\Select::make('disbursement_method')
                                ->label('Disbursement Method')
                                ->options([
                                    'Bank Transfer' => 'Bank Transfer',
                                    'UPI' => 'UPI',
                                    'Cash' => 'Cash',
                                ])
                                ->required(),
                            Forms\Components\Select::make('loan_approved_by')
                                ->label('Loan Approved By')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->nullable(), // Not required until approved
                            Forms\Components\Textarea::make('remark')
                                ->label('Remark')
                                ->rows(3),
                        ])->columnSpan(1),
                Forms\Components\Section::make('Repayment History')
                    ->schema([
                        Forms\Components\Repeater::make('repayments')
                            ->relationship()
                            ->disabled()
                            ->schema([
                                Forms\Components\TextInput::make('salary_month')->label('Month'),
                                Forms\Components\TextInput::make('amount')->label('Amount'),
                                Forms\Components\DatePicker::make('paid_at')->label('Paid On'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (?Loans $record) => $record !== null)
                    ->collapsible(),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_date')
                    ->label('Application Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_installments')
                    ->label('Total Installments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('installment_amount_per_month')
                    ->label('Installment Amount/Month')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Completed' => 'gray',
                    ]),
                Tables\Columns\TextColumn::make('disbursement_method')
                    ->label('Disbursement Method')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('Remark')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('loan_status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->action(function (Loans $record) {
                        $record->update([
                            'loan_status' => 'Approved',
                            'loan_approved_by' => auth()->id(),
                            'updated_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Loans $record) => $record->loan_status === 'Pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function calculateInstallmentDetails(Forms\Set $set, Forms\Get $get): void
    {
        $loanAmount = $get('loan_amount');
        $installmentAmountPerMonth = $get('installment_amount_per_month');
        $totalInstallments = $get('total_installments');
        $loanStartDate = $get('loan_start_date');

        // Case 1: Calculate total_installments if loan_amount and installment_amount_per_month are set
        if (is_numeric($loanAmount) && is_numeric($installmentAmountPerMonth) && $installmentAmountPerMonth > 0) {
            $calculatedInstallments = ceil($loanAmount / $installmentAmountPerMonth);
            $set('total_installments', $calculatedInstallments);

            // Calculate loan_end_date if loan_start_date is available
            if ($loanStartDate) {
                $endDate = Carbon::parse($loanStartDate)->addMonths($calculatedInstallments)->toDateString();
                $set('loan_end_date', $endDate);
            }
        }

        // Case 2: Calculate installment_amount_per_month if loan_amount and total_installments are set
        elseif (is_numeric($loanAmount) && is_numeric($totalInstallments) && $totalInstallments > 0) {
            $calculatedInstallment = round($loanAmount / $totalInstallments, 2);
            $set('installment_amount_per_month', $calculatedInstallment);

            // Calculate loan_end_date if loan_start_date is available
            if ($loanStartDate) {
                $endDate = Carbon::parse($loanStartDate)->addMonths($totalInstallments)->toDateString();
                $set('loan_end_date', $endDate);
            }
        }
    }

    public static function getRelations(): array
    {
        return [
            // Add relations if needed (e.g., EmployeeRelationManager)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoans::route('/create'),
            'edit' => Pages\EditLoans::route('/{record}/edit'),
        ];
    }
}