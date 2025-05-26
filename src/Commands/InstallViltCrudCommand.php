<?php

namespace artisanalbyte\VILTCrudGenerator\Commands;

use Illuminate\Console\Command;

class InstallViltCrudCommand extends Command
{
    protected $signature   = 'vilt-crud:install';
    protected $description = 'Publish config, PHP stubs, and Vue components for Inertia CRUD Generator';

    public function handle()
    {
        // Single publish call
        $this->call('vendor:publish', [
            '--tag'   => 'vilt-crud-generator',
            '--force' => true,
        ]);

        $this->info('✅ VILT CRUD Generator installed! All assets published.');
    }
}
