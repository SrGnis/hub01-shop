<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Webinvader\Faker\Provider\EnglishWord;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        fake()->addProvider(EnglishWord::class);

        $projectType = ProjectType::inRandomOrder()->first();
        $name = fake()->unique()->words(rand(1, 3), true);

        // Generate a random logo
        $logoPath = $this->generateRandomLogo();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'summary' => fake()->text(150),
            'description' => $this->randomMarkdown(),
            'logo_path' => $logoPath,
            'website' => fake()->url(),
            'issues' => fake()->url(),
            'source' => fake()->url(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'project_type_id' => $projectType->id,
            'approval_status' => ApprovalStatus::APPROVED,
            'submitted_at' => now()->subDays(rand(1, 30)),
            'reviewed_at' => now()->subDays(rand(0, 30)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Add an owner to the project.
     */
    public function owner(User $user): static
    {
        return $this->afterCreating(function (Project $project) use ($user) {
            $membership = new Membership([
                'role' => 'owner',
                'primary' => true,
            ]);
            $membership->user()->associate($user);
            $membership->project()->associate($project);
            $membership->save();
        });
    }

    /**
     * Add a member to the project.
     */
    public function member(User $user): static
    {
        return $this->afterCreating(function (Project $project) use ($user) {
            $membership = new Membership([
                'role' => 'member',
                'primary' => false,
            ]);
            $membership->user()->associate($user);
            $membership->project()->associate($project);
            $membership->save();
        });
    }

    /**
     * Indicate that the project is draft.
     */
    public function draft(): static
    {
        return $this->state(function () {
            return [
                'approval_status' => ApprovalStatus::DRAFT,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
            ];
        });
    }

    /**
     * Indicate that the project is pending approval.
     */
    public function pending(): static
    {
        return $this->state(function () {
            return [
                'approval_status' => ApprovalStatus::PENDING,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
            ];
        });
    }

    /**
     * Indicate that the project was rejected.
     */
    public function rejected(?string $reason = null): static
    {
        return $this->state(function () use ($reason) {
            return [
                'approval_status' => ApprovalStatus::REJECTED,
                'submitted_at' => now()->subDays(rand(1, 7)),
                'reviewed_at' => now()->subDays(rand(0, 3)),
                'reviewed_by' => User::where('role', 'admin')->inRandomOrder()->first()?->id,
                'rejection_reason' => $reason ?? fake()->sentence(),
            ];
        });
    }

    /**
     * Indicate that the project is a mod.
     */
    public function mod(): static
    {
        return $this->state(function () {
            $modType = ProjectType::where('value', 'mod')->first();

            return [
                'project_type_id' => $modType->id,
            ];
        })->afterCreating(function ($project) {
            $modType = ProjectType::where('value', 'mod')->first();
            $tags = ProjectTag::whereHas('projectTypes', function ($query) use ($modType) {
                $query->where('project_type_id', $modType->id);
            })->inRandomOrder()->take(rand(1, 3))->get();
            $project->tags()->attach($tags);
        });
    }

    /**
     * Indicate that the project is a tile set.
     */
    public function tileSet(): static
    {
        return $this->state(function () {
            $tileSetType = ProjectType::where('value', 'tile_set')->first();

            return [
                'project_type_id' => $tileSetType->id,
            ];
        })->afterCreating(function ($project) {
            $tileSetType = ProjectType::where('value', 'tile_set')->first();
            $tags = ProjectTag::whereHas('projectTypes', function ($query) use ($tileSetType) {
                $query->where('project_type_id', $tileSetType->id);
            })->inRandomOrder()->take(rand(1, 3))->get();
            $project->tags()->attach($tags);
        });
    }

    /**
     * Indicate that the project is a sound pack.
     */
    public function soundPack(): static
    {
        return $this->state(function () {
            $soundPackType = ProjectType::where('value', 'sound_pack')->first();

            return [
                'project_type_id' => $soundPackType->id,
            ];
        })->afterCreating(function ($project) {
            $soundPackType = ProjectType::where('value', 'sound_pack')->first();
            $tags = ProjectTag::whereHas('projectTypes', function ($query) use ($soundPackType) {
                $query->where('project_type_id', $soundPackType->id);
            })->inRandomOrder()->take(rand(1, 3))->get();
            $project->tags()->attach($tags);
        });
    }

    /**
     * Generate random markdown content using Faker.
     */
    public function randomMarkdown(): string
    {
        $faker = fake();
        $markdown = '';

        // Generate a random number of paragraphs (1 to 5)
        $paragraphs = rand(1, 5);

        for ($i = 0; $i < $paragraphs; $i++) {
            // Add headers
            $markdown .= '## '.$faker->sentence()."\n\n";

            // Add paragraphs
            $markdown .= $faker->paragraph()."\n\n";

            // Randomly add lists
            if (rand(0, 1)) {
                $listItems = rand(2, 5);
                for ($j = 0; $j < $listItems; $j++) {
                    $markdown .= '- '.$faker->sentence()."\n";
                }
                $markdown .= "\n";
            }

            // Randomly add code blocks
            if (rand(0, 1)) {
                $markdown .= "```\n".$faker->text()."\n```\n\n";
            }
        }

        return $markdown;
    }

    /**
     * Generate a random polygon logo for the project.
     *
     * @return string|null The path to the generated logo
     */
    protected function generateRandomLogo(): ?string
    {
        try {
            // Create a unique filename
            $filename = 'project-logo-'.uniqid().'.png';
            $directory = 'project-logos';
            $fullPath = storage_path('app/public/'.$directory);

            // Create directory if it doesn't exist
            if (! file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Set image dimensions
            $width = 300;
            $height = 300;

            // Create the image
            $image = imagecreatetruecolor($width, $height);

            // Generate darker background color suitable for dark themes
            $bgR = rand(30, 80);
            $bgG = rand(30, 80);
            $bgB = rand(30, 80);
            $backgroundColor = imagecolorallocate($image, $bgR, $bgG, $bgB);

            // Fill background
            imagefill($image, 0, 0, $backgroundColor);

            // Generate random number of polygon vertices (3-7)
            $numVertices = rand(3, 7);
            $vertices = [];

            // Generate random polygon vertices
            $centerX = $width / 2;
            $centerY = $height / 2;
            $radius = min($width, $height) * 0.4;

            for ($i = 0; $i < $numVertices; $i++) {
                $angle = 2 * M_PI * $i / $numVertices;
                $x = $centerX + $radius * cos($angle);
                $y = $centerY + $radius * sin($angle);
                $vertices[] = (int) $x;
                $vertices[] = (int) $y;
            }

            // Generate vibrant polygon colors that contrast with dark backgrounds
            $vibrantColors = [
                [255, 100, 100], // Red
                [100, 255, 100], // Green
                [100, 100, 255], // Blue
                [255, 255, 100], // Yellow
                [255, 100, 255], // Magenta
                [100, 255, 255], // Cyan
                [255, 165, 0],   // Orange
                [128, 0, 128],    // Purple
            ];

            $randomColor = $vibrantColors[array_rand($vibrantColors)];
            $polyR = $randomColor[0];
            $polyG = $randomColor[1];
            $polyB = $randomColor[2];

            // Ensure the polygon color is different from the background
            while (abs($polyR - $bgR) < 50 && abs($polyG - $bgG) < 50 && abs($polyB - $bgB) < 50) {
                $randomColor = $vibrantColors[array_rand($vibrantColors)];
                $polyR = $randomColor[0];
                $polyG = $randomColor[1];
                $polyB = $randomColor[2];
            }

            $polygonColor = imagecolorallocate($image, $polyR, $polyG, $polyB);

            // Draw the polygon
            imagefilledpolygon($image, $vertices, $polygonColor);

            // Add a bright border to the polygon for better visibility on dark backgrounds
            // Use a brighter version of the polygon color or white for the border
            $borderR = min(255, $polyR + 70);
            $borderG = min(255, $polyG + 70);
            $borderB = min(255, $polyB + 70);
            $borderColor = imagecolorallocate($image, $borderR, $borderG, $borderB);
            imagepolygon($image, $vertices, $borderColor);

            // Add some decorative elements
            $decorationType = rand(0, 3);

            // Create a bright accent color for decorations
            $accentR = 255 - $polyR;
            $accentG = 255 - $polyG;
            $accentB = 255 - $polyB;
            $accentColor = imagecolorallocate($image, $accentR, $accentG, $accentB);

            switch ($decorationType) {
                case 0:
                    // Add a small circle in the center
                    $circleColor = $accentColor;
                    imagefilledellipse($image, $centerX, $centerY, 30, 30, $circleColor);
                    // Add a border to the circle
                    imageellipse($image, $centerX, $centerY, 30, 30, $borderColor);
                    break;

                case 1:
                    // Add a few small dots with varying sizes
                    for ($i = 0; $i < 5; $i++) {
                        $dotX = rand($width * 0.2, $width * 0.8);
                        $dotY = rand($height * 0.2, $height * 0.8);
                        $dotSize = rand(5, 15);
                        imagefilledellipse($image, $dotX, $dotY, $dotSize, $dotSize, $accentColor);
                        imageellipse($image, $dotX, $dotY, $dotSize, $dotSize, $borderColor);
                    }
                    break;

                case 2:
                    // Add a smaller polygon inside
                    $innerVertices = [];
                    $innerRadius = $radius * 0.5;

                    for ($i = 0; $i < $numVertices; $i++) {
                        $angle = 2 * M_PI * $i / $numVertices;
                        $x = $centerX + $innerRadius * cos($angle);
                        $y = $centerY + $innerRadius * sin($angle);
                        $innerVertices[] = (int) $x;
                        $innerVertices[] = (int) $y;
                    }

                    imagefilledpolygon($image, $innerVertices, $accentColor);
                    imagepolygon($image, $innerVertices, $borderColor);
                    break;

                case 3:
                    // Add lines from center to each vertex with some thickness
                    for ($i = 0; $i < $numVertices; $i++) {
                        $vx = $vertices[$i * 2];
                        $vy = $vertices[$i * 2 + 1];

                        // Draw a thicker line by using multiple lines
                        for ($offset = -1; $offset <= 1; $offset++) {
                            imageline($image, $centerX + $offset, $centerY, $vx, $vy, $accentColor);
                            imageline($image, $centerX, $centerY + $offset, $vx, $vy, $accentColor);
                        }
                    }
                    break;
            }

            // Save the image
            $filePath = $fullPath.'/'.$filename;
            imagepng($image, $filePath);

            // Return the relative path for storage in the database
            return $directory.'/'.$filename;
        } catch (\Exception $e) {
            // If there's an error, log it and return null (the default placeholder will be used)
            \Illuminate\Support\Facades\Log::error('Failed to generate project logo: '.$e->getMessage());

            return null;
        }
    }
}
