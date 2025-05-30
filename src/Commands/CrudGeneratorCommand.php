<?php

namespace artisanalbyte\VILTCrudGenerator\Commands;

use Illuminate\Console\Command;
use artisanalbyte\VILTCrudGenerator\Context\CrudContextBuilder;
use artisanalbyte\VILTCrudGenerator\Generators\ModelGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ControllerGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ViewGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\FormRequestGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\RouteGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ResourceGenerator;

class CrudGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vilt-crud:generate
                            {name : The model/table name}
                            {--force : Overwrite existing files}
                            {--form-request : Generate FormRequest classes}
                            {--resource-collection : Generate Resource and ResourceCollection classes}
                            {--no-ts : Generate Vue pages without TypeScript}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VILT CRUD scaffold (Model, Controller, Vue pages, FormRequests)';

    public function handle()
    {
        // Build the context DTO from input
        $ctx = app(CrudContextBuilder::class)->build($this->input);

        // Core generators
        $generators = [
            ModelGenerator::class,
            ControllerGenerator::class,
            ViewGenerator::class,
            RouteGenerator::class,
        ];

        if ($ctx->options['formRequest']) {
            $generators[] = FormRequestGenerator::class;
        }
        // Resource/Collection generation (optional)
        if ($ctx->options['resourceCollection']) {
            $generators[] = ResourceGenerator::class;
        }

        // Execute each generator
        foreach ($generators as $genClass) {
            app($genClass)->generate($ctx);
        }

        $this->info('🎉 VILT CRUD scaffolding generated successfully.');
    }
}
