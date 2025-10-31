<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação de Preços - {{ $item->descricao }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f9fafb;
            overflow-x: hidden;
        }

        .page-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container-fluid {
            max-width: 1800px;
            margin: 0 auto;
            padding: 24px;
        }

        .section-title {
            background: #e5e7eb;
            padding: 12px 16px;
            border-radius: 6px 6px 0 0;
            font-weight: 700;
            font-size: 14px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button {
            flex: 1;
            padding: 12px;
            border: none;
            background: #d1d5db;
            color: #374151;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-button.active {
            background: #3b82f6;
            color: white;
        }

        .tab-button:hover:not(.active) {
            background: #9ca3af;
        }

        .badge-fonte {
            font-size: 9px;
            padding: 4px 8px;
            font-weight: 700;
            border-radius: 3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge-pncp { background: #3b82f6; color: white; }
        .badge-licitacon { background: #10b981; color: white; }
        .badge-banco-precos { background: #f59e0b; color: white; }
        .badge-comprasnet { background: #8b5cf6; color: white; }
        .badge-local { background: #6b7280; color: white; }

        .stats-card {
            background: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-action {
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-detalhe {
            background: #3b82f6;
            color: white;
        }

        .btn-ajuste {
            background: #10b981;
            color: white;
        }

        .table-responsive-custom {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .table-responsive-custom::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive-custom::-webkit-scrollbar-track {
            background: #f3f4f6;
        }

        .table-responsive-custom::-webkit-scrollbar-thumb {
            background: #9ca3af;
            border-radius: 4px;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f3f4f6;
        }

        .analise-section {
            background: white;
            border-radius: 6px;
            padding: 24px;
            margin-top: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .footer-actions {
            background: white;
            padding: 20px 24px;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            bottom: 0;
            z-index: 50;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho da Página -->
    <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0; font-weight: 700;">COTAÇÃO DE PREÇOS</h4>
                <p style="margin: 0; margin-top: 8px; opacity: 0.9; font-size: 14px;">
                    <strong>Orçamento:</strong> {{ $orcamento->titulo }} |
                    <strong>Item:</strong> {{ $item->descricao }}
                </p>
            </div>
            <button onclick="window.close()" class="btn btn-light">
                <i class="fas fa-times"></i> FECHAR
            </button>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Abas: Palavra-chave / CATMAT -->
        <div style="display: flex; margin-bottom: 24px; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
            <button class="tab-button active" id="tab-palavra-chave" onclick="alternarAba('palavra-chave')">
                <i class="fas fa-keyboard"></i> LOCALIZAR A DESCRIÇÃO DE ITEM POR PALAVRA-CHAVE
            </button>
            <button class="tab-button" id="tab-catmat" onclick="alternarAba('catmat')">
                <i class="fas fa-barcode"></i> LOCALIZAR PELO CÓDIGO CATMAT/CATSER
            </button>
        </div>

        <!-- Conteúdo das Abas -->
        <div id="conteudo-palavra-chave" class="aba-conteudo">
            <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                    DESCRIÇÃO DO ITEM OU PALAVRA-CHAVE RELACIONADA À SUA BUSCA
                </label>
                <div style="display: flex; gap: 12px;">
                    <input
                        type="text"
                        id="cotacao-palavra-chave"
                        class="form-control"
                        placeholder="Digite a palavra-chave para buscar (ex: caneta, caderno, mouse)"
                        style="font-size: 13px;"
                        value="{{ $item->descricao }}"
                    >
                    <button id="btn-pesquisar-cotacao" class="btn btn-primary" style="min-width: 140px; font-weight: 600;">
                        <i class="fas fa-search"></i> PESQUISAR
                    </button>
                </div>
            </div>

            <!-- Box: Refinar + Resultados -->
            <div style="display: flex; min-height: 600px; background: white; border-radius: 6px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                <!-- Coluna Esquerda: Refinar -->
                <div style="flex: 0 0 280px; background: #e5e7eb; padding: 20px; border-right: 1px solid #d1d5db;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <i class="fas fa-filter" style="color: #6b7280;"></i>
                        <h6 style="margin: 0; color: #374151; font-weight: 700; font-size: 13px;">REFINAR</h6>
                    </div>
                    <p style="font-size: 12px; color: #6b7280; line-height: 1.5;">
                        Quando você preencher o campo de busca em <strong>localizar descrição de item</strong>, e depois clicar no botão <strong>pesquisar</strong>, o sistema disponibilizará mais opções de filtros.
                    </p>
                </div>

                <!-- Coluna Direita: Resultado da Pesquisa -->
                <div style="flex: 1; padding: 20px; background: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-equals" style="color: #6b7280;"></i>
                            <h6 style="margin: 0; color: #374151; font-weight: 700; font-size: 13px;">RESULTADO DA PESQUISA</h6>
                        </div>
                        <select id="ordem-resultado" class="form-select" style="width: auto; font-size: 12px; padding: 6px 12px;">
                            <option>ORDENAR POR: MENOR PREÇO/UNID ▼</option>
                            <option>ORDENAR POR: MAIOR PREÇO/UNID ▼</option>
                            <option>ORDENAR POR: DATA MAIS RECENTE ▼</option>
                        </select>
                    </div>

                    <!-- Mensagem Inicial -->
                    <div id="cotacao-resultado-inicial" class="alert alert-warning" style="background: #fef3c7; border: 1px solid #fbbf24; color: #92400e; padding: 16px; border-radius: 6px; font-size: 13px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Preencha os campos de <strong>localizar descrição de item</strong> e depois clique no botão <strong>pesquisar</strong> para listar amostras de preços.
                    </div>

                    <!-- Tabela de Resultados (será preenchida via JavaScript) -->
                    <div id="cotacao-resultados" style="display: none;">
                        <!-- Estatísticas do topo -->
                        <div class="stats-card" style="margin-bottom: 16px;">
                            <div style="display: flex; gap: 12px; justify-content: space-between; margin-bottom: 12px;">
                                <button class="btn btn-sm" style="background: #0891b2; color: white; font-size: 11px; font-weight: 600;">
                                    ➜ IR PARA MEDIANA
                                </button>
                                <button class="btn btn-sm" style="background: #6b7280; color: white; font-size: 11px; font-weight: 600;">
                                    ☑ SELECIONAR 6 ITENS A PARTIR DA MEDIANA
                                </button>
                            </div>
                            <div style="display: flex; gap: 24px; font-size: 12px;">
                                <div>
                                    <span style="color: #6b7280;">QUANTIDADE DE AMOSTRAS</span><br>
                                    <strong id="stats-qtd-amostras" style="font-size: 18px; color: #0891b2;">0</strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">PREÇO MÍNIMO</span><br>
                                    <strong id="stats-preco-min" style="font-size: 16px; color: #10b981;">R$ 0,00</strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">PREÇO MÁXIMO</span><br>
                                    <strong id="stats-preco-max" style="font-size: 16px; color: #ef4444;">R$ 0,00</strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">MÉDIA DE PREÇOS</span><br>
                                    <strong id="stats-media" style="font-size: 16px; color: #3b82f6;">R$ 0,00</strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">MEDIANA</span><br>
                                    <strong id="stats-mediana" style="font-size: 16px; color: #0891b2;">R$ 0,00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive-custom">
                            <table class="table table-sm" style="font-size: 11px; margin: 0;">
                                <thead class="sticky-header">
                                    <tr>
                                        <th style="width: 40px; text-align: center;">☑</th>
                                        <th style="min-width: 400px;">PRODUTO/SERVIÇO</th>
                                        <th style="min-width: 250px;">ORG. LICITANTE / FONTE</th>
                                        <th style="width: 100px;">UNIDADE DE FORNEC.</th>
                                        <th style="width: 100px; text-align: right;">QUANT.</th>
                                        <th style="width: 120px; text-align: right;">VALOR UNITÁRIO (R$)</th>
                                        <th style="width: 140px; text-align: center;">AÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody id="cotacao-tbody">
                                    <!-- Será preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="conteudo-catmat" class="aba-conteudo" style="display: none;">
            <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                <label style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                    CÓDIGO CATMAT/CATSER
                </label>
                <div style="display: flex; gap: 12px;">
                    <input
                        type="text"
                        id="cotacao-catmat"
                        class="form-control"
                        placeholder="Digite o código CATMAT/CATSER"
                        style="font-size: 13px;"
                    >
                    <button class="btn btn-primary" style="min-width: 140px; font-weight: 600;">
                        <i class="fas fa-search"></i> PESQUISAR
                    </button>
                </div>
            </div>
        </div>

        <!-- Análise Crítica das Amostras -->
        <div class="analise-section">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                <i class="fas fa-chart-bar" style="color: #3b82f6; font-size: 18px;"></i>
                <h5 style="margin: 0; color: #374151; font-weight: 700; font-size: 16px;">ANÁLISE CRÍTICA DAS AMOSTRAS</h5>
            </div>

            <!-- Juízo Crítico -->
            <div style="margin-bottom: 24px;">
                <h6 style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px;">JUÍZO CRÍTICO</h6>
                <table class="table table-sm table-bordered" style="font-size: 12px;">
                    <thead style="background: #f3f4f6;">
                        <tr>
                            <th>Nº DE AMOSTRAS COLETADAS</th>
                            <th>MÉDIA</th>
                            <th>DESVIO-PADRÃO</th>
                            <th>LIMITE INFERIOR</th>
                            <th>LIMITE SUPERIOR</th>
                            <th>CRÍTICAS</th>
                            <th>AMOSTRAS EXPURGADAS</th>
                        </tr>
                    </thead>
                    <tbody id="juizo-critico-tbody">
                        <tr style="text-align: center;">
                            <td id="juizo-num-amostras">0</td>
                            <td id="juizo-media">R$ 0,00</td>
                            <td id="juizo-desvio">0,00</td>
                            <td id="juizo-limite-inf">R$ 0,00 (DP - média)</td>
                            <td id="juizo-limite-sup">R$ 0,00 (DP + média)</td>
                            <td><span class="badge bg-danger" id="juizo-criticas">0</span></td>
                            <td><span class="badge bg-secondary" id="juizo-expurgadas">0</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Método Estatístico -->
            <div style="margin-bottom: 24px;">
                <h6 style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px;">MÉTODO ESTATÍSTICO APLICADO ÀS AMOSTRAS SANEADAS</h6>
                <table class="table table-sm table-bordered" style="font-size: 12px;">
                    <thead style="background: #f3f4f6;">
                        <tr>
                            <th>Nº DE AMOSTRAS VÁLIDAS</th>
                            <th>DESVIO-PADRÃO</th>
                            <th>COEFICIENTE DE VARIAÇÃO</th>
                            <th>MENOR PREÇO</th>
                            <th>MÉDIA</th>
                            <th>MEDIANA</th>
                        </tr>
                    </thead>
                    <tbody id="metodo-estatistico-tbody">
                        <tr style="text-align: center;">
                            <td id="metodo-num-validas">0</td>
                            <td id="metodo-desvio">0,00</td>
                            <td id="metodo-coef-var">0,00%</td>
                            <td id="metodo-menor">R$ 0,00</td>
                            <td id="metodo-media">R$ 0,00</td>
                            <td id="metodo-mediana">R$ 0,00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Série de Preços Coletados -->
            <div style="margin-bottom: 24px;">
                <h6 style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; text-transform: uppercase;">Série de Preços Coletados</h6>

                <!-- Mensagem quando não tem amostras -->
                <div id="serie-vazia" style="font-size: 12px; color: #6b7280; padding: 12px; background: #f9fafb; border-radius: 4px;">
                    VOCÊ NÃO POSSUI AMOSTRAS DE PREÇOS PARA ESTE ITEM
                </div>

                <!-- Tabela de amostras selecionadas -->
                <div id="serie-tabela" style="display: none;">
                    <div class="table-responsive-custom" style="max-height: 400px;">
                        <table class="table table-sm table-bordered" style="font-size: 11px; margin: 0;">
                            <thead class="sticky-header">
                                <tr>
                                    <th style="width: 60px;">AMOSTRA</th>
                                    <th style="width: 100px;">SITUAÇÃO</th>
                                    <th style="min-width: 300px;">FONTE</th>
                                    <th style="width: 100px;">MARCA</th>
                                    <th style="width: 100px;">DATA</th>
                                    <th style="width: 80px;">MEDIDA</th>
                                    <th style="width: 100px; text-align: right;">QNT ORIGINAL</th>
                                    <th style="width: 120px; text-align: right;">VALOR UNITÁRIO</th>
                                    <th style="width: 100px; text-align: center;">AÇÕES</th>
                                </tr>
                            </thead>
                            <tbody id="serie-tbody">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Botão remover todas -->
                    <div style="text-align: right; margin-top: 12px;">
                        <button id="btn-remover-todas-amostras" class="btn btn-sm" style="background: #dc2626; color: white; font-size: 11px; font-weight: 600;">
                            <i class="fas fa-times"></i> REMOVER TODAS AS AMOSTRAS
                        </button>
                    </div>
                </div>
            </div>

            <!-- Método Estatístico Final -->
            <div style="margin-bottom: 24px;">
                <h6 style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px;">MÉTODO ESTATÍSTICO APLICADO ÀS AMOSTRAS SANEADAS</h6>
                <div style="background: #f3f4f6; padding: 16px; border-radius: 4px;">
                    <table style="width: 100%; font-size: 13px;">
                        <tr>
                            <td style="text-align: right; font-weight: 600; padding: 4px;">MEDIANA</td>
                            <td style="width: 150px; text-align: right; padding: 4px;" id="final-mediana">R$ 0,00</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; font-weight: 600; padding: 4px;">MÉDIA</td>
                            <td style="text-align: right; padding: 4px;" id="final-media">R$ 0,00</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; font-weight: 600; padding: 4px;">MENOR PREÇO</td>
                            <td style="text-align: right; padding: 4px;" id="final-menor">R$ 0,00</td>
                        </tr>
                        <tr style="border-top: 2px solid #0891b2;">
                            <td style="text-align: right; font-weight: 700; padding: 8px 4px; color: #0891b2;">MEDIDA DE TENDÊNCIA CENTRAL</td>
                            <td style="text-align: right; font-weight: 700; padding: 8px 4px; color: #0891b2; font-size: 16px;" id="final-tendencia">R$ 0,00</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Crítica dos Dados -->
            <div>
                <h6 style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; text-transform: uppercase;">Crítica dos Dados</h6>
                <button id="btn-adicionar-justificativa" class="btn btn-outline-secondary btn-sm" style="font-size: 12px; font-weight: 600; margin-bottom: 12px;">
                    <i class="fas fa-pencil-alt"></i> ADICIONAR JUSTIFICATIVA OU OBSERVAÇÃO
                </button>

                <!-- Checkboxes de Críticas -->
                <div style="margin-top: 12px;">
                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="critica-medidas-desiguais" style="width: 16px; height: 16px;">
                        <label for="critica-medidas-desiguais" style="font-size: 12px; margin: 0; cursor: pointer;">
                            1. Existem amostras com medidas de fornecimentos desiguais.
                        </label>
                        <button class="btn btn-sm" style="padding: 2px 8px; background: #6b7280; color: white; font-size: 10px;">+</button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="critica-valores-discrepantes" style="width: 16px; height: 16px;">
                        <label for="critica-valores-discrepantes" style="font-size: 12px; margin: 0; cursor: pointer;">
                            2. Existem amostras com valores muito discrepantes.
                        </label>
                        <button class="btn btn-sm" style="padding: 2px 8px; background: #6b7280; color: white; font-size: 10px;">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer com Botões -->
    <div class="footer-actions">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button id="btn-concluir-cotacao" class="btn btn-lg" style="background: #0891b2; color: white; font-weight: 600; padding: 12px 32px;">
                <i class="fas fa-check"></i> CONCLUIR COTAÇÃO E FECHAR JANELA
            </button>
            <button type="button" class="btn btn-lg" onclick="window.close()" style="background: #9ca3af; color: white; font-weight: 600; padding: 12px 32px;">
                <i class="fas fa-times"></i> FECHAR
            </button>
        </div>
    </div>

    <!-- Modal: Detalhes da Fonte Consultada -->
    <div class="modal fade" id="modalDetalhesFonte" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 900px;">
            <div class="modal-content">
                <div class="modal-header" style="background: #3b82f6; color: white; padding: 16px 24px;">
                    <h5 class="modal-title" style="font-weight: 700; font-size: 16px; text-transform: uppercase;">
                        DETALHES DA FONTE CONSULTADA
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <table class="table table-sm table-bordered" style="font-size: 12px;">
                        <tbody>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600; width: 180px;">FONTE:</td>
                                <td id="detalhe-fonte"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">IDENTIFICAÇÃO:</td>
                                <td id="detalhe-identificacao"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">Nº DO PREGÃO:</td>
                                <td id="detalhe-pregao"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">Nº DA ATA:</td>
                                <td id="detalhe-ata"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">DATA/HOMOLOGAÇÃO:</td>
                                <td id="detalhe-data-homologacao"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">ÓRGÃO:</td>
                                <td id="detalhe-orgao"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">OBJETO:</td>
                                <td id="detalhe-objeto"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">LOTE/ITEM/SUBITEM:</td>
                                <td id="detalhe-lote"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">VENCEDOR:</td>
                                <td id="detalhe-vencedor"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">DESCRIÇÃO:</td>
                                <td id="detalhe-descricao"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">MARCA:</td>
                                <td id="detalhe-marca"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">UNIDADE:</td>
                                <td id="detalhe-unidade"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">QUANTIDADE:</td>
                                <td id="detalhe-quantidade"></td>
                            </tr>
                            <tr>
                                <td style="background: #f3f4f6; font-weight: 600;">VALOR UNITÁRIO:</td>
                                <td id="detalhe-valor-unitario"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="text-align: center; margin-top: 20px;">
                        <a id="btn-download-arp" href="#" target="_blank" class="btn btn-primary" style="font-weight: 600;">
                            <i class="fas fa-download"></i> DOWNLOAD DA ARP
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Ajuste de Embalagem -->
    <div class="modal fade" id="modalAjusteEmbalagem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 900px;">
            <div class="modal-content">
                <div class="modal-header" style="background: #10b981; color: white; padding: 16px 24px;">
                    <h5 class="modal-title" style="font-weight: 700; font-size: 16px; text-transform: uppercase;">
                        AJUSTE DE EMBALAGEM
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Coluna Esquerda: Dados Originais (read-only) -->
                        <div style="background: #e5e7eb; padding: 20px; border-radius: 6px;">
                            <h6 style="font-weight: 700; color: #374151; margin-bottom: 16px;">Medida de Fornecimento Original</h6>

                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">UNIDADE</label>
                            <input id="ajuste-original-unidade" class="form-control form-control-sm mb-3" readonly>

                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">EMBALAGEM</label>
                            <select id="ajuste-original-embalagem" class="form-select form-select-sm mb-3" disabled>
                                <option>PRIMÁRIA</option>
                                <option>SECUNDÁRIA</option>
                            </select>

                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">PREÇO UNITÁRIO ORIGINAL</label>
                            <input id="ajuste-original-preco" class="form-control form-control-sm" readonly>
                        </div>

                        <!-- Coluna Direita: Dados Desejados (editável) -->
                        <div style="background: #dbeafe; padding: 20px; border-radius: 6px;">
                            <h6 style="font-weight: 700; color: #374151; margin-bottom: 16px;">Medida de Fornecimento Desejada</h6>

                            <label style="font-size: 11px; font-weight: 600; color: #1e40af; margin-bottom: 4px; display: block;">UNIDADE DESEJADA</label>
                            <input id="ajuste-desejado-unidade" class="form-control form-control-sm mb-3" placeholder="Ex: UNIDADE, CAIXA, PACOTE">

                            <label style="font-size: 11px; font-weight: 600; color: #1e40af; margin-bottom: 4px; display: block;">EMBALAGEM DESEJADA</label>
                            <select id="ajuste-desejado-embalagem" class="form-select form-select-sm mb-3">
                                <option>PRIMÁRIA</option>
                                <option>SECUNDÁRIA</option>
                            </select>

                            <label style="font-size: 11px; font-weight: 600; color: #1e40af; margin-bottom: 4px; display: block;">FATOR DE CONVERSÃO</label>
                            <input id="ajuste-fator" type="number" step="0.01" class="form-control form-control-sm mb-3" placeholder="Ex: 1.5, 2, 0.5">

                            <label style="font-size: 11px; font-weight: 600; color: #1e40af; margin-bottom: 4px; display: block;">PREÇO AJUSTADO</label>
                            <input id="ajuste-desejado-preco" class="form-control form-control-sm" readonly style="background: #fef3c7; font-weight: 700;">
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 24px;">
                        <button id="btn-concluir-ajuste" class="btn btn-success btn-lg" style="font-weight: 600; min-width: 200px;">
                            <i class="fas fa-check"></i> CONCLUIR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const APP_BASE_PATH = '{{ config("app.base_path") }}';
        const ITEM_ID = {{ $item->id }};
        const ORCAMENTO_ID = {{ $orcamento->id }};
    </script>
    <script src="{{ asset('js/cotacao-precos.js') }}"></script>
</body>
</html>
