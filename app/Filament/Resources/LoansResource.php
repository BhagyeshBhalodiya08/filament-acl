<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoansResource\Pages;
use App\Filament\Resources\LoansResource\RelationManagers;
use App\Models\Loans;
use App\Models\User;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\{Section, Select, DatePicker, TextInput, Textarea};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;

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
                    Section::make('Applicant Information')->collapsible()
                        ->schema([
                            Select::make('employee_id')
                                ->label('Employee')
                                ->options(Employee::pluck('full_name', 'id'))
                                ->searchable()
                                ->required(),
                            DatePicker::make('application_date')
                                ->label('Application Date')
                                ->default(now()),
                        ])->columnSpan(2),

                    // Section 2: Loan Details
                    Section::make('Loan Details')->collapsible()
                        ->schema([
                            TextInput::make('loan_amount')
                                ->label('Loan Amount (₹)')
                                ->numeric()
                                ->required()
                                ->live(debounce: 700)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateTotalInstallments($set, $get);
                                }),
                            DatePicker::make('loan_start_date')
                                ->label('Loan Start Date')
                                ->default(now())
                                ->required(),
                            DatePicker::make('loan_end_date')
                                ->label('Loan End Date'),
                            TextInput::make('installment_amount_per_month')
                                ->label('Installment Amount per Month (₹)')
                                ->numeric()
                                ->required()
                                ->live(debounce: 700)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateTotalInstallments($set, $get);
                                }),
                            TextInput::make('total_installments')
                                ->label('Total Installments')
                                ->numeric()
                                ->required(),
                        ])->columnSpan(1),

                    // Section 3: Additional Information
                    Section::make('Additional Information')->collapsible()
                    ->schema([
                        Select::make('loan_status')
                            ->label('Loan Status')
                            ->options([
                                'Pending' => 'Pending',
                                'Approved' => 'Approved',
                                'Rejected' => 'Rejected',
                                'Completed' => 'Completed',
                            ])
                            ->required(),
                        Textarea::make('loan_purpose')
                            ->label('Loan Purpose')
                            ->rows(2),
                        Select::make('disbursement_method')
                            ->label('Disbursement Method')
                            ->options([
                                'Bank Transfer' => 'Bank Transfer',
                                'UPI' => 'UPI',
                                'Cash' => 'Cash',
                            ])
                            ->required(),
                        Select::make('loan_approved_by')
                            ->label('Loan Approved By')
                            ->options(User::pluck('name', 'id'))
                            ->required(),
                        Textarea::make('remark')
                            ->label('Remark')
                            ->rows(3),
                    ])->columnSpan(1),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee ID')
                    ->sortable(),
                TextColumn::make('application_date')
                    ->label('Application Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('loan_start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('loan_end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_installments')
                    ->label('Total Installments')
                    ->sortable(),
                TextColumn::make('installment_amount_per_month')
                    ->label('Installment Amount/Month')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('loan_status')
                    ->label('Status')->badge()->sortable()
                    ->colors([
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'paid' => 'gray',
                    ]),
                TextColumn::make('disbursement_method')
                    ->label('Disbursement Method')
                    ->sortable(),
                TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable(),
                TextColumn::make('remark')
                    ->label('Remark')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->datetime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->datetime()
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

    protected static function calculateTotalInstallments(Forms\Set $set, Forms\Get $get): void
    {
        $loanAmount = $get('loan_amount');
        $installmentAmountPerMonth = $get('installment_amount_per_month');

        if (is_numeric($loanAmount) && is_numeric($installmentAmountPerMonth) && $installmentAmountPerMonth > 0) {
            $totalInstallments = ceil($loanAmount / $installmentAmountPerMonth);
            $set('total_installments', $totalInstallments);
        } else {
            $set('total_installments', null);
        }
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
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoans::route('/create'),
            'edit' => Pages\EditLoans::route('/{record}/edit'),
        ];
    }
}
