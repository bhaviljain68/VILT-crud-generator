<?php

namespace artisanalbyte\VILTCrudGenerator\Commands;

use Illuminate\Console\Command;

class InstallInertiaCrudCommand extends Command
{
    protected $signature   = 'inertia-crud:install';
    protected $description = 'Publish config, PHP stubs, and Vue components for Inertia CRUD Generator';

    public function handle()
    {
        // Single publish call
        $this->call('vendor:publish', [
            '--tag'   => 'inertia-crud-generator',
            '--force' => true,
        ]);

        $this->info('✅ Inertia CRUD Generator installed! All assets published.');
    }
}
