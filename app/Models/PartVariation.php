<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'automotive_parts_id',
    'name',
    'price',
    'stock_quantity',
    'picture', 
])]
class PartVariation extends Model
{
    protected $primaryKey = 'variation_id';

    public function part(): BelongsTo
    {
        return $this->belongsTo(AutomotiveParts::class, 'automotive_parts_id', 'automotive_parts_id');
    }
}