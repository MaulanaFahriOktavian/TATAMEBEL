<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'name' => fake()->name(),
            'company_name' => fake()->boolean(40) ? fake()->company() : null,
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'notes' => fake()->sentence(),
        ];
    }
}
