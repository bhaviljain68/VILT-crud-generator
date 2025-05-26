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

            // Rules and attributes for store
            $config    = ValidationBuilder::buildRules(
                $context->fields,
                $context->tableName,
                $context->modelVar,
                true,
                $action
            );
            $messages  = ValidationBuilder::buildMessages(
                $context->fields,
                $context->modelName,
                $action
            );

            $replacements = [
                'namespace'        => $context->paths['request_namespace'],
                'class'            => $context->modelName . 'Request',
                'rules'            => $config['rules'],
                'attributes'       => $config['attributes'],
                'messagesStore'    => $messages,
            ];
            
            $stub = $this->renderer->render('form-request.stub', $replacements);
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $stub);
        }
    }
}
