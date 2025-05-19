<?php

namespace artisanalbyte\InertiaCrudGenerator\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('generates CRUD files without export by default', function () {
    // Clean up old artifacts
    File::delete([
        app_path('Models/Post.php'),
        app_path('Http/Controllers/PostController.php'),
        app_path('Http/Requests/StorePostRequest.php'),
        resource_path('stubs/inertia-crud-generator/controller.stub'),
    ]);

    // Run the command
    $this->artisan('inertia-crud:generate', [
        'Model' => 'Post',
        'Table' => 'posts',
    ])->assertExitCode(0);

    // Assert Model created
    expect(file_exists(app_path('Models/Post.php')))->toBeTrue();
    $model = File::get(app_path('Models/Post.php'));
    expect($model)->toContain('class Post extends Model');

    // Assert Controller exists without export trait
    $ctrl = File::get(app_path('Http/Controllers/PostController.php'));
    expect($ctrl)->not->toContain('HasExport');
    expect($ctrl)->toContain('class PostController');

    // Assert StoreFormRequest created
    expect(file_exists(app_path('Http/Requests/StorePostRequest.php')))->toBeTrue();
    $req = File::get(app_path('Http/Requests/StorePostRequest.php'));
    expect($req)->toContain('class StorePostRequest');

    // Assert resource stubs exist
    expect(file_exists(resource_path('stubs/inertia-crud-generator/model.stub')))->toBeTrue();
});
