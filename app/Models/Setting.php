<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $value]);
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function allGrouped(): array
    {
        return static::all()
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('value', 'key')->toArray())
            ->toArray();
    }
}
