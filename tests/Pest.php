<?php

use artisanalbyte\InertiaCrudGenerator\Tests\TestCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

uses(TestCase::class)->in('Feature');

/**
 * Before every Feature test, publish the stubs & components.
 */
beforeEach(function () {
    // publish your stubs & components
    $this->artisan('inertia-crud:install')->assertExitCode(0);

    // mock DomPDF loadHTML + download
    Pdf::shouldReceive('loadHTML')
        ->andReturnSelf()
        ->shouldReceive('download')
        ->andReturn(response('PDF_BYTES', 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="posts_export.pdf"',
        ]));

    // mock Excel download
    Excel::shouldReceive('download')
        ->andReturn(response('CSV_BYTES', 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="posts_export.csv"',
        ]));
});
