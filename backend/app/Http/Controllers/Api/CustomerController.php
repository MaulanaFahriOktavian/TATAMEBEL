<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of the workshop's customers.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Customer::class);

        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $customers = $this->customerService->list($workshop);

        return response()->json([
            'success' => true,
            'message' => 'Customers retrieved successfully.',
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ],
        ], 200);
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $customer = $this->customerService->create($workshop, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Display the specified customer.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $customer = $workshop->customers()->whereKey($id)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        Gate::authorize('view', $customer);

        return response()->json([
            'success' => true,
            'message' => 'Customer retrieved successfully.',
            'data' => new CustomerResource($customer),
        ], 200);
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, int|string $id): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $customer = $workshop->customers()->whereKey($id)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        Gate::authorize('update', $customer);

        $updated = $this->customerService->update($customer, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => new CustomerResource($updated),
        ], 200);
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $customer = $workshop->customers()->whereKey($id)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        Gate::authorize('delete', $customer);

        $this->customerService->delete($customer, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
            'data' => null,
        ], 200);
    }
}
