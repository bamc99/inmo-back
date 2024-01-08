<?php

namespace App\Http\Resources\Inmuebles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InmuebleRentaCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
