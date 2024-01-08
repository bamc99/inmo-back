<?php

namespace App\Http\Controllers\Modules\Admin\LoanApplication;

use App\Http\Controllers\Controller;
use App\Models\AdditionalApplicationStage;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CobranzaController extends Controller
{
    public function getClientsFilesStageTen() {
        $loanApplicationsQuery = LoanApplication::whereBetween('current_stage', [4, 10])
            ->with(['loanApplicationAttachments.attachment', 'client']);

        $total = $loanApplicationsQuery->count();
        $loanApplications = $loanApplicationsQuery->get();

        return response()->json([
            'loanApplications' => $loanApplications,
            'total' => $total
        ]);
    }

    public function getClientsFilesStageNine() {
        $loanApplicationsQuery = LoanApplication::where('current_stage', 9)
            ->with(['loanApplicationAttachments.attachment', 'client']);

        $total = $loanApplicationsQuery->count();
        $loanApplications = $loanApplicationsQuery->get();

        return response()->json([
            'loanApplications' => $loanApplications,
            'total' => $total
        ]);
    }
    public function uploadAttachmentsCobranza( Request $request, $loanApplicationId  ) {

        /** @var Client $authUser */
        $authUser = Auth::user();
        $loanApplicationId = $request->get('loanApplicationId');

        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('id', $loanApplicationId)
            ->first();

        if(!$loanApplication) return $this->invalidLoanApplicationResponse();

        DB::beginTransaction();
        try {

            $generalFiles = $request->file('generalFiles');
            $cobranzaFiles = $request->file('cobranzaFiles');

            if($generalFiles) {
                foreach($generalFiles as $generalFile) {
                    $attachment = $this->createAttachmentFromUploadedFile($generalFile);
                    $loanApplication->loanApplicationAttachments()->create([
                        'attachment_id' => $attachment->id,
                        'type' => 'general'
                    ]);
                }
            }

            if($cobranzaFiles) {
                foreach($cobranzaFiles as $cobranzaFile) {
                    $attachment = $this->createAttachmentFromUploadedFile($cobranzaFile);
                    $loanApplication->loanApplicationAttachments()->create([
                        'attachment_id' => $attachment->id,
                        'type' => 'cobranza'
                    ]);
                }
            }

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

    private function createAttachmentFromUploadedFile(UploadedFile $file): Attachment {
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        $size = $file->getSize();
        $hash = hash_file('md5', $file->getRealPath());

        $name = Str::uuid();
        $pathToSave = "admin/loan-applications/";
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

    public function probablyPayDate(Request $request, $loanApplicationId) {

        $data = $request->validate([
            'probablePayDate' => 'required|date',
            'folio' => 'required'
        ], [
            'probablePayDate.required' => 'La fecha de pago probable es requerida.',
            'probablePayDate.date' => 'La fecha de pago probable debe ser una fecha válida.',
            'folio.required' => 'El folio es requerido.'
        ]);

        /** @var Client $authUser */
        $authUser = Auth::user();
        if (!$authUser) return $this->unauthenticatedResponse();

        $loanApplication = LoanApplication::where('id', $loanApplicationId)
            ->first();

        if(!$loanApplication) return $this->invalidLoanApplicationResponse();

        $stageTen = AdditionalApplicationStage::where('loan_application_id', $loanApplication->id)
            ->where('stage', 10)
            ->first();

        if($stageTen) {
            return response()->json([
                'message' => 'La solicitud ya cuenta con una fecha de pago probable.',
                'errors' => [
                    'probablePayDate' => 'La solicitud ya cuenta con una fecha de pago probable.'
                ]
            ], 400);
        }

        DB::beginTransaction();
        try {

            AdditionalApplicationStage::create([
                'loan_application_id' => $loanApplication->id,
                'stage' => 10,
                'data' => json_encode([
                    'probable_pay_date' => $data['probablePayDate'],
                    'folio' => $data['folio'],
                    'etapa' => 10,
                    'status' => 'activo'
                ])
            ]);

            $loanApplication->current_stage = 10;
            $loanApplication->save();

            DB::commit();
            return response()->json([
                'message' => 'Fecha de pago probable actualizada correctamente.',
                'loanApplication' => $loanApplication
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error al actualizar la fecha de pago probable.',
                'errors'  => [
                    'probablePayDate' => 'Hubo un error al actualizar la fecha de pago probable.'
                ]
            ], 500);
        }

    }

    private function unauthenticatedResponse() {
        return response()->json([
            'message' => 'No se encontró el usuario autenticado.',
            'errors' => [
                'auth_user' => 'No se encontró el usuario autenticado.'
            ]
        ], 400);
    }

    private function invalidLoanApplicationResponse() {
        return response()->json([
            'message' => 'La solicitud no pertenece al cliente.',
            'errors' => [
                'loanApplication' => 'La solicitud no pertenece al cliente.'
            ]
        ], 400);
    }
}
