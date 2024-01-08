<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'owner_id',
        'is_active'
    ];

    protected $hidden = [
        'owner_id',
        'deleted_at',
        'created_at',
        'updated_at'
    ];


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'memberships');
    }
}
