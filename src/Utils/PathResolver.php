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
        // $modelNamespace      = "App\\Models\\{$model}";
        // $controllerNamespace = "App\\Http\\Controllers\\{$modelPlural}Controller";
        // $requestNamespace    = "App\\Http\\Requests\\{$model}Request";
        $modelNamespace      = "App\\Models";
        $controllerNamespace = "App\\Http\\Controllers";
        $requestNamespace    = "App\\Http\\Requests";
        $resourceNamespace   = "App\\Http\\Resources";

        // Filesystem paths
        $modelPath      = app_path("Models/{$model}.php");
        $controllerPath = app_path("Http/Controllers/{$model}Controller.php");
        $storeRequestPath    = app_path("Http/Requests/{$model}StoreRequest.php");
        $updateRequestPath    = app_path("Http/Requests/{$model}UpdateRequest.php");
        $requestPath = app_path("Http/Requests/{$model}Request.php");

        // Inertia/Vue pages directory
        $vueDir = resource_path('js/Pages/' . Str::studly($modelPlural));

        return [
            // Namespaces
            'modelNamespace'      => $modelNamespace,
            'controllerNamespace' => $controllerNamespace,
            'requestNamespace'    => $requestNamespace,
            'resourceNamespace'   => $resourceNamespace,

            // Paths
            'modelPath'           => $modelPath,
            'controllerPath'      => $controllerPath,
            'storeRequestPath'     => $storeRequestPath,
            'updateRequestPath'    => $updateRequestPath,
            'requestPath'          => $requestPath,

            // View directory (Inertia Vue)
            'vueDirectory'   => $vueDir,
        ];
    }
}
