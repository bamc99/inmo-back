<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class QuotationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'additionalIncome'         => ['required', 'numeric', 'min:0'],
            'additionalPropertyValue'  => ['required', 'numeric', 'min:0'],
            'clientId'                 => ['required', 'numeric','exists:clients,id', 'min:0'],
            'constructionArea'         => ['required', 'numeric', 'min:0'],
            'creditImport'             => ['required', 'numeric', 'min:0'],
            'creditType'               => ['required', 'string'],
            'currentDebt'              => ['required', 'numeric', 'min:0'],
            'downPayment'              => ['required', 'numeric', 'min:0'],
            'infonavitCredit'          => ['required', 'numeric', 'min:0'],
            'landArea'                 => ['required', 'numeric', 'min:0'],
            'loanAmount'               => ['required', 'numeric', 'min:0'],
            'loanTerm'                 => ['required', 'numeric', 'min:0'],
            'monthlyIncome'            => ['required', 'numeric', 'min:0'],
            'notarialFeesPercentage'   => ['required', 'numeric', 'min:1', 'max:20'],
            'projectValue'             => ['required', 'numeric', 'min:0'],
            'propertyState'            => ['required', 'string'],
            'propertyValue'            => ['required', 'numeric', 'min:0'],
            'remodelingBudget'         => ['required', 'numeric', 'min:0'],
            'scheme'                   => ['required', 'string', 'in:fijos,crecientes'],
            'subAccount'               => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(){
        return [
            'additionalPropertyValue.min'      => 'El valor adicional de la propiedad debe ser mayor o igual a cero.',
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
