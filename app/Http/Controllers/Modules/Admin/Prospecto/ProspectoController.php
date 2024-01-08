<?php

namespace App\Http\Controllers\Modules\Admin\Prospecto;

use App\Http\Controllers\Controller;
use App\Models\Prospecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProspectoController extends Controller
{
    public function getAll( Request $request )
    {
        $limit = $request->get('limit', 25);
        $offset = $request->get('offset', 0);
        $page = $request->get('page', 1);

        /** @var \App\Models\Admin $authUser */
        $authUser = Auth::user();
        if(!$authUser) {
            return response()->json([
                'message' => 'No se ha encontrado el usuario',
                'errors' => [
                    'user' => 'No se ha encontrado el usuario'
                ]
            ], 404);
        }

        $prospectoQuery = Prospecto::with(['client', 'admin']);

        $prospectos = $prospectoQuery->skip($offset)->take($limit)->get();
        $total = $prospectoQuery->count();

        return response()->json([
            'prospectos' => $prospectos,
            'total'      => $total,
        ], 200);
    }

    public function getById($id)
    {
        /** @var \App\Models\Admin $authUser */
        $authUser = Auth::user();
        if(!$authUser) {
            return response()->json([
                'message' => 'No se ha encontrado el usuario',
                'errors' => [
                    'user' => 'No se ha encontrado el usuario'
                ]
            ], 404);
        }

        $prospecto = Prospecto::with(['client', 'admin'])
            ->where('id', $id)
            ->first();

        if (!$prospecto) {
            return response()->json([
                'message' => 'No se ha encontrado el prospecto',
                'errors' => [
                    'prospecto' => 'No se ha encontrado el prospecto'
                ]
            ], 404);
        }

        return response()->json([
            'prospecto' => $prospecto
        ], 200);
    }


    public function store( Request $request ) {

        $storeData = $request->validate([
            'name'         => ['required', 'string'],
            'clientId'     => ['integer','exists:clients,id'],
            'status'        => ['required', 'boolean'],
            'visual'        => ['required', 'boolean'],
            'phoneNumber'   => ['required', 'string'],
            'email'         => ['required', 'string', 'email'],
            'state'         => ['required', 'string'],
            'municipality'  => ['required', 'string'],
        ], [
            'name.required'     => 'El nombre del prospecto es obligatorio.',
            'clientId.required' => 'El cliente es obligatorio',
            'clientId.exists'   => 'El cliente no existe',
            'phoneNumber'        => 'El teléfono es obligatorio',
            'email.email'        => 'El correo electrónico del cliente no es válido.',
            'email.required'     => 'El correo electrónico del cliente es obligatorio.',
            'email.unique'       => 'Este correo electrónico ya está registrado.',
            'state.required'     => 'El estado del prospecto es obligatorio.',
            'state.string'       => 'El estado del prospecto debe ser una cadena de texto.',
            'status.boolean'     => 'El campo status debe ser un booleano',
            'visual.required'    => 'El campo visual es obligatorio',
            'visual.boolean'     => 'El campo visual debe ser un booleano',
        ]);

        /** @var \App\Models\Admin $authUser */
        $authUser = Auth::user();
        if(!$authUser) {
            return response()->json([
                'message' => 'No se ha encontrado el usuario autenticado',
                'errors' => [
                    'auth_user' => 'No se ha encontrado el usuario autenticado'
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $prospecto = Prospecto::create([
                'name'          => $storeData['name'] ?? null,
                'client_id'     => $storeData['clientId'] ?? null,
                'admin_id'      => $authUser->id,
                'email'         => $storeData['email'] ?? null,
                'municipality'  => $storeData['municipality'] ?? null,
                'phone_number'   => $storeData['phoneNumber'] ?? null,
                'state'         => $storeData['state'] ?? null,
                'status'        => filter_var($storeData['status'], FILTER_VALIDATE_BOOLEAN) ?? null,
                'visual'        => filter_var($storeData['visual'], FILTER_VALIDATE_BOOLEAN) ?? null,
            ]);

            DB::commit();
            return response()->json([
                'message'   => 'Se ha creado el prospecto exitosamente',
                'prospecto' => $prospecto
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'No se ha podido crear el prospecto',
                'errors'  => [
                    'prospecto' => 'No se ha podido crear el prospecto'
                ]
            ], 500);
        }
    }

    public function update( Prospecto $prospecto, Request $request ) {

        $updateData = $request->validate([
            'name'          => ['string'],
            'email'         => ['string', 'email'],
            'municipality'  => ['string'],
            'state'         => ['string'],
            'phoneNumber'   => ['string'],
            'status'        => ['boolean'],
            'visual'        => ['boolean'],
        ], [
            'name.string'        => 'El nombre del prospecto debe ser una cadena de texto.',
            'email.email'        => 'El correo electrónico del cliente no es válido.',
            'municipality.string' => 'El municipio del prospecto debe ser una cadena de texto.',
            'state.required'     => 'El estado del prospecto es obligatorio.',
            'state.string'       => 'El estado del prospecto debe ser una cadena de texto.',
            'phoneNumber'        => 'El teléfono es obligatorio',
            'status.boolean'     => 'El campo status debe ser un booleano',
            'visual.boolean'     => 'El campo visual debe ser un booleano',
        ]);

        /** @var \App\Models\Admin $authUser */
        $authUser = Auth::user();
        if(!$authUser) {
            return response()->json([
                'message' => 'No se ha encontrado el usuario autenticado',
                'errors' => [
                    'auth_user' => 'No se ha encontrado el usuario autenticado'
                ]
            ], 404);
        }

        if(!$prospecto) {
            return response()->json([
                'message' => 'No se ha encontrado el prospecto',
                'errors' => [
                    'prospecto' => 'No se ha encontrado el prospecto'
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {

            $prospecto->update([
                'name'          => $updateData['name'] ?? $prospecto->name,
                'email'         => $updateData['email'] ?? $prospecto->email,
                'municipality'  => $updateData['municipality'] ?? $prospecto->municipality,
                'phoneNumber'   => $updateData['phoneNumber'] ?? $prospecto->phoneNumber,
                'state'         => $updateData['state'] ?? $prospecto->state,
                'status'        => filter_var($updateData['status'] ?? $prospecto->status, FILTER_VALIDATE_BOOLEAN) ,
                'visual'        => filter_var($updateData['visual'] ?? $prospecto->visual, FILTER_VALIDATE_BOOLEAN) ,
            ]);

            DB::commit();
            return response()->json([
                'message'   => 'Se ha actualizado el prospecto exitosamente',
                'prospecto' => $prospecto
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'No se ha podido actualizar el prospecto',
                'errors'  => [
                    'prospecto' => 'No se ha podido actualizar el prospecto'
                ]
            ], 500);
        }
    }

    public function destroy( Prospecto $prospecto ) {

        /** @var \App\Models\Admin $authUser */
        $authUser = Auth::user();
        if(!$authUser) {
            return response()->json([
                'message' => 'No se ha encontrado el usuario autenticado',
                'errors' => [
                    'auth_user' => 'No se ha encontrado el usuario autenticado'
                ]
            ], 404);
        }

        if(!$prospecto) {
            return response()->json([
                'message' => 'No se ha encontrado el prospecto',
                'errors' => [
                    'prospecto' => 'No se ha encontrado el prospecto'
                ]
            ], 404);
        }

        DB::beginTransaction();

        try {
            $prospecto->delete();
            DB::commit();
            return response()->json([
                'message' => 'Se ha eliminado el prospecto exitosamente',
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'No se ha podido eliminar el prospecto',
                'errors'  => [
                    'prospecto' => 'No se ha podido eliminar el prospecto'
                ]
            ], 500);
        }

    }

}
