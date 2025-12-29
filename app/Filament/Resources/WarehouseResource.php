<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Warehouses';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Warehouse')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Lower number = higher priority for fulfillment.'),

                        Forms\Components\Toggle::make('is_dropship')
                            ->default(false),

                        Forms\Components\Toggle::make('is_virtual')
                            ->default(false),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('postcode')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('country')
                            ->label('Country (ISO 3166-1 alpha-2)')
                            ->maxLength(2),

                        Forms\Components\TextInput::make('phone')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_dropship')
                    ->boolean()
                    ->label('Dropship')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_virtual')
                    ->boolean()
                    ->label('Virtual')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}

