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

  public function generate(CrudContext $context): array
  {
        $generated = [];
        if (! $context->options['formRequest']) {
            return $generated;
        }
        $separate = $context->options['separateRequestFiles'] ?? false;
        if ($separate) {
            foreach (['store', 'update'] as $action) {
                $path = $action === 'store' ? $context->paths['storeRequestPath'] : $context->paths['updateRequestPath'];
                if ($this->files->exists($path) && ! $context->options['force']) {
                    continue;
                }
                $fields = $context->columnFilter->filterSystem($context->columnFilter->filterId($context->fields));
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
                $path = Str::of($path)
                    ->replace('\\', '/')
                    ->after('/app')
                    ->prepend('app')
                    ->toString();
                $generated[] = "✅ {$replacements['class']} Generated : $path 😎";
            }
        } else {
            // Single request file for both store/update
            $path = $context->paths['requestPath'];
            if ($this->files->exists($path) && ! $context->options['force']) {
                return $generated;
            }
            $fields = $context->columnFilter->filterSystem($context->columnFilter->filterId($context->fields));
            $config    = ValidationBuilder::buildRules(
                $fields,
                $context->tableName,
                $context->modelVar,
                true,
                'store' // Use store rules as default
            );
            $messages  = ValidationBuilder::buildMessages(
                $fields,
                $context->modelName,
                'store'
            );
            $replacements = [
                'namespace'        => $context->paths['requestNamespace'],
                'class'            => $context->modelName . 'Request',
                'rules'            => $config['rules'],
                'attributes'       => $config['attributes'],
                'messages'    => $messages,
            ];
            $stub = $this->renderer->render('form-request.stub', $replacements);
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $stub);
            $path = Str::of($path)
                ->replace('\\', '/')
                ->after('/app')
                ->prepend('app')
                ->toString();
            $generated[] = "✅ {$replacements['class']} Generated : $path 😎";
        }
        return $generated;
    }
}
