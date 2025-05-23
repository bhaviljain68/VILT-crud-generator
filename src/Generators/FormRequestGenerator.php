<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use artisanalbyte\VILTCrudGenerator\Utils\ValidationBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Generates a FormRequest class for CRUD validation when form-request option is enabled.
 */
class FormRequestGenerator implements GeneratorInterface
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
        if (! $context->options['formRequest']) {
            return;
        }

        $path = $context->paths['request_path'];
        if ($this->files->exists($path) && ! $context->options['force']) {
            return;
        }

        // Rules and attributes for store
        $storeConfig    = ValidationBuilder::buildRules(
            $context->fields,
            $context->tableName,
            $context->modelVar,
            true,
            'store'
        );
        $storeMessages  = ValidationBuilder::buildMessages(
            $context->fields,
            $context->modelName,
            'store'
        );

        // Rules and attributes for update
        $updateConfig   = ValidationBuilder::buildRules(
            $context->fields,
            $context->tableName,
            $context->modelVar,
            true,
            'update'
        );
        $updateMessages = ValidationBuilder::buildMessages(
            $context->fields,
            $context->modelName,
            'update'
        );

        $replacements = [
            'namespace'        => $context->paths['request_namespace'],
            'class'            => $context->modelName . 'Request',
            'rulesStore'       => $storeConfig['rules'],
            'attributesStore'  => $storeConfig['attributes'],
            'messagesStore'    => $storeMessages,
            'rulesUpdate'      => $updateConfig['rules'],
            'attributesUpdate' => $updateConfig['attributes'],
            'messagesUpdate'   => $updateMessages,
        ];

        $stub = $this->renderer->render('form-request.stub', $replacements);
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);
    }
}
