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
                            {name?* : The model/table name(s) to generate. Separate multiple names with a space or comma (e.g. User Project or User,Project)}
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
    // Try to get the argument, but if not present, call gatherArgs
    try {
      $rawNames = $this->argument('name');
      if (empty($rawNames) || (is_array($rawNames) && count($rawNames) === 1 && trim($rawNames[0]) === '')) {
        return $this->gatherArgs();
      }
    } catch (\Exception $e) {
      return $this->gatherArgs();
    }
    // If we have args, run generate
    $this->generate();
  }

  protected function gatherArgs()
  {
    // Prompt for model/table name(s)
    $modelInput = $this->ask('Enter model/table name(s) (comma or space separated)');
    $rawNames = [$modelInput];
    $this->input->setArgument('name', $rawNames);
    // Prompt for options using choice for better UX
    $options = [
      'force' => $this->choice('Overwrite existing files?', ['No', 'Yes'], 0) === 'Yes',
      'form-request' => $this->choice('Generate FormRequest classes?', ['No', 'Yes'], 1) === 'Yes',
    ];
    // Ask separate-form-requests / single-form-request immediately after form-request
    $separateRequestFiles = config('vilt-crud-generator.separateRequestFiles', false);
    if (!$separateRequestFiles) {
      $options['separate-form-requests'] = $this->choice('Generate separate Store/Update request files?', ['No', 'Yes'], 0) === 'Yes';
      $options['single-form-request'] = false;
    } else {
      $options['single-form-request'] = $this->choice('Generate a single request file for both store/update?', ['No', 'Yes'], 0) === 'Yes';
      $options['separate-form-requests'] = false;
    }
    $options['resource-collection'] = $this->choice('Generate Resource and ResourceCollection classes?', ['No', 'Yes'], 0) === 'Yes';
    // Ask for TypeScript (reverse logic for --no-ts)
    $options['no-ts'] = !($this->choice('Generate Vue pages with TypeScript?', ['No', 'Yes'], 1) === 'Yes');
    foreach ($options as $key => $value) {
      $this->input->setOption($key, $value);
    }
    $this->generate();
  }

  protected function generate()
  {
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

      // Output results

      $this->info("============================================================================");
      $this->info("                   ⚙️ Generating CRUD for {$name} ⚙️       ");
      $this->info("============================================================================");

      if (!empty($allGeneratedFiles)) {
        $this->info("Generated files:");
        foreach ($allGeneratedFiles as $file) {
          $this->line(" - {$file}");
        }
      }
      $this->info("============================================================================");
      $this->info("        🎉 CRUD scaffolding for {$name} generated successfully 🎉");
      $this->info("============================================================================");
      $this->info(" \n ");
    }
  }
}
