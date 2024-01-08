<?php

namespace App\Models;

use App\Models\UserEmailVerificationAttempt;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'email',
        'password',
        'verification_token',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'roles',
        'deleted_at',
        'email_verified_at',
    ];

    protected $appends = [
        'full_name',
        'has_organizations',
        'is_admin',
        'is_organization_collaborator',
        'is_organization_owner',
        'is_verified',
        'my_organization',
        'permission_names',
        'profile_image_url',
        'role_names',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'user_name' => 'string',
    ];

    /**
        * Generate a unique username
        *
        * @param string $name
        * @param string $lastName
        * @return string
    */
    public function generateUsername($name, $lastName)
    {
        $username = Str::slug($name . ' ' . $lastName, '.');
        $count = 1;
        while (self::where('user_name', $username)->exists()) {
            $username = Str::slug($name . ' ' . $lastName, '') . $count;
            $count++;
        }
        return $username;
    }

    public function isVerified(): Attribute {
        return new Attribute(
            get: fn () => $this->hasVerifiedEmail()
        );
    }

    public function isAdmin(): Attribute {
        return new Attribute(
            get: fn () => $this->hasRole('admin')
        );
    }

    public function permissionNames(): Attribute {
        return new Attribute(
            get: fn () => $this->permissions()->pluck('name')->toArray() ?? []
        );
    }

    public function roleNames(): Attribute {
        return new Attribute(
            get: fn () => $this->roles()->pluck('name')->toArray() ?? []
        );
    }

    public function fullName(): Attribute {
        $lastName = $this->profile ? $this->profile->last_name : '';
        return new Attribute(
            get: fn () => "{$this->name} $lastName"
        );
    }

    public function profileImageUrl(): Attribute {
        return new Attribute(
            get: fn () => $this->profile && $this->profile->profileImage && $this->profile->profileImage->attachment
                ? $this->profile->profileImage->attachment->url
                : null
        );
    }


    public function hasOrganizations(): Attribute {
        return new Attribute(
            get: fn () => $this->ownedOrganizations()->exists() || $this->organizations()->exists()
        );
    }

    public function isOrganizationOwner(): Attribute {
        return new Attribute(
            get: fn () => $this->ownedOrganizations()->exists() // return $this->hasRole('owner');
        );
    }

    public function isOrganizationCollaborator(): Attribute {
        return new Attribute(
            get: fn () => $this->hasRole('collaborator')
        );
    }

    public function myOrganization(): Attribute {
        return new Attribute(
            get: fn () => $this->ownedOrganizations->first() ?? $this->organizations->first()
        );
    }

    /**
     * Define la relación uno a uno entre el usuario y su perfil.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @throws \Exception si el perfil del usuario no existe
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Define la relación uno a muchos entre el usuario y los intentos de verificación de correo electrónico.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    public function emailVerificationAttempts()
    {
        return $this->hasMany(UserEmailVerificationAttempt::class);
    }

    /**
     * Define la relación uno a muchos entre el usuario y sus clientes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @throws \Exception si no se encuentra ningún cliente asociado con el usuario
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Define la relación uno a muchos entre el usuario y las organizaciones que ha creado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ownedOrganizations()
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /**
     * Define la relación muchos a muchos entre el usuario y las organizaciones a las que pertenece como colaborador.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'memberships');
    }
}
