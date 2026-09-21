<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Retrieve a paginated list of customers for a workshop.
     */
    public function list(Workshop $workshop, int $perPage = 15): LengthAwarePaginator
    {
        return $workshop->customers()
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Create a new customer for the workshop.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Workshop $workshop, array $data, ?User $actor = null): Customer
    {
        $customer = $workshop->customers()->create([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        ActivityLogService::log(
            workshopId: $workshop->id,
            action: 'CUSTOMER_CREATED',
            entity: $customer,
            description: "Customer '{$customer->name}' created.",
            user: $actor,
            metadata: ['name' => $customer->name, 'phone' => $customer->phone]
        );

        return $customer;
    }

    /**
     * Update an existing customer.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data, ?User $actor = null): Customer
    {
        $customer->update([
            'name' => $data['name'] ?? $customer->name,
            'company_name' => array_key_exists('company_name', $data) ? $data['company_name'] : $customer->company_name,
            'phone' => $data['phone'] ?? $customer->phone,
            'email' => array_key_exists('email', $data) ? $data['email'] : $customer->email,
            'address' => array_key_exists('address', $data) ? $data['address'] : $customer->address,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $customer->notes,
        ]);

        ActivityLogService::log(
            workshopId: $customer->workshop_id,
            action: 'CUSTOMER_UPDATED',
            entity: $customer,
            description: "Customer '{$customer->name}' updated.",
            user: $actor,
            metadata: $data
        );

        return $customer;
    }

    /**
     * Delete a customer if no associated orders exist.
     *
     * @throws ValidationException
     */
    public function delete(Customer $customer, ?User $actor = null): void
    {
        if ($customer->orders()->exists()) {
            throw ValidationException::withMessages([
                'customer' => ['Pelanggan tidak dapat dihapus karena memiliki pesanan yang terdaftar.'],
            ]);
        }

        ActivityLogService::log(
            workshopId: $customer->workshop_id,
            action: 'CUSTOMER_DELETED',
            entity: $customer,
            description: "Customer '{$customer->name}' deleted.",
            user: $actor,
            metadata: ['name' => $customer->name, 'id' => $customer->id]
        );

        $customer->delete();
    }
}
