<?php

namespace App\Http\Controllers\Inmueble;

use App\Http\Controllers\Controller;
use App\Http\Resources\InmuebleCollection;
use App\Http\Resources\Inmuebles\InmuebleRentaCollection;
use App\Http\Resources\Inmuebles\InmuebleVentaCollection;
use App\Models\InmuebleComprar;
use App\Models\InmueblePreventa;
use App\Models\InmuebleRentar;
use App\Models\InmuebleVisorMetropoli;
use Illuminate\Http\Request;

class InmuebleController extends Controller
{
    private function applyFiltersInmuebles(Request $request, $query) {
        $filters = [
            'estado' => 'state',
            'municipio' => 'municipality',
            'metros_construidos' => 'construction',
            'metros_totales' => 'terrain',
            'recamaras' => 'rooms',
            'estacionamientos' => 'parkings',
            'banios' => 'bathrooms',
            'antiguedad' => 'antique',
        ];

        foreach ($filters as $column => $filter) {
            if ($request->has($filter) && $request->input($filter) !== null) {
                $query->where($column, $request->input($filter));
            }
        }

        // Calcular la distancia si se proporcionan la latitud y longitud del usuario
        if ($request->has('latitude') && $request->has('longitude')) {
            $userLatitude = $request->input('latitude');
            $userLongitude = $request->input('longitude');

            $query->selectRaw("*, (3959 * ACOS(
                COS(RADIANS(?)) * COS(RADIANS(latitud)) *
                COS(RADIANS(longitud) - RADIANS(?)) +
                SIN(RADIANS(?)) * SIN(RADIANS(latitud))
            )) AS distance", [$userLatitude, $userLongitude, $userLatitude]);
            $query->orderBy('distance', 'ASC');
        }

        if ($request->has('minPrice') && $request->input('minPrice') !== null) {
            $query->where('precios', '>=', $request->input('minPrice'));
        }

        if ($request->has('maxPrice') && $request->input('maxPrice') !== null) {
            $query->where('precios', '<=', $request->input('maxPrice'));
        }

        $query->limit(250);
        return $query;
    }

    public function getInmueblesComprar( Request $request ) {
        $query = InmuebleComprar::query();
        $query =  $this->applyFiltersInmuebles($request, $query);
        $inmuebles = $query->get();
        return new InmuebleCollection($inmuebles);
    }

    public function getInmueblesRentar( Request $request ) {
        $query = InmuebleRentar::query();

        $filters = [
            'estado' => 'state',
            'municipio' => 'municipality',
            'metros_construidos' => 'construction',
            'metros_totales' => 'terrain',
            'recamaras' => 'rooms',
            'estacionamientos' => 'parkings',
            'banios' => 'bathrooms',
            'antiguedad' => 'antique',
        ];

        foreach ($filters as $column => $filter) {
            if ($request->has($filter) && $request->input($filter) !== null) {
                $query->where($column, $request->input($filter));
            }
        }

        // Calcular la distancia si se proporcionan la latitud y longitud del usuario
        if ($request->has('latitude') && $request->has('longitude')) {
            $userLatitude = $request->input('latitude');
            $userLongitude = $request->input('longitude');

            $query->selectRaw("*, (3959 * ACOS(
                COS(RADIANS(?)) * COS(RADIANS(latitud)) *
                COS(RADIANS(longitud) - RADIANS(?)) +
                SIN(RADIANS(?)) * SIN(RADIANS(latitud))
            )) AS distance", [$userLatitude, $userLongitude, $userLatitude]);
            $query->orderBy('distance', 'ASC');
        }

        if ($request->has('minPrice') && $request->input('minPrice') !== null) {
            $query->where('precio', '>=', $request->input('minPrice'));
        }

        if ($request->has('maxPrice') && $request->input('maxPrice') !== null) {
            $query->where('precio', '<=', $request->input('maxPrice'));
        }

        $inmuebles = $query->get();
        return new InmuebleRentaCollection($inmuebles);
    }

    public function getInmueblesPreventa( Request $request ) {
        $query = InmueblePreventa::query();

        $filters = [
            'estado' => 'state',
            'municipio' => 'municipality',
            'metros_construidos' => 'construction',
            'metros_totales' => 'terrain',
            'recamaras' => 'rooms',
            'estacionamientos' => 'parkings',
            'banios' => 'bathrooms',
            'antiguedad' => 'antique',
        ];

        foreach ($filters as $column => $filter) {
            if ($request->has($filter) && $request->input($filter) !== null) {
                $query->where($column, $request->input($filter));
            }
        }

        // Calcular la distancia si se proporcionan la latitud y longitud del usuario
        if ($request->has('latitude') && $request->has('longitude')) {
            $userLatitude = $request->input('latitude');
            $userLongitude = $request->input('longitude');

            $query->selectRaw("*, (3959 * ACOS(
                COS(RADIANS(?)) * COS(RADIANS(latitud)) *
                COS(RADIANS(longitud) - RADIANS(?)) +
                SIN(RADIANS(?)) * SIN(RADIANS(latitud))
            )) AS distance", [$userLatitude, $userLongitude, $userLatitude]);
            $query->orderBy('distance', 'ASC');
        }

        if ($request->has('minPrice') && $request->input('minPrice') !== null) {
            $query->where('precio', '>=', $request->input('minPrice'));
        }

        if ($request->has('maxPrice') && $request->input('maxPrice') !== null) {
            $query->where('precio', '<=', $request->input('maxPrice'));
        }

        $inmuebles = $query->get();

        return new InmuebleVentaCollection($inmuebles);
    }

    public function getInmueblesInventario( Request $request ) {

        $comprarQuery = InmuebleComprar::query();
        $comprarQuery = $this->applyFiltersInmuebles($request, $comprarQuery);

        $rentarQuery = InmuebleRentar::query();
        $rentarQuery = $this->applyFiltersInmuebles($request, $rentarQuery);

        $preventaQuery = InmueblePreventa::query();
        $preventaQuery = $this->applyFiltersInmuebles($request, $preventaQuery);

        $query = $comprarQuery->union($rentarQuery)->union($preventaQuery);

        $inmuebles = $query->get();

        return new InmuebleCollection( $inmuebles );
    }

    public function getInmueblesVisorMetropoli( Request $request ) {
        $query = InmuebleVisorMetropoli::query();
        $query =  $this->applyFiltersInmuebles($request, $query);
        $inmuebles = $query->get();
        return new InmuebleCollection($inmuebles);
    }

}
