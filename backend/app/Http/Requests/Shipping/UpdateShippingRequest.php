<?php

namespace App\Http\Requests\Shipping;

use App\Enums\ShippingStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class UpdateShippingRequest extends FormRequest
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
            'courier' => ['sometimes', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['sometimes', 'string'],
            'estimated_arrival' => ['nullable', 'date'],
            'shipped_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', new Enum(ShippingStatus::class)],
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
