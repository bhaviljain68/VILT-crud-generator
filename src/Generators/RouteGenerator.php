<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use Illuminate\Filesystem\Filesystem;

/**
 * Registers resource and export routes under auth and verified middleware.
 */
class RouteGenerator implements GeneratorInterface
{
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function generate(CrudContext $context): void
    {
        $routeName        = strtolower($context->modelPluralVar);
        $controllerClass  = class_basename($context->paths['controllerNamespace']);
        $routesFile       = base_path('routes/web.php');

        $groupStart = "\nRoute::middleware(['auth','verified'])->group(function () {\n";
        $resourceRoute = "\tRoute::resource('{$routeName}', {$controllerClass}::class);\n";
        $exportRoute   = '';
        if ($context->options['export']) {
            $exportRoute = "\tRoute::get('{$routeName}/export', [{$controllerClass}::class, 'export'])\n"
                . "\t\t->name('{$routeName}.export');\n";
        }
        $groupEnd = "});\n";

        $block = $groupStart . $resourceRoute . $exportRoute . $groupEnd;

        $existing = $this->files->get($routesFile);
        // Only append if resource route not already registered
        if (! str_contains($existing, "Route::resource('{$routeName}'")) {
            $this->files->append($routesFile, $block);
        }
    }
}
