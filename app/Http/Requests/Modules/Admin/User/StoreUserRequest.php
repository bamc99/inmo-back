<?php

namespace App\Http\Requests\Modules\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'phoneNumber' => ['string'],
            'email' => ['required', 'email'],
            'birthDate' => ['required', 'date'],
            'password' => [
                'required',
                'string',
                'min:6',
                Password::min(6),
                'confirmed'
            ],
            'roles' => ['required', 'in:admin,experto'],
        ];
    }

    public function messages() {
        return [
            'name' => 'El nombre es requerido',
            'lastName' => 'El apellido es requerido',
            'birthDate' => 'La fecha de nacimiento es requerida',
            'phoneNumber' => 'El número de teléfono es requerido',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email no es válido',
            'email.unique' => 'El usuario ya está registrado',
            'password' => 'La contraseña debe tener al menos 6 caracteres, una letra, un número y un símbolo',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'roles.required' => 'El rol es requerido',
            'roles.in' => 'El rol no es válido',
        ];
    }
}
