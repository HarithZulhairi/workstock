<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $primaryKey = 'category_id'; // [cite: 60]

    protected $fillable = [
        'name', 'description'
    ];

    public function automotiveParts(): HasMany
    {
        return $this->hasMany(AutomotivePart::class, 'category_id', 'category_id');
    }
}