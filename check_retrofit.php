<?php

require 'vendor/autoload.php';

$_ENV = array_merge($_ENV, (function() {
    $result = [];
    foreach (file('.env') as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        [$k, $v] = explode('=', $line, 2) + [1 => ''];
        $result[trim($k)] = trim($v);
    }
    return $result;
})());

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== VERIFICAÇÃO DE DADOS ===\n\n";

    // 1. Obras com tipos_unidade preenchidos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM obras WHERE tipos_unidade IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Obras com tipos_unidade preenchido: " . $result['total'] . "\n";

    // 2. Projetos com sigla terminando em _RET
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos WHERE sigla LIKE '%_RET'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Projetos com sigla terminando em _RET: " . $result['total'] . "\n";

    // 3. Obras que deveriam ter RETROFIT mas não têm
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT o.id) as total
        FROM obras o
        WHERE o.projeto_id IN (
            SELECT id FROM projetos WHERE sigla LIKE '%_RET'
        )
        AND (o.tipos_unidade IS NULL OR o.tipos_unidade = 'null')
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Obras retrofit SEM dados preenchidos: " . $result['total'] . "\n\n";

    // Atualizar agora
    if ($result['total'] > 0) {
        echo "Atualizando obras retrofit...\n";
        
        $pdo->exec("
            UPDATE obras o
            SET tipos_unidade = JSON_ARRAY('RETROFIT')
            WHERE projeto_id IN (
                SELECT id FROM projetos WHERE sigla LIKE '%_RET'
            )
            AND (tipos_unidade IS NULL OR tipos_unidade = 'null')
        ");
        
        echo "✓ Obras atualizadas com sucesso!\n";
    } else {
        echo "✓ Todos os dados já estão preenchidos!\n";
    }

    echo "\n✅ Agora tente expandir as obras na página.\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
