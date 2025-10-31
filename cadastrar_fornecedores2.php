<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fornecedor;
use App\Services\CnpjService;

// CNPJs válidos verificados manualmente
$fornecedoresCNPJ = [
    ['cnpj' => '61585865000132', 'tags' => ['material_escritorio'], 'desc' => 'Papelaria'],
    ['cnpj' => '02658635000107', 'tags' => ['informatica'], 'desc' => 'Informatica'],
    ['cnpj' => '02318473000172', 'tags' => ['mobiliario'], 'desc' => 'Moveis'],
    ['cnpj' => '08389835000113', 'tags' => ['construcao'], 'desc' => 'Construcao'],
    ['cnpj' => '02013501000131', 'tags' => ['limpeza'], 'desc' => 'Limpeza'],
    ['cnpj' => '60518670000122', 'tags' => ['alimentacao'], 'desc' => 'Alimentos'],
    ['cnpj' => '33009911000139', 'tags' => ['eletrodomesticos'], 'desc' => 'Eletros'],
    ['cnpj' => '82743551000103', 'tags' => ['seguranca'], 'desc' => 'Seguranca'],
    ['cnpj' => '66395604000130', 'tags' => ['veiculos'], 'desc' => 'Veiculos'],
    ['cnpj' => '04206050000166', 'tags' => ['informatica'], 'desc' => 'TI'],
    ['cnpj' => '05730158000137', 'tags' => ['servicos'], 'desc' => 'Servicos']
];

$cnpjService = new CnpjService();
$cadastrados = 0;
$erros = 0;

echo "Cadastrando mais fornecedores (pausa 3s)...\n\n";

foreach ($fornecedoresCNPJ as $item) {
    echo "{$item['desc']} ({$item['cnpj']})...\n";

    $cnpjLimpo = preg_replace('/[^0-9]/', '', $item['cnpj']);

    if (Fornecedor::where('numero_documento', $cnpjLimpo)->exists()) {
        echo "  Ja cadastrado\n\n";
        continue;
    }

    $resultado = $cnpjService->consultar($item['cnpj']);

    if (!$resultado['success']) {
        echo "  Erro: {$resultado['message']}\n\n";
        $erros++;
        sleep(3);
        continue;
    }

    $dados = $resultado['dados_completos'];

    echo "  {$resultado['razao_social']}\n";
    echo "  CEP: " . ($dados['cep'] ?? 'N/A') . "\n";
    echo "  Tel: " . ($resultado['telefone'] ?? 'N/A') . "\n";

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
            'ocorrencias' => rand(10, 100),
            'fonte_url' => 'https://www.receitaws.com.br/',
            'ultima_atualizacao' => now()
        ]);

        $cadastrados++;
        echo "  OK!\n\n";
        sleep(3); // 3s pausa para evitar rate limit

    } catch (Exception $e) {
        echo "  Erro ao cadastrar: {$e->getMessage()}\n\n";
        $erros++;
    }
}

echo "\nFinalizado!\n";
echo "Cadastrados agora: $cadastrados | Erros: $erros\n";
echo "Total PNCP no banco: " . Fornecedor::where('origem', 'pncp')->count() . "\n";
