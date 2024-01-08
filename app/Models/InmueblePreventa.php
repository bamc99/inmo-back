<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InmueblePreventa extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';
    protected $table = 'venta';

    protected $fillable = [
        'id',
        'titulo',
        'precio',
        'etapa',
        'moneda',
        'inmobiliaria',
        'tipo',
        'estado',
        'municipio',
        'zona',
        'latitud',
        'longitud',
        'imagenes',
        'amenidades',
        'direcciones',
        'url',
        'fecha',
        'created_at',
    ];
}
