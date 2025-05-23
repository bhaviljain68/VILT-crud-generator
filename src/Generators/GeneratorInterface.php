<?php

namespace artisanalbyte\VILTCrudGenerator\Generators;

use artisanalbyte\VILTCrudGenerator\Context\CrudContext;

/**
 * Interface for all CRUD generators in the VILT Crud Generator package.
 */
interface GeneratorInterface
{
    /**
     * Generate a specific CRUD artifact (model, controller, view, etc.).
     *
     * @param CrudContext $context The DTO containing all generation context
     * @return void
     */
    public function generate(CrudContext $context): void;
}
