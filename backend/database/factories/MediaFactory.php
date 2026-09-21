<?php

namespace Database\Factories;

use App\Enums\MediaVisibility;
use App\Models\Media;
use App\Models\Order;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'order_id' => function (array $attributes) {
                return Order::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'production_update_id' => null,
            'qc_inspection_id' => null,
            'uploaded_by' => function (array $attributes) {
                return User::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'file_path' => 'workshops/1/orders/1/media/test.jpg',
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024 * 500, // 500 KB
            'visibility' => MediaVisibility::INTERNAL,
            'caption' => 'Test photo',
        ];
    }
}
