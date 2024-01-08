<?php

namespace App\Http\Requests\Modules\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name'                       => ['string'],
            'lastName'                   => ['string'],
            'email'                      => ['email', 'required', 'unique:clients,email,' . auth()->id()],
            'birthDate'                  => ['date', 'date_format:Y-m-d'],
            'phoneNumber'                => ['string'],
            'roles'                      => ['required', 'in:admin,experto'],
        ];
    }

    public function messages() {
        return [
            'name.required'              => 'El nombre del cliente es obligatorio.',
            'lastName.required'          => 'El apellido del cliente es obligatorio.',
            'birthDate.date_format'      => 'La fecha de nacimiento del cliente debe tener el formato Y-m-d.',
            'birthDate.date'             => 'La fecha de nacimiento del cliente debe ser una fecha válida.',
            'birthDate.min_age'          => 'La edad del cliente debe ser mayor o igual a 21 años.',
            'email.email'                => 'El correo electrónico del cliente no es válido.',
            'email.required'             => 'El correo electrónico del cliente es obligatorio.',
            'email.unique'               => 'Este correo electrónico ya está registrado.',
            'phoneNumber.string'         => 'El teléfono del cliente debe ser una cadena de texto.',
            'roles.required'             => 'El rol del cliente es obligatorio.',
            'roles.in'                   => 'El rol del cliente debe ser admin o experto.',
        ];
    }
}
