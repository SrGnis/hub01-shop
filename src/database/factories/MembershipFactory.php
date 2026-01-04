<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Membership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'role' => $this->faker->randomElement(['owner', 'contributor', 'tester', 'translator']),
            'primary' => false,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the membership is for an owner.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function owner()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'owner',
            ];
        });
    }

    /**
     * Indicate that the membership is primary.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'primary' => true,
            ];
        });
    }

    /**
     * Indicate that the membership is for a contributor.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function contributor()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'contributor',
            ];
        });
    }

    /**
     * Indicate that the membership is for a tester.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function tester()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'tester',
            ];
        });
    }

    /**
     * Indicate that the membership is for a translator.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function translator()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'translator',
            ];
        });
    }
}
