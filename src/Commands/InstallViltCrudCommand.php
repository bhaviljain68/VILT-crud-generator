<?php

namespace artisanalbyte\VILTCrudGenerator\Commands;

use Illuminate\Console\Command;

class InstallViltCrudCommand extends Command
{
    protected $signature   = 'vilt-crud:publish {--stubs} {--all}';
    protected $description = 'Publish config, PHP stubs, and Vue components for Inertia CRUD Generator';

    public function handle()
    {
        if ($this->option('all')) {
            $this->call('vendor:publish', [
                '--tag'   => 'vilt-crud-all',
                '--force' => true,
            ]);
            $this->info('✅ All VILT CRUD Generator assets published.');
            return;
        }

        // Default: config + vue
        $this->call('vendor:publish', [
            '--tag'   => 'vilt-crud-default',
            '--force' => true,
        ]);

        if ($this->option('stubs')) {
            $this->call('vendor:publish', [
                '--tag'   => 'vilt-crud-stubs',
                '--force' => true,
            ]);
        }

        $this->info('✅ VILT CRUD Generator assets published as per selected options.');
    }
}
