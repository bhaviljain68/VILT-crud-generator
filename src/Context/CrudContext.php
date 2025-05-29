<?php

namespace artisanalbyte\VILTCrudGenerator\Context;

use artisanalbyte\VILTCrudGenerator\Utils\ColumnFilter;

/**
 * Data Transfer Object holding all context information
 * required to generate a full CRUD set.
 */
class CrudContext
{
    /** @var string The singular, studly Model name (e.g. "User") */
    public string $modelName;

    /** @var string The plural, studly Model name (e.g. "Users") */
    public string $modelPlural;

    /** @var string The singular, camel variable name (e.g. "user") */
    public string $modelVar;

    /** @var string The plural, camel variable name (e.g. "users") */
    public string $modelPluralVar;

    /** @var string The table name in snake case (e.g. "users") */
    public string $tableName;

    /** @var array<int,array{column:string,type:string,nullable:bool,...}> Column metadata from DBAL */
    public array $fields;

    /** @var array<string,string> Paths for each generated artifact (model, controller, views, etc.) */
    public array $paths;

    /** @var array<string,mixed> All CLI options and flags (force, form-request, export, resourceCollection, useTypeScript, etc.) */
    public array $options;

    /** @var ColumnFilter */
    public ColumnFilter $columnFilter;

    public function __construct(
        string $modelName,
        string $modelPlural,
        string $modelVar,
        string $modelPluralVar,
        string $tableName,
        array $fields,
        array $paths,
        array $options,
        ColumnFilter $columnFilter
    ) {
        $this->modelName       = $modelName;
        $this->modelPlural     = $modelPlural;
        $this->modelVar        = $modelVar;
        $this->modelPluralVar  = $modelPluralVar;
        $this->tableName       = $tableName;
        $this->fields          = $fields;
        $this->paths           = $paths;
        $this->options         = $options;
        $this->columnFilter    = $columnFilter;
    }
}
