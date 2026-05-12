<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HeadElementsTest extends TestCase
{
    public function test_config_normalization_with_invalid_json()
    {
        config(['head.elements' => []]);
        config(['head.json' => 'invalid-json']);

        $result = require config_path('head.php');
        $this->assertArrayHasKey('elements', $result);
        $this->assertEquals([], $result['elements']);
    }

    public function test_config_normalization_with_non_array_json()
    {
        config(['head.elements' => []]);
        config(['head.json' => '"not-an-array"']);

        $result = require config_path('head.php');
        $this->assertArrayHasKey('elements', $result);
        $this->assertEquals([], $result['elements']);
    }

    public function test_config_normalization_with_valid_meta_element()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-meta","type":"meta","attrs":{"name":"description","content":"Test"}}]']);
        config(['head.allowed_script_domains' => []]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertEquals('test-meta', $result['elements'][0]['key']);
        $this->assertEquals('meta', $result['elements'][0]['type']);
        $this->assertEquals(['name' => 'description', 'content' => 'Test'], $result['elements'][0]['attrs']);
    }

    public function test_config_normalization_with_invalid_meta_element()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-meta","type":"meta","attrs":{"http-equiv":"refresh"}}]']);

        $result = require config_path('head.php');
        $this->assertEquals([], $result['elements']);
    }

    public function test_config_normalization_with_valid_link_element()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-link","type":"link","attrs":{"rel":"stylesheet","href":"/css/style.css"}}]']);
        config(['head.allowed_script_domains' => []]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertEquals('test-link', $result['elements'][0]['key']);
        $this->assertEquals('link', $result['elements'][0]['type']);
        $this->assertEquals(['rel' => 'stylesheet', 'href' => '/css/style.css'], $result['elements'][0]['attrs']);
    }

    public function test_config_normalization_with_valid_script_element()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-script","type":"script","attrs":{"src":"https://example.com/script.js"}}]']);
        config(['head.allowed_script_domains' => ['example.com']]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertEquals('test-script', $result['elements'][0]['key']);
        $this->assertEquals('script', $result['elements'][0]['type']);
        $this->assertEquals(['src' => 'https://example.com/script.js'], $result['elements'][0]['attrs']);
    }

    public function test_config_normalization_with_disallowed_script_domain()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-script","type":"script","attrs":{"src":"https://disallowed.com/script.js"}}]']);
        config(['head.allowed_script_domains' => ['example.com']]);

        $result = require config_path('head.php');
        $this->assertEquals([], $result['elements']);
    }

    public function test_config_normalization_with_unknown_attributes()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-meta","type":"meta","attrs":{"name":"test","content":"value","unknown":"value"}}]']);
        config(['head.allowed_script_domains' => []]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertEquals(['name' => 'test', 'content' => 'value'], $result['elements'][0]['attrs']);
    }

    public function test_config_normalization_with_disabled_element()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-meta","type":"meta","attrs":{"name":"test","content":"value"},"enabled":false}]']);
        config(['head.allowed_script_domains' => []]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertFalse($result['elements'][0]['enabled']);
    }

    public function test_config_normalization_with_relative_link_href()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"test-link","type":"link","attrs":{"rel":"icon","href":"/favicon.ico"}}]']);
        config(['head.allowed_script_domains' => []]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertEquals('/favicon.ico', $result['elements'][0]['attrs']['href']);
    }

    public function test_config_normalization_with_script_data_attributes()
    {
        config(['head.elements' => []]);
        config(['head.json' => '[{"key":"umami","type":"script","attrs":{"src":"https://stats.example.com/script.js","defer":true,"data-website-id":"abc-123"}}]']);
        config(['head.allowed_script_domains' => ['stats.example.com']]);
        config(['enable_custom_head_elements' => true]);

        $result = require config_path('head.php');
        $this->assertCount(1, $result['elements']);
        $this->assertArrayHasKey('data-website-id', $result['elements'][0]['attrs']);
        $this->assertEquals('abc-123', $result['elements'][0]['attrs']['data-website-id']);
        $this->assertTrue($result['elements'][0]['attrs']['defer']);
    }
}
