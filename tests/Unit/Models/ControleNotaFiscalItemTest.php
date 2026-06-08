<?php

it('has a migration removing the legacy reverse authorization link column', function (): void {
    $migrationPath = dirname(__DIR__, 3).'/database/migrations/*.php';

    $migrationContents = collect(glob($migrationPath))
        ->filter(fn (string $path): bool => basename($path) > '2026_05_08_195700_backfill_direct_fiscal_document_links.php')
        ->map(fn (string $path): string => file_get_contents($path) ?: '')
        ->filter(fn (string $contents): bool => str_contains($contents, "Schema::table('controle_nota_fiscal_items'"))
        ->implode("\n");

    expect($migrationContents)
        ->toContain("dropConstrainedForeignId('autorizacao_servico_id')");
});
