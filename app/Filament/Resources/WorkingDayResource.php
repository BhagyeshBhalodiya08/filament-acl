<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingDayResource\Pages;
use App\Filament\Resources\WorkingDayResource\RelationManagers;
use App\Models\WorkingDay;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkingDayResource extends Resource
{
    protected static ?string $model = WorkingDay::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $label = 'Calendar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'Working Day' => 'Working Day',
                    'Holiday' => 'Holiday',
                    'Weekend' => 'Weekend',
                ])
                ->required(),
            Forms\Components\TextInput::make('remark')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->colors([
                        'success' => 'Working Day',
                        'warning' => 'Weekend',
                        'danger' => 'Holiday',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')->limit(30),
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
            'index' => \App\Filament\Resources\AttendanceResource\Pages\AttendanceCalendar::route('/'),
            // 'index' => \App\Filament\Resources\AttendanceResource\Widgets\EventCalendarWidget::class,
            'create' => Pages\CreateWorkingDay::route('/create'),
            'edit' => Pages\EditWorkingDay::route('/{record}/edit'),
        ];
    }
}
