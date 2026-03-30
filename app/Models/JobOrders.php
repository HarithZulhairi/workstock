<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobOrder extends Model
{
    protected $primaryKey = 'job_orders_id'; // [cite: 60]

    protected $fillable = [
        'customer_name', 'customer_phone_num', 'vehicle_plate', 
        'reported_issue', 'status', 'total_cost', 'handled_by'
    ];

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }

    public function automotiveParts(): BelongsToMany
    {
        return $this->belongsToMany(AutomotivePart::class, 'job_orders_parts', 'job_orders_id', 'automotive_parts_id')
                    ->withPivot('job_order_parts_id', 'quantity_used', 'subtotal')
                    ->withTimestamps();
    }
}
