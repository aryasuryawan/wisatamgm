<?php

namespace Database\Seeders;

use App\Domain\Transaction\Services\TransactionService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\EquipmentUnit;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tulamben = Branch::where('name', 'Tulamben Scuba')->firstOrFail();
        $scubago = Branch::where('name', 'ScubaGo')->firstOrFail();

        $this->seedUsers($tulamben, $scubago);
        $products = $this->seedProducts($tulamben, $scubago);
        $this->seedEquipment($tulamben, $scubago);
        $customers = $this->seedCustomers($tulamben, $scubago);
        $this->seedSchedules($tulamben, $scubago, $products);
        $this->seedDiscounts();
        $this->seedTransactions($tulamben, $products, $customers);
    }

    private function seedUsers(Branch $tulamben, Branch $scubago): void
    {
        $accounts = [
            ['name' => 'Kasir Tulamben', 'email' => 'kasir@tulambenscuba.test', 'role' => 'kasir', 'branches' => [$tulamben->id]],
            ['name' => 'Kasir ScubaGo', 'email' => 'kasir@scubago.test', 'role' => 'kasir', 'branches' => [$scubago->id]],
            ['name' => 'Admin Cabang', 'email' => 'admin@tulambenscuba.test', 'role' => 'admin-cabang', 'branches' => [$tulamben->id]],
            ['name' => 'Guide Wayan', 'email' => 'wayan@tulambenscuba.test', 'role' => 'dive-guide', 'branches' => [$tulamben->id]],
            ['name' => 'Instructor Made', 'email' => 'made@tulambenscuba.test', 'role' => 'dive-guide', 'branches' => [$tulamben->id]],
            ['name' => 'Finance', 'email' => 'finance@tulambenscuba.test', 'role' => 'finance', 'branches' => []],
            ['name' => 'Marketing', 'email' => 'marketing@tulambenscuba.test', 'role' => 'marketing', 'branches' => []],
        ];

        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$acc['role']]);
            $user->branches()->sync($acc['branches']);
        }
    }

    private function seedProducts(Branch $tulamben, Branch $scubago): array
    {
        $cat = fn (string $slug) => \App\Models\ProductCategory::where('type_slug', $slug)->firstOrFail();

        $rows = [
            ['branch' => $tulamben, 'category' => 'wisata', 'name' => 'Fun Dive USAT Liberty (2x)', 'price' => 950000, 'unit' => 'pax'],
            ['branch' => $tulamben, 'category' => 'wisata', 'name' => 'Discover Scuba Diving', 'price' => 750000, 'unit' => 'pax'],
            ['branch' => $tulamben, 'category' => 'wisata', 'name' => 'Snorkeling Trip Tulamben', 'price' => 350000, 'unit' => 'pax'],
            ['branch' => $tulamben, 'category' => 'jasa', 'name' => 'Open Water Course (PADI)', 'price' => 4500000, 'unit' => 'pax'],
            ['branch' => $tulamben, 'category' => 'sewa-alat', 'name' => 'Sewa BCD', 'price' => 100000, 'unit' => 'hari'],
            ['branch' => $tulamben, 'category' => 'sewa-alat', 'name' => 'Sewa Regulator', 'price' => 100000, 'unit' => 'hari'],
            ['branch' => $tulamben, 'category' => 'sewa-alat', 'name' => 'Sewa Masker + Fin', 'price' => 60000, 'unit' => 'hari'],
            ['branch' => $tulamben, 'category' => 'makanan', 'name' => 'Nasi Campur Bali', 'price' => 35000, 'unit' => 'porsi', 'stock' => 40],
            ['branch' => $tulamben, 'category' => 'makanan', 'name' => 'Air Mineral 600ml', 'price' => 10000, 'unit' => 'btl', 'stock' => 100],
            ['branch' => $tulamben, 'category' => 'merchandise', 'name' => 'Kaos Tulamben Scuba', 'price' => 150000, 'unit' => 'pcs', 'stock' => 25],
            ['branch' => $tulamben, 'category' => 'merchandise', 'name' => 'Buff Dive Logo', 'price' => 90000, 'unit' => 'pcs', 'stock' => 30],
            ['branch' => $tulamben, 'category' => 'transportasi', 'name' => 'Pickup Denpasar-Tulamben', 'price' => 600000, 'unit' => 'trip'],
            ['branch' => $scubago, 'category' => 'wisata', 'name' => 'Island Hopper Day Tour', 'price' => 550000, 'unit' => 'pax'],
            ['branch' => $scubago, 'category' => 'transportasi', 'name' => 'Sewa Motor Harian', 'price' => 90000, 'unit' => 'hari'],
            ['branch' => $scubago, 'category' => 'makanan', 'name' => 'Paket Lunch Box', 'price' => 45000, 'unit' => 'porsi', 'stock' => 35],
        ];

        $products = [];
        foreach ($rows as $row) {
            $products[$row['name']] = Product::updateOrCreate(
                ['name' => $row['name'], 'branch_id' => $row['branch']->id],
                [
                    'category_id' => $cat($row['category'])->id,
                    'base_price' => $row['price'],
                    'unit' => $row['unit'],
                    'stock_quantity' => $row['stock'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        return $products;
    }

    private function seedEquipment(Branch $tulamben, Branch $scubago): void
    {
        $bcd = Product::where('name', 'Sewa BCD')->firstOrFail();
        $reg = Product::where('name', 'Sewa Regulator')->firstOrFail();

        for ($i = 1; $i <= 6; $i++) {
            EquipmentUnit::firstOrCreate(
                ['branch_id' => $tulamben->id, 'code' => "BCD-{$i}"],
                ['product_id' => $bcd->id, 'condition' => 'good', 'status' => 'available']
            );
        }
        for ($i = 1; $i <= 6; $i++) {
            EquipmentUnit::firstOrCreate(
                ['branch_id' => $tulamben->id, 'code' => "REG-{$i}"],
                ['product_id' => $reg->id, 'condition' => 'good', 'status' => 'available']
            );
        }
        for ($i = 1; $i <= 3; $i++) {
            EquipmentUnit::firstOrCreate(
                ['branch_id' => $scubago->id, 'code' => "SG-SNORK-{$i}"],
                ['product_id' => $bcd->id, 'condition' => 'fair', 'status' => 'available']
            );
        }
    }

    private function seedCustomers(Branch $tulamben, Branch $scubago): array
    {
        $rows = [
            ['branch' => $tulamben, 'name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@mail.com', 'nat' => 'indonesia', 'source' => 'organic'],
            ['branch' => $tulamben, 'name' => 'Sari Wulandari', 'phone' => '081298765432', 'email' => 'sari@mail.com', 'nat' => 'indonesia', 'source' => 'referral'],
            ['branch' => $tulamben, 'name' => 'John Miller', 'phone' => '+14155550123', 'email' => 'john@example.com', 'nat' => 'international', 'source' => 'ads'],
            ['branch' => $tulamben, 'name' => 'Emma Schmidt', 'phone' => '+4915112345678', 'email' => 'emma@example.com', 'nat' => 'international', 'source' => 'walk_in'],
            ['branch' => $scubago, 'name' => 'Ketut Ariawan', 'phone' => '0813777888999', 'email' => null, 'nat' => 'indonesia', 'source' => 'walk_in'],
        ];

        $customers = [];
        foreach ($rows as $row) {
            $customers[$row['name']] = Customer::updateOrCreate(
                ['phone' => $row['phone']],
                [
                    'branch_id' => $row['branch']->id,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'nationality_type' => $row['nat'],
                    'source' => $row['source'],
                ]
            );
        }

        return $customers;
    }

    private function seedSchedules(Branch $tulamben, Branch $scubago, array $products): void
    {
        $wayan = User::where('email', 'wayan@tulambenscuba.test')->firstOrFail();
        $made = User::where('email', 'made@tulambenscuba.test')->firstOrFail();

        $funDive = $products['Fun Dive USAT Liberty (2x)'];
        $diveCourse = $products['Open Water Course (PADI)'];
        $snorkel = $products['Snorkeling Trip Tulamben'];

        $schedules = [
            [
                'branch_id' => $tulamben->id,
                'product_id' => $funDive->id,
                'date_start' => now()->addDays(2)->setTime(8, 0),
                'date_end' => now()->addDays(2)->setTime(14, 0),
                'capacity' => 8,
                'status' => 'confirmed',
                'notes' => 'Wreck dive pagi — meet up 07:30 di dive shop.',
            ],
            [
                'branch_id' => $tulamben->id,
                'product_id' => $snorkel->id,
                'date_start' => now()->addDays(2)->setTime(9, 0),
                'date_end' => now()->addDays(2)->setTime(13, 0),
                'capacity' => 10,
                'status' => 'confirmed',
                'notes' => null,
            ],
            [
                'branch_id' => $tulamben->id,
                'product_id' => $diveCourse->id,
                'date_start' => now()->addDays(5)->setTime(9, 0),
                'date_end' => now()->addDays(7)->setTime(16, 0),
                'capacity' => 4,
                'status' => 'draft',
                'notes' => 'Kelas Open Water batch baru.',
            ],
            [
                'branch_id' => $tulamben->id,
                'product_id' => $funDive->id,
                'date_start' => now()->subDays(3)->setTime(8, 0),
                'date_end' => now()->subDays(3)->setTime(13, 0),
                'capacity' => 6,
                'status' => 'completed',
                'notes' => 'Trip yang sudah selesai (contoh laporan).',
            ],
        ];

        foreach ($schedules as $index => $data) {
            $schedule = Schedule::updateOrCreate(
                ['branch_id' => $data['branch_id'], 'product_id' => $data['product_id'], 'date_start' => $data['date_start']],
                collect($data)->except(['branch_id', 'product_id', 'date_start'])->all()
            );

            if ($index === 0) {
                $schedule->staff()->firstOrCreate(
                    ['user_id' => $made->id],
                    ['role_in_trip' => 'instructor']
                );
            }
            if ($index === 2) {
                $schedule->staff()->firstOrCreate(
                    ['user_id' => $made->id],
                    ['role_in_trip' => 'instructor']
                );
                $schedule->staff()->firstOrCreate(
                    ['user_id' => $wayan->id],
                    ['role_in_trip' => 'guide']
                );
            }
        }
    }

    private function seedDiscounts(): void
    {
        $rows = [
            ['code' => 'HEMAT10', 'name' => 'Promo Hemat 10%', 'type' => 'percent', 'value' => 10, 'active' => true],
            ['code' => 'WISATA50K', 'name' => 'Potongan Rp 50.000 paket wisata', 'type' => 'nominal', 'value' => 50000, 'active' => true, 'scope' => ['wisata']],
            ['code' => 'MERCH20', 'name' => 'Diskon merchandise 20%', 'type' => 'percent', 'value' => 20, 'active' => true, 'scope' => ['merchandise', 'makanan'], 'limit' => 50, 'per_customer' => 2],
            ['code' => 'LAMA2025', 'name' => 'Promo tahun lalu (expired)', 'type' => 'percent', 'value' => 25, 'active' => true, 'from' => now()->subMonths(2), 'until' => now()->subMonth()],
            ['code' => 'NONAKTIF', 'name' => 'Kupon nonaktif', 'type' => 'nominal', 'value' => 100000, 'active' => false],
        ];

        foreach ($rows as $row) {
            Discount::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'value' => $row['value'],
                    'valid_from' => ($row['from'] ?? now()->subDay())->toDateString(),
                    'valid_until' => ($row['until'] ?? now()->addMonths(3))->toDateString(),
                    'usage_limit' => $row['limit'] ?? null,
                    'usage_limit_per_customer' => $row['per_customer'] ?? null,
                    'category_scope' => $row['scope'] ?? null,
                    'is_active' => $row['active'],
                ]
            );
        }
    }

    private function seedTransactions(Branch $tulamben, array $products, array $customers): void
    {
        if (\App\Models\Transaction::exists()) {
            return;
        }

        /** @var TransactionService $service */
        $service = app(TransactionService::class);

        $kasir = User::where('email', 'kasir@tulambenscuba.test')->firstOrFail();

        // Transaksi 1 — lunas penuh, paket + merchandise, pakai kode diskon.
        auth()->setUser($kasir);
        $service->create(
            [
                'branch_id' => $tulamben->id,
                'customer_id' => $customers['John Miller']->id,
                'discount_code' => 'HEMAT10',
                'idempotency_key' => 'demo-tx-001',
                'notes' => 'Walk-in, bayar tunai penuh.',
            ],
            [
                ['product_id' => $products['Fun Dive USAT Liberty (2x)']->id, 'qty' => 1],
                ['product_id' => $products['Kaos Tulamben Scuba']->id, 'qty' => 1],
            ],
            [
                ['method' => 'cash', 'amount' => 1098900],
            ],
        );

        // Transaksi 2 — DP (partial), kelas Open Water + sewa alat.
        $tx2 = $service->create(
            [
                'branch_id' => $tulamben->id,
                'customer_id' => $customers['Emma Schmidt']->id,
                'idempotency_key' => 'demo-tx-002',
                'notes' => 'DP kelas Open Water, pelunasan H-1.',
            ],
            [
                ['product_id' => $products['Open Water Course (PADI)']->id, 'qty' => 1],
                ['product_id' => $products['Sewa BCD']->id, 'qty' => 1],
            ],
            [
                ['method' => 'transfer', 'amount' => 2000000, 'reference_no' => 'TRF-8891'],
            ],
        );

        // Transaksi 3 — makanan & merchandise (uji stok keluar), QRIS.
        $service->create(
            [
                'branch_id' => $tulamben->id,
                'customer_id' => $customers['Budi Santoso']->id,
                'idempotency_key' => 'demo-tx-003',
            ],
            [
                ['product_id' => $products['Nasi Campur Bali']->id, 'qty' => 2],
                ['product_id' => $products['Air Mineral 600ml']->id, 'qty' => 3],
                ['product_id' => $products['Buff Dive Logo']->id, 'qty' => 1],
            ],
            [
                ['method' => 'qris', 'amount' => 210900],
            ],
        );

        unset($tx2);
    }
}
