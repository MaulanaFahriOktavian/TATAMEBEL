<?php

namespace App\Http\Requests\Specification;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSpecificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'depth' => ['nullable', 'numeric', 'min:0'],
            'dimension_unit' => ['nullable', 'string', 'max:20', 'in:mm,cm,m,inch'],
            'material' => ['nullable', 'string', 'max:255'],
            'wood_grade' => ['nullable', 'string', 'max:100'],
            'finishing' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'fabric' => ['nullable', 'string', 'max:255'],
            'design_reference' => ['nullable', 'string'],
            'special_request' => ['nullable', 'string'],
            'production_note' => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
