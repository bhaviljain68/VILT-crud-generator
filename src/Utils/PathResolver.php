<?php

namespace artisanalbyte\VILTCrudGenerator\Utils;

use Illuminate\Support\Str;

/**
 * Resolves fully qualified namespaces and filesystem paths
 * for CRUD artifacts based on the model name.
 */
class PathResolver
{
    /**
     * Build all paths and namespaces for the given model
     *
     * @param string $model       Singular studly Model name, e.g. User
     * @param string $modelPlural Plural studly Model name, e.g. Users
     * @return array<string,string> Array of keys to paths/namespaces
     */
    public function resolve(string $model, string $modelPlural): array
    {
        // PSR-4 root namespaces
        $modelNamespace      = "App\\Models\\{$model}";
        $controllerNamespace = "App\\Http\\Controllers\\{$modelPlural}Controller";
        $requestNamespace    = "App\\Http\\Requests\\{$model}Request";

        // Filesystem paths
        $modelPath      = app_path("Models/{$model}.php");
        $controllerPath = app_path("Http/Controllers/{$modelPlural}Controller.php");
        $requestPath    = app_path("Http/Requests/{$model}Request.php");

        // Inertia/Vue pages directory
        $vueDir = resource_path('js/Pages/' . Str::studly($modelPlural));

        return [
            // Namespaces
            'model_namespace'      => $modelNamespace,
            'controller_namespace' => $controllerNamespace,
            'request_namespace'    => $requestNamespace,

            // Paths
            'model_path'      => $modelPath,
            'controller_path' => $controllerPath,
            'request_path'    => $requestPath,

            // View directory (Inertia Vue)
            'vue_directory'   => $vueDir,
        ];
    }
}
