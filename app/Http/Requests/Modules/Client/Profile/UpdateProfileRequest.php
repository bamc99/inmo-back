<?php

namespace App\Http\Requests\Modules\Client\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateProfileRequest extends FormRequest
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
            'email'                      => ['email', 'required', 'unique:clients,email,' . auth()->id()],
            'additionalIncome'           => ['numeric'],
            'street'                     => ['string'],
            'attachment_id'              => ['numeric', 'exists:attachments,id'],
            'birthDate'                  => ['date', 'date_format:Y-m-d',"min_age:21"],
            'city'                       => ['string', 'nullable'],
            'country'                    => ['string', 'nullable'],
            'lastName'                   => ['string'],
            'middleName'                 => ['string'],
            'monthlyIncome'              => ['numeric', 'min:10000'],
            'name'                       => ['string'],
            'phoneNumber'                => ['string'],
            'postalCode'                 => ['string'],
            'rfc'                        => ['string'],
            'municipality'               => ['string','nullable','required_with:state'],
            'state'                      => ['string','nullable'],
            'houseNumber'                => ['string','nullable'],
            'neighborhood'               => ['string','nullable'],
        ];
    }

    public function messages() {
        return [
            'additionalIncome.numeric'   => 'El ingreso adicional del cliente debe ser un valor numérico.',
            'street.string'              => 'La calle del cliente debe ser una cadena de texto.',
            'attachment_id.exists'       => 'El id del adjunto del cliente no existe.',
            'attachment_id.numeric'      => 'El id del adjunto del cliente debe ser un valor numérico.',
            'birthDate.date_format'      => 'La fecha de nacimiento del cliente debe tener el formato Y-m-d.',
            'birthDate.date'             => 'La fecha de nacimiento del cliente debe ser una fecha válida.',
            'birthDate.min_age'          => 'La edad del cliente debe ser mayor o igual a 21 años.',
            'city.string'                => 'La ciudad del cliente debe ser una cadena de texto.',
            'country.string'             => 'El país del cliente debe ser una cadena de texto.',
            'email.email'                => 'El correo electrónico del cliente no es válido.',
            'email.required'             => 'El correo electrónico del cliente es obligatorio.',
            'email.unique'               => 'Este correo electrónico ya está registrado.',
            'lastName.required'          => 'El apellido del cliente es obligatorio.',
            'middleName.string'          => 'El segundo nombre del cliente debe ser una cadena de texto.',
            'monthlyIncome.min'          => 'El ingreso mensual de la cotización debe ser mayor o igual a cero.',
            'monthlyIncome.numeric'      => 'El ingreso mensual de la cotización debe ser un valor numérico.',
            'name.required'              => 'El nombre del cliente es obligatorio.',
            'phoneNumber.string'         => 'El teléfono del cliente debe ser una cadena de texto.',
            'postalCode.string'          => 'El código postal del cliente debe ser una cadena de texto.',
            'rfc.required'               => 'El RFC del cliente es obligatorio.',
            'rfc.string'                 => 'El RFC del cliente debe ser una cadena de texto.',
            'municipality.string'        => 'El municipio debe ser una cadena de texto.',
            'municipality.required_with' => 'El campo municipio es requerido cuando se selecciona un estado.',
            'state.string'               => 'El estado debe ser una cadena de texto.',
            'state.required_with'        => 'El campo estado es requerido cuando se selecciona un municipio.',
            'houseNumber.string'         => 'El número de casa debe ser una cadena de texto.',
            'neighborhood.string'        => 'La colonia debe ser una cadena de texto.',
        ];
    }
}
