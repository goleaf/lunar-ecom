<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Lunar\Models\Url;

class UrlsRelationManager extends RelationManager
{
    protected static string $relationship = 'urls';

    protected static ?string $recordTitleAttribute = 'slug';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('language_id')
                    ->relationship('language', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->rules([
                        Rule::unique((new Url())->getTable(), 'slug')
                            ->where(fn ($query, $get) => $query->where('language_id', $get('language_id')))
                            ->ignore(fn (?Url $record) => $record),
                    ])
                    ->maxLength(255),
                Forms\Components\Toggle::make('default')
                    ->label('Default'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('language.name')
                    ->label('Language')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\IconColumn::make('default')
                    ->boolean()
                    ->label('Default'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (UrlsRelationManager $livewire, Url $record) {
                        if ($record->default) {
                            $livewire->getOwnerRecord()
                                ->urls()
                                ->where('id', '!=', $record->id)
                                ->update(['default' => false]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (UrlsRelationManager $livewire, Url $record) {
                        if ($record->default) {
                            $livewire->getOwnerRecord()
                                ->urls()
                                ->where('id', '!=', $record->id)
                                ->update(['default' => false]);
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
