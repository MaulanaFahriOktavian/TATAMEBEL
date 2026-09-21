<?php

namespace Database\Factories;

use App\Enums\QcItemStatus;
use App\Models\Order;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QcItem>
 */
class QcItemFactory extends Factory
{
    protected $model = QcItem::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'qc_inspection_id' => function (array $attributes) {
                return QcInspection::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'order_item_id' => null,
            'category' => 'dimension',
            'item' => 'Kesesuaian dimensi panjang, lebar, dan tinggi',
            'status' => null,
            'notes' => null,
        ];
    }

    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QcItemStatus::PASS,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QcItemStatus::FAIL,
            'notes' => 'Terdapat deviasi ukuran atau cacat',
        ]);
    }
}
