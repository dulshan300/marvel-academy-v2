<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'slug', 'description', 'price_monthly', 'subjects_included', 'hours_per_month',
    'trial_days', 'features', 'stripe_product_id', 'stripe_price_id', 'is_featured', 'is_active', 'position',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Effective hourly rate in pence, used for the per-hour pricing breakdown.
     */
    public function hourlyRate(): int
    {
        if ($this->hours_per_month === 0) {
            return 0;
        }

        return (int) round($this->price_monthly / $this->hours_per_month);
    }
}
