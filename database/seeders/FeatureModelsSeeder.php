<?php

namespace Database\Seeders;

use App\Models\AvailabilityBooking;
use App\Models\AvailabilityRule;
use App\Models\B2BContract;
use App\Models\CheckoutLock;
use App\Models\ContractAudit;
use App\Models\ContractCreditLimit;
use App\Models\ContractPrice;
use App\Models\ContractPurchaseOrder;
use App\Models\ContractRule;
use App\Models\ContractSharedCart;
use App\Models\CustomizationExample;
use App\Models\CustomizationTemplate;
use App\Models\FitFeedback;
use App\Models\FitFinderAnswer;
use App\Models\FitFinderQuestion;
use App\Models\FitFinderQuiz;
use App\Models\InventoryLevel;
use App\Models\InventoryTransaction;
use App\Models\LowStockAlert;
use App\Models\MarginAlert;
use App\Models\OrderItemCustomization;
use App\Models\PriceHistory;
use App\Models\PriceList;
use App\Models\PriceSimulation;
use App\Models\PriceSnapshot;
use App\Models\Product;
use App\Models\ProductAnswer;
use App\Models\ProductAvailability;
use App\Models\ProductBadge;
use App\Models\ProductBadgeAssignment;
use App\Models\ProductBadgePerformance;
use App\Models\ProductBadgeRule;
use App\Models\ProductCustomization;
use App\Models\ProductImport;
use App\Models\ProductImportRollback;
use App\Models\ProductImportRow;
use App\Models\ProductQuestion;
use App\Models\ProductSchedule;
use App\Models\ProductScheduleHistory;
use App\Models\ProductVariant;
use App\Models\ReferralAnalytics;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralGroupOverride;
use App\Models\ReferralLandingTemplate;
use App\Models\ReferralProgram;
use App\Models\ReferralReward;
use App\Models\ReferralRewardIssuance;
use App\Models\ReferralRule;
use App\Models\ReferralTracking;
use App\Models\ReferralUserOverride;
use App\Models\SmartCollectionRule;
use App\Models\StockMovement;
use App\Models\StockNotification;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;

/**
 * Seeds feature-domain models that are not covered by the core CompleteSeeder.
 *
 * Goals:
 * - Ensure every feature has usable demo data in admin (Filament) and frontend.
 * - Create realistic relations (contracts->price lists->prices, referral programs->rules->codes, etc.)
 * - Be resilient to schema drift (filters attributes to existing columns when needed).
 *
 * Safe to run multiple times (tries to only top-up when missing).
 */
class FeatureModelsSeeder extends Seeder
{
    /**
     * Cache of table => column lookup (for schema drift-safe inserts).
     *
     * @var array<string, array<string, true>>
     */
    protected array $tableColumnCache = [];

    public function run(): void
    {
        $this->command?->info('🧩 Seeding feature models + relations...');

        // Ensure core data exists (products/customers/users) so relations have something to connect to.
        if (!Language::query()->exists()) {
            $this->call(LanguageSeeder::class);
        }
        if (!Currency::query()->exists()) {
            $this->call(CurrencySeeder::class);
        }
        if (!Product::query()->exists()) {
            // Creates products, variants, customers, orders, etc.
            $this->call(CompleteSeeder::class);
        }

        // Ensure Filament resources backed by factories have data too.
        $this->seedFactoryBackedResources();

        $this->seedUserGroups();
        $this->seedB2BContracts();
        $this->seedReferralPrograms();
        $this->seedWarehousesAndInventory();
        $this->seedCustomizations();
        $this->seedProductBadges();
        $this->seedCheckoutLocks();

        $this->command?->info('✅ Feature models seeded.');
    }

    /**
     * Top up data for Filament resources that already have factories but are not part of CompleteSeeder.
     *
     * This keeps the admin panel "fully populated" without having to manually maintain many dedicated seeders.
     */
    protected function seedFactoryBackedResources(): void
    {
        $products = Product::query()->inRandomOrder()->limit(80)->get();
        $variants = ProductVariant::query()->inRandomOrder()->limit(250)->get();
        $collections = \App\Models\Collection::query()->inRandomOrder()->limit(80)->get();

        $currencyId = Currency::query()->where('default', true)->value('id') ?? Currency::query()->value('id');
        $channelId = Channel::query()->where('default', true)->value('id') ?? Channel::query()->value('id');
        $customerGroupId = \Lunar\Models\CustomerGroup::query()->where('default', true)->value('id')
            ?? \Lunar\Models\CustomerGroup::query()->value('id');

        // Price history
        if (Schema::hasTable((new PriceHistory())->getTable()) && $variants->isNotEmpty()) {
            $target = 120;
            $missing = max(0, $target - PriceHistory::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                $variant = $variants->random();
                PriceHistory::factory()
                    ->forVariant($variant)
                    ->create([
                        'currency_id' => $currencyId,
                        'channel_id' => $channelId,
                        'customer_group_id' => $customerGroupId,
                    ]);
            }
        }

        // Price simulations
        if (Schema::hasTable((new PriceSimulation())->getTable()) && $variants->isNotEmpty() && $currencyId) {
            $target = 80;
            $missing = max(0, $target - PriceSimulation::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                $variant = $variants->random();
                PriceSimulation::factory()->create([
                    'product_variant_id' => $variant->id,
                    'currency_id' => $currencyId,
                    'channel_id' => $channelId,
                    'customer_group_id' => $customerGroupId,
                    'customer_id' => null,
                ]);
            }
        }

        // Margin alerts
        if (Schema::hasTable((new MarginAlert())->getTable()) && $variants->isNotEmpty()) {
            $target = 40;
            $missing = max(0, $target - MarginAlert::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                $variant = $variants->random();
                MarginAlert::factory()->create([
                    'product_variant_id' => $variant->id,
                ]);
            }
        }

        // Product availability records
        if (Schema::hasTable((new ProductAvailability())->getTable()) && $products->isNotEmpty()) {
            $target = 25;
            $missing = max(0, $target - ProductAvailability::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                if ($variants->isNotEmpty() && fake()->boolean(40)) {
                    $variant = $variants->random();
                    ProductAvailability::factory()->forVariant($variant)->create([
                        'is_active' => true,
                    ]);
                } else {
                    ProductAvailability::factory()->create([
                        'product_id' => $products->random()->id,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // Product schedules + histories
        if (Schema::hasTable((new ProductSchedule())->getTable()) && $products->isNotEmpty()) {
            $target = 25;
            $missing = max(0, $target - ProductSchedule::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                ProductSchedule::factory()->create([
                    'product_id' => $products->random()->id,
                ]);
            }
        }

        if (Schema::hasTable((new ProductScheduleHistory())->getTable()) && ProductSchedule::query()->exists()) {
            $target = 30;
            $missing = max(0, $target - ProductScheduleHistory::query()->count());
            $schedules = ProductSchedule::query()->inRandomOrder()->limit(50)->get();
            for ($i = 0; $i < $missing; $i++) {
                $schedule = $schedules->isNotEmpty() ? $schedules->random() : null;
                if (!$schedule) {
                    break;
                }
                ProductScheduleHistory::factory()->create([
                    'product_schedule_id' => $schedule->id,
                    'product_id' => $schedule->product_id,
                ]);
            }
        }

        // Smart collection rules
        if (Schema::hasTable((new SmartCollectionRule())->getTable()) && $collections->isNotEmpty()) {
            $target = 30;
            $missing = max(0, $target - SmartCollectionRule::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                SmartCollectionRule::factory()->forCollection($collections->random())->create();
            }
        }

        // Product Q&A (questions + answers)
        if (Schema::hasTable((new ProductQuestion())->getTable()) && $products->isNotEmpty()) {
            $target = 40;
            $missing = max(0, $target - ProductQuestion::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                $product = $products->random();
                ProductQuestion::factory()->create([
                    'product_id' => $product->id,
                    'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                    'is_public' => true,
                ]);
            }
        }

        if (Schema::hasTable((new ProductAnswer())->getTable()) && ProductQuestion::query()->exists()) {
            $target = 30;
            $missing = max(0, $target - ProductAnswer::query()->count());
            $questions = ProductQuestion::query()->inRandomOrder()->limit(80)->get();
            for ($i = 0; $i < $missing; $i++) {
                $question = $questions->isNotEmpty() ? $questions->random() : null;
                if (!$question) {
                    break;
                }
                ProductAnswer::factory()->create([
                    'question_id' => $question->id,
                ]);
                // Mark answered (best-effort; safe if column exists).
                if (Schema::hasColumn((new ProductQuestion())->getTable(), 'is_answered')) {
                    $question->update(['is_answered' => true]);
                }
            }
        }

        // Product imports (imports + rows + rollbacks)
        if (Schema::hasTable((new ProductImport())->getTable())) {
            $target = 4;
            $missing = max(0, $target - ProductImport::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                /** @var ProductImport $import */
                $import = ProductImport::factory()->completed()->create([
                    'user_id' => User::query()->inRandomOrder()->value('id'),
                ]);

                if (Schema::hasTable((new ProductImportRow())->getTable()) && $products->isNotEmpty()) {
                    $rows = ProductImportRow::factory()
                        ->count(fake()->numberBetween(5, 15))
                        ->create([
                            'product_import_id' => $import->id,
                        ]);

                    // Mark some as success rows referencing real products.
                    foreach ($rows->take(fake()->numberBetween(2, min(6, $rows->count()))) as $row) {
                        $p = $products->random();
                        $row->update([
                            'status' => 'success',
                            'product_id' => $p->id,
                            'mapped_data' => ['action' => 'updated', 'seeded' => true],
                        ]);
                    }
                }

                if (Schema::hasTable((new ProductImportRollback())->getTable())) {
                    ProductImportRollback::factory()->create([
                        'product_import_id' => $import->id,
                    ]);
                }
            }
        }

        // Availability rules + bookings
        if (Schema::hasTable((new AvailabilityRule())->getTable()) && $products->isNotEmpty()) {
            $target = 20;
            $missing = max(0, $target - AvailabilityRule::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                AvailabilityRule::factory()->create([
                    'product_id' => $products->random()->id,
                    'is_active' => true,
                ]);
            }
        }

        if (Schema::hasTable((new AvailabilityBooking())->getTable()) && $products->isNotEmpty()) {
            $target = 25;
            $missing = max(0, $target - AvailabilityBooking::query()->count());
            $orderIds = Order::query()->inRandomOrder()->limit(20)->pluck('id')->all();
            $orderLineIds = OrderLine::query()->inRandomOrder()->limit(50)->pluck('id')->all();
            $customerIds = Customer::query()->inRandomOrder()->limit(30)->pluck('id')->all();

            for ($i = 0; $i < $missing; $i++) {
                $product = $products->random();
                $variantId = null;
                if ($variants->isNotEmpty() && fake()->boolean(50)) {
                    $matching = $variants->where('product_id', $product->id)->values();
                    $variantId = $matching->isNotEmpty()
                        ? $matching->random()->id
                        : $variants->random()->id;
                }

                AvailabilityBooking::factory()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'order_id' => !empty($orderIds) && fake()->boolean(40) ? $orderIds[array_rand($orderIds)] : null,
                    'order_line_id' => !empty($orderLineIds) && fake()->boolean(30) ? $orderLineIds[array_rand($orderLineIds)] : null,
                    'customer_id' => !empty($customerIds) && fake()->boolean(50) ? $customerIds[array_rand($customerIds)] : null,
                ]);
            }
        }

        // Fit finder (quiz/question/answer) + feedback
        if (Schema::hasTable((new FitFinderQuiz())->getTable())) {
            $target = 5;
            $missing = max(0, $target - FitFinderQuiz::query()->count());
            FitFinderQuiz::factory()->count($missing)->create();
        }

        if (Schema::hasTable((new FitFinderQuestion())->getTable()) && FitFinderQuiz::query()->exists()) {
            $target = 15;
            $missing = max(0, $target - FitFinderQuestion::query()->count());
            $quizIds = FitFinderQuiz::query()->pluck('id')->all();
            for ($i = 0; $i < $missing; $i++) {
                FitFinderQuestion::factory()->create([
                    'fit_finder_quiz_id' => $quizIds[array_rand($quizIds)],
                ]);
            }
        }

        if (Schema::hasTable((new FitFinderAnswer())->getTable()) && FitFinderQuestion::query()->exists()) {
            $target = 45;
            $missing = max(0, $target - FitFinderAnswer::query()->count());
            $questionIds = FitFinderQuestion::query()->pluck('id')->all();
            for ($i = 0; $i < $missing; $i++) {
                FitFinderAnswer::factory()->create([
                    'fit_finder_question_id' => $questionIds[array_rand($questionIds)],
                ]);
            }
        }

        if (Schema::hasTable((new FitFeedback())->getTable()) && $products->isNotEmpty()) {
            $target = 30;
            $missing = max(0, $target - FitFeedback::query()->count());
            for ($i = 0; $i < $missing; $i++) {
                FitFeedback::factory()->create([
                    'product_id' => $products->random()->id,
                    'customer_id' => Customer::query()->inRandomOrder()->value('id'),
                    'order_id' => Order::query()->inRandomOrder()->value('id'),
                ]);
            }
        }
    }

    protected function seedUserGroups(): void
    {
        $table = (new UserGroup())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        $groups = [
            ['name' => 'Default', 'type' => UserGroup::TYPE_B2C, 'default_discount_stack_policy' => 'exclusive'],
            ['name' => 'B2B', 'type' => UserGroup::TYPE_B2B, 'default_discount_stack_policy' => 'best_of'],
            ['name' => 'VIP', 'type' => UserGroup::TYPE_VIP, 'default_discount_stack_policy' => 'stackable'],
            ['name' => 'Staff', 'type' => UserGroup::TYPE_STAFF, 'default_discount_stack_policy' => 'exclusive'],
        ];

        $created = 0;
        foreach ($groups as $data) {
            $attrs = $this->filterToTableColumns($table, array_merge($data, ['meta' => ['seeded' => true]]));
            $group = UserGroup::query()->firstOrCreate(['name' => $data['name']], $attrs);
            if ($group->wasRecentlyCreated) {
                $created++;
            }
        }

        // Assign some users to groups so overrides can target groups.
        $userCount = User::query()->count();
        if ($userCount > 0 && Schema::hasColumn((new User())->getTable(), 'group_id')) {
            $allGroups = UserGroup::query()->pluck('id')->all();
            if (!empty($allGroups)) {
                User::query()
                    ->whereNull('group_id')
                    ->inRandomOrder()
                    ->limit(10)
                    ->get()
                    ->each(function (User $user) use ($allGroups) {
                        $user->update(['group_id' => $allGroups[array_rand($allGroups)]]);
                    });
            }
        }

        if ($created > 0) {
            $this->command?->info("  ✓ User groups created: {$created}");
        }
    }

    protected function seedB2BContracts(): void
    {
        $table = (new B2BContract())->getTable();
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Customer::query()->exists()) {
            $this->call(CustomerSeeder::class);
        }
        if (!User::query()->exists()) {
            User::factory()->count(10)->create();
        }

        $customers = Customer::query()->inRandomOrder()->limit(10)->get();
        $users = User::query()->inRandomOrder()->limit(10)->get();
        $currency = Currency::query()->where('default', true)->first() ?? Currency::query()->first();

        if ($customers->isEmpty() || $users->isEmpty() || !$currency) {
            return;
        }

        $variants = ProductVariant::query()->inRandomOrder()->limit(200)->get();
        $collections = \App\Models\Collection::query()->inRandomOrder()->limit(50)->get();
        $orders = Order::query()->inRandomOrder()->limit(50)->get();

        $targetContracts = 6;
        $existing = B2BContract::query()->count();
        $toCreate = max(0, $targetContracts - $existing);

        $created = 0;
        for ($i = 0; $i < $toCreate; $i++) {
            $customer = $customers->random();
            $approver = $users->random();

            $contract = B2BContract::query()->create($this->filterToTableColumns($table, [
                'contract_id' => 'CON-' . str_pad((string) (B2BContract::query()->max('id') + 1), 5, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'name' => "B2B Contract {$customer->id} #" . ($i + 1),
                'description' => 'Seeded B2B contract for admin testing.',
                'valid_from' => now()->subDays(fake()->numberBetween(30, 180))->toDateString(),
                'valid_to' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
                'currency_id' => $currency->id,
                'priority' => fake()->numberBetween(1, 100),
                'status' => B2BContract::STATUS_ACTIVE,
                'approval_state' => B2BContract::APPROVAL_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => 'Seeded approval',
                'terms_reference' => 'Seed terms v1',
                'meta' => ['seeded' => true],
            ]));

            // Sales reps pivot
            $rep = $users->random();
            try {
                $contract->salesReps()->syncWithoutDetaching([
                    $rep->id => [
                        'is_primary' => true,
                        'commission_rate' => 0.05,
                        'meta' => ['seeded' => true],
                    ],
                ]);
            } catch (\Throwable) {
                // Ignore if pivot schema differs.
            }

            // Credit limit
            if (Schema::hasTable((new ContractCreditLimit())->getTable())) {
                ContractCreditLimit::query()->firstOrCreate(
                    ['contract_id' => $contract->id],
                    $this->filterToTableColumns((new ContractCreditLimit())->getTable(), [
                        'contract_id' => $contract->id,
                        'credit_limit' => fake()->numberBetween(50_000, 500_000),
                        'current_balance' => fake()->numberBetween(0, 50_000),
                        'payment_terms' => ContractCreditLimit::TERMS_NET_30,
                        'payment_terms_days' => 30,
                        'last_payment_date' => now()->subDays(fake()->numberBetween(1, 90))->toDateString(),
                        'meta' => ['seeded' => true],
                    ])
                );
            }

            // Price lists + contract prices
            if (Schema::hasTable((new PriceList())->getTable())) {
                $baseList = PriceList::query()->create($this->filterToTableColumns((new PriceList())->getTable(), [
                    'contract_id' => $contract->id,
                    'name' => 'Base Price List',
                    'description' => 'Seeded base price list.',
                    'parent_id' => null,
                    'version' => 1,
                    'is_active' => true,
                    'valid_from' => now()->subDays(60)->toDateString(),
                    'valid_to' => null,
                    'priority' => 10,
                    'meta' => ['seeded' => true],
                ]));

                $promoList = PriceList::query()->create($this->filterToTableColumns((new PriceList())->getTable(), [
                    'contract_id' => $contract->id,
                    'name' => 'Promo Price List',
                    'description' => 'Seeded promo price list inheriting base.',
                    'parent_id' => $baseList->id,
                    'version' => 1,
                    'is_active' => true,
                    'valid_from' => now()->subDays(14)->toDateString(),
                    'valid_to' => now()->addDays(30)->toDateString(),
                    'priority' => 20,
                    'meta' => ['seeded' => true],
                ]));

                if (Schema::hasTable((new ContractPrice())->getTable()) && $variants->isNotEmpty()) {
                    $priceCount = fake()->numberBetween(15, 40);
                    $selected = $variants->random(min($priceCount, $variants->count()));

                    foreach ($selected as $idx => $variant) {
                        $list = $idx % 3 === 0 ? $promoList : $baseList;
                        ContractPrice::query()->create($this->filterToTableColumns((new ContractPrice())->getTable(), [
                            'price_list_id' => $list->id,
                            'pricing_type' => ContractPrice::TYPE_VARIANT_FIXED,
                            'product_variant_id' => $variant->id,
                            'category_id' => $collections->isNotEmpty() && fake()->boolean(15) ? $collections->random()->id : null,
                            'fixed_price' => fake()->numberBetween(800, 150_000),
                            'min_quantity' => fake()->boolean(20) ? fake()->numberBetween(5, 25) : null,
                            'quantity_break' => null,
                            'currency_id' => $currency->id,
                            'meta' => ['seeded' => true],
                        ]));
                    }
                }

                // Audits
                if (Schema::hasTable((new ContractAudit())->getTable())) {
                    ContractAudit::query()->create($this->filterToTableColumns((new ContractAudit())->getTable(), [
                        'contract_id' => $contract->id,
                        'price_list_id' => $baseList->id,
                        'audit_type' => ContractAudit::TYPE_CONTRACT_CHANGE,
                        'action' => ContractAudit::ACTION_CREATED,
                        'description' => 'Seeded contract created.',
                        'old_values' => [],
                        'new_values' => ['status' => B2BContract::STATUS_ACTIVE],
                        'user_id' => $approver->id,
                        'meta' => ['seeded' => true],
                    ]));

                    if ($orders->isNotEmpty()) {
                        $order = $orders->random();
                        ContractAudit::query()->create($this->filterToTableColumns((new ContractAudit())->getTable(), [
                            'contract_id' => $contract->id,
                            'price_list_id' => $promoList->id,
                            'audit_type' => ContractAudit::TYPE_USAGE,
                            'action' => ContractAudit::ACTION_USED,
                            'description' => 'Seeded contract usage sample.',
                            'order_id' => $order->id,
                            'quantity' => fake()->numberBetween(1, 25),
                            'total_value' => fake()->numberBetween(1_000, 250_000),
                            'meta' => ['seeded' => true],
                        ]));
                    }
                }
            }

            // Contract rules
            if (Schema::hasTable((new ContractRule())->getTable())) {
                $types = [
                    ContractRule::TYPE_DISCOUNT,
                    ContractRule::TYPE_SHIPPING,
                    ContractRule::TYPE_PAYMENT_METHOD,
                ];
                foreach (range(1, 2) as $r) {
                    ContractRule::query()->create($this->filterToTableColumns((new ContractRule())->getTable(), [
                        'contract_id' => $contract->id,
                        'rule_type' => $types[array_rand($types)],
                        'name' => "Rule {$r}",
                        'description' => 'Seeded contract rule.',
                        'is_active' => true,
                        'priority' => 100 - $r,
                        'conditions' => ['cart_total' => ['min' => fake()->numberBetween(0, 10_000)]],
                        'actions' => ['note' => 'Seeded action'],
                        'meta' => ['seeded' => true],
                    ]));
                }
            }

            // Shared carts + purchase orders
            if (Schema::hasTable((new ContractSharedCart())->getTable())) {
                $cart = Cart::factory()->create([
                    'user_id' => $users->random()->id,
                    'customer_id' => $customers->random()->id,
                    'currency_id' => $currency->id,
                    'channel_id' => Channel::query()->where('default', true)->value('id') ?? Channel::query()->value('id'),
                ]);

                ContractSharedCart::query()->create($this->filterToTableColumns((new ContractSharedCart())->getTable(), [
                    'contract_id' => $contract->id,
                    'cart_id' => $cart->id,
                    'name' => 'Team Cart',
                    'description' => 'Seeded shared cart.',
                    'created_by' => $users->random()->id,
                    'shared_with' => $users->take(3)->pluck('id')->values()->all(),
                    'is_active' => true,
                    'meta' => ['seeded' => true],
                ]));
            }

            if (Schema::hasTable((new ContractPurchaseOrder())->getTable()) && $orders->isNotEmpty()) {
                $order = $orders->random();
                $po = ContractPurchaseOrder::query()->create($this->filterToTableColumns((new ContractPurchaseOrder())->getTable(), [
                    'contract_id' => $contract->id,
                    'order_id' => $order->id,
                    'po_number' => 'PO-' . Str::upper(Str::random(8)),
                    'po_date' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
                    'required_date' => now()->addDays(fake()->numberBetween(1, 45))->toDateString(),
                    'notes' => 'Seeded purchase order.',
                    'status' => ContractPurchaseOrder::STATUS_PENDING,
                    'meta' => ['seeded' => true],
                ]));

                if (fake()->boolean(50)) {
                    $po->approve($users->random());
                }
            }

            $created++;
        }

        if ($created > 0) {
            $this->command?->info("  ✓ B2B contracts created: {$created}");
        }
    }

    protected function seedReferralPrograms(): void
    {
        $programTable = (new ReferralProgram())->getTable();
        if (!Schema::hasTable($programTable)) {
            return;
        }

        if (!User::query()->exists()) {
            User::factory()->count(10)->create();
        }
        if (!Customer::query()->exists()) {
            $this->call(CustomerSeeder::class);
        }

        $locales = Language::query()->pluck('code')->values()->all();
        if (empty($locales)) {
            $locales = ['en'];
        }
        $defaultLocale = Language::getDefault()?->code ?? ($locales[0] ?? 'en');

        // Landing template
        $template = null;
        $templateTable = (new ReferralLandingTemplate())->getTable();
        if (Schema::hasTable($templateTable)) {
            $content = [];
            foreach ($locales as $locale) {
                $content[$locale] = [
                    'headline' => $locale === $defaultLocale ? 'Refer a friend' : "Refer a friend ({$locale})",
                    'body' => $locale === $defaultLocale
                        ? 'Share your code and both of you get rewarded.'
                        : "Share your code and both of you get rewarded. ({$locale})",
                ];
            }

            $template = ReferralLandingTemplate::query()->firstOrCreate(
                ['name' => 'Default Referral Landing'],
                $this->filterToTableColumns($templateTable, [
                    'name' => 'Default Referral Landing',
                    'status' => ReferralLandingTemplate::STATUS_ACTIVE,
                    'is_default' => true,
                    'supported_locales' => $locales,
                    'content' => $content,
                    'noindex' => false,
                    'og_image_url' => null,
                    'version' => 1,
                    'meta' => ['seeded' => true],
                ])
            );
        }

        // Program
        $channels = Channel::query()->pluck('id')->all();
        $currencies = Currency::query()->pluck('id')->all();
        $program = ReferralProgram::query()->firstOrCreate(
            ['handle' => 'default'],
            $this->filterToTableColumns($programTable, [
                'name' => 'Default Referral Program',
                'handle' => 'default',
                'description' => 'Seeded referral program for admin testing.',
                // New schema
                'status' => ReferralProgram::STATUS_ACTIVE,
                'start_at' => now()->subDays(90),
                'end_at' => now()->addDays(180),
                'channel_ids' => $channels,
                'currency_scope' => ReferralProgram::CURRENCY_SCOPE_ALL,
                'currency_ids' => $currencies,
                'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
                'audience_user_ids' => null,
                'audience_group_ids' => null,
                'terms_url' => config('app.url') . '/terms',
                'referral_landing_template_id' => $template?->id,
                'last_click_wins' => true,
                'attribution_ttl_days' => 7,
                'referral_code_validity_days' => 365,
                'default_stacking_mode' => 'exclusive',
                'apply_before_tax' => true,
                'shipping_discount_stacks' => false,
                'total_referrals' => 0,
                'total_rewards_issued' => 0,
                'total_reward_value' => 0,
                'meta' => ['seeded' => true],
                // Old schema (if present)
                'is_active' => true,
                'starts_at' => now()->subDays(90),
                'ends_at' => now()->addDays(180),
            ])
        );

        // Rules
        $ruleTable = (new ReferralRule())->getTable();
        if (Schema::hasTable($ruleTable) && ReferralRule::query()->where('referral_program_id', $program->id)->count() < 2) {
            $rules = [
                [
                    'trigger_event' => ReferralRule::TRIGGER_SIGNUP,
                    'referee_reward_type' => ReferralRule::REWARD_PERCENTAGE_DISCOUNT,
                    'referee_reward_value' => 10,
                    'referrer_reward_type' => ReferralRule::REWARD_FIXED_AMOUNT,
                    'referrer_reward_value' => 5,
                ],
                [
                    'trigger_event' => ReferralRule::TRIGGER_FIRST_ORDER_PAID,
                    'referee_reward_type' => ReferralRule::REWARD_FIXED_DISCOUNT,
                    'referee_reward_value' => 15,
                    'referrer_reward_type' => ReferralRule::REWARD_FIXED_AMOUNT,
                    'referrer_reward_value' => 10,
                ],
            ];

            foreach ($rules as $idx => $data) {
                ReferralRule::query()->create($this->filterToTableColumns($ruleTable, array_merge($data, [
                    'referral_program_id' => $program->id,
                    'nth_order' => $data['trigger_event'] === ReferralRule::TRIGGER_NTH_ORDER_PAID ? 3 : null,
                    'min_order_total' => fake()->boolean(50) ? fake()->randomFloat(2, 20, 200) : null,
                    'eligible_product_ids' => [],
                    'eligible_category_ids' => [],
                    'eligible_collection_ids' => [],
                    'max_redemptions_total' => null,
                    'max_redemptions_per_referrer' => null,
                    'max_redemptions_per_referee' => null,
                    'cooldown_days' => 0,
                    'stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
                    'max_total_discount_percent' => null,
                    'max_total_discount_amount' => null,
                    'apply_before_tax' => true,
                    'shipping_discount_stacks' => false,
                    'priority' => 100 - $idx,
                    'validation_window_days' => 30,
                    'coupon_validity_days' => 30,
                    'is_active' => true,
                ])));
            }
        }

        // Codes + tracking + analytics + attributions + rewards
        $codeTable = (new ReferralCode())->getTable();
        $trackingTable = (new ReferralTracking())->getTable();
        $analyticsTable = (new ReferralAnalytics())->getTable();
        $attrTable = (new ReferralAttribution())->getTable();
        $rewardTable = (new ReferralReward())->getTable();
        $issuanceTable = (new ReferralRewardIssuance())->getTable();

        $users = User::query()->inRandomOrder()->limit(25)->get();
        $customers = Customer::query()->inRandomOrder()->limit(25)->get();
        $orders = Order::query()->inRandomOrder()->limit(25)->get();
        $currency = Currency::query()->where('default', true)->first() ?? Currency::query()->first();

        if ($users->isEmpty() || !$currency) {
            return;
        }

        if (Schema::hasTable($codeTable) && ReferralCode::query()->where('referral_program_id', $program->id)->count() < 10) {
            $toCreate = 10 - ReferralCode::query()->where('referral_program_id', $program->id)->count();
            for ($i = 0; $i < $toCreate; $i++) {
                $referrer = $users->random();
                $refCustomerId = $customers->isNotEmpty() ? $customers->random()->id : null;

                $code = Str::upper(Str::random(8));
                $slug = Str::lower($code);

                $refCode = ReferralCode::query()->create($this->filterToTableColumns($codeTable, [
                    'referral_program_id' => $program->id,
                    'code' => $code,
                    'slug' => $slug,
                    'referrer_id' => $referrer->id,
                    'referrer_customer_id' => $refCustomerId,
                    'is_active' => true,
                    'expires_at' => now()->addDays(365),
                    'max_uses' => null,
                    'current_uses' => 0,
                    'custom_url' => null,
                    'total_clicks' => 0,
                    'total_signups' => 0,
                    'total_purchases' => 0,
                    'total_revenue' => 0,
                    'meta' => ['seeded' => true],
                ]));

                // Tracking events
                if (Schema::hasTable($trackingTable)) {
                    foreach (range(1, fake()->numberBetween(2, 6)) as $t) {
                        ReferralTracking::query()->create($this->filterToTableColumns($trackingTable, [
                            'referral_code_id' => $refCode->id,
                            'session_id' => (string) Str::uuid(),
                            'ip_address' => fake()->ipv4(),
                            'user_agent' => fake()->userAgent(),
                            'referrer_url' => fake()->url(),
                            'landing_page' => fake()->url(),
                            'user_id' => fake()->boolean(50) ? $users->random()->id : null,
                            'customer_id' => fake()->boolean(30) && $customers->isNotEmpty() ? $customers->random()->id : null,
                            'event_type' => ReferralTracking::EVENT_CLICK,
                            'event_data' => ['utm_source' => 'seed'],
                            'converted' => false,
                            'metadata' => ['seeded' => true],
                        ]));
                    }
                }

                // Analytics
                if (Schema::hasTable($analyticsTable)) {
                    $analytics = ReferralAnalytics::query()->create($this->filterToTableColumns($analyticsTable, [
                        'referral_program_id' => $program->id,
                        'referral_code_id' => $refCode->id,
                        'date' => now()->toDateString(),
                        'clicks' => fake()->numberBetween(0, 50),
                        'signups' => fake()->numberBetween(0, 20),
                        'first_purchases' => fake()->numberBetween(0, 10),
                        'repeat_purchases' => fake()->numberBetween(0, 5),
                        'total_orders' => fake()->numberBetween(0, 12),
                        'total_revenue' => fake()->randomFloat(2, 0, 5000),
                        'rewards_issued' => fake()->numberBetween(0, 12),
                        'rewards_value' => fake()->randomFloat(2, 0, 500),
                        'aggregation_level' => ReferralAnalytics::LEVEL_DAILY,
                    ]));
                    $analytics->calculateConversionRates();
                }

                // Attribution + reward sample
                if (Schema::hasTable($attrTable) && $users->count() >= 2) {
                    $eligibleReferees = $users->reject(fn ($u) => (int) $u->id === (int) $referrer->id)->values();
                    if ($eligibleReferees->isEmpty()) {
                        continue;
                    }
                    $referee = $eligibleReferees->random();

                    $attr = ReferralAttribution::query()->create($this->filterToTableColumns($attrTable, [
                        'referee_user_id' => $referee->id,
                        'referrer_user_id' => $referrer->id,
                        'program_id' => $program->id,
                        'code_used' => $refCode->code,
                        'attributed_at' => now()->subMinutes(fake()->numberBetween(1, 10_000)),
                        'attribution_method' => ReferralAttribution::METHOD_CODE,
                        'status' => fake()->boolean(80) ? ReferralAttribution::STATUS_CONFIRMED : ReferralAttribution::STATUS_PENDING,
                        'priority' => fake()->numberBetween(1, 100),
                        'metadata' => ['seeded' => true],
                    ]));

                    if (Schema::hasTable($rewardTable)) {
                        $reward = ReferralReward::query()->create($this->filterToTableColumns($rewardTable, [
                            'referral_program_id' => $program->id,
                            'referral_event_id' => null,
                            'user_id' => $referrer->id,
                            'customer_id' => $refCustomerId,
                            'reward_type' => ReferralReward::TYPE_FIXED_AMOUNT,
                            'reward_value' => fake()->randomFloat(2, 5, 50),
                            'currency_id' => $currency->id,
                            'status' => ReferralReward::STATUS_ISSUED,
                            'delivery_method' => ReferralReward::DELIVERY_AUTOMATIC,
                            'discount_id' => null,
                            'discount_code' => null,
                            'issued_at' => now()->subDays(fake()->numberBetween(0, 30)),
                            'expires_at' => now()->addDays(fake()->numberBetween(10, 90)),
                            'times_used' => 0,
                            'max_uses' => 1,
                            'notes' => 'Seeded reward.',
                            'metadata' => ['seeded' => true, 'referral_attribution_id' => $attr->id],
                        ]));

                        if (Schema::hasTable($issuanceTable)) {
                            $ruleId = ReferralRule::query()->where('referral_program_id', $program->id)->inRandomOrder()->value('id');
                            ReferralRewardIssuance::query()->create($this->filterToTableColumns($issuanceTable, [
                                'referral_rule_id' => $ruleId,
                                'referral_attribution_id' => $attr->id,
                                'referee_user_id' => $referee->id,
                                'referrer_user_id' => $referrer->id,
                                'order_id' => $orders->isNotEmpty() ? $orders->random()->id : null,
                                'referee_reward_type' => ReferralRule::REWARD_PERCENTAGE_DISCOUNT,
                                'referee_reward_value' => fake()->randomFloat(2, 5, 20),
                                'referrer_reward_type' => ReferralRule::REWARD_FIXED_AMOUNT,
                                'referrer_reward_value' => $reward->reward_value,
                                'status' => ReferralRewardIssuance::STATUS_ISSUED,
                                'issued_at' => now()->subDays(fake()->numberBetween(0, 30)),
                                'metadata' => ['seeded' => true],
                            ]));
                        }
                    }
                }
            }
        }

        // Overrides (group/user)
        $groupOverrideTable = (new ReferralGroupOverride())->getTable();
        if (Schema::hasTable($groupOverrideTable) && UserGroup::query()->exists()) {
            $groupId = UserGroup::query()->inRandomOrder()->value('id');
            $ruleId = ReferralRule::query()->where('referral_program_id', $program->id)->inRandomOrder()->value('id');
            ReferralGroupOverride::query()->firstOrCreate(
                [
                    'user_group_id' => $groupId,
                    'referral_program_id' => $program->id,
                    'referral_rule_id' => $ruleId,
                ],
                $this->filterToTableColumns($groupOverrideTable, [
                    'user_group_id' => $groupId,
                    'referral_program_id' => $program->id,
                    'referral_rule_id' => $ruleId,
                    'reward_value_override' => 25,
                    'stacking_mode_override' => ReferralRule::STACKING_STACKABLE,
                    'max_redemptions_override' => 999,
                    'enabled' => true,
                    'auto_vip_tiers' => ['gold' => 50, 'platinum' => 75],
                    'metadata' => ['seeded' => true],
                ])
            );
        }

        $userOverrideTable = (new ReferralUserOverride())->getTable();
        if (Schema::hasTable($userOverrideTable)) {
            $userId = User::query()->inRandomOrder()->value('id');
            $ruleId = ReferralRule::query()->where('referral_program_id', $program->id)->inRandomOrder()->value('id');
            ReferralUserOverride::query()->firstOrCreate(
                [
                    'user_id' => $userId,
                    'referral_program_id' => $program->id,
                    'referral_rule_id' => $ruleId,
                ],
                $this->filterToTableColumns($userOverrideTable, [
                    'user_id' => $userId,
                    'referral_program_id' => $program->id,
                    'referral_rule_id' => $ruleId,
                    'reward_value_override' => 50,
                    'stacking_mode_override' => ReferralRule::STACKING_BEST_OF,
                    'max_redemptions_override' => 50,
                    'block_referrals' => false,
                    'vip_tier' => 'gold',
                    'metadata' => ['seeded' => true],
                ])
            );
        }
    }

    protected function seedWarehousesAndInventory(): void
    {
        $warehouseTable = (new Warehouse())->getTable();
        if (!Schema::hasTable($warehouseTable)) {
            return;
        }

        $channels = Channel::query()->pluck('id')->all();
        $variants = ProductVariant::query()->inRandomOrder()->limit(200)->get();
        $users = User::query()->inRandomOrder()->limit(10)->get();

        // Warehouses
        $warehouseCountTarget = 3;
        if (Warehouse::query()->count() < $warehouseCountTarget) {
            $toCreate = $warehouseCountTarget - Warehouse::query()->count();
            for ($i = 0; $i < $toCreate; $i++) {
                $wh = Warehouse::query()->create($this->filterToTableColumns($warehouseTable, [
                    'name' => "Warehouse " . ($i + 1),
                    'code' => 'WH-' . Str::upper(Str::random(4)),
                    'address' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->stateAbbr(),
                    'postcode' => fake()->postcode(),
                    'country' => fake()->countryCode(),
                    'phone' => fake()->phoneNumber(),
                    'email' => fake()->companyEmail(),
                    'is_active' => true,
                    'priority' => $i + 1,
                    'notes' => 'Seeded warehouse.',
                    'latitude' => fake()->latitude(),
                    'longitude' => fake()->longitude(),
                    'service_areas' => ['countries' => [fake()->countryCode()]],
                    'geo_distance_rules' => [],
                    'max_fulfillment_distance' => 250,
                    'is_dropship' => fake()->boolean(20),
                    'dropship_provider' => fake()->boolean(20) ? fake()->company() : null,
                    'is_virtual' => false,
                    'virtual_config' => [],
                    'fulfillment_rules' => [],
                    'auto_fulfill' => fake()->boolean(50),
                ]));

                // Map to channels
                if (!empty($channels)) {
                    try {
                        $wh->channels()->syncWithoutDetaching([
                            $channels[array_rand($channels)] => [
                                'priority' => 1,
                                'is_default' => true,
                                'is_active' => true,
                                'fulfillment_rules' => [],
                            ],
                        ]);
                    } catch (\Throwable) {
                        // Ignore pivot schema mismatches.
                    }
                }
            }
        }

        $warehouses = Warehouse::query()->inRandomOrder()->limit(5)->get();
        if ($warehouses->isEmpty() || $variants->isEmpty()) {
            return;
        }

        // Inventory levels (top-up)
        $invTable = (new InventoryLevel())->getTable();
        if (!Schema::hasTable($invTable)) {
            return;
        }

        $createdLevels = 0;
        foreach ($variants->take(120) as $variant) {
            $warehouse = $warehouses->random();

            $existing = InventoryLevel::query()
                ->where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();
            if ($existing) {
                continue;
            }

            InventoryLevel::query()->create($this->filterToTableColumns($invTable, [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => fake()->numberBetween(0, 500),
                'reserved_quantity' => fake()->numberBetween(0, 20),
                'incoming_quantity' => fake()->numberBetween(0, 100),
                'damaged_quantity' => fake()->numberBetween(0, 5),
                'preorder_quantity' => fake()->numberBetween(0, 30),
                'backorder_limit' => fake()->numberBetween(0, 50),
                'reorder_point' => fake()->numberBetween(5, 25),
                'safety_stock_level' => fake()->numberBetween(0, 10),
                'reorder_quantity' => fake()->numberBetween(25, 100),
            ]));

            $createdLevels++;
        }

        if ($createdLevels > 0) {
            $this->command?->info("  ✓ Inventory levels created: {$createdLevels}");
        }

        // Stock movements + low stock alerts
        $movementTable = (new StockMovement())->getTable();
        $alertTable = (new LowStockAlert())->getTable();
        $txTable = (new InventoryTransaction())->getTable();

        $levels = InventoryLevel::query()->inRandomOrder()->limit(80)->get();
        foreach ($levels as $level) {
            if (Schema::hasTable($movementTable) && StockMovement::query()->where('inventory_level_id', $level->id)->count() < 2) {
                $before = $level->quantity;
                $delta = fake()->numberBetween(-10, 25);
                $after = max(0, $before + $delta);

                StockMovement::query()->create($this->filterToTableColumns($movementTable, [
                    'product_variant_id' => $level->product_variant_id,
                    'warehouse_id' => $level->warehouse_id,
                    'inventory_level_id' => $level->id,
                    'type' => $delta >= 0 ? 'in' : 'out',
                    'quantity' => $delta,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reserved_quantity_before' => $level->reserved_quantity,
                    'reserved_quantity_after' => $level->reserved_quantity,
                    'available_quantity_before' => max(0, $before - $level->reserved_quantity),
                    'available_quantity_after' => max(0, $after - $level->reserved_quantity),
                    'reference_type' => null,
                    'reference_id' => null,
                    'reference_number' => null,
                    'reason' => 'Seeded movement',
                    'notes' => null,
                    'metadata' => ['seeded' => true],
                    'created_by' => $users->isNotEmpty() ? $users->random()->id : null,
                    'actor_type' => 'system',
                    'actor_identifier' => 'seed',
                    'ip_address' => fake()->ipv4(),
                    'movement_date' => now()->subDays(fake()->numberBetween(0, 60)),
                ]));
            }

            if (Schema::hasTable($alertTable) && $level->isLowStock() && LowStockAlert::query()->where('inventory_level_id', $level->id)->doesntExist()) {
                LowStockAlert::query()->create($this->filterToTableColumns($alertTable, [
                    'inventory_level_id' => $level->id,
                    'product_variant_id' => $level->product_variant_id,
                    'warehouse_id' => $level->warehouse_id,
                    'current_quantity' => $level->available_quantity,
                    'reorder_point' => $level->reorder_point,
                    'is_resolved' => false,
                    'notification_sent' => fake()->boolean(40),
                    'notification_sent_at' => fake()->boolean(40) ? now()->subDays(fake()->numberBetween(0, 10)) : null,
                ]));
            }

            if (Schema::hasTable($txTable) && InventoryTransaction::query()->where('warehouse_id', $level->warehouse_id)->where('product_variant_id', $level->product_variant_id)->doesntExist()) {
                InventoryTransaction::query()->create($this->filterToTableColumns($txTable, [
                    'product_variant_id' => $level->product_variant_id,
                    'warehouse_id' => $level->warehouse_id,
                    'type' => 'adjustment',
                    'quantity' => fake()->numberBetween(-5, 10),
                    'reason' => 'Seed adjustment',
                    'notes' => null,
                    'created_by' => $users->isNotEmpty() ? $users->random()->id : null,
                    'transaction_date' => now()->subDays(fake()->numberBetween(0, 30)),
                    'metadata' => ['seeded' => true],
                ]));
            }
        }

        // Stock notifications (back-in-stock emails)
        $notifTable = (new StockNotification())->getTable();
        if (Schema::hasTable($notifTable) && $variants->isNotEmpty()) {
            $target = 15;
            $existing = StockNotification::query()->count();
            $toCreate = max(0, $target - $existing);

            for ($i = 0; $i < $toCreate; $i++) {
                $variant = $variants->random();
                $productId = $variant->product_id ?? Product::query()->inRandomOrder()->value('id');

                $token = Str::random(32);
                // Keep it deterministic-safe for the unique index.
                while (StockNotification::query()->where('token', $token)->exists()) {
                    $token = Str::random(32);
                }

                StockNotification::query()->create($this->filterToTableColumns($notifTable, [
                    'product_id' => $productId,
                    'product_variant_id' => $variant->id,
                    'customer_id' => Customer::query()->inRandomOrder()->value('id'),
                    'email' => fake()->safeEmail(),
                    'name' => fake()->name(),
                    'phone' => fake()->optional(0.3)->phoneNumber(),
                    'status' => fake()->randomElement(['pending', 'sent', 'cancelled']),
                    'notified_at' => fake()->boolean(30) ? now()->subDays(fake()->numberBetween(0, 30)) : null,
                    'notification_count' => fake()->numberBetween(0, 3),
                    'notify_on_backorder' => fake()->boolean(50),
                    'min_quantity' => fake()->boolean(40) ? fake()->numberBetween(1, 5) : null,
                    'token' => $token,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ]));
            }
        }
    }

    protected function seedCustomizations(): void
    {
        $templateTable = (new CustomizationTemplate())->getTable();
        if (!Schema::hasTable($templateTable)) {
            return;
        }

        // Templates
        if (CustomizationTemplate::query()->count() < 8) {
            $toCreate = 8 - CustomizationTemplate::query()->count();
            $categories = ['Engraving', 'Embroidery', 'Gift Note', 'Print', 'Upload'];

            for ($i = 0; $i < $toCreate; $i++) {
                CustomizationTemplate::query()->create($this->filterToTableColumns($templateTable, [
                    'name' => fake()->words(3, true),
                    'description' => fake()->sentence(),
                    'category' => $categories[array_rand($categories)],
                    'template_data' => [
                        'fields' => [
                            ['type' => 'text', 'label' => 'Line 1', 'max' => 20],
                            ['type' => 'text', 'label' => 'Line 2', 'max' => 20],
                        ],
                    ],
                    'preview_image' => null,
                    'usage_count' => fake()->numberBetween(0, 200),
                    'is_active' => true,
                ]));
            }
        }

        // Product customizations + examples
        $customizationTable = (new ProductCustomization())->getTable();
        $exampleTable = (new CustomizationExample())->getTable();
        if (!Schema::hasTable($customizationTable) || !Schema::hasTable($exampleTable)) {
            return;
        }

        $products = Product::query()->inRandomOrder()->limit(30)->get();
        if ($products->isEmpty()) {
            return;
        }

        foreach ($products->take(15) as $product) {
            if (ProductCustomization::query()->where('product_id', $product->id)->count() >= 2) {
                continue;
            }

            $types = ['text', 'image', 'option'];
            foreach (range(1, 2) as $idx) {
                $type = $types[array_rand($types)];

                $customization = ProductCustomization::query()->create($this->filterToTableColumns($customizationTable, [
                    'product_id' => $product->id,
                    'customization_type' => $type,
                    'field_name' => "custom_{$type}_{$idx}",
                    'field_label' => Str::title(str_replace('_', ' ', "custom {$type} {$idx}")),
                    'description' => fake()->optional(0.7)->sentence(),
                    'placeholder' => $type === 'text' ? fake()->words(3, true) : null,
                    'is_required' => fake()->boolean(40),
                    'min_length' => $type === 'text' ? 0 : null,
                    'max_length' => $type === 'text' ? fake()->numberBetween(10, 40) : null,
                    'pattern' => null,
                    'allowed_values' => $type === 'option' ? ['Red', 'Blue', 'Green'] : null,
                    'allowed_formats' => $type === 'image' ? ['png', 'jpg', 'webp'] : null,
                    'max_file_size_kb' => $type === 'image' ? 2048 : null,
                    'min_width' => $type === 'image' ? 200 : null,
                    'max_width' => $type === 'image' ? 2000 : null,
                    'min_height' => $type === 'image' ? 200 : null,
                    'max_height' => $type === 'image' ? 2000 : null,
                    'aspect_ratio_width' => $type === 'image' ? 1 : null,
                    'aspect_ratio_height' => $type === 'image' ? 1 : null,
                    'price_modifier' => fake()->randomFloat(2, 0, 25),
                    'price_modifier_type' => fake()->randomElement(['fixed', 'per_character', 'per_image']),
                    'display_order' => $idx,
                    'is_active' => true,
                    'show_in_preview' => fake()->boolean(60),
                    'preview_settings' => ['x' => 0.5, 'y' => 0.5],
                    'template_image' => null,
                    'example_values' => $type === 'text' ? ['Hello', 'Lunar'] : null,
                ]));

                foreach (range(1, fake()->numberBetween(1, 3)) as $exIdx) {
                    CustomizationExample::query()->create($this->filterToTableColumns($exampleTable, [
                        'product_id' => $product->id,
                        'customization_id' => $customization->id,
                        'title' => "Example {$exIdx}",
                        'description' => fake()->optional(0.8)->sentence(),
                        'example_image' => fake()->imageUrl(1200, 800),
                        'customization_values' => [
                            $customization->field_name => $type === 'option'
                                ? fake()->randomElement(['Red', 'Blue', 'Green'])
                                : fake()->words(2, true),
                        ],
                        'display_order' => $exIdx,
                        'is_active' => true,
                    ]));
                }
            }
        }

        // Order item customizations (tie to existing order lines)
        $orderItemCustomizationTable = (new OrderItemCustomization())->getTable();
        if (!Schema::hasTable($orderItemCustomizationTable)) {
            return;
        }

        $orderLines = OrderLine::query()->inRandomOrder()->limit(60)->get();
        $customizations = ProductCustomization::query()->inRandomOrder()->limit(60)->get();

        foreach ($orderLines->take(20) as $line) {
            $customization = $customizations->firstWhere('product_id', $line->purchasable?->product_id) ?? ($customizations->isNotEmpty() ? $customizations->random() : null);
            if (!$customization) {
                continue;
            }

            if (OrderItemCustomization::query()->where('order_item_id', $line->id)->where('customization_id', $customization->id)->exists()) {
                continue;
            }

            OrderItemCustomization::query()->create($this->filterToTableColumns($orderItemCustomizationTable, [
                'order_item_id' => $line->id,
                'customization_id' => $customization->id,
                'value' => $customization->customization_type === 'option'
                    ? fake()->randomElement($customization->allowed_values ?? ['Default'])
                    : fake()->words(2, true),
                'value_type' => $customization->customization_type,
                'image_path' => null,
                'image_original_name' => null,
                'image_width' => null,
                'image_height' => null,
                'image_size_kb' => null,
                'additional_cost' => $customization->price_modifier ?? 0,
                'currency_code' => Currency::query()->where('default', true)->value('code') ?? 'USD',
                'production_notes' => fake()->optional(0.3)->sentence(),
                'preview_data' => ['seeded' => true],
            ]));
        }
    }

    protected function seedProductBadges(): void
    {
        $badgeTable = (new ProductBadge())->getTable();
        if (!Schema::hasTable($badgeTable)) {
            return;
        }

        $badges = [
            [
                'name' => 'Sale',
                'handle' => 'sale',
                'type' => 'promotion',
                'label' => 'Sale',
                'color' => '#ffffff',
                'background_color' => '#ef4444',
                'border_color' => null,
                'position' => 'top-left',
                'style' => 'solid',
                'show_icon' => true,
                'icon' => 'heroicon-o-tag',
                'animated' => true,
                'animation_type' => 'pulse',
                'priority' => 100,
                'auto_assign' => true,
                'assignment_rules' => ['on_sale' => true],
                'display_conditions' => ['show_everywhere' => true],
            ],
            [
                'name' => 'New',
                'handle' => 'new',
                'type' => 'status',
                'label' => 'New',
                'color' => '#111827',
                'background_color' => '#fbbf24',
                'border_color' => null,
                'position' => 'top-right',
                'style' => 'solid',
                'show_icon' => false,
                'icon' => null,
                'animated' => false,
                'animation_type' => null,
                'priority' => 90,
                'auto_assign' => true,
                'assignment_rules' => ['recent_days' => 30],
                'display_conditions' => ['show_everywhere' => true],
            ],
            [
                'name' => 'Best Seller',
                'handle' => 'best-seller',
                'type' => 'performance',
                'label' => 'Best Seller',
                'color' => '#ffffff',
                'background_color' => '#10b981',
                'border_color' => null,
                'position' => 'bottom-left',
                'style' => 'solid',
                'show_icon' => false,
                'icon' => null,
                'animated' => false,
                'animation_type' => null,
                'priority' => 80,
                'auto_assign' => false,
                'assignment_rules' => [],
                'display_conditions' => ['show_everywhere' => true],
            ],
        ];

        $created = 0;
        foreach ($badges as $data) {
            $payload = $this->filterToTableColumns($badgeTable, array_merge($data, [
                'description' => 'Seeded badge.',
                'font_size' => 12,
                'padding_x' => 8,
                'padding_y' => 4,
                'border_radius' => 6,
                'is_active' => true,
                'max_display_count' => null,
                'starts_at' => null,
                'ends_at' => null,
            ]));

            $badge = ProductBadge::query()->firstOrCreate(['handle' => $data['handle']], $payload);
            if ($badge->wasRecentlyCreated) {
                $created++;
            }
        }

        if ($created > 0) {
            $this->command?->info("  ✓ Product badges created: {$created}");
        }

        $products = Product::query()->inRandomOrder()->limit(60)->get();
        $allBadges = ProductBadge::query()->get();
        $users = User::query()->inRandomOrder()->limit(10)->get();

        if ($products->isEmpty() || $allBadges->isEmpty()) {
            return;
        }

        $ruleTable = (new ProductBadgeRule())->getTable();
        $assignTable = (new ProductBadgeAssignment())->getTable();
        $perfTable = (new ProductBadgePerformance())->getTable();

        // Rules
        if (Schema::hasTable($ruleTable)) {
            foreach ($allBadges as $badge) {
                if (ProductBadgeRule::query()->where('badge_id', $badge->id)->exists()) {
                    continue;
                }

                ProductBadgeRule::query()->create($this->filterToTableColumns($ruleTable, [
                    'badge_id' => $badge->id,
                    'condition_type' => $badge->auto_assign ? 'automatic' : 'manual',
                    'name' => "{$badge->name} Rule",
                    'description' => 'Seeded badge rule.',
                    'conditions' => $badge->assignment_rules ?? [],
                    'priority' => $badge->priority ?? 0,
                    'is_active' => true,
                    'starts_at' => null,
                    'expires_at' => null,
                ]));
            }
        }

        // Assignments
        if (Schema::hasTable($assignTable)) {
            foreach ($products->take(40) as $product) {
                $badge = $allBadges->random();
                $ruleId = ProductBadgeRule::query()->where('badge_id', $badge->id)->inRandomOrder()->value('id');

                ProductBadgeAssignment::query()->firstOrCreate(
                    [
                        'badge_id' => $badge->id,
                        'product_id' => $product->id,
                        'assignment_type' => $badge->auto_assign ? 'automatic' : 'manual',
                    ],
                    $this->filterToTableColumns($assignTable, [
                        'badge_id' => $badge->id,
                        'product_id' => $product->id,
                        'assignment_type' => $badge->auto_assign ? 'automatic' : 'manual',
                        'rule_id' => $ruleId,
                        'priority' => $badge->priority ?? 0,
                        'display_position' => $badge->position ?? 'top-left',
                        'visibility_rules' => $badge->display_conditions ?? ['show_everywhere' => true],
                        'starts_at' => null,
                        'expires_at' => null,
                        'assigned_at' => now()->subDays(fake()->numberBetween(0, 60)),
                        'assigned_by' => $users->isNotEmpty() ? $users->random()->id : null,
                        'is_active' => true,
                    ])
                );
            }
        }

        // Performance
        if (Schema::hasTable($perfTable)) {
            foreach (ProductBadgeAssignment::query()->inRandomOrder()->limit(30)->get() as $assignment) {
                $existing = ProductBadgePerformance::query()
                    ->where('badge_id', $assignment->badge_id)
                    ->where('product_id', $assignment->product_id)
                    ->exists();
                if ($existing) {
                    continue;
                }

                $views = fake()->numberBetween(0, 5_000);
                $clicks = fake()->numberBetween(0, min($views, 500));
                $addToCart = fake()->numberBetween(0, min($clicks, 200));
                $purchases = fake()->numberBetween(0, min($addToCart, 80));

                $perf = ProductBadgePerformance::query()->create($this->filterToTableColumns($perfTable, [
                    'badge_id' => $assignment->badge_id,
                    'product_id' => $assignment->product_id,
                    'views' => $views,
                    'clicks' => $clicks,
                    'add_to_cart' => $addToCart,
                    'purchases' => $purchases,
                    'revenue' => fake()->randomFloat(2, 0, 50_000),
                    'click_through_rate' => 0,
                    'conversion_rate' => 0,
                    'add_to_cart_rate' => 0,
                    'period_start' => now()->subDays(30)->toDateString(),
                    'period_end' => now()->toDateString(),
                ]));
                $perf->calculateRates();
            }
        }
    }

    protected function seedCheckoutLocks(): void
    {
        $lockTable = (new CheckoutLock())->getTable();
        if (!Schema::hasTable($lockTable)) {
            return;
        }

        $carts = Cart::query()->inRandomOrder()->limit(25)->get();
        $users = User::query()->inRandomOrder()->limit(10)->get();
        if ($carts->isEmpty()) {
            return;
        }

        $snapshotTable = (new PriceSnapshot())->getTable();
        $reservationTable = (new StockReservation())->getTable();
        $levels = InventoryLevel::query()->inRandomOrder()->limit(80)->get();

        $targetLocks = 12;
        $existingLocks = CheckoutLock::query()->count();
        $toCreate = max(0, $targetLocks - $existingLocks);

        $created = 0;
        for ($i = 0; $i < $toCreate; $i++) {
            $cart = $carts->random();

            $lock = CheckoutLock::query()->create($this->filterToTableColumns($lockTable, [
                'cart_id' => $cart->id,
                'session_id' => (string) Str::uuid(),
                'user_id' => $users->isNotEmpty() ? $users->random()->id : null,
                'state' => fake()->randomElement([
                    CheckoutLock::STATE_PENDING,
                    CheckoutLock::STATE_LOCKING_PRICES,
                    CheckoutLock::STATE_AUTHORIZING,
                    CheckoutLock::STATE_COMPLETED,
                    CheckoutLock::STATE_FAILED,
                ]),
                'phase' => fake()->randomElement(['pricing', 'stock', 'payment', null]),
                'failure_reason' => fake()->boolean(20) ? ['message' => 'Seeded failure'] : null,
                'locked_at' => now()->subMinutes(fake()->numberBetween(0, 240)),
                'expires_at' => now()->addMinutes(fake()->numberBetween(5, 120)),
                'completed_at' => null,
                'failed_at' => null,
                'metadata' => ['seeded' => true],
            ]));

            // Price snapshots
            if (Schema::hasTable($snapshotTable)) {
                $lines = CartLine::query()->where('cart_id', $cart->id)->get();

                $cartSub = 0;
                foreach ($lines as $line) {
                    $unit = fake()->numberBetween(500, 50_000);
                    $sub = $unit * max(1, (int) $line->quantity);
                    $cartSub += $sub;

                    PriceSnapshot::query()->create($this->filterToTableColumns($snapshotTable, [
                        'checkout_lock_id' => $lock->id,
                        'cart_id' => $cart->id,
                        'cart_line_id' => $line->id,
                        'unit_price' => $unit,
                        'sub_total' => $sub,
                        'discount_total' => 0,
                        'tax_total' => 0,
                        'total' => $sub,
                        'discount_breakdown' => [],
                        'applied_discounts' => [],
                        'tax_breakdown' => [],
                        'tax_rate' => 0,
                        'currency_code' => Currency::query()->where('default', true)->value('code') ?? 'USD',
                        'compare_currency_code' => null,
                        'exchange_rate' => 1,
                        'coupon_code' => $cart->coupon_code,
                        'promotion_details' => [],
                        'snapshot_at' => now(),
                    ]));
                }

                PriceSnapshot::query()->create($this->filterToTableColumns($snapshotTable, [
                    'checkout_lock_id' => $lock->id,
                    'cart_id' => $cart->id,
                    'cart_line_id' => null,
                    'unit_price' => 0,
                    'sub_total' => $cartSub,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'total' => $cartSub,
                    'discount_breakdown' => [],
                    'applied_discounts' => [],
                    'tax_breakdown' => [],
                    'tax_rate' => 0,
                    'currency_code' => Currency::query()->where('default', true)->value('code') ?? 'USD',
                    'compare_currency_code' => null,
                    'exchange_rate' => 1,
                    'coupon_code' => $cart->coupon_code,
                    'promotion_details' => [],
                    'snapshot_at' => now(),
                ]));
            }

            // Stock reservations linked to this lock (if inventory exists)
            if (Schema::hasTable($reservationTable) && $levels->isNotEmpty()) {
                foreach ($levels->random(min(3, $levels->count())) as $level) {
                    StockReservation::query()->create($this->filterToTableColumns($reservationTable, [
                        'product_variant_id' => $level->product_variant_id,
                        'warehouse_id' => $level->warehouse_id,
                        'inventory_level_id' => $level->id,
                        'quantity' => fake()->numberBetween(1, 5),
                        'reserved_quantity' => fake()->numberBetween(0, 5),
                        'status' => fake()->randomElement(['cart', 'order_confirmed', 'manual']),
                        'reference_type' => CheckoutLock::class,
                        'reference_id' => $lock->id,
                        'session_id' => $lock->session_id,
                        'user_id' => $lock->user_id,
                        'lock_token' => Str::random(16),
                        'locked_at' => now(),
                        'lock_expires_at' => now()->addMinutes(15),
                        'expires_at' => now()->addMinutes(30),
                        'is_released' => false,
                        'metadata' => ['seeded' => true],
                    ]));
                }
            }

            $created++;
        }

        if ($created > 0) {
            $this->command?->info("  ✓ Checkout locks created: {$created}");
        }
    }

    /**
     * Filter an attributes array to columns that exist on the given table.
     * This keeps seeding resilient to migrations/schema drift.
     *
     * @param  array<string,mixed>  $attributes
     * @return array<string,mixed>
     */
    protected function filterToTableColumns(string $table, array $attributes): array
    {
        $columns = $this->tableColumns($table);
        return array_intersect_key($attributes, $columns);
    }

    /**
     * @return array<string,true>
     */
    protected function tableColumns(string $table): array
    {
        if (isset($this->tableColumnCache[$table])) {
            return $this->tableColumnCache[$table];
        }

        if (!Schema::hasTable($table)) {
            return $this->tableColumnCache[$table] = [];
        }

        $cols = Schema::getColumnListing($table);
        return $this->tableColumnCache[$table] = array_fill_keys($cols, true);
    }
}

