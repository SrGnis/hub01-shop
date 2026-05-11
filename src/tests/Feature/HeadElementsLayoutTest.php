<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class HeadElementsLayoutTest extends TestCase
{
    public function test_admin_layout_does_not_include_head_elements()
    {
        config(['head.elements' => [
            ['key' => 'test-meta', 'type' => 'meta', 'attrs' => ['name' => 'head-elements-layout-admin-only', 'content' => 'head-elements-layout-admin-content']]
        ]]);

        $result = Blade::render('<x-layouts.admin />');
        $this->assertStringNotContainsString('head-elements-layout-admin-only', $result);
        $this->assertStringNotContainsString('head-elements-layout-admin-content', $result);
    }

    public function test_auth_layout_does_not_include_head_elements()
    {
        config(['head.elements' => [
            ['key' => 'test-meta', 'type' => 'meta', 'attrs' => ['name' => 'head-elements-layout-auth-only', 'content' => 'head-elements-layout-auth-content']]
        ]]);

        $result = Blade::render('<x-layouts.auth />');
        $this->assertStringNotContainsString('head-elements-layout-auth-only', $result);
        $this->assertStringNotContainsString('head-elements-layout-auth-content', $result);
    }

    public function test_main_layout_includes_head_elements()
    {
        config(['head.elements' => [
            ['key' => 'test-meta', 'type' => 'meta', 'attrs' => ['name' => 'head-elements-layout-app-only', 'content' => 'head-elements-layout-app-content']]
        ]]);

        $result = Blade::render('<x-layouts.app />');
        $this->assertStringContainsString('head-elements-layout-app-only', $result);
        $this->assertStringContainsString('head-elements-layout-app-content', $result);
    }
}
