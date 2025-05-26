<?php

namespace artisanalbyte\VILTCrudGenerator;

use Illuminate\Support\ServiceProvider;
use artisanalbyte\VILTCrudGenerator\Commands\CrudGeneratorCommand;
use artisanalbyte\VILTCrudGenerator\Commands\InstallViltCrudCommand;

class VILTCrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge package configuration with application config
        $this->mergeConfigFrom(__DIR__ . '/../config/vilt-crud-generator.php', 'vilt-crud-generator');

        // $this->app->bind(\Doctrine\DBAL\Connection::class, function ($app) {
        //     return $app['db']->connection()->getDoctrineConnection();
        // });

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
            __DIR__ . '/../config/vilt-crud-generator.php' => config_path('vilt-crud-generator.php'),
            // Publish stub templates to allow user customization
            __DIR__ . '/../stubs' => resource_path('stubs/vilt-crud-generator'),
            // Publish our wrapper Vue components:
            __DIR__ . '/../vue-components/ui/input/NumberInput.vue'
            => resource_path('js/components/ui/input/NumberInput.vue'),
            __DIR__ . '/../vue-components/ui/input/DateInput.vue'
            => resource_path('js/components/ui/input/DateInput.vue'),
            // Publish Export Trait
            __DIR__ . '/../stubs/traits/has-export.stub' => app_path('Http/Traits/HasExport.php'),
        ], 'vilt-crud-generator');
    }
}
