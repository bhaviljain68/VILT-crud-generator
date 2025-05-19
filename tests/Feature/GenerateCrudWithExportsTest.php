<?php

namespace VendorName\InertiaCrudGenerator\Tests\Feature;

use Illuminate\Support\Facades\File;

it('includes export functionality when --export is used', function () {
    // Run generator with export flag
    $this->artisan('inertia-crud:generate', [
        'Model'   => 'Post',
        '--export' => true,
    ])->assertExitCode(0);

    // Controller should now use HasExport trait
    $ctrl = File::get(app_path('Http/Controllers/PostController.php'));
    expect($ctrl)->toContain('use HasExport;')
                 ->and($ctrl)->toContain('protected string $modelClass = App\Models\Post::class;');

    // routes/web.php should have export route
    $routes = File::get(base_path('routes/web.php'));
    expect($routes)->toContain("Route::get('posts/export'");
});
