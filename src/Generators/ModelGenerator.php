<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\ColumnFilter;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

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

  public function generate(CrudContext $context): array
  {
    $path = $context->paths['modelPath'];
    $generated = [];
    // Don't overwrite unless --force
    if ($this->files->exists($path) && ! $context->options['force']) {
      return $generated;
    }
    $stub = $this->renderer->render('model.stub', [
      'model'       => $context->modelName,
      'table'       => $context->tableName,
      'fillable'    => $this->buildFillable($context->fields, $context->columnFilter),
      'casts'       => $this->buildCasts($context->fields, $context->columnFilter),
      'hidden'      => $this->buildHidden($context->fields, $context->columnFilter),
    ]);
    $this->files->ensureDirectoryExists(dirname($path));
    $this->files->put($path, $stub);
    $path = Str::of($path)
      ->replace('\\', '/')
      ->after('/app')
      ->prepend('app')
      ->toString();
    $generated[] = "✅ $context->modelName Model Generated : $path 😎";
    return $generated;
  }

  protected function buildFillable(array $fields, ColumnFilter $columnFilter): string
  {
    // Filter out fields that are not fillable
    $fields = $fields = $columnFilter->filterSystem($fields);
    $cols = array_map(fn($f) => "\n\t\t\t'{$f['column']}'", $fields);
    return '[' . implode(', ', $cols) . "\n\t\t]";
  }

  protected function buildCasts(array $fields, ColumnFilter $columnFilter): string
  {
    $casts = [];
    // $fields = array_filter($fields, fn($f) => !in_array($f['column'], ['id'], true));
    $fields = $fields = $columnFilter->filter($fields, ['id']);
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
    return  implode(', ', $pairs);
  }
  /**
   * Build the $hidden array for the model by filtering out common sensitive fields.
   *
   * @param  array  $fields  The array of field metadata from the context.
   * @return string          PHP array syntax for the $hidden property.
   */
  protected function buildHidden(array $fields, ColumnFilter $columnFilter): string
  {
    $hidden = array_map(fn($f) => $f['column'], $columnFilter->onlySensitive($fields));

    if (empty($hidden)) {
      return '[]';
    }

    $out = "[";
    foreach ($hidden as $column) {
      $out .= "\n\t\t\t'{$column}',\n";
    }
    $out .= "\t\t]";

    return $out;
  }
}
