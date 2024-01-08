<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'contact_email',
        'contact_phone',
        'logo_id',
        'pdf_id',
    ];

}
