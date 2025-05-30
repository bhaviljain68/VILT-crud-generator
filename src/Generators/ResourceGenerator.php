<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\ColumnFilter;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use Illuminate\Filesystem\Filesystem;

/**
 * Generates an API Resource and Resource Collection for the model.
 */
class ResourceGenerator implements GeneratorInterface
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
        if (! $context->options['resourceCollection']) {
            return $generated;
        }
        $force        = $context->options['force'];
        $modelName    = $context->modelName;
        $namespace    = $context->paths['resourceNamespace'];

        // --- Resource class ---
        $resourceClass = $modelName . 'Resource';
        $resourcePath  = app_path("Http/Resources/{$resourceClass}.php");

        if ($force || ! $this->files->exists($resourcePath)) {
            $fieldsCode = $this->generateResourceFields($context->columnFilter->filterAll($context->fields));

            $stub = $this->renderer->render('resource.stub', [
                'namespace'  => $namespace,
                'class'  => $resourceClass,
                'fields' => $fieldsCode,
            ]);
            $this->files->ensureDirectoryExists(dirname($resourcePath));
            $this->files->put($resourcePath, $stub);
            $generated[] = $resourcePath;
        }

        // --- Collection class ---
        $collectionClass = $modelName . 'Collection';
        $collectionPath  = app_path("Http/Resources/{$collectionClass}.php");

        if ($force || ! $this->files->exists($collectionPath)) {
            $stub = $this->renderer->render('resource-collection.stub', [
                'namespace' => $namespace,
                'class' => $collectionClass,
                'resourceClass' => $resourceClass,
            ]);
            $this->files->ensureDirectoryExists(dirname($collectionPath));
            $this->files->put($collectionPath, $stub);
            $generated[] = $collectionPath;
        }
        return $generated;
    }

    /**
     * Build each field line for the toArray() method.
     */
    protected function generateResourceFields(array $fields): string
    {

        // Filter system & sensitive Columns

        $lines = [];
        foreach ($fields as $col) {
            $name = $col['column'];
            $lines[] = "\t\t\t'{$name}' => \$model->{$name},";
        }

        return implode("\n", $lines);
    }
}
