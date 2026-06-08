<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('mantém manifesto explícito de resources ativos consistente com inventário', function () {
    $manifest = collect(activeFilamentResourceManifest())->sort()->values();
    $discovered = collect(discoveredActiveFilamentResources());

    foreach (activeFilamentResourceManifest() as $resourceClass) {
        expect(class_exists($resourceClass))->toBeTrue("Resource ausente no código: {$resourceClass}");
    }

    expect($manifest->diff($discovered)->all())->toBe([]);
    expect($discovered->diff($manifest)->all())->toBe([]);
});
