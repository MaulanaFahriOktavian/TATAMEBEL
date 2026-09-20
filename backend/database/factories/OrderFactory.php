<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'customer_id' => function (array $attributes) {
                return Customer::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'order_number' => 'ORD-' . fake()->unique()->numerify('#####'),
            'title' => 'Custom ' . fake()->words(3, true),
            'status' => OrderStatus::DRAFT,
            'total_amount' => fake()->randomFloat(2, 500000, 25000000),
            'notes' => fake()->paragraph(),
            'public_token' => Str::random(64),
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
