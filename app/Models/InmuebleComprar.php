<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InmuebleComprar extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';
    protected $table = 'inmuebles';

    protected $fillable = [
        'id',
        'titulo',
        'precios',
        'moneda',
        'inmobiliaria',
        'tipo',
        'estado',
        'municipio',
        'zona',
        'latitud',
        'longitud',
        'imagenes',
        'metros_totales',
        'metros_construidos',
        'banios',
        'estacionamientos',
        'recamaras',
        'antiguedad',
        'medio_banio',
        'direcciones',
        'new_latitud',
        'new_longitud',
    ];
}
