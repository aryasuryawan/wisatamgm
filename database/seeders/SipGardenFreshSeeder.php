<?php

namespace Database\Seeders;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Payroll\Services\PayrollService;
use App\Domain\Transaction\Services\TransactionService;
use App\Models\BookableUnit;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\EquipmentUnit;
use App\Models\EquipmentMaintenanceLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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
 * Fresh seed untuk SIP Garden — agrowisata edukasi, camping ground,
 * peternakan ayam petelur, villa, dan cafe/resto di Bogor.
 *
 * Jalankan: php artisan db:seed --class=SipGardenFreshSeeder
 * (setelah base seeders: RolesPermissionSeeder, ProductCategorySeeder, dll)
 */
class SipGardenFreshSeeder extends Seeder
{
    private Branch $branch;
    private User $owner;
    private User $kasir;
    private User $barista;
    private User $admin;
    private User $pemandu;
    private User $finance;
    private User $petugasKandang;
    private array $categories = [];
    private array $products = [];
    private array $customers = [];
    private array $units = [];
    private array $schedules = [];
    private array $bookings = [];
    private array $discounts = [];
    private int $txCounter = 0;
    private \Faker\Generator $faker;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('id_ID');

        $this->call([
            RolesPermissionSeeder::class,
            ProductCategorySeeder::class,
            ExpenseCategorySeeder::class,
        ]);

        $this->resetSipGardenData();

        $this->createBranch();
        $this->createCategories();
        $this->createUsers();
        $this->createProducts();
        $this->createEquipmentUnits();
        $this->createBookableUnits();
        $this->createCustomers();
        $this->createSchedules();
        $this->createBookings();
        $this->createDiscounts();
        $this->createStockMovements();
        $this->createExpenses();
        $this->createPayroll();
        $this->createTransactions();
    }

    // ---------------------------------------------------------------- reset

    private function resetSipGardenData(): void
    {
        $sip = Branch::where('name', 'SIP Garden')->first();
        if (! $sip) {
            return;
        }

        $sipId = $sip->id;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Delete in FK-safe order — scope ke branch SIP Garden saja
        $txIds = DB::table('transactions')->where('branch_id', $sipId)->pluck('id');
        DB::table('whatsapp_logs')->whereIn('transaction_id', $txIds)->orWhereIn('schedule_id',
            DB::table('schedules')->where('branch_id', $sipId)->pluck('id')
        )->delete();
        DB::table('email_logs')->whereIn('transaction_id', $txIds)->delete();

        DB::table('payments')->whereIn('transaction_id', $txIds)->delete();
        DB::table('transaction_items')->whereIn('transaction_id', $txIds)->delete();
        DB::table('transactions')->where('branch_id', $sipId)->delete();

        $discountIds = DB::table('discounts')->where('branch_id', $sipId)->pluck('id');
        DB::table('discount_usages')->whereIn('discount_id', $discountIds)->delete();
        DB::table('discounts')->where('branch_id', $sipId)->delete();

        DB::table('stock_movements')->where('branch_id', $sipId)->delete();

        $schedIds = DB::table('schedules')->where('branch_id', $sipId)->pluck('id');
        DB::table('schedule_participants')->whereIn('schedule_id', $schedIds)->delete();
        DB::table('schedule_staff')->whereIn('schedule_id', $schedIds)->delete();
        DB::table('schedules')->where('branch_id', $sipId)->delete();

        DB::table('bookings')->where('branch_id', $sipId)->delete();

        $ppIds = DB::table('payroll_periods')->where('branch_id', $sipId)->pluck('id');
        DB::table('payroll_items')->whereIn('payroll_period_id', $ppIds)->delete();
        DB::table('payroll_periods')->where('branch_id', $sipId)->delete();

        DB::table('expenses')->where('branch_id', $sipId)->delete();

        $euIds = DB::table('equipment_units')->where('branch_id', $sipId)->pluck('id');
        DB::table('equipment_maintenance_logs')->whereIn('equipment_unit_id', $euIds)->delete();
        DB::table('equipment_units')->where('branch_id', $sipId)->delete();

        DB::table('bookable_units')->where('branch_id', $sipId)->delete();
        DB::table('products')->where('branch_id', $sipId)->delete();
        DB::table('audit_logs')
            ->where('model_type', 'App\Models\Branch')
            ->where('model_id', $sipId)
            ->delete();
        DB::table('user_branch')->where('branch_id', $sipId)->delete();
        DB::table('customers')->where('branch_id', $sipId)->delete();

        User::where('email', 'like', '%@sipgarden.id')->each(function (User $u) {
            $role = $u->getRoleNames()->first();
            if ($role) {
                $u->removeRole($role);
            }
        });
        User::where('email', 'like', '%@sipgarden.id')->delete();

        $sip->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // --------------------------------------------------------------- branch

    private function createBranch(): void
    {
        $this->branch = Branch::updateOrCreate(
            ['name' => 'SIP Garden'],
            [
                'brand' => 'lainnya',
                'domain' => 'sipgarden.id',
                'address' => 'Jl. Katapang Andir, Tegal, Bogor, Jawa Barat',
                'phone' => '081234567890',
                'is_active' => true,
            ]
        );
    }

    // ---------------------------------------------------------- categories

    private function createCategories(): void
    {
        $rows = [
            ['name' => 'Tiket Wisata Edukasi', 'type_slug' => 'wisata', 'sort_order' => 1],
            ['name' => 'Sewa Campsite', 'type_slug' => 'jasa', 'sort_order' => 2],
            ['name' => 'Sewa Alat Camping', 'type_slug' => 'sewa-alat', 'sort_order' => 3],
            ['name' => 'Ayam Petelur', 'type_slug' => 'merchandise', 'sort_order' => 4],
            ['name' => 'Telur', 'type_slug' => 'merchandise', 'sort_order' => 5],
            ['name' => 'Bibit Tanaman', 'type_slug' => 'merchandise', 'sort_order' => 6],
            ['name' => 'Kamar Villa', 'type_slug' => 'jasa', 'sort_order' => 7],
            ['name' => 'Menu Makanan', 'type_slug' => 'makanan', 'sort_order' => 8],
            ['name' => 'Menu Minuman', 'type_slug' => 'makanan', 'sort_order' => 9],
            ['name' => 'Sewa Ruang Gathering', 'type_slug' => 'jasa', 'sort_order' => 10],
            ['name' => 'Alat Berkebun', 'type_slug' => 'sewa-alat', 'sort_order' => 11],
        ];

        foreach ($rows as $row) {
            $cat = ProductCategory::updateOrCreate(
                ['type_slug' => $row['type_slug']],
                ['name' => $row['name'], 'sort_order' => $row['sort_order'], 'is_active' => true]
            );
            $this->categories[$row['type_slug']] = $cat;
        }
    }

    // ----------------------------------------------------------------- users

    private function createUsers(): void
    {
        $accounts = [
            ['key' => 'owner', 'name' => 'H. Surya Adiwinata (Owner)', 'email' => 'owner@sipgarden.id', 'role' => 'owner', 'salary' => null],
            ['key' => 'admin', 'name' => 'Rina Susanti (Admin)', 'email' => 'admin@sipgarden.id', 'role' => 'admin-cabang', 'salary' => 4500000],
            ['key' => 'kasir', 'name' => 'Dewi Lestari (Kasir)', 'email' => 'kasir@sipgarden.id', 'role' => 'kasir', 'salary' => 3000000],
            ['key' => 'barista', 'name' => 'Andi Pratama (Barista/Pelayan Cafe)', 'email' => 'barista@sipgarden.id', 'role' => 'kasir', 'salary' => 2800000],
            ['key' => 'pemandu', 'name' => 'Budi Setiawan (Pemandu Wisata)', 'email' => 'pemandu@sipgarden.id', 'role' => 'dive-guide', 'salary' => 3200000],
            ['key' => 'petugas_kandang', 'name' => 'Joko Widodo (Petugas Kandang)', 'email' => 'kandang@sipgarden.id', 'role' => 'dive-guide', 'salary' => 3000000],
            ['key' => 'finance', 'name' => 'Siti Nurhaliza (Finance)', 'email' => 'finance@sipgarden.id', 'role' => 'finance', 'salary' => 4200000],
            ['key' => 'marketing', 'name' => 'Rudi Hermawan (Marketing)', 'email' => 'marketing@sipgarden.id', 'role' => 'marketing', 'salary' => 3800000],
        ];

        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'base_salary' => $acc['salary'],
                ]
            );
            $user->syncRoles([$acc['role']]);
            $user->branches()->sync([$this->branch->id]);
            $this->{$acc['key']} = $user;
        }
    }

    // --------------------------------------------------------------- products

    private function createProducts(): void
    {
        $cat = fn (string $slug) => ProductCategory::where('type_slug', $slug)->firstOrFail();

        $rows = [
            // === Wisata Edukasi ===
            ['wisata', 'Tiket Wisata Edukasi Anak (TK/SD)', 50000, 'pax', 0, 'Kunjungan edukasi greenhouse + panen sayur + feeding corner'],
            ['wisata', 'Tiket Wisata Edukasi Keluarga', 75000, 'pax', 0, 'Paket keluarga: tour agrowisata + workshop tanam bibit'],
            ['wisata', 'Paket Outbound Sekolah', 85000, 'pax', 0, 'Outbound + team building + edukasi pertanian'],

            // === Camping ===
            ['jasa', 'Paket Camping 1 Malam (Weekday)', 150000, 'pax', 0, 'Sewa campsite + fasilitas toilet + api unggun'],
            ['jasa', 'Paket Camping 1 Malam (Weekend)', 200000, 'pax', 0, 'Weekend premium: campsite + welcome drink'],
            ['jasa', 'Paket Camping 2 Malam', 350000, 'pax', 0, 'Camping 2 malam + sunrise trekking'],

            // === Sewa Alat Camping ===
            ['sewa-alat', 'Sewa Tenda Dome 4 Orang', 75000, 'unit', 0, 'Tenda dome waterproof, kapasitas 4 orang'],
            ['sewa-alat', 'Sewa Sleeping Bag', 30000, 'unit', 0, 'Sleeping bag hangat, ukuran standar'],
            ['sewa-alat', 'Sewa Matras Camping', 20000, 'unit', 0, 'Matras outdoor tipis, nyaman untuk tidur'],
            ['sewa-alat', 'Sewa Kompor Portable', 25000, 'unit', 0, 'Kompor gas portable + gas 1 tabung kecil'],
            ['sewa-alat', 'Sewa Lantern LED', 15000, 'unit', 0, 'Lentera LED rechargeable, terang 8 jam'],

            // === Peternakan ===
            ['merchandise', 'Ayam Petelur Siap Bertelur (18 minggu)', 85000, 'ekor', 0, 'Ayam ras petelur sehat, sudah mulai bertelur'],
            ['merchandise', 'Ayam Petelur Premium (24 minggu)', 95000, 'ekor', 0, 'Ayam petelur produksi tinggi, sehat & aktif'],
            ['merchandise', 'Telur Ayam Kampung per Kg', 35000, 'kg', 0, 'Telur ayam kampung asli, segar dari peternakan'],
            ['merchandise', 'Telur Ayam Kampung per Butir', 3500, 'butir', 0, 'Ecer per butir, segar'],

            // === Bibit Tanaman ===
            ['merchandise', 'Bibit Terong Ungu', 5000, 'pcs', 0, 'Bibit siap tanam, tinggi 15-20cm'],
            ['merchandise', 'Bibit Cabai Merah', 4000, 'pcs', 0, 'Bibit cabai rawit merah, siap tanam'],
            ['merchandise', 'Bibit Tomat Cherry', 4500, 'pcs', 0, 'Bibit tomat cherry, tinggi 10-15cm'],
            ['merchandise', 'Bibit Sawi Hijau', 3000, 'pcs', 0, 'Bibit sawi hijau lokal, cepat panen'],

            // === Villa ===
            ['jasa', 'Villa 2 Kamar (Weekday)', 650000, 'malam', 0, 'Villa 2 bedroom, AC, WiFi, balkon'],
            ['jasa', 'Villa 2 Kamar (Weekend)', 850000, 'malam', 0, 'Weekend rate: villa 2 bedroom lengkap'],
            ['jasa', 'Villa 4 Kamar (Weekday)', 1200000, 'malam', 0, 'Villa besar 4 bedroom, cocok untuk rombongan'],
            ['jasa', 'Villa 4 Kamar (Weekend)', 1500000, 'malam', 0, 'Weekend rate: villa 4 bedroom'],

            // === Cafe Makanan ===
            ['makanan', 'Nasi Goreng Kampung', 25000, 'porsi', 50, 'Nasi goreng dengan lauk kampung'],
            ['makanan', 'Mie Goreng Sapi', 28000, 'porsi', 40, 'Mie goreng daging sapi pilihan'],
            ['makanan', 'Nasi Timbel Komplit', 32000, 'porsi', 35, 'Nasi timbel + ayam goreng + tempe + tahu + sambal'],
            ['makanan', 'Soto Ayam Kampung', 22000, 'porsi', 45, 'Soto ayam bening, hangat & segar'],
            ['makanan', 'Pisang Goreng (Pisgor)', 12000, 'porsi', 60, 'Pisang goreng renyah, 5 pcs'],
            ['makanan', 'Gorengan Campur', 10000, 'porsi', 80, 'Tahu, tempe, bakwan, payok, 1 porsi'],

            // === Cafe Minuman ===
            ['makanan', 'Es Jeruk Segar', 12000, 'gelas', 100, 'Jeruk segar diperas, es batu'],
            ['makanan', 'Es Teh Manis', 8000, 'gelas', 120, 'Teh manis dingin, segar'],
            ['makanan', 'Kopi Susu Gula Aren', 22000, 'cup', 80, 'Kopi arabica lokal + susu + gula aren'],
            ['makanan', 'Kopi Tubruk Biasa', 15000, 'cup', 90, 'Kopi tubruk tradisional, kopi robusta Bogor'],
            ['makanan', 'Air Mineral 600ml', 5000, 'btl', 200, 'Air mineral kemasan'],

            // === Gathering/Workshop ===
            ['jasa', 'Sewa Ruang Gathering (Half Day)', 2500000, 'sesi', 0, 'Ruang gathering kapasitas 30 orang, half day'],
            ['jasa', 'Sewa Ruang Gathering (Full Day)', 4000000, 'sesi', 0, 'Full day + sound system + proyektor'],
            ['jasa', 'Workshop Bertani (per Kelas)', 1500000, 'sesi', 0, 'Workshop bertani untuk 20 siswa + instruktur'],
        ];

        foreach ($rows as [$catSlug, $name, $price, $unit, $stock, $desc]) {
            $product = Product::updateOrCreate(
                ['name' => $name, 'branch_id' => $this->branch->id],
                [
                    'category_id' => $cat($catSlug)->id,
                    'base_price' => $price,
                    'unit' => $unit,
                    'stock_quantity' => $stock,
                    'is_active' => true,
                    'meta' => ['description' => $desc],
                ]
            );
            $this->products[$name] = $product;
        }
    }

    // ---------------------------------------------------------- equipment

    private function createEquipmentUnits(): void
    {
        $equipMap = [
            'Sewa Tenda Dome 4 Orang' => ['TENDA', 4, 'good'],
            'Sewa Sleeping Bag' => ['SLEEPBAG', 4, 'good'],
            'Sewa Matras Camping' => ['MATRAS', 3, 'fair'],
            'Sewa Kompor Portable' => ['KOMPOR', 2, 'good'],
            'Sewa Lantern LED' => ['LANTERN', 2, 'good'],
        ];

        $codeIdx = [];
        foreach ($equipMap as $productName => [$prefix, $count, $condition]) {
            $product = $this->products[$productName] ?? null;
            if (! $product) {
                continue;
            }
            for ($i = 1; $i <= $count; $i++) {
                $code = "{$prefix}-" . str_pad($i, 2, '0', STR_PAD_LEFT);
                $unit = EquipmentUnit::firstOrCreate(
                    ['branch_id' => $this->branch->id, 'code' => $code],
                    [
                        'product_id' => $product->id,
                        'condition' => $condition,
                        'status' => 'available',
                    ]
                );
                $this->units[$code] = $unit;
            }
        }

        // Villa units — tiap kamar sebagai "unit" unik
        $villaProducts = [
            'Villa 2 Kamar (Weekday)' => [['VILLA-2K-01', 2], ['VILLA-2K-02', 2]],
            'Villa 2 Kamar (Weekend)' => [],
            'Villa 4 Kamar (Weekday)' => [['VILLA-4K-01', 4], ['VILLA-4K-02', 4]],
            'Villa 4 Kamar (Weekend)' => [],
        ];

        foreach ($villaProducts as $productName => $roomDefs) {
            $product = $this->products[$productName] ?? null;
            if (! $product) {
                continue;
            }
            foreach ($roomDefs as [$code, $cap]) {
                $unit = EquipmentUnit::firstOrCreate(
                    ['branch_id' => $this->branch->id, 'code' => $code],
                    [
                        'product_id' => $product->id,
                        'condition' => 'good',
                        'status' => 'available',
                    ]
                );
                $this->units[$code] = $unit;
            }
        }

        // Alat berkebun
        $gardenTools = [
            'GARDEN-SABIT-01' => 'Sabit Panen',
            'GARDEN-CANGKUL-01' => 'Cangkul Mini',
            'GARDEN-GAYUNG-01' => 'Gayung Irigasi',
        ];
        foreach ($gardenTools as $code => $desc) {
            $this->units[$code] = EquipmentUnit::firstOrCreate(
                ['branch_id' => $this->branch->id, 'code' => $code],
                [
                    'product_id' => $this->products['Sewa Kompor Portable']->id,
                    'condition' => 'good',
                    'status' => 'available',
                    'notes' => $desc,
                ]
            );
        }
    }

    // ------------------------------------------------------- bookable units

    private function createBookableUnits(): void
    {
        $villaProducts = [
            ['name' => 'Villa 2 Kamar WD-01', 'product' => 'Villa 2 Kamar (Weekday)', 'type' => 'room', 'capacity' => 2, 'price' => 650000],
            ['name' => 'Villa 2 Kamar WD-02', 'product' => 'Villa 2 Kamar (Weekday)', 'type' => 'room', 'capacity' => 2, 'price' => 650000],
            ['name' => 'Villa 2 Kamar WE-01', 'product' => 'Villa 2 Kamar (Weekend)', 'type' => 'room', 'capacity' => 2, 'price' => 850000],
            ['name' => 'Villa 4 Kamar WD-01', 'product' => 'Villa 4 Kamar (Weekday)', 'type' => 'room', 'capacity' => 4, 'price' => 1200000],
            ['name' => 'Villa 4 Kamar WD-02', 'product' => 'Villa 4 Kamar (Weekday)', 'type' => 'room', 'capacity' => 4, 'price' => 1200000],
            ['name' => 'Villa 4 Kamar WE-01', 'product' => 'Villa 4 Kamar (Weekend)', 'type' => 'room', 'capacity' => 4, 'price' => 1500000],
        ];

        foreach ($villaProducts as $def) {
            BookableUnit::updateOrCreate(
                ['branch_id' => $this->branch->id, 'name' => $def['name']],
                [
                    'product_id' => $this->products[$def['product']]->id,
                    'type' => $def['type'],
                    'capacity' => $def['capacity'],
                    'base_price' => $def['price'],
                    'is_active' => true,
                ]
            );
        }

        // Campsite
        BookableUnit::updateOrCreate(
            ['branch_id' => $this->branch->id, 'name' => 'Campsite Zone A'],
            [
                'product_id' => $this->products['Paket Camping 1 Malam (Weekday)']->id,
                'type' => 'camp_site',
                'capacity' => 4,
                'base_price' => 150000,
                'is_active' => true,
            ]
        );
        BookableUnit::updateOrCreate(
            ['branch_id' => $this->branch->id, 'name' => 'Campsite Zone B'],
            [
                'product_id' => $this->products['Paket Camping 1 Malam (Weekend)']->id,
                'type' => 'camp_site',
                'capacity' => 6,
                'base_price' => 200000,
                'is_active' => true,
            ]
        );

        // Gathering room
        BookableUnit::updateOrCreate(
            ['branch_id' => $this->branch->id, 'name' => 'Ruang Gathering Banyan'],
            [
                'product_id' => $this->products['Sewa Ruang Gathering (Full Day)']->id,
                'type' => 'meeting_room',
                'capacity' => 60,
                'base_price' => 4000000,
                'is_active' => true,
            ]
        );
    }

    // ----------------------------------------------------------- customers

    private function createCustomers(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        // Pelanggan grup (sekolah/rombongan)
        $grups = [
            ['name' => 'SDN 01 Bogor (Rombongan)', 'phone' => '081210001001', 'email' => 'sdn01bogor@sdp.test', 'source' => 'organic', 'notes' => 'Rombongan 40 siswa + 5 guru'],
            ['name' => 'SMPN 3 Cibungbulang', 'phone' => '081210001002', 'email' => 'smpn3cb@smp.test', 'source' => 'organic', 'notes' => 'Rombongan 60 siswa'],
            ['name' => 'TK Tunas Harapan', 'phone' => '081210001003', 'email' => null, 'source' => 'referral', 'notes' => 'Rombongan 25 anak + 4 guru'],
            ['name' => 'Karang Taruna Tegal', 'phone' => '081210001004', 'email' => 'katar.tegal@kt.test', 'source' => 'walk_in', 'notes' => 'Komunitas pemuda'],
            ['name' => 'PT Bogor Agrindo (Corporate)', 'phone' => '081210001005', 'email' => 'hr@bogoragrindo.co.id', 'source' => 'organic', 'notes' => 'Corporate gathering, 35 pax'],
            ['name' => 'Keluarga Besar Wijaya', 'phone' => '081210001006', 'email' => 'wijaya.family@family.test', 'source' => 'referral', 'notes' => 'Keluarga besar, 12 orang'],
        ];

        foreach ($grups as $row) {
            $this->customers[$row['name']] = Customer::updateOrCreate(
                ['phone' => $row['phone']],
                [
                    'branch_id' => $this->branch->id,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'nationality_type' => 'indonesia',
                    'source' => $row['source'],
                    'notes' => $row['notes'],
                ]
            );
        }

        // Pelanggan individu/keluarga (30+)
        for ($i = 0; $i < 35; $i++) {
            $name = $faker->unique()->name();
            $phone = '0812' . str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT);
            $source = $faker->randomElement(['organic', 'referral', 'ads', 'walk_in']);
            $email = str_replace(' ', '.', strtolower($name)) . '@email.test';

            $this->customers[$name] = Customer::updateOrCreate(
                ['phone' => $phone],
                [
                    'branch_id' => $this->branch->id,
                    'name' => $name,
                    'email' => $faker->optional(0.7)->safeEmail(),
                    'nationality_type' => 'indonesia',
                    'source' => $source,
                    'notes' => null,
                ]
            );
        }
    }

    // ----------------------------------------------------------- schedules

    private function createSchedules(): void
    {
        // Jadwal wisata edukasi harian (slot pagi & siang)
        $eduProducts = [
            $this->products['Tiket Wisata Edukasi Anak (TK/SD)']->id,
            $this->products['Tiket Wisata Edukasi Keluarga']->id,
            $this->products['Paket Outbound Sekolah']->id,
        ];

        $day = Carbon::parse('2026-03-01');
        $end = Carbon::parse('2026-08-31');
        $i = 0;

        while ($day->lte($end)) {
            // Skip some days for variety
            if ($day->dayOfWeek === Carbon::MONDAY) {
                $day->addDay();
                continue;
            }

            $productId = $eduProducts[$i % count($eduProducts)];
            $cap = ($productId === $eduProducts[2]) ? 30 : 20;
            $status = $day->isPast() ? 'completed' : ($day->diffInDays(now()) <= 7 ? 'confirmed' : 'draft');

            $schedule = Schedule::firstOrCreate(
                ['branch_id' => $this->branch->id, 'product_id' => $productId, 'date_start' => $day->copy()->setTime(8, 0)],
                [
                    'date_end' => $day->copy()->setTime(12, 0),
                    'capacity' => $cap,
                    'status' => $status,
                    'notes' => 'Jadwal wisata edukasi harian',
                ]
            );

            // Assign pemandu
            ScheduleStaff::firstOrCreate(
                ['schedule_id' => $schedule->id, 'user_id' => $this->pemandu->id],
                ['role_in_trip' => 'guide']
            );

            // Add random participants (completed schedules only)
            if ($status === 'completed') {
                $allCustomers = array_values($this->customers);
                $participantCount = rand(3, min(8, $cap));
                for ($p = 0; $p < $participantCount; $p++) {
                    $cust = $allCustomers[array_rand($allCustomers)];
                    ScheduleParticipant::firstOrCreate(
                        ['schedule_id' => $schedule->id, 'customer_id' => $cust->id]
                    );
                }
            }

            $this->schedules[] = $schedule;
            $day->addDays(rand(1, 2));
            $i++;
        }
    }

    // ----------------------------------------------------------- bookings

    private function createBookings(): void
    {
        $bookingService = app(BookingService::class);

        // Bookable units (villa + campsite)
        $villaUnits = BookableUnit::where('branch_id', $this->branch->id)->where('type', 'room')->get();
        $campUnits = BookableUnit::where('branch_id', $this->branch->id)->where('type', 'camp_site')->get();
        $gatherUnits = BookableUnit::where('branch_id', $this->branch->id)->where('type', 'meeting_room')->get();

        $allCustomers = array_values($this->customers);

        // Villa bookings — 20 bookings
        for ($i = 0; $i < 20; $i++) {
            $unit = $villaUnits->random();
            $cust = $allCustomers[array_rand($allCustomers)];
            $nights = rand(1, 4);
            $start = now()->addDays(rand(-90, 30))->startOfDay();
            $end = $start->copy()->addDays($nights);
            $price = (float) $unit->base_price * $nights;
            $status = $start->isPast()
                ? $this->faker->randomElement(['checked_out', 'cancelled'])
                : $this->faker->randomElement(['confirmed']);

            if ($bookingService->isAvailable($unit, $start->toDateString(), $end->toDateString())) {
                $booking = $bookingService->create($this->kasir, [
                    'bookable_unit_id' => $unit->id,
                    'customer_id' => $cust->id,
                    'guest_name' => $cust->name,
                    'guest_phone' => $cust->phone,
                    'guests_count' => $unit->capacity,
                    'date_start' => $start->toDateString(),
                    'date_end' => $end->toDateString(),
                    'amount_total' => $price,
                    'notes' => 'Booking villa',
                ]);

                // Some DP payments
                if ($nights >= 2 && rand(1, 3) === 1) {
                    $bookingService->recordPayment($booking, $this->kasir, 'transfer', $price / 2, 'DP-V-' . ($i + 1));
                }

                $this->bookings[] = $booking;
            }
        }

        // Camping bookings — 15 bookings
        for ($i = 0; $i < 15; $i++) {
            $unit = $campUnits->first();
            if (! $unit) {
                continue;
            }
            $cust = $allCustomers[array_rand($allCustomers)];
            $nights = rand(1, 3);
            $start = now()->addDays(rand(-60, 45))->startOfDay();
            $end = $start->copy()->addDays($nights);
            $price = (float) $unit->base_price * $nights * rand(2, 4);
            $status = $start->isPast()
                ? $this->faker->randomElement(['checked_out', 'cancelled'])
                : 'confirmed';

            if ($bookingService->isAvailable($unit, $start->toDateString(), $end->toDateString())) {
                $booking = $bookingService->create($this->kasir, [
                    'bookable_unit_id' => $unit->id,
                    'customer_id' => $cust->id,
                    'guest_name' => $cust->name,
                    'guest_phone' => $cust->phone,
                    'guests_count' => rand(2, 4),
                    'date_start' => $start->toDateString(),
                    'date_end' => $end->toDateString(),
                    'amount_total' => $price,
                    'notes' => 'Booking camping ground',
                ]);

                $this->bookings[] = $booking;
            }
        }

        // Gathering/workshop bookings — 10 bookings
        for ($i = 0; $i < 10; $i++) {
            $unit = $gatherUnits->first();
            if (! $unit) {
                continue;
            }
            $cust = $allCustomers[array_rand($allCustomers)];
            $start = now()->addDays(rand(-45, 60))->startOfDay();
            $end = $start->copy()->addDay();
            $price = (float) $unit->base_price;
            $status = $start->isPast()
                ? $this->faker->randomElement(['checked_out', 'cancelled'])
                : 'confirmed';

            if ($bookingService->isAvailable($unit, $start->toDateString(), $end->toDateString())) {
                $booking = $bookingService->create($this->kasir, [
                    'bookable_unit_id' => $unit->id,
                    'customer_id' => $cust->id,
                    'guest_name' => $cust->name,
                    'guest_phone' => $cust->phone,
                    'guests_count' => rand(15, 45),
                    'date_start' => $start->toDateString(),
                    'date_end' => $end->toDateString(),
                    'amount_total' => $price,
                    'notes' => 'Booking gathering/workshop',
                ]);

                $this->bookings[] = $booking;
            }
        }
    }

    // ----------------------------------------------------------- discounts

    private function createDiscounts(): void
    {
        $rows = [
            ['code' => 'HEMAT10', 'name' => 'Promo Hemat 10%', 'type' => 'percent', 'value' => 10, 'active' => true, 'scope' => null],
            ['code' => 'WISATA30K', 'name' => 'Diskon Tiket Wisata Rp 30.000', 'type' => 'nominal', 'value' => 30000, 'active' => true, 'scope' => ['wisata']],
            ['code' => 'CAMPING15', 'name' => 'Promo Camping 15%', 'type' => 'percent', 'value' => 15, 'active' => true, 'scope' => ['jasa']],
            ['code' => 'TELUR5K', 'name' => 'Hemat Rp 5.000 Telur per Kg', 'type' => 'nominal', 'value' => 5000, 'active' => true, 'scope' => ['merchandise']],
            ['code' => 'GATHER20', 'name' => 'Diskon Gathering 20%', 'type' => 'percent', 'value' => 20, 'active' => true, 'scope' => ['jasa'], 'limit' => 10],
            ['code' => 'VILLA100K', 'name' => 'Potongan Villa Rp 100.000', 'type' => 'nominal', 'value' => 100000, 'active' => false, 'scope' => ['jasa']],
            ['code' => 'LAMA2025', 'name' => 'Promo Lama 2025 (Expired)', 'type' => 'percent', 'value' => 25, 'active' => true, 'from' => now()->subMonths(3), 'until' => now()->subMonth()],
            ['code' => 'NEWYEAR2027', 'name' => 'Promo Tahun Baru 2027', 'type' => 'percent', 'value' => 30, 'active' => true, 'from' => now()->addMonths(3), 'until' => now()->addMonths(4)],
        ];

        foreach ($rows as $row) {
            $this->discounts[$row['code']] = Discount::updateOrCreate(
                ['code' => $row['code']],
                [
                    'branch_id' => $this->branch->id,
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'value' => $row['value'],
                    'valid_from' => ($row['from'] ?? now()->subMonth())->toDateString(),
                    'valid_until' => ($row['until'] ?? now()->addMonths(3))->toDateString(),
                    'usage_limit' => $row['limit'] ?? null,
                    'category_scope' => $row['scope'] ?? null,
                    'is_active' => $row['active'],
                ]
            );
        }
    }

    // ----------------------------------------------------------- stock

    private function createStockMovements(): void
    {
        $stockProducts = [
            'Nasi Goreng Kampung' => 100,
            'Mie Goreng Sapi' => 80,
            'Nasi Timbel Komplit' => 70,
            'Soto Ayam Kampung' => 90,
            'Pisang Goreng (Pisgor)' => 120,
            'Gorengan Campur' => 160,
            'Es Jeruk Segar' => 200,
            'Es Teh Manis' => 240,
            'Kopi Susu Gula Aren' => 160,
            'Kopi Tubruk Biasa' => 180,
            'Air Mineral 600ml' => 400,
            'Telur Ayam Kampung per Kg' => 50,
            'Telur Ayam Kampung per Butir' => 200,
            'Bibit Terong Ungu' => 100,
            'Bibit Cabai Merah' => 80,
            'Bibit Tomat Cherry' => 60,
            'Bibit Sawi Hijau' => 120,
        ];

        foreach ($stockProducts as $name => $qty) {
            $product = $this->products[$name] ?? null;
            if (! $product) {
                continue;
            }

            $product->forceFill(['stock_quantity' => $qty])->save();

            DB::table('stock_movements')->insert([
                'branch_id' => $this->branch->id,
                'product_id' => $product->id,
                'type' => 'in',
                'qty' => $qty,
                'qty_after' => $qty,
                'ref_type' => 'purchase',
                'ref_id' => null,
                'unit_cost' => null,
                'notes' => 'Stok awal SIP Garden',
                'user_id' => $this->owner->id,
                'created_at' => Carbon::parse('2026-03-01 08:00'),
                'updated_at' => Carbon::parse('2026-03-01 08:00'),
            ]);
        }

        // Low stock alert: Es Teh Manis tinggal 12 gelas (stok kritis)
        $esTeh = $this->products['Es Teh Manis'] ?? null;
        if ($esTeh) {
            $esTeh->forceFill(['stock_quantity' => 12])->save();
        }
    }

    // ----------------------------------------------------------- expenses

    private function createExpenses(): void
    {
        $cat = fn (string $slug) => ExpenseCategory::where('slug', $slug)->firstOrFail()->id;

        $faker = \Faker\Factory::create('id_ID');
        $months = 6;

        for ($m = 0; $m < $months; $m++) {
            $month = now()->subMonths($m);
            $monthStr = $month->format('Y-m');

            // Pakan ayam (bulanan)
            Expense::create([
                'branch_id' => $this->branch->id,
                'expense_category_id' => $cat('operasional'),
                'user_id' => $this->owner->id,
                'description' => "Pakan ayam petelur {$monthStr}",
                'amount' => $faker->numberBetween(2500000, 3500000),
                'expense_date' => $month->copy()->startOfMonth()->addDays(5),
            ]);

            // Listrik (bulanan)
            Expense::create([
                'branch_id' => $this->branch->id,
                'expense_category_id' => $cat('operasional'),
                'user_id' => $this->owner->id,
                'description' => "Listrik SIP Garden {$monthStr}",
                'amount' => $faker->numberBetween(1500000, 2500000),
                'expense_date' => $month->copy()->startOfMonth()->addDays(25),
            ]);

            // Air PDAM
            Expense::create([
                'branch_id' => $this->branch->id,
                'expense_category_id' => $cat('operasional'),
                'user_id' => $this->owner->id,
                'description' => "Air PDAM {$monthStr}",
                'amount' => $faker->numberBetween(400000, 800000),
                'expense_date' => $month->copy()->startOfMonth()->addDays(26),
            ]);

            // Perawatan kebun
            if ($faker->boolean(70)) {
                Expense::create([
                    'branch_id' => $this->branch->id,
                    'expense_category_id' => $cat('alat'),
                    'user_id' => $this->owner->id,
                    'description' => "Pupuk & obat tanaman {$monthStr}",
                    'amount' => $faker->numberBetween(500000, 1500000),
                    'expense_date' => $month->copy()->startOfMonth()->addDays(10),
                ]);
            }

            // Maintenance alat camping
            if ($faker->boolean(40)) {
                Expense::create([
                    'branch_id' => $this->branch->id,
                    'expense_category_id' => $cat('alat'),
                    'user_id' => $this->owner->id,
                    'description' => "Maintenance alat camping {$monthStr}",
                    'amount' => $faker->numberBetween(300000, 800000),
                    'expense_date' => $month->copy()->startOfMonth()->addDays(15),
                ]);
            }

            // Maintenance villa
            if ($faker->boolean(50)) {
                Expense::create([
                    'branch_id' => $this->branch->id,
                    'expense_category_id' => $cat('alat'),
                    'user_id' => $this->owner->id,
                    'description' => "Maintenance villa & AC {$monthStr}",
                    'amount' => $faker->numberBetween(500000, 2000000),
                    'expense_date' => $month->copy()->startOfMonth()->addDays(20),
                ]);
            }

            // Internet/WiFi
            Expense::create([
                'branch_id' => $this->branch->id,
                'expense_category_id' => $cat('operasional'),
                'user_id' => $this->owner->id,
                'description' => "Internet/WiFi {$monthStr}",
                'amount' => 500000,
                'expense_date' => $month->copy()->startOfMonth()->addDays(5),
            ]);

            // ATK & operasional cafe
            if ($faker->boolean(60)) {
                Expense::create([
                    'branch_id' => $this->branch->id,
                    'expense_category_id' => $cat('operasional'),
                    'user_id' => $this->owner->id,
                    'description' => "ATK & perlengkapan cafe {$monthStr}",
                    'amount' => $faker->numberBetween(200000, 600000),
                    'expense_date' => $month->copy()->startOfMonth()->addDays(8),
                ]);
            }
        }
    }

    // ----------------------------------------------------------- payroll

    private function createPayroll(): void
    {
        $payrollService = app(PayrollService::class);
        $periods = [
            ['2026-06-01', '2026-06-30', 'closed'],
            ['2026-07-01', '2026-07-31', 'closed'],
            ['2026-08-01', '2026-08-26', 'draft'],
        ];

        foreach ($periods as [$start, $end, $targetStatus]) {
            $period = PayrollPeriod::firstOrCreate(
                [
                    'branch_id' => $this->branch->id,
                    'period_start' => $start,
                    'period_end' => $end,
                ],
                ['status' => 'draft', 'created_by' => $this->owner->id]
            );

            if ($period->status === 'draft') {
                $payrollService->generateItems($period, $this->owner);
            }

            if ($targetStatus === 'closed' && in_array($period->status, ['draft', 'approved'])) {
                if ($period->status === 'draft') {
                    $payrollService->approve($period, $this->owner);
                }
                $payrollService->close($period, $this->owner);
            }
        }
    }

    // -------------------------------------------------------- transactions

    private function createTransactions(): void
    {
        $existingCount = Transaction::where('branch_id', $this->branch->id)->count();
        if ($existingCount >= 50) {
            return;
        }

        $service = app(TransactionService::class);
        $faker = \Faker\Factory::create('id_ID');
        $allCustomers = array_values($this->customers);

        $successCount = 0;
        $failCount = 0;

        // Helper: create transaction with backdate
        $makeTx = function (
            Customer $customer,
            array $items,
            array $payments,
            ?string $discountCode = null,
            ?string $date = null,
        ) use ($service, &$successCount, &$failCount) {
            $this->txCounter++;
            $data = [
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'idempotency_key' => "sip-tx-" . str_pad((string) $this->txCounter, 4, '0', STR_PAD_LEFT),
            ];
            if ($discountCode) {
                $data['discount_code'] = $discountCode;
            }

            try {
                $tx = $service->create($data, $items, []);

                foreach ($payments as $pm) {
                    $remaining = $service->remaining($tx);
                    $amount = min((float) $pm['amount'], (float) $remaining);
                    if ($amount > 0) {
                        $service->addPayment($tx, $pm['method'], (string) $amount, $pm['ref'] ?? null);
                    }
                }

                if ($date) {
                    $tx->forceFill(['transaction_date' => $date])->save();
                    $tx->payments()->update(['paid_at' => $date]);
                    $tx->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
                    DB::table('stock_movements')
                        ->where('ref_type', 'transaction')
                        ->where('ref_id', $tx->id)
                        ->update(['created_at' => $date, 'updated_at' => $date]);
                }

                $successCount++;
                return $tx->refresh();
            } catch (\Throwable $e) {
                $failCount++;
                return null;
            }
        };

        // ========================================
        // 1. Wisata Edukasi transactions (20)
        // ========================================
        for ($i = 0; $i < 20; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $product = collect([
                $this->products['Tiket Wisata Edukasi Anak (TK/SD)'],
                $this->products['Tiket Wisata Edukasi Keluarga'],
                $this->products['Paket Outbound Sekolah'],
            ])->random();
            $qty = $faker->numberBetween(1, 4);
            $date = now()->subDays(rand(10, 180))->setTime(rand(8, 14), rand(0, 59));

            $method = $faker->randomElement(['cash', 'transfer', 'qris']);
            $total = (float) $product->base_price * $qty;

            $makeTx($cust, [
                ['product_id' => $product->id, 'qty' => $qty],
            ], [
                ['method' => $method, 'amount' => $total],
            ], null, $date->format('Y-m-d H:i:s'));
        }

        // ========================================
        // 2. Camping transactions (15)
        // ========================================
        for ($i = 0; $i < 15; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $product = collect([
                $this->products['Paket Camping 1 Malam (Weekday)'],
                $this->products['Paket Camping 1 Malam (Weekend)'],
                $this->products['Paket Camping 2 Malam'],
            ])->random();
            $qty = $faker->numberBetween(1, 4);
            $date = now()->subDays(rand(10, 150))->setTime(rand(10, 16), rand(0, 59));

            // Some with equipment rental
            $items = [['product_id' => $product->id, 'qty' => $qty]];
            if ($faker->boolean(50)) {
                $items[] = ['product_id' => $this->products['Sewa Tenda Dome 4 Orang']->id, 'qty' => 1];
            }
            if ($faker->boolean(40)) {
                $items[] = ['product_id' => $this->products['Sewa Sleeping Bag']->id, 'qty' => $qty];
            }
            if ($faker->boolean(30)) {
                $items[] = ['product_id' => $this->products['Sewa Kompor Portable']->id, 'qty' => 1];
            }

            $method = $faker->randomElement(['cash', 'transfer', 'qris']);
            $discount = $faker->optional(0.3)->randomElement(['HEMAT10', 'CAMPING15']);

            $makeTx($cust, $items, [
                ['method' => $method, 'amount' => PHP_FLOAT_MAX],
            ], $discount, $date->format('Y-m-d H:i:s'));
        }

        // ========================================
        // 3. Peternakan/Ayam/Telur transactions (15)
        // ========================================
        for ($i = 0; $i < 15; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $items = [];
            $product = collect([
                $this->products['Ayam Petelur Siap Bertelur (18 minggu)'],
                $this->products['Ayam Petelur Premium (24 minggu)'],
                $this->products['Telur Ayam Kampung per Kg'],
            ])->random();
            $items[] = ['product_id' => $product->id, 'qty' => $faker->numberBetween(1, 5)];

            if ($faker->boolean(40)) {
                $items[] = ['product_id' => $this->products['Telur Ayam Kampung per Kg']->id, 'qty' => $faker->numberBetween(1, 3)];
            }

            $date = now()->subDays(rand(10, 180))->setTime(rand(8, 16), rand(0, 59));
            $method = $faker->randomElement(['cash', 'transfer']);

            $makeTx($cust, $items, [
                ['method' => $method, 'amount' => PHP_FLOAT_MAX],
            ], null, $date->format('Y-m-d H:i:s'));
        }

        // ========================================
        // 4. Bibit Tanaman transactions (10)
        // ========================================
        for ($i = 0; $i < 10; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $items = [];
            $bibitProducts = [
                $this->products['Bibit Terong Ungu'],
                $this->products['Bibit Cabai Merah'],
                $this->products['Bibit Tomat Cherry'],
                $this->products['Bibit Sawi Hijau'],
            ];

            $items[] = ['product_id' => $bibitProducts[array_rand($bibitProducts)]->id, 'qty' => $faker->numberBetween(5, 30)];
            if ($faker->boolean(50)) {
                $items[] = ['product_id' => $bibitProducts[array_rand($bibitProducts)]->id, 'qty' => $faker->numberBetween(5, 20)];
            }

            $date = now()->subDays(rand(10, 150))->setTime(rand(8, 15), rand(0, 59));

            $makeTx($cust, $items, [
                ['method' => 'cash', 'amount' => PHP_FLOAT_MAX],
            ], null, $date->format('Y-m-d H:i:s'));
        }

        // ========================================
        // 5. Villa transactions (15) — some linked to bookings
        // ========================================
        $villaBookings = collect($this->bookings)->filter(fn ($b) => $b->unit?->type === 'room' && $b->status !== 'cancelled')->take(10);

        foreach ($villaBookings as $booking) {
            $nights = $booking->nights();
            $product = $nights <= 2
                ? $this->products['Villa 2 Kamar (Weekday)']
                : $this->products['Villa 4 Kamar (Weekday)'];

            $date = $booking->date_start->copy()->setTime(12, 0)->format('Y-m-d H:i:s');

            $makeTx($booking->customer ?? $allCustomers[array_rand($allCustomers)], [
                ['product_id' => $product->id, 'qty' => $nights],
            ], [
                ['method' => 'transfer', 'amount' => PHP_FLOAT_MAX],
            ], null, $date);
        }

        // Walk-in villa (no booking)
        for ($i = 0; $i < 5; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $nights = rand(1, 3);
            $product = $nights <= 2
                ? $this->products['Villa 2 Kamar (Weekend)']
                : $this->products['Villa 4 Kamar (Weekend)'];

            $date = now()->subDays(rand(10, 90))->setTime(14, 0)->format('Y-m-d H:i:s');
            $discount = $faker->optional(0.2)->randomElement(['HEMAT10', 'VILLA100K']);

            $makeTx($cust, [
                ['product_id' => $product->id, 'qty' => $nights],
            ], [
                ['method' => $faker->randomElement(['cash', 'transfer', 'qris']), 'amount' => PHP_FLOAT_MAX],
            ], $discount, $date);
        }

        // ========================================
        // 6. Cafe transactions — DINE-IN (20)
        // ========================================
        for ($i = 0; $i < 20; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $items = [];

            // Makanan
            $makanan = collect([
                $this->products['Nasi Goreng Kampung'],
                $this->products['Mie Goreng Sapi'],
                $this->products['Nasi Timbel Komplit'],
                $this->products['Soto Ayam Kampung'],
            ])->random();
            $items[] = ['product_id' => $makanan->id, 'qty' => $faker->numberBetween(1, 3)];

            // Snack (optional)
            if ($faker->boolean(40)) {
                $snack = collect([
                    $this->products['Pisang Goreng (Pisgor)'],
                    $this->products['Gorengan Campur'],
                ])->random();
                $items[] = ['product_id' => $snack->id, 'qty' => 1];
            }

            // Minuman
            $minuman = collect([
                $this->products['Es Jeruk Segar'],
                $this->products['Es Teh Manis'],
                $this->products['Kopi Susu Gula Aren'],
                $this->products['Kopi Tubruk Biasa'],
                $this->products['Air Mineral 600ml'],
            ])->random();
            $items[] = ['product_id' => $minuman->id, 'qty' => $faker->numberBetween(1, 3)];

            $date = now()->subDays(rand(1, 90))->setTime(rand(8, 20), rand(0, 59));
            $method = $faker->randomElement(['cash', 'qris', 'card']);

            $makeTx($cust, $items, [
                ['method' => $method, 'amount' => PHP_FLOAT_MAX],
            ], null, $date->format('Y-m-d H:i:s'));
        }

        // ========================================
        // 7. Cafe transactions — ROOM SERVICE (10)
        // ========================================
        $activeVillaGuests = collect($this->bookings)
            ->filter(fn ($b) => $b->unit?->type === 'room' && in_array($b->status, ['checked_in', 'confirmed']) && $b->date_start->lte(now()) && $b->date_end->gte(now()))
            ->take(8);

        foreach ($activeVillaGuests as $booking) {
            $items = [];
            $makanan = collect([
                $this->products['Nasi Goreng Kampung'],
                $this->products['Nasi Timbel Komplit'],
            ])->random();
            $items[] = ['product_id' => $makanan->id, 'qty' => $faker->numberBetween(1, 2)];

            $minuman = collect([
                $this->products['Kopi Susu Gula Aren'],
                $this->products['Es Teh Manis'],
                $this->products['Air Mineral 600ml'],
            ])->random();
            $items[] = ['product_id' => $minuman->id, 'qty' => $faker->numberBetween(1, 2)];

            $date = now()->subDays(rand(1, 30))->setTime(rand(18, 21), rand(0, 59));

            $makeTx($booking->customer ?? $allCustomers[array_rand($allCustomers)], $items, [
                ['method' => 'cash', 'amount' => PHP_FLOAT_MAX],
            ], null, $date->format('Y-m-d H:i:s'));
        }

        // Room service standalone (no linked booking)
        for ($i = 0; $i < 5; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $items = [
                ['product_id' => $this->products['Nasi Goreng Kampung']->id, 'qty' => 1],
                ['product_id' => $this->products['Es Teh Manis']->id, 'qty' => 2],
            ];

            $date = now()->subDays(rand(1, 60))->setTime(rand(19, 22), rand(0, 59));

            $makeTx($cust, $items, [
                ['method' => 'cash', 'amount' => PHP_FLOAT_MAX],
            ], null, $date->format('Y-m-d H:i:s'));
        }

        // ========================================
        // 8. Gathering/Workshop transactions (10)
        // ========================================
        $gatherBookings = collect($this->bookings)->filter(fn ($b) => $b->unit?->type === 'meeting_room' && $b->status !== 'cancelled')->take(8);

        foreach ($gatherBookings as $booking) {
            $date = $booking->date_start->copy()->setTime(9, 0)->format('Y-m-d H:i:s');

            $makeTx($booking->customer ?? $allCustomers[array_rand($allCustomers)], [
                ['product_id' => $this->products['Sewa Ruang Gathering (Full Day)']->id, 'qty' => 1],
            ], [
                ['method' => 'transfer', 'amount' => PHP_FLOAT_MAX],
            ], null, $date);
        }

        // Walk-in gathering
        for ($i = 0; $i < 2; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $date = now()->subDays(rand(10, 60))->setTime(9, 0)->format('Y-m-d H:i:s');

            $makeTx($cust, [
                ['product_id' => $this->products['Sewa Ruang Gathering (Half Day)']->id, 'qty' => 1],
            ], [
                ['method' => 'transfer', 'amount' => PHP_FLOAT_MAX],
            ], 'GATHER20', $date);
        }

        // ========================================
        // 9. Void transaction (2)
        // ========================================
        try {
            $voidCust = $allCustomers[array_rand($allCustomers)];
            $voidDate = now()->subDays(rand(30, 120))->setTime(10, 0);
            $voidTx = $makeTx($voidCust, [
                ['product_id' => $this->products['Tiket Wisata Edukasi Keluarga']->id, 'qty' => 3],
            ], [
                ['method' => 'cash', 'amount' => PHP_FLOAT_MAX],
            ], null, $voidDate->format('Y-m-d H:i:s'));
            $service->void($voidTx);
        } catch (\Throwable) {
            // Void gagal, skip
        }

        try {
            $voidCust2 = $allCustomers[array_rand($allCustomers)];
            $voidDate2 = now()->subDays(rand(15, 60))->setTime(14, 0);
            $voidTx2 = $makeTx($voidCust2, [
                ['product_id' => $this->products['Paket Camping 1 Malam (Weekend)']->id, 'qty' => 2],
            ], [
                ['method' => 'qris', 'amount' => PHP_FLOAT_MAX],
            ], null, $voidDate2->format('Y-m-d H:i:s'));
            $service->void($voidTx2);
        } catch (\Throwable) {
            // Void gagal, skip
        }

        // ========================================
        // 10. Partial payment transactions (5)
        // ========================================
        for ($i = 0; $i < 5; $i++) {
            $cust = $allCustomers[array_rand($allCustomers)];
            $product = $this->products['Villa 2 Kamar (Weekday)'];
            $date = now()->subDays(rand(5, 30))->setTime(10, 0)->format('Y-m-d H:i:s');

            $makeTx($cust, [
                ['product_id' => $product->id, 'qty' => 3],
            ], [
                ['method' => 'transfer', 'amount' => 1000000],
            ], null, $date);
        }
    }
}
