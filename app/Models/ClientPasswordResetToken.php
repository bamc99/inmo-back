<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientPasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $expireInHours = config('auth.passwords.users.expire') / 60;
            $model->id = Str::uuid()->toString();
            $model->expires_at = Carbon::now()->addHours($expireInHours); // Establece un valor predeterminado para expires_at
        });
    }

    /**
     * Verifica si un usuario puede crear un nuevo token de restablecimiento de contraseña.
     *
     * @param string $email El correo electrónico del usuario
     * @return bool True si el usuario puede crear un nuevo token; de lo contrario, false.
     */
    public static function canCreatePasswordResetToken(string $email): bool
    {
        $count = static::where('email', $email)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        return $count < 3;
    }

}
