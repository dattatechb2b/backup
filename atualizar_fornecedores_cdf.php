#!/usr/bin/env php
<?php

/**
 * Script para atualizar retroativamente fornecedores que responderam CDFs
 * - Enriquece dados com consulta à Receita Federal
 * - Vincula fornecedores aos produtos das CDFs respondidas
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fornecedor;
use App\Models\RespostaCDF;
use App\Models\RespostaCDFItem;
use App\Models\FornecedorItem;
use App\Models\OrcamentoItem;
use App\Services\CnpjService;
use Illuminate\Support\Facades\DB;

echo "===================================================\n";
echo "ATUALIZAÇÃO RETROATIVA DE FORNECEDORES VIA CDF\n";
echo "===================================================\n\n";

try {
    // Buscar fornecedores cadastrados via CDF
    $fornecedores = Fornecedor::where('origem', 'CDF')
        ->where('status', 'cdf_respondida')
        ->get();

    echo "Encontrados " . $fornecedores->count() . " fornecedor(es) cadastrado(s) via CDF.\n\n";

    if ($fornecedores->isEmpty()) {
        echo "Nenhum fornecedor para processar.\n";
        exit(0);
    }

    $cnpjService = app(CnpjService::class);
    $processados = 0;
    $erros = 0;

    foreach ($fornecedores as $fornecedor) {
        echo "---------------------------------------------------\n";
        echo "[{$processados}/{$fornecedores->count()}] Processando: {$fornecedor->razao_social}\n";
        echo "CNPJ: {$fornecedor->numero_documento}\n";

        DB::beginTransaction();

        try {
            // 1. Enriquecer dados do fornecedor com consulta à Receita Federal
            echo "  → Consultando dados na Receita Federal...\n";
            $dadosCnpj = $cnpjService->consultar($fornecedor->numero_documento);

            if ($dadosCnpj['success']) {
                echo "  → Dados encontrados! Atualizando fornecedor...\n";

                $fornecedor->update([
                    'razao_social' => $dadosCnpj['razao_social'],
                    'nome_fantasia' => $dadosCnpj['nome_fantasia'] ?? null,
                    'uf' => $dadosCnpj['uf'] ?? 'XX',
                    'cidade' => $dadosCnpj['municipio'] ?? 'Não informado',
                    'logradouro' => $dadosCnpj['logradouro'] ?? 'Não informado',
                    'bairro' => $dadosCnpj['bairro'] ?? 'Não informado',
                    'numero' => $dadosCnpj['numero'] ?? '',
                    'complemento' => $dadosCnpj['complemento'] ?? '',
                    'cep' => $dadosCnpj['cep'] ?? ''
                ]);

                echo "  ✓ Fornecedor atualizado com sucesso!\n";
                echo "    UF: {$fornecedor->uf}, Cidade: {$fornecedor->cidade}\n";
            } else {
                echo "  ⚠ Não foi possível consultar CNPJ. Mantendo dados atuais.\n";
            }

            // 2. Vincular fornecedor aos produtos das CDFs respondidas
            echo "  → Vinculando produtos...\n";

            $respostasCDF = RespostaCDF::where('fornecedor_id', $fornecedor->id)->get();
            $produtosVinculados = 0;

            foreach ($respostasCDF as $resposta) {
                $itensCDF = RespostaCDFItem::where('resposta_cdf_id', $resposta->id)->get();

                foreach ($itensCDF as $itemCDF) {
                    $itemOrcamento = OrcamentoItem::find($itemCDF->item_orcamento_id);

                    if ($itemOrcamento) {
                        // Vincular fornecedor ao produto
                        FornecedorItem::updateOrCreate(
                            [
                                'fornecedor_id' => $fornecedor->id,
                                'descricao' => $itemOrcamento->descricao
                            ],
                            [
                                'fornecedor_id' => $fornecedor->id,
                                'descricao' => $itemOrcamento->descricao,
                                'codigo_catmat' => $itemOrcamento->codigo_catmat ?? null,
                                'preco_referencia' => $itemCDF->preco_unitario,
                                'unidade' => $itemOrcamento->medida_fornecimento ?? 'Unidade'
                            ]
                        );

                        $produtosVinculados++;
                    }
                }
            }

            echo "  ✓ {$produtosVinculados} produto(s) vinculado(s)!\n";

            DB::commit();
            $processados++;
            echo "  ✅ SUCESSO!\n";

        } catch (\Exception $e) {
            DB::rollBack();
            $erros++;
            echo "  ❌ ERRO: " . $e->getMessage() . "\n";
        }
    }

    echo "\n===================================================\n";
    echo "RESUMO DA ATUALIZAÇÃO\n";
    echo "===================================================\n";
    echo "Total de fornecedores: {$fornecedores->count()}\n";
    echo "Processados com sucesso: {$processados}\n";
    echo "Erros: {$erros}\n";
    echo "===================================================\n\n";

    if ($processados > 0) {
        echo "✅ Fornecedores atualizados com sucesso!\n";
        echo "Agora eles devem aparecer no Mapa de Fornecedores.\n\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
