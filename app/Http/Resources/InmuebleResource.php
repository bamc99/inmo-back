<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InmuebleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = array_map(function($url) {
            return str_replace("'", "", $url);
        }, explode(", ", $this->imagenes));

        return [
            'antiguedad'            => $this->antiguedad,
            'banios'                => $this->banios,
            'direcciones'           => $this->direcciones,
            'estacionamientos'      => $this->estacionamientos,
            'estado'                => $this->estado,
            'id'                    => $this->id,
            'imagenes'              => $images,
            'inmobiliaria'          => $this->inmobiliaria,
            'latitud'               => $this->new_latitud,
            'longitud'              => $this->new_longitud,
            'medio_banio'           => $this->medio_banio,
            'metros_construidos'    => $this->metros_construidos,
            'metros_totales'        => $this->metros_totales,
            'moneda'                => $this->moneda,
            'municipio'             => $this->municipio,
            'precios'               => $this->precios,
            'recamaras'             => $this->recamaras,
            'tipo'                  => $this->tipo,
            'titulo'                => $this->titulo,
            'zona'                  => $this->zona,
            'url'                   => $this->url,
            'telefono'              => $this->telefono,
            'nombre_asesor'         => $this->nombre_asesor,
            'email'                 => $this->email
        ];
    }
}
