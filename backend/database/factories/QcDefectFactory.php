<?php

namespace Database\Factories;

use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QcDefect>
 */
class QcDefectFactory extends Factory
{
    protected $model = QcDefect::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'qc_inspection_id' => function (array $attributes) {
                return QcInspection::factory()->create([
                    'workshop_id' => $attributes['workshop_id'],
                ])->id;
            },
            'qc_item_id' => null,
            'description' => 'Goresan pada permukaan finishing daun meja',
            'severity' => QcDefectSeverity::MEDIUM,
            'status' => QcDefectStatus::OPEN,
            'resolution' => null,
            'resolved_at' => null,
        ];
    }

    public function inRework(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QcDefectStatus::IN_REWORK,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QcDefectStatus::RESOLVED,
            'resolution' => 'Permukaan diamplas ulang dan disemprot top coat satin',
            'resolved_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QcDefectStatus::ACCEPTED,
            'resolution' => 'Diterima sebagai variasi corak alami kayu jati atas persetujuan owner',
            'resolved_at' => now(),
        ]);
    }
}
