<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'last_name',
        'middle_name',
        'phone_number', // E.164 format

        'street',
        'house_number',
        'neighborhood',
        'municipality',
        'state',
        'postal_code',
        'country',

        'score',
        'birth_date',
        'rfc',
        'monthly_income',
        'additional_income',
    ];

    protected $hidden = [
        'profileImage',
        'client_id'
    ];

    protected $appends = [
        'municipality_name',
        'state_name',
        'country_name'
    ];


    public function getMunicipalityNameAttribute() {
        $federalEntities = config('federal_entities');

        // Encuentra la entidad federal (estado) que coincida con el estado de la instancia actual
        $federalEntity = collect($federalEntities)->firstWhere('slug', $this->state);

        // Si encontramos la entidad federal, entonces procedemos a buscar el municipio
        if ($federalEntity) {
            // Iteramos a través de los municipios de la entidad federal encontrada
            foreach ($federalEntity['municipalities'] as $municipality) {
                // Si el código del municipio coincide con el municipio de la instancia actual,
                // retornamos el nombre del municipio
                if ($municipality['code'] == $this->municipality) {
                    return Str::title($municipality['name']);
                }
            }
        }

        // Si no encontramos ni la entidad federal ni el municipio, retornamos el valor de municipio de la instancia actual
        return Str::title($this->municipality) ?? null;
    }
    public function getStateNameAttribute() {
        $federalEntities = config('federal_entities');
        $federalEntity = collect($federalEntities)->firstWhere('slug', $this->state);
        $state = $federalEntity['state'] ?? null;
        return Str::title($state) ?? null;
    }

    public function getCountryNameAttribute() {
        $countries = config('countries');
        $country = $countries[$this->country] ?? null;
        return Str::title($country) ?? null;
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function profileImage()
    {
        return $this->hasOne(ClientProfileImage::class);
    }
}
