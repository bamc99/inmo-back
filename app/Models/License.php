<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'stripe_product_id',
        'stripe_price_id',
        'price',
        'description',
        'currency',
    ];

    public $timestamps = false;

}
