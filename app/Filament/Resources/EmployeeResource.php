<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\{TextInput, Textarea, DatePicker, Select, NumberInput};
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\BaseResource;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
// class EmployeeResource extends Resource
class EmployeeResource extends BaseResource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')->schema([
                    TextInput::make('full_name')->required(),
                    PhoneInput::make('phone_number'),
                    Textarea::make('address')->nullable(),
                    DatePicker::make('joining_date')->nullable(),
                ])->columns(2),
    
                Forms\Components\Section::make('Job Details')->schema([
                    TextInput::make('designation')->nullable(),
                    Select::make('work_type')->default('Full-time')
                        ->options([
                            'Full-time' => 'Full-time',
                            'Part-time' => 'Part-time',
                            'Contract' => 'Contract',
                        ])->nullable(),
                    TextInput::make('manager_name')->nullable(),
                    // Select::make('industry_id')
                    //     ->relationship('industry', 'name')
                    //     ->searchable()
                    //     ->multiple()
                    //     ->options(fn () => \App\Models\Industry::pluck('name', 'id'))
                    //     ->required(),
                ])->columns(2),
    
                Forms\Components\Section::make('Financial Details')->schema([
                    TextInput::make('salary_per_day')->numeric()->nullable()->prefix('₹'),
                    TextInput::make('pf_amount')->numeric()->nullable()->prefix('₹'),
                    TextInput::make('regular_expense')->numeric()->nullable()->prefix('₹'),
                    TextInput::make('food_expense')->numeric()->nullable()->prefix('₹'),
                    TextInput::make('bank_account_number')->nullable(),
                ])->columns(2),
    
                Forms\Components\Section::make('Emergency Contact')->schema([
                    TextInput::make('emergency_contact')->tel()->nullable(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->searchable(),
                TextColumn::make('phone_number')->sortable(),
                TextColumn::make('department')->sortable(),
                TextColumn::make('designation')->sortable(),
                TextColumn::make('work_type')->sortable(),
                TextColumn::make('industry.name')->badge()->sortable()->visible(auth()->user()->isSuperAdmin() ?? false),
                TextColumn::make('created_at')->date(),
            ])
            ->filters([
                SelectFilter::make('industry_id')
                    ->relationship('industry', 'name'),
                SelectFilter::make('work_type')
                    ->options([
                        'Full-time' => 'Full-time',
                        'Part-time' => 'Part-time',
                        'Contract' => 'Contract',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_attendances')
                    ->label('View Attendances')->icon('heroicon-s-eye')
                    ->modalHeading('Attendances')
                    ->modalContent(fn ($record): \Illuminate\Contracts\View\View => view('modals.calendar-modal', ['employee_id' => $record->id]))
                    ->modalSubmitAction(false),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
