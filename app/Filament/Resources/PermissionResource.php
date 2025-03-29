<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Permission;
use App\Filament\Resources\BaseResource;

// class PermissionResource extends Resource
class PermissionResource extends BaseResource
{
    protected static ?string $model = Permission::class;
    
    protected static ?string $navigationGroup = 'Administrator';

    // protected static ?string $tenantRelationshipName = 'permissions'; // 🔹 Add this line
    
    // protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

                Forms\Components\TextInput::make('guard_name')
                    ->required()
                    ->maxLength(255),

                // Show industry_id only if the current user is a super user
                Forms\Components\Select::make('industry_id')
                    ->label('Industry')
                    ->relationship('industry', 'name') // Assuming Industry model has 'name'
                    ->hidden(fn () => auth()->user()->super_user !== 'yes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->sortable()
                    ->searchable(),

                // Show industry_id only if the user is a super user
                Tables\Columns\TextColumn::make('industry.name')
                    ->label('Industry')
                    ->sortable()
                    ->searchable()
                    ->hidden(fn () => auth()->user()->super_user !== 'yes'),
            ])
            ->filters([
                 Tables\Filters\SelectFilter::make('industry_id')
                    ->label('Industry')
                    ->relationship('industry', 'name')
                    ->visible(fn () => auth()->user()->super_user === 'yes'),
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
