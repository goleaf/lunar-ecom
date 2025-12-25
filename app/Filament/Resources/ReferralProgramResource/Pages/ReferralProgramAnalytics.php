<?php

namespace App\Filament\Resources\ReferralProgramResource\Pages;

use App\Filament\Resources\ReferralProgramResource;
use App\Models\ReferralProgram;
use App\Services\ReferralAnalyticsService;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ReferralProgramAnalytics extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string $resource = ReferralProgramResource::class;

    protected static string $view = 'filament.resources.referral-program-resource.pages.referral-program-analytics';

    public ?array $dateRange = [
        'start' => null,
        'end' => null,
    ];

    public ReferralProgram $record;

    public function mount(int | string $record): void
    {
        $this->record = ReferralProgramResource::getRecord($record);
        $this->dateRange['start'] = now()->subDays(30)->format('Y-m-d');
        $this->dateRange['end'] = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dateRange.start')
                    ->label('Start Date')
                    ->default(now()->subDays(30))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),

                DatePicker::make('dateRange.end')
                    ->label('End Date')
                    ->default(now())
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetTable()),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('clicks')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('signups')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('first_purchases')
                    ->label('First Purchases')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('repeat_purchases')
                    ->label('Repeat Purchases')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_orders')
                    ->label('Total Orders')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_revenue')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('rewards_issued')
                    ->label('Rewards Issued')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('rewards_value')
                    ->label('Rewards Value')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('click_to_signup_rate')
                    ->label('Click→Signup %')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('signup_to_purchase_rate')
                    ->label('Signup→Purchase %')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('overall_conversion_rate')
                    ->label('Overall Conversion %')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        $startDate = $this->dateRange['start'] ? Carbon::parse($this->dateRange['start']) : now()->subDays(30);
        $endDate = $this->dateRange['end'] ? Carbon::parse($this->dateRange['end']) : now();

        return $this->record->analytics()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
    }

    public function getSummary(): array
    {
        $startDate = $this->dateRange['start'] ? Carbon::parse($this->dateRange['start']) : now()->subDays(30);
        $endDate = $this->dateRange['end'] ? Carbon::parse($this->dateRange['end']) : now();

        $service = app(ReferralAnalyticsService::class);
        return $service->getProgramSummary($this->record, $startDate, $endDate);
    }
}

