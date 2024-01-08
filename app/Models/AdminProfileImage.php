<?php

namespace App\Models;

use App\Models\Attachment;
use App\Models\AdminProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminProfileImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_profile_id',
        'attachment_id',
    ];

    public function adminProfile()
    {
        return $this->belongsTo(AdminProfile::class);
    }

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }
}
