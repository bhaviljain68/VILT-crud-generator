<?php

namespace artisanalbyte\InertiaCrudGenerator\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\PostController;
use artisanalbyte\InertiaCrudGenerator\Utils\ModelCollectionExport;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    // Create a dummy posts table
    \Schema::create('posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });

    // Insert sample data
    \DB::table('posts')->insert([
        ['title' => 'Foo', 'created_at' => now(), 'updated_at' => now()],
    ]);
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
