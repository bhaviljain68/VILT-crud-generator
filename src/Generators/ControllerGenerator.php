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

    public function generate(CrudContext $context): void
    {
        $path = $context->paths['controllerPath'];

        if ($this->files->exists($path) && ! $context->options['force']) {
            return;
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

        // Optional export trait application
        $exportImport     = $context->options['export']
            ? 'use App\\Http\\Traits\\HasExport;'
            : '';
        $exportTraitBlock = $context->options['export']
            ? "\tuse HasExport;\n\tprotected string \$modelClass = {$context->paths['modelNamespace']}\\{$context->modelName}::class;\n"
            : '';

        $replacements = [
            'namespace'         => $context->paths['controllerNamespace'],
            'model'             => $context->modelName,
            'modelVar'          => $context->modelVar,
            'modelPlural'       => $context->modelPlural,
            'modelPluralVar'    => $context->modelPluralVar,
            'exportImport'      => $exportImport,
            'exportTraitBlock'  => $exportTraitBlock,
            'table'             => $context->tableName,
            'validationStore'   => $validationStore,
            'validationUpdate'  => $validationUpdate,
            'route'             => $context->modelPluralVar,
            'useFormRequestsImports'   => $context->options['formRequest']
                ? "use {$context->paths['requestNamespace']}\\Store{$context->modelName}Request;\n"
                . "use {$context->paths['requestNamespace']}\\Update{$context->modelName}Request;"
                : '',
            'storeRequestParam'        => $context->options['formRequest']
                ? "Store{$context->modelName}Request \$request"
                : "Request \$request",
            'updateRequestParam'       => $context->options['formRequest']
                ? "Update{$context->modelName}Request \$request"
                : "Request \$request",
            'validateStoreData'        => $context->options['formRequest']
                ? '$request->validated()'
                : '$request->validate(' . $storeConfig['rules'] . ',' . $messagesConfig . ')',
            'validateUpdateData'       => $context->options['formRequest']
                ? '$request->validated()'
                : '$request->validate(' . $updateConfig['rules'] . ',' . $messagesConfig . ')',
        ];

        $stub = $this->renderer->render('controller.stub', $replacements);
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);
    }
}
