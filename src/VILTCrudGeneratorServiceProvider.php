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
        $this->mergeConfigFrom(__DIR__ . '/../config/ViltCrudGenerator.php', 'vilt-crud-generator');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGeneratorCommand::class,
                InstallViltCrudCommand::class,
            ]);
        }

        // Config + Vue Components
        $this->publishes([
            __DIR__ . '/../config/ViltCrudGenerator.php' => config_path('ViltCrudGenerator.php'),
            __DIR__ . '/../vue-components/ui/input/NumberInput.vue' => resource_path('js/components/ui/input/NumberInput.vue'),
            __DIR__ . '/../vue-components/ui/input/DateInput.vue' => resource_path('js/components/ui/input/DateInput.vue'),
            __DIR__ . '/../vue-components/ui/button/BackButton.vue' => resource_path('js/components/ui/button/BackButton.vue'),
        ], 'vilt-crud-default');

        // Stubs
        $this->publishes([
            __DIR__ . '/../stubs' => resource_path('stubs/vilt-crud-generator'),
        ], 'vilt-crud-stubs');

        // Export Trait
        $this->publishes([
            __DIR__ . '/Export/HasExport.php' => app_path('Http/Traits/HasExport.php'),
        ], 'vilt-crud-export');

        // All (everything)
        $this->publishes([
            __DIR__ . '/../config/ViltCrudGenerator.php' => config_path('ViltCrudGenerator.php'),
            __DIR__ . '/../stubs' => resource_path('stubs/vilt-crud-generator'),
            __DIR__ . '/../vue-components/ui/input/NumberInput.vue' => resource_path('js/components/ui/input/NumberInput.vue'),
            __DIR__ . '/../vue-components/ui/input/DateInput.vue' => resource_path('js/components/ui/input/DateInput.vue'),
            __DIR__ . '/../vue-components/ui/button/BackButton.vue' => resource_path('js/components/ui/button/BackButton.vue'),
        ], 'vilt-crud-all');
    }
}
