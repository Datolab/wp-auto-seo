<?php
namespace Tests;

// tests/test-datolab-auto-seo-settings.php

require_once __DIR__ . '/../includes/settings.php';

use PHPUnit\Framework\TestCase;
use Datolab\AutoSEO\Datolab_Auto_SEO_Settings;

class Datolab_Auto_SEO_Settings_Test extends TestCase {

    protected function setUp(): void {
        // Set up the environment for testing
        // This can include setting up WordPress functions, etc.
        // For example, you might want to mock the WordPress functions used in your class.
        // You can use the WP_Mock library for this purpose.
    }

    public function testAddSettingsPage() {
        // Create an instance of the settings class
        $settings = new Datolab_Auto_SEO_Settings();

        // Call the method to add the settings page
        $settings->add_settings_page();

        // Check if the settings page was added correctly
        // You can use assertions to verify the expected behavior
        // For example, you might check if the page is in the admin menu
        $this->assertTrue(has_action('admin_menu', [$settings, 'add_settings_page']));
    }

    public function testRegisterSettings() {
        // Create an instance of the settings class
        $settings = new Datolab_Auto_SEO_Settings();

        // Call the method to register settings
        $settings->register_settings();

        // Check if the settings were registered correctly
        // You can use assertions to verify the expected behavior
        $this->assertNotEmpty(get_option('datolab_auto_seo_openai_api_key'));
        $this->assertNotEmpty(get_option('datolab_auto_seo_cohere_api_key'));
        $this->assertNotEmpty(get_option('datolab_auto_seo_anthropic_api_key'));
    }
    // Add more tests for other methods as needed
}


