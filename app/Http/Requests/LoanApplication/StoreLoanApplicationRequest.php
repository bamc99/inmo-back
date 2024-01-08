<?php

namespace App\Http\Requests\LoanApplication;

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
            'client_id' => 'required|exists:clients,id',
            'quotation_id' => 'required|exists:quotations,id',
            'bank_slug' => 'required|exists:banks,slug',
            'amortization_data' => 'json',
        ];
    }
}
