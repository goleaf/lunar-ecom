<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductCustomization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class CustomizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'customizations';

    protected static ?string $recordTitleAttribute = 'field_label';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customization_type')
                    ->options([
                        'text' => 'Text',
                        'image' => 'Image',
                        'option' => 'Option',
                        'color' => 'Color',
                        'number' => 'Number',
                        'date' => 'Date',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('field_name')
                    ->helperText('Internal identifier (unique per product), e.g. "engraving_text".')
                    ->maxLength(255)
                    ->required()
                    ->unique(
                        table: (new ProductCustomization())->getTable(),
                        column: 'field_name',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('product_id', $this->getOwnerRecord()->getKey()),
                    ),

                Forms\Components\TextInput::make('field_label')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('placeholder')
                    ->maxLength(255)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_required')
                    ->default(false),

                Forms\Components\TextInput::make('min_length')
                    ->numeric()
                    ->minValue(0)
                    ->nullable(),

                Forms\Components\TextInput::make('max_length')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('pattern')
                    ->maxLength(255)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('allowed_values')
                    ->label('Allowed values (JSON)')
                    ->rows(3)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    })
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('allowed_formats')
                    ->label('Allowed formats (JSON)')
                    ->rows(2)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    })
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('max_file_size_kb')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('min_width')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('max_width')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('min_height')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('max_height')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('aspect_ratio_width')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('aspect_ratio_height')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\TextInput::make('price_modifier')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Forms\Components\Select::make('price_modifier_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'per_character' => 'Per character',
                        'per_image' => 'Per image',
                    ])
                    ->default('fixed')
                    ->required(),

                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),

                Forms\Components\Toggle::make('show_in_preview')
                    ->default(true),

                Forms\Components\Textarea::make('preview_settings')
                    ->label('Preview settings (JSON)')
                    ->rows(4)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    })
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('template_image')
                    ->disk('public')
                    ->directory('customizations/template-images')
                    ->image()
                    ->maxSize(2048)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('example_values')
                    ->label('Example values (JSON)')
                    ->rows(3)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                    ->dehydrateStateUsing(function ($state) {
                        if (! is_string($state) || trim($state) === '') {
                            return null;
                        }

                        $decoded = json_decode($state, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    })
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('field_label')
                    ->label('Label')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('field_name')
                    ->label('Field')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('customization_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_modifier')
                    ->label('Price')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->reorderable('display_order')
            ->defaultSort('display_order');
    }
}

