<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SearchSynonymResource\Pages;
use App\Models\SearchSynonym;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SearchSynonymResource extends Resource
{
    protected static ?string $model = SearchSynonym::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Insights';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Synonym')
                    ->schema([
                        Forms\Components\TextInput::make('term')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TagsInput::make('synonyms')
                            ->required()
                            ->helperText('Add one or more synonyms; used to expand search queries.')
                            ->reorderable()
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('term')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('synonyms')
                    ->label('Synonyms')
                    ->formatStateUsing(fn ($state): string => implode(', ', (array) ($state ?? [])))
                    ->limit(60)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSearchSynonyms::route('/'),
            'create' => Pages\CreateSearchSynonym::route('/create'),
            'edit' => Pages\EditSearchSynonym::route('/{record}/edit'),
        ];
    }
}

