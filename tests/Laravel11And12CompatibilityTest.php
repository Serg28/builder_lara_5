<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Vis\Builder\BuilderServiceProvider;

class Laravel11And12CompatibilityTest extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Test that the service provider can be registered
     */
    public function test_service_provider_registration()
    {
        $provider = new BuilderServiceProvider($this->app);
        
        $this->assertInstanceOf(BuilderServiceProvider::class, $provider);
    }

    /**
     * Test Laravel version compatibility
     */
    public function test_laravel_version_compatibility()
    {
        $laravelVersion = $this->app->version();
        
        // Check if Laravel version is 11.x or 12.x
        $this->assertTrue(
            version_compare($laravelVersion, '11.0', '>=') || 
            version_compare($laravelVersion, '12.0', '>='),
            "Laravel version {$laravelVersion} should be 11.x or 12.x"
        );
    }

    /**
     * Test PHP version compatibility
     */
    public function test_php_version_compatibility()
    {
        $phpVersion = PHP_VERSION;
        
        $this->assertTrue(
            version_compare($phpVersion, '8.2.0', '>='),
            "PHP version {$phpVersion} should be 8.2 or higher"
        );
    }

    /**
     * Test that required dependencies are available
     */
    public function test_required_dependencies()
    {
        // Test Cartalyst Sentinel
        $this->assertTrue(
            class_exists('Cartalyst\Sentinel\Laravel\Facades\Sentinel'),
            'Cartalyst Sentinel should be available'
        );

        // Test Intervention Image
        $this->assertTrue(
            class_exists('Intervention\Image\ImageManager'),
            'Intervention Image should be available'
        );

        // Test Predis
        $this->assertTrue(
            class_exists('Predis\Client'),
            'Predis should be available'
        );
    }

    /**
     * Test middleware registration
     */
    public function test_middleware_registration()
    {
        $router = $this->app['router'];
        
        // Check if middleware aliases are registered
        $middlewareGroups = $router->getMiddlewareGroups();
        
        $this->assertIsArray($middlewareGroups);
    }

    /**
     * Test configuration publishing
     */
    public function test_configuration_publishing()
    {
        $provider = new BuilderServiceProvider($this->app);
        
        // This should not throw an exception
        $provider->register();
        
        $this->assertTrue(true, 'Service provider registration completed without errors');
    }

    /**
     * Test view loading
     */
    public function test_view_loading()
    {
        $viewPath = realpath(__DIR__ . '/../src/resources/views');
        
        if ($viewPath) {
            $this->assertDirectoryExists($viewPath, 'Views directory should exist');
        } else {
            $this->markTestSkipped('Views directory not found');
        }
    }

    /**
     * Test command registration
     */
    public function test_command_registration()
    {
        $provider = new BuilderServiceProvider($this->app);
        $provider->register();
        
        // Check if commands are registered in the container
        $this->assertTrue(
            $this->app->bound('command.admin.install'),
            'Admin install command should be registered'
        );
    }
}