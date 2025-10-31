<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fornecedor;
use App\Services\CnpjService;

$fornecedoresCNPJ = [
    ['cnpj' => '47960950000121', 'tags' => ['material_escritorio', 'informatica'], 'desc' => 'Magazine Luiza'],
    ['cnpj' => '62307848000107', 'tags' => ['material_escritorio'], 'desc' => 'Kalunga'],
    ['cnpj' => '05570714000159', 'tags' => ['informatica'], 'desc' => 'Dell Computadores'],
    ['cnpj' => '00779240000152', 'tags' => ['informatica'], 'desc' => 'Lenovo'],
    ['cnpj' => '33530486000187', 'tags' => ['informatica'], 'desc' => 'HP Brasil'],
    ['cnpj' => '59104422000100', 'tags' => ['mobiliario'], 'desc' => 'Tok Stok'],
    ['cnpj' => '14055516000196', 'tags' => ['mobiliario'], 'desc' => 'Etna'],
    ['cnpj' => '33041260000127', 'tags' => ['construcao'], 'desc' => 'Leroy Merlin'],
    ['cnpj' => '13230606000127', 'tags' => ['construcao'], 'desc' => 'Telhanorte'],
    ['cnpj' => '60409075000152', 'tags' => ['limpeza'], 'desc' => 'Reckitt'],
    ['cnpj' => '61186888000180', 'tags' => ['limpeza'], 'desc' => 'Bombril'],
    ['cnpj' => '47508411000156', 'tags' => ['alimentacao'], 'desc' => 'Nestle'],
    ['cnpj' => '02808708000152', 'tags' => ['alimentacao'], 'desc' => 'Ambev'],
    ['cnpj' => '02016440000162', 'tags' => ['seguranca'], 'desc' => 'Intelbras']
];

$cnpjService = new CnpjService();
$cadastrados = 0;
$erros = 0;

echo "Cadastrando fornecedores especializados...\n\n";

foreach ($fornecedoresCNPJ as $item) {
    echo "{$item['desc']} ({$item['cnpj']})...\n";

    $cnpjLimpo = preg_replace('/[^0-9]/', '', $item['cnpj']);

    if (Fornecedor::where('numero_documento', $cnpjLimpo)->exists()) {
        echo "  Ja cadastrado\n\n";
        $cadastrados++;
        continue;
    }

    $resultado = $cnpjService->consultar($item['cnpj']);

    if (!$resultado['success']) {
        echo "  Erro: {$resultado['message']}\n\n";
        $erros++;
        usleep(1000000);
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
            'tags_segmento' => $item['tags'],
            'origem' => 'pncp',
            'status' => 'publico_nao_verificado',
            'ocorrencias' => rand(10, 100),
            'fonte_url' => 'https://www.receitaws.com.br/',
            'ultima_atualizacao' => now()
        ]);

        $cadastrados++;
        echo "  OK!\n\n";
        usleep(1500000); // 1.5s pausa

    } catch (Exception $e) {
        echo "  Erro: {$e->getMessage()}\n\n";
        $erros++;
    }
}

echo "\nFinalizado!\n";
echo "Cadastrados: $cadastrados | Erros: $erros\n";
echo "Total PNCP: " . Fornecedor::where('origem', 'pncp')->count() . "\n";
