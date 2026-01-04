<?php

namespace Database\Factories;

use App\Models\AbuseReport;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbuseReport>
 */
class AbuseReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportable = $this->faker->randomElement([
            Project::factory(),
            ProjectVersion::factory(),
            User::factory(),
        ]);
        $reportedItem = $reportable->create();

        return [
            'reason' => $this->faker->sentence(),
            'reportable_id' => $reportedItem->id,
            'reportable_type' => $reportedItem::class,
            'reporter_id' => User::factory()->create(['email_verified_at' => now()]),
            'status' => $this->faker->randomElement(['pending', 'resolved']),
        ];
    }

    /**
     * Indicate that the report is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the report is resolved.
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'resolved',
            ];
        });
    }
}
