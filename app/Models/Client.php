<?php

namespace App\Models;

use App\Models\Prospecto;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'verification_token',
        'is_active'
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
        'is_verified',
        'profile_image_url',
        'can_create_loan_application',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function canCreateLoanApplication(): Attribute {
        // return new Attribute(
        //     get: fn () => is_null($this->profile->score) || $this->profile->score >= 550
        // );
        return new Attribute(
            get: fn () => !is_null($this->profile) && (is_null($this->profile->score) || $this->profile->score >= 550)
        );
    }

    public function isVerified(): Attribute {
        return new Attribute(
            get: fn () => $this->hasVerifiedEmail()
        );
    }

    public function profile()
    {
        return $this->hasOne(ClientProfile::class);
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
    /**
     * Obtiene el usuario asociado con el cliente.
     *
     * La función `belongsTo` establece una relación de "uno a muchos" inversa
     * entre el modelo `Client` y el modelo `User`. En este caso, cada cliente
     * pertenece a un usuario, lo que significa que hay una clave foránea
     * `user_id` en la tabla `clients` que hace referencia al campo `id` en la
     * tabla `users`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtiene las cotizaciones asociadas con el cliente.
     *
     * La función `hasMany` establece una relación "uno a muchos" entre el modelo
     * `Client` y el modelo `Cotización`. En este caso, un cliente puede tener
     * múltiples cotizaciones, lo que significa que hay una clave foránea
     * `client_id` en la tabla `cotizaciones` que hace referencia al campo `id` en la
     * tabla `clients`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
    */
    public function quotations(){
        return $this->hasMany(Quotation::class, 'client_id');
    }

    public function emailVerificationAttempts()
    {
        return $this->hasMany(ClientEmailVerificationAttempt::class);
    }

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function checkBuro() {
        $responseBuroArray = [];

        try {
            $jsonBuro = file_get_contents(resource_path('json/buro_example.json'));
            $responseBuroArray = json_decode($jsonBuro, true);
        } catch (Exception $e) {
            $responseBuroArray = [
                'status' => 'error',
                'message' => 'Error al leer el archivo JSON.',
                'errors' => [
                    'file' => 'Error al leer el archivo JSON.',
                ]
            ];
        }
        return $responseBuroArray;

        $publicApiKey = config('services.scorce.api_key');
        $scorceUrl = config('services.scorce.api_url');
        $buroCollection = $this->buroCollection();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Publicapikey' => $publicApiKey,
        ])->post($scorceUrl, $buroCollection);

        if ($response->successful()) {
            $responseArray = json_decode($response->body(), true);
        } else {
            $statusCode = $response->status();
            $errorDescription = 'Error desconocido al obtener el score del buró de crédito.';

            if ($statusCode == 404) {
                $errorDescription = 'No se encontró el recurso del buró de crédito.';
            } elseif ($statusCode == 403) {
                $errorDescription = 'No se tiene permiso para acceder al recurso del buró de crédito.';
            }

            $responseArray = [
                'status' => 'error',
                'message' => $errorDescription,
                'errors' => [
                    'score' => $errorDescription,
                ]
            ];
        }
        return $responseArray;
    }

    /**
     *
     * @return array
     */
    public function buroCollection () {
        $idEndPoint = config('services.scorce.api_id_endpoint');

        $params = [];
        $params[] = ['name' => 'birthdate',      'value' => $this->profile->birth_date];
        $params[] = ['name' => 'email',          'value' => 'sistemas@toi.com.mx'];
        $params[] = ['name' => 'externalId',     'value' => '' ];
        $params[] = ['name' => 'firstName',      'value' => $this->name];
        $params[] = ['name' => 'middleName',     'value' => '' ];
        $params[] = ['name' => 'firstLastName',  'value' => $this->profile->last_name];
        $params[] = ['name' => 'secondLastName', 'value' => $this->profile->middle_name];
        $params[] = ['name' => 'phone',          'value' => '' ];
        $params[] = ['name' => 'rfc',            'value' => $this->profile->rfc];
        $params[] = ['name' => 'curp',           'value' => '' ];
        $params[] = ['name' => 'accountType',    'value' => 'PF']; // Regimen PF - Persona Física
        $params[] = ['name' => 'address',        'value' => $this->profile->street];
        $params[] = ['name' => 'neighborhood',   'value' => $this->profile->neighborhood];
        $params[] = ['name' => 'city',           'value' => '' ];
        $params[] = ['name' => 'municipality',   'value' => $this->profile->municipality_name];
        $params[] = ['name' => 'state',          'value' => $this->profile->state_name];
        $params[] = ['name' => 'zipCode',        'value' => $this->profile->postal_code];
        $params[] = ['name' => 'exteriorNumber', 'value' => $this->profile->house_number];
        $params[] = ['name' => 'interiorNumber', 'value' => '' ];
        $params[] = ['name' => 'country',        'value' => $this->profile->country_name];
        $params[] = ['name' => 'nationality',    'value' => "MX"];
        return [
            'idEndPoint' => $idEndPoint,
            'params'     => $params
        ];
    }

    public function prospectos() {
        return $this->hasMany(Prospecto::class);
    }
}
