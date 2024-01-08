<?php

namespace App\Http\Controllers\Modules\Client\LoanApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\LoanApplication\CompleteStageOneRequest;
use App\Http\Requests\Modules\Client\LoanApplication\CompleteStageSixRequest;
use App\Http\Requests\Modules\Client\LoanApplication\StoreLoanApplicationRequest;
use App\Models\AdditionalApplicationStage;
use App\Models\Attachment;
use App\Models\Bank;
use App\Models\Client;
use App\Models\LoanApplication;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LoanApplicationController extends Controller
{
    public function getLoanApplicationsById(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => [
                    'auth_user' => 'No se encontró el usuario autenticado.'
                ]
            ], 400);
        }

        $loanApplication = LoanApplication::where('client_id', $authUser->id)
            ->where('id', $id)
            ->with('quotation')
            ->with('bank')
            ->with('startApplication')
            ->with('startAttached')
            ->first();

        if (!$loanApplication) {
            return response()->json([
                'message' => 'No se encontró la solicitud de préstamo.',
                'errors'  => [
                    'loanApplication' => 'No se encontró la solicitud de préstamo.'
                ]
            ], 400);
        }

        if($loanApplication->current_stage === 6){
            $conditions = AdditionalApplicationStage::where('loan_application_id', $loanApplication->id)
                ->where('stage', 6)
                ->first();
            $conditions = $conditions->data ?? null;
            // $conditions = ['hola' => 'hola'];
            // array_merge($loanApplication, ['conditions' => $conditions]);
        }

        return response()->json([
            'loanApplication' => $loanApplication,
            'conditions' => $conditions ?? null
        ], 200);
    }

    public function getLoanApplicationsByIdAdmin(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => [
                    'auth_user' => 'No se encontró el usuario autenticado.'
                ]
            ], 400);
        }

        $loanApplication = LoanApplication::where('id', $id)
            ->with('quotation')
            ->with('bank')
            ->with('startApplication')
            ->with('startAttached')
            ->first();

        if (!$loanApplication) {
            return response()->json([
                'message' => 'No se encontró la solicitud de préstamo.',
                'errors'  => [
                    'loanApplication' => 'No se encontró la solicitud de préstamo.'
                ]
            ], 400);
        }

        return response()->json([
            'loanApplication' => $loanApplication
        ], 200);
    }

    public function getUserLoanApplications(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => [
                    'auth_user' => 'No se encontró el usuario autenticado.'
                ]
            ], 400);
        }

        $loanApplications = LoanApplication::where('client_id', $id)
            ->with('quotation')
            ->with('bank')
            ->get();

        return response()->json([
            'loanApplications' => $loanApplications
        ], 200);
    }
    public function getMyLoanApplications(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => [
                    'auth_user' => 'No se encontró el usuario autenticado.'
                ]
            ], 400);
        }

        $loanApplications = LoanApplication::where('client_id', $authUser->id)
            ->with('quotation')
            ->with('bank')
            ->get();

        return response()->json([
            'loanApplications' => $loanApplications
        ], 200);
    }

    public function store(StoreLoanApplicationRequest $request)
    {
        $storeLoanApplicationRequest = $request->validated();

        DB::beginTransaction();
        try {
            /** @var Client $authUser */
            $authUser = Auth::user();
            $quotationId = $storeLoanApplicationRequest['quotationId'];
            $bankSlug = $storeLoanApplicationRequest['bankSlug'];
            $loanApplicationOwner = 0;

            if (!$authUser) {
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors'  => [
                        'auth_user' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            if (!$authUser->hasRole('admin')) {
                $quotation = Quotation::where('client_id', $authUser->id)
                    ->where('id', $quotationId)
                    ->first();

                if (!$quotation) {
                    return response()->json([
                        'message' => 'La cotización no pertenece al cliente.',
                        'errors'  => [
                            'quotation' => 'La cotización no pertenece al cliente.'
                        ]
                    ], 400);
                }
                $loanApplicationOwner = $authUser->id;
            }else{
                $quotation = Quotation::where('id', $quotationId)
                    ->first();

                if (!$quotation) {
                    return response()->json([
                        'message' => 'No se encontró la cotización.',
                        'errors'  => [
                            'quotation' => 'No se encontró la cotización.'
                        ]
                    ], 400);
                }
                $loanApplicationOwner = $quotation->client_id;
            }

            $bank = Bank::where('slug', $bankSlug)->first();
            if (!$bank) {
                return response()->json([
                    'message' => 'No se encontró el banco.',
                    'errors'  => [
                        'bank' => 'No se encontró el banco.'
                    ]
                ], 400);
            }

            $simulation = $quotation->simulation($bankSlug);

            if (!$simulation) {
                return response()->json([
                    'message' => 'La cotización no tiene simulación para el banco seleccionado.',
                    'errors'  => []
                ], 400);
            }

            $loanApplication = LoanApplication::where('quotation_id', $quotationId)
                ->exists();

            if ($loanApplication) {
                return response()->json([
                    'message' => 'La cotización ya tiene una solicitud de préstamo.',
                    'errors'  => []
                ], 400);
            }

            $newLoanApplication = LoanApplication::create([
                'client_id'          => $loanApplicationOwner,
                'quotation_id'       => $quotationId,
                'bank_id'            => $bank->id,
                'amortization_data'  => json_encode($simulation['montos'], JSON_UNESCAPED_UNICODE),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Solicitud de préstamo creada correctamente',
                'loanApplication' => $newLoanApplication
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la solicitud de préstamo.',
                'errors'  => []
            ], 500);
        }
    }

    public function completeStageOne(CompleteStageOneRequest $request)
    {
        $data = $request->validated();

        /** @var Client $authUser */
        $authUser = Auth::user();
        $loanApplicationId = $data['loanApplicationId'];

        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('client_id', $authUser->id)
            ->where('id', $loanApplicationId)
            ->first();

        if (!$loanApplication) return $this->invalidLoanApplicationResponse();

        if ($loanApplication->current_stage !== 1) {
            return response()->json([
                'message' => 'Los archivos solo se pueden cargar en la etapa 1.',
                'errors'  => [
                    'stage' => 'Los archivos solo se pueden cargar en la etapa 1.'
                ]
            ], 400);
        }

        DB::beginTransaction();
        try {

            $endApplicationFile = $request->file('applicationFile'); // Solicitud Firmada
            $endAttachedFile = $request->file('attachedFile'); // Anexo Firmado

            $endApplicationAttachment = $this->createAttachmentFromUploadedFile($endApplicationFile);
            $endAttachedAttachment = $this->createAttachmentFromUploadedFile($endAttachedFile);

            $loanApplication->end_attached_id = $endAttachedAttachment->id;
            $loanApplication->end_application_id = $endApplicationAttachment->id;
            $loanApplication->current_stage = 2;
            $loanApplication->save();

            DB::commit();
            return response()->json([
                'message'         => 'Archivos cargados correctamente',
                'loanApplication' => $loanApplication,
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error al cargar los archivos',
                'errors'  => [
                    'file' => 'Hubo un error al cargar los archivos.'
                ]
            ], 500);
        }
    }

    public function completeStageSix(CompleteStageSixRequest $request)
    {
        $data = $request->validated();

        /** @var Client $authUser */
        $authUser = Auth::user();
        $loanApplicationId = $data['loanApplicationId'];

        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('client_id', $authUser->id)
            ->where('id', $loanApplicationId)
            ->first();

        if (!$loanApplication) return $this->invalidLoanApplicationResponse();

        if ($loanApplication->current_stage !== 6) {
            return response()->json([
                'message' => 'Para completar la etapa 6, la solicitud debe estar en la etapa 6.',
                'errors'  => [
                    'stage' => 'Para completar la etapa 6, la solicitud debe estar en la etapa 6.'
                ]
            ], 400);
        }

        if ($loanApplication->confirm_conditions) {
            return response()->json([
                'message' => 'Las condiciones ya fueron confirmadas.',
                'errors'  => [
                    'stage' => 'Las condiciones ya fueron confirmadas.'
                ]
            ], 400);
        }

        DB::beginTransaction();
        try {

            $loanApplication->confirm_conditions = true;
            $loanApplication->current_stage = 7;
            $loanApplication->save();

            DB::commit();
            return response()->json([
                'message'         => 'Condiciones confirmadas correctamente',
                'loanApplication' => $loanApplication,
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error al cargar los archivos',
                'errors'  => [
                    'file' => 'Hubo un error al cargar los archivos.'
                ]
            ], 500);
        }
    }

    private function createAttachmentFromUploadedFile(UploadedFile $file): Attachment
    {
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        $size = $file->getSize();
        $hash = hash_file('md5', $file->getRealPath());

        $name = Str::uuid();
        $pathToSave = "client/loan-applications/";
        $diskToSave = "public";

        $attachment = Attachment::create([
            'name' => $name,
            'original_name' => $originalName,
            'mime' => $mime,
            'extension' => $extension,
            'size' => $size,
            'sort' => 0,
            'path' => $pathToSave,
            'description' => null,
            'alt' => null,
            'hash' => $hash,
            'disk' => $diskToSave,
            'group' => null,
        ]);

        $attachment->uploadFile($file);

        return $attachment;
    }

    private function unauthenticatedResponse()
    {
        return response()->json([
            'message' => 'No se encontró el usuario autenticado.',
            'errors' => [
                'auth_user' => 'No se encontró el usuario autenticado.'
            ]
        ], 400);
    }

    private function invalidLoanApplicationResponse()
    {
        return response()->json([
            'message' => 'La solicitud no pertenece al cliente.',
            'errors' => [
                'loanApplication' => 'La solicitud no pertenece al cliente.'
            ]
        ], 400);
    }
}
