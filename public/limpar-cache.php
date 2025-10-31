<?php
/**
 * Script de Limpeza de Cache - Emergência
 * Acesse: https://catasaltas.dattapro.online/module-proxy/price_basket/limpar-cache.php
 */

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Limpeza de Cache - Cesta de Preços</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        .success { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { background: #dbeafe; color: #1e40af; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f1f5f9; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Limpeza de Cache - Cesta de Preços</h1>
        <p><strong>Ambiente:</strong> Produção (catasaltas.dattapro.online)</p>
        <hr>
';

$resultados = [];

// 1. Limpar views compiladas
echo '<h2>1. Limpando Views Compiladas</h2>';
$viewsPath = __DIR__ . '/../storage/framework/views';
$arquivosRemovidos = 0;

if (is_dir($viewsPath)) {
    $arquivos = glob($viewsPath . '/*.php');
    foreach ($arquivos as $arquivo) {
        if (unlink($arquivo)) {
            $arquivosRemovidos++;
        }
    }
    echo "<div class='success'>✓ {$arquivosRemovidos} views compiladas removidas</div>";
} else {
    echo "<div class='error'>✗ Diretório de views não encontrado</div>";
}

// 2. Limpar cache da aplicação
echo '<h2>2. Limpando Cache da Aplicação</h2>';
exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && php artisan cache:clear 2>&1', $output1, $return1);
if ($return1 === 0) {
    echo "<div class='success'>✓ Cache da aplicação limpo</div>";
    echo "<pre>" . implode("\n", $output1) . "</pre>";
} else {
    echo "<div class='error'>✗ Erro ao limpar cache da aplicação</div>";
}

// 3. Limpar cache de views
echo '<h2>3. Limpando Cache de Views (Artisan)</h2>';
exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && php artisan view:clear 2>&1', $output2, $return2);
if ($return2 === 0) {
    echo "<div class='success'>✓ Cache de views limpo</div>";
    echo "<pre>" . implode("\n", $output2) . "</pre>";
} else {
    echo "<div class='error'>✗ Erro ao limpar cache de views</div>";
}

// 4. Limpar cache de configuração
echo '<h2>4. Limpando Cache de Configuração</h2>';
exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && php artisan config:clear 2>&1', $output3, $return3);
if ($return3 === 0) {
    echo "<div class='success'>✓ Cache de configuração limpo</div>";
    echo "<pre>" . implode("\n", $output3) . "</pre>";
} else {
    echo "<div class='error'>✗ Erro ao limpar cache de configuração</div>";
}

// 5. Limpar cache de rotas
echo '<h2>5. Limpando Cache de Rotas</h2>';
exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && php artisan route:clear 2>&1', $output4, $return4);
if ($return4 === 0) {
    echo "<div class='success'>✓ Cache de rotas limpo</div>";
    echo "<pre>" . implode("\n", $output4) . "</pre>";
} else {
    echo "<div class='error'>✗ Erro ao limpar cache de rotas</div>";
}

// 6. Limpar OPcache
echo '<h2>6. Limpando OPcache (PHP)</h2>';
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<div class='success'>✓ OPcache limpo com sucesso</div>";
    } else {
        echo "<div class='error'>✗ Erro ao limpar OPcache</div>";
    }
} else {
    echo "<div class='info'>ℹ OPcache não está disponível</div>";
}

// 7. Corrigir permissões
echo '<h2>7. Corrigindo Permissões</h2>';
exec('chmod -R 775 ' . escapeshellarg(__DIR__ . '/../storage') . ' 2>&1', $output5, $return5);
exec('chown -R www-data:www-data ' . escapeshellarg(__DIR__ . '/../storage') . ' 2>&1', $output6, $return6);
if ($return5 === 0 && $return6 === 0) {
    echo "<div class='success'>✓ Permissões corrigidas</div>";
} else {
    echo "<div class='error'>✗ Erro ao corrigir permissões (pode precisar de sudo)</div>";
}

echo '
        <hr>
        <h2>✅ Limpeza Concluída!</h2>
        <div class="info">
            <strong>Próximos passos:</strong><br>
            1. Limpe o cache do seu navegador (Ctrl+Shift+Del)<br>
            2. Recarregue a página com Ctrl+F5<br>
            3. Teste as funcionalidades novamente<br>
            <br>
            <strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após o uso por segurança!<br>
            <code>rm /home/dattapro/modulos/cestadeprecos/public/limpar-cache.php</code>
        </div>
        <p><small>Executado em: ' . date('d/m/Y H:i:s') . '</small></p>
    </div>
</body>
</html>';
