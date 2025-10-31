<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espelho CNPJ - {{ $dadosCNPJ['nome'] ?? 'Empresa' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1e40af;
        }

        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #666;
        }

        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #1e40af;
            color: white;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            padding: 4px 8px;
            background: #f3f4f6;
            font-weight: bold;
            width: 35%;
            border: 1px solid #d1d5db;
        }

        .info-value {
            display: table-cell;
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            width: 65%;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .status-ativa {
            background: #dcfce7;
            color: #166534;
        }

        .status-inativa {
            background: #fee2e2;
            color: #991b1b;
        }

        .atividades {
            margin-top: 5px;
        }

        .atividade-item {
            padding: 4px 0;
            border-bottom: 1px dotted #d1d5db;
        }

        .atividade-item:last-child {
            border-bottom: none;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .qsa-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .qsa-table th {
            background: #f3f4f6;
            padding: 5px;
            border: 1px solid #d1d5db;
            font-size: 10px;
            text-align: left;
        }

        .qsa-table td {
            padding: 5px;
            border: 1px solid #d1d5db;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ESPELHO DE CONSULTA CNPJ</h1>
        <p>Documento gerado em {{ date('d/m/Y \à\s H:i') }}</p>
    </div>

    <!-- Seção: Dados Cadastrais -->
    <div class="section">
        <div class="section-title">DADOS CADASTRAIS</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">CNPJ:</div>
                <div class="info-value">{{ $dadosCNPJ['cnpj'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Razão Social:</div>
                <div class="info-value">{{ $dadosCNPJ['nome'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Nome Fantasia:</div>
                <div class="info-value">{{ $dadosCNPJ['fantasia'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Situação Cadastral:</div>
                <div class="info-value">
                    @if(isset($dadosCNPJ['situacao']) && strtolower($dadosCNPJ['situacao']) == 'ativa')
                        <span class="status-badge status-ativa">{{ $dadosCNPJ['situacao'] }}</span>
                    @else
                        <span class="status-badge status-inativa">{{ $dadosCNPJ['situacao'] ?? 'Não informado' }}</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Data de Situação:</div>
                <div class="info-value">{{ $dadosCNPJ['data_situacao'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data de Abertura:</div>
                <div class="info-value">{{ $dadosCNPJ['abertura'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo:</div>
                <div class="info-value">{{ $dadosCNPJ['tipo'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Natureza Jurídica:</div>
                <div class="info-value">{{ $dadosCNPJ['natureza_juridica'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Porte:</div>
                <div class="info-value">{{ $dadosCNPJ['porte'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Capital Social:</div>
                <div class="info-value">R$ {{ isset($dadosCNPJ['capital_social']) ? number_format((float)$dadosCNPJ['capital_social'], 2, ',', '.') : '0,00' }}</div>
            </div>
        </div>
    </div>

    <!-- Seção: Endereço -->
    <div class="section">
        <div class="section-title">ENDEREÇO</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Logradouro:</div>
                <div class="info-value">{{ $dadosCNPJ['logradouro'] ?? 'Não informado' }}, {{ $dadosCNPJ['numero'] ?? 's/n' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Complemento:</div>
                <div class="info-value">{{ $dadosCNPJ['complemento'] ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Bairro:</div>
                <div class="info-value">{{ $dadosCNPJ['bairro'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Município:</div>
                <div class="info-value">{{ $dadosCNPJ['municipio'] ?? 'Não informado' }} - {{ $dadosCNPJ['uf'] ?? '' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CEP:</div>
                <div class="info-value">{{ $dadosCNPJ['cep'] ?? 'Não informado' }}</div>
            </div>
        </div>
    </div>

    <!-- Seção: Contato -->
    <div class="section">
        <div class="section-title">CONTATO</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value">{{ $dadosCNPJ['telefone'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">E-mail:</div>
                <div class="info-value">{{ $dadosCNPJ['email'] ?? 'Não informado' }}</div>
            </div>
        </div>
    </div>

    <!-- Seção: Atividades Econômicas -->
    <div class="section">
        <div class="section-title">ATIVIDADES ECONÔMICAS</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Atividade Principal:</div>
                <div class="info-value">
                    @if(isset($dadosCNPJ['atividade_principal']) && is_array($dadosCNPJ['atividade_principal']) && count($dadosCNPJ['atividade_principal']) > 0)
                        {{ $dadosCNPJ['atividade_principal'][0]['code'] ?? '' }} - {{ $dadosCNPJ['atividade_principal'][0]['text'] ?? 'Não informado' }}
                    @else
                        Não informado
                    @endif
                </div>
            </div>
            @if(isset($dadosCNPJ['atividades_secundarias']) && is_array($dadosCNPJ['atividades_secundarias']) && count($dadosCNPJ['atividades_secundarias']) > 0)
            <div class="info-row">
                <div class="info-label">Atividades Secundárias:</div>
                <div class="info-value">
                    <div class="atividades">
                        @foreach($dadosCNPJ['atividades_secundarias'] as $atividade)
                        <div class="atividade-item">
                            {{ $atividade['code'] ?? '' }} - {{ $atividade['text'] ?? '' }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Seção: Quadro Societário -->
    @if(isset($dadosCNPJ['qsa']) && is_array($dadosCNPJ['qsa']) && count($dadosCNPJ['qsa']) > 0)
    <div class="section">
        <div class="section-title">QUADRO DE SÓCIOS E ADMINISTRADORES (QSA)</div>
        <table class="qsa-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Qualificação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dadosCNPJ['qsa'] as $socio)
                <tr>
                    <td>{{ $socio['nome'] ?? '' }}</td>
                    <td>{{ $socio['qual'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Seção: Outras Informações -->
    <div class="section">
        <div class="section-title">OUTRAS INFORMAÇÕES</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">EFR (Ente Federativo Responsável):</div>
                <div class="info-value">{{ $dadosCNPJ['efr'] ?? 'Não informado' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Situação Especial:</div>
                <div class="info-value">{{ $dadosCNPJ['situacao_especial'] ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data Situação Especial:</div>
                <div class="info-value">{{ $dadosCNPJ['data_situacao_especial'] ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Seção: Informações da CDF (opcional - somente quando há CDF vinculada) -->
    @if(isset($cdf) && $cdf)
    <div class="section">
        <div class="section-title">INFORMAÇÕES DA SOLICITAÇÃO CDF</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Número da CDF:</div>
                <div class="info-value">{{ str_pad($cdf->id, 2, '0', STR_PAD_LEFT) }}/2025</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fornecedor (do sistema):</div>
                <div class="info-value">{{ $cdf->fornecedor }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data de Geração:</div>
                <div class="info-value">{{ $cdf->data_geracao ? \Carbon\Carbon::parse($cdf->data_geracao)->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data de Solicitação:</div>
                <div class="info-value">{{ $cdf->data_solicitacao ? \Carbon\Carbon::parse($cdf->data_solicitacao)->format('d/m/Y') : '-' }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <p><strong>Fonte dos dados:</strong> ReceitaWS (https://receitaws.com.br)</p>
        <p>Este documento foi gerado automaticamente pelo sistema de Cesta de Preços</p>
        <p>Data e hora de geração: {{ date('d/m/Y \à\s H:i:s') }}</p>
    </div>
</body>
</html>
