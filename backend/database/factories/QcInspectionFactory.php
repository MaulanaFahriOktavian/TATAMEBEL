<?php

namespace Database\Factories;

use App\Enums\QcInspectionStatus;
use App\Models\Order;
use App\Models\QcInspection;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QcInspection>
 */
class QcInspectionFactory extends Factory
{
    protected $model = QcInspection::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'order_id' => function (array $attributes) {
                return Order::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'inspected_by' => function (array $attributes) {
                return User::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'status' => QcInspectionStatus::PENDING,
            'notes' => 'Pre-delivery quality check inspection',
            'inspected_at' => null,
        ];
    }
}
