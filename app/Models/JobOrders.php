<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_name', 
    'customer_phone_num', 
    'vehicle_plate',
    'vehicle_brand',
    'vehicle_model',
    'vehicle_picture',
    'reported_issue', 
    'status', 
    'total_cost', 
    'handled_by'
])]

class JobOrders extends Model
{
    protected $primaryKey = 'job_orders_id';

    protected $casts = [
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the user who handled this job order
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }

    /**
     * Get all parts used in this job order (many-to-many)
     */
    public function automotiveParts(): BelongsToMany
    {
        return $this->belongsToMany(AutomotiveParts::class, 'job_orders_parts', 'job_orders_id', 'automotive_parts_id')
                    ->withPivot('job_order_parts_id', 'variation_id', 'quantity_used', 'unit_price', 'subtotal')
                    ->withTimestamps();
    }

    /**
     * Get all job order parts entries (direct access to pivot records)
     */
    public function jobOrderParts(): HasMany
    {
        return $this->hasMany(JobOrdersParts::class, 'job_orders_id', 'job_orders_id');
    }
}
