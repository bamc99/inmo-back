<?php

namespace App\Http\Requests\Modules\Client\LoanApplication;

use Illuminate\Foundation\Http\FormRequest;

class CompleteStageOneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'applicationFile'   => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'attachedFile'      => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'loanApplicationId' => ['required', 'exists:loan_applications,id']
        ];
    }

    public function messages(): array {
        return [
            'applicationFile.required'   => 'La solicitud es requerida.',
            'applicationFile.file'       => 'La solicitud debe ser un archivo.',
            'applicationFile.mimes'      => 'La solicitud debe ser una imagen o un PDF.',
            'applicationFile.uploaded'   => 'La solicitud no se pudo subir.',
            'attachedFile.required'      => 'El anexo es requerido.',
            'attachedFile.file'          => 'El anexo debe ser un archivo.',
            'attachedFile.mimes'         => 'El anexo debe ser una imagen o un PDF.',
            'attachedFile.uploaded'      => 'El anexo no se pudo subir.',
            'loanApplicationId.required' => 'La solicitud de préstamo es requerida.',
            'loanApplicationId.exists'   => 'La solicitud de préstamo no existe.',
        ];
    }
}
