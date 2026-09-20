<?php

namespace Database\Factories;

use App\Enums\ProductionStageStatus;
use App\Models\Order;
use App\Models\ProductionStage;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionStage>
 */
class ProductionStageFactory extends Factory
{
    protected $model = ProductionStage::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'order_id' => function (array $attributes) {
                return Order::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'name' => 'Assembly',
            'sequence' => 1,
            'status' => ProductionStageStatus::PENDING,
            'started_at' => null,
            'completed_at' => null,
            'is_active' => true,
        ];
    }
}
