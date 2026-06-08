<?php

use App\Models\CronogramaTemplate;
use App\Models\Projeto;
use App\Services\CronogramaTemplateService;
use Carbon\CarbonImmutable;
use Database\Factories\ProjetoFactory;
use Database\Seeders\CronogramaTemplateFaseItensSeeder;
use Database\Seeders\CronogramaTemplateSmartFitSeeder;

/**
 * Carrega o seeder Smart Fit no banco e popula subitens-padrão de fases.
 * Idempotente (updateOrCreate).
 */
function seedTemplateSmartFit(): CronogramaTemplate
{
    (new CronogramaTemplateSmartFitSeeder)->run();
    (new CronogramaTemplateFaseItensSeeder)->run();

    return CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
}

/**
 * Cria um Projeto com data_posse e aplica o template Smart Fit.
 * Retorna o projeto recarregado (relações frescas).
 */
function aplicarSmartFit(string $dataPosse = '2026-08-01'): Projeto
{
    $template = seedTemplateSmartFit();

    $projeto = ProjetoFactory::new()->create(['data_posse' => $dataPosse]);

    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    return $projeto->fresh();
}

/**
 * Simula datas do template Smart Fit a partir de uma data de posse,
 * sem persistir. Útil para asserts de cálculo puro.
 */
function simularSmartFit(string $dataPosse = '2026-08-01'): array
{
    $template = seedTemplateSmartFit();

    return app(CronogramaTemplateService::class)
        ->simular($template, CarbonImmutable::parse($dataPosse));
}
