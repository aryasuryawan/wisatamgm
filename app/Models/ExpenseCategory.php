<?php

namespace App\Models;

use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use HasFactory;

    public const SLUGS = [
        'operasional' => 'Operasional',
        'alat' => 'Alat',
        'gaji' => 'Gaji',
        'marketing' => 'Marketing',
        'sewa-tempat' => 'Sewa Tempat',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'name',
        'slug',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }
}
