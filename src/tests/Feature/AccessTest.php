<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\ProjectForm;
use App\Livewire\ProjectSearch;
use App\Livewire\ProjectShow;
use App\Livewire\ProjectVersionForm;
use App\Livewire\ProjectVersionShow;
use App\Livewire\UserProfile;
use App\Livewire\UserProfileEdit;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

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
    public function welcome_page_loads_successfully()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(config('app.name'));
        $response->assertSee('Browse Mods');
    }

    #[Test]
    public function login_page_loads_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        Livewire::test(Login::class)->assertStatus(200);
    }

    #[Test]
    public function register_page_loads_successfully()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        Livewire::test(Register::class)->assertStatus(200);
    }

    #[Test]
    public function project_search_page_loads_successfully()
    {
        $projectType = ProjectType::first();

        $response = $this->get(route('project-search', $projectType));
        $response->assertStatus(200);

        Livewire::test(ProjectSearch::class, ['projectType' => $projectType])->assertStatus(200);
    }

    #[Test]
    public function project_show_page_loads_successfully()
    {
        $project = Project::first();
        $projectType = $project->projectType;

        $response = $this->get(route('project.show', ['projectType' => $projectType, 'project' => $project]));
        $response->assertStatus(200);

        Livewire::test(ProjectShow::class, ['projectType' => $projectType, 'project' => $project])->assertStatus(200);
    }

    #[Test]
    public function project_version_show_page_loads_successfully()
    {
        $project = Project::has('versions')->first();
        $projectType = $project->projectType;
        $version = $project->versions->first();

        $response = $this->get(route('project.version.show', ['projectType' => $projectType, 'project' => $project, 'version_key' => $version->version]));
        $response->assertStatus(200);

        Livewire::test(ProjectVersionShow::class, ['project' => $project, 'version_key' => $version->version])->assertStatus(200);
    }

    #[Test]
    public function user_profile_page_loads_successfully()
    {
        $user = User::first();

        $response = $this->get(route('user.profile', ['user' => $user]));
        $response->assertStatus(200);

        Livewire::test(UserProfile::class, ['user' => $user])->assertStatus(200);
    }

    #[Test]
    public function user_profile_edit_redirects_unauthenticated_users()
    {
        $user = User::first();

        $response = $this->get(route('user.profile.edit', ['user' => $user]));
        $response->assertRedirectToRoute('login');

        Livewire::test(UserProfileEdit::class, ['user' => $user])->assertRedirectToRoute('login');
    }

    #[Test]
    public function project_create_redirects_unauthenticated_users()
    {
        $projectType = ProjectType::first();

        $response = $this->get(route('project.create', ['projectType' => $projectType]));
        $response->assertRedirectToRoute('login');

        Livewire::test(ProjectForm::class, ['projectType' => $projectType])->assertRedirectToRoute('login', ['projectType' => $projectType]);
    }

    #[Test]
    public function project_edit_redirects_unauthenticated_users()
    {
        $project = Project::first();
        $projectType = $project->projectType;

        $response = $this->get(route('project.edit', ['projectType' => $projectType, 'project' => $project]));
        $response->assertRedirectToRoute('login');

        Livewire::test(ProjectForm::class, ['projectType' => $projectType, 'project' => $project])->assertRedirectToRoute('login', ['projectType' => $projectType]);
    }

    #[Test]
    public function project_version_create_redirects_unauthenticated_users()
    {
        $project = Project::first();
        $projectType = $project->projectType;

        $response = $this->get(route('project.version.create', ['projectType' => $projectType, 'project' => $project]));
        $response->assertRedirectToRoute('login');

        Livewire::test(ProjectVersionForm::class, ['projectType' => $projectType, 'project' => $project])->assertRedirectToRoute('login', ['projectType' => $projectType]);
    }

    #[Test]
    public function project_version_edit_redirects_unauthenticated_users()
    {
        $project = Project::first();
        $projectType = $project->projectType;
        $version = $project->versions->first();

        $response = $this->get(route('project.version.edit', ['projectType' => $projectType, 'project' => $project, 'version_key' => $version->version]));
        $response->assertRedirectToRoute('login');

        Livewire::test(ProjectVersionForm::class, ['projectType' => $projectType, 'project' => $project, 'version' => $version])->assertRedirectToRoute('login', ['projectType' => $projectType]);
    }

    #[Test]
    public function email_verification_redirects_unauthenticated_users()
    {
        $response = $this->get(route('verification.notice'));
        $response->assertRedirectToRoute('login');
    }

    #[Test]
    public function user_profile_edit_loads_successfully_for_authenticated_users()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $this->get(route('user.profile.edit', ['user' => $user]))->assertStatus(200);

        Livewire::test(UserProfileEdit::class, ['user' => $user])->assertStatus(200);
    }

    #[Test]
    public function project_create_loads_successfully_for_authenticated_users()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $projectType = ProjectType::factory()->create();
        $this->actingAs($user);

        $this->get(route('project.create', ['projectType' => $projectType]))->assertStatus(200);

        Livewire::test(ProjectForm::class, ['projectType' => $projectType])->assertStatus(200);
    }

    #[Test]
    public function project_edit_loads_successfully_for_authenticated_users()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->owner($user)->create();
        $projectType = $project->projectType;
        $this->actingAs($user);

        $this->get(route('project.edit', ['projectType' => $projectType, 'project' => $project]))->assertStatus(200);

        Livewire::test(ProjectForm::class, ['projectType' => $projectType, 'project' => $project])->assertStatus(200);
    }

    #[Test]
    public function project_version_create_loads_successfully_for_authenticated_users()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->owner($user)->create();
        $projectType = $project->projectType;
        $this->actingAs($user);

        $this->get(route('project.version.create', ['projectType' => $projectType, 'project' => $project]))->assertStatus(200);

        Livewire::test(ProjectVersionForm::class, ['projectType' => $projectType, 'project' => $project])->assertStatus(200);
    }

    #[Test]
    public function project_version_edit_loads_successfully_for_authenticated_users()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->owner($user)->create();
        $projectType = $project->projectType;
        $version = ProjectVersion::factory()->create([
            'project_id' => $project->id,
            'version' => '1.0.0'
        ]);
        $this->actingAs($user);

        $this->get(route('project.version.edit', ['projectType' => $projectType, 'project' => $project, 'version_key' => $version->version]))->assertStatus(200);

        Livewire::test(ProjectVersionForm::class, ['projectType' => $projectType, 'project' => $project, 'version' => $version])->assertStatus(200);
    }

    #[Test]
    public function admin_dashboard_redirects_non_admin_users()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->get('/admin')->assertStatus(403);
    }

    #[Test]
    public function admin_users_redirects_non_admin_users()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->get('/admin/users')->assertStatus(403);
    }

    #[Test]
    public function admin_projects_redirects_non_admin_users()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->get('/admin/projects')->assertStatus(403);
    }

    #[Test]
    public function admin_site_redirects_non_admin_users()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->get('/admin/site')->assertStatus(403);
    }

    #[Test]
    public function admin_dashboard_loads_successfully_for_admin_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/admin')->assertStatus(200);
    }

    #[Test]
    public function admin_users_loads_successfully_for_admin_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/admin/users')->assertStatus(200);
    }

    #[Test]
    public function admin_projects_loads_successfully_for_admin_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/admin/projects')->assertStatus(200);
    }

    #[Test]
    public function admin_site_loads_successfully_for_admin_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/admin/site')->assertStatus(200);
    }
}
