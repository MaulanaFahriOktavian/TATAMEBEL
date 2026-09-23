<?php

namespace App\Http\Requests\Shipping;

use App\Enums\ShippingStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class StoreShippingRequest extends FormRequest
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
            'courier' => ['required', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['required', 'string'],
            'estimated_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', new Enum(ShippingStatus::class)],
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
