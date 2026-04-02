<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 
    'description'
])]

class Categories extends Model
{
    protected $primaryKey = 'category_id';

    public function automotiveParts(): HasMany
    {
        return $this->hasMany(AutomotiveParts::class, 'category_id', 'category_id');
    }
}