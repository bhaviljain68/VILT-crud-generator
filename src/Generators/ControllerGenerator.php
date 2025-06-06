<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use artisanalbyte\VILTCrudGenerator\Utils\ValidationBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Generates the Inertia controller for CRUD operations,
 * injecting inline validation when form-request is disabled and
 * optionally applying the export trait.
 */
class ControllerGenerator implements GeneratorInterface
{
  protected Filesystem $files;
  protected StubRenderer $renderer;

  public function __construct(Filesystem $files, StubRenderer $renderer)
  {
    $this->files    = $files;
    $this->renderer = $renderer;
  }

  public function generate(CrudContext $context): array
  {
    $path = $context->paths['controllerPath'];
    $generated = [];
    if ($this->files->exists($path) && ! $context->options['force']) {
      return $generated;
    }
    $fields = $fields = $context->columnFilter->filterSystem($context->fields);

    // Prepare inline validation for store and update
    $validationStore  = '';
    $validationUpdate = '';
    if (! $context->options['formRequest']) {
      $storeConfig  = ValidationBuilder::buildRules(
        $fields,
        $context->tableName,
        $context->modelVar,
        false,
        'store'
      );
      $updateConfig = ValidationBuilder::buildRules(
        $fields,
        $context->tableName,
        $context->modelVar,
        false,
        'update'
      );
      $messagesConfig = ValidationBuilder::buildMessages(
        $fields,
      );

      $validationStore  = "\t\t\$request->validate({$storeConfig['rules']}, {$messagesConfig});\n" .
        (empty($storeConfig['attributes']) ? '' : "\t\t// custom attributes\n\t\t");
      $validationUpdate = "\t\t\$request->validate({$updateConfig['rules']}, {$messagesConfig});\n" .
        (empty($updateConfig['attributes']) ? '' : "\t\t// custom attributes\n\t\t");
    }

    // Remove export trait application

    $resourceImports = $context->options['resourceCollection']
      ? "use App\\Http\\Resources\\{$context->modelName}Resource;\nuse App\\Http\\Resources\\{$context->modelName}Collection;"
      : '';
    $indexResourceCollection = $context->options['resourceCollection']
      ? "new {$context->modelName}Collection($" . $context->modelPluralVar . ")"
      : "$" . $context->modelPluralVar;
    $showResource = $context->options['resourceCollection']
      ? "new {$context->modelName}Resource($" . $context->modelVar . ")"
      : "$" . $context->modelVar;
    $editResource = $showResource;

    $separate = $context->options['separateRequestFiles'] ?? false;
    $useFormRequestsImports = '';
    $storeRequestParam = '';
    $updateRequestParam = '';
    if ($context->options['formRequest']) {
      if ($separate) {
        $useFormRequestsImports = "use {$context->paths['requestNamespace']}\\{$context->modelName}StoreRequest;\n"
          . "use {$context->paths['requestNamespace']}\\{$context->modelName}UpdateRequest;";
        $storeRequestParam = "{$context->modelName}StoreRequest \$request";
        $updateRequestParam = "{$context->modelName}UpdateRequest \$request";
      } else {
        $useFormRequestsImports = "use {$context->paths['requestNamespace']}\\{$context->modelName}Request;";
        $storeRequestParam = "{$context->modelName}Request \$request";
        $updateRequestParam = "{$context->modelName}Request \$request";
      }
    } else {
      $storeRequestParam = $updateRequestParam = "Request \$request";
    }

    $importRequestClass = '';
    if ($context->options['formRequest']) {
      $importRequestClass = $useFormRequestsImports;
    } else {
      $importRequestClass = 'use Illuminate\\Http\\Request;';
    }

    $replacements = [
      'namespace'         => $context->paths['controllerNamespace'],
      'model'             => $context->modelName,
      'modelVar'          => $context->modelVar,
      'modelPlural'       => $context->modelPlural,
      'modelPluralVar'    => $context->modelPluralVar,
      'table'             => $context->tableName,
      'validationStore'   => $validationStore,
      'validationUpdate'  => $validationUpdate,
      'route'             => $context->modelPluralVar,
      'useFormRequestsImports'   => $useFormRequestsImports,
      'storeRequestParam'        => $storeRequestParam,
      'updateRequestParam'       => $updateRequestParam,
      'validateStoreData'        => $context->options['formRequest']
        ? '$request->validated()'
        : '$request->validate(' . $storeConfig['rules'] . ',' . $messagesConfig . ')',
      'validateUpdateData'       => $context->options['formRequest']
        ? '$request->validated()'
        : '$request->validate(' . $updateConfig['rules'] . ',' . $messagesConfig . ')',
      'resourceImports' => $resourceImports,
      'indexResourceCollection' => $indexResourceCollection,
      'showResource' => $showResource,
      'editResource' => $editResource,
      'importRequestClass' => $importRequestClass,
    ];


    $stub = $this->renderer->render('controller.stub', $replacements);
    $this->files->ensureDirectoryExists(dirname($path));
    $this->files->put($path, $stub);
    $path = Str::of($path)
      ->replace('\\', '/')
      ->after('/app')
      ->prepend('app')
      ->toString();
    $generated[] = "✅ " . $context->modelName . "Controller Generated : $path 😎";
    return $generated;
  }
}
