<?php

namespace artisanalbyte\VILTCrudGenerator\Commands;

use Illuminate\Console\Command;
use artisanalbyte\VILTCrudGenerator\Context\CrudContextBuilder;
use artisanalbyte\VILTCrudGenerator\Generators\ModelGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ControllerGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ViewGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\FormRequestGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\RouteGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ResourceGenerator;

class CrudGeneratorCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'vilt-crud:generate
                            {name* : The model/table name(s) to generate. Separate multiple names with a space or comma (e.g. User Project or User,Project)}
                            {--force : Overwrite existing files}
                            {--form-request : Generate FormRequest classes}
                            {--resource-collection : Generate Resource and ResourceCollection classes}
                            {--no-ts : Generate Vue pages without TypeScript}
                            {--separate-form-requests : Generate separate Store/Update request files}
                            {--single-form-request : Generate a single request file for both store/update}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate VILT CRUD scaffold (Model, Controller, Vue pages, FormRequests)';

  public function handle()
  {
    // Support multiple names (space or comma separated)
    $rawNames = $this->argument('name');
    $names = [];
    foreach ($rawNames as $raw) {
      foreach (preg_split('/[ ,]+/', $raw) as $name) {
        $name = trim($name);
        if ($name !== '') {
          $names[] = $name;
        }
      }
    }
    foreach ($names as $name) {

      // Build the context DTO from input, but override the name argument
      $input = clone $this->input;
      $input->setArgument('name', $name);
      $ctx = app(CrudContextBuilder::class)->build($input);

      // Core generators
      $generators = [
        ModelGenerator::class,
        ControllerGenerator::class,
        ViewGenerator::class,
        RouteGenerator::class,
      ];

      if ($ctx->options['formRequest']) {
        $generators[] = FormRequestGenerator::class;
      }
      // Resource/Collection generation (optional)
      if ($ctx->options['resourceCollection']) {
        $generators[] = ResourceGenerator::class;
      }

      $allGeneratedFiles = [];
      // Execute each generator and collect generated files
      foreach ($generators as $genClass) {
        $result = app($genClass)->generate($ctx);
        if (is_array($result)) {
          $allGeneratedFiles = array_merge($allGeneratedFiles, $result);
        }
      }

      // Remove duplicates and normalize paths
      $allGeneratedFiles = array_unique($allGeneratedFiles);
      $allGeneratedFiles = array_map(function ($path) {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
      }, $allGeneratedFiles);

      if (!empty($allGeneratedFiles)) {
        $this->info("Generated files:");
        foreach ($allGeneratedFiles as $file) {
          $this->line(" - {$file}");
        }
      }
      $this->info("🎉 VILT CRUD scaffolding generated successfully for {$name}.");
    }
  }
}
