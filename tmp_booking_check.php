<?php

$sip = \App\Models\Branch::where('name', 'SIP Garden Resort')->first();

echo 'units: '.\App\Models\BookableUnit::count().PHP_EOL;
echo 'bookings: '.\App\Models\Booking::count().PHP_EOL;

$paidBookings = \App\Models\Booking::whereHas('transaction', function ($q) {
    $q->where('status', 'paid');
})->count();
echo 'paid bookings: '.$paidBookings.PHP_EOL;

echo 'sip txns: '.(\App\Models\Transaction::where('branch_id', $sip?->id)->count()).PHP_EOL;

foreach (\App\Models\Booking::with('unit')->get() as $b) {
    echo '  ['.$b->status.'] '.$b->guest_name.' · '.$b->unit->name
        .' '.$b->date_start->format('d M').'→'.$b->date_end->format('d M')
        .' paid='.number_format($b->paidTotal(), 0, ',', '.').PHP_EOL;
}
