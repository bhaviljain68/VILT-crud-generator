<?php

namespace artisanalbyte\InertiaCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Filesystem\Filesystem;
use Doctrine\DBAL\DriverManager;

class CrudGeneratorCommand extends Command
{
    protected $signature = 'inertia:crud {Model} {Table?} {--form-request} {--force}';
    protected $description = 'Generate Inertia CRUD (model, controller, form requests, resources, views, routes) for a given model and table';

    public function handle()
    {
        $modelName = Str::studly($this->argument('Model'));           // e.g. "Post"
        $tableName = $this->argument('Table') ?: Str::plural(Str::snake($modelName));  // e.g. "posts"
        $useFormRequest = $this->option('form-request')
            ?: config('inertia-crud-generator.generate_form_requests_by_default', false);

        $this->info("Generating Inertia CRUD for model: $modelName (table: $tableName)");

        // Ensure Doctrine DBAL is installed for detailed schema info
        if (!class_exists(DriverManager::class)) {
            $this->error("Doctrine DBAL is required to read table schema. Please require doctrine/dbal.");
            return Command::FAILURE;
        }

        // Retrieve table columns and their details
        Schema::requireTable($tableName); // ensure table exists (Laravel 10+ method) 
        $columns = Schema::getConnection()->getDoctrineSchemaManager()->listTableColumns($tableName);
        // $columns is an array of Doctrine\DBAL\Schema\Column objects

        // Filter and prepare columns for generation (skip timestamps, etc.)
        $fields = [];
        foreach ($columns as $columnObj) {
            /** @var Column $columnObj */
            $name = $columnObj->getName();
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue; // skip primary key and timestamp fields
            }
            $typeName = $columnObj->getType()->getName();      // e.g. string, integer
            $length   = $columnObj->getLength();
            $required = !$columnObj->getNotnull() ? false : true;  // true if NOT NULL
            $fields[$name] = [
                'type' => $typeName,
                'length' => $length,
                'required' => $required,
            ];
        }

        // Determine class names and file paths
        $modelClass      = "App\\Models\\$modelName";
        $controllerClass = "{$modelName}Controller";
        $controllerNamespace = "App\\Http\\Controllers";
        $requestClasses = [
            'store' => "Store{$modelName}Request",
            'update' => "Update{$modelName}Request",
        ];
        $resourceClass  = "{$modelName}Resource";
        $collectionClass = "{$modelName}Collection";

        // Determine base paths for output files
        $modelPath       = base_path("app/Models/{$modelName}.php");
        $controllerPath  = base_path("app/Http/Controllers/{$controllerClass}.php");
        $requestPathStore = base_path("app/Http/Requests/{$requestClasses['store']}.php");
        $requestPathUpdate = base_path("app/Http/Requests/{$requestClasses['update']}.php");
        $resourcePath    = base_path("app/Http/Resources/{$resourceClass}.php");
        $collectionPath  = base_path("app/Http/Resources/{$collectionClass}.php");
        $pagesDir        = resource_path("js/Pages/" . Str::plural($modelName));
        $indexVuePath    = $pagesDir . "/Index.vue";
        $createVuePath   = $pagesDir . "/Create.vue";
        $editVuePath     = $pagesDir . "/Edit.vue";
        // (Optional: Show.vue could be generated similarly if needed)

        // Check for file collisions unless --force is used
        $fs = new Filesystem;
        $outputs = [
            $modelPath,
            $controllerPath,
            $resourcePath,
            $collectionPath,
            $indexVuePath,
            $createVuePath,
            $editVuePath
        ];
        if ($useFormRequest) {
            $outputs[] = $requestPathStore;
            $outputs[] = $requestPathUpdate;
        }
        foreach ($outputs as $out) {
            if ($fs->exists($out) && !$this->option('force')) {
                $this->warn("Skipped existing file: " . $fs->basename($out) . " (use --force to overwrite)");
            }
        }

        // Helper: load a stub file (prefers published stub in resources/stubs over package default)
        $loadStub = function (string $stubName) use ($fs) {
            $published = resource_path("stubs/inertia-crud-generator/{$stubName}.stub");
            $default   = __DIR__ . "/../../stubs/{$stubName}.stub";
            $path = $fs->exists($published) ? $published : $default;
            return $fs->get($path);
        };

        // 3.a. Generate Eloquent Model (if not exists)
        if (!$fs->exists($modelPath)) {
            $stub = $loadStub('model');
            // Replace placeholders in model stub
            $stub = str_replace(
                ['{{ modelNamespace }}', '{{ modelName }}', '{{ tableName }}', '{{ fillable }}'],
                ['App\Models', $modelName, $tableName, $this->generateFillableArray($fields)],
                $stub
            );
            $fs->ensureDirectoryExists(dirname($modelPath));
            $fs->put($modelPath, $stub);
            $this->info("Created Model: $modelName");
        } else {
            $this->line("Model $modelName already exists, skipping.");
        }

        // 3.b. Generate Controller
        $stub = $loadStub('controller');
        $stub = str_replace(
            [
                '{{ namespace }}',
                '{{ modelName }}',
                '{{ modelVar }}',
                '{{ modelClass }}',
                '{{ resourceName }}',
                '{{ resourceCollectionName }}',
                '{{ controllerClass }}',
                '{{ table }}',
                '{{ routeName }}',
                '{{ useFormRequest }}'
            ],
            [
                $controllerNamespace,
                $modelName,
                Str::camel($modelName),
                $modelClass,
                $resourceClass,
                $collectionClass,
                $controllerClass,
                $tableName,
                Str::plural($tableName), // route name prefix (plural)
                $useFormRequest ? 'true' : 'false'
            ],
            $stub
        );
        // If using form requests, we will adjust the controller stub content accordingly below
        $fs->ensureDirectoryExists(dirname($controllerPath));
        $fs->put($controllerPath, $stub);
        $this->info("Created Controller: $controllerClass");

        // 3.c. Generate Form Request classes (if requested)
        if ($useFormRequest) {
            foreach (['store', 'update'] as $action) {
                $className = $requestClasses[$action];
                $path = ($action === 'store') ? $requestPathStore : $requestPathUpdate;
                $stub = $loadStub('form-request' . ($action === 'update' ? '-update' : ''));
                $rulesArray = $this->generateValidationRules($fields, $modelName, $action);
                $stub = str_replace(
                    ['{{ namespace }}', '{{ className }}', '{{ rules }}', '{{ attributes }}'],
                    ['App\\Http\\Requests', $className, $rulesArray['rules'], $rulesArray['attributes']],
                    $stub
                );
                $fs->ensureDirectoryExists(dirname($path));
                $fs->put($path, $stub);
                $this->info("Created Form Request: $className");
            }
        }

        // 3.d. Generate API Resource and ResourceCollection
        $stub = $loadStub('resource');
        $stub = str_replace(
            ['{{ namespace }}', '{{ resourceClass }}', '{{ modelClass }}', '{{ modelVar }}'],
            ['App\\Http\\Resources', $resourceClass, $modelClass, Str::camel($modelName)],
            $stub
        );
        $fs->ensureDirectoryExists(dirname($resourcePath));
        $fs->put($resourcePath, $stub);
        $this->info("Created API Resource: $resourceClass");

        $stub = $loadStub('resource-collection');
        $stub = str_replace(
            ['{{ namespace }}', '{{ collectionClass }}', '{{ resourceClass }}'],
            ['App\\Http\\Resources', $collectionClass, $resourceClass],
            $stub
        );
        $fs->put($collectionPath, $stub);
        $this->info("Created Resource Collection: $collectionClass");

        // 3.e. Generate Inertia Vue pages (Index, Create, Edit)
        $fs->ensureDirectoryExists($pagesDir);
        foreach (['Index', 'Create', 'Edit'] as $page) {
            $stubName = strtolower($page) . '.vue';
            $stub = $loadStub($stubName);
            // Replace placeholders for component namespace and maybe others
            $componentNs = config('inertia-crud-generator.default_component_namespace', '@/Components/');
            $stub = str_replace(
                ['{{ modelName }}', '{{ modelVar }}', '{{ modelPlural }}', '{{ componentNs }}', '{{ langAttr }}'],
                [
                    $modelName,
                    Str::camel($modelName),
                    Str::plural($modelName),
                    rtrim($componentNs, '/'),  // ensure no trailing slash 
                    config('inertia-crud-generator.use_typescript') ? ' lang="ts"' : ''
                ],
                $stub
            );
            $vuePath = $pagesDir . "/$page.vue";
            $fs->put($vuePath, $stub);
            $this->info("Created Inertia Page: {$page}.vue");
        }

        // 3.f. Provide route registration hint
        $routeSnippet = "Route::resource('{$tableName}', {$controllerClass}::class);";
        $this->line("\n" . $this->laravel->runningInConsole() ?
            "Add the following line to your routes/web.php:\n  $routeSnippet" : '');
        return Command::SUCCESS;
    }

    /**
     * Generate fillable array string for model stub.
     */
    protected function generateFillableArray(array $fields): string
    {
        if (empty($fields)) return '[]';
        $fieldNames = array_keys($fields);
        // Quote each field name and join
        $quoted = array_map(fn($f) => "'$f'", $fieldNames);
        return '[ ' . implode(', ', $quoted) . ' ]';
    }

    /**
     * Generate validation rules string and attributes array for the form request stub.
     */
    protected function generateValidationRules(array $fields, string $modelName, string $action): array
    {
        $rules = [];
        $attrs = [];
        foreach ($fields as $name => $meta) {
            $ruleLine = [];
            if ($meta['required']) {
                $ruleLine[] = 'required';
            } else {
                $ruleLine[] = 'nullable';
            }
            // Basic type-based rules
            $type = $meta['type'];
            if ($type === 'integer' || $type === 'bigint') {
                $ruleLine[] = 'integer';
            } elseif ($type === 'boolean') {
                $ruleLine[] = 'boolean';
            } elseif ($type === 'datetime' || $type === 'datetimetz' || $type === 'date') {
                $ruleLine[] = 'date';
            } elseif ($type === 'string') {
                if ($meta['length']) {
                    $ruleLine[] = 'max:' . $meta['length'];
                }
            }
            // Example: unique constraint handling
            // if ($name === 'email') { ... $ruleLine[] = 'unique:'.$tableName }
            $rules[$name] = implode('|', $ruleLine);
            // Prepare a human-friendly attribute name (Title Case for label)
            $attrs[$name] = Str::headline($name);
        }
        // Convert to stub-ready PHP code strings
        $rulesStr = '';
        foreach ($rules as $field => $ruleStr) {
            $rulesStr .= "            '$field' => '$ruleStr',\n";
        }
        $attrStr = '';
        foreach ($attrs as $field => $label) {
            $attrStr .= "            '$field' => __('$label'),\n";
        }
        return [
            'rules' => "[\n$rulesStr        ]",
            'attributes' => "[\n$attrStr        ]"
        ];
    }
}
