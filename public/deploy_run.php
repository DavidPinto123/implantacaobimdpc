<?php
/**
 * SCRIPT TEMPORÁRIO DE DEPLOY — USE E APAGUE IMEDIATAMENTE DEPOIS
 * Acesse: https://implantacaobimdpc.com.br/deploy_run.php?key=bim2026deploy
 * Após rodar, delete este arquivo via FTP ou Gerenciador de Arquivos.
 */

if (($_GET['key'] ?? '') !== 'bim2026deploy') {
    http_response_code(403);
    exit('Acesso negado.');
}

// Sobe um nível para a raiz do Laravel
chdir(dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$saida = [];

// 1. Rodar migrations pendentes
$kernel->call('migrate', ['--force' => true]);
$saida[] = '<b>migrate:</b><pre>' . htmlspecialchars($kernel->output()) . '</pre>';

// 2. Rodar o seeder de permissão
$kernel->call('db:seed', ['--class' => 'ValoresPlanejamentoPermissionSeeder', '--force' => true]);
$saida[] = '<b>seeder ValoresPlanejamentoPermissionSeeder:</b><pre>' . htmlspecialchars($kernel->output()) . '</pre>';

// 3. Limpar caches
$kernel->call('optimize:clear');
$saida[] = '<b>optimize:clear:</b><pre>' . htmlspecialchars($kernel->output()) . '</pre>';

// 4. Recriar cache de config/rotas
$kernel->call('optimize');
$saida[] = '<b>optimize:</b><pre>' . htmlspecialchars($kernel->output()) . '</pre>';

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Deploy</title>
<style>body{font-family:monospace;padding:20px;background:#111;color:#0f0;}
pre{background:#000;padding:10px;border-radius:4px;white-space:pre-wrap;}
.ok{color:#2dd67c;} .warn{color:#f5ba00;}</style></head><body>';
echo '<h2 class="ok">Deploy concluído ✓</h2>';
foreach ($saida as $bloco) {
    echo '<div style="margin-bottom:16px">' . $bloco . '</div>';
}
echo '<p class="warn"><strong>⚠ APAGUE ESTE ARQUIVO AGORA via FTP ou Gerenciador de Arquivos da Hostinger!</strong></p>';
echo '</body></html>';
