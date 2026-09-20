<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 250000, 5000000);

        return [
            'workshop_id' => Workshop::factory(),
            'order_id' => function (array $attributes) {
                return Order::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'product_name' => fake()->words(2, true) . ' Woodwork',
            'product_code' => 'SKU-' . fake()->numerify('####'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
            'notes' => fake()->sentence(),
        ];
    }
}
