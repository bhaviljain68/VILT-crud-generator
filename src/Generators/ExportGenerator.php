<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;
use artisanalbyte\VILTCrudGenerator\Utils\StubRenderer;
use Illuminate\Filesystem\Filesystem;

/**
 * Generates the shared export trait and utility when --export is enabled.
 */
class ExportGenerator implements GeneratorInterface
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
        if (! $context->options['export']) {
            return;
        }

        // 1) Export trait
        $traitPath = app_path('Http/Traits/HasExport.php');
        if (! $this->files->exists($traitPath) || $context->options['force']) {
            $stub = $this->renderer->render('traits/has-export.stub', []);
            $this->files->ensureDirectoryExists(dirname($traitPath));
            $this->files->put($traitPath, $stub);
        }

        // 2) ModelCollectionExport utility in app/Exports
        $utilPath = app_path('Exports/ModelCollectionExport.php');
        if (! $this->files->exists($utilPath) || $context->options['force']) {
            $stub = $this->renderer->render('utils/model-collection-export.stub', []);
            $this->files->ensureDirectoryExists(dirname($utilPath));
            $this->files->put($utilPath, $stub);
        }
    }
}
