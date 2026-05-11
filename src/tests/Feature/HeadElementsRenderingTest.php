<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class HeadElementsRenderingTest extends TestCase
{
    public function test_disabled_elements_are_not_rendered()
    {
        config(['head.elements' => [
            ['key' => 'test-meta', 'type' => 'meta', 'enabled' => false, 'attrs' => ['name' => 'test', 'content' => 'value']]
        ]]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringNotContainsString('test', $result);
        $this->assertStringNotContainsString('value', $result);
    }

    public function test_boolean_attributes_are_rendered_correctly()
    {
        config(['head.elements' => [
            ['key' => 'test-script', 'type' => 'script', 'attrs' => ['src' => 'https://example.com/script.js', 'async' => true]]
        ]]);
        config(['enable_custom_head_elements' => true]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringContainsString('<script', $result);
        $this->assertStringContainsString('src="https://example.com/script.js"', $result);
        $this->assertStringContainsString('async', $result);
    }

    public function test_scalar_attributes_are_rendered_and_escaped()
    {
        config(['head.elements' => [
            ['key' => 'test-meta', 'type' => 'meta', 'attrs' => ['name' => 'test', 'content' => 'value<>&"\'']]
        ]]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringContainsString('name="test"', $result);
        $this->assertStringContainsString('content="value&lt;&gt;&amp;&quot;&#039;"', $result);
    }

    public function test_meta_element_rendering()
    {
        config(['head.elements' => [
            ['key' => 'test-meta', 'type' => 'meta', 'attrs' => ['name' => 'description', 'content' => 'Test description']]
        ]]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringContainsString('<meta', $result);
        $this->assertStringContainsString('name="description"', $result);
        $this->assertStringContainsString('content="Test description"', $result);
    }

    public function test_link_element_rendering()
    {
        config(['head.elements' => [
            ['key' => 'test-link', 'type' => 'link', 'attrs' => ['rel' => 'stylesheet', 'href' => '/css/style.css']]
        ]]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringContainsString('<link', $result);
        $this->assertStringContainsString('rel="stylesheet"', $result);
        $this->assertStringContainsString('href="/css/style.css"', $result);
    }

    public function test_script_element_rendering()
    {
        config(['head.elements' => [
            ['key' => 'test-script', 'type' => 'script', 'attrs' => ['src' => 'https://example.com/script.js']]
        ]]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringContainsString('<script', $result);
        $this->assertStringContainsString('src="https://example.com/script.js"', $result);
        $this->assertStringContainsString('</script>', $result);
    }

    public function test_multiple_elements_rendering()
    {
        config(['head.elements' => [
            ['key' => 'meta1', 'type' => 'meta', 'attrs' => ['name' => 'description', 'content' => 'Desc']],
            ['key' => 'link1', 'type' => 'link', 'attrs' => ['rel' => 'icon', 'href' => '/favicon.ico']],
            ['key' => 'script1', 'type' => 'script', 'attrs' => ['src' => 'https://example.com/script.js']]
        ]]);

        $result = Blade::render('<x-head-elements />');
        $this->assertStringContainsString('name="description"', $result);
        $this->assertStringContainsString('content="Desc"', $result);
        $this->assertStringContainsString('rel="icon"', $result);
        $this->assertStringContainsString('href="/favicon.ico"', $result);
        $this->assertStringContainsString('src="https://example.com/script.js"', $result);
    }
}
