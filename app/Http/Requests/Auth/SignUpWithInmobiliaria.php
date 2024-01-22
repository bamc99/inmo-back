<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpWithInmobiliaria extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'phoneNumber' => ['required','string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'terms' => ['required', 'boolean'],
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed'
            ],
            'organizationName' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'name' => 'El nombre es requerido',
            'phoneNumber' => 'El número de teléfono es requerido',
            'lastName' => 'El apellido es requerido',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email no es válido',
            'email.unique' => 'El usuario ya está registrado',
            'password' => 'La contraseña debe tener al menos 6 caracteres, una letra, un número y un símbolo',
            'password.confirmed' => 'La contraseña no coincide',
            'organizationName' => 'El nombre de la inmobiliaria es requerido',
        ];
    }
}
