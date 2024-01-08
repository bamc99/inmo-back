<?php

namespace App\Http\Requests\Email;

use Illuminate\Foundation\Http\FormRequest;

class ResendVerificationEmailRequest extends FormRequest
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
            'email' => ['required', 'email', 'exists:users,email', 'unverified_email'],
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
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo electrónico válida.',
            'email.exists' => 'No se encontró un usuario con esa dirección de correo electrónico.',
            'email.unverified_email' => 'La cuenta de correo electrónico ya ha sido verificada.',
        ];
    }
}
