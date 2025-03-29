<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceSalariesResource\Pages;
use App\Filament\Resources\AdvanceSalariesResource\RelationManagers;
use App\Models\AdvanceSalaries;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\{Select, DatePicker, TextInput, Textarea, Section};
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;

class AdvanceSalariesResource extends Resource
{
    protected static ?string $model = AdvanceSalaries::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $tenantRelationshipName = 'advance_salaries';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2) // Creates a grid with two equal-width columns
                ->schema([
                    Section::make('Employee Details')
                        ->schema([
                            Select::make('employee_id')
                                ->label('Employee')
                                ->relationship('employee', 'full_name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            DatePicker::make('requested_date')
                                ->label('Requested Date')
                                ->default(now())
                                ->required(),
                        ])->columnSpan(1),

                    Section::make('Advance Salary Details')
                        ->schema([
                            TextInput::make('advance_salary_amount')
                                ->label('Advance Salary Amount (₹)')
                                ->numeric()
                                ->required(),
                            Forms\Components\DatePicker::make('advance_salary_month')
                                ->native(false)
                                ->label('Advance Salary Month')
                                ->displayFormat('F Y')
                                ->required()
                                ->closeOnDateSelection(true),
                            Textarea::make('reason')
                                ->label('Reason')
                                ->nullable(),
                        ])->columnSpan(1),

                    Section::make('Payment & Approval')
                        ->schema([
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'Bank Transfer' => 'Bank Transfer',
                                    'UPI' => 'UPI',
                                    'Cash' => 'Cash',
                                ])
                                ->required(),
                            Select::make('advance_salary_status')
                                ->label('Advance Salary Status')
                                ->options([
                                    'Pending' => 'Pending',
                                    'Paid' => 'Paid',
                                    'Hold' => 'Hold',
                                ])
                                ->default('Pending')
                                ->required(),
                            Select::make('approved_by')
                                ->label('Approved By')
                                ->required()
                                ->relationship('approver', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                        ]),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            TextColumn::make('worker.name')
                ->label('Worker')
                ->searchable()
                ->sortable(),

            TextColumn::make('requested_date')
                ->label('Requested Date')
                ->date()
                ->sortable(),

            TextColumn::make('advance_salary_amount')
                ->label('Advance Salary Amount (₹)')
                ->money('INR')
                ->sortable(),

            TextColumn::make('advance_salary_month')
                ->label('Advance Salary Month')
                ->sortable(),
            TextColumn::make('reason')
                ->label('Reason')
                ->limit(50)
                ->wrap(),

            TextColumn::make('payment_method')
                ->label('Payment Method')
                ->sortable(),

            TextColumn::make('advance_salary_status')
                ->label('Status')->badge()
                ->colors([
                    'Pending' => 'warning',
                    'Paid' => 'success',
                    'Hold' => 'gray',
                ])
                ->sortable(),

            TextColumn::make('approver.name')
                ->label('Approved By')
                ->sortable()
                ->searchable(),

            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime('d M Y, H:i')
                ->sortable(),

            TextColumn::make('updated_at')
                ->label('Updated At')
                ->dateTime('d M Y, H:i')
                ->sortable(),
            ])
            ->filters([
                SelectFilter::make('advance_salary_status')
                ->options([
                    'Pending' => 'Pending',
                    'Paid' => 'Paid',
                    'Hold' => 'Hold',
                ])
                ->label('Filter by Status'),
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
            'index' => Pages\ListAdvanceSalaries::route('/'),
            'create' => Pages\CreateAdvanceSalaries::route('/create'),
            'edit' => Pages\EditAdvanceSalaries::route('/{record}/edit'),
        ];
    }
}
