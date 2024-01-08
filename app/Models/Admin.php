<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\AdminProfile;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Authenticatable
{
    use HasFactory, HasRoles, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // Hidden
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Appends
    protected $appends = [
        'position',
        'profile_image_url',
        'full_name',
        'role_names',
    ];

    public function roleNames(): Attribute {
        return new Attribute(
            get: fn () => $this->roles()->pluck('name')->toArray() ?? []
        );
    }

    // Relationships
    public function profile()
    {
        return $this->hasOne(AdminProfile::class);
    }

    // Accessors
    public function position(): Attribute
    {
        return new Attribute(
            get: fn () => $this->profile ? $this->profile->position : null
        );
    }

    public function profileImageUrl(): Attribute
    {
        return new Attribute(
            get: fn () => $this->profile && $this->profile->profileImage && $this->profile->profileImage->attachment
                ? $this->profile->profileImage->attachment->url
                : null
        );
    }

    public function fullName(): Attribute {
        $lastName = $this->profile ? $this->profile->last_name : '';
        return new Attribute(
            get: fn () => "{$this->name} $lastName"
        );

    }

}
