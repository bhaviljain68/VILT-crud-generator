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
        $path = $context->paths['controller_path'];

        if ($this->files->exists($path) && ! $context->options['force']) {
            return;
        }

        // Prepare inline validation for store and update
        $validationStore  = '';
        $validationUpdate = '';
        if (! $context->options['formRequest']) {
            $storeConfig  = ValidationBuilder::buildRules(
                $context->fields,
                $context->tableName,
                $context->modelVar,
                false,
                'store'
            );
            $updateConfig = ValidationBuilder::buildRules(
                $context->fields,
                $context->tableName,
                $context->modelVar,
                false,
                'update'
            );
            $messagesConfig = ValidationBuilder::buildMessages(
                $context->fields
            );

            $validationStore  = "        \$request->validate({$storeConfig['rules']}, {$messagesConfig});\n" .
                (empty($storeConfig['attributes']) ? '' : "        // custom attributes\n        ");
            $validationUpdate = "        \$request->validate({$updateConfig['rules']}, {$messagesConfig});\n" .
                (empty($updateConfig['attributes']) ? '' : "        // custom attributes\n        ");
        }

        // Optional export trait application
        $exportImport     = $context->options['export']
            ? 'use App\\Http\\Traits\\HasExport;'
            : '';
        $exportTraitBlock = $context->options['export']
            ? "    use HasExport;\n    protected string \\\$modelClass = {$context->paths['model_namespace']}::class;\n"
            : '';

        $replacements = [
            'namespace'         => $context->paths['controller_namespace'],
            'exportImport'      => $exportImport,
            'exportTraitBlock'  => $exportTraitBlock,
            'model'             => $context->modelName,
            'model_var'         => $context->modelVar,
            'model_plural_var'  => $context->modelPluralVar,
            'request_namespace' => $context->paths['request_namespace'],
            'table'             => $context->tableName,
            'validationStore'   => $validationStore,
            'validationUpdate'  => $validationUpdate,
        ];

        $stub = $this->renderer->render('controller.stub', $replacements);
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);
    }
}
