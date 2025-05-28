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

        foreach (['store', 'update'] as $action) {
            $path = $action === 'store' ? $context->paths['storeRequestPath'] : $context->paths['updateRequestPath'];
            // $path = $context->paths['request_path'];
            if ($this->files->exists($path) && ! $context->options['force']) {
                return;
            }

            // Filter out system fields
            $fields = $context->columnFilter->filterSystem($context->columnFilter->filterId($context->fields));

            // Rules and attributes for store
            $config    = ValidationBuilder::buildRules(
                $fields,
                $context->tableName,
                $context->modelVar,
                true,
                $action
            );
            $messages  = ValidationBuilder::buildMessages(
                $fields,
                $context->modelName,
                $action
            );
            $replacements = [
                'namespace'        => $context->paths['requestNamespace'],
                'class'            => $context->modelName . ucwords($action) . 'Request',
                'rules'            => $config['rules'],
                'attributes'       => $config['attributes'],
                'messages'    => $messages,
            ];

            $stub = $this->renderer->render('form-request.stub', $replacements);
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $stub);
        }
    }
}
