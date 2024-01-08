<?php

namespace App\Http\Requests\Modules\Client\LoanApplication;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
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
            'quotationId' => 'required|exists:quotations,id',
            'bankSlug' => 'required|exists:banks,slug',
        ];
    }

    public function messages(): array
    {
        return [
            'quotationId.required' => 'La cotización es requerida',
            'quotationId.exists' => 'La cotización no existe',
            'bankSlug.required' => 'El banco es requerido',
            'bankSlug.exists' => 'El banco no existe',
        ];
    }

}
