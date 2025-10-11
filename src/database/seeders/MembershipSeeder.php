<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and projects
        $users = User::all();
        $projects = Project::all();

        // For each project, assign a primary owner
        foreach ($projects as $project) {
            // Skip if the project already has a primary owner
            if ($project->memberships()->where('primary', true)->exists()) {
                continue;
            }

            // Assign a random user as the primary owner
            $primaryOwner = $users->random();
            Membership::create([
                'user_id' => $primaryOwner->id,
                'project_id' => $project->id,
                'role' => 'owner',
                'primary' => true,
            ]);

            // Randomly assign other users as contributors
            $contributors = $users->except($primaryOwner->id)->random(rand(0, 3));
            foreach ($contributors as $contributor) {
                Membership::create([
                    'user_id' => $contributor->id,
                    'project_id' => $project->id,
                    'role' => fake()->randomElement(['contributor', 'tester', 'translator']),
                    'primary' => false,
                ]);
            }
        }
    }
}
