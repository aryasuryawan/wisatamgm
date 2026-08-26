<?php

return [
    // PPN diaktifkan (keputusan owner, 2026-08-24). Rate 11% sesuai regulasi Indonesia.
    'ppn' => [
        'enabled' => true,
        'rate' => 0.11,
    ],

    // Kategori produk yang mengurangi stok saat transaksi POS.
    'stockable_category_slugs' => ['makanan', 'merchandise'],

    'payment_methods' => ['cash', 'transfer', 'qris', 'card'],
];
