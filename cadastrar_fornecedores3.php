<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fornecedor;
use App\Services\CnpjService;

// CNPJs de empresas conhecidas e válidas
$fornecedoresCNPJ = [
    // Material Escritório
    ['cnpj' => '60394723004501', 'tags' => ['material_escritorio'], 'desc' => 'Papel'],

    // Informática
    ['cnpj' => '07282807000103', 'tags' => ['informatica'], 'desc' => 'TI'],
    ['cnpj' => '71508516000160', 'tags' => ['informatica'], 'desc' => 'Tech'],

    // Mobiliário
    ['cnpj' => '43662476000106', 'tags' => ['mobiliario'], 'desc' => 'Moveis'],

    // Construção
    ['cnpj' => '61591325000151', 'tags' => ['construcao'], 'desc' => 'Construcao'],
    ['cnpj' => '10882790000129', 'tags' => ['construcao'], 'desc' => 'Mat Construcao'],

    // Limpeza
    ['cnpj' => '07711949000173', 'tags' => ['limpeza'], 'desc' => 'Produtos Limpeza'],

    // Alimentação
    ['cnpj' => '45543915000181', 'tags' => ['alimentacao'], 'desc' => 'Alimentos'],

    // Eletrodomésticos
    ['cnpj' => '59291534000138', 'tags' => ['eletrodomesticos'], 'desc' => 'Eletros'],

    // Segurança
    ['cnpj' => '15124464000173', 'tags' => ['seguranca'], 'desc' => 'Seguranca'],

    // Veículos
    ['cnpj' => '54500944000142', 'tags' => ['veiculos'], 'desc' => 'Auto Pecas']
];

$cnpjService = new CnpjService();
$cadastrados = 0;
$erros = 0;
$total = count($fornecedoresCNPJ);

echo "Tentando cadastrar $total fornecedores (pausa 5s)...\n\n";

foreach ($fornecedoresCNPJ as $idx => $item) {
    $progresso = ($idx + 1) . "/$total";
    echo "[$progresso] {$item['desc']} ({$item['cnpj']})...\n";

    $cnpjLimpo = preg_replace('/[^0-9]/', '', $item['cnpj']);

    if (Fornecedor::where('numero_documento', $cnpjLimpo)->exists()) {
        echo "  -> Ja existe\n\n";
        continue;
    }

    $resultado = $cnpjService->consultar($item['cnpj']);

    if (!$resultado['success']) {
        echo "  -> Erro: {$resultado['message']}\n\n";
        $erros++;

        // Se for rate limit, para tudo
        if (strpos($resultado['message'], '429') !== false || strpos($resultado['message'], 'Too many') !== false) {
            echo "\n*** RATE LIMIT ATINGIDO. Parando... ***\n";
            break;
        }

        sleep(5);
        continue;
    }

    $dados = $resultado['dados_completos'];

    echo "  -> {$resultado['razao_social']}\n";
    echo "  -> {$resultado['telefone']}\n";

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
        echo "  -> OK!\n\n";

        sleep(5); // 5s pausa

    } catch (Exception $e) {
        echo "  -> Erro BD: {$e->getMessage()}\n\n";
        $erros++;
    }
}

$totalBanco = Fornecedor::where('origem', 'pncp')->count();

echo "\n===== RESULTADO =====\n";
echo "Cadastrados agora: $cadastrados\n";
echo "Erros: $erros\n";
echo "Total PNCP no banco: $totalBanco\n";
echo "Meta: 15 fornecedores\n";
echo "Faltam: " . (15 - $totalBanco) . "\n";
