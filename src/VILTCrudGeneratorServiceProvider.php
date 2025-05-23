<?php

namespace artisanalbyte\VILTCrudGenerator;

use Illuminate\Support\ServiceProvider;
use artisanalbyte\VILTCrudGenerator\Commands\CrudGeneratorCommand;
use artisanalbyte\VILTCrudGenerator\Commands\InstallInertiaCrudCommand;

class VILTCrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge package configuration with application config
        $this->mergeConfigFrom(__DIR__ . '/../config/inertia-crud-generator.php', 'inertia-crud-generator');

        // Register the console command for Artisan
        // $this->commands([
        //     CrudGeneratorCommand::class, // Generate Command
        //     InstallInertiaCrudCommand::class, // install command
        // ]);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGeneratorCommand::class,
                InstallInertiaCrudCommand::class,  // ensure this is present
            ]);
        }
        $this->publishes([
            // Publish the config file to the application's config directory
            __DIR__ . '/../config/inertia-crud-generator.php' => config_path('inertia-crud-generator.php'),
            // Publish stub templates to allow user customization
            __DIR__ . '/../stubs' => resource_path('stubs/inertia-crud-generator'),
            // Publish our wrapper Vue components:
            __DIR__ . '/../vue-components/ui/input/NumberInput.vue'
            => resource_path('js/components/ui/input/NumberInput.vue'),
            __DIR__ . '/../vue-components/ui/input/DateInput.vue'
            => resource_path('js/components/ui/input/DateInput.vue'),
            // Publish Export Trait
            __DIR__.'/../stubs/traits/has-export.stub' => app_path('Http/Traits/HasExport.php'),
        ], 'inertia-crud-generator');
    }
}
