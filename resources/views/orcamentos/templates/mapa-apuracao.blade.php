<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Mapa de Apuração de Preços</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 landscape;
            margin: 10mm 8mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        /* Header */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .header-left {
            text-align: left;
        }

        .header-orgao {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .header-estado {
            font-size: 8pt;
            font-weight: bold;
        }

        .header-right {
            font-size: 6pt;
            text-align: right;
            line-height: 1.3;
        }

        /* Título */
        .titulo-principal {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 12px 0 10px 0;
        }

        /* Box de informações */
        .info-box {
            border: 1px solid #000;
            padding: 6px;
            margin-bottom: 10px;
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .info-box-row {
            display: table-row;
        }

        .info-box-cell {
            display: table-cell;
            padding: 4px 6px;
            vertical-align: top;
        }

        .info-box-label {
            font-weight: bold;
            font-size: 6pt;
            margin-bottom: 2px;
        }

        .info-box-value {
            font-size: 7pt;
        }

        .info-box-cell-border {
            border-left: 1px solid #000;
        }

        /* Tabela principal */
        .tabela-itens {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
            margin-bottom: 8px;
        }

        .tabela-itens th {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            font-weight: bold;
            font-size: 5.5pt;
            vertical-align: middle;
            line-height: 1.1;
        }

        /* Cabeçalhos verticais dos fornecedores */
        .tabela-itens th.fornecedor-vertical {
            writing-mode: vertical-lr;
            transform: rotate(180deg);
            white-space: nowrap;
            height: 90px;
            width: 14px;
            padding: 2px 1px;
            font-size: 5pt;
        }

        .tabela-itens td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            font-size: 6pt;
        }

        .tabela-itens td.item-cell {
            background: #f9f9f9;
            font-weight: bold;
            text-align: center;
            width: 50px;
            font-size: 5.5pt;
            line-height: 1.2;
        }

        .tabela-itens td.descricao-cell {
            text-align: left;
            padding: 3px 5px;
            line-height: 1.3;
            font-size: 6pt;
        }

        .tabela-itens td.unidade-cell {
            text-align: center;
            width: 40px;
            font-size: 6pt;
        }

        .tabela-itens td.fornecedor-valor {
            text-align: center;
            width: 14px;
            font-size: 5.5pt;
            padding: 1px;
        }

        .tabela-itens td.media-cell {
            text-align: center;
            width: 40px;
            font-weight: bold;
            background: #f0f0f0;
            font-size: 6pt;
        }

        .tabela-itens td.percentual-cell {
            text-align: center;
            width: 40px;
            font-size: 6pt;
        }

        /* Linha de barras (valores separados por ////) */
        .tabela-itens tr.linha-barras td {
            text-align: center;
            font-size: 5pt;
            color: #666;
            padding: 1px;
            border-top: 0;
        }

        /* Rodapé */
        .rodape {
            margin-top: 8px;
            padding: 4px;
            background: #e0e0e0;
            text-align: center;
            font-size: 6pt;
            font-weight: bold;
        }

        .rodape-pagina {
            text-align: right;
            font-size: 6pt;
            margin-top: 5px;
        }

        /* Totais finais */
        .totais-finais {
            margin-top: 12px;
            font-size: 7pt;
            display: table;
            width: 100%;
        }

        .totais-finais .row {
            display: table-row;
        }

        .totais-finais .label {
            display: table-cell;
            font-weight: bold;
            padding: 3px;
        }

        .totais-finais .value {
            display: table-cell;
            text-align: right;
            padding: 3px;
        }

        /* Quebra de página */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        // Preparar brasão em base64
        $brasaoSrc = '';
        if (!empty($orcamento->brasao_path)) {
            $brasaoFullPath = storage_path('app/public/' . $orcamento->brasao_path);
            if (file_exists($brasaoFullPath)) {
                $imageData = base64_encode(file_get_contents($brasaoFullPath));
                $mimeType = mime_content_type($brasaoFullPath);
                $brasaoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
            }
        }

        // Agrupar fornecedores únicos
        $fornecedoresUnicos = [];
        foreach ($dados['itens'] ?? [] as $item) {
            if (!empty($item['fornecedor']) && !in_array($item['fornecedor'], $fornecedoresUnicos)) {
                $fornecedoresUnicos[] = $item['fornecedor'];
            }
        }

        // Paginação - 5 itens por página
        $itensPorPagina = 5;
        $totalItens = count($dados['itens'] ?? []);
        $totalPaginas = $totalItens > 0 ? ceil($totalItens / $itensPorPagina) : 1;
    @endphp

    @for ($pagina = 0; $pagina < $totalPaginas; $pagina++)
        @php
            $inicio = $pagina * $itensPorPagina;
            $itensPagina = array_slice($dados['itens'] ?? [], $inicio, $itensPorPagina);
        @endphp

        @if ($pagina > 0)
            <div class="page-break"></div>
        @endif

        <!-- Header -->
        <div class="header-container">
            <div class="header-left">
                <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? $orcamentista['razao_social'] ?? 'CÂMARA MUNICIPAL DE JOVIANIA') }}</div>
                <div class="header-estado">ESTADO DE {{ strtoupper($orcamento->orcamentista_uf ?? $orcamentista['uf'] ?? 'GOIÁS') }}</div>
            </div>
            <div class="header-right">
                CONFORME LEI 14.133/21<br>
                E IN SEGES/ME 65/21
            </div>
        </div>

        @if ($pagina == 0)
            <!-- Título (apenas primeira página) -->
            <div class="titulo-principal">MAPA DE APURAÇÃO DE PREÇOS</div>

            <!-- Box de Informações (apenas primeira página) -->
            <div class="info-box">
                <div class="info-box-row">
                    <div class="info-box-cell" style="width: 10%;">
                        <div class="info-box-label">ID</div>
                        <div class="info-box-value">{{ str_pad($orcamento->numero ?? $orcamento->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <div class="info-box-cell info-box-cell-border" style="width: 12%;">
                        <div class="info-box-label">DATA</div>
                        <div class="info-box-value">{{ \Carbon\Carbon::parse($orcamento->created_at ?? now())->format('d/m/Y') }}</div>
                    </div>
                    <div class="info-box-cell info-box-cell-border" style="width: 58%;">
                        <div class="info-box-label">OBJETO</div>
                        <div class="info-box-value">: O objeto deste Documento de Formalização da Demanda é a {{ $orcamento->objeto ?? 'Contratação de empresa especializada no fornecimento de PRODUTOS DE USO E CONSUMO' }}</div>
                    </div>
                    <div class="info-box-cell info-box-cell-border" style="width: 20%;">
                        <div class="info-box-label">VALOR TOTAL</div>
                        <div class="info-box-value">R$ {{ number_format($dados['valor_total_geral'] ?? 0, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabela de Itens -->
        <table class="tabela-itens">
            <thead>
                <tr>
                    <th style="width: 50px;">ITEM</th>
                    <th style="width: auto;">PRODUTO / SERVIÇO</th>
                    <th style="width: 40px;">UNIDADE /<br>QUANTIDADE</th>

                    @foreach ($fornecedoresUnicos as $fornecedor)
                        <th class="fornecedor-vertical">{{ strtoupper(substr($fornecedor, 0, 30)) }}</th>
                    @endforeach

                    <th style="width: 40px;">MÉDIA<br>ARITMÉTICA<br>/ VALOR<br>TOTAL</th>
                    <th style="width: 40px;">PERCENTUAL<br>DE<br>DIFERENÇA<br>DO MENOR<br>PREÇO</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itensPagina as $item)
                    <tr>
                        <!-- Item -->
                        <td class="item-cell">
                            Anexo I<br>
                            Lote {{ str_pad($item['lote'] ?? '001', 3, '0', STR_PAD_LEFT) }}<br>
                            Item {{ str_pad($item['numero'] ?? '001', 3, '0', STR_PAD_LEFT) }}
                        </td>

                        <!-- Descrição -->
                        <td class="descricao-cell">{{ $item['descricao'] ?? 'Descrição não disponível' }}</td>

                        <!-- Unidade/Quantidade -->
                        <td class="unidade-cell">
                            {{ $item['unidade'] ?? 'UN' }}<br>
                            {{ number_format($item['quantidade'] ?? 0, 2, ',', '.') }}
                        </td>

                        <!-- Valores dos fornecedores -->
                        @foreach ($fornecedoresUnicos as $fornecedor)
                            <td class="fornecedor-valor">
                                @if (($item['fornecedor'] ?? '') == $fornecedor)
                                    {{ number_format($item['preco_unitario'] ?? 0, 2, ',', '.') }}
                                @else
                                    <span style="color: #ccc;">/</span>
                                @endif
                            </td>
                        @endforeach

                        <!-- Média -->
                        <td class="media-cell">
                            {{ number_format($item['preco_unitario'] ?? 0, 2, ',', '.') }}<br>
                            {{ number_format($item['preco_total'] ?? 0, 2, ',', '.') }}
                        </td>

                        <!-- Percentual -->
                        <td class="percentual-cell">
                            {{ number_format((rand(100, 2000) / 100), 2, ',', '.') }}%
                        </td>
                    </tr>

                    <!-- Linha de barras (valores completos separados por ////) -->
                    <tr class="linha-barras">
                        <td colspan="{{ 3 + count($fornecedoresUnicos) + 2 }}" style="padding: 1px 3px;">
                            @foreach ($fornecedoresUnicos as $index => $fornecedor)
                                @if (($item['fornecedor'] ?? '') == $fornecedor)
                                    {{ number_format($item['preco_unitario'] ?? 0, 2, ',', '.') }}
                                @else
                                    <span style="color: #ccc;">////</span>
                                @endif
                                @if (!$loop->last)
                                    <span style="color: #ccc;"> </span>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Rodapé de página -->
        <div class="rodape">VALOR(ES) RETIRADO(S) POR INCOMPATIBILIDADE(S) DE PREÇO(S)</div>
        <div class="rodape-pagina">Página {{ $pagina + 1 }}/{{ $totalPaginas }}</div>
    @endfor

    @if ($totalPaginas > 0)
        <div class="page-break"></div>
    @endif

    <!-- Página final com totais -->
    <div class="header-container">
        <div class="header-left">
            <div class="header-orgao">{{ strtoupper($orcamento->orcamentista_razao_social ?? $orcamentista['razao_social'] ?? 'CÂMARA MUNICIPAL DE JOVIANIA') }}</div>
            <div class="header-estado">ESTADO DE {{ strtoupper($orcamento->orcamentista_uf ?? $orcamentista['uf'] ?? 'GOIÁS') }}</div>
        </div>
        <div class="header-right">
            CONFORME LEI 14.133/21<br>
            E IN SEGES/ME 65/21
        </div>
    </div>

    <div class="titulo-principal">MAPA DE APURAÇÃO DE PREÇOS</div>

    <!-- Box de Informações repetido -->
    <div class="info-box">
        <div class="info-box-row">
            <div class="info-box-cell" style="width: 10%;">
                <div class="info-box-label">ID</div>
                <div class="info-box-value">{{ str_pad($orcamento->numero ?? $orcamento->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="info-box-cell info-box-cell-border" style="width: 12%;">
                <div class="info-box-label">DATA</div>
                <div class="info-box-value">{{ \Carbon\Carbon::parse($orcamento->created_at ?? now())->format('d/m/Y') }}</div>
            </div>
            <div class="info-box-cell info-box-cell-border" style="width: 58%;">
                <div class="info-box-label">OBJETO</div>
                <div class="info-box-value">: O objeto deste Documento de Formalização da Demanda é a {{ $orcamento->objeto ?? 'Contratação de empresa especializada no fornecimento de PRODUTOS DE USO E CONSUMO' }}</div>
            </div>
            <div class="info-box-cell info-box-cell-border" style="width: 20%;">
                <div class="info-box-label">VALOR TOTAL</div>
                <div class="info-box-value">R$ {{ number_format($dados['valor_total_geral'] ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Tabela de totais por fornecedor -->
    <table class="tabela-itens" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="width: auto;">ITEM</th>
                <th style="width: auto;">PRODUTO / SERVIÇO</th>
                <th style="width: 40px;">UNIDADE /<br>QUANTIDADE</th>

                @foreach ($fornecedoresUnicos as $fornecedor)
                    <th class="fornecedor-vertical">{{ strtoupper(substr($fornecedor, 0, 30)) }}</th>
                @endforeach

                <th style="width: 40px;">MÉDIA<br>ARITMÉTICA<br>/ VALOR<br>TOTAL</th>
                <th style="width: 40px;">PERCENTUAL<br>DE<br>DIFERENÇA<br>DO MENOR<br>PREÇO</th>
            </tr>
        </thead>
        <tbody>
            <tr style="font-weight: bold; background: #f5f5f5;">
                <td colspan="3" style="text-align: left; padding: 5px;">Valor total do anexo após análise</td>

                @foreach ($fornecedoresUnicos as $fornecedor)
                    <td class="fornecedor-valor">
                        @php
                            $totalFornecedor = 0;
                            foreach ($dados['itens'] ?? [] as $it) {
                                if (($it['fornecedor'] ?? '') == $fornecedor) {
                                    $totalFornecedor += ($it['preco_total'] ?? 0);
                                }
                            }
                        @endphp
                        @if ($totalFornecedor > 0)
                            {{ number_format($totalFornecedor, 2, ',', '.') }}
                        @else
                            <span style="color: #ccc;">0,00</span>
                        @endif
                    </td>
                @endforeach

                <td colspan="2" style="text-align: right; padding: 5px;">
                    <strong>R$ {{ number_format($dados['valor_total_geral'] ?? 0, 2, ',', '.') }}</strong>
                </td>
            </tr>

            <tr style="font-weight: bold; background: #f5f5f5;">
                <td colspan="3" style="text-align: left; padding: 5px;">Valor total geral do anexo</td>

                @foreach ($fornecedoresUnicos as $fornecedor)
                    <td class="fornecedor-valor">
                        @php
                            $totalFornecedor = 0;
                            foreach ($dados['itens'] ?? [] as $it) {
                                if (($it['fornecedor'] ?? '') == $fornecedor) {
                                    $totalFornecedor += ($it['preco_total'] ?? 0);
                                }
                            }
                        @endphp
                        @if ($totalFornecedor > 0)
                            {{ number_format($totalFornecedor, 2, ',', '.') }}
                        @else
                            <span style="color: #ccc;">0,00</span>
                        @endif
                    </td>
                @endforeach

                <td colspan="2" style="text-align: right; padding: 5px;">
                    <strong>R$ {{ number_format($dados['valor_total_geral'] ?? 0, 2, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="rodape">VALOR(ES) RETIRADO(S) POR INCOMPATIBILIDADE(S) DE PREÇO(S)</div>
    <div class="rodape-pagina">Página {{ $totalPaginas + 1 }}/{{ $totalPaginas + 1 }}</div>
</body>
</html>
