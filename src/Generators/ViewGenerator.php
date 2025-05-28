<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\ColumnFilter;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Generates Inertia Vue pages and TypeScript types for CRUD (index, create, edit, show).
 */
class ViewGenerator implements GeneratorInterface
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
        $cols           = $context->columnFilter->filterAll($context->fields);
        $modelName      = $context->modelName;
        $modelVar       = $context->modelVar;
        $modelVar       = $context->modelVar;
        $modelPluralVar = $context->modelPluralVar;
        $tableName      = $context->tableName;
        $routeName      = Str::kebab($modelPluralVar);
        $dir            = $context->paths['vueDirectory'];
        $columnFilter   = $context->columnFilter;

        // ensure page directory exists
        $this->files->ensureDirectoryExists($dir);

        // build dynamic pieces
        [$tableHeaders, $tableCells] = $this->buildTableColumns($cols, $modelVar);
        $formDataDefaults            = $this->buildFormDataDefaults($cols, $tableName);
        $formFields                  = $this->buildFormFields($context->columnFilter->filterId($cols), $tableName);
        $formDataWithValues          = $this->buildFormDataWithValues($cols, $modelVar);
        $showFieldsMarkup            = $this->buildShowFields($cols, $modelVar);
        $componentImports            = $this->buildComponentImports($cols, $tableName);

        // pages
        $pages = [
            'Index' => [
                'stub'    => 'pages/index.vue.stub',
                'target'  => "{$dir}/Index.vue",
                'replace' => [
                    '{{ modelPlural }}'      => $context->modelPlural,
                    '{{ modelPluralLower }}' => $modelPluralVar,
                    '{{ routeName }}'        => $routeName,
                    '{{ modelName }}'        => $modelName,
                    '{{ modelVar }}'         => $modelVar,
                    '{{ tableHeaders }}'     => $tableHeaders,
                    '{{ tableCells }}'       => $tableCells,
                ],
            ],
            'Create' => [
                'stub'    => 'pages/create.vue.stub',
                'target'  => "{$dir}/Create.vue",
                'replace' => [
                    '{{ componentImports }}' => $componentImports,
                    '{{ modelName }}'        => $modelName,
                    '{{ routeName }}'        => $routeName,
                    '{{ formDataDefaults }}' => $formDataDefaults,
                    '{{ formFields }}'       => $formFields,
                ],
            ],
            'Edit' => [
                'stub'    => 'pages/edit.vue.stub',
                'target'  => "{$dir}/Edit.vue",
                'replace' => [
                    '{{ componentImports }}'          => $componentImports,
                    '{{ modelName }}'                 => $modelName,
                    '{{ modelVar }}'                  => $modelVar,
                    '{{ routeName }}'                 => $routeName,
                    '{{ formDataDefaultsWithValues }}' => $formDataWithValues,
                    '{{ formFields }}'                => $formFields,
                ],
            ],
            'Show' => [
                'stub'    => 'pages/show.vue.stub',
                'target'  => "{$dir}/Show.vue",
                'replace' => [
                    '{{ modelName }}'   => $modelName,
                    '{{ modelVar }}'    => $modelVar,
                    '{{ routeName }}'   => $routeName,
                    '{{ showFields }}'  => $showFieldsMarkup,
                ],
            ],
        ];

        foreach ($pages as $page) {
            if ($this->files->exists($page['target']) && ! $context->options['force']) {
                continue;
            }
            $stubContent = $this->renderer->render($page['stub'], []);
            // apply replacements
            $content = str_replace(
                array_keys($page['replace']),
                array_values($page['replace']),
                $stubContent
            );
            $this->files->put($page['target'], $content);
        }

        // generate Inertia TypeScript definitions
        $typesDir = resource_path('js/types');
        $this->files->ensureDirectoryExists($typesDir);
        $tsStub = $this->renderer->render('inertia-types.ts.stub', [
            'modelName' => $modelName,
            'modelVar'  => $modelVar,
        ]);
        $this->files->put("{$typesDir}/inertia.d.ts", $tsStub);
    }

    protected function buildTableColumns(array $columns, string $modelVar): array
    {
        $headers = $cells = '';
        $first = true;
        foreach ($columns as $col) {
            $name = $col['column'];
            $label   = Str::headline($name);
            if ($first) {
                $first = false;
                $headers .= "<th class=\"px-4 py-2 text-left\">{$label}</th>\n";
                $cells   .= "<td class=\"px-4 py-2\">{{ {$modelVar}.{$name} }}</td>\n";
            } else {
                $headers .= "\t\t\t\t\t\t<th class=\"px-4 py-2 text-left\">{$label}</th>\n";
                $cells   .= "\t\t\t\t\t\t<td class=\"px-4 py-2\">{{ {$modelVar}.{$name} }}</td>\n";
            }
        }
        return [$headers, $cells];
    }

    protected function buildFormDataDefaults(array $columns, string $tableName): string
    {
        $out  = '';
        foreach ($columns as $col) {
            $name = $col['column'];
            $defaultType = Schema::getColumnType($tableName, $name);
            $default     = ($defaultType === 'boolean') ? 'false' : "''";
            $out .= "\t{$name}: {$default},\n";
        }
        return $out;
    }

    protected function buildFormDataWithValues(array $columns, string $modelVar): string
    {
        $out  = '';
        foreach ($columns as $col) {
            $name = $col['column'];
            $out .= "\t{$name}: props.{$modelVar}.{$name} ?? null,\n";
        }
        return $out;
    }

    protected function buildFormFields(array $columns, string $tableName): string
    {
        $out  = '';
        foreach ($columns as $col) {
            $name = $col['column'];
            $label     = Str::headline($name);
            $type      = Schema::getColumnType($tableName, $name);
            $component = match ($type) {
                'boolean'                            => 'Checkbox',
                'integer', 'bigint', 'float', 'decimal' => 'NumberInput',
                'date', 'datetime', 'datetimetz', 'time' => 'DateInput',
                default                               => 'Input',
            };
            $out .= <<<HTML
                        <div class="mt-4">
                            <Label class="mb-2">{{ '{$label}' }}</Label>
                            <{$component}
                                v-model="form.{$name}"
                                name="{$name}"
                            />
                            <InputError :message="form.errors.{$name}" class="mt-2" />
                        </div>\n
            HTML;
        }
        return $out;
    }

    protected function buildShowFields(array $columns, string $modelVar): string
    {
        $out  = '';
        foreach ($columns as $col) {
            $name = $col['column'];
            $label = Str::headline($name);
            $out .= <<<HTML
                    <div class="mt-4">
                        <Label class="font-bold mb-1 text-lg">{{ '{$label}' }}</Label>
                        <p>{{ {$modelVar}.{$name} }}</p>
                    </div>\n
            HTML;
        }
        return $out;
    }

    protected function buildComponentImports(array $columns, string $tableName): string
    {
        // Load the type-component map from config
        $map = config('vilt-crud-generator.type_component_map');
        $needed = [];
        foreach ($columns as $col) {
            $type = Schema::getColumnType($tableName, $col['column']);
            if (isset($map[$type])) {
                $needed[$map[$type]] = true;
            } else {
                // Fallback: treat unknown types as Input
                $needed['Input'] = true;
            }
        }
        $imports = [];
        foreach (array_keys($needed) as $componentPath) {
            $componentName = Str::beforeLast(Str::afterLast($componentPath, '/'), ".");
            $imports[] = "import {$componentName} from '@/components/{$componentPath}'";
        }
        return implode("\n", $imports);
    }
}
