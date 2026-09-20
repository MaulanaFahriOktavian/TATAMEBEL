<?php

namespace Database\Factories;

use App\Enums\ShippingStatus;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipping>
 */
class ShippingFactory extends Factory
{
    protected $model = Shipping::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'order_id' => function (array $attributes) {
                return Order::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'courier' => 'Workshop Internal Delivery Fleet',
            'tracking_number' => 'TRK-' . fake()->numerify('######'),
            'shipping_address' => fake()->address(),
            'shipped_at' => null,
            'estimated_arrival' => now()->addDays(3)->toDateString(),
            'delivered_at' => null,
            'status' => ShippingStatus::PENDING,
            'notes' => 'Call customer 1 hour prior to arrival',
        ];
    }
}
