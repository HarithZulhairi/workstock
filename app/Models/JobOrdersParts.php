<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOrdersParts extends Model
{
    protected $table = 'job_orders_parts';
    protected $primaryKey = 'job_order_parts_id';

    protected $fillable = [
        'job_orders_id',
        'automotive_parts_id',
        'variation_id',
        'quantity_used',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the job order that owns this part entry
     */
    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrders::class, 'job_orders_id', 'job_orders_id');
    }

    /**
     * Get the automotive part
     */
    public function automotivePart(): BelongsTo
    {
        return $this->belongsTo(AutomotiveParts::class, 'automotive_parts_id', 'automotive_parts_id');
    }

    /**
     * Get the part variation (if used)
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(PartVariation::class, 'variation_id', 'variation_id');
    }
}
