<?php

namespace artisanalbyte\VILTCrudGenerator\Context;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use artisanalbyte\VILTCrudGenerator\Utils\SchemaIntrospector;
use artisanalbyte\VILTCrudGenerator\Utils\PathResolver;

/**
 * Builds the CrudContext DTO from console input.
 */
class CrudContextBuilder
{
    public function __construct(
        protected SchemaIntrospector $introspector,
        protected PathResolver $pathResolver,
        protected Filesystem $files
    ) {}

    /**
     * @param InputInterface $input The console input (arguments & options)
     * @return CrudContext
     */
    public function build(InputInterface $input): CrudContext
    {
        // Read CLI arguments & options
        $name        = $input->getArgument('name');
        $force       = (bool) $input->getOption('force');
        $formRequest = (bool) $input->getOption('form-request');
        $export      = (bool) $input->getOption('export');

        // Compute naming conventions
        $modelName      = Str::studly(Str::singular($name));
        $modelPlural    = Str::studly(Str::plural($name));
        $modelVar       = Str::camel($modelName);
        $modelPluralVar = Str::camel($modelPlural);
        $tableName      = Str::snake($modelPlural);

        // Introspect DB schema for fields
        $fields = $this->introspector->getFields($tableName);

        // Resolve file paths for each artifact
        $paths = $this->pathResolver->resolve(
            model: $modelName,
            modelPlural: $modelPlural
        );

        // Collect options
        $options = [
            'force'       => $force,
            'formRequest' => $formRequest,
            'export'      => $export,
        ];

        // Construct and return the context
        return new CrudContext(
            modelName: $modelName,
            modelPlural: $modelPlural,
            modelVar: $modelVar,
            modelPluralVar: $modelPluralVar,
            tableName: $tableName,
            fields: $fields,
            paths: $paths,
            options: $options,
        );
    }
}
