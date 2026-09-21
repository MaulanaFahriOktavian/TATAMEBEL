<?php

namespace App\Http\Requests\Qc;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreQcInspectionRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'custom_items' => ['nullable', 'array'],
            'custom_items.*.category' => ['nullable', 'string', 'max:100'],
            'custom_items.*.item' => ['required_with:custom_items', 'string', 'max:255'],
            'custom_items.*.order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'custom_items.*.notes' => ['nullable', 'string', 'max:1000'],
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
