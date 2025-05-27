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
            'hidden'      => $this->buildHidden($context->fields),
        ]);

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);
    }

    protected function buildFillable(array $fields): string
    {
        // Filter out fields that are not fillable
        $fields = array_filter($fields, fn($f) => !in_array($f['column'], ['id', 'created_at', 'updated_at', 'deleted_at'], true));
        $cols = array_map(fn($f) => "\n\t\t\t'{$f['column']}'", $fields);
        return '[' . implode(', ', $cols) . "\n\t\t]";
    }

    protected function buildCasts(array $fields): string
    {
        $casts = [];
        $fields = array_filter($fields, fn($f) => !in_array($f['column'], ['id'], true));
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

        $pairs = array_map(fn($col, $cast) => "\n\t\t\t'{$col}' => '{$cast}'", array_keys($casts), $casts);
        return '[' . implode(', ', $pairs) . "\n\t\t]";
    }
    /**
     * Build the $hidden array for the model by filtering out common sensitive fields.
     *
     * @param  array  $fields  The array of field metadata from the context.
     * @return string          PHP array syntax for the $hidden property.
     */
    protected function buildHidden(array $fields): string
    {
        // List any columns you consider sensitive
        $sensitive = [
            'password',
            'remember_token',
            'api_token',
            'api_key',
            'secret',
            'credit_card',
            'card_number',
            'cvv',
            'card',
            'ssn',
            'social_security_number',
            // add more as needed…
        ];

        $hidden = [];
        foreach ($fields as $f) {
            if (in_array($f['column'], $sensitive, true)) {
                $hidden[] = $f['column'];
            }
        }

        // If nothing sensitive, return an empty array
        if (empty($hidden)) {
            return '[]';
        }

        // Build a nicely indented PHP array string
        $out = "[";
        foreach ($hidden as $column) {
            $out .= "\n\t\t\t'{$column}',\n";
        }
        $out .= "\t\t]";

        return $out;
    }
}
