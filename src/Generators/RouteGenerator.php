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
        $routeName = strtolower($context->modelPluralVar);
        $controllerClass = $context->modelName . 'Controller';
        $controllerFQCN = 'App\\Http\\Controllers\\' . $controllerClass;
        $routesFile = base_path('routes/web.php');

        // Read the existing routes file
        $existing = $this->files->exists($routesFile) ? $this->files->get($routesFile) : '';

        // --- Insert use statement after last use ---
        $useStatement = "use {$controllerFQCN};";
        if (!str_contains($existing, $useStatement)) {
            // Find all use statements
            $matches = [];
            preg_match_all('/^use [^;]+;/m', $existing, $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches[0])) {
                $lastUse = end($matches[0]);
                $insertPos = $lastUse[1] + strlen($lastUse[0]);
                // Insert without extra newline
                $existing = substr_replace($existing, "\n" . $useStatement, $insertPos, 0);
            } else {
                // Fallback: after <?php
                if (str_starts_with($existing, '<?php')) {
                    $pos = strpos($existing, "\n");
                    if ($pos !== false) {
                        $existing = substr_replace($existing, "\n" . $useStatement, $pos + 1, 0);
                    } else {
                        $existing = "<?php\n" . $useStatement . substr($existing, 5);
                    }
                } else {
                    $existing = $useStatement . "\n" . $existing;
                }
            }
            $this->files->put($routesFile, $existing);
        }

        // --- VILT Generator Route Block ---
        $blockStart = "// VILT Generator Routes START";
        $blockEnd = "// VILT Generator Routes END";
        $resourceRoute = "\tRoute::resource('{$routeName}', {$controllerClass}::class);\n";
        $exportRoute = '';
        if ($context->options['export']) {
            $exportRoute = "\tRoute::get('{$routeName}/export', [{$controllerClass}::class, 'export'])\n"
                . "\t\t->name('{$routeName}.export');\n";
        }
        $routeBlock = $resourceRoute . $exportRoute;
        // Find or create the VILT block
        $pattern = "/Route::middleware\\(\\['auth','verified'\\]\)\\->group\\(function \(\) \{\\s*\/\/ VILT Generator Routes START \(<-DO NOT REMOVE THIS COMMENT\)(.*?)\/\/ VILT Generator Routes END\(<-DO NOT REMOVE THIS COMMENT\)\\s*\}\);/s";
        if (preg_match($pattern, $existing, $matches, PREG_OFFSET_CAPTURE)) {
            // Block exists: insert before END if not already present
            $blockContent = $matches[1][0];
            if (!str_contains($blockContent, $resourceRoute)) {
                $newBlockContent = rtrim($blockContent) . "\n" . $routeBlock;
                $newBlock = "Route::middleware(['auth','verified'])->group(function () {\n    // VILT Generator Routes START (<-DO NOT REMOVE THIS COMMENT) " . $newBlockContent . "\n    // VILT Generator Routes END(<-DO NOT REMOVE THIS COMMENT)\n});";
                $existing = substr_replace($existing, $newBlock, $matches[0][1], strlen($matches[0][0]));
                $this->files->put($routesFile, $existing);
            }
        } else {
            // Block does not exist: insert BEFORE require __DIR__.'/settings.php'; using strpos for reliability
            $needle = "require __DIR__.'/settings.php';";
            $insertPos = strpos($existing, $needle);
            if ($insertPos !== false) {
                $block = "Route::middleware(['auth','verified'])->group(function () {\n    // VILT Generator Routes START (<-DO NOT REMOVE THIS COMMENT) \n\n" . $routeBlock . "\n    // VILT Generator Routes END(<-DO NOT REMOVE THIS COMMENT)\n});\n\n";
                $existing = substr($existing, 0, $insertPos) . $block . substr($existing, $insertPos);
                $this->files->put($routesFile, $existing);
            } else {
                // Fallback: append at end
                $block = "\nRoute::middleware(['auth','verified'])->group(function () {\n    // VILT Generator Routes START (<-DO NOT REMOVE THIS COMMENT) \n\n" . $routeBlock . "\n    // VILT Generator Routes END(<-DO NOT REMOVE THIS COMMENT)\n});\n";
                $this->files->put($routesFile, $existing . $block);
            }
        }
    }
}
