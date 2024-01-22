<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Jobs\SendEmail;
use App\Mail\Organization\AddCollaboratorEmail;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPasswordResetToken;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{

    public function getAll(Request $request)
    {

        $limit = $request->get('limit', 25);
        $offset = $request->get('offset', 0);
        $searchTerm = $request->get('searchTerm', null);

        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No estás autenticado',
                'errors' => [],
                'data' => [
                    'organization_id' => null
                ]
            ], 401);
        }

        if (!$authUser->hasRole('admin')) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción',
                'errors' => [],
                'data' => [
                    'organization_id' => null
                ]
            ], 403);
        }

        if (empty($searchTerm)) {
            // Obtiene la lista de organizaciones
            /** @var \App\Models\Organization $clients **/
            $organizations = Organization::with('owner')
                ->limit($limit)
                ->offset($offset)
                ->get();

            $total = Organization::count();

            // Devuelve la lista de clientes en una respuesta JSON.
            return response()->json(['organizations' => $organizations, 'total' => $total], 200);
        }

        /** $var \App\Models\Organization $clients **/
        $organizations = Organization::where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('type', 'LIKE', '%' . $searchTerm . '%');
        })
            ->orWhereHas('owner', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            })
            ->with('owner')
            ->skip($offset)
            ->take($limit)
            ->get();

        return [
            'organizations' => $organizations
        ];
    }

    public function getOrganizationAuth()
    {

        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->my_organization) {
            return response()->json([
                'message' => 'No tienes una organización',
                'errors' => [],
                'data' => [
                    'organization_id' => null
                ]
            ], 404);
        }

        $organization = $authUser->myOrganization;
        $collaborators = $organization->members;

        return response()->json([
            'data' => [
                'organization'  => $organization,
                'collaborators' => $collaborators,
                'user'          => $authUser
            ]
        ], 200);
    }

    public function addCollaborator(Request $request)
    {

        $errors = [];

        DB::beginTransaction();
        try {

            $addCollaboratorRequest = $request->validate([
                'email' => 'required|email'
            ], [
                'email.required' => 'El correo electrónico es requerido',
                'email.email' => 'El correo electrónico no es válido'
            ]);

            /** @var User $authUser */
            $authUser = Auth::user();
            if (!$authUser->my_organization) {
                return response()->json([
                    'message' => 'No tienes una organización',
                    'errors' => [],
                    'data' => [
                        'organization_id' => null
                    ]
                ], 404);
            }

            $userToCollaborate = User::where('email', $addCollaboratorRequest['email'])->first();

            if (!$userToCollaborate) {
                $errors['email'] = ['No se encontró ningún usuario con esa dirección de correo electrónico.'];
                return response()->json([
                    'message' => 'No se encontró ningún usuario con esa dirección de correo electrónico.',
                    'errors' => $errors
                ], 404);
            }

            if ($authUser->id === $userToCollaborate->id) {
                $errors['email'] = ['No puedes invitarte a ti mismo como colaborador.'];
                return response()->json([
                    'message' => 'No puedes invitarte a ti mismo como colaborador.',
                    'errors' => $errors
                ], 422);
            }

            // TODO: Descomentar cuando se implemente la funcionalidad de que un usuario pueda tener más de una organización
            // if (
            //     $authUser->ownedOrganizations()->exists() &&
            //     $userToCollaborate->collaboratingOrganizations()->exists() &&
            //     $authUser->ownedOrganizations()->first()->id !== $userToCollaborate->collaboratingOrganizations()->first()->id
            // ) {
            //     $errors['email'] = ['El usuario ya tiene una organización.'];
            //     return response()->json([
            //         'message' => 'El usuario ya tiene una organización.',
            //         'errors' => $errors
            //     ], 422);
            // }

            if ($userToCollaborate->organizations()->exists()) {
                $errors['email'] = ['El usuario ya está asociado con otra organización como colaborador.'];
                return response()->json([
                    'message' => 'El usuario ya está asociado con otra organización como colaborador.',
                    'errors' => $errors
                ], 400);
            }

            $token = Str::random(10);
            $expireInDays = 7; // 7 days

            UserPasswordResetToken::create([
                'email'      => $addCollaboratorRequest['email'],
                'token'      => $token,
                'expires_at' => Carbon::now()->addDays($expireInDays)
            ]);

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $resetUrl = "{$frontUrl}/organizacion/add-collaborator/{$token}?email={$addCollaboratorRequest['email']}&organization_id={$authUser->my_organization->id}";

            $addCollaboratorEmail = new AddCollaboratorEmail([
                'user'         => $userToCollaborate,
                'organization' => $authUser->my_organization,
                'action_url'   => $resetUrl,
                'code'         => $token,
                'expireInDays' => $expireInDays,
            ]);

            SendEmail::dispatch($userToCollaborate->email, $addCollaboratorEmail);

            DB::commit();
            return response()->json([
                'message' => 'Se ha enviado un correo electrónico a la dirección de correo electrónico proporcionada con un enlace para agregarlo como colaborador.',
            ], 200);
        } catch (ValidationException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 400);
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno del servidor',
                'errors' => []
            ], 500);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error interno del servidor',
                'errors' => $errors,
                'exception' => $th->getMessage()
            ], 500);
        }
    }

    public function acceptInvitation(Request $request)
    {
        $errors = [];

        DB::beginTransaction();
        try {

            $collaboratorRole = Role::where('name', 'collaborator')->first();

            if (!$collaboratorRole) {
                $errors['role'] = ['No se encontró el rol de colaborador.'];
                return response()->json([
                    'message' => 'No se encontró el rol de colaborador.',
                    'errors' => $errors
                ], 404);
            }

            $acceptInvitationRequest = $request->validate([
                'token' => [
                    'required',
                    'string',
                    'size:10',
                ],
                'email' => [
                    'required',
                    'email',
                ],
                'organization_id' => [
                    'required',
                    'integer',
                    'exists:organizations,id',
                ],
            ], [
                'token.required' => 'El token es requerido',
                'token.string' => 'El token debe ser una cadena de caracteres',
                'token.size' => 'El token debe tener exactamente 10 caracteres',
                'email.required' => 'El correo electrónico es requerido',
                'email.email' => 'El correo electrónico no es válido',
                'organization_id.required' => 'El ID de la organización es requerido',
                'organization_id.integer' => 'El ID de la organización debe ser un número entero',
                'organization_id.exists' => 'La organización con el ID proporcionado no existe',
            ]);

            $token = $acceptInvitationRequest['token'];
            $email = $acceptInvitationRequest['email'];
            $organizationId = $acceptInvitationRequest['organization_id'];

            $passwordResetToken = UserPasswordResetToken::where('email', $email)
                ->where('token', $token)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$passwordResetToken) {
                return response()->json([
                    'message' => 'El token no es válido.',
                    'errors' => [
                        'token' => ['El token no es válido.']
                    ]
                ], 400);
            }

            $userToCollaborate = User::where('email', $email)->first();

            if (!$userToCollaborate) {
                return response()->json([
                    'message' => 'No se encontró ningún usuario con esa dirección de correo electrónico.',
                    'errors' => [
                        'email' => ['No se encontró ningún usuario con esa dirección de correo electrónico.']
                    ]
                ], 404);
            }

            if ($userToCollaborate->my_organization) {
                return response()->json([
                    'message' => 'El usuario ya es parte de una organización y no puede unirse a otra.',
                    'errors' => [
                        'email' => ['El usuario ya es parte de una organización y no puede unirse a otra.']
                    ]
                ], 422);
            }

            $organization = Organization::find($organizationId);

            if (!$organization) {
                return response()->json([
                    'message' => 'No se encontró ninguna organización con ese ID.',
                    'errors' => [
                        'organization_id' => ['No se encontró ninguna organización con ese ID.']
                    ]
                ], 404);
            }

            if ($organization->members->contains($userToCollaborate->id)) {
                return response()->json([
                    'message' => 'El usuario ya es colaborador de esta organización.',
                    'errors' => [
                        'email' => ['El usuario ya es colaborador de esta organización.']
                    ]
                ], 422);
            }

            Membership::create([
                'user_id' => $userToCollaborate->id,
                'organization_id' => $organization->id,
            ]);

            $userToCollaborate->assignRole('collaborator', $collaboratorRole);

            if ($organization->type === 'inmobiliaria') {
                $userToCollaborate->givePermissionTo('collaborator_inmobiliaria');
            } else if ($organization->type === 'desarrollo') {
                $userToCollaborate->givePermissionTo('collaborator_desarrollo');
            } else {
                return response()->json([
                    'message' => 'El tipo de organización no es válido.',
                ], 500);
            }

            $passwordResetToken->delete();

            DB::commit();
            return response()->json([
                'message' => 'Se ha agregado al usuario como colaborador de la organización.',
            ], 200);
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno del servidor',
                'errors' => []
            ], 500);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error interno del servidor',
                'errors' => [
                    'exception' => $th->getMessage()
                ],
            ], 500);
        }
    }

    public function newOrganization(StoreOrganizationRequest $storeOrganizationRequest)
    {

        $storeOrganizationRequest->validated();

        $errors = [];

        DB::beginTransaction();

        try {

            /** @var User $authUser */
            $authUser = Auth::user();

            $ownerRole = Role::where('name', 'owner')
                ->where('guard_name', 'web')->first();

            if ($authUser->organization) {
                return response()->json([
                    'message' => 'No puedes crear una organización si ya tienes una',
                    'errors' => [],
                    'data' => [
                        'organization_id' => $authUser->organization->id
                    ]
                ], 400);
            }

            $newOrganization = Organization::create([
                'name' => $storeOrganizationRequest->name,
                'type' => $storeOrganizationRequest->organizationType,
                'owner_id' => $authUser->id,
            ]);

            $authUser->assignRole($ownerRole);

            if ($newOrganization->type === 'inmobiliaria') {
                $permission = Permission::where('name', 'collaborator_inmobiliaria')
                    ->where('guard_name', 'web')
                    ->first();
                $authUser->givePermissionTo($permission);
            } else if ($newOrganization->type === 'desarrollo') {
                $permission = Permission::where('name', 'collaborator_desarrollo')
                    ->where('guard_name', 'web')
                    ->first();
                $authUser->givePermissionTo($permission);
            }

            Membership::create([
                'user_id' => $authUser->id,
                'organization_id' => $newOrganization->id,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Organización creada correctamente',
                'data' => $newOrganization
            ], 201);
        } catch (ValidationException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 400);
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno del servidor',
                'errors' => []
            ], 500);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error al crear la organización',
                'errors' => $errors,
                'exception' => $th->getMessage()
            ], 500);
        }
    }

    public function newInmoFromController($userId, $organizationName)
    {
        $errors = [];

        DB::beginTransaction();

        try {

            /** @var User $authUser */
            $authUser = User::find($userId);

            $ownerRole = Role::where('name', 'owner')
                ->where('guard_name', 'web')->first();

            if ($authUser->organization) {
                return response()->json([
                    'message' => 'No puedes crear una organización si ya tienes una',
                    'errors' => [],
                    'data' => [
                        'organization_id' => $authUser->organization->id
                    ]
                ], 400);
            }

            $newOrganization = Organization::create([
                'name' => $organizationName,
                'type' => 'inmobiliaria',
                'owner_id' => $authUser->id,
            ]);

            $authUser->assignRole($ownerRole);

            if ($newOrganization->type === 'inmobiliaria') {
                $permission = Permission::where('name', 'collaborator_inmobiliaria')
                    ->where('guard_name', 'web')
                    ->first();
                $authUser->givePermissionTo($permission);
            } else if ($newOrganization->type === 'desarrollo') {
                $permission = Permission::where('name', 'collaborator_desarrollo')
                    ->where('guard_name', 'web')
                    ->first();
                $authUser->givePermissionTo($permission);
            }

            Membership::create([
                'user_id' => $authUser->id,
                'organization_id' => $newOrganization->id,
            ]);

            DB::commit();
            return [
                'message' => 'Organización creada correctamente',
                'data' => $newOrganization
            ];
        } catch (ValidationException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return [
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ];
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return [
                'message' => 'Error interno del servidor',
                'errors' => []
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return [
                'message' => 'Ocurrió un error al crear la organización',
                'errors' => $errors,
                'exception' => $th->getMessage()
            ];
        }
    }

    public function update(Request $request, $organizationId)
    {

        $requestData = $request->validate([
            'name' => 'string|max:255',
            'organizationType' => 'string|in:inmobiliaria,desarrollo',
            'isActive' => 'boolean'
        ]);

        DB::beginTransaction();

        try {

            /** @var User $authUser */
            $authUser = Auth::user();

            $organization = Organization::where('id', $organizationId);

            if (!$authUser->is_admin) {
                $organization = $organization->where('owner_id', $authUser->id);
            }

            $organization = $organization->first();

            if (!$organization) {
                return response()->json([
                    'message' => 'No se encontró ninguna organización con ese ID.',
                    'errors'  => [
                        'organization_id' => ['No se encontró ninguna organización con ese ID.']
                    ]
                ], 404);
            }

            $organization->update([
                'name'      => $requestData['name'] ?? $organization->name,
                'type'      => $requestData['organizationType'] ?? $organization->type,
                'is_active' => $requestData['isActive'] ?? $organization->is_active,
            ]);

            DB::commit();
            return response()->json([
                'organization' => $organization,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error al actualizar la organización',
                'errors' => [],
                'exception' => 'Ocurrió un error al actualizar la organización'
            ], 500);
        }
    }
}
