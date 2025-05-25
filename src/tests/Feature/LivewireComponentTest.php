<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ProjectManagement;
use App\Livewire\Admin\ProjectTypeManagement;
use App\Livewire\Admin\SiteManagement;
use App\Livewire\Admin\TagGroupManagement;
use App\Livewire\Admin\TagManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\VersionTagGroupManagement;
use App\Livewire\Admin\VersionTagManagement;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\ProjectForm;
use App\Livewire\ProjectSearch;
use App\Livewire\ProjectShow;
use App\Livewire\ProjectShowChangelog;
use App\Livewire\ProjectShowDescription;
use App\Livewire\ProjectShowVersions;
use App\Livewire\ProjectVersionForm;
use App\Livewire\ProjectVersionShow;
use App\Livewire\UserProfile;
use App\Livewire\UserProfileEdit;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// TODO: duplicated tests with AccessTest
class LivewireComponentTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // This works but I don't like it,
        // the alternative is to use a static bool and use it to seed in the setUp method
        shell_exec('php artisan migrate:fresh --seed');
    }

    public static function tearDownAfterClass(): void
    {
        shell_exec('php artisan migrate:fresh');
        parent::tearDownAfterClass();
    }

    #[Test]
    public function login_component_renders_successfully()
    {
        Livewire::test(Login::class)
            ->assertStatus(200);
    }

    #[Test]
    public function register_component_renders_successfully()
    {
        Livewire::test(Register::class)
            ->assertStatus(200);
    }

    #[Test]
    public function verify_email_component_renders_successfully()
    {
        $user = User::factory()->unverified()->create();
        Livewire::actingAs($user)
            ->test(VerifyEmail::class)
            ->assertStatus(200);
    }

    #[Test]
    public function project_search_component_renders_successfully()
    {
        $projectType = ProjectType::first();

        Livewire::test(ProjectSearch::class, ['projectType' => $projectType])
            ->assertStatus(200);
    }

    #[Test]
    public function project_show_components_render_successfully()
    {
        $project = Project::has('versions')->first();

        Livewire::test(ProjectShow::class, ['project' => $project])
            ->assertStatus(200);

        Livewire::test(ProjectShowDescription::class, ['project' => $project])
            ->assertStatus(200);

        Livewire::test(ProjectShowChangelog::class, ['project' => $project])
            ->assertStatus(200);

        Livewire::test(ProjectShowVersions::class, ['project' => $project])
            ->assertStatus(200);
    }

    #[Test]
    public function project_form_component_renders_successfully()
    {
        $projectType = ProjectType::first();
        $user = User::factory()->create(['email_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test(ProjectForm::class, ['projectType' => $projectType])
            ->assertStatus(200);
    }

    #[Test]
    public function project_version_show_component_renders_successfully()
    {
        $project = Project::has('versions')->first();
        $version = $project->versions->first();

        Livewire::test(ProjectVersionShow::class, [
            'project' => $project,
            'version_key' => $version->version,
        ])
            ->assertStatus(200);
    }

    #[Test]
    public function project_version_form_component_renders_successfully()
    {
        $project = Project::has('versions')->first();
        $projectType = $project->projectType;
        $version = $project->versions->first();
        $user = $project->owner->first();

        Livewire::actingAs($user)
            ->test(ProjectVersionForm::class, ['projectType' => $projectType, 'project' => $project, 'version' => $version])
            ->assertStatus(200);
    }

    #[Test]
    public function user_profile_component_renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::test(UserProfile::class, ['user' => $user])
            ->assertStatus(200);
    }

    #[Test]
    public function user_profile_edit_component_renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfileEdit::class, ['user' => $user])
            ->assertStatus(200);
    }

    #[Test]
    public function admin_dashboard_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_user_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_project_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ProjectManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_site_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(SiteManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_project_type_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ProjectTypeManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_tag_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(TagManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_tag_group_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(TagGroupManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_version_tag_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(VersionTagManagement::class)
            ->assertStatus(200);
    }

    #[Test]
    public function admin_version_tag_group_management_component_renders_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(VersionTagGroupManagement::class)
            ->assertStatus(200);
    }
}
