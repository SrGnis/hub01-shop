<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserQuota>
 */
class UserQuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'total_storage_max' => 2147483648, // 2GB in bytes
        ];
    }

    /**
     * Set a specific total storage limit.
     */
    public function storageLimit(int $bytes): static
    {
        return $this->state(function () use ($bytes) {
            return [
                'total_storage_max' => $bytes,
            ];
        });
    }
}
