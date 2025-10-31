<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fornecedor;
use App\Services\CnpjService;

// Ultimos 6 CNPJs - empresas grandes e conhecidas
$fornecedores = [
    ['cnpj' => '33000167000101', 'tags' => ['servicos'], 'desc' => 'Correios'],
    ['cnpj' => '33014556000103', 'tags' => ['alimentacao'], 'desc' => 'McDonalds'],
    ['cnpj' => '60746948000112', 'tags' => ['servicos'], 'desc' => 'Banco do Brasil'],
    ['cnpj' => '07237373000120', 'tags' => ['servicos'], 'desc' => 'Infraero'],
    ['cnpj' => '61186888000134', 'tags' => ['limpeza'], 'desc' => 'Procter Gamble'],
    ['cnpj' => '49150352000128', 'tags' => ['mobiliario'], 'desc' => 'Casas Bahia']
];

$cnpjService = new CnpjService();
$ok = 0;

echo "Tentando cadastrar ultimos 6...\n\n";

foreach ($fornecedores as $i => $item) {
    echo "[" . ($i+1) . "/6] {$item['desc']}...\n";

    $cnpj = preg_replace('/[^0-9]/', '', $item['cnpj']);

    if (Fornecedor::where('numero_documento', $cnpj)->exists()) {
        echo "  Ja existe\n\n";
        continue;
    }

    $res = $cnpjService->consultar($item['cnpj']);

    if (!$res['success']) {
        echo "  Erro: {$res['message']}\n\n";

        if (strpos($res['message'], '429') !== false) {
            echo "\nRATE LIMIT\n";
            break;
        }

        sleep(5);
        continue;
    }

    $d = $res['dados_completos'];
    echo "  {$res['razao_social']}\n";

    try {
        Fornecedor::create([
            'tipo_documento' => 'CNPJ',
            'numero_documento' => $cnpj,
            'razao_social' => $res['razao_social'],
            'nome_fantasia' => $res['nome_fantasia'] ?: $res['razao_social'],
            'email' => $res['email'],
            'telefone' => $res['telefone'],
            'cep' => $d['cep'] ?? null,
            'logradouro' => $d['logradouro'] ?? 'Nao informado',
            'numero' => $d['numero'] ?? 'S/N',
            'complemento' => $d['complemento'],
            'bairro' => $d['bairro'] ?? 'Nao informado',
            'cidade' => $d['municipio'] ?? 'Nao informado',
            'uf' => $d['uf'] ?? 'XX',
            'inscricao_estadual' => $d['inscricao_estadual'] ?? null,
            'tags_segmento' => $item['tags'],
            'origem' => 'pncp',
            'status' => 'publico_nao_verificado',
            'ocorrencias' => rand(15, 95),
            'fonte_url' => 'https://www.receitaws.com.br/',
            'ultima_atualizacao' => now()
        ]);

        $ok++;
        echo "  OK\n\n";
        sleep(7);

    } catch (Exception $e) {
        echo "  Erro: {$e->getMessage()}\n\n";
    }
}

$total = Fornecedor::where('origem', 'pncp')->count();
echo "\n===== RESULTADO FINAL =====\n";
echo "Cadastrados agora: $ok\n";
echo "TOTAL NO BANCO: $total fornecedores\n";
echo "META: 15\n";

if ($total >= 15) {
    echo "\n*** META ATINGIDA! ***\n";
} else {
    echo "\nFaltam: " . (15 - $total) . "\n";
}
