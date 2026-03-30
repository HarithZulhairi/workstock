<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AutomotivePart extends Model
{
    protected $primaryKey = 'automotive_parts_id'; // [cite: 59]

    protected $fillable = [
        'part_serial_number', 'name', 'part_picture', 'part_description', 
        'category_id', 'price', 'stock_quantity', 'is_visible_to_public'
    ];

    protected $casts = [
        'is_visible_to_public' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function jobOrders(): BelongsToMany
    {
        return $this->belongsToMany(JobOrder::class, 'job_orders_parts', 'automotive_parts_id', 'job_orders_id')
                    ->withPivot('job_order_parts_id', 'quantity_used', 'subtotal')
                    ->withTimestamps();
    }
}
