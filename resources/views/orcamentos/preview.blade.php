<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento #{{ $orcamento->numero }}</title>
    <style>
        /* ========================================
           FASE 4.1: LAYOUT FORMAL PRETO E BRANCO
           ======================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            color: #000000;
            background: #FFFFFF;
            line-height: 1.6;
        }

        .page {
            width: 100%;
            padding: 20px;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        /* Header repetido em todas as páginas */
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
            background: #FFFFFF;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000000;
        }

        .header p {
            font-size: 9pt;
            margin: 2px 0;
            color: #000000;
        }

        /* Capa */
        .capa {
            text-align: center;
            padding-top: 100px;
        }

        .capa h1 {
            font-size: 32pt;
            font-weight: bold;
            padding: 30px;
            border: 3px solid #000000;
            margin: 50px auto;
            max-width: 600px;
            background: #FFFFFF;
            color: #000000;
        }

        .capa .dados-box {
            background: #FFFFFF;
            border: 2px solid #000000;
            padding: 20px;
            margin: 30px auto;
            max-width: 700px;
            text-align: left;
        }

        .capa .dados-box h2 {
            font-size: 14pt;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            color: #000000;
        }

        .capa .dados-box table {
            width: 100%;
            font-size: 10pt;
        }

        .capa .dados-box td {
            padding: 5px;
            vertical-align: top;
            border: none;
        }

        .capa .dados-box td:first-child {
            font-weight: bold;
            width: 200px;
        }

        /* Seções - FUNDO PRETO, TEXTO BRANCO */
        .secao {
            background: #000000;
            color: #FFFFFF;
            padding: 8px 12px;
            margin: 20px 0 15px 0;
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
        }

        /* Tabelas - BORDAS PRETAS */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
            border: 1px solid #000000;
        }

        table th {
            background: #000000;
            color: #FFFFFF;
            padding: 6px;
            border: 1px solid #000000;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
        }

        table td {
            padding: 5px;
            border: 1px solid #000000;
            background: #FFFFFF;
            color: #000000;
        }

        table tbody tr:nth-child(even) {
            background: #FFFFFF;
        }

        /* Badges de situação - SEM COR, APENAS BORDA */
        .badge-validada {
            background: #FFFFFF;
            color: #000000;
            padding: 3px 8px;
            border: 1px solid #000000;
            font-weight: bold;
            font-size: 8pt;
            display: inline-block;
        }

        .badge-expurgada {
            background: #000000;
            color: #FFFFFF;
            padding: 3px 8px;
            border: 1px solid #000000;
            font-weight: bold;
            font-size: 8pt;
            display: inline-block;
        }

        /* Box de destaque - APENAS BORDA */
        .box-destaque {
            background: #FFFFFF;
            border: 2px solid #000000;
            padding: 10px;
            margin: 10px 0;
        }

        /* Rodapé */
        .rodape {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #000000;
            padding: 10px;
            border-top: 1px solid #000000;
            background: #FFFFFF;
        }

        /* Assinatura */
        .assinatura {
            margin-top: 50px;
            text-align: center;
        }

        .assinatura-linha {
            border-top: 2px solid #000000;
            width: 400px;
            margin: 0 auto;
            padding-top: 5px;
        }

        /* Formatação de valores */
        .valor {
            text-align: right;
            font-weight: bold;
        }

        /* Justificativa - FUNDO BRANCO, BORDA ESQUERDA PRETA */
        .justificativa-box {
            background: #FFFFFF;
            border-left: 4px solid #000000;
            border: 1px solid #000000;
            padding: 10px;
            margin: 10px 0;
            font-size: 9pt;
        }

        /* Quebra de página */
        .quebra-pagina {
            page-break-before: always;
        }

        /* Texto em negrito */
        strong, b {
            font-weight: bold;
            color: #000000;
        }

        /* Parágrafos */
        p {
            margin: 5px 0;
            text-align: justify;
            color: #000000;
        }

        /* Listas */
        ul, ol {
            margin: 10px 0;
            padding-left: 20px;
        }

        li {
            margin: 3px 0;
        }
    </style>
</head>
<body>

@php
    // Calcular valor global
    $valorGlobal = 0;
    foreach($orcamento->itens as $item) {
        $valorGlobal += ($item->preco_unitario ?? 0) * ($item->quantidade ?? 0);
    }
@endphp

<!-- ============================================ -->
<!-- PÁGINA 1: CAPA -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO NÃO INFORMADO') }}<br>
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="page-title">ORÇAMENTO ESTIMATIVO</div>

<div class="box">
    <div class="secao">DADOS DO ORÇAMENTO</div>

    <div class="data-row">
        <span class="data-label">OBJETO:</span>
        <span class="data-value">{{ $orcamento->objeto }}</span>
    </div>

    <div class="data-row">
        <span class="data-label">UNID. INTERESSADA:</span>
        <span class="data-value">{{ $orcamento->orgao_interessado ?? 'NÃO INFORMADO' }}</span>
    </div>

    <div class="data-row">
        <span class="data-label">REFERÊNCIA EXTERNA:</span>
        <span class="data-value">{{ $orcamento->referencia_externa ?? 'NÃO INFORMADO' }}</span>
    </div>

    <div class="data-row">
        <span class="data-label">PARAMETRO(S):</span>
        <span class="data-value">{{ strtoupper($orcamento->metodo_obtencao_preco ?? 'SÍTIO DE COMÉRCIO ELETRÔNICO') }}</span>
    </div>

    <div class="data-row">
        <span class="data-label">CONCLUSÃO:</span>
        <span class="data-value">{{ $orcamento->data_conclusao ? $orcamento->data_conclusao->format('d/m/Y') : 'EM ANDAMENTO' }}</span>
    </div>
</div>

<div style="text-align: center; margin-top: 60px; font-size: 9pt; color: #666;">
    <p>Orçamento gerado em {{ now()->format('d/m/Y H:i') }}</p>
    <p>Responsável: {{ strtoupper($orcamento->orcamentista_nome ?? $orcamento->user->name ?? 'NÃO INFORMADO') }}</p>
</div>

<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 2: SEÇÃO 1 E 2 -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">1 - DADOS DO ORÇAMENTO</div>

<div class="data-row">
    <span class="data-label">NOME:</span>
    <span class="data-value">{{ $orcamento->nome }}</span>
</div>

<div class="data-row">
    <span class="data-label">NÚMERO:</span>
    <span class="data-value">{{ $orcamento->numero }}</span>
</div>

<div class="data-row">
    <span class="data-label">OBJETO:</span>
    <span class="data-value">{{ $orcamento->objeto }}</span>
</div>

<div class="data-row">
    <span class="data-label">UNID. INTERESSADA:</span>
    <span class="data-value">{{ $orcamento->orgao_interessado ?? 'NÃO INFORMADO' }}</span>
</div>

<div class="data-row">
    <span class="data-label">ORÇAMENTISTA:</span>
    <span class="data-value">{{ strtoupper($orcamento->orcamentista_nome ?? $orcamento->user->name ?? 'NÃO INFORMADO') }}</span>
</div>

<div class="data-row">
    <span class="data-label">REFERÊNCIA EXTERNA:</span>
    <span class="data-value">{{ $orcamento->referencia_externa ?? 'NÃO INFORMADO' }}</span>
</div>

<div class="data-row">
    <span class="data-label">PARAMETRO(S):</span>
    <span class="data-value">{{ strtoupper($orcamento->metodo_obtencao_preco ?? 'SÍTIO DE COMÉRCIO ELETRÔNICO') }}</span>
</div>

<div class="data-row">
    <span class="data-label">CONCLUSÃO:</span>
    <span class="data-value">{{ $orcamento->data_conclusao ? $orcamento->data_conclusao->format('d/m/Y') : 'EM ANDAMENTO' }}</span>
</div>

<div class="secao" style="margin-top: 20px;">2 - PREÇOS ESTIMADOS</div>

<table>
    <thead>
        <tr>
            <th style="width: 8%;">LOTE/ITEM</th>
            <th style="width: 40%;">DESCRIÇÃO</th>
            <th style="width: 10%;">UND. FORNEC.</th>
            <th style="width: 10%; text-align: right;">QNT</th>
            <th style="width: 16%; text-align: right;">PREÇO UNIT. (R$)</th>
            <th style="width: 16%; text-align: right;">PREÇO TOTAL (R$)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orcamento->itens as $item)
            @php
                $precoUnit = $item->preco_unitario ?? 0;
                $quantidade = $item->quantidade ?? 0;
                $precoTotal = $precoUnit * $quantidade;
            @endphp
            <tr>
                <td>{{ $item->lote_numero ?? '00' }}/{{ str_pad($item->item_numero ?? 1, 3, '0', STR_PAD_LEFT) }}</td>
                <td>{{ strtoupper($item->descricao) }}</td>
                <td>{{ strtoupper($item->unidade) }}</td>
                <td style="text-align: right;">{{ number_format($quantidade, 2, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($precoUnit, 2, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($precoTotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align: right;">VALOR GLOBAL</td>
            <td style="text-align: right;">{{ number_format($valorGlobal, 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

{{-- SEÇÃO DE FORNECEDORES (apenas para cotação externa) --}}
@if(($cotacaoExterna ?? false) && !empty($fornecedores ?? []))
    <div class="page-break"></div>

    <div class="header">
        @if($orcamento->brasao_path)
            <div class="header-logo">
                @php
                    // Construir caminho absoluto do arquivo
                    $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                    // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                    if (file_exists($brasaoFullPath)) {
                        $imageData = base64_encode(file_get_contents($brasaoFullPath));
                        $mimeType = mime_content_type($brasaoFullPath);
                        $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                    } else {
                        $brasaoSrc = '';
                    }
                @endphp
                @if($brasaoSrc)
                    <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
                @endif
            </div>
        @endif
        <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
        <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
        <div class="header-endereco">
            {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO NÃO INFORMADO') }}<br>
            CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
            {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
        </div>
        @if($isPreview ?? true)
            <div class="header-aviso">
                ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
            </div>
        @endif
    </div>

    <div class="secao">FORNECEDORES E PREÇOS INDIVIDUAIS</div>

    @foreach($orcamento->itens as $item)
        @if(!empty($item->precos_fornecedores ?? []))
            <div style="margin-bottom: 20px; page-break-inside: avoid;">
                <div style="background: #f3f4f6; padding: 8px 12px; font-weight: bold; font-size: 9pt; border-left: 4px solid #3b82f6; margin-bottom: 8px;">
                    Item {{ str_pad($item->item_numero ?? 1, 3, '0', STR_PAD_LEFT) }} - {{ substr(strtoupper($item->descricao), 0, 100) }}{{ strlen($item->descricao) > 100 ? '...' : '' }}
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 12px;">
                    <thead style="background: #e5e7eb;">
                        <tr>
                            <th style="padding: 6px 10px; text-align: left; border: 1px solid #d1d5db;">FORNECEDOR</th>
                            <th style="padding: 6px 10px; text-align: right; border: 1px solid #d1d5db; width: 120px;">PREÇO UNIT. (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->precos_fornecedores as $fornecedor => $preco)
                            <tr>
                                <td style="padding: 6px 10px; border: 1px solid #d1d5db;">{{ strtoupper($fornecedor) }}</td>
                                <td style="padding: 6px 10px; text-align: right; border: 1px solid #d1d5db; font-weight: 500;">{{ number_format($preco, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background: #dbeafe;">
                        <tr>
                            <td style="padding: 6px 10px; border: 1px solid #d1d5db; font-weight: bold;">MÉDIA ARITMÉTICA</td>
                            <td style="padding: 6px 10px; text-align: right; border: 1px solid #d1d5db; font-weight: bold; color: #1e40af;">{{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endforeach
@endif

{{-- SEÇÕES ABAIXO: Apenas para orçamento estimativo (NÃO para cotação externa) --}}
@if(!($cotacaoExterna ?? false))
<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 3: SEÇÃO 3 - SÉRIE DE PREÇOS COLETADOS -->
<!-- ============================================ -->
@foreach($orcamento->itens as $index => $item)
    @if($index > 0)
        <div class="page-break"></div>
    @endif

    <div class="header">
        @if($orcamento->brasao_path)
            <div class="header-logo">
                @php
                    // Construir caminho absoluto do arquivo
                    $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                    // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                    if (file_exists($brasaoFullPath)) {
                        $imageData = base64_encode(file_get_contents($brasaoFullPath));
                        $mimeType = mime_content_type($brasaoFullPath);
                        $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                    } else {
                        $brasaoSrc = '';
                    }
                @endphp
                @if($brasaoSrc)
                    <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
                @endif
            </div>
        @endif
        <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
        <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
        <div class="header-endereco">
            {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
            CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
            {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
        </div>
        @if($isPreview ?? true)
            <div class="header-aviso">
                ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
            </div>
        @endif
    </div>

    <div class="secao">3 - SÉRIE DE PREÇOS COLETADOS - ITEM {{ str_pad($item->item_numero ?? ($index + 1), 3, '0', STR_PAD_LEFT) }}</div>

    <div class="section-content">
        <div class="data-row">
            <span class="data-label">DESCRIÇÃO:</span>
            <span class="data-value">{{ strtoupper($item->descricao) }}</span>
        </div>

        <div class="data-row">
            <span class="data-label">UNIDADE:</span>
            <span class="data-value">{{ strtoupper($item->unidade) }}</span>
        </div>

        <div class="data-row">
            <span class="data-label">QUANTIDADE:</span>
            <span class="data-value">{{ number_format($item->quantidade ?? 0, 2, ',', '.') }}</span>
        </div>
    </div>

    @php
        // Coletar preços do item de diferentes fontes
        $precosColetados = [];

        // 1. Preços de e-commerce
        foreach($orcamento->coletasEcommerce as $coleta) {
            foreach($coleta->itens as $itemEcommerce) {
                if($itemEcommerce->orcamento_item_id == $item->id) {
                    $precosColetados[] = [
                        'fonte' => 'E-COMMERCE',
                        'fornecedor' => $coleta->fornecedor_nome ?? 'NÃO INFORMADO',
                        'cnpj' => $coleta->fornecedor_cnpj ?? '',
                        'marca' => $itemEcommerce->marca ?? '',
                        'valor' => $itemEcommerce->preco_unitario ?? 0,
                        'data' => $coleta->data_coleta ? $coleta->data_coleta->format('d/m/Y') : '',
                        'url' => $coleta->url_site ?? ''  // CORRIGIDO: campo correto é url_site
                    ];
                }
            }
        }

        // 2. Preços de contratações similares
        foreach($orcamento->contratacoesSimilares as $contratacao) {
            foreach($contratacao->itens as $itemContratacao) {
                if($itemContratacao->orcamento_item_id == $item->id) {
                    $precosColetados[] = [
                        'fonte' => 'CONTRATAÇÃO SIMILAR',
                        'fornecedor' => $contratacao->fornecedor_nome ?? 'NÃO INFORMADO',
                        'cnpj' => $contratacao->fornecedor_cnpj ?? '',
                        'marca' => $itemContratacao->marca ?? '',
                        'valor' => $itemContratacao->preco_unitario ?? 0,
                        'data' => $contratacao->data_publicacao ? $contratacao->data_publicacao->format('d/m/Y') : '',
                        'url' => $contratacao->link_oficial ?? ''
                    ];
                }
            }
        }

        // 3. Preço do próprio orçamento atual (se existir)
        if ($item->preco_unitario > 0) {
            $precosColetados[] = [
                'fonte' => 'ORÇAMENTO ATUAL',
                'fornecedor' => $item->fornecedor_nome ?? 'NÃO INFORMADO',
                'cnpj' => $item->fornecedor_cnpj ?? '',
                'marca' => $item->marca ?? '-',
                'valor' => $item->preco_unitario,
                'data' => $orcamento->created_at ? $orcamento->created_at->format('d/m/Y') : date('d/m/Y'),
                'url' => ''
            ];
        }

        // ===================================================================
        // USAR MESMOS PARÂMETROS QUE O MODAL "ANÁLISE CRÍTICA"
        // ===================================================================
        // Buscar configurações salvas na guia "Metodologia e Padrões"
        $metodoJuizoCritico = $orcamento->metodo_juizo_critico ?? 'saneamento_desvio_padrao';
        $casasDecimais = $orcamento->casas_decimais ?? 'duas';
        $numCasasDecimais = ($casasDecimais === 'quatro') ? 4 : 2;

                // Calcular estatísticas
        $valores = array_column($precosColetados, 'valor');
        $valores = array_filter($valores, function($v) { return $v > 0; });
        sort($valores);

        $qtdAmostras = count($valores);
        $valorMin = $qtdAmostras > 0 ? min($valores) : 0;
        $valorMax = $qtdAmostras > 0 ? max($valores) : 0;
        $valorMedio = $qtdAmostras > 0 ? array_sum($valores) / $qtdAmostras : 0;

        // Mediana
        if ($qtdAmostras > 0) {
            if ($qtdAmostras % 2 == 0) {
                $valorMediano = ($valores[$qtdAmostras / 2 - 1] + $valores[$qtdAmostras / 2]) / 2;
            } else {
                $valorMediano = $valores[floor($qtdAmostras / 2)];
            }
        } else {
            $valorMediano = 0;
        }

        // Desvio padrão
        if ($qtdAmostras > 1) {
            $variancia = 0;
            foreach($valores as $v) {
                $variancia += pow($v - $valorMedio, 2);
            }
            $desvioPadrao = sqrt($variancia / ($qtdAmostras - 1));
            $coefVariacao = $valorMedio > 0 ? ($desvioPadrao / $valorMedio) * 100 : 0;
        } else {
            $desvioPadrao = 0;
            $coefVariacao = 0;
        }
    @endphp

    <div style="margin-top: 15px;">
        <strong style="font-size: 9pt;">JUÍZO CRÍTICO:</strong>

        <table style="margin-top: 8px;">
            <thead>
                <tr>
                    <th>AMOSTRAS</th>
                    <th style="text-align: right;">MENOR PREÇO</th>
                    <th style="text-align: right;">MAIOR PREÇO</th>
                    <th style="text-align: right;">PREÇO MÉDIO</th>
                    <th style="text-align: right;">PREÇO MEDIANO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">{{ $qtdAmostras }}</td>
                    <td style="text-align: right;">R$ {{ number_format($valorMin, $numCasasDecimais, ',', '.') }}</td>
                    <td style="text-align: right;">R$ {{ number_format($valorMax, $numCasasDecimais, ',', '.') }}</td>
                    <td style="text-align: right;">R$ {{ number_format($valorMedio, $numCasasDecimais, ',', '.') }}</td>
                    <td style="text-align: right;">R$ {{ number_format($valorMediano, $numCasasDecimais, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 15px;">
        <strong style="font-size: 9pt;">MÉTODO ESTATÍSTICO:</strong>

        <table style="margin-top: 8px;">
            <thead>
                <tr>
                    <th style="text-align: right;">DESVIO PADRÃO</th>
                    <th style="text-align: right;">COEF. DE VARIAÇÃO</th>
                    <th>ANÁLISE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: right;">R$ {{ number_format($desvioPadrao, $numCasasDecimais, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($coefVariacao, $numCasasDecimais, ',', '.') }}%</td>
                    <td>
                        @if($coefVariacao < 20)
                            BAIXA DISPERSÃO - Preços homogêneos
                        @elseif($coefVariacao < 40)
                            MÉDIA DISPERSÃO - Preços moderadamente variáveis
                        @else
                            ALTA DISPERSÃO - Preços heterogêneos
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if(count($precosColetados) > 0)
        <div style="margin-top: 15px;">
            <strong style="font-size: 9pt;">SÉRIE DE PREÇOS:</strong>

            <table style="margin-top: 8px;">
                <thead>
                    <tr>
                        <th style="width: 3%;">Nº</th>
                        <th style="width: 8%;">SITUAÇÃO</th>
                        <th style="width: 10%;">TIPO DE FONTE</th>
                        <th style="width: 8%;">ORIGEM</th>
                        <th style="width: 13%;">FORNECEDOR</th>
                        <th style="width: 8%;">CNPJ</th>
                        <th style="width: 6%;">DATA</th>
                        <th style="width: 6%;">MARCA</th>
                        <th style="width: 8%; text-align: right;">VALOR</th>
                        <th style="width: 18%;">LINK</th>
                        <th style="width: 12%; text-align: center;">QR CODE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($precosColetados as $idx => $preco)
                        @php
                            // Calcular SITUAÇÃO (VALIDADA ou EXPURGADA)
                            $precoValor = $preco['valor'];
                            $limiteInferior = $valorMedio - $desvioPadrao;
                            $limiteSuperior = $valorMedio + $desvioPadrao;

                            $situacao = 'VALIDADA';
                            $situacaoCor = '#166534'; // Verde escuro
                            $situacaoBg = '#dcfce7'; // Verde claro
                            $justificativaExpurgo = '';

                            if ($precoValor < $limiteInferior) {
                                $situacao = 'EXPURGADA';
                                $situacaoCor = '#991b1b'; // Vermelho escuro
                                $situacaoBg = '#fee2e2'; // Vermelho claro
                                $justificativaExpurgo = 'Valor abaixo do limite inferior (R$ ' . number_format($limiteInferior, 2, ',', '.') . ')';
                            } elseif ($precoValor > $limiteSuperior) {
                                $situacao = 'EXPURGADA';
                                $situacaoCor = '#991b1b';
                                $situacaoBg = '#fee2e2';
                                $justificativaExpurgo = 'Valor acima do limite superior (R$ ' . number_format($limiteSuperior, 2, ',', '.') . ')';
                            }

                            // Determinar TIPO DE FONTE
                            $tipoFonte = match($preco['fonte']) {
                                'CONTRATAÇÃO SIMILAR' => 'CONTRATAÇÃO PÚBLICA SIMILAR',
                                'E-COMMERCE' => 'SÍTIO DE COMÉRCIO ELETRÔNICO',
                                'CDF' => 'COTAÇÃO DIRETA COM FORNECEDOR',
                                default => 'PESQUISA DIRETA'
                            };

                            // Determinar ORIGEM
                            $origem = 'NÃO INFORMADO';
                            if ($preco['fonte'] == 'CONTRATAÇÃO SIMILAR') {
                                // Tentar identificar origem pela URL
                                if (strpos($preco['url'], 'licitacon') !== false) {
                                    $origem = 'LICITACON (TCE/RS)';
                                } elseif (strpos($preco['url'], 'portaltransparencia') !== false || strpos($preco['url'], 'cgu.gov.br') !== false) {
                                    $origem = 'PORTAL DA TRANSPARÊNCIA (CGU)';
                                } elseif (strpos($preco['url'], 'pncp.gov.br') !== false) {
                                    $origem = 'PNCP';
                                } else {
                                    $origem = 'CONTRATAÇÃO SIMILAR';
                                }
                            } elseif ($preco['fonte'] == 'E-COMMERCE') {
                                // Extrair domínio
                                $urlParts = parse_url($preco['url']);
                                $domain = $urlParts['host'] ?? 'E-COMMERCE';
                                $domain = str_replace('www.', '', $domain);
                                $origem = strtoupper($domain);
                            } elseif ($preco['fonte'] == 'CDF') {
                                $origem = 'COTAÇÃO DIRETA';
                            }
                        @endphp
                        <tr>
                            <td style="text-align: center; font-size: 8pt;">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>

                            {{-- COLUNA SITUAÇÃO (COLORIDA) --}}
                            <td style="text-align: center; font-size: 6.5pt; font-weight: bold; padding: 3px;">
                                <span style="background: {{ $situacaoBg }}; color: {{ $situacaoCor }}; padding: 2px 6px; border-radius: 3px; border: 1px solid {{ $situacaoCor }};">
                                    {{ $situacao }}
                                </span>
                            </td>

                            {{-- COLUNA TIPO DE FONTE --}}
                            <td style="font-size: 6.5pt; text-align: center;">{{ $tipoFonte }}</td>

                            {{-- COLUNA ORIGEM --}}
                            <td style="font-size: 6.5pt; text-align: center; font-weight: 600;">{{ $origem }}</td>

                            <td style="font-size: 7pt;">{{ substr($preco['fornecedor'], 0, 18) }}</td>
                            <td style="font-size: 6.5pt;">{{ substr($preco['cnpj'], 0, 14) }}</td>
                            <td style="font-size: 7pt;">{{ $preco['data'] }}</td>
                            <td style="font-size: 7pt;">{{ substr($preco['marca'], 0, 10) }}</td>
                            <td style="text-align: right; font-size: 8pt; font-weight: 600;">R$ {{ number_format($preco['valor'], 2, ',', '.') }}</td>

                            {{-- COLUNA LINK --}}
                            <td style="font-size: 6.5pt; word-break: break-word; padding: 4px 6px; line-height: 1.3;">
                                @if(!empty($preco['url']))
                                    @php
                                        // Limitar URL exibida
                                        $urlExibir = strlen($preco['url']) > 45 ? substr($preco['url'], 0, 45) . '...' : $preco['url'];
                                    @endphp
                                    <a href="{{ $preco['url'] }}" style="color: #2563eb; text-decoration: underline; font-size: 6pt; display: block;">
                                        {{ $urlExibir }}
                                    </a>
                                @else
                                    <span style="color: #9ca3af; font-size: 6pt;">Sem link</span>
                                @endif
                            </td>

                            {{-- COLUNA QR CODE --}}
                            <td style="text-align: center; padding: 4px; vertical-align: middle;">
                                @if(!empty($preco['url']))
                                    @php
                                        try {
                                            $qrCode = new \Mpdf\QrCode\QrCode($preco['url']);
                                            $output = new \Mpdf\QrCode\Output\Png();
                                            $qrImage = $output->output($qrCode, 400, [255, 255, 255], [0, 0, 0]);
                                            $qrBase64 = 'data:image/png;base64,' . base64_encode($qrImage);
                                        } catch (\Exception $e) {
                                            $qrBase64 = null;
                                        }
                                    @endphp

                                    @if(isset($qrBase64) && $qrBase64)
                                        <div style="display: inline-block; padding: 3px; background: white; border: 1px solid #e5e7eb; border-radius: 3px;">
                                            <img src="{{ $qrBase64 }}" style="width: 55px; height: 55px; display: block;" alt="QR Code">
                                        </div>
                                    @else
                                        <span style="color: #ef4444; font-size: 6pt;">Erro</span>
                                    @endif
                                @else
                                    <span style="color: #9ca3af; font-size: 7pt;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SEÇÃO DE JUSTIFICATIVA DE EXPURGOS --}}
        @php
            // Coletar todas as amostras expurgadas
            $amostrasExpurgadas = [];
            foreach($precosColetados as $idx => $preco) {
                $precoValor = $preco['valor'];
                $limiteInferior = $valorMedio - $desvioPadrao;
                $limiteSuperior = $valorMedio + $desvioPadrao;

                if ($precoValor < $limiteInferior || $precoValor > $limiteSuperior) {
                    $motivo = $precoValor < $limiteInferior
                        ? 'Valor abaixo do limite inferior (R$ ' . number_format($limiteInferior, 2, ',', '.') . ')'
                        : 'Valor acima do limite superior (R$ ' . number_format($limiteSuperior, 2, ',', '.') . ')';

                    $amostrasExpurgadas[] = [
                        'numero' => $idx + 1,
                        'valor' => $precoValor,
                        'motivo' => $motivo,
                        'fornecedor' => $preco['fornecedor'],
                        'fonte' => $preco['fonte']
                    ];
                }
            }
        @endphp

        @if(count($amostrasExpurgadas) > 0)
            <div style="margin-top: 20px; border: 2px solid #991b1b; border-radius: 6px; padding: 12px; background: #fef2f2;">
                <div style="font-size: 10pt; font-weight: bold; color: #991b1b; margin-bottom: 10px; text-align: center; text-transform: uppercase;">
                    📋 JUSTIFICATIVA DE EXPURGOS
                </div>

                <div style="font-size: 8.5pt; color: #1a1a1a; line-height: 1.6; text-align: justify; margin-bottom: 12px;">
                    <strong>METODOLOGIA APLICADA:</strong> Foram expurgadas as amostras que apresentaram valores fora do intervalo de confiança
                    estatístico, calculado como <strong>MÉDIA ± DESVIO PADRÃO</strong>, conforme recomendações do Manual de Orientação de Pesquisa
                    de Preços do STJ (Edição 2021) e princípios da Lei 14.133/2021.
                </div>

                <div style="font-size: 8.5pt; color: #1a1a1a; line-height: 1.6; margin-bottom: 12px;">
                    <strong>INTERVALO DE CONFIANÇA PARA ESTE ITEM:</strong>
                    <ul style="margin: 8px 0 0 20px; padding: 0;">
                        <li><strong>Limite Inferior:</strong> R$ {{ number_format($valorMedio - $desvioPadrao, $numCasasDecimais, ',', '.') }}</li>
                        <li><strong>Limite Superior:</strong> R$ {{ number_format($valorMedio + $desvioPadrao, $numCasasDecimais, ',', '.') }}</li>
                        <li><strong>Média Aritmética:</strong> R$ {{ number_format($valorMedio, $numCasasDecimais, ',', '.') }}</li>
                        <li><strong>Desvio Padrão:</strong> R$ {{ number_format($desvioPadrao, $numCasasDecimais, ',', '.') }}</li>
                    </ul>
                </div>

                <div style="font-size: 8.5pt; color: #1a1a1a; line-height: 1.6; margin-bottom: 10px;">
                    <strong>AMOSTRAS EXPURGADAS ({{ count($amostrasExpurgadas) }}):</strong>
                </div>

                <table style="margin-top: 8px; border: 1px solid #991b1b;">
                    <thead style="background: #991b1b; color: white;">
                        <tr>
                            <th style="width: 8%; text-align: center;">AMOSTRA Nº</th>
                            <th style="width: 25%;">FORNECEDOR</th>
                            <th style="width: 15%;">FONTE</th>
                            <th style="width: 12%; text-align: right;">VALOR</th>
                            <th style="width: 40%;">JUSTIFICATIVA DO EXPURGO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($amostrasExpurgadas as $expurgada)
                            <tr style="background: #fee2e2;">
                                <td style="text-align: center; font-size: 8pt; font-weight: bold;">{{ str_pad($expurgada['numero'], 2, '0', STR_PAD_LEFT) }}</td>
                                <td style="font-size: 7.5pt;">{{ substr($expurgada['fornecedor'], 0, 30) }}</td>
                                <td style="font-size: 7.5pt;">{{ $expurgada['fonte'] }}</td>
                                <td style="text-align: right; font-size: 8pt; font-weight: bold; color: #991b1b;">
                                    R$ {{ number_format($expurgada['valor'], 2, ',', '.') }}
                                </td>
                                <td style="font-size: 7.5pt; line-height: 1.4;">{{ $expurgada['motivo'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="font-size: 8pt; color: #7f1d1d; margin-top: 10px; text-align: justify; line-height: 1.5; font-style: italic;">
                    <strong>OBSERVAÇÃO:</strong> As amostras expurgadas foram desconsideradas do cálculo do preço estimado final,
                    restando apenas as amostras dentro do intervalo de confiança estatístico para composição do valor de referência.
                </div>
            </div>
        @else
            <div style="margin-top: 15px; padding: 12px; background: #dcfce7; border: 1px solid #166534; border-radius: 4px;">
                <strong style="color: #166534; font-size: 8.5pt;">✅ NENHUMA AMOSTRA EXPURGADA</strong> - Todas as amostras coletadas
                estão dentro do intervalo de confiança estatístico e foram utilizadas no cálculo do preço estimado.
            </div>
        @endif
    @endif
@endforeach

<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 4: SEÇÃO 4 - VALIDAÇÃO DAS COTAÇÕES DIRETAS -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">4 - VALIDAÇÃO DAS COTAÇÕES DIRETAS</div>

@php
    $solicitacoesCDF = $orcamento->solicitacoesCDF ?? collect();
@endphp

@if($solicitacoesCDF->count() > 0)
    @foreach($solicitacoesCDF as $cdf)
        <div class="section-content" style="margin-bottom: 20px; border: 2px solid #3b82f6; border-radius: 8px; padding: 15px; background: #f8fafc;">
            {{-- DADOS DA SOLICITAÇÃO CDF --}}
            <div class="data-row">
                <span class="data-label">FORNECEDOR:</span>
                <span class="data-value">{{ strtoupper($cdf->razao_social ?? 'NÃO INFORMADO') }}</span>
            </div>

            <div class="data-row">
                <span class="data-label">CNPJ:</span>
                <span class="data-value">{{ $cdf->cnpj ?? 'NÃO INFORMADO' }}</span>
            </div>

            <div class="data-row">
                <span class="data-label">DATA SOLICITAÇÃO:</span>
                <span class="data-value">{{ $cdf->created_at ? $cdf->created_at->format('d/m/Y') : 'NÃO INFORMADO' }}</span>
            </div>

            <div class="data-row">
                <span class="data-label">STATUS:</span>
                <span class="data-value">{{ strtoupper($cdf->status ?? 'PENDENTE') }}</span>
            </div>

            {{-- TABELA DE ITENS SOLICITADOS NA CDF --}}
            @php
                // Buscar itens relacionados via cp_solicitacao_cdf_itens
                $itensSolicitados = DB::table('solicitacao_cdf_itens')
                    ->where('solicitacao_cdf_id', $cdf->id)
                    ->get();
            @endphp

            @if($itensSolicitados->count() > 0)
                <div style="margin-top: 15px;">
                    <h4 style="font-size: 11px; font-weight: bold; margin-bottom: 8px; color: #1f2937;">ITENS SOLICITADOS PARA COTAÇÃO:</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                        <thead>
                            <tr style="background: #dbeafe;">
                                <th style="border: 1px solid #3b82f6; padding: 6px; text-align: left; font-weight: bold;">ITEM</th>
                                <th style="border: 1px solid #3b82f6; padding: 6px; text-align: left; font-weight: bold;">DESCRIÇÃO</th>
                                <th style="border: 1px solid #3b82f6; padding: 6px; text-align: center; font-weight: bold; width: 70px;">QTDE SOLICITADA</th>
                                <th style="border: 1px solid #3b82f6; padding: 6px; text-align: center; font-weight: bold; width: 50px;">UND</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itensSolicitados as $index => $itemSolicitado)
                                @php
                                    // Buscar dados do item do orçamento para descrição
                                    $orcamentoItem = \App\Models\OrcamentoItem::find($itemSolicitado->orcamento_item_id);
                                @endphp
                                <tr style="background: {{ $index % 2 == 0 ? '#ffffff' : '#f0f9ff' }};">
                                    <td style="border: 1px solid #3b82f6; padding: 6px; text-align: center;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td style="border: 1px solid #3b82f6; padding: 6px;">
                                        {{ strtoupper($orcamentoItem->descricao ?? 'NÃO INFORMADO') }}
                                    </td>
                                    <td style="border: 1px solid #3b82f6; padding: 6px; text-align: center;">
                                        {{ number_format($orcamentoItem->quantidade ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td style="border: 1px solid #3b82f6; padding: 6px; text-align: center;">
                                        {{ strtoupper($orcamentoItem->medida_fornecimento ?? 'UN') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- VERIFICAR SE HÁ RESPOSTA COM PREÇOS DO FORNECEDOR --}}
                @php
                    $resposta = DB::table('respostas_cdf')
                        ->where('solicitacao_cdf_id', $cdf->id)
                        ->first();

                    $itensRespondidos = collect();
                    if ($resposta) {
                        $itensRespondidos = DB::table('resposta_cdf_itens')
                            ->where('resposta_cdf_id', $resposta->id)
                            ->get();
                    }
                @endphp

                @if($itensRespondidos->count() > 0)
                    <div style="margin-top: 20px;">
                        <h4 style="font-size: 11px; font-weight: bold; margin-bottom: 8px; color: #059669;">✅ PREÇOS COTADOS PELO FORNECEDOR:</h4>
                        <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                            <thead>
                                <tr style="background: #d1fae5;">
                                    <th style="border: 1px solid #10b981; padding: 6px; text-align: left; font-weight: bold;">ITEM</th>
                                    <th style="border: 1px solid #10b981; padding: 6px; text-align: left; font-weight: bold;">DESCRIÇÃO</th>
                                    <th style="border: 1px solid #10b981; padding: 6px; text-align: center; font-weight: bold; width: 60px;">QTDE</th>
                                    <th style="border: 1px solid #10b981; padding: 6px; text-align: right; font-weight: bold; width: 90px;">PREÇO UNIT.</th>
                                    <th style="border: 1px solid #10b981; padding: 6px; text-align: right; font-weight: bold; width: 100px;">PREÇO TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalGeralCotado = 0;
                                @endphp
                                @foreach($itensRespondidos as $idx => $itemResp)
                                    @php
                                        $orcItem = \App\Models\OrcamentoItem::find($itemResp->item_orcamento_id);
                                        $precoUnit = $itemResp->preco_unitario ?? 0;
                                        $precoTotal = $itemResp->preco_total ?? ($orcItem->quantidade * $precoUnit);
                                        $totalGeralCotado += $precoTotal;
                                    @endphp
                                    <tr style="background: {{ $idx % 2 == 0 ? '#ffffff' : '#f0fdf4' }};">
                                        <td style="border: 1px solid #10b981; padding: 6px; text-align: center;">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td style="border: 1px solid #10b981; padding: 6px;">
                                            {{ strtoupper($orcItem->descricao ?? 'NÃO INFORMADO') }}
                                            @if($itemResp->marca)
                                                <br><small style="color: #6b7280;">Marca: {{ strtoupper($itemResp->marca) }}</small>
                                            @endif
                                        </td>
                                        <td style="border: 1px solid #10b981; padding: 6px; text-align: center;">
                                            {{ number_format($orcItem->quantidade ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td style="border: 1px solid #10b981; padding: 6px; text-align: right;">
                                            R$ {{ number_format($precoUnit, 2, ',', '.') }}
                                        </td>
                                        <td style="border: 1px solid #10b981; padding: 6px; text-align: right; font-weight: bold;">
                                            R$ {{ number_format($precoTotal, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr style="background: #059669; color: white; font-weight: bold;">
                                    <td colspan="4" style="border: 1px solid #10b981; padding: 6px; text-align: right;">TOTAL COTADO:</td>
                                    <td style="border: 1px solid #10b981; padding: 6px; text-align: right;">
                                        R$ {{ number_format($totalGeralCotado, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <div style="margin-top: 10px; padding: 8px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; font-size: 9px;">
                    ⚠️ <strong>Nenhum item selecionado para esta CDF</strong>
                </div>
            @endif

            @if($cdf->status == 'Respondido')
                <div style="margin-top: 10px; padding: 8px; background: #d1fae5; border: 1px solid #10b981; border-radius: 4px; font-size: 9px;">
                    ✅ <strong>VALIDADO</strong> - Proposta recebida e validada
                </div>
            @else
                <div style="margin-top: 10px; padding: 8px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 4px; font-size: 9px;">
                    ⏳ <strong>AGUARDANDO RESPOSTA DO FORNECEDOR</strong>
                </div>
            @endif
        </div>
    @endforeach
@else
    <div style="padding: 30px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; text-align: center;">
        <strong>ℹ️ NENHUMA SOLICITAÇÃO DE COTAÇÃO DIRETA REGISTRADA</strong>
    </div>
@endif

<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 5: SEÇÃO 5 - CURVA ABC -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">5 - CURVA ABC (ANÁLISE DE PARETO)</div>

@php
    // Calcular curva ABC
    $itensComValor = [];
    foreach($orcamento->itens as $item) {
        $valorTotal = ($item->preco_unitario ?? 0) * ($item->quantidade ?? 0);
        $itensComValor[] = [
            'item' => $item,
            'valor_total' => $valorTotal
        ];
    }

    // Ordenar por valor decrescente
    usort($itensComValor, function($a, $b) {
        return $b['valor_total'] <=> $a['valor_total'];
    });

    // Calcular acumulados
    $valorTotalGeral = array_sum(array_column($itensComValor, 'valor_total'));
    $acumulado = 0;
    foreach($itensComValor as &$itemData) {
        $acumulado += $itemData['valor_total'];
        $itemData['acumulado'] = $acumulado;
        $itemData['percentual'] = $valorTotalGeral > 0 ? ($itemData['valor_total'] / $valorTotalGeral) * 100 : 0;
        $itemData['percentual_acumulado'] = $valorTotalGeral > 0 ? ($acumulado / $valorTotalGeral) * 100 : 0;

        // Classificar
        if ($itemData['percentual_acumulado'] <= 80) {
            $itemData['classe'] = 'A';
        } elseif ($itemData['percentual_acumulado'] <= 95) {
            $itemData['classe'] = 'B';
        } else {
            $itemData['classe'] = 'C';
        }
    }
@endphp

<table>
    <thead>
        <tr>
            <th style="width: 8%;">ITEM</th>
            <th style="width: 35%;">DESCRIÇÃO</th>
            <th style="width: 12%; text-align: right;">VALOR TOTAL</th>
            <th style="width: 12%; text-align: right;">% INDIVIDUAL</th>
            <th style="width: 15%; text-align: right;">VALOR ACUMULADO</th>
            <th style="width: 10%; text-align: right;">% ACUMULADO</th>
            <th style="width: 8%; text-align: center;">CLASSE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($itensComValor as $itemData)
            <tr style="background: {{ $itemData['classe'] == 'A' ? '#dcfce7' : ($itemData['classe'] == 'B' ? '#fef3c7' : '#fee2e2') }}">
                <td>{{ str_pad($itemData['item']->item_numero ?? 1, 3, '0', STR_PAD_LEFT) }}</td>
                <td>{{ substr(strtoupper($itemData['item']->descricao), 0, 50) }}</td>
                <td style="text-align: right;">R$ {{ number_format($itemData['valor_total'], 2, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($itemData['percentual'], 2, ',', '.') }}%</td>
                <td style="text-align: right;">R$ {{ number_format($itemData['acumulado'], 2, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($itemData['percentual_acumulado'], 2, ',', '.') }}%</td>
                <td style="text-align: center; font-weight: bold;">{{ $itemData['classe'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 20px; padding: 15px; background: #f3f4f6; border-radius: 4px;">
    <strong style="font-size: 9.5pt;">INTERPRETAÇÃO DA CURVA ABC:</strong>
    <ul style="margin-top: 8px; margin-left: 20px; font-size: 8.5pt; line-height: 1.6;">
        <li><strong>Classe A (verde):</strong> Itens que representam até 80% do valor total - requerem maior atenção na cotação</li>
        <li><strong>Classe B (amarelo):</strong> Itens que representam de 80% a 95% do valor total - importância moderada</li>
        <li><strong>Classe C (vermelho):</strong> Itens que representam acima de 95% do valor total - menor impacto no orçamento</li>
    </ul>
</div>

<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 6: SEÇÃO 6 - JUSTIFICATIVAS E OBSERVAÇÕES -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">6 - JUSTIFICATIVAS E OBSERVAÇÕES</div>

@if($orcamento->observacoes)
    <div class="section-content">
        <p style="text-align: justify; line-height: 1.6;">{{ $orcamento->observacoes }}</p>
    </div>
@else
    <div style="padding: 30px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;">
        <em>Nenhuma observação ou justificativa registrada para este orçamento.</em>
    </div>
@endif

<div style="margin-top: 30px;">
    <strong style="font-size: 9.5pt;">OBSERVAÇÕES GERAIS:</strong>
    <ul style="margin-top: 8px; margin-left: 20px; font-size: 8.5pt; line-height: 1.8;">
        <li>Os preços foram coletados de fontes confiáveis e verificadas</li>
        <li>As cotações estão sujeitas a variações de mercado</li>
        <li>Recomenda-se validação periódica dos valores estimados</li>
        <li>Este orçamento serve como base para planejamento e não constitui compromisso de compra</li>
    </ul>
</div>

<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 7: SEÇÃO 7 - JUSTIFICATIVA DA METODOLOGIA -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">7 - JUSTIFICATIVA DA METODOLOGIA UTILIZADA</div>

<div class="section-content" style="text-align: justify; line-height: 1.7; font-size: 8.5pt;">
    <p style="margin-bottom: 10px;">
        O presente orçamento estimativo foi elaborado em conformidade com a Lei nº 14.133/2021 (Nova Lei de Licitações e Contratos Administrativos)
        e suas regulamentações, especialmente quanto à necessidade de estimativa de preços para contratações públicas.
    </p>

    <p style="margin-bottom: 10px;">
        <strong>Metodologia Aplicada:</strong> {{ strtoupper($orcamento->metodo_obtencao_preco ?? 'PESQUISA DE PREÇOS EM SÍTIOS DE COMÉRCIO ELETRÔNICO') }}
    </p>

    <p style="margin-bottom: 10px;">
        A pesquisa de preços foi realizada através de múltiplas fontes confiáveis, incluindo:
    </p>

    <ul style="margin-left: 25px; margin-bottom: 10px;">
        <li>Sítios especializados de comércio eletrônico</li>
        <li>Consultas a contratações públicas similares (PNCP - Portal Nacional de Contratações Públicas)</li>
        <li>Cotações diretas com fornecedores qualificados</li>
        <li>Painel de Preços do Governo Federal</li>
    </ul>

    <p style="margin-bottom: 10px;">
        <strong>Tratamento Estatístico:</strong> Os valores coletados foram submetidos a análise estatística criteriosa,
        incluindo cálculo de média aritmética, mediana, desvio padrão e coeficiente de variação. Valores discrepantes
        (outliers) foram identificados e avaliados quanto à sua pertinência e justificativa de inclusão ou exclusão da série.
    </p>

    <p style="margin-bottom: 10px;">
        <strong>Fundamentação Legal:</strong>
    </p>

    <ul style="margin-left: 25px; margin-bottom: 10px; line-height: 1.8;">
        <li><strong>Lei nº 14.133/2021, Art. 23:</strong> "É vedada a contratação de pessoa física ou jurídica, se ocorrer uma das seguintes hipóteses:
        I - sobrepreço ou superfaturamento na execução do contrato"</li>
        <li><strong>IN SEGES/ME nº 65/2021:</strong> Orientações sobre pesquisa de preços para contratações públicas</li>
        <li><strong>Acórdão TCU nº 2622/2019:</strong> Orientações sobre metodologia de pesquisa de preços</li>
    </ul>

    <p style="margin-bottom: 10px;">
        <strong>Validade da Estimativa:</strong> Recomenda-se que este orçamento seja utilizado dentro de um prazo de
        {{ $orcamento->validade_dias ?? 90 }} dias a contar da data de conclusão, considerando as variações normais de mercado.
        Após este período, sugere-se atualização dos valores.
    </p>

    <p style="margin-bottom: 10px;">
        <strong>Responsabilidade Técnica:</strong> Este orçamento foi elaborado por
        {{ strtoupper($orcamento->orcamentista_nome ?? $orcamento->user->name ?? 'NÃO INFORMADO') }},
        responsável técnico pela pesquisa de preços, que atesta a veracidade das informações aqui apresentadas.
    </p>
</div>

<div class="assinatura">
    <div class="assinatura-linha"></div>
    <div class="assinatura-nome">{{ strtoupper($orcamento->orcamentista_nome ?? $orcamento->user->name ?? 'NÃO INFORMADO') }}</div>
    <div class="assinatura-cargo">Orçamentista Responsável</div>
    <div class="assinatura-cargo">{{ $orcamento->orcamentista_cargo ?? 'Servidor(a) Público(a)' }}</div>
</div>

<div class="page-break"></div>

<!-- ============================================ -->
<!-- PÁGINA 8: SEÇÃO 8 - ANEXOS -->
<!-- ============================================ -->
<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">8 - ANEXOS</div>

<div style="margin-bottom: 20px;">
    <strong style="font-size: 9.5pt;">8.1 - DOCUMENTAÇÃO CONSULTADA</strong>

    <table style="margin-top: 10px;">
        <thead>
            <tr>
                <th style="width: 5%;">Nº</th>
                <th style="width: 30%;">TIPO DE DOCUMENTO</th>
                <th style="width: 45%;">FONTE/FORNECEDOR</th>
                <th style="width: 20%;">DATA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $anexoNumero = 1;
            @endphp

            @foreach($orcamento->coletasEcommerce as $coleta)
                <tr>
                    <td style="text-align: center;">{{ $anexoNumero++ }}</td>
                    <td>Cotação E-commerce</td>
                    <td>{{ $coleta->fornecedor_nome ?? 'E-commerce' }}</td>
                    <td>{{ $coleta->data_coleta ? $coleta->data_coleta->format('d/m/Y') : '' }}</td>
                </tr>
            @endforeach

            @foreach($orcamento->contratacoesSimilares as $contratacao)
                <tr>
                    <td style="text-align: center;">{{ $anexoNumero++ }}</td>
                    <td>Contratação Similar</td>
                    <td>{{ $contratacao->orgao_nome ?? 'Órgão Público' }}</td>
                    <td>{{ $contratacao->data_contratacao ? $contratacao->data_contratacao->format('d/m/Y') : '' }}</td>
                </tr>
            @endforeach

            @foreach($orcamento->solicitacoesCDF as $cdf)
                <tr>
                    <td style="text-align: center;">{{ $anexoNumero++ }}</td>
                    <td>Cotação Direta (CDF)</td>
                    <td>{{ $cdf->fornecedor_nome ?? 'Fornecedor' }}</td>
                    <td>{{ $cdf->data_solicitacao ? $cdf->data_solicitacao->format('d/m/Y') : '' }}</td>
                </tr>
            @endforeach

            @if($anexoNumero == 1)
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;">
                        <em>Nenhum anexo disponível</em>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div style="margin-top: 30px;">
    <strong style="font-size: 9.5pt;">8.2 - PRINTS DE TELA E CAPTURAS</strong>

    <div style="margin-top: 10px; padding: 20px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;">
        <em>Os prints de tela das pesquisas realizadas estão disponíveis nos arquivos digitais anexos a este orçamento.</em>
    </div>
</div>

<div style="margin-top: 30px;">
    <strong style="font-size: 9.5pt;">8.3 - PROPOSTAS COMERCIAIS</strong>

    <div style="margin-top: 10px; padding: 20px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;">
        <em>As propostas comerciais recebidas estão arquivadas digitalmente e disponíveis para consulta quando necessário.</em>
    </div>
</div>

{{-- ============================================
     SEÇÃO 5: JUSTIFICATIVA DA METODOLOGIA + ASSINATURA
     ============================================ --}}
@if($orcamento->observacao_justificativa || $orcamento->orcamentista_cpf_cnpj || $orcamento->orcamentista_matricula || $orcamento->orcamentista_portaria)
<div class="page-break"></div>

<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">5 - JUSTIFICATIVA DA METODOLOGIA</div>

@if($orcamento->observacao_justificativa)
    <div class="section-content" style="text-align: justify; line-height: 1.8; font-size: 9pt;">
        {!! nl2br(e($orcamento->observacao_justificativa)) !!}
    </div>
@else
    <div style="padding: 30px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; text-align: center;">
        <strong>ℹ️ JUSTIFICATIVA NÃO INFORMADA</strong>
    </div>
@endif

{{-- ASSINATURA DO ORÇAMENTISTA --}}
<div class="assinatura">
    <div class="assinatura-linha"></div>
    <div class="assinatura-nome">{{ strtoupper($orcamento->orcamentista_nome ?? 'NÃO INFORMADO') }}</div>
    <div class="assinatura-cargo">Orçamentista Responsável</div>

    @if($orcamento->orcamentista_cpf_cnpj || $orcamento->orcamentista_matricula || $orcamento->orcamentista_portaria)
        <div style="margin-top: 12px; font-size: 8.5pt; color: #64748b; line-height: 1.6;">
            @if($orcamento->orcamentista_cpf_cnpj)
                <div><strong>CPF/CNPJ:</strong> {{ $orcamento->orcamentista_cpf_cnpj }}</div>
            @endif
            @if($orcamento->orcamentista_matricula)
                <div><strong>Matrícula:</strong> {{ $orcamento->orcamentista_matricula }}</div>
            @endif
            @if($orcamento->orcamentista_portaria)
                <div><strong>Portaria:</strong> {{ $orcamento->orcamentista_portaria }}</div>
            @endif
        </div>
    @endif
</div>
@endif

{{-- ============================================
     SEÇÃO 6: AMOSTRAS COLETADAS
     ============================================ --}}
@php
    // Buscar itens com amostras selecionadas
    // Para cotação externa, $orcamento é stdClass, não Model
    if ($cotacaoExterna ?? false) {
        // Cotação externa: filtrar itens manualmente
        $itensComAmostras = collect($orcamento->itens ?? [])->filter(function($item) {
            $amostras = is_string($item->amostras_selecionadas ?? null)
                ? json_decode($item->amostras_selecionadas, true)
                : ($item->amostras_selecionadas ?? []);
            return !empty($amostras);
        });
    } else {
        // Orçamento normal: usar relação Eloquent
        $itensComAmostras = $orcamento->itens()->whereNotNull('amostras_selecionadas')->get();
    }
@endphp

@if($itensComAmostras->count() > 0)
<div class="page-break"></div>

<div class="header">
    @if($orcamento->brasao_path)
        <div class="header-logo">
            @php
                // Construir caminho absoluto do arquivo
                $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);

                // Se o arquivo existir, converter para base64 (funciona tanto no navegador quanto no PDF)
                if (file_exists($brasaoFullPath)) {
                    $imageData = base64_encode(file_get_contents($brasaoFullPath));
                    $mimeType = mime_content_type($brasaoFullPath);
                    $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $brasaoSrc = '';
                }
            @endphp
            @if($brasaoSrc)
                <img src="{{ $brasaoSrc }}" style="max-width: 60px; max-height: 60px;" alt="Brasão">
            @endif
        </div>
    @endif
    <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? 'ÓRGÃO NÃO INFORMADO') }}</div>
    <div class="header-setor">{{ strtoupper($orcamento->orcamentista_setor ?? 'SETOR NÃO INFORMADO') }}</div>
    <div class="header-endereco">
        {{ strtoupper($orcamento->orcamentista_endereco ?? 'ENDEREÇO') }}
        CEP: {{ $orcamento->orcamentista_cep ?? '00.000-000' }} -
        {{ strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') }}/{{ strtoupper($orcamento->orcamentista_uf ?? 'UF') }}
    </div>
    @if($isPreview ?? true)
        <div class="header-aviso">
            ⚠️ ESTE É UM PREVIEW DO ORÇAMENTO - DOCUMENTO NÃO OFICIAL
        </div>
    @endif
</div>

<div class="secao">COLETA DE AMOSTRAS DA COTAÇÃO DOS ITENS</div>

@foreach($itensComAmostras as $item)
    @php
        $amostras = json_decode($item->amostras_selecionadas, true) ?? [];
    @endphp

    @if(count($amostras) > 0)
        <div class="section-content" style="margin-bottom: 20px;">
            <div style="font-weight: bold; font-size: 10pt; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; color: #1f2937;">
                Item: {{ $item->descricao }}
            </div>

            {{-- GRID DE 3 CARDS POR LINHA --}}
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin: 0 -5px;">
                @foreach($amostras as $index => $amostra)
                    <div style="flex: 0 0 calc(33.333% - 10px); min-width: 180px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; background: #f9fafb; page-break-inside: avoid; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        {{-- QR CODE + LINK --}}
                        @if(!empty($amostra['link_fonte']))
                        <div style="text-align: center; margin-bottom: 8px;">
                            {{-- QR CODE SVG (compatível com DOMPDF) --}}
                            @php
                                try {
                                    // Gerar SVG e remover declaração XML para evitar problemas no PDF
                                    $qrCodeSvg = QrCode::format('svg')->size(75)->margin(1)->errorCorrection('H')->generate($amostra['link_fonte']);
                                    // Remover declaração XML que causa problemas visuais
                                    $qrCodeSvg = preg_replace('/<\?xml.*?\?>\s*/s', '', $qrCodeSvg);
                                    // Remover DOCTYPE se existir
                                    $qrCodeSvg = preg_replace('/<!DOCTYPE.*?>\s*/s', '', $qrCodeSvg);
                                } catch (\Exception $e) {
                                    $qrCodeSvg = null;
                                }
                            @endphp

                            @if($qrCodeSvg)
                            <div style="display: inline-block; background: white; padding: 5px; border: 1px solid #e5e7eb; border-radius: 4px;">
                                {!! $qrCodeSvg !!}
                            </div>
                            @endif

                            <div style="font-size: 7.5pt; color: #374151; margin-top: 6px; font-weight: 600;">Amostra {{ $index + 1 }}</div>

                            {{-- LINK CLICÁVEL --}}
                            <div style="margin-top: 6px; padding: 5px; background: #eff6ff; border-radius: 3px;">
                                <div style="font-size: 5.5pt; color: #6b7280; margin-bottom: 2px; text-transform: uppercase;">Link da Fonte:</div>
                                <a href="{{ $amostra['link_fonte'] }}" target="_blank" style="word-break: break-all; color: #2563eb; font-size: 5.5pt; text-decoration: none; line-height: 1.3;">
                                    {{ $amostra['link_fonte'] }}
                                </a>
                            </div>
                        </div>
                        @endif

                        {{-- INFORMAÇÕES COMPACTAS --}}
                        <div style="font-size: 7.5pt; line-height: 1.4;">
                            <div style="margin-bottom: 3px;">
                                <strong style="color: #374151;">Fonte:</strong>
                                <span style="color: #1f2937;">{{ $amostra['fonte'] ?? 'N/A' }}</span>
                            </div>

                            <div style="margin-bottom: 3px;">
                                <strong style="color: #374151;">Órgão:</strong>
                                <span style="color: #1f2937; font-size: 7pt;">{{ Str::limit($amostra['orgao'] ?? 'N/A', 30) }}</span>
                            </div>

                            <div style="margin-bottom: 3px;">
                                <strong style="color: #374151;">Local:</strong>
                                <span style="color: #1f2937;">{{ $amostra['uf'] ?? 'N/A' }}</span>
                            </div>

                            <div style="margin-bottom: 3px; padding: 4px; background: #dcfce7; border-radius: 3px; text-align: center;">
                                <strong style="color: #059669; font-size: 8.5pt;">R$ {{ number_format($amostra['valor_unitario'] ?? 0, 2, ',', '.') }}</strong>
                            </div>

                            @if(!empty($amostra['unidade_medida']))
                            <div style="margin-bottom: 3px; font-size: 7pt;">
                                <strong style="color: #374151;">Unid:</strong>
                                <span style="color: #1f2937;">{{ $amostra['unidade_medida'] }}</span>
                            </div>
                            @endif

                            @if(!empty($amostra['data_publicacao']))
                            <div style="font-size: 6.5pt; color: #6b7280;">
                                {{ date('d/m/Y', strtotime($amostra['data_publicacao'])) }}
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
@endif

{{-- FIM DAS SEÇÕES APENAS PARA ORÇAMENTO ESTIMATIVO --}}
@endif

<div style="margin-top: 50px; padding: 15px; background: #dbeafe; border: 2px solid #3b82f6; border-radius: 6px;">
    <strong style="font-size: 9.5pt; color: #1e40af;">📋 NOTA IMPORTANTE:</strong>
    <p style="margin-top: 8px; font-size: 8.5pt; line-height: 1.6; color: #1e40af;">
        Este documento constitui uma estimativa de preços para fins de planejamento.
        Os valores apresentados não constituem garantia ou compromisso de fornecimento pelos preços indicados.
        Para contratação efetiva, deverá ser realizado processo licitatório nos termos da Lei nº 14.133/2021.
    </p>
</div>

<div style="margin-top: 40px; text-align: center; font-size: 8pt; color: #666;">
    <p>*** FIM DO DOCUMENTO ***</p>
    <p style="margin-top: 5px;">Orçamento #{{ $orcamento->numero }} | Gerado em {{ now()->format('d/m/Y H:i:s') }}</p>
</div>

</body>
</html>
