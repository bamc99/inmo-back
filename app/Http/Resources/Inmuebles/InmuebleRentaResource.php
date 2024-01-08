<?php

namespace App\Http\Resources\Inmuebles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InmuebleRentaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = array_map(function($url) {
            return str_replace('\\/', '/', $url);
        }, json_decode($this->imagenes));

        return [
            'antiguedad'         => floatval($this->antiguedad),
            'banios'             => floatval($this->banios),
            'created_at'         => $this->created_at,
            'direcciones'        => $this->direcciones,
            'estacionamientos'   => floatval($this->estacionamientos),
            'estado'             => $this->estado,
            'fecha'              => $this->fecha,
            'id'                 => $this->id,
            'imagenes'           => $images,
            'inmobiliaria'       => $this->inmobiliaria,
            'latitud'            => floatval($this->latitud),
            'longitud'           => floatval($this->longitud),
            'mantenimiento'      => floatval($this->mantenimiento),
            'medio_banio'        => floatval($this->medio_banio),
            'metros_construidos' => floatval($this->metros_construidos),
            'metros_totales'     => floatval($this->metros_totales),
            'moneda'             => $this->moneda,
            'municipio'          => $this->municipio,
            'precio'             => floatval($this->precio),
            'recamaras'          => floatval($this->recamaras),
            'tipo'               => $this->tipo,
            'titulo'             => $this->titulo,
            'url'                => $this->url,
            'zona'               => $this->zona,
        ];
    }
}
