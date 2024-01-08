<?php

namespace App\Http\Requests\Modules\Client\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
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
            'additionalIncome'        => ['numeric'],
            'monthlyIncome'           => ['numeric'],
            'additionalPropertyValue' => ['numeric', 'min:0'],
            'constructionArea'        => ['numeric', 'min:0'],
            'creditImport'            => ['numeric', 'min:0'],
            'creditType'              => ['string'],
            'currentDebt'             => ['numeric', 'min:0'],
            'downPayment'             => ['numeric', 'min:0'],
            'infonavitCredit'         => ['numeric', 'min:0'],
            'landArea'                => ['numeric', 'min:0'],
            'loanAmount'              => ['numeric', 'min:0'],
            'loanTerm'                => ['numeric', 'min:0'],
            'notarialFeesPercentage'  => ['numeric', 'min:1', 'max:20'],
            'projectValue'            => ['numeric', 'min:0'],
            'propertyState'           => ['string'],
            'propertyValue'           => ['numeric', 'min:0'],
            'remodelingBudget'        => ['numeric', 'min:0'],
            'scheme'                  => ['string', 'in:fijos,crecientes'],
            'subAccount'              => ['numeric', 'min:0'],
        ];
    }

    public function messages() {
        return [
            'additionalPropertyValue.min'      => 'El valor adicional de la propiedad debe ser mayor o igual a cero.',
            'additionalIncome.numeric'         => 'El ingreso adicional de la cotización debe ser un valor numérico.',
            'monthlyIncome.numeric'            => 'El ingreso mensual de la cotización debe ser un valor numérico.',
            'additionalPropertyValue.numeric'  => 'El valor adicional de la propiedad debe ser un valor numérico.',
            'additionalPropertyValue.required' => 'El valor adicional de la propiedad es obligatorio.',
            'constructionArea.min'             => 'El área de construcción debe ser mayor o igual a cero.',
            'constructionArea.numeric'         => 'El área de construcción debe ser un valor numérico.',
            'constructionArea.required'        => 'El área de construcción es obligatoria.',
            'creditImport.min'                 => 'El importe del crédito debe ser mayor o igual a cero.',
            'creditImport.numeric'             => 'El importe del crédito debe ser un valor numérico.',
            'creditImport.required'            => 'El importe del crédito es obligatorio.',
            'creditType.required'              => 'El tipo de crédito es obligatorio.',
            'currentDebt.min'                  => 'La deuda actual debe ser mayor o igual a cero.',
            'currentDebt.numeric'              => 'La deuda actual debe ser un valor numérico.',
            'currentDebt.required'             => 'La deuda actual es obligatoria.',
            'downPayment.min'                  => 'El enganche debe ser mayor o igual a cero.',
            'downPayment.numeric'              => 'El enganche debe ser un valor numérico.',
            'downPayment.required'             => 'El enganche es obligatorio.',
            'infonavitCredit.min'              => 'El crédito de Infonavit debe ser mayor o igual a cero.',
            'infonavitCredit.numeric'          => 'El crédito de Infonavit debe ser un valor numérico.',
            'infonavitCredit.required'         => 'El crédito de Infonavit es obligatorio.',
            'landArea.min'                     => 'El área del terreno debe ser mayor o igual a cero.',
            'landArea.numeric'                 => 'El área del terreno debe ser un valor numérico.',
            'landArea.required'                => 'El área del terreno es obligatoria.',
            'loanAmount.min'                   => 'La cantidad del préstamo debe ser mayor o igual a cero.',
            'loanAmount.numeric'               => 'La cantidad del préstamo debe ser un valor numérico.',
            'loanAmount.required'              => 'La cantidad del préstamo es obligatoria.',
            'loanTerm.min'                     => 'El plazo del préstamo debe ser mayor o igual a cero.',
            'loanTerm.numeric'                 => 'El plazo del préstamo debe ser un valor numérico.',
            'loanTerm.required'                => 'El plazo del préstamo es obligatorio.',
            'notarialFeesPercentage.max'       => 'El porcentaje de gastos notariales debe ser menor o igual a 20.',
            'notarialFeesPercentage.min'       => 'El porcentaje de gastos notariales debe ser mayor o igual a uno.',
            'notarialFeesPercentage.numeric'   => 'El porcentaje de gastos notariales debe ser un valor numérico.',
            'notarialFeesPercentage.required'  => 'El porcentaje de gastos notariales es obligatorio.',
            'projectValue.min'                 => 'El valor del proyecto debe ser mayor o igual a cero.',
            'projectValue.numeric'             => 'El valor del proyecto debe ser un valor numérico.',
            'projectValue.required'            => 'El valor del proyecto es obligatorio.',
            'propertyState.required'           => 'El estado de la propiedad es obligatorio.',
            'propertyState.string'             => 'El estado de la propiedad debe ser una cadena de texto.',
            'propertyValue.min'                => 'El valor de la propiedad debe ser mayor o igual a cero.',
            'propertyValue.numeric'            => 'El valor de la propiedad debe ser un valor numérico.',
            'propertyValue.required'           => 'El valor de la propiedad es obligatorio.',
            'remodelingBudget.min'             => 'El presupuesto de remodelación debe ser mayor o igual a cero.',
            'remodelingBudget.numeric'         => 'El presupuesto de remodelación debe ser un valor numérico.',
            'remodelingBudget.required'        => 'El presupuesto de remodelación es obligatorio.',
            'scheme.in'                        => 'El esquema debe ser fijos o crecientes.',
            'scheme.required'                  => 'El esquema es obligatorio.',
            'scheme.string'                    => 'El esquema debe ser una cadena de texto.',
            'subAccount.min'                   => 'La subcuenta debe ser mayor o igual a cero.',
            'subAccount.numeric'               => 'La subcuenta debe ser un valor numérico.',
            'subAccount.required'              => 'La subcuenta es obligatoria.',
        ];
    }
}
