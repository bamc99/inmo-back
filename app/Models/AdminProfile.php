<?php

namespace App\Models;

use App\Models\AdminProfileImage;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'phone_number', // E.164 format
        'position',
        'birth_date',
    ];

    protected $hidden = [
        'profileImage',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
    
    public function profileImage()
    {
        return $this->hasOne(AdminProfileImage::class);
    }

}
