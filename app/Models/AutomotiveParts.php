<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'part_serial_number', 
    'name', 
    'part_picture', 
    'part_description', 
    'category_id', 
    'price', 
    'stock_quantity', 
    'is_visible_to_public',
    'brand',
    'warranty',
    'dimensions',
    'condition',
    'part_images',
])]

class AutomotiveParts extends Model
{
    protected $primaryKey = 'automotive_parts_id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'part_images' => 'array',
            'is_visible_to_public' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id', 'category_id');
    }

    public function jobOrders(): BelongsToMany
    {
        return $this->belongsToMany(JobOrders::class, 'job_orders_parts', 'automotive_parts_id', 'job_orders_id')
                    ->withPivot('job_order_parts_id', 'quantity_used', 'subtotal')
                    ->withTimestamps();
    }

    public function variations()
    {
        return $this->hasMany(PartVariation::class, 'automotive_parts_id', 'automotive_parts_id');
    }
}
