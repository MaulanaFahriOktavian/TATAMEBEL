<?php

namespace Database\Factories;

use App\Enums\SpecificationStatus;
use App\Models\OrderItem;
use App\Models\Specification;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Specification>
 */
class SpecificationFactory extends Factory
{
    protected $model = Specification::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'order_item_id' => function (array $attributes) {
                return OrderItem::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'version' => 1,
            'width' => 120.00,
            'height' => 75.00,
            'depth' => 80.00,
            'dimension_unit' => 'cm',
            'material' => 'Solid Teak Wood (Kayu Jati)',
            'wood_grade' => 'Grade A',
            'finishing' => 'Natural Matte Polyurethane',
            'color' => 'Warm Honey Oak',
            'fabric' => null,
            'design_reference' => 'https://example.com/design/sketches/101.jpg',
            'special_request' => 'Smooth rounded bevel edge',
            'production_note' => 'Kiln dry moisture content below 12%',
            'status' => SpecificationStatus::DRAFT,
            'locked_at' => null,
            'locked_by' => null,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
        ]);
    }
}
