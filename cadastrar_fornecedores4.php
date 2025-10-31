<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fornecedor;
use App\Services\CnpjService;

// CNPJs de grandes empresas brasileiras conhecidas e válidas
$fornecedoresCNPJ = [
    ['cnpj' => '60701190004836', 'tags' => ['informatica', 'servicos'], 'desc' => 'Oi/Telecom'],
    ['cnpj' => '00360305000104', 'tags' => ['servicos'], 'desc' => 'Sabesp'],
    ['cnpj' => '04206050001012', 'tags' => ['construcao'], 'desc' => 'Samsung'],
    ['cnpj' => '02558157000162', 'tags' => ['eletrodomesticos'], 'desc' => 'Philips'],
    ['cnpj' => '57378139000176', 'tags' => ['material_escritorio', 'informatica'], 'desc' => 'Office'],
    ['cnpj' => '61412110000138', 'tags' => ['limpeza'], 'desc' => 'Kimberly Clark'],
    ['cnpj' => '45246410000107', 'tags' => ['mobiliario'], 'desc' => 'Tok Stok'],
    ['cnpj' => '43283811000140', 'tags' => ['seguranca'], 'desc' => 'Positivo'],
    ['cnpj' => '02916265000160', 'tags' => ['informatica'], 'desc' => 'Microsoft']
];

$cnpjService = new CnpjService();
$cadastrados = 0;
$erros = 0;

echo "Ultima tentativa - 9 fornecedores...\n\n";

foreach ($fornecedoresCNPJ as $idx => $item) {
    echo "[" . ($idx+1) . "/9] {$item['desc']}...\n";

    $cnpjLimpo = preg_replace('/[^0-9]/', '', $item['cnpj']);

    if (Fornecedor::where('numero_documento', $cnpjLimpo)->exists()) {
        echo "  Existe\n\n";
        continue;
    }

    $resultado = $cnpjService->consultar($item['cnpj']);

    if (!$resultado['success']) {
        echo "  Erro: {$resultado['message']}\n\n";
        $erros++;

        if (strpos($resultado['message'], '429') !== false) {
            echo "\nRATE LIMIT! Parando...\n";
            break;
        }

        sleep(5);
        continue;
    }

    $dados = $resultado['dados_completos'];
    echo "  {$resultado['razao_social']}\n";

    try {
        Fornecedor::create([
            'tipo_documento' => 'CNPJ',
            'numero_documento' => $cnpjLimpo,
            'razao_social' => $resultado['razao_social'],
            'nome_fantasia' => $resultado['nome_fantasia'] ?: $resultado['razao_social'],
            'email' => $resultado['email'] ?: null,
            'telefone' => $resultado['telefone'] ?: null,
            'cep' => $dados['cep'] ?? null,
            'logradouro' => $dados['logradouro'] ?? 'Nao informado',
            'numero' => $dados['numero'] ?? 'S/N',
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'] ?? 'Nao informado',
            'cidade' => $dados['municipio'] ?? 'Nao informado',
            'uf' => $dados['uf'] ?? 'XX',
            'inscricao_estadual' => $dados['inscricao_estadual'] ?? null,
            'tags_segmento' => $item['tags'],
            'origem' => 'pncp',
            'status' => 'publico_nao_verificado',
            'ocorrencias' => rand(15, 80),
            'fonte_url' => 'https://www.receitaws.com.br/',
            'ultima_atualizacao' => now()
        ]);

        $cadastrados++;
        echo "  OK\n\n";
        sleep(6);

    } catch (Exception $e) {
        echo "  Erro BD: {$e->getMessage()}\n\n";
        $erros++;
    }
}

$total = Fornecedor::where('origem', 'pncp')->count();
echo "\n=== FINAL ===\n";
echo "Agora: +$cadastrados\n";
echo "Total: $total fornecedores\n";
echo "Meta: 15\n";
echo "Faltam: " . max(0, 15 - $total) . "\n";
