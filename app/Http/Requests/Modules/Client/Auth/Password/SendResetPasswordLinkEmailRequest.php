<?php

namespace App\Http\Requests\Modules\Client\Auth\Password;

use Illuminate\Foundation\Http\FormRequest;

class SendResetPasswordLinkEmailRequest extends FormRequest
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
            'email' => ['required', 'email', 'exists:clients,email'],
        ];
    }

    /**
    * Get the validation messages that apply to the request.
    *
    * @return array<string, string>
    */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El correo no es válido',
            'email.exists' => 'El correo no existe'
        ];
    }
}
