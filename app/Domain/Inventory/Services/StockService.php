<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function stockIn(Product $product, int $qty, ?string $notes = null, ?string $unitCost = null): StockMovement
    {
        return DB::transaction(function () use ($product, $qty, $notes, $unitCost) {
            $product->increment('stock_quantity', $qty);

            $movement = StockMovement::create([
                'branch_id' => $product->branch_id ?? auth()->user()->branches()->first()?->id ?? 1,
                'product_id' => $product->id,
                'type' => 'in',
                'qty' => $qty,
                'qty_after' => $product->fresh()->stock_quantity,
                'unit_cost' => $unitCost,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            AuditLogger::log('stock.in', $movement, null, ['qty' => $qty, 'qty_after' => $movement->qty_after]);

            return $movement;
        });
    }

    public function stockOut(Product $product, int $qty, ?string $notes = null, ?string $refType = null, ?int $refId = null): StockMovement
    {
        if ($product->stock_quantity < $qty) {
            throw new \RuntimeException(__('messages.stock_insufficient'));
        }

        return DB::transaction(function () use ($product, $qty, $notes, $refType, $refId) {
            $product->decrement('stock_quantity', $qty);

            $movement = StockMovement::create([
                'branch_id' => $product->branch_id ?? auth()->user()->branches()->first()?->id ?? 1,
                'product_id' => $product->id,
                'type' => 'out',
                'qty' => -$qty,
                'qty_after' => $product->fresh()->stock_quantity,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            AuditLogger::log('stock.out', $movement, null, ['qty' => -$qty, 'qty_after' => $movement->qty_after]);

            return $movement;
        });
    }

    public function adjust(Product $product, int $newQty, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($product, $newQty, $notes) {
            $diff = $newQty - $product->stock_quantity;
            $product->update(['stock_quantity' => $newQty]);

            $movement = StockMovement::create([
                'branch_id' => $product->branch_id ?? auth()->user()->branches()->first()?->id ?? 1,
                'product_id' => $product->id,
                'type' => 'adjustment',
                'qty' => $diff,
                'qty_after' => $newQty,
                'notes' => $notes ?? __('ui.stock_opname'),
                'user_id' => auth()->id(),
            ]);

            AuditLogger::log('stock.adjustment', $movement, ['stock_quantity' => $product->stock_quantity], ['stock_quantity' => $newQty]);

            return $movement;
        });
    }
}
