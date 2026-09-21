<?php

namespace App\Http\Requests\ChangeRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreChangeRequestRequest extends FormRequest
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
            'order_item_id' => ['required', 'integer'],
            'requested_by' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
            'requested_changes' => ['required', 'array', 'min:1'],
            'requested_changes.width' => ['nullable', 'numeric', 'min:0'],
            'requested_changes.height' => ['nullable', 'numeric', 'min:0'],
            'requested_changes.depth' => ['nullable', 'numeric', 'min:0'],
            'requested_changes.dimension_unit' => ['nullable', 'string', 'max:20', 'in:mm,cm,m,inch'],
            'requested_changes.material' => ['nullable', 'string', 'max:255'],
            'requested_changes.wood_grade' => ['nullable', 'string', 'max:100'],
            'requested_changes.finishing' => ['nullable', 'string', 'max:255'],
            'requested_changes.color' => ['nullable', 'string', 'max:100'],
            'requested_changes.fabric' => ['nullable', 'string', 'max:255'],
            'requested_changes.design_reference' => ['nullable', 'string'],
            'requested_changes.special_request' => ['nullable', 'string'],
            'requested_changes.production_note' => ['nullable', 'string'],
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
