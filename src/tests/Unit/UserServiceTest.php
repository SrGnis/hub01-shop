<?php

namespace Tests\Unit;

use App\Models\PendingEmailChange;
use App\Models\PendingPasswordChange;
use App\Models\User;
use App\Notifications\AuthorizeEmailChange;
use App\Notifications\ConfirmPasswordChange;
use App\Notifications\UserDeactivated;
use App\Notifications\UserReactivated;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_update_profile_updates_info()
    {
        $user = User::factory()->create();
        $data = ['name' => 'New Name', 'bio' => 'New Bio'];

        $updatedUser = $this->userService->updateProfile($user, $data);

        $this->assertEquals('New Name', $updatedUser->name); // Assuming name is fillable and passed
        // Note: UserService::updateProfile logic: $user->update($data).
        // Check if 'name' and 'bio' are in $data.
        $this->assertDatabaseHas('users', ['id' => $user->id, 'bio' => 'New Bio']);
    }

    public function test_update_profile_updates_avatar()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');
        $data = [];

        $updatedUser = $this->userService->updateProfile($user, $data, $file);

        $this->assertNotNull($updatedUser->avatar);
        Storage::disk('public')->assertExists($updatedUser->avatar);
    }

    public function test_create_user()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ];

        $user = $this->userService->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_update_user_admin_function()
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $data = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role' => 'admin',
            'password' => 'newpassword',
        ];

        $updatedUser = $this->userService->update($user, $data);

        $this->assertEquals('New Name', $updatedUser->name);
        $this->assertEquals('new@example.com', $updatedUser->email);
        $this->assertTrue(Hash::check('newpassword', $updatedUser->password));
    }

    public function test_update_user_without_password_change()
    {
        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
        $data = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => $user->role,
            'password' => '', // Empty password
        ];

        $updatedUser = $this->userService->update($user, $data);

        $this->assertTrue(Hash::check('oldpassword', $updatedUser->password));
        $this->assertEquals('Updated Name', $updatedUser->name);
    }

    public function test_delete_user()
    {
        $user = User::factory()->create();

        $this->userService->delete($user);

        $this->assertSoftDeleted($user);
    }

    public function test_deactivate_user()
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->userService->deactivate($user);

        $this->assertNotNull($user->refresh()->deactivated_at);
        Notification::assertSentTo($user, UserDeactivated::class);
        // Ensure sessions are cleared (mocking DB logic for sessions might be tricky in pure unit, but this is integration)
        // We can't easily verify DB::table('sessions')->delete() without creating a session entry first.
    }

    public function test_reactivate_user()
    {
        Notification::fake();
        $user = User::factory()->create(['deactivated_at' => now()]);

        $this->userService->reactivate($user);

        $this->assertNull($user->refresh()->deactivated_at);
        Notification::assertSentTo($user, UserReactivated::class);
    }

    public function test_request_email_change()
    {
        Notification::fake();
        $user = User::factory()->create();
        $newEmail = 'new@example.com';

        $this->userService->requestEmailChange($user, $newEmail);

        $this->assertDatabaseHas('pending_email_changes', [
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'status' => 'pending_authorization',
        ]);

        Notification::assertSentTo($user, AuthorizeEmailChange::class);
    }

    public function test_request_email_change_clears_previous_pending()
    {
        $user = User::factory()->create();
        PendingEmailChange::create([
            'user_id' => $user->id,
            'old_email' => $user->email,
            'new_email' => 'old_request@example.com',
            'authorization_token' => 'token',
            'status' => 'pending_authorization',
            'authorization_expires_at' => now()->addHour(),
        ]);

        $this->userService->requestEmailChange($user, 'new@example.com');

        $this->assertEquals(1, PendingEmailChange::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('pending_email_changes', ['new_email' => 'new@example.com']);
    }

    public function test_cancel_email_change()
    {
        $user = User::factory()->create();
        $pending = PendingEmailChange::create([
            'user_id' => $user->id,
            'old_email' => $user->email,
            'new_email' => 'test@example.com',
            'authorization_token' => 'token',
            'status' => 'pending_authorization',
            'authorization_expires_at' => now()->addHour(),
        ]);

        $this->userService->cancelEmailChange($pending);

        $this->assertModelMissing($pending);
    }

    public function test_request_password_change()
    {
        Notification::fake();
        $user = User::factory()->create();
        $newPassword = 'NewPassword123!';

        $this->userService->requestPasswordChange($user, $newPassword);

        $pending = PendingPasswordChange::where('user_id', $user->id)->first();
        $this->assertNotNull($pending);
        $this->assertTrue(Hash::check($newPassword, $pending->hashed_password));
        $this->assertEquals('pending_verification', $pending->status);

        Notification::assertSentTo($user, ConfirmPasswordChange::class);
    }

    public function test_request_password_change_clears_previous()
    {
        $user = User::factory()->create();
        PendingPasswordChange::create([
            'user_id' => $user->id,
            'hashed_password' => 'hash',
            'verification_token' => 'token',
            'status' => 'pending_verification',
            'expires_at' => now()->addHour(),
        ]);

        $this->userService->requestPasswordChange($user, 'NewPassword');

        $this->assertEquals(1, PendingPasswordChange::where('user_id', $user->id)->count());
    }

    public function test_cancel_password_change()
    {
        $user = User::factory()->create();
        $pending = PendingPasswordChange::create([
            'user_id' => $user->id,
            'hashed_password' => 'hash',
            'verification_token' => 'token',
            'status' => 'pending_verification',
            'expires_at' => now()->addHour(),
        ]);

        $this->userService->cancelPasswordChange($pending);

        $this->assertModelMissing($pending);
    }
}
