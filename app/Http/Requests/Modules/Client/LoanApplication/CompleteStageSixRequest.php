<?php

namespace App\Http\Requests\Modules\Client\LoanApplication;

use Illuminate\Foundation\Http\FormRequest;

class CompleteStageSixRequest extends FormRequest
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
            'loanApplicationId' => ['required', 'exists:loan_applications,id']
        ];
    }

    public function messages(): array {
        return [
            'loanApplicationId.required' => 'La solicitud de préstamo es requerida.',
            'loanApplicationId.exists'   => 'La solicitud de préstamo no existe.',
        ];
    }
}
