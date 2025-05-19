<?php

namespace VendorName\InertiaCrudGenerator\Tests\Feature;

use Illuminate\Support\Facades\File;

it('publishes config, stubs, and components via install command', function () {
    // Ensure none exist
    File::delete([
        config_path('inertia-crud-generator.php'),
        resource_path('stubs/inertia-crud-generator/model.stub'),
        resource_path('js/components/ui/input/NumberInput.vue'),
    ]);

    // Run install
    $this->artisan('inertia-crud:install')->assertExitCode(0);

    // Assert config published
    expect(file_exists(config_path('inertia-crud-generator.php')))->toBeTrue();

    // Assert PHP stubs published
    expect(file_exists(resource_path('stubs/inertia-crud-generator/controller.stub')))->toBeTrue();

    // Assert wrapper components published
    expect(file_exists(resource_path('js/components/ui/input/NumberInput.vue')))->toBeTrue();
});
