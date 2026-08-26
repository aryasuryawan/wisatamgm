<?php

namespace App\Domain\Discount\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Transaction;
use InvalidArgumentException;

class DiscountService
{
    /**
     * Validasi kode + hitung amount terhadap daftar (slug => subtotal-line).
     *
     * @param  list<array{type_slug: ?string, line_total: string}>  $lines
     * @return array{discount: Discount, amount: string}
     */
    public function resolveAndCalculate(string $code, array $lines, ?int $customerId, ?int $branchId): array
    {
        /** @var Discount|null $discount */
        $discount = Discount::where('code', $code)->first();

        if (! $discount || ! $discount->is_active) {
            throw new InvalidArgumentException(__('ui.discount_invalid'));
        }

        if ($discount->branch_id && $branchId && (int) $discount->branch_id !== $branchId) {
            throw new InvalidArgumentException(__('ui.discount_invalid'));
        }

        $today = now()->toDateString();
        if (($discount->valid_from && $discount->valid_from->toDateString() > $today)
            || ($discount->valid_until && $discount->valid_until->toDateString() < $today)) {
            throw new InvalidArgumentException(__('ui.discount_expired'));
        }

        if ($discount->usage_limit !== null && $discount->usages()->count() >= $discount->usage_limit) {
            throw new InvalidArgumentException(__('ui.discount_limit_reached'));
        }

        if ($customerId && $discount->usage_limit_per_customer !== null) {
            $used = $discount->usages()->where('customer_id', $customerId)->count();
            if ($used >= $discount->usage_limit_per_customer) {
                throw new InvalidArgumentException(__('ui.discount_customer_limit_reached'));
            }
        }

        $discountable = '0';
        foreach ($lines as $line) {
            if ($discount->category_scope === null || in_array($line['type_slug'], $discount->category_scope, true)) {
                $discountable = bcadd($discountable, $line['line_total'], 2);
            }
        }

        if (bccomp($discountable, '0', 2) <= 0) {
            throw new InvalidArgumentException(__('ui.discount_scope_mismatch'));
        }

        $amount = $discount->type === 'percent'
            ? bcmul($discountable, bcdiv((string) $discount->value, '100', 4), 2)
            : (string) $discount->value;

        $amount = bccomp($amount, $discountable, 2) > 0 ? $discountable : $amount;

        return ['discount' => $discount, 'amount' => $amount];
    }

    public function recordUsage(Discount $discount, Transaction $transaction, string $amount): DiscountUsage
    {
        /** @var DiscountUsage $usage */
        $usage = $discount->usages()->create([
            'transaction_id' => $transaction->id,
            'customer_id' => $transaction->customer_id,
            'amount' => $amount,
        ]);

        AuditLogger::log('discount.used', $usage, null, [
            'code' => $discount->code,
            'amount' => $amount,
            'transaction_id' => $transaction->id,
        ]);

        return $usage;
    }
}
