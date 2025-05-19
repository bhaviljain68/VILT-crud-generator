<?php

namespace artisanalbyte\InertiaCrudGenerator\Commands;

use Illuminate\Console\Command;

class InstallInertiaCrudCommand extends Command
{
    protected $signature   = 'inertia-crud:install';
    protected $description = 'Publish config, PHP stubs, and Vue components for Inertia CRUD Generator';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag'   => 'inertia-crud-generator-config',
            '--force' => true,
        ]);
        $this->call('vendor:publish', [
            '--tag'   => 'inertia-crud-generator-stubs',
            '--force' => true,
        ]);
        $this->call('vendor:publish', [
            '--tag'   => 'inertia-crud-generator-components',
            '--force' => true,
        ]);

        $this->info('✅ Inertia CRUD Generator installed! Config, stubs & components published.');
    }
}
