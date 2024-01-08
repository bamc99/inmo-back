<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientEmailVerificationAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'token',
        'expires_at',
        'ip_address',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'expires_at',
    ];


    public function client() {
        return $this->belongsTo(Client::class);
    }
}
