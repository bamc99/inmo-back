<?php

namespace App\Models;

use App\Models\Attachment;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfileImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_profile_id',
        'attachment_id',
    ];


    public function userProfile()
    {
        return $this->belongsTo(UserProfile::class);
    }

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

}
