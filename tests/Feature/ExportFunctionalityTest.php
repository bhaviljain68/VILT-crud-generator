<?php

namespace artisanalbyte\InertiaCrudGenerator\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\PostController;
use artisanalbyte\InertiaCrudGenerator\Utils\ModelCollectionExport;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutExceptionHandling();
    // 1) Create the posts table
    \Schema::create('posts', function ($t) {
        $t->id();
        $t->string('title');
        $t->timestamps();
    });

    // 2) Insert sample data
    \DB::table('posts')->insert([
        ['title' => 'Foo', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // 3) Generate the Post CRUD with export enabled
    $this->artisan('inertia-crud:generate', [
        'Model' => 'Post',
        'Table' => 'posts',
        '--export' => true,
    ])->assertExitCode(0);
});

it('downloads CSV when hitting export route', function () {
    // Simulate a GET request to /posts/export?format=csv
    $response = $this->get('/posts/export?format=csv');
    $response->assertStatus(200);
    $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
});

it('downloads PDF when hitting export route', function () {
    $response = $this->get('/posts/export?format=pdf');
    $response->assertStatus(200);
    $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
});
