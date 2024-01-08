<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required'],
            'password' => ['required']
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo es requerido',
            'email.exists:admins,email' => 'El correo o la contraseña son incorrectos',
            'email.email' => 'El correo no es válido',
            'email.exists' => 'El correo o la contraseña son incorrectos',
            'password.required' => 'La contraseña es requerida',
        ];
    }
}
