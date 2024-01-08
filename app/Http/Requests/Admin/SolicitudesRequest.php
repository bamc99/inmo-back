<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudesRequest extends FormRequest
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
            'solicitudFile' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'anexoFile' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf']
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
            'solicitudFile.required'   => 'La solicitud es requerida.',
            'solicitudFile.file'       => 'La solicitud debe ser un archivo.',
            'solicitudFile.mimes'      => 'La solicitud debe ser una imagen o un PDF.',
            'solicitudFile.uploaded'   => 'La solicitud no se pudo subir.',
            'anexoFile.required'      => 'El anexo es requerido.',
            'anexoFile.file'          => 'El anexo debe ser un archivo.',
            'anexoFile.mimes'         => 'El anexo debe ser una imagen o un PDF.',
            'anexoFile.uploaded'      => 'El anexo no se pudo subir.',
        ];
    }
}
