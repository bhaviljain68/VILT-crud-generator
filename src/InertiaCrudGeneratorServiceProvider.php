<?php

namespace artisanalbyte\InertiaCrudGenerator;

use Illuminate\Support\ServiceProvider;
use artisanalbyte\InertiaCrudGenerator\Commands\CrudGeneratorCommand;

class InertiaCrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge package configuration with application config
        $this->mergeConfigFrom(__DIR__ . '/../config/inertia-crud-generator.php', 'inertia-crud-generator');

        // Register the console command for Artisan
        $this->commands([
            CrudGeneratorCommand::class,
        ]);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        // Publish the config file to the application's config directory
        $this->publishes([
            __DIR__ . '/../config/inertia-crud-generator.php' => config_path('inertia-crud-generator.php'),
        ], 'inertia-crud-generator-config');

        // Publish stub templates to allow user customization
        $this->publishes([
            __DIR__ . '/../stubs' => resource_path('stubs/inertia-crud-generator'),
        ], 'inertia-crud-generator-stubs');

        // (Optional) Publish a lang file stub if provided
        // $this->publishes([...], 'inertia-crud-generator-lang');
    }
}
