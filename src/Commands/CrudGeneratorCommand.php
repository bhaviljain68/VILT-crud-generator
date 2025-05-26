<?php

namespace artisanalbyte\VILTCrudGenerator\Commands;

use Illuminate\Console\Command;
use artisanalbyte\VILTCrudGenerator\Context\CrudContextBuilder;
use artisanalbyte\VILTCrudGenerator\Generators\ModelGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ControllerGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ViewGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\FormRequestGenerator;
use artisanalbyte\VILTCrudGenerator\Generators\ExportGenerator;
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
                            {--export : Include export trait and utility}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VILT CRUD scaffold (Model, Controller, Vue pages, FormRequests, optional Export)';

    public function handle()
    {
        // Build the context DTO from input
        $ctx = app(CrudContextBuilder::class)->build($this->input);

        // Core generators
        $generators = [
            ModelGenerator::class,
            ControllerGenerator::class,
            ResourceGenerator::class,
            FormRequestGenerator::class,
            ViewGenerator::class,
            RouteGenerator::class,
        ];

        // Optional export support
        if ($ctx->options['export']) {
            $generators[] = ExportGenerator::class;
        }

        // Execute each generator
        foreach ($generators as $genClass) {
            app($genClass)->generate($ctx);
        }

        $this->info('🎉 VILT CRUD scaffolding generated successfully.');
    }
}
