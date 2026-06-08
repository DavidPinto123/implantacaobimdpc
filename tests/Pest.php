<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/Support/AdminResourceTestHelpers.php';
require_once __DIR__.'/Support/ObrasAsaResourceTestHelpers.php';

uses(TestCase::class)->in('Feature');
uses(TestCase::class, LazilyRefreshDatabase::class)->in('Browser');

foreach (glob(__DIR__.'/Support/*.php') as $supportFile) {
    require_once $supportFile;
}

pest()
    ->browser()
    ->timeout(10000);
