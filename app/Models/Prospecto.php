<?php

namespace App\Models;

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospecto extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'admin_id',
        'name',
        'status',
        'visual',
        'phone_number',
        'email',
        'state',
        'municipality',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }


}
