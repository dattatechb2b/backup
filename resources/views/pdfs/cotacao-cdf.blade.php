<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação Direta com Fornecedor - CDF #{{ $cdf->id }}</title>
    <style>
        /* ========================================
           LAYOUT PROFISSIONAL PRETO E BRANCO
           (Baseado no layout do Preview)
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
            line-height: 1.3;
        }

        /* Cabeçalho */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000000;
            background: #FFFFFF;
        }

        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000000;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 12pt;
            font-weight: normal;
            color: #000000;
        }

        /* Seções - FUNDO PRETO, TEXTO BRANCO */
        .secao {
            background: #000000;
            color: #FFFFFF;
            padding: 8px 12px;
            margin: 20px 0 10px 0;
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
        }

        /* Info Box - APENAS BORDAS PRETAS */
        .info-section {
            background: #FFFFFF;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #000000;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 9pt;
        }

        .info-label {
            font-weight: bold;
            color: #000000;
            width: 180px;
        }

        .info-value {
            color: #000000;
            flex: 1;
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
            padding: 8px 6px;
            border: 1px solid #000000;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }

        table td {
            padding: 6px;
            border: 1px solid #000000;
            background: #FFFFFF;
            color: #000000;
        }

        table tbody tr:nth-child(even) {
            background: #F5F5F5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Total - FUNDO PRETO */
        .total-row {
            background: #000000 !important;
            color: #FFFFFF !important;
            font-weight: bold;
            font-size: 10pt;
        }

        .total-row td {
            background: #000000 !important;
            color: #FFFFFF !important;
            border: 1px solid #000000;
        }

        /* Box de destaque - APENAS BORDA */
        .box-destaque {
            background: #FFFFFF;
            border: 2px solid #000000;
            padding: 10px;
            margin: 10px 0;
        }

        .box-destaque h3 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #000000;
        }

        .box-destaque p {
            font-size: 9pt;
            color: #000000;
            line-height: 1.4;
        }

        /* Badge - SEM COR, APENAS BORDA */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #000000;
            font-weight: bold;
            font-size: 8pt;
            background: #FFFFFF;
            color: #000000;
        }

        /* Rodapé */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #000000;
            text-align: center;
            font-size: 8pt;
            color: #000000;
            background: #FFFFFF;
        }

        .footer p {
            margin: 3px 0;
        }

        /* Justificativas */
        .justificativa-item {
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .justificativa-item:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <h1>COTAÇÃO DIRETA COM FORNECEDOR</h1>
        <h2>CDF #{{ $cdf->id }} - {{ $orcamento->nome }}</h2>
    </div>

    <!-- Seção: Informações do Orçamento -->
    <div class="secao">INFORMAÇÕES DO ORÇAMENTO</div>
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Número do Orçamento:</span>
            <span class="info-value">{{ $orcamento->numero }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nome:</span>
            <span class="info-value">{{ $orcamento->nome }}</span>
        </div>
        @if($orcamento->referencia_externa)
        <div class="info-row">
            <span class="info-label">Referência Externa:</span>
            <span class="info-value">{{ $orcamento->referencia_externa }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Objeto:</span>
            <span class="info-value">{{ $orcamento->objeto }}</span>
        </div>
    </div>

    <!-- Seção: Dados do Fornecedor -->
    <div class="secao">DADOS DO FORNECEDOR</div>
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">CNPJ:</span>
            <span class="info-value">{{ $cdf->cnpj }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Razão Social:</span>
            <span class="info-value">{{ $cdf->razao_social }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">E-mail:</span>
            <span class="info-value">{{ $cdf->email }}</span>
        </div>
        @if($cdf->telefone)
        <div class="info-row">
            <span class="info-label">Telefone:</span>
            <span class="info-value">{{ $cdf->telefone }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
                <span class="badge">{{ $cdf->status }}</span>
            </span>
        </div>
    </div>

    <!-- Seção: Condições da Cotação -->
    <div class="secao">CONDIÇÕES DA COTAÇÃO</div>
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Prazo de Resposta:</span>
            <span class="info-value">{{ $cdf->prazo_resposta_dias }} dias úteis</span>
        </div>
        <div class="info-row">
            <span class="info-label">Prazo de Entrega:</span>
            <span class="info-value">{{ $cdf->prazo_entrega_dias }} dias úteis</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tipo de Frete:</span>
            <span class="info-value">{{ $cdf->frete }}</span>
        </div>
        @if($cdf->metodo_coleta)
        <div class="info-row">
            <span class="info-label">Método de Coleta:</span>
            <span class="info-value">{{ ucfirst($cdf->metodo_coleta) }}</span>
        </div>
        @endif
        @if($cdf->data_resposta)
        <div class="info-row">
            <span class="info-label">Data da Resposta:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($cdf->data_resposta)->format('d/m/Y H:i') }}</span>
        </div>
        @endif
    </div>

    <!-- Observações (se houver) -->
    @if($cdf->observacao)
    <div class="secao">OBSERVAÇÕES</div>
    <div class="box-destaque">
        <p>{{ $cdf->observacao }}</p>
    </div>
    @endif

    <!-- Seção: Itens da Cotação -->
    <div class="secao">ITENS DA COTAÇÃO</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Nº</th>
                <th style="width: 35%;">Descrição</th>
                <th style="width: 10%;" class="text-center">Unid.</th>
                <th style="width: 10%;" class="text-center">Qtd.</th>
                <th style="width: 15%;">Marca</th>
                <th style="width: 12%;" class="text-right">Preço Unit.</th>
                <th style="width: 13%;" class="text-right">Preço Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
            <tr>
                <td class="text-center">{{ $item['numero'] }}</td>
                <td>{{ $item['descricao'] }}</td>
                <td class="text-center">{{ $item['unidade'] }}</td>
                <td class="text-center">{{ number_format($item['quantidade'], 2, ',', '.') }}</td>
                <td>{{ $item['marca'] }}</td>
                <td class="text-right">R$ {{ number_format($item['preco_unitario'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item['preco_total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right"><strong>VALOR TOTAL DA COTAÇÃO:</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($total_geral, 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Justificativas (se houver) -->
    @if($cdf->justificativa_fornecedor_unico || $cdf->justificativa_produto_exclusivo || $cdf->justificativa_urgencia || $cdf->justificativa_melhor_preco || $cdf->justificativa_outro)
    <div class="secao">JUSTIFICATIVAS PARA COTAÇÃO DIRETA</div>
    <div class="info-section">
        @if($cdf->justificativa_fornecedor_unico)
        <div class="justificativa-item">Fornecedor único ou exclusivo</div>
        @endif
        @if($cdf->justificativa_produto_exclusivo)
        <div class="justificativa-item">Produto exclusivo ou marca específica</div>
        @endif
        @if($cdf->justificativa_urgencia)
        <div class="justificativa-item">Situação de urgência</div>
        @endif
        @if($cdf->justificativa_melhor_preco)
        <div class="justificativa-item">Melhor preço comprovado</div>
        @endif
        @if($cdf->justificativa_outro)
        <div class="info-row" style="margin-top: 10px;">
            <span class="info-label">Outra Justificativa:</span>
            <span class="info-value">{{ $cdf->justificativa_outro }}</span>
        </div>
        @endif
    </div>
    @endif

    <!-- Rodapé -->
    <div class="footer">
        <p><strong>Sistema Cesta de Preços</strong> - Datta Tech</p>
        <p>Documento gerado em: {{ $data_geracao }}</p>
        <p>Este documento é uma representação oficial da cotação registrada no sistema.</p>
    </div>
</body>
</html>
