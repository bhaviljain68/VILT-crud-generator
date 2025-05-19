<?php

namespace artisanalbyte\InertiaCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Filesystem\Filesystem;
use Doctrine\DBAL\DriverManager;

/**
 * Artisan command to scaffold a full Inertia/Vue CRUD for a given model and table.
 */
class CrudGeneratorCommand extends Command
{
    /** @var string The Artisan command signature. */
    protected $signature = 'inertia-crud:generate 
                            {Model           : The Eloquent model name (singular, StudlyCase)}
                            {Table?          : Optional table name (plural, snake_case). Defaults to plural(model).}
                            {--form-request  : Generate FormRequest classes for validation}
                            {--force         : Overwrite existing files}
                            {--export        : Include CSV/XLSX/PDF export functionality}';

    /** @var string The Artisan command description. */
    protected $description = 'Generate Inertia CRUD (model, controller, form requests, resources, Vue pages, routes)';

    /**
     * Execute the command.
     */
    public function handle()
    {
        // --------------------------------------------------
        // * 1. Prepare names & flags
        // --------------------------------------------------
        $modelName        = Str::studly($this->argument('Model'));
        $tableName        = $this->argument('Table') ?: Str::plural(Str::snake($modelName));
        $modelVar         = Str::camel($modelName);
        $modelPlural      = Str::plural($modelName);
        $modelPluralVar   = Str::camel($modelPlural);
        $routeName        = $tableName;
        $useFormRequest   = $this->option('form-request')
            ?: config('inertia-crud-generator.generate_form_requests_by_default', false);
        $force            = $this->option('force');
        $includeExport    = $this->option('export');

        $this->info("🛠  Generating CRUD for {$modelName} (table: {$tableName})");

        // --------------------------------------------------
        // * 2. Ensure Doctrine DBAL is available
        // --------------------------------------------------
        if (! class_exists(DriverManager::class)) {
            $this->error("Please require doctrine/dbal: composer require doctrine/dbal");
            return Command::FAILURE;
        }

        // --------------------------------------------------
        // * 3. Introspect table schema via DBAL
        // --------------------------------------------------
        Schema::requireTable($tableName);
        $columns = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableColumns($tableName);

        // Build a $fields array for fillable, validation, etc.
        $fields = [];
        foreach ($columns as $col) {
            /** @var Column $col */
            $name = $col->getName();
            // Skip only `deleted_at` for fillable/validation
            if ($name === 'deleted_at') {
                continue;
            }
            $typeName  = Schema::getConnection()
                ->getDatabasePlatform()
                ->getTypeRegistry()
                ->lookupName($col->getType());
            $fields[$name] = [
                'type'     => $typeName,
                'length'   => $col->getLength(),
                'required' => $col->getNotnull(),
            ];
        }

        // --------------------------------------------------
        // * 4. Prepare class names & paths
        // --------------------------------------------------
        $modelClass        = "App\\Models\\{$modelName}";
        $controllerClass   = "{$modelName}Controller";
        $resourceClass     = "{$modelName}Resource";
        $collectionClass   = "{$modelName}Collection";
        $requestStoreClass = "Store{$modelName}Request";
        $requestUpdateClass = "Update{$modelName}Request";

        $basePath = base_path();
        $resourcePath = resource_path();

        $paths = [
            'model'         => "{$basePath}/app/Models/{$modelName}.php",
            'controller'    => "{$basePath}/app/Http/Controllers/{$controllerClass}.php",
            'requestStore'  => "{$basePath}/app/Http/Requests/{$requestStoreClass}.php",
            'requestUpdate' => "{$basePath}/app/Http/Requests/{$requestUpdateClass}.php",
            'resource'      => "{$basePath}/app/Http/Resources/{$resourceClass}.php",
            'collection'    => "{$basePath}/app/Http/Resources/{$collectionClass}.php",
            'vueDir'        => "{$resourcePath}/js/Pages/{$modelPlural}",
        ];

        // Ensure Vue pages dir exists
        (new Filesystem)->ensureDirectoryExists($paths['vueDir']);

        // --------------------------------------------------
        // * 5. Stub loader helper
        // --------------------------------------------------
        $fs   = new Filesystem;
        $load = function (string $stubName) use ($fs): string {
            $published = resource_path("stubs/inertia-crud-generator/{$stubName}.stub");
            $default   = __DIR__ . "/../../stubs/{$stubName}.stub";
            return $fs->get($fs->exists($published) ? $published : $default);
        };

        // --------------------------------------------------
        // * 6. Generate Model
        // --------------------------------------------------
        if ($force || ! $fs->exists($paths['model'])) {
            $stub = $load('model');
            $stub = str_replace(
                ['{{ modelNamespace }}', '{{ modelName }}', '{{ tableName }}', '{{ fillable }}'],
                ['App\Models', $modelName, $tableName, $this->generateFillableArray($fields)],
                $stub
            );
            $fs->ensureDirectoryExists(dirname($paths['model']));
            $fs->put($paths['model'], $stub);
            $this->info("✔ Model created: {$modelName}");
        }

        // --------------------------------------------------
        // * 7. Generate Controller
        // --------------------------------------------------
        $stubVariables = [
            // existing placeholders…
            '{{ namespace }}'            => 'App\\Http\\Controllers',
            '{{ modelName }}'            => $modelName,
            '{{ modelClass }}'           => $modelClass,
            '{{ controllerClass }}'      => $controllerClass,
            '{{ routeName }}'            => $routeName,
            '{{ useFormRequest }}'       => $useFormRequest ? 'true' : 'false',

            // NEW: export‐flag placeholders
            '{{ hasExportTrait }}'       => $includeExport ? 'true' : 'false',
            '{{ exportTraitUse }}'       => $includeExport
                ? 'use VendorName\\InertiaCrudGenerator\\Traits\\HasExport;'
                : '',
            '{{ exportModelProperty }}'  => $includeExport
                ? "protected string \$modelClass = {$modelClass}::class;"
                : '',
        ];
        if ($force || ! $fs->exists($paths['controller'])) {
            $stub = $load('controller');
            $stub = str_replace(
                array_keys($stubVariables),
                array_values($stubVariables),
                $stub
            );
            $fs->ensureDirectoryExists(dirname($paths['controller']));
            $fs->put($paths['controller'], $stub);
            $this->info("✔ Controller created: {$controllerClass}");
        }

        // --------------------------------------------------
        // * 8. Generate Form Requests
        // --------------------------------------------------
        if ($useFormRequest) {
            foreach (['store', 'update'] as $action) {
                $class = $action === 'store'
                    ? $requestStoreClass
                    : $requestUpdateClass;
                $path  = $action === 'store'
                    ? $paths['requestStore']
                    : $paths['requestUpdate'];
                if ($force || ! $fs->exists($path)) {
                    $stubName = 'form-request' . ($action === 'update' ? '-update' : '');
                    $stub = $load($stubName);

                    $rulesArray = $this->generateValidationRules($fields, $tableName, $modelName, $action);
                    $msgString  = $this->generateValidationMessages($fields, $modelName, $action);

                    $stub = str_replace(
                        ['{{ namespace }}', '{{ className }}', '{{ rules }}', '{{ messages }}', '{{ attributes }}'],
                        ['App\\Http\\Requests', $class, $rulesArray['rules'], $msgString, $rulesArray['attributes']],
                        $stub
                    );
                    $fs->ensureDirectoryExists(dirname($path));
                    $fs->put($path, $stub);
                    $this->info("✔ FormRequest created: {$class}");
                }
            }
        }

        // --------------------------------------------------
        // * 9. Generate API Resource & Collection
        // --------------------------------------------------
        if ($force || ! $fs->exists($paths['resource'])) {
            $stub = $load('resource');
            $fieldsCode = $this->generateResourceFields($columns);
            $stub = str_replace(
                ['{{ namespace }}', '{{ resourceClass }}', '{{ modelVar }}', '{{ resourceFields }}'],
                ['App\\Http\\Resources', $resourceClass, $modelVar, $fieldsCode],
                $stub
            );
            $fs->ensureDirectoryExists(dirname($paths['resource']));
            $fs->put($paths['resource'], $stub);
            $this->info("✔ API Resource created: {$resourceClass}");
        }

        if ($force || ! $fs->exists($paths['collection'])) {
            $stub = $load('resource-collection');
            $stub = str_replace(
                ['{{ namespace }}', '{{ collectionClass }}', '{{ resourceClass }}'],
                ['App\\Http\\Resources', $collectionClass, $resourceClass],
                $stub
            );
            $fs->put($paths['collection'], $stub);
            $this->info("✔ API Collection created: {$collectionClass}");
        }

        // --------------------------------------------------
        // * 10. Generate Inertia/Vue Pages
        // --------------------------------------------------
        // Build dynamic bits:
        [$tableHeaders, $tableCells]         = $this->buildTableColumns($columns);
        $formDataDefaults                    = $this->buildFormDataDefaults($columns);
        $formFields                          = $this->buildFormFields($columns);
        $formDataWithValues                  = $this->buildFormDataWithValues($columns, $modelVar);
        $showFieldsMarkup                    = $this->buildShowFields($columns, $modelVar);
        $componentImports = $this->buildComponentImports($columns);

        // -- Index.vue --
        $indexContent = $load('index.vue');
        $indexContent = str_replace(
            [
                '{{ modelPlural }}',
                '{{ modelPluralLower }}',
                '{{ routeName }}',
                '{{ modelName }}',
                '{{ tableHeaders }}',
                '{{ tableCells }}'
            ],
            [
                $modelPlural,
                $modelPluralVar,
                $routeName,
                $modelName,
                $tableHeaders,
                $tableCells
            ],
            $indexContent
        );
        $fs->put("{$paths['vueDir']}/Index.vue", $indexContent);
        $this->info("✔ Vue page created: {$modelPlural}/Index.vue");

        // -- Create.vue --
        $createContent = $load('create.vue');
        $createContent = str_replace(
            ['{{ componentImports }}', '{{ modelName }}', '{{ routeName }}', '{{ formDataDefaults }}', '{{ formFields }}'],
            [$componentImports, $modelName, $routeName, $formDataDefaults, $formFields],
            $createContent
        );
        $fs->put("{$paths['vueDir']}/Create.vue", $createContent);
        $this->info("✔ Vue page created: {$modelPlural}/Create.vue");

        // -- Edit.vue --
        $editContent = $load('edit.vue');
        $editContent = str_replace(
            ['{{ componentImports }}', '{{ modelName }}', '{{ modelVar }}', '{{ routeName }}', '{{ formDataDefaultsWithValues }}', '{{ formFields }}'],
            [$componentImports, $modelName, $modelVar, $routeName, $formDataWithValues, $formFields],
            $editContent
        );
        $fs->put("{$paths['vueDir']}/Edit.vue", $editContent);
        $this->info("✔ Vue page created: {$modelPlural}/Edit.vue");

        // -- Show.vue --
        $showContent = $load('show.vue');
        $showContent = str_replace(
            ['{{ modelName }}', '{{ modelVar }}', '{{ routeName }}', '{{ showFields }}'],
            [$modelName, $modelVar, $routeName, $showFieldsMarkup],
            $showContent
        );
        $fs->put("{$paths['vueDir']}/Show.vue", $showContent);
        $this->info("✔ Vue page created: {$modelPlural}/Show.vue");

        // --------------------------------------------------
        // * 11. Auto-register the resource route
        // --------------------------------------------------
        $routesPath       = base_path('routes/web.php');
        $routeDefinition  = "Route::resource('{$routeName}', {$controllerClass}::class);";

        // Read the file once
        $routesContents = $fs->get($routesPath);

        // If it isn’t already there, append it
        if (! str_contains($routesContents, $routeDefinition)) {
            $fs->append($routesPath, "\n{$routeDefinition}\n");
            $this->info("✔ Route added to routes/web.php: {$routeDefinition}");
        } else {
            $this->info("ℹ Route already exists in routes/web.php, skipping.");
        }

        // --------------------------------------------------
        // * 12. Auto-register the export route if requested
        // --------------------------------------------------
        if ($includeExport) {
            // Append to routes/web.php
            $routeLine = "Route::get('{$tableName}/export', [{$controllerClass}::class, 'export'])->name('{$routeName}.export');";
            file_put_contents(
                base_path('routes/web.php'),
                "\n" . $routeLine,
                FILE_APPEND
            );
            $this->info("Added export route: GET /{$tableName}/export");
        }



        $this->info("🎉 All done! Next, run php artisan inertia-crud:install to publish stubs and components.");
        return Command::SUCCESS;
    }

    // --------------------------------------------------
    // Helper methods
    // --------------------------------------------------

    /**
     * Generate a PHP array literal for the $fillable property.
     */
    protected function generateFillableArray(array $fields): string
    {
        if (empty($fields)) {
            return '[]';
        }
        $quoted = array_map(fn($f) => "'{$f}'", array_keys($fields));
        return '[ ' . implode(', ', $quoted) . ' ]';
    }

    /**
     * Generate validation rules & attributes arrays.
     */
    protected function generateValidationRules(array $fields, string $table, string $model, string $action): array
    {
        $rules = [];
        $attrs = [];
        foreach ($fields as $name => $meta) {
            $line = [];
            $line[] = $meta['required'] ? 'required' : 'nullable';

            // type-based rules
            match ($meta['type']) {
                'integer', 'bigint' => $line[] = 'integer',
                'boolean'          => $line[] = 'boolean',
                'date', 'datetime', 'datetimetz' => $line[] = 'date',
                'string' => $meta['length']
                    ? $line[] = 'max:' . $meta['length']
                    : null,
                default => null,
            };

            // unique email example
            if ($name === 'email') {
                $uniqueRule = $action === 'update'
                    ? "unique:{$table},email,\${$model}->id"
                    : "unique:{$table},email";
                $line[] = $uniqueRule;
            }

            $rules[$name] = implode('|', array_filter($line));
            $attrs[$name] = Str::headline($name);
        }

        $rulesStr = '';
        foreach ($rules as $field => $r) {
            $rulesStr .= "            '{$field}' => '$r',\n";
        }
        $attrStr = '';
        foreach ($attrs as $field => $label) {
            $attrStr .= "            '{$field}' => __('{$label}'),\n";
        }

        return [
            'rules'      => "[\n{$rulesStr}        ]",
            'attributes' => "[\n{$attrStr}        ]",
        ];
    }

    /**
     * Generate custom messages() array for FormRequest.
     */
    protected function generateValidationMessages(array $fields, string $model, string $action): string
    {
        $msgs = [];
        foreach ($fields as $name => $meta) {
            $label = Str::headline($name);
            if ($meta['required']) {
                $msgs[] = "'{$name}.required' => __('The {$label} field is required.'),";
            }
            match ($meta['type']) {
                'integer', 'bigint' => $msgs[] = "'{$name}.integer' => __('{$label} must be an integer.'),",
                'boolean'          => $msgs[] = "'{$name}.boolean' => __('{$label} must be true or false.'),",
                'date', 'datetime', 'datetimetz' => $msgs[] = "'{$name}.date' => __('{$label} must be a valid date.'),",
                'string' => $meta['length']
                    ? $msgs[] = "'{$name}.max' => __('{$label} may not exceed {$meta['length']} characters.'),"
                    : null,
                default => null,
            };
            if ($name === 'email') {
                $msgs[] = "'{$name}.email' => __('Please enter a valid email.'),";
                $msgs[] = "'{$name}.unique' => __('The email has already been taken.'),";
            }
        }
        $out = "[\n";
        foreach ($msgs as $line) {
            $out .= "            {$line}\n";
        }
        $out .= "        ]";
        return $out;
    }

    /**
     * Build the array lines for resource fields, skipping sensitive.
     */
    protected function generateResourceFields(array $columns): string
    {
        $sensitive = ['password', 'remember_token', 'api_token', 'secret', 'token', 'client_secret'];
        $lines = '';
        foreach ($columns as $col) {
            $name = $col->getName();
            if (in_array($name, $sensitive, true)) continue;
            $type = $col->getType()->getName();
            if (
                in_array($type, ['date', 'datetime', 'datetimetz', 'time'], true)
                || in_array($name, ['created_at', 'updated_at', 'deleted_at'], true)
            ) {
                $lines .= "            '{$name}' => \$model->{$name}?->toIso8601String(),\n";
            } else {
                $lines .= "            '{$name}' => \$model->{$name},\n";
            }
        }
        return $lines;
    }

    /**
     * Build <th> and <td> lines for Index.vue table.
     *
     * @return array [string $headers, string $cells]
     */
    protected function buildTableColumns(array $columns): array
    {
        $sensitive = ['password', 'remember_token', 'api_token', 'secret', 'token', 'client_secret'];
        $headers = $cells = '';
        foreach ($columns as $col) {
            $name  = $col->getName();
            if (in_array($name, $sensitive, true)) continue;
            $label = Str::headline($name);
            $headers .= "            <th class=\"px-4 py-2 text-left\">{{ __('{$label}') }}</th>\n";
            $cells   .= "            <td class=\"px-4 py-2\">{{ item.{$name} }}</td>\n";
        }
        return [$headers, $cells];
    }

    /**
     * Build the initial form data defaults for Create.vue.
     */
    protected function buildFormDataDefaults(array $columns): string
    {
        $sensitive = ['password', 'remember_token', 'api_token', 'secret', 'token', 'client_secret'];
        $out = '';
        foreach ($columns as $col) {
            $name = $col->getName();
            if (in_array($name, $sensitive, true)) continue;
            $default = $col->getType()->getName() === 'boolean' ? 'false' : "''";
            $out   .= "    {$name}: {$default},\n";
        }
        return $out;
    }

    /**
     * Build the form data defaults pre-populated for Edit.vue.
     */
    protected function buildFormDataWithValues(array $columns, string $modelVar): string
    {
        $sensitive = ['password', 'remember_token', 'api_token', 'secret', 'token', 'client_secret'];
        $out = '';
        foreach ($columns as $col) {
            $name = $col->getName();
            if (in_array($name, $sensitive, true)) continue;
            $out .= "    {$name}: {$modelVar}.{$name} ?? null,\n";
        }
        return $out;
    }

    /**
     * Build the <div> blocks for form inputs in Create/Edit.
     */
    protected function buildFormFields(array $columns): string
    {
        $sensitive = ['password', 'remember_token', 'api_token', 'secret', 'token', 'client_secret'];
        $out = '';
        foreach ($columns as $col) {
            $name  = $col->getName();
            if (in_array($name, $sensitive, true)) continue;
            $label = Str::headline($name);
            $type  = $col->getType()->getName();
            $component = match ($type) {
                'boolean'                              => 'Checkbox',
                'integer', 'bigint', 'float', 'decimal'   => 'NumberInput',
                'date', 'datetime', 'datetimetz', 'time'  => 'DateInput',
                default                                => 'Input',
            };
            $out .= <<<HTML
                        <div class="mt-4">
                        <label class="block font-medium">{{ __('{$label}') }}</label>
                        <{$component}
                            v-model="form.{$name}"
                            name="{$name}"
                            :error="form.errors.{$name}"
                        />
                        </div>
                    HTML;
        }
        return $out;
    }

    /**
     * Build the show layout fields for Show.vue.
     */
    protected function buildShowFields(array $columns, string $modelVar): string
    {
        $sensitive = ['password', 'remember_token', 'api_token', 'secret', 'token', 'client_secret'];
        $out = '';
        foreach ($columns as $col) {
            $name = $col->getName();
            if (in_array($name, $sensitive, true)) continue;
            $label = Str::headline($name);
            $out .= <<<HTML
                        <div>
                            <dt class="font-medium">{{ __('{$label}') }}</dt>
                            <dd>{{ {$modelVar}.{$name} }}</dd>
                        </div>
                    HTML;
        }
        return $out;
    }

    /**
     * Given the columns, determine which components we need and return TS import lines.
     */
    protected function buildComponentImports(array $columns): string
    {
        $map = [
            'boolean'          => 'Checkbox',
            'integer'          => 'NumberInput',
            'bigint'           => 'NumberInput',
            'float'            => 'NumberInput',
            'decimal'          => 'NumberInput',
            'date'             => 'DateInput',
            'datetime'         => 'DateInput',
            'datetimetz'       => 'DateInput',
            'time'             => 'DateInput',
            'string'           => 'Input',
            'text'             => 'Input',
            // add more type⇒component mappings as needed
        ];

        $needed = [];
        foreach ($columns as $col) {
            $type = $col->getType()->getName();
            if (isset($map[$type])) {
                $needed[$map[$type]] = true;
            }
        }

        $imports = [];
        foreach (array_keys($needed) as $component) {
            $imports[] = "import {$component} from '@/components/ui/input/{$component}.vue'";
        }

        return implode("\n", $imports);
    }
}
