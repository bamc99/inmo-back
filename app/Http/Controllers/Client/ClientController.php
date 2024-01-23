<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\StoreClientWithQuotationRequest;
use App\Http\Requests\Client\UpdateClientWithQuotationRequest;
use App\Models\Client;
use App\Models\Permission;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{

    private function updateClientProfile($client, $clientRequestData)
    {
        $client->profile()->update([
            // ... datos para actualizar
        ]);
        $client->save();
    }

    private function validateClientEmail($clientEmail, $clientId)
    {
        $existingClient = Client::where('email', $clientEmail)
            ->where('id', '<>', $clientId)
            ->first();
        if ($existingClient) {
            throw new \Exception('El correo electrónico ya existe.');
        }
    }

    public function updateWithQuotation(UpdateClientWithQuotationRequest $request, $clientId)
    {

        DB::beginTransaction();
        try {
            $clientWithQuotationRequest = $request->validated();

            $clientRequestData = $clientWithQuotationRequest['client'];
            $quotationRequestData = $clientWithQuotationRequest['quotation'] ?? null;

            /** @var \App\Models\User $authUser **/
            $authUser = Auth::user();
            if (!$authUser) {
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => [
                        'auth' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            $client = Client::where('id', $clientId);

            if (!$authUser->hasRole('admin') && !$authUser->is_organization_owner) {
                $client->where('user_id',  $authUser->id);
            }

            $client = $client->first();

            if (!$client) {
                return response()->json([
                    'message' => 'No se encontró el cliente.',
                    'errors' => [
                        'client' => 'No se encontró el cliente.'
                    ]
                ], 400);
            }

            // TODO: Refactorizar a métodos más pequeños
            // $this->validateClientEmail($clientRequestData['email'] ?? null, $clientId);
            // $this->updateClientProfile($client, $clientRequestData);

            $clientEmail = $clientRequestData['email'] ?? null;
            $existingClient = Client::where('email', $clientEmail)
                ->where('id', '<>', $client->id)
                ->first();

            if ($existingClient) {
                $errors['email'] = 'El correo electrónico ya existe.';
                return response()->json([
                    'message' => 'No se pudo guardar el cliente.',
                    'errors' => $errors
                ], 400);
            }

            $client->update([
                'name' => $clientRequestData['name'] ?? $client->name,
                'email' => $clientRequestData['email'] ?? $client->email,
            ]);

            $client->profile()->update([
                'last_name'         => $clientRequestData['lastName'] ?? $client->profile->last_name,
                'rfc'               => isset($clientRequestData['rfc']) ? Str::upper($clientRequestData['rfc']) : $client->profile->rfc,
                'phone_number'      => $clientRequestData['phoneNumber'] ?? $client->profile->phone_number,
                'street'            => $clientRequestData['street'] ?? null,
                'house_number'      => $clientRequestData['house_number'] ?? null,
                'neighborhood'      => $clientRequestData['neighborhood'] ?? null,
                'municipality'      => $clientRequestData['municipality'] ?? null,
                'state'             => $clientRequestData['state'] ?? null,
                'postal_code'       => $clientRequestData['postal_code'] ?? null,
                'country'           => $clientRequestData['country'] ?? null,
                'birth_date'        => $clientRequestData['birthDate'] ?? $client->profile->birth_date,
                'monthly_income'    => $clientRequestData['monthlyIncome'] ?? $client->profile->monthly_income,
                'additional_income' => $clientRequestData['additionalIncome'] ?? $client->profile->additional_income,
            ]);

            $client->save();

            if (
                // ($authUser->hasPermissionTo('collaborator_inmobiliaria') ||
                //     $authUser->hasRole('admin')
                // ) && 
                $request->has('quotation')
            ) {
                $newQuotation = $client->quotations()->create([
                    'additional_income'         => $clientRequestData['additionalIncome'] ?? 0,
                    'monthly_income'            => $clientRequestData['monthlyIncome'] ?? 0,
                    'additional_property_value' => $quotationRequestData['additionalPropertyValue'] ?? 0,
                    'construction_area'         => $quotationRequestData['constructionArea'] ?? 0,
                    'credit_import'             => $quotationRequestData['creditImport'] ?? 0,
                    'credit_type'               => $quotationRequestData['creditType'] ?? 0,
                    'current_debt'              => $quotationRequestData['currentDebt'] ?? 0,
                    'down_payment'              => $quotationRequestData['downPayment'] ?? 0,
                    'infonavit_credit'          => $quotationRequestData['infonavitCredit'] ?? 0,
                    'land_area'                 => $quotationRequestData['landArea'] ?? 0,
                    'loan_amount'               => $quotationRequestData['loanAmount'] ?? 0,
                    'loan_term'                 => $quotationRequestData['loanTerm'] ?? 0,
                    'project_value'             => $quotationRequestData['projectValue'] ?? 0,
                    'notarial_fees_percentage'  => $quotationRequestData['notarialFeesPercentage'] ?? 6,
                    'property_value'            => $quotationRequestData['propertyValue'] ?? 0,
                    'remodeling_budget'         => $quotationRequestData['remodeling_budget'] ?? 0,
                    'sub_account'               => $quotationRequestData['subAccount'] ?? 0,
                    'scheme'                    => $quotationRequestData['scheme'] ?? "fijos", // 0 - Fijos | 1 - Crecientes
                    'state'                     => $quotationRequestData['propertyState'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json([
                'client' => $client,
                'quotation' => isset($newQuotation) ? $newQuotation : null
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
                'message' => 'Ocurrió un error inesperado.',
                'errors' => [
                    'client' => 'Ocurrió un error al actualizar el cliente.'
                ],
            ], 500);
        }
    }

    public function storeWithQuotation(StoreClientWithQuotationRequest $request)
    {

        DB::beginTransaction();

        try {
            $clientWithQuotationRequest = $request->validated();

            $clientRequestData = $clientWithQuotationRequest['client'];
            $quotationRequestData = $clientWithQuotationRequest['quotation'] ?? null;

            /** @var \App\Models\User $authUser **/
            $authUser = Auth::user();
            if (!$authUser) {
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => [
                        'auth' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            $authUserId = $authUser->id;

            $client = Client::create([
                'user_id' => $authUserId,
                'name' => $clientRequestData['name'],
                'email' => $clientRequestData['email'],
                'password' => Hash::make('@')
            ]);

            if (!$client) {
                return response()->json([
                    'message' => 'No se pudo crear el cliente.',
                    'errors' => [
                        'client' => 'No se pudo crear el cliente.'
                    ]
                ], 400);
            }

            $client->profile()->create([
                'last_name'         => $clientRequestData['lastName'] ?? "",
                'middle_name'       => $clientRequestData['middleName'] ?? "",
                'rfc'               => isset($clientRequestData['rfc']) ? Str::upper($clientRequestData['rfc']) : null,
                'phone_number'      => $clientRequestData['phoneNumber'] ?? null,
                'street'            => $clientRequestData['street'] ?? null,
                'house_number'      => $clientRequestData['house_number'] ?? null,
                'neighborhood'      => $clientRequestData['neighborhood'] ?? null,
                'municipality'      => $clientRequestData['municipality'] ?? null,
                'state'             => $clientRequestData['state'] ?? null,
                'postal_code'       => $clientRequestData['postal_code'] ?? null,
                'country'           => $clientRequestData['country'] ?? null,
                'birth_date'        => $clientRequestData['birthDate'] ?? null,
                'monthly_income'    => $clientRequestData['monthlyIncome'] ?? null,
                'additional_income' => $clientRequestData['additionalIncome'] ?? null,
            ]);

            if (isset($clientStoreRequest['attachment_id'])) {
                $client->profile->profileImage()->create([
                    'attachment_id' => $clientRequestData['attachment_id']
                ]);
            }

            // $permission = Permission::where('name', 'collaborator_inmobiliaria')
            //                 ->where('guard_name', 'web');

            if (
                // ($authUser->hasPermissionTo('collaborator_inmobiliaria') ||
                //     $authUser->hasRole('admin')
                // ) && 
                $request->has('quotation')
            ) {
                $newQuotation = $client->quotations()->create([
                    'additional_income'         => $clientRequestData['additionalIncome'] ?? 0,
                    'monthly_income'            => $clientRequestData['monthlyIncome'] ?? 0,
                    'additional_property_value' => $quotationRequestData['additionalPropertyValue'] ?? 0,
                    'construction_area'         => $quotationRequestData['constructionArea'] ?? 0,
                    'credit_import'             => $quotationRequestData['creditImport'] ?? 0,
                    'credit_type'               => $quotationRequestData['creditType'] ?? 0,
                    'current_debt'              => $quotationRequestData['currentDebt'] ?? 0,
                    'down_payment'              => $quotationRequestData['downPayment'] ?? 0,
                    'infonavit_credit'          => $quotationRequestData['infonavitCredit'] ?? 0,
                    'land_area'                 => $quotationRequestData['landArea'] ?? 0,
                    'loan_amount'               => $quotationRequestData['loanAmount'] ?? 0,
                    'loan_term'                 => $quotationRequestData['loanTerm'] ?? 0,
                    'project_value'             => $quotationRequestData['projectValue'] ?? 0,
                    'notarial_fees_percentage'  => $quotationRequestData['notarialFeesPercentage'] ?? 6,
                    'property_value'            => $quotationRequestData['propertyValue'] ?? 0,
                    'remodeling_budget'         => $quotationRequestData['remodeling_budget'] ?? 0,
                    'sub_account'               => $quotationRequestData['subAccount'] ?? 0,
                    'scheme'                    => $quotationRequestData['scheme'] ?? "fijos", // 0 - Fijos | 1 - Crecientes
                    'state'                     => $quotationRequestData['propertyState'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json([
                'client' => $client,
                'quotation' => isset($newQuotation) ? $newQuotation : null
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
                'message' => 'Ocurrió un error inesperado',
                'errors' => [
                    'client' => 'Ocurrió un error al crear el cliente.'
                ],
                'exception' => $th->getMessage()
            ], 500);
        }
    }

    /**
     *  Create a new Client
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreClientRequest $request)
    {
        $errors = [];

        DB::beginTransaction();
        try {
            $clientStoreRequest = $request->validated();

            /** @var \App\Models\User $authUser **/
            $authUser = Auth::user();
            if (!$authUser) {
                $errors['auth'] = 'No se encontró el usuario autenticado.';
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => $errors
                ], 400);
            }

            $authUserId = $authUser->id;
            $authModelType = $authUser->getMorphClass();

            $client = Client::create([
                'user_id' => ($authModelType == 'App\Models\Admin') ? null : $authUserId,
                'name' => $clientStoreRequest['name'],
                'email' => $clientStoreRequest['email'],
                'password' => Hash::make('@')
            ]);

            if (!$client) {
                $errors['client'] = 'No se pudo crear el cliente.';
                return response()->json([
                    'message' => 'No se pudo crear el cliente.',
                    'errors' => $errors
                ], 400);
            }

            $client->profile()->create([
                'last_name'         => $clientStoreRequest['lastName'] ?? "",
                'rfc'               => $clientStoreRequest['rfc'] ?? null,
                'phone_number'      => $clientStoreRequest['phoneNumber'] ?? null,
                'address'           => $clientStoreRequest['address'] ?? null,
                'country'           => $clientStoreRequest['country'] ?? null,
                'city'              => $clientStoreRequest['city'] ?? null,
                'postal_code'       => $clientStoreRequest['postalCode'] ?? null,
                'birth_date'        => $clientStoreRequest['birthCate'] ?? null,
                'monthly_income'    => $clientStoreRequest['monthlyIncome'] ?? null,
                'additional_income' => $clientStoreRequest['additionalIncome'] ?? null,
            ]);

            if (isset($clientStoreRequest['attachment_id'])) {
                $client->profile->profileImage()->create([
                    'attachment_id' => $clientStoreRequest['attachment_id']
                ]);
            }

            DB::commit();
            return response()->json(['client' => $client], 200);
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
                'message' => 'Ocurrió un error al crear el cliente.',
                'errors' => $errors,
                'exception' => $th->getMessage()
            ], 500);
        }
    }

    public function adminStore(Request $request)
    {
        $errors = [];
        DB::beginTransaction();
        try {
            $clientStoreRequest = $request->validate([
                'name' => ['required'],
                'email' => ['required', 'email', 'unique:clients'],
                'lastName' => ['required'],
                'middleName' => ['required'],
                'phoneNumber' => ['required'],
                'street' => ['required'],
                'houseNumber' => ['required'],
                'neighborhood' => ['required'],
                'municipality' => ['required'],
                'state' => ['required'],
                'postalCode' => ['required'],
                'country' => ['string'],
                'birthDate' => ['required'],
                'rfc' => ['required'],
                'monthlyIncome' => ['required'],
                'additionalIncome' => ['required'],
            ], [
                'lastName.required' => 'El apellido es requerido.',
                'middleName.required' => 'El segundo apellido es requerido.',
                'phoneNumber.required' => 'El número de teléfono es requerido.',
                'street.required' => 'La calle es requerida.',
                'houseNumber.required' => 'El número de casa es requerido.',
                'neighborhood.required' => 'La colonia es requerida.',
                'municipality.required' => 'El municipio es requerido.',
                'state.required' => 'El estado es requerido.',
                'postalCode.required' => 'El código postal es requerido.',
                'birthDate.required' => 'La fecha de nacimiento es requerida.',
                'rfc.required' => 'El RFC es requerido.',
                'monthlyIncome.required' => 'Los ingresos mensuales son requeridos.',
                'additionalIncome.required' => 'Los ingresos adicionales son requeridos.',
            ]);

            /** @var \App\Models\User $authUser **/
            $authUser = Auth::user();
            if (!$authUser) {
                $errors['auth'] = 'No se encontró el usuario autenticado.';
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => $errors
                ], 400);
            }

            $authUserId = $authUser->id;
            $authModelType = $authUser->getMorphClass();

            $client = Client::create([
                'user_id' => null,
                'name' => $clientStoreRequest['name'],
                'email' => $clientStoreRequest['email'],
                'password' => Hash::make('141621Dj')
            ]);

            if (!$client) {
                $errors['client'] = 'No se pudo crear el cliente.';
                return response()->json([
                    'message' => 'No se pudo crear el cliente.',
                    'errors' => $errors
                ], 400);
            }

            $client->profile()->create([
                'last_name'         => $clientStoreRequest['lastName'] ?? "",
                'middle_name'       => $clientStoreRequest['middleName'] ?? "",
                'phone_number'      => $clientStoreRequest['phoneNumber'] ?? null,
                'street'            => $clientStoreRequest['street'] ?? null,
                'house_number'      => $clientStoreRequest['houseNumber'] ?? null,
                'neighborhood'      => $clientStoreRequest['neighborhood'] ?? null,
                'municipality'      => $clientStoreRequest['municipality'] ?? null,
                'state'             => $clientStoreRequest['state'] ?? null,
                'postal_code'       => $clientStoreRequest['postalCode'] ?? null,
                'country'           => $clientStoreRequest['country'] ?? 'México',
                'birth_date'        => $clientStoreRequest['birthDate'] ?? null,
                'rfc'               => $clientStoreRequest['rfc'] ?? null,
                'monthly_income'    => $clientStoreRequest['monthlyIncome'] ?? null,
                'additional_income' => $clientStoreRequest['additionalIncome'] ?? null,
            ]);

            if (isset($clientStoreRequest['attachment_id'])) {
                $client->profile->profileImage()->create([
                    'attachment_id' => $clientStoreRequest['attachment_id']
                ]);
            }

            DB::commit();
            return response()->json(['client' => $client], 200);
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
                'message' => 'Ocurrió un error al crear el cliente.',
                'errors' => $errors,
                'exception' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Función para obtener todos los clientes asociados al usuario autenticado.
     *
     * @param Request $request La solicitud HTTP recibida.
     * @return \Illuminate\Http\JsonResponse Una respuesta JSON que contiene la lista de clientes solicitada.
     */
    public function getAll(Request $request)
    {
        $limit = $request->get('limit', 25);
        $offset = $request->get('offset', 0);
        $searchTerm = $request->get('searchTerm', null);

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors' => []
            ], 400);
        }

        $organization = $authUser->my_organization;
        $isOwner = $organization && $organization->owner_id === $authUser->id;

        $clientIdCondition = $isOwner
            ? $organization->members->pluck('id')->toArray()
            : [$authUser->id];

        if ($authUser->hasRole('admin')) {
            $clientQuery = Client::with('user');
        } else {
            $clientQuery = Client::whereIn('user_id', $clientIdCondition)
                ->with('user');
        }

        $sitoiClients = [];
        if (empty($searchTerm)) {
            // Concatenar sitoi
            $url = env('SITOI_URL', 'http://localhost:80');
            $url = $url."/openapi/getByInmo.php";
            $response = Http::get($url, [
                "inmo" => $organization->id,
            ]);
            $holdMyClients = [];
            if (!$response->failed()) {
                $holdMyClients = $response->json();
                foreach ($holdMyClients as $key => $client) {
                    $holdMyClients[$key]['id'] = 'S'.$client['id'];

                    // Normalizar keys
                    $holdMyClients[$key]['full_name'] = $client['nombre'];
                    $holdMyClients[$key]['profile']['phone_number'] = $client['telefono'];
                    $holdMyClients[$key]['email'] = $client['correo'];
                    $holdMyClients[$key]['profile']['score'] = $client['score'];
                    $holdMyClients[$key]['user']['full_name'] = $client['experto'];
                }
            }

            $clients = $clientQuery->skip($offset)->take($limit)->get();
            $total = $clientQuery->count();

            $mergeClients = array_merge($clients->toArray(), $holdMyClients);
            return response()->json([
                'clients' => $mergeClients,
                'total' => $total,
                'inmoClients' => $clients,
                'sitoiClients' => $holdMyClients,
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

        $clientQuery = $clientQuery->where($searchQuery)
            ->orWhereHas('profile', $profileQuery);

        $clients = $clientQuery->skip($offset)->take($limit)->get();
        $total = $clientQuery->count() + 2;

        // Concatenar sitoi
        $url = "http://localhost:80/openapi/getByInmo.php?inmo=$organization";


        return response()->json([
            'clients' => $clients,
            'total' => $total,
            'url' => $url
        ], 200);
    }

    public function adminStoreQuotization(Request $request, $clientId)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors' => []
            ], 400);
        }

        $client = Client::where('id', $clientId)->first();

        if (!$client) {
            return response()->json([
                'message' => 'No se encontró el cliente.',
                'errors' => []
            ], 400);
        }

        DB::beginTransaction();
        try {
            $newQuotation = $client->quotations()->create([
                'additional_income'         => $client->profile()->additional_income ?? 0,
                'monthly_income'            => $client->profile()->monthly_income ?? 0,
                'property_value'            => $request['propertyValue'] ?? 0,
                'down_payment'              => $request['downPayment'] ?? 0,
                'credit_import'             => $request['creditImport'] ?? 0,
                'loan_amount'               => $request['creditImport'] ?? 0,
                'loan_term'                 => $request['loanTerm'] ?? 0,
                'credit_type'               => $request['creditType'] ?? 0,
                'scheme'                    => $request['scheme'] ?? "fijos", // 0 - Fijos | 1 - Crecientes
                'notarial_fees_percentage'  => $request['notarialFeesPercentage'] ?? 6,
                'infonavit_credit'          => $request['infonavitCredit'] ?? 0,
                'sub_account'               => $request['subAccount'] ?? 0,
                'additional_property_value' => $request['additionalPropertyValue'] ?? 0,
                'construction_area'         => $request['constructionArea'] ?? 0,
                'current_debt'              => $request['currentDebt'] ?? 0,
                'land_area'                 => $request['landArea'] ?? 0,
                'project_value'             => $request['projectValue'] ?? 0,
                'remodeling_budget'         => $request['remodeling_budget'] ?? 0,
                'state'                     => $request['propertyState'] ?? null,
            ]);

            DB::commit();
            return response()->json([
                'quotation' => $newQuotation,
                'client' => $client->id
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error inesperado.',
                'errors' => [
                    'client' => 'Ocurrió un error al actualizar el cliente.'
                ],
            ], 500);
        }
    }

    /**
     * Get By Id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getById(Request $request, $clientId)
    {

        if (!is_numeric($clientId)) {
            return response()->json([
                'message' => 'Invalid id'
            ], 400);
        }

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors' => []
            ], 400);
        }

        $client = Client::where('id', $clientId)
            ->with('user', 'quotations')
            ->with('user', 'loanApplications');

        if (!$authUser->hasRole('admin')) {
            if (!$authUser->is_organization_owner) {
                $client->where('user_id',  $authUser->id);
            }
        }

        $client = $client->first();

        if (!$client) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json(['client' => $client], 200);
    }
}
