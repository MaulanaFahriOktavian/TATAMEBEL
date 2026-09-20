<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'order_id' => function (array $attributes) {
                return Order::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'type' => PaymentType::DP,
            'amount' => 5000000.00,
            'payment_date' => now()->toDateString(),
            'method' => 'Bank Transfer BCA',
            'reference' => 'TRX-' . fake()->numerify('########'),
            'notes' => 'Down payment 50% confirmed',
            'confirmed_by' => null,
            'confirmed_at' => now(),
        ];
    }
}
