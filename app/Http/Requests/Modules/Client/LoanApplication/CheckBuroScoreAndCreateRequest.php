<?php

namespace App\Http\Requests\Modules\Client\LoanApplication;

use Illuminate\Foundation\Http\FormRequest;

class CheckBuroScoreAndCreateRequest extends FormRequest
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
            'name'         => ['required', 'string'],
            'lastName'     => ['required', 'string'],
            'middleName'   => ['required', 'string'],
            'birthDate'    => ['required', 'date', 'date_format:Y-m-d',"min_age:21"],
            'rfc'          => ['required', 'string'],
            'street'       => ['required', 'string'],
            'houseNumber'  => ['required', 'string'],
            'neighborhood' => ['required', 'string'],
            'state'        => ['required', 'string'],
            'municipality' => ['required', 'string','required_with:state'],
            'postalCode'   => ['required', 'string'],
            'country'      => ['required', 'string'],
            'file'         => ['required', 'file','image','max:2048'],
            'quotationId'  => ['required', 'integer', 'exists:quotations,id'],
            'bankSlug'     => ['required', 'string', 'exists:banks,slug'],
        ];
    }

    public function messages(){
        return [
            'name.required'              => 'El nombre es obligatorio.',
            'name.string'                => 'El nombre debe ser una cadena de texto.',
            'lastName.required'          => 'El apellido es obligatorio.',
            'lastName.string'            => 'El apellido debe ser una cadena de texto.',
            'middleName.required'        => 'El segundo nombre es obligatorio.',
            'middleName.string'          => 'El segundo nombre debe ser una cadena de texto.',
            'birthDate.required'         => 'La fecha de nacimiento es obligatoria.',
            'birthDate.date'             => 'La fecha de nacimiento debe ser una fecha válida.',
            'birthDate.date_format'      => 'La fecha de nacimiento debe estar en el formato YYYY-MM-DD.',
            'birthDate.min_age'          => 'Debes tener al menos 21 años.',
            'rfc.required'               => 'El RFC es obligatorio.',
            'rfc.string'                 => 'El RFC debe ser una cadena de texto.',
            'street.required'            => 'La calle es obligatoria.',
            'street.string'              => 'La calle debe ser una cadena de texto.',
            'houseNumber.required'       => 'El número de casa es obligatorio.',
            'houseNumber.string'         => 'El número de casa debe ser una cadena de texto.',
            'neighborhood.required'      => 'El barrio es obligatorio.',
            'neighborhood.string'        => 'El barrio debe ser una cadena de texto.',
            'state.required'             => 'El estado es obligatorio.',
            'state.string'               => 'El estado debe ser una cadena de texto.',
            'municipality.required'      => 'El municipio es obligatorio.',
            'municipality.string'        => 'El municipio debe ser una cadena de texto.',
            'municipality.required_with' => 'El municipio es obligatorio cuando el estado también está presente.',
            'postalCode.required'        => 'El código postal es obligatorio.',
            'postalCode.string'          => 'El código postal debe ser una cadena de texto.',
            'country.required'           => 'El país es obligatorio.',
            'country.string'             => 'El país debe ser una cadena de texto.',
            'file.required'              => 'El archivo es obligatorio.',
            'file.file'                  => 'Debe proporcionar un archivo válido.',
            'file.image'                 => 'El archivo debe ser una imagen.',
            'file.max'                   => 'La imagen no debe exceder de 2048KB.',
        ];
    }

}
