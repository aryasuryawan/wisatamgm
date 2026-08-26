<?php

namespace Database\Seeders;

use App\Domain\Payroll\Services\PayrollService;
use App\Domain\Transaction\Services\TransactionService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EquipmentMaintenanceLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MarketingCampaign;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Schedule;
use App\Models\ScheduleParticipant;
use App\Models\ScheduleStaff;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Simulasi operasional Juni–Juli 2026 untuk provider diving di Bali.
 *
 * Jalankan: php artisan db:seed --class=SimulationSeeder
 * (aman dijalankan berulang; pakai updateOrCreate / firstOrCreate)
 */
class SimulationSeeder extends Seeder
{
    private TransactionService $transactions;

    private PayrollService $payroll;

    private User $owner;

    public function run(): void
    {
        $this->call([
            RolesPermissionSeeder::class,
            BranchSeeder::class,
            ProductCategorySeeder::class,
            ExpenseCategorySeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->transactions = app(TransactionService::class);
        $this->payroll = app(PayrollService::class);
        $this->owner = User::where('email', 'owner@tulambenscuba.test')->firstOrFail();

        $this->resetSimulationData();

        $tulamben = Branch::where('name', 'Tulamben Scuba')->firstOrFail();
        $scubago = Branch::where('name', 'ScubaGo')->firstOrFail();

        $staff = $this->seedStaff($tulamben, $scubago);
        $products = $this->seedProducts($tulamben, $scubago);
        $customers = $this->seedCustomers($tulamben, $scubago);
        $campaigns = $this->seedCampaigns($tulamben, $scubago);

        $schedules = $this->seedSchedules($tulamben, $scubago, $products, $staff, $customers);

        $this->seedTransactions($tulamben, $scubago, $products, $customers, $staff, $schedules);
        $this->seedExpenses($tulamben, $scubago, $campaigns);
        $this->seedEquipmentExtras($tulamben);
        $this->seedPayroll($tulamben, $scubago, $staff['owner']);
    }

    // ------------------------------------------------------------------ reset

    /**
     * Bersihkan data hasil simulasi sebelumnya supaya re-run tidak menduplikasi
     * transaksi/payroll. Data master (user, cabang, produk) tetap dipakai.
     */
    private function resetSimulationData(): void
    {
        DB::table('whatsapp_logs')->delete();
        DB::table('email_logs')->delete();
        DB::table('jobs')->delete(); // antrian WA/email run sebelumnya
        DB::table('payments')->delete();
        DB::table('transaction_items')->delete();
        DB::table('transactions')->delete();
        DB::table('stock_movements')->delete();
        DB::table('schedule_participants')->delete();
        DB::table('schedule_staff')->delete();
        DB::table('schedules')->where('notes', 'Simulasi Juni–Juli 2026')->delete();
        DB::table('payroll_items')->delete();
        DB::table('payroll_periods')->delete();
        DB::table('expenses')->delete();
        DB::table('marketing_campaigns')->delete();

        Customer::query()->update([
            'total_orders' => 0,
            'total_spent' => 0,
        ]);
    }

    // ---------------------------------------------------------------- users

    private function seedStaff(Branch $tulamben, Branch $scubago): array
    {
        $accounts = [
            ['key' => 'owner', 'name' => 'I Gede Owner', 'email' => 'owner@tulambenscuba.test', 'role' => 'owner', 'branches' => [], 'salary' => null, 'commission_type' => 'none', 'commission_rate' => 0],
            ['key' => 'admin_tlb', 'name' => 'Ni Luh Paramitha (Admin Tulamben)', 'email' => 'admin@tulambenscuba.test', 'role' => 'admin-cabang', 'branches' => [$tulamben->id], 'salary' => 4500000, 'commission_type' => 'none', 'commission_rate' => 0],
            ['key' => 'kasir_tlb', 'name' => 'Dewi Anggraeni (Kasir Tulamben)', 'email' => 'kasir@tulambenscuba.test', 'role' => 'kasir', 'branches' => [$tulamben->id], 'salary' => 2800000, 'commission_type' => 'none', 'commission_rate' => 0],
            ['key' => 'guide_wayan', 'name' => 'Wayan Saputra (Divemaster)', 'email' => 'wayan@tulambenscuba.test', 'role' => 'dive-guide', 'branches' => [$tulamben->id], 'salary' => null, 'commission_type' => 'per_pax', 'commission_rate' => 50000],
            ['key' => 'instructor_made', 'name' => 'Made Wirawan (Instructor)', 'email' => 'made@tulambenscuba.test', 'role' => 'dive-guide', 'branches' => [$tulamben->id], 'salary' => null, 'commission_type' => 'per_trip', 'commission_rate' => 200000],
            ['key' => 'boat_jero', 'name' => 'Jero Remaja (Kapten Boat)', 'email' => 'jero@tulambenscuba.test', 'role' => 'dive-guide', 'branches' => [$tulamben->id], 'salary' => 3000000, 'commission_type' => 'none', 'commission_rate' => 0],
            ['key' => 'kasir_sg', 'name' => 'Putu Ayu Lestari (Kasir ScubaGo)', 'email' => 'kasir@scubago.test', 'role' => 'kasir', 'branches' => [$scubago->id], 'salary' => 2600000, 'commission_type' => 'none', 'commission_rate' => 0],
            ['key' => 'guide_putu', 'name' => 'Putu Adnyana (Guide ScubaGo)', 'email' => 'putu@scubago.test', 'role' => 'dive-guide', 'branches' => [$scubago->id], 'salary' => null, 'commission_type' => 'per_pax', 'commission_rate' => 40000],
            ['key' => 'finance', 'name' => 'Ratna Sari (Finance)', 'email' => 'finance@tulambenscuba.test', 'role' => 'finance', 'branches' => [], 'salary' => 4200000, 'commission_type' => 'none', 'commission_rate' => 0],
            ['key' => 'marketing', 'name' => 'Agus Pratama (Marketing)', 'email' => 'marketing@tulambenscuba.test', 'role' => 'marketing', 'branches' => [], 'salary' => 4000000, 'commission_type' => 'none', 'commission_rate' => 0],
        ];

        $staff = [];
        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'base_salary' => $acc['salary'],
                    'commission_type' => $acc['commission_type'],
                    'commission_rate' => $acc['commission_rate'],
                ]
            );
            $user->syncRoles([$acc['role']]);
            $user->branches()->sync($acc['branches']);
            $staff[$acc['key']] = $user;
        }

        return $staff;
    }

    // ------------------------------------------------------------- products

    private function seedProducts(Branch $tulamben, Branch $scubago): array
    {
        $cat = fn (string $slug) => ProductCategory::where('type_slug', $slug)->firstOrFail();

        $rows = [
            [$tulamben, 'wisata', 'Fun Dive USAT Liberty (2x)', 950000, 'pax', 0],
            [$tulamben, 'wisata', 'Night Dive USAT Liberty', 1100000, 'pax', 0],
            [$tulamben, 'wisata', 'Discover Scuba Diving', 750000, 'pax', 0],
            [$tulamben, 'wisata', 'Snorkeling Trip Tulamben', 350000, 'pax', 0],
            [$tulamben, 'jasa', 'Open Water Course (PADI)', 4500000, 'pax', 0],
            [$tulamben, 'jasa', 'Advanced Open Water Course', 3800000, 'pax', 0],
            [$tulamben, 'sewa-alat', 'Sewa BCD', 100000, 'hari', 0],
            [$tulamben, 'sewa-alat', 'Sewa Regulator', 100000, 'hari', 0],
            [$tulamben, 'sewa-alat', 'Sewa Masker + Fin', 60000, 'hari', 0],
            [$tulamben, 'sewa-alat', 'Sewa Wetsuit', 80000, 'hari', 0],
            [$tulamben, 'makanan', 'Nasi Campur Bali', 35000, 'porsi', 500],
            [$tulamben, 'makanan', 'Air Mineral 600ml', 10000, 'btl', 900],
            [$tulamben, 'makanan', 'Kopi Bali Dingin', 25000, 'cup', 350],
            [$tulamben, 'merchandise', 'Kaos Tulamben Scuba', 150000, 'pcs', 260],
            [$tulamben, 'merchandise', 'Buff Dive Logo', 90000, 'pcs', 280],
            [$tulamben, 'transportasi', 'Pickup Denpasar-Tulamben', 600000, 'trip', 0],
            [$scubago, 'wisata', 'Island Hopper Day Tour', 550000, 'pax', 0],
            [$scubago, 'wisata', 'Blue Lagoon Snorkeling', 400000, 'pax', 0],
            [$scubago, 'transportasi', 'Sewa Motor Harian', 90000, 'hari', 0],
            [$scubago, 'transportasi', 'Car Charter 8 Jam + Driver', 700000, 'trip', 0],
            [$scubago, 'makanan', 'Paket Lunch Box', 45000, 'porsi', 420],
        ];

        foreach ($rows as [$branch, $catSlug, $name, $price, $unit, $stock]) {
            $product = Product::updateOrCreate(
                ['name' => $name, 'branch_id' => $branch->id],
                [
                    'category_id' => $cat($catSlug)->id,
                    'base_price' => $price,
                    'unit' => $unit,
                    'is_active' => true,
                ]
            );
            // Reset stok tiap run supaya simulasi selalu konsisten.
            if ($product->stock_quantity !== $stock) {
                $product->forceFill(['stock_quantity' => $stock])->save();
            }
        }

        // Stok masuk awal Juni untuk barang berstok (kartu stok realistis).
        $jun1 = Carbon::parse('2026-06-01 09:00');
        Product::whereIn('branch_id', [$tulamben->id, $scubago->id])
            ->where('stock_quantity', '>', 0)
            ->get()
            ->each(function (Product $product) use ($jun1) {
                $exists = DB::table('stock_movements')
                    ->where('product_id', $product->id)
                    ->where('type', 'in')
                    ->exists();
                if (! $exists) {
                    DB::table('stock_movements')->insert([
                        'branch_id' => $product->branch_id,
                        'product_id' => $product->id,
                        'type' => 'in',
                        'qty' => $product->stock_quantity,
                        'qty_after' => $product->stock_quantity,
                        'ref_type' => 'purchase',
                        'ref_id' => null,
                        'unit_cost' => null,
                        'notes' => 'Stok awal Juni 2026',
                        'user_id' => $this->owner->id,
                        'created_at' => $jun1,
                        'updated_at' => $jun1,
                    ]);
                }
            });

        $all = Product::whereIn('branch_id', [$tulamben->id, $scubago->id])->get()->keyBy('name');

        return $all->all();
    }

    // ------------------------------------------------------------ customers

    private function seedCustomers(Branch $tulamben, Branch $scubago): array
    {
        $rows = [
            [$tulamben, 'Budi Santoso', '081234567890', 'budi@mail.com', 'indonesia', 'organic'],
            [$tulamben, 'Sari Wulandari', '081298765432', 'sari@mail.com', 'indonesia', 'referral'],
            [$tulamben, 'John Miller', '+14155550123', 'john.miller@example.com', 'international', 'ads'],
            [$tulamben, 'Emma Schmidt', '+4915112345678', 'emma.schmidt@example.com', 'international', 'ads'],
            [$tulamben, 'Hiroshi Tanaka', '+819012345678', 'tanaka@example.jp', 'international', 'ads'],
            [$tulamben, 'Claire Dubois', '+33712345678', 'claire@example.fr', 'international', 'referral'],
            [$tulamben, 'Agus Setiawan', '081322334455', null, 'indonesia', 'walk_in'],
            [$tulamben, 'Mia Johansson', '+46701234567', 'mia@example.se', 'international', 'organic'],
            [$tulamben, 'Rina Marlina', '081755667788', 'rina@mail.com', 'indonesia', 'ads'],
            [$tulamben, 'Daniel Craig Jr.', '+447700900123', 'daniel@example.co.uk', 'international', 'referral'],
            [$tulamben, 'Yuki Sato', '+818098765432', 'yuki@example.jp', 'international', 'ads'],
            [$scubago, 'Ketut Ariawan', '0813777888999', null, 'indonesia', 'walk_in'],
            [$scubago, 'Lisa Anderson', '+16175550134', 'lisa@example.com', 'international', 'ads'],
            [$scubago, 'Marco Rossi', '+393401234567', 'marco@example.it', 'international', 'ads'],
            [$scubago, 'Putri Ayu', '081988776655', 'putri@mail.com', 'indonesia', 'organic'],
            [$scubago, 'Anna Becker', '+4915198765432', 'anna.becker@example.de', 'international', 'referral'],
            [$scubago, 'Kevin Tanujaya', '081122334455', 'kevin@mail.com', 'indonesia', 'ads'],
            [$scubago, 'Sophie Laurent', '+33798765432', 'sophie@example.fr', 'international', 'organic'],
        ];

        $customers = [];
        foreach ($rows as [$branch, $name, $phone, $email, $nat, $source]) {
            $customers[$name] = Customer::updateOrCreate(
                ['phone' => $phone],
                [
                    'branch_id' => $branch->id,
                    'name' => $name,
                    'email' => $email,
                    'nationality_type' => $nat,
                    'source' => $source,
                ]
            );
        }

        return $customers;
    }

    // ------------------------------------------------------------ campaigns

    private function seedCampaigns(Branch $tulamben, Branch $scubago): array
    {
        $campaigns = [];

        $defs = [
            ['Meta Ads Juni — Fun Dive Promo', $tulamben, 'meta_ads', 3500000, '2026-06-01', '2026-06-30'],
            ['Google Ads Juni–Juli — "diving tulamben"', $tulamben, 'google_ads', 5000000, '2026-06-01', '2026-07-31'],
            ['Instagram Influencer Juli', $tulamben, 'instagram', 2500000, '2026-07-01', '2026-07-31'],
            ['Meta Ads Juli — Island Tour', $scubago, 'meta_ads', 2000000, '2026-07-01', '2026-07-31'],
        ];

        foreach ($defs as [$name, $branch, $channel, $budget, $start, $end]) {
            $campaigns[$name] = MarketingCampaign::updateOrCreate(
                ['name' => $name],
                [
                    'branch_id' => $branch->id,
                    'channel' => $channel,
                    'budget' => $budget,
                    'start_date' => $start,
                    'end_date' => $end,
                ]
            );
        }

        return $campaigns;
    }

    // ------------------------------------------------------------- schedules

    private function seedSchedules(Branch $tulamben, Branch $scubago, array $products, array $staff, array $customers): array
    {
        $created = [];

        // Pola trip Tulamben: fun dive tiap 2 hari, snorkel tiap 4 hari,
        // night dive & kursus sesekali. Juni s/d 25 Juli.
        $funDive = $products['Fun Dive USAT Liberty (2x)'];
        $snorkel = $products['Snorkeling Trip Tulamben'];
        $nightDive = $products['Night Dive USAT Liberty'];
        $owCourse = $products['Open Water Course (PADI)'];

        $tlbGuides = [$staff['guide_wayan'], $staff['instructor_made']];
        $tlbCustomers = array_values(array_filter($customers, fn ($c) => $c->branch_id === $tulamben->id));

        $day = Carbon::parse('2026-06-02');
        $i = 0;
        while ($day->lte(Carbon::parse('2026-07-25'))) {
            $product = match ($i % 5) {
                3 => $snorkel,
                default => $funDive,
            };
            $guide = $tlbGuides[$i % 2];

            $created[] = $this->makeSchedule(
                $tulamben, $product, $day->copy()->setTime(8, 0), $day->copy()->setTime(14, 0),
                8, 'completed', $guide,
                $this->pickCustomers($tlbCustomers, 2 + ($i % 4)),
            );

            if ($i % 5 === 1) {
                $created[] = $this->makeSchedule(
                    $tulamben, $snorkel, $day->copy()->setTime(9, 0), $day->copy()->setTime(13, 0),
                    10, 'completed', $staff['guide_wayan'],
                    $this->pickCustomers($tlbCustomers, 3 + ($i % 3)),
                );
            }

            if ($i === 6 || $i === 16) {
                $created[] = $this->makeSchedule(
                    $tulamben, $nightDive, $day->copy()->setTime(17, 30), $day->copy()->setTime(21, 0),
                    6, 'completed', $staff['instructor_made'],
                    $this->pickCustomers($tlbCustomers, 2 + ($i % 2)),
                );
            }

            $i++;
            $day->addDays(2);
        }

        // Kursus multi-hari (Open Water batch Juni & Juli).
        $created[] = $this->makeSchedule(
            $tulamben, $owCourse,
            Carbon::parse('2026-06-15 09:00'), Carbon::parse('2026-06-17 16:00'),
            4, 'completed', $staff['instructor_made'],
            $this->pickCustomers($tlbCustomers, 2),
        );
        $created[] = $this->makeSchedule(
            $tulamben, $owCourse,
            Carbon::parse('2026-07-13 09:00'), Carbon::parse('2026-07-15 16:00'),
            4, 'completed', $staff['instructor_made'],
            $this->pickCustomers($tlbCustomers, 3),
        );

        // ScubaGo: island tour tiap 3 hari.
        $islandTour = $products['Island Hopper Day Tour'];
        $blueLagoon = $products['Blue Lagoon Snorkeling'];
        $sgCustomers = array_values(array_filter($customers, fn ($c) => $c->branch_id === $scubago->id));

        $day = Carbon::parse('2026-06-03');
        $j = 0;
        while ($day->lte(Carbon::parse('2026-07-24'))) {
            $product = $j % 3 === 2 ? $blueLagoon : $islandTour;

            $created[] = $this->makeSchedule(
                $scubago, $product, $day->copy()->setTime(8, 30), $day->copy()->setTime(16, 0),
                12, 'completed', $staff['guide_putu'],
                $this->pickCustomers($sgCustomers, 3 + ($j % 4)),
            );

            $j++;
            $day->addDays(3);
        }

        // Beberapa jadwal mendatang (confirmed/draft) biar dashboard hidup.
        $created[] = $this->makeSchedule(
            $tulamben, $funDive, Carbon::parse('2026-08-28 08:00'), Carbon::parse('2026-08-28 14:00'),
            8, 'confirmed', null, [],
        );
        $created[] = $this->makeSchedule(
            $tulamben, $snorkel, Carbon::parse('2026-08-29 09:00'), Carbon::parse('2026-08-29 13:00'),
            10, 'draft', null, [],
        );

        return $created;
    }

    private function makeSchedule(
        Branch $branch,
        Product $product,
        Carbon $start,
        Carbon $end,
        int $capacity,
        string $status,
        ?User $guide,
        array $participants,
    ): Schedule {
        $schedule = Schedule::firstOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id, 'date_start' => $start],
            [
                'date_end' => $end,
                'capacity' => $capacity,
                'status' => $status,
                'notes' => 'Simulasi Juni–Juli 2026',
            ]
        );

        if ($guide) {
            ScheduleStaff::firstOrCreate(
                ['schedule_id' => $schedule->id, 'user_id' => $guide->id],
                ['role_in_trip' => $guide->commission_type === 'per_trip' ? 'instructor' : 'guide']
            );
        }

        foreach ($participants as $customer) {
            ScheduleParticipant::firstOrCreate(
                ['schedule_id' => $schedule->id, 'customer_id' => $customer->id]
            );
        }

        return $schedule;
    }

    private function pickCustomers(array $pool, int $count): array
    {
        if ($pool === []) {
            return [];
        }

        $picked = [];
        $offset = rand(0, count($pool) - 1);
        for ($k = 0; $k < min($count, count($pool)); $k++) {
            $picked[] = $pool[($offset + $k * 3) % count($pool)];
        }

        return array_unique($picked, SORT_REGULAR);
    }

    // ----------------------------------------------------------- transactions

    private function seedTransactions(
        Branch $tulamben,
        Branch $scubago,
        array $products,
        array $customers,
        array $staff,
        array $schedules,
    ): void {
        $completedTlb = array_values(array_filter(
            $schedules,
            fn (Schedule $s) => $s->branch_id === $tulamben->id && $s->status === 'completed'
        ));
        $completedSg = array_values(array_filter(
            $schedules,
            fn (Schedule $s) => $s->branch_id === $scubago->id && $s->status === 'completed'
        ));

        // ---- Transaksi per peserta jadwal Tulamben (kasir login sesuai cabang)
        Auth::login($staff['kasir_tlb']);

        foreach ($completedTlb as $index => $schedule) {
            $participants = $schedule->participants()->with('customer')->get();

            foreach ($participants as $pIndex => $participant) {
                $items = [[
                    'product_id' => $schedule->product_id,
                    'qty' => 1,
                    'schedule_id' => $schedule->id,
                ]];

                // Extras realistis: sewa alat & konsumsi.
                if ($pIndex % 2 === 0) {
                    $items[] = ['product_id' => $products['Sewa BCD']->id, 'qty' => 1];
                    $items[] = ['product_id' => $products['Sewa Regulator']->id, 'qty' => 1];
                }
                if ($pIndex % 3 === 0) {
                    $items[] = ['product_id' => $products['Nasi Campur Bali']->id, 'qty' => 1];
                    $items[] = ['product_id' => $products['Air Mineral 600ml']->id, 'qty' => 2];
                }
                if ($pIndex === 1) {
                    $items[] = ['product_id' => $products['Kaos Tulamben Scuba']->id, 'qty' => 1];
                }

                $method = match ($index % 3) {
                    0 => 'cash',
                    1 => 'transfer',
                    default => 'qris',
                };

                $this->createTransaction(
                    branchId: $tulamben->id,
                    customerId: $participant->customer_id,
                    items: $items,
                    payments: [['method' => $method, 'amount' => PHP_FLOAT_MAX]], // full pay
                    date: $schedule->date_start->copy()->setTime(7, 30),
                );
            }
        }

        // ---- DP/partial: beberapa pelanggan bayar separuh dulu
        $dpTargets = array_slice($completedTlb, 0, 3);
        foreach ($dpTargets as $index => $schedule) {
            $customer = $schedule->participants()->first()?->customer;
            if (! $customer) {
                continue;
            }

            $txn = $this->createTransaction(
                branchId: $tulamben->id,
                customerId: $customer->id,
                items: [['product_id' => $schedule->product_id, 'qty' => 1, 'schedule_id' => $schedule->id]],
                payments: [['method' => 'transfer', 'amount' => 500000]],
                date: $schedule->date_start->copy()->subDays(7)->setTime(10, 0),
            );
            unset($txn);
        }

        // ---- Satu void (salah input awal Juni)
        try {
            Auth::logout();
            Auth::login($staff['kasir_tlb']);
            $voidTxn = $this->createTransaction(
                branchId: $tulamben->id,
                customerId: $customers['Agus Setiawan']->id,
                items: [['product_id' => $products['Snorkeling Trip Tulamben']->id, 'qty' => 2]],
                payments: [['method' => 'cash', 'amount' => PHP_FLOAT_MAX]],
                date: Carbon::parse('2026-06-04 11:00'),
            );
            $this->transactions->void($voidTxn);
        } catch (\Throwable) {
            // Void gagal? Lanjut saja — bukan bagian krusial simulasi.
        }

        // ---- Walk-in non-jadwal Tulamben (makan, merch, sewa alat lepas)
        foreach ([[6, '2026-06-20'], [7, '2026-06-27'], [9, '2026-07-11'], [10, '2026-07-18']] as [$ci, $date]) {
            $customer = array_values(array_filter($customers, fn ($c) => $c->branch_id === $tulamben->id))[$ci] ?? null;
            if (! $customer) {
                continue;
            }

            $this->createTransaction(
                branchId: $tulamben->id,
                customerId: $customer->id,
                items: [
                    ['product_id' => $products['Nasi Campur Bali']->id, 'qty' => 2],
                    ['product_id' => $products['Kopi Bali Dingin']->id, 'qty' => 2],
                    ['product_id' => $products['Buff Dive Logo']->id, 'qty' => 1],
                ],
                payments: [['method' => 'cash', 'amount' => PHP_FLOAT_MAX]],
                date: Carbon::parse($date.' 12:30'),
            );
        }

        // ---- ScubaGo
        Auth::logout();
        Auth::login($staff['kasir_sg']);

        foreach ($completedSg as $index => $schedule) {
            foreach ($schedule->participants()->with('customer')->get() as $participant) {
                $extras = $index % 2 === 0
                    ? [['product_id' => $products['Paket Lunch Box']->id, 'qty' => 1]]
                    : [];

                $this->createTransaction(
                    branchId: $scubago->id,
                    customerId: $participant->customer_id,
                    items: array_merge([[
                        'product_id' => $schedule->product_id,
                        'qty' => 1,
                        'schedule_id' => $schedule->id,
                    ]], $extras),
                    payments: [['method' => $index % 2 ? 'card' : 'cash', 'amount' => PHP_FLOAT_MAX]],
                    date: $schedule->date_start->copy()->setTime(8, 0),
                );
            }
        }

        // Sewa motor & car charter lepas di ScubaGo.
        foreach ([['2026-06-10', 2], ['2026-06-24', 1], ['2026-07-08', 3], ['2026-07-22', 2]] as [$date, $days]) {
            $this->createTransaction(
                branchId: $scubago->id,
                customerId: $customers['Ketut Ariawan']->id,
                items: [['product_id' => $products['Sewa Motor Harian']->id, 'qty' => $days]],
                payments: [['method' => 'cash', 'amount' => PHP_FLOAT_MAX]],
                date: Carbon::parse($date.' 09:15'),
            );
        }
        $this->createTransaction(
            branchId: $scubago->id,
            customerId: $customers['Lisa Anderson']->id,
            items: [['product_id' => $products['Car Charter 8 Jam + Driver']->id, 'qty' => 1]],
            payments: [['method' => 'transfer', 'amount' => PHP_FLOAT_MAX]],
            date: Carbon::parse('2026-07-05 08:00'),
        );

        Auth::logout();
    }

    private function createTransaction(
        int $branchId,
        int $customerId,
        array $items,
        array $payments,
        Carbon $date,
    ): Transaction {
        $transaction = $this->transactions->create(
            ['branch_id' => $branchId, 'customer_id' => $customerId],
            $items,
            []
        );

        foreach ($payments as $payment) {
            $remaining = $this->transactions->remaining($transaction);

            // PHP_FLOAT_MAX = bayar penuh sisa tagihan.
            $amount = min((float) $payment['amount'], (float) $remaining);

            $this->transactions->addPayment(
                $transaction,
                $payment['method'],
                $amount,
                $payment['reference_no'] ?? null,
            );
        }

        // Backdate supaya laporan Juni–Juli akurat.
        $stamp = $date->format('Y-m-d H:i:s');
        $transaction->forceFill(['transaction_date' => $stamp])->save();
        $transaction->payments()->update(['paid_at' => $stamp]);
        $transaction->forceFill(['created_at' => $stamp, 'updated_at' => $stamp])->save();
        DB::table('stock_movements')
            ->where('ref_type', 'transaction')
            ->where('ref_id', $transaction->id)
            ->update(['created_at' => $stamp, 'updated_at' => $stamp]);

        return $transaction->refresh();
    }

    // --------------------------------------------------------------- expenses

    private function seedExpenses(Branch $tulamben, Branch $scubago, array $campaigns): void
    {
        $cat = fn (string $slug) => ExpenseCategory::where('slug', $slug)->firstOrFail()->id;

        Auth::login($this->owner);

        // [branch, slug, deskripsi, nominal, tanggal, campaign?]
        $rows = [
            // Bensin boat Tulamben (tiap ±5 hari)
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 400000, '2026-06-03'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 350000, '2026-06-08'],
            [$tulamben, 'operasional', 'Bensin boat + solar generator dive shop', 425000, '2026-06-13'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 380000, '2026-06-18'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty + night dive', 450000, '2026-06-23'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 395000, '2026-06-28'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 410000, '2026-07-03'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 365000, '2026-07-08'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 430000, '2026-07-13'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 388000, '2026-07-18'],
            [$tulamben, 'operasional', 'Bensin boat trip Liberty', 402000, '2026-07-23'],

            // Isi angin tabung
            [$tulamben, 'operasional', 'Isi angin tabung (kompresor)', 220000, '2026-06-10'],
            [$tulamben, 'operasional', 'Isi angin tabung (kompresor)', 240000, '2026-07-01'],
            [$tulamben, 'operasional', 'Isi angin tabung (kompresor)', 230000, '2026-07-20'],

            // Sewa kendaraan
            [$tulamben, 'lainnya', 'Sewa Avanza + driver (Juni), antar-jemput Sanur', 4500000, '2026-06-01'],
            [$tulamben, 'lainnya', 'Sewa Avanza + driver (Juli), antar-jemput Sanur', 4500000, '2026-07-01'],
            [$scubago, 'operasional', 'Bensin motor armada sewa (Juni)', 600000, '2026-06-15'],
            [$scubago, 'operasional', 'Bensin motor armada sewa (Juli)', 650000, '2026-07-15'],
            [$scubago, 'sewa-tempat', 'Parkir & retribusi pantai Sanur (Juni)', 350000, '2026-06-30'],
            [$scubago, 'sewa-tempat', 'Parkir & retribusi pantai Sanur (Juli)', 375000, '2026-07-31'],

            // Operasional rutin
            [$tulamben, 'operasional', 'Galon air + kopi gula untuk guest', 185000, '2026-06-07'],
            [$tulamben, 'operasional', 'Galon air + snack welcome drink', 210000, '2026-06-21'],
            [$tulamben, 'operasional', 'Galon air + kopi gula untuk guest', 195000, '2026-07-05'],
            [$tulamben, 'operasional', 'Galon air + snack welcome drink', 205000, '2026-07-19'],
            [$tulamben, 'operasional', 'Listrik dive shop (Juni)', 850000, '2026-06-25'],
            [$tulamben, 'operasional', 'Listrik dive shop (Juli)', 920000, '2026-07-25'],
            [$tulamben, 'operasional', 'Internet/wifi dive shop (Juni)', 400000, '2026-06-05'],
            [$tulamben, 'operasional', 'Internet/wifi dive shop (Juli)', 400000, '2026-07-05'],
            [$tulamben, 'operasional', 'ATK, sabun & toiletries guest', 165000, '2026-06-14'],
            [$tulamben, 'operasional', 'ATK & cetak brosur', 240000, '2026-07-09'],

            // Maintenance & pajak
            [$tulamben, 'alat', 'Servis regulator + overhaul BCD-3', 750000, '2026-07-11'],
            [$tulamben, 'alat', 'Ganti O-ring & filter kompresor', 320000, '2026-06-26'],
            [$tulamben, 'lainnya', 'Pajak progresif mobil Avanza 4 bulanan', 1250000, '2026-07-16'],
            [$scubago, 'alat', 'Servis 6 set masker-fin (strap baru)', 180000, '2026-07-06'],

            // Iklan / marketing (terhubung kampanye utk ROI)
            [$tulamben, 'marketing', 'Meta Ads week 1 Juni', 850000, '2026-06-07', 'Meta Ads Juni — Fun Dive Promo'],
            [$tulamben, 'marketing', 'Meta Ads week 2 Juni', 850000, '2026-06-14', 'Meta Ads Juni — Fun Dive Promo'],
            [$tulamben, 'marketing', 'Meta Ads week 3 Juni', 900000, '2026-06-21', 'Meta Ads Juni — Fun Dive Promo'],
            [$tulamben, 'marketing', 'Meta Ads week 4 Juni', 900000, '2026-06-28', 'Meta Ads Juni — Fun Dive Promo'],
            [$tulamben, 'marketing', 'Google Ads billing Juni (setengah)', 1250000, '2026-06-30', 'Google Ads Juni–Juli — "diving tulamben"'],
            [$tulamben, 'marketing', 'Google Ads billing Juli (setengah)', 1250000, '2026-07-15', 'Google Ads Juni–Juli — "diving tulamben"'],
            [$tulamben, 'marketing', 'Google Ads top-up budget Juli', 1250000, '2026-07-29', 'Google Ads Juni–Juli — "diving tulamben"'],
            [$tulamben, 'marketing', 'Fee influencer IG @balidivergirl (reels + stories)', 2500000, '2026-07-10', 'Instagram Influencer Juli'],
            [$scubago, 'marketing', 'Meta Ads Island Tour week 1-2 Juli', 1000000, '2026-07-08', 'Meta Ads Juli — Island Tour'],
            [$scubago, 'marketing', 'Meta Ads Island Tour week 3-4 Juli', 1000000, '2026-07-24', 'Meta Ads Juli — Island Tour'],
        ];

        foreach ($rows as $row) {
            [$branch, $slug, $description, $amount, $date] = $row;
            $campaignId = isset($row[5]) ? $campaigns[$row[5]]->id : null;

            Expense::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'description' => $description,
                    'expense_date' => $date,
                ],
                [
                    'expense_category_id' => $cat($slug),
                    'user_id' => $this->owner->id,
                    'marketing_campaign_id' => $campaignId,
                    'amount' => $amount,
                ]
            );
        }

        Auth::logout();
    }

    // ------------------------------------------------------- equipment extras

    private function seedEquipmentExtras(Branch $tulamben): void
    {
        $bcd = Product::where('name', 'Sewa BCD')->where('branch_id', $tulamben->id)->first();
        $reg = Product::where('name', 'Sewa Regulator')->where('branch_id', $tulamben->id)->first();

        if ($reg) {
            EquipmentMaintenanceLog::firstOrCreate(
                ['equipment_unit_id' => $this->unitId($tulamben, 'REG-1'), 'date' => '2026-07-11', 'type' => 'repair'],
                [
                    'description' => 'Overhaul regulator pertama stage + intermediate pressure tune',
                    'cost' => 450000,
                    'performed_by' => $this->owner->id,
                ]
            );
        }
        if ($bcd) {
            EquipmentMaintenanceLog::firstOrCreate(
                ['equipment_unit_id' => $this->unitId($tulamben, 'BCD-3'), 'date' => '2026-07-11', 'type' => 'replacement'],
                [
                    'description' => 'Ganti hose inflator & low pressure valve',
                    'cost' => 300000,
                    'performed_by' => $this->owner->id,
                ]
            );
        }
    }

    private function unitId(Branch $branch, string $code): int
    {
        return (int) DB::table('equipment_units')
            ->where('branch_id', $branch->id)
            ->where('code', $code)
            ->value('id');
    }

    // ---------------------------------------------------------------- payroll

    private function seedPayroll(Branch $tulamben, Branch $scubago, User $actor): void
    {
        Auth::login($actor);

        $periods = [
            [$tulamben, '2026-06-01', '2026-06-30'],
            [$tulamben, '2026-07-01', '2026-07-31'],
            [$scubago, '2026-06-01', '2026-06-30'],
            [$scubago, '2026-07-01', '2026-07-31'],
        ];

        foreach ($periods as [$branch, $start, $end]) {
            $period = PayrollPeriod::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'period_start' => $start,
                    'period_end' => $end,
                ],
                ['status' => 'draft', 'created_by' => $actor->id]
            );

            if ($period->status === 'draft') {
                $this->payroll->generateItems($period, $actor);
                $this->payroll->approve($period, $actor);
                $this->payroll->close($period, $actor);
            }
        }

        Auth::logout();
    }
}
