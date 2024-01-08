<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InmuebleRentar extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';
    protected $table = 'renta';

    protected $fillable = [
        'id',
        'titulo',
        'precio',
        'mantenimiento',
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
        'url',
        'fecha',
        'created_at',
    ];
}
