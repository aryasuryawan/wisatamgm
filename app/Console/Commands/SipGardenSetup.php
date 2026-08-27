<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SipGardenSetup extends Command
{
    protected $signature = 'sipgarden:setup
        {--fresh : Hapus semua data, migrate ulang, dan seed dari awal}
        {--seed-only : Jalankan seed saja tanpa migrate}';

    protected $description = 'Setup database sipgarden: migrate + seed SipGardenFreshSeeder';

    private string $connection = 'sipgarden';
    private string $database;

    public function handle(): int
    {
        $this->database = config("database.connections.{$this->connection}.database");

        $this->info("=== SIP Garden Database Setup ===");
        $this->newLine();

        // 1. Pastikan database ada
        $this->ensureDatabase();

        // 2. Set default connection
        config(['database.default' => $this->connection]);
        DB::purge($this->connection);

        if ($this->option('seed-only')) {
            return $this->runSeed();
        }

        // 3. Migrate
        if ($this->option('fresh')) {
            $this->info('Migration: fresh (drop + recreate all tables)...');
            Artisan::call('migrate:fresh', ['--database' => $this->connection], $this->getOutput());
        } else {
            $this->info('Migration...');
            Artisan::call('migrate', ['--database' => $this->connection], $this->getOutput());
        }

        $this->newLine();

        // 4. Seed
        return $this->runSeed();
    }

    private function runSeed(): int
    {
        $this->info('Seeding SIP Garden data...');

        // Seed base tables first
        $baseSeeders = [
            \Database\Seeders\RolesPermissionSeeder::class,
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ExpenseCategorySeeder::class,
        ];

        foreach ($baseSeeders as $seeder) {
            $shortName = class_basename($seeder);
            $this->line("  -> {$shortName}");
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--database' => $this->connection,
                '--force' => true,
            ], $this->getOutput());
        }

        // Seed SIP Garden data
        $this->line('  -> SipGardenFreshSeeder');
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\SipGardenFreshSeeder::class,
            '--database' => $this->connection,
            '--force' => true,
        ], $this->getOutput());

        $this->newLine();
        $this->info("=== SIP Garden selesai! ===");
        $this->line("Database: {$this->database}");
        $this->line("Login:    owner@sipgarden.id / password");
        $this->line("Jalankan: php artisan serve --database=sipgarden");
        $this->newLine();

        return Command::SUCCESS;
    }

    private function ensureDatabase(): void
    {
        $dbName = $this->database;

        // Create database via raw MySQL connection (using default connection)
        config(['database.default' => 'mysql']);
        DB::purge('mysql');

        $this->info("Mengecek database '{$dbName}'...");

        $exists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

        if (empty($exists)) {
            $this->line("  Membuat database '{$dbName}'...");
            DB::statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("  Database berhasil dibuat.");
        } else {
            $this->info("  Database sudah ada.");
        }
    }
}
