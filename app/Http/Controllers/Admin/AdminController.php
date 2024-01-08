<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\User\StoreUserRequest;
use App\Http\Requests\Modules\Admin\User\UpdateUserRequest;
use App\Models\Admin;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request) {
        $storeUserRequestData = $request->validated();

        /** @var Admin $authUser */
        $authUser = Auth::user();
        if (!$authUser) return $this->unauthenticatedResponse();

        DB::beginTransaction();
        try {

            $userFounded = Admin::where('email', $storeUserRequestData['email'])->first();

            if($userFounded) {
                return response()->json([
                    'message' => 'El correo electrónico ya está en uso.',
                    'errors' => [
                        'email' => ['El correo electrónico ya está en uso.']
                    ]
                ], 400);
            }

            $newAdmin = Admin::create([
                'name'               => $storeUserRequestData['name'],
                'email'              => $storeUserRequestData['email'],
                'email_verified_at'  => Carbon::now(),
                'password'           => Hash::make($storeUserRequestData['password']),
            ]);

            $role = Role::where('name', $storeUserRequestData['roles'])
                ->where('guard_name', 'admin-api')->first();

            if(!$role) {
                $role = Role::create([
                    'name'       => $storeUserRequestData['roles'],
                    'guard_name' => 'admin-api'
                ]);
            }

            $newAdmin->assignRole($role);

            $newAdmin->profile()->create([
                'last_name'    => $storeUserRequestData['lastName'] ?? "",
                'phone_number' => $storeUserRequestData['phoneNumber'] ?? null,
                'position'     => "",
                'birth_date'   => $storeUserRequestData['birthDate'] ?? null,
            ]);

            DB::commit();
            return response()->json([
                'user'    => $newAdmin,
                'message' => 'Usuario registrado correctamente.',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al registrar el usuario.',
                'errors' => [
                    'client' => 'Ocurrió un error al registrar el usuario.'
                ]
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, Admin $admin) {
        DB::beginTransaction();
        try {
            $updateUserRequestData = $request->validated();

            /** @var \App\Models\User $authUser **/
            $authUser = Auth::user();
            if(!$authUser){
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => [
                        'auth' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            if( !$admin ){
                return response()->json([
                    'message' => 'No se encontró el usuario.',
                    'errors' => [
                        'user' => 'No se encontró el usuario.'
                    ]
                ], 400);
            }

            $adminEmail = $updateUserRequestData['email'] ?? null;
            $existingAdmin = Admin::where('email', $adminEmail)
                ->where('id', '<>', $admin->id)
                ->first();

            if( $existingAdmin ){
                return response()->json([
                    'message' => 'El correo electrónico ya está en uso.',
                    'errors' => [
                        'email' => 'El correo electrónico ya está en uso.'
                    ]
                ], 400);
            }

            $admin->update([
                'name' => $updateUserRequestData['name'] ?? $admin->name,
                'email' => $updateUserRequestData['email'] ?? $admin->email,
            ]);

            $admin->profile()->update([
                'last_name'         => $updateUserRequestData['lastName'] ?? $admin->profile->last_name,
                'phone_number'      => $updateUserRequestData['phoneNumber'] ?? $admin->profile->phone_number,
                'birth_date'        => $updateUserRequestData['birthDate'] ?? $admin->profile->birth_date,
            ]);

            $role = Role::where('name', $updateUserRequestData['roles'])
                ->where('guard_name', 'admin-api')->first();

            if(!$role) {
                $role = Role::create([
                    'name'       => $updateUserRequestData['roles'],
                    'guard_name' => 'admin-api'
                ]);
            }

            $admin->syncRoles($role);
            $admin->save();

            DB::commit();
            return response()->json([
                'user'=> $admin,
            ], 200);
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error con la base de datos',
                'errors' => []
            ], 500);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error al actualizar el usuario',
                'errors' => [
                    'user' => 'Hubo un error al actualizar el usuario'
                ]
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin) {
        /** @var Admin $authUser */
        $authUser = Auth::user();
        if (!$authUser) return $this->unauthenticatedResponse();

        DB::beginTransaction();

        try {

            if(!$admin) {
                return response()->json([
                    'message' => 'El usuario no existe.',
                    'errors' => [
                        'user' => 'El usuario no existe.'
                    ]
                ], 400);
            }

            if($authUser->id === $admin->id) {
                return response()->json([
                    'message' => 'No puedes eliminar tu propio usuario.',
                    'errors' => [
                        'user' => 'No puedes eliminar tu propio usuario.'
                    ]
                ], 400);
            }

            $admin->delete();

            DB::commit();
            return response()->json([
                'message' => 'Usuario eliminado correctamente.',
                'user' => $admin,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al eliminar el usuario.',
                'errors' => [
                    'user' => 'Ocurrió un error al eliminar el usuario.'
                ]
            ], 500);
        }

    }

    /**
     * Get By Id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function getById( Admin $admin ){

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if(!$authUser){
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors' => []
            ], 400);
        }

        if( !$admin ){
            return response()->json([
                'message' => 'No se encontró el usuario.',
                'errors' => []
            ], 400);
        }

        $adminUser = Admin::with('profile')->find($admin->id);

        return response()->json([ 'user' => $adminUser ], 200);
    }

    public function getAll( Request $request ) {
        $limit = $request->get('limit', 25);
        $offset = $request->get('offset', 0);
        $searchTerm = $request->get('searchTerm', null);


        $adminQuery = Admin::query()
            ->with('profile');

        if( empty( $searchTerm )) {

            $admins = $adminQuery->skip($offset)->take($limit)->get();
            $total = $adminQuery->count();

            return response()->json([
                'users' => $admins,
                'total' => $total,
            ], 200);
        }

        $searchQuery = function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
        };

        $profileQuery = function ($q) use ($searchTerm) {
            $q->where(DB::raw("CONCAT(last_name, ' ', middle_name)"), 'LIKE', '%' . $searchTerm . '%')
                ->orWhere(DB::raw("CONCAT(middle_name, ' ', last_name)"), 'LIKE', '%' . $searchTerm . '%');
        };

        $adminQuery = $adminQuery->where($searchQuery)
            ->orWhereHas('profile', $profileQuery);

        $admins = $adminQuery->skip($offset)->take($limit)->get();
        $total = $adminQuery->count();

        return response()->json([
            'admins' => $admins,
            'total' => $total,
        ], 200);
    }
}
