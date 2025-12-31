<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomizationTemplateResource\Pages\CreateCustomizationTemplate;
use App\Filament\Resources\CustomizationTemplateResource\Pages\EditCustomizationTemplate;
use App\Filament\Resources\CustomizationTemplateResource\Pages\ListCustomizationTemplates;
use App\Filament\Resources\CustomizationTemplateResource\Pages\ViewCustomizationTemplate;
use App\Models\CustomizationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomizationTemplateResource extends Resource
{
    protected static ?string $model = CustomizationTemplate::class;

    protected static ?string $slug = 'ops-customization-templates';

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Customization Templates';

    protected static ?int $navigationSort = 60;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\TextInput::make('category')
                            ->maxLength(100)
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Data')
                    ->schema([
                        Forms\Components\Textarea::make('template_data')
                            ->label('Template data (JSON)')
                            ->rows(6)
                            ->required()
                            ->rules(['json'])
                            ->helperText('Example: {"font":"Arial","font_size":24,"color":"#000000"}')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                            ->dehydrateStateUsing(function ($state) {
                                if (! is_string($state) || trim($state) === '') {
                                    return [];
                                }

                                $decoded = json_decode($state, true);
                                return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                            })
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('preview_image')
                            ->label('Preview image')
                            ->disk('public')
                            ->directory('customizations/template-previews')
                            ->image()
                            ->maxSize(2048)
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('usage_count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('usage_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn (): array => CustomizationTemplate::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all())
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomizationTemplates::route('/'),
            'create' => CreateCustomizationTemplate::route('/create'),
            'view' => ViewCustomizationTemplate::route('/{record}'),
            'edit' => EditCustomizationTemplate::route('/{record}/edit'),
        ];
    }
}

