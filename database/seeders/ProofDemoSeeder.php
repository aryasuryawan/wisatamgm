<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProofDemoSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('proofs');

        $fakePdf = '%PDF-1.4 fake proof document for demo';
        Storage::disk('public')->put('proofs/demo-proof.pdf', $fakePdf);

        Expense::where('id', '<=', 3)->update(['proof_path' => 'proofs/demo-proof.pdf']);
        Payment::where('id', '<=', 5)->update(['proof_path' => 'proofs/demo-proof.pdf']);

        $this->command->info('Proof demo data seeded.');
    }
}
