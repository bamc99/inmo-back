<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientWithQuotationRequest extends FormRequest
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
            'client.name'                       => ['required', 'string'],
            'client.lastName'                   => ['required', 'string'],
            'client.middleName'                 => ['required', 'string'],
            'client.email'                      => [
                'required',
                'email',
                Rule::unique('clients', 'email')->where(function ($query) {
                    return $query->where('email', request('client.email'));
                })
            ],
            'client.additionalIncome'           => ['numeric'],
            'client.attachmentId'               => ['numeric', 'exists:attachments,id'],
            'client.birthDate'                  => ['date', 'date_format:Y-m-d'],
            'client.monthlyIncome'              => ['numeric'],
            'client.phoneNumber'                => ['string'],
            'client.rfc'                        => ['string'],

            'client.street'                     => ['string'],
            'client.house_number'               => ['string'],
            'client.neighborhood'               => ['string'],
            'client.municipality'               => ['string'],
            'client.state'                      => ['string'],
            'client.postal_code'                => ['string'],
            'client.country'                    => ['string'],

            'quotation.additionalIncome'        => ['numeric'],
            'quotation.monthlyIncome'           => ['numeric'],
            'quotation.additionalPropertyValue' => ['numeric', 'min:0'],
            'quotation.constructionArea'        => ['numeric', 'min:0'],
            'quotation.creditImport'            => ['numeric', 'min:0'],
            'quotation.creditType'              => ['string'],
            'quotation.currentDebt'             => ['numeric', 'min:0'],
            'quotation.downPayment'             => ['numeric', 'min:0'],
            'quotation.infonavitCredit'         => ['numeric', 'min:0'],
            'quotation.landArea'                => ['numeric', 'min:0'],
            'quotation.loanAmount'              => ['numeric', 'min:0'],
            'quotation.loanTerm'                => ['numeric', 'min:0'],
            'quotation.notarialFeesPercentage'  => ['numeric', 'min:1', 'max:20'],
            'quotation.projectValue'            => ['numeric', 'min:0'],
            'quotation.propertyState'           => ['string'],
            'quotation.propertyValue'           => ['numeric', 'min:0'],
            'quotation.remodelingBudget'        => ['numeric', 'min:0'],
            'quotation.scheme'                  => ['string', 'in:fijos,crecientes'],
            'quotation.subAccount'              => ['numeric', 'min:0'],
        ];
    }

    public function messages() {
        return [
            'client.name.required'                       => 'El nombre del cliente es obligatorio.',
            'client.name.string'                         => 'El nombre del cliente debe ser una cadena de caracteres.',
            'client.lastName.required'                   => 'El apellido del cliente es obligatorio.',
            'client.lastName.string'                     => 'El apellido del cliente debe ser una cadena de caracteres.',
            'client.middleName.required'                 => 'El segundo nombre del cliente es obligatorio.',
            'client.middleName.string'                   => 'El segundo nombre del cliente debe ser una cadena de caracteres.',
            'client.email.required'                      => 'El correo electrónico del cliente es obligatorio.',
            'client.email.email'                         => 'El correo electrónico del cliente debe ser una dirección de correo electrónico válida.',
            'client.email.unique'                        => 'El correo electrónico del cliente ya existe en la base de datos.',
            'client.phoneNumber.string'                  => 'El número telefónico del cliente debe ser una cadena de caracteres.',
            // 'client.rfc.required'                        => 'El RFC del cliente es obligatorio.',
            'client.rfc.string'                          => 'El RFC del cliente debe ser una cadena de caracteres.',
            'client.birthDate.date'                      => 'La fecha de nacimiento del cliente debe ser una fecha válida.',
            'client.birthDate.date_format'               => 'La fecha de nacimiento del cliente debe tener el formato Y-m-d.',

            'client.street.string'                       => 'La calle del cliente debe ser una cadena de caracteres.',
            'client.house_number.string'                 => 'El número de casa del cliente debe ser una cadena de caracteres.',
            'client.neighborhood.string'                 => 'La colonia del cliente debe ser una cadena de caracteres.',
            'client.municipality.string'                 => 'El municipio del cliente debe ser una cadena de caracteres.',
            'client.state.string'                        => 'El estado del cliente debe ser una cadena de caracteres.',
            'client.postal_code.string'                  => 'El código postal del cliente debe ser una cadena de caracteres.',
            'client.country.string'                      => 'El país del cliente debe ser una cadena de caracteres.',

            'quotation.additionalPropertyValue.min'      => 'El valor adicional de la propiedad debe ser mayor o igual a cero.',
            'quotation.additionalIncome.numeric'         => 'El ingreso adicional de la cotización debe ser un valor numérico.',
            'quotation.monthlyIncome.numeric'            => 'El ingreso mensual de la cotización debe ser un valor numérico.',
            'quotation.additionalPropertyValue.numeric'  => 'El valor adicional de la propiedad debe ser un valor numérico.',
            'quotation.additionalPropertyValue.required' => 'El valor adicional de la propiedad es obligatorio.',
            'quotation.constructionArea.min'             => 'El área de construcción debe ser mayor o igual a cero.',
            'quotation.constructionArea.numeric'         => 'El área de construcción debe ser un valor numérico.',
            'quotation.constructionArea.required'        => 'El área de construcción es obligatoria.',
            'quotation.creditImport.min'                 => 'El importe del crédito debe ser mayor o igual a cero.',
            'quotation.creditImport.numeric'             => 'El importe del crédito debe ser un valor numérico.',
            'quotation.creditImport.required'            => 'El importe del crédito es obligatorio.',
            'quotation.creditType.required'              => 'El tipo de crédito es obligatorio.',
            'quotation.currentDebt.min'                  => 'La deuda actual debe ser mayor o igual a cero.',
            'quotation.currentDebt.numeric'              => 'La deuda actual debe ser un valor numérico.',
            'quotation.currentDebt.required'             => 'La deuda actual es obligatoria.',
            'quotation.downPayment.min'                  => 'El enganche debe ser mayor o igual a cero.',
            'quotation.downPayment.numeric'              => 'El enganche debe ser un valor numérico.',
            'quotation.downPayment.required'             => 'El enganche es obligatorio.',
            'quotation.infonavitCredit.min'              => 'El crédito de Infonavit debe ser mayor o igual a cero.',
            'quotation.infonavitCredit.numeric'          => 'El crédito de Infonavit debe ser un valor numérico.',
            'quotation.infonavitCredit.required'         => 'El crédito de Infonavit es obligatorio.',
            'quotation.landArea.min'                     => 'El área del terreno debe ser mayor o igual a cero.',
            'quotation.landArea.numeric'                 => 'El área del terreno debe ser un valor numérico.',
            'quotation.landArea.required'                => 'El área del terreno es obligatoria.',
            'quotation.loanAmount.min'                   => 'La cantidad del préstamo debe ser mayor o igual a cero.',
            'quotation.loanAmount.numeric'               => 'La cantidad del préstamo debe ser un valor numérico.',
            'quotation.loanAmount.required'              => 'La cantidad del préstamo es obligatoria.',
            'quotation.loanTerm.min'                     => 'El plazo del préstamo debe ser mayor o igual a cero.',
            'quotation.loanTerm.numeric'                 => 'El plazo del préstamo debe ser un valor numérico.',
            'quotation.loanTerm.required'                => 'El plazo del préstamo es obligatorio.',
            'quotation.notarialFeesPercentage.max'       => 'El porcentaje de gastos notariales debe ser menor o igual a 20.',
            'quotation.notarialFeesPercentage.min'       => 'El porcentaje de gastos notariales debe ser mayor o igual a uno.',
            'quotation.notarialFeesPercentage.numeric'   => 'El porcentaje de gastos notariales debe ser un valor numérico.',
            'quotation.notarialFeesPercentage.required'  => 'El porcentaje de gastos notariales es obligatorio.',
            'quotation.projectValue.min'                 => 'El valor del proyecto debe ser mayor o igual a cero.',
            'quotation.projectValue.numeric'             => 'El valor del proyecto debe ser un valor numérico.',
            'quotation.projectValue.required'            => 'El valor del proyecto es obligatorio.',
            'quotation.propertyState.required'           => 'El estado de la propiedad es obligatorio.',
            'quotation.propertyState.string'             => 'El estado de la propiedad debe ser una cadena de texto.',
            'quotation.propertyValue.min'                => 'El valor de la propiedad debe ser mayor o igual a cero.',
            'quotation.propertyValue.numeric'            => 'El valor de la propiedad debe ser un valor numérico.',
            'quotation.propertyValue.required'           => 'El valor de la propiedad es obligatorio.',
            'quotation.remodelingBudget.min'             => 'El presupuesto de remodelación debe ser mayor o igual a cero.',
            'quotation.remodelingBudget.numeric'         => 'El presupuesto de remodelación debe ser un valor numérico.',
            'quotation.remodelingBudget.required'        => 'El presupuesto de remodelación es obligatorio.',
            'quotation.scheme.in'                        => 'El esquema debe ser fijos o crecientes.',
            'quotation.scheme.required'                  => 'El esquema es obligatorio.',
            'quotation.scheme.string'                    => 'El esquema debe ser una cadena de texto.',
            'quotation.subAccount.min'                   => 'La subcuenta debe ser mayor o igual a cero.',
            'quotation.subAccount.numeric'               => 'La subcuenta debe ser un valor numérico.',
            'quotation.subAccount.required'              => 'La subcuenta es obligatoria.',
        ];
    }

}
