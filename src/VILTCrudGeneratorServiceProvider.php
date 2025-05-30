<?php

namespace artisanalbyte\VILTCrudGenerator;

use Illuminate\Support\ServiceProvider;
use artisanalbyte\VILTCrudGenerator\Commands\CrudGeneratorCommand;
use artisanalbyte\VILTCrudGenerator\Commands\PublishViltCrudCommand;

class VILTCrudGeneratorServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register()
  {
    // Merge package configuration with application config
    $this->mergeConfigFrom(__DIR__ . '/../config/vilt-crud-generator.php', 'vilt-crud-generator');
  }

  /**
   * Bootstrap any package services.
   */
  public function boot()
  {
    if ($this->app->runningInConsole()) {
      $this->commands([
        CrudGeneratorCommand::class,
        PublishViltCrudCommand::class,
      ]);
    }

    // Config + Vue Components + inertia-types.ts
    $this->publishes([
      __DIR__ . '/../config/vilt-crud-generator.php' => config_path('vilt-crud-generator.php'),
      __DIR__ . '/../vue-components/ui/input/NumberInput.vue' => resource_path('js/components/ui/input/NumberInput.vue'),
      __DIR__ . '/../vue-components/ui/input/DateInput.vue' => resource_path('js/components/ui/input/DateInput.vue'),
      __DIR__ . '/../vue-components/ui/button/BackButton.vue' => resource_path('js/components/ui/button/BackButton.vue'),
      __DIR__ . '/../resources/js/types/inertia.d.ts' => resource_path('js/types/inertia.d.ts'),
    ], 'vilt-crud-default');

    // Stubs
    $this->publishes([
      __DIR__ . '/../stubs' => resource_path('stubs/vilt-crud-generator'),
    ], 'vilt-crud-stubs');

    // All (everything)
    $this->publishes([
      __DIR__ . '/../config/vilt-crud-generator.php' => config_path('vilt-crud-generator.php'),
      __DIR__ . '/../stubs' => resource_path('stubs/vilt-crud-generator'),
      __DIR__ . '/../vue-components/ui/input/NumberInput.vue' => resource_path('js/components/ui/input/NumberInput.vue'),
      __DIR__ . '/../vue-components/ui/input/DateInput.vue' => resource_path('js/components/ui/input/DateInput.vue'),
      __DIR__ . '/../vue-components/ui/button/BackButton.vue' => resource_path('js/components/ui/button/BackButton.vue'),
      __DIR__ . '/../resources/js/types/inertia-types.ts' => resource_path('js/types/inertia.d.ts'),
    ], 'vilt-crud-all');
  }
}
