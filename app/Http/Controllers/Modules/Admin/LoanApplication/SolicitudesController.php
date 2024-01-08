<?php

namespace App\Http\Controllers\Modules\Admin\LoanApplication;

use App\Http\Controllers\Controller;
use App\Models\AdditionalApplicationStage;
use App\Models\Attachment;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudesController extends Controller
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

        $loanApplication = LoanApplication::where('id', $id)
            ->with('quotation')
            ->with('bank')
            ->with('startApplication')
            ->with('endApplication')
            ->with('startAttached')
            ->with('endAttached')
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

    public function uploadSolicitudAndAnexo(Request $request, $loanApplicationId)
    {
        $authUser = Auth::user();

        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('id', $loanApplicationId)
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
            $startApplicationFile = $request->file('solicitudFile'); // Solicitud llenada
            $startAttachedFile = $request->file('anexoFile'); // Anexo Firmado

            $startApplicationAttachment = $this->createAttachmentFromUploadedFile($startApplicationFile);
            $startAttachedAttachment = $this->createAttachmentFromUploadedFile($startAttachedFile);

            $loanApplication->start_application_id = $startApplicationAttachment->id;
            $loanApplication->start_attached_id = $startAttachedAttachment->id;
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

    public function getStageInform(Request $request, $loanApplicationId)
    {
        $authUser = Auth::user();
        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('id', $loanApplicationId)
            ->first();

        if (!$loanApplication) return $this->invalidLoanApplicationResponse();

        $additionalStage = AdditionalApplicationStage::where('loan_application_id', $loanApplication->id)
            ->where('stage', $loanApplication->current_stage)
            ->first();

        if (!$additionalStage) {
            return response()->json([
                'message' => 'No se encontró el informe de la etapa.',
                'errors'  => [
                    'stage' => 'No se encontró el informe de la etapa.'
                ],
                'status' => 'not_exists'
            ], 200);
        }

        return response()->json([
            'additionalStage' => $additionalStage,
            'status' => 'exists'
        ], 200);
    }

    public function stageInform(Request $request, $loanApplicationId)
    {
        try {
            $data = $request->validate([
                'actualStage' => ['required'],
                'action' => ['required']
            ], [
                'actualStage.required' => 'El campo actualStage es requerido.',
                'action.required' => 'El campo action es requerido.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Hubo un error al informar la solicitud.',
                'errors' => $th->getMessage()
            ], 500);
        }
        $authUser = Auth::user();
        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('id', $loanApplicationId)
            ->first();

        if (!$loanApplication) return $this->invalidLoanApplicationResponse();

        if ($loanApplication->current_stage !== $data['actualStage']) {
            return response()->json([
                'message' => 'La etapa no coincide con el informe.',
                'errors'  => [
                    'stage' => 'La etapa no coincide con el informe.'
                ]
            ], 400);
        }

        $additionalStage = AdditionalApplicationStage::where('loan_application_id', $loanApplication->id)
            ->where('stage', $data['actualStage'])
            ->first();
        
        if (!$additionalStage) {
            // No existe en additionalStage
            DB::beginTransaction();
            try {
                $additionalStage = AdditionalApplicationStage::create([
                    'loan_application_id' => $loanApplication->id,
                    'stage' => $data['actualStage'],
                    'data' => json_encode( $request->all() ),
                ]);
                DB::commit();
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
                DB::rollBack();
                return response()->json([
                    'message' => 'Hubo un error al informar la solicitud.',
                    'errors'  => [
                        'file' => 'Hubo un error al informar la solicitud.'
                    ]
                ], 500);
            }
        }else{
            // Ya existe en additionalStage
            DB::beginTransaction();
            try {
                $additionalStage->data = json_encode( $request->all() );
                $additionalStage->save();
                DB::commit();
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
                DB::rollBack();
                return response()->json([
                    'message' => 'Hubo un error al informar la solicitud.',
                    'errors'  => [
                        'file' => 'Hubo un error al informar la solicitud.'
                    ]
                ], 500);
            }
        }

        if ($data['action'] == 'terminar') {

            DB::beginTransaction();
            try {
                if ($data['actualStage'] == 6) {
                    // No Actualizar
                }else if($data['actualStage'] == 9){
                    $loanApplication->signature_date = $request->fechaFirma;
                }else{
                    $loanApplication->current_stage = $data['actualStage'] + 1;
                }
                $loanApplication->save();
                DB::commit();
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
                DB::rollBack();
                return response()->json([
                    'message' => 'Hubo un error al informar la solicitud.',
                    'errors'  => [
                        'file' => 'Hubo un error al informar la solicitud.'
                    ]
                ], 500);
            }

        }

        return response()->json([
            'message' => 'Solicitud informada correctamente',
            'loanApplication' => $loanApplication,
        ], 201);


    }

    public function confirmSolicitudAndAnexo(Request $request, $loanApplicationId)
    {

        try {
            $data = $request->validate([
                'confirmFiles' => ['required', 'boolean'],
            ], [
                'confirmFiles.required' => 'El campo confirmFiles es requerido.',
                'confirmFiles.boolean' => 'El campo confirmFiles debe ser booleano.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Hubo un error al confirmar los archivos.',
                'errors' => $th->getMessage()
            ], 500);
        }

        $authUser = Auth::user();
        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('id', $loanApplicationId)
            ->first();

        if (!$loanApplication) return $this->invalidLoanApplicationResponse();

        if ($loanApplication->current_stage !== 2) {
            return response()->json([
                'message' => 'Los archivos solo se pueden confirmar en la etapa 2.',
                'errors'  => [
                    'stage' => 'Los archivos solo se pueden confirmar en la etapa 2.'
                ]
            ], 400);
        }

        DB::beginTransaction();
        try {
            if ($data['confirmFiles']) {
                $loanApplication->current_stage = 3;
                $loanApplication->confirm_application = 1;
                $loanApplication->confirm_attached = 1;
                $loanApplication->save();
                DB::commit();
                return response()->json([
                    'message' => 'Archivos confirmados correctamente',
                    'loanApplication' => $loanApplication,
                ], 201);
            } else {
                $loanApplication->current_stage = 1;
                $loanApplication->start_application_id = null;
                $loanApplication->start_attached_id = null;
                $loanApplication->end_application_id = null;
                $loanApplication->end_attached_id = null;
                $loanApplication->save();
                DB::commit();
                return response()->json([
                    'message' => 'Archivos rechazados correctamente',
                    'loanApplication' => $loanApplication,
                ], 201);
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error al confirmar los archivos',
                'errors'  => [
                    'file' => 'Hubo un error al confirmar los archivos.'
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
        $pathToSave = "admin/solicitudes-anexos/";
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
            'message' => 'La solicitud parece no existir.',
            'errors' => [
                'loanApplication' => 'La solicitud parece no existir.'
            ]
        ], 400);
    }
}
