<?php

namespace App\Models;

use Database\Factories\BookableUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookableUnit extends Model
{
    /** @use HasFactory<BookableUnitFactory> */
    use HasFactory;

    public const TYPES = ['room', 'meeting_room', 'camp_site'];

    protected $fillable = [
        'branch_id',
        'product_id',
        'type',
        'name',
        'capacity',
        'base_price',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Apakah unit bebas pada rentang [start, end)? Booking cancelled tidak
     * memblokir. end eksklusif (checkout boleh sama dengan check-in tamu lain).
     */
    public function scopeAvailable(Builder $query, string $dateStart, string $dateEnd): Builder
    {
        return $query->whereDoesntHave('bookings', function (Builder $q) use ($dateStart, $dateEnd) {
            $q->where('status', '!=', 'cancelled')
                ->whereDate('date_start', '<', $dateEnd)
                ->whereDate('date_end', '>', $dateStart);
        });
    }

    public function scopeForBranches(Builder $query, array $branchIds): Builder
    {
        return $query->whereIn('branch_id', $branchIds);
    }
}
