<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductBadgeAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'badgeAssignments';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('badge_id')
                    ->label('Badge')
                    ->relationship('badge', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('assignment_type')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automatic',
                    ])
                    ->default('manual')
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->nullable(),

                Forms\Components\Select::make('display_position')
                    ->options([
                        'top-left' => 'Top left',
                        'top-right' => 'Top right',
                        'bottom-left' => 'Bottom left',
                        'bottom-right' => 'Bottom right',
                        'center' => 'Center',
                    ])
                    ->nullable(),

                Forms\Components\DateTimePicker::make('starts_at')
                    ->nullable(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->nullable(),

                Forms\Components\Textarea::make('visibility_rules')
                    ->label('Visibility rules (JSON)')
                    ->rows(6)
                    ->helperText('Optional JSON config (e.g. {"show_on_product": true, "show_on_search": false}).')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        : (string) ($state ?? '')
                    )
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state;
                        }

                        if (!is_string($state) || trim($state) === '') {
                            return null;
                        }

                        return json_decode($state, true);
                    })
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('badge.name')
                    ->label('Badge')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('badge.type')
                    ->label('Type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assignment_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('display_position')
                    ->label('Position')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // NOTE: `assigned_by` references `users`, not `staff`.
                        $data['assigned_at'] ??= now();
                        $data['assigned_by'] ??= auth('web')->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }
}

