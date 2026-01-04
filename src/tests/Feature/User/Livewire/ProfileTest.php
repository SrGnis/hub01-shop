<?php

namespace Tests\Feature\User\Livewire;

use App\Livewire\UserProfileEdit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        /** @disregard P1006 */
        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk();
    }

    #[Test]
    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->set('bio', 'Test Bio')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test Bio', $user->bio);
    }

    #[Test]
    public function test_avatar_can_be_updated()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->set('avatar', $file)
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }
}
