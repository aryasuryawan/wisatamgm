<?php

namespace App\Domain\Finance\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(User $actor, array $data): Expense
    {
        $expense = Expense::create($this->prepare($data, $actor));

        AuditLogger::log('expense_created', $expense, null, $expense->only(
            ['branch_id', 'expense_category_id', 'description', 'amount', 'expense_date', 'marketing_campaign_id']
        ));

        return $expense;
    }

    public function update(Expense $expense, User $actor, array $data): Expense
    {
        $this->assertEditable($expense);

        $before = $expense->only(
            ['branch_id', 'expense_category_id', 'description', 'amount', 'expense_date', 'marketing_campaign_id']
        );

        $expense->update($this->prepare($data, $actor));

        AuditLogger::log('expense_updated', $expense, $before, $expense->only(array_keys($before)));

        return $expense;
    }

    public function delete(Expense $expense, User $actor): void
    {
        $this->assertEditable($expense);

        $before = $expense->toArray();

        $expense->delete();

        AuditLogger::log('expense_deleted', $expense, $before, null);
    }

    /**
     * Baris expense yang di-generate sistem (mis. dari payroll) tidak boleh
     * diubah manual — harus lewat modul sumbernya agar laba-rugi konsisten.
     */
    public function assertEditable(Expense $expense): void
    {
        if ($expense->ref_type !== null) {
            throw ValidationException::withMessages([
                'expense' => __('messages.expense_generated_locked'),
            ]);
        }
    }

    private function prepare(array $data, User $actor): array
    {
        $data['user_id'] = $actor->id;

        if (($data['marketing_campaign_id'] ?? null) === '') {
            $data['marketing_campaign_id'] = null;
        }

        return $data;
    }
}
