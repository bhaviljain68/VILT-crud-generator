<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use Illuminate\Filesystem\Filesystem;

/**
 * Generates the Eloquent model for the CRUD.
 */
class ModelGenerator implements GeneratorInterface
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
        $path = $context->paths['modelPath'];

        // Don't overwrite unless --force
        if ($this->files->exists($path) && ! $context->options['force']) {
            return;
        }

        $stub = $this->renderer->render('model.stub', [
            
            'model'       => $context->modelName,
            'table'       => $context->tableName,
            'fillable'    => $this->buildFillable($context->fields),
            'casts'       => $this->buildCasts($context->fields),
        ]);

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);
    }

    protected function buildFillable(array $fields): string
    {
        $cols = array_map(fn($f) => "'{$f['column']}'", $fields);
        return '[' . implode(', ', $cols) . ']';
    }

    protected function buildCasts(array $fields): string
    {
        $casts = [];
        foreach ($fields as $f) {
            switch ($f['type']) {
                case 'integer':
                case 'bigint':
                case 'smallint':
                    $casts[$f['column']] = 'integer';
                    break;
                case 'boolean':
                    $casts[$f['column']] = 'boolean';
                    break;
                case 'float':
                case 'decimal':
                    $casts[$f['column']] = 'float';
                    break;
                case 'json':
                case 'jsonb':
                    $casts[$f['column']] = 'array';
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $casts[$f['column']] = 'datetime';
                    break;
                default:
                    continue 2; // Skip unsupported types
            }
        }

        $pairs = array_map(fn($col, $cast) => "'{$col}' => '{$cast}'", array_keys($casts), $casts);
        return '[' . implode(', ', $pairs) . ']';
    }
}
