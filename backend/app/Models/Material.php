<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'price',
        'stock',
        'description',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
    ];
}

