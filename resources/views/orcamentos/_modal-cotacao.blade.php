{{-- ================================================
     MODAL DE COTAÇÃO DE PREÇOS - REDESIGN MODERNO v3.0
     Design Moderno com Cards, Gradientes e Animações Suaves
     Data: 23/10/2025 | Versão: 3.0.20251023
     ================================================ --}}

{{-- Incluir CSS Moderno --}}
<link rel="stylesheet" href="{{ asset('css/modal-cotacao-modern.css') }}">

<div class="modal fade" id="modalCotacaoPrecos" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" data-version="3.0.20251023">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content" style="background: #f9fafb;">
            <!-- VERSÃO: 2.1 - REDESIGN MODERNO COM CARDS COLORIDOS -->

            {{-- ========================================
                 CABEÇALHO MODERNO v3.0
                 ======================================== --}}
            <div class="modal-header-modern" style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1; padding-right: 40px;">
                    <h5 class="modal-title-modern">
                        <i class="fas fa-shopping-cart" style="margin-right: 12px;"></i>
                        Cotação de Preços
                    </h5>
                    <p class="modal-subtitle-modern">Pesquise e compare preços de diferentes fontes governamentais</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar" style="font-size: 20px; opacity: 0.9; flex-shrink: 0;"></button>
            </div>

            {{-- ========================================
                 CORPO DO MODAL
                 ======================================== --}}
            <div class="modal-body" style="padding: 20px; background: #f9fafb; overflow-y: auto; max-height: calc(100vh - 140px);">

                {{-- ========================================
                     SEÇÃO 1: DESCRIÇÃO DO ITEM A SER COTADO - REDESIGN v3.0
                     ======================================== --}}
                <div class="card-modern animate-fadein">
                    <div class="card-modern-header">
                        <div class="card-modern-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <h2 class="card-modern-title">Item a ser Cotado</h2>
                            <p class="card-modern-subtitle">Descrição do produto ou serviço</p>
                        </div>
                    </div>
                    <div style="background: var(--bg-light); padding: 20px; border-radius: var(--border-radius-sm); border-left: 4px solid var(--primary-color);">
                        <p id="cotacao-item-descricao" style="margin: 0; font-size: 15px; color: var(--text-primary); font-weight: 500; line-height: 1.7;">
                            {{-- Preenchido via JavaScript --}}
                        </p>
                    </div>
                </div>

                {{-- ========================================
                     SEÇÃO 2: ARGUMENTO DE PESQUISA - REDESIGN v3.0
                     ======================================== --}}
                <div class="card-modern animate-fadein">
                    <div class="card-modern-header">
                        <div class="card-modern-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <h2 class="card-modern-title">Argumento de Pesquisa</h2>
                            <p class="card-modern-subtitle">Busque por palavra-chave ou código CATMAT</p>
                        </div>
                    </div>

                    {{-- Abas Modernos --}}
                    <div class="tabs-modern-container">
                        <button type="button" class="tab-modern tab-pesquisa active" data-tab="palavra-chave">
                            <i class="fas fa-keyboard" style="margin-right: 6px;"></i>
                            Pesquisar por Palavra-Chave
                        </button>
                        <button type="button" class="tab-modern tab-pesquisa" data-tab="catmat">
                            <i class="fas fa-barcode" style="margin-right: 6px;"></i>
                            Pesquisar por CATMAT/CATSER
                        </button>
                    </div>

                    {{-- Conteúdo Aba 1: Palavra-chave --}}
                    <div id="content-palavra-chave" class="tab-content-pesquisa">
                        <div style="display: grid; grid-template-columns: 3fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                            <div class="input-group-modern">
                                <label class="input-label-modern">
                                    <i class="fas fa-file-alt" style="margin-right: 6px; color: var(--primary-color);"></i>
                                    Descrição do produto ou serviço
                                </label>
                                <input type="text" id="input-palavra-chave" class="input-modern" placeholder="Ex: notebook, mouse, papel A4...">
                            </div>
                            <div class="input-group-modern">
                                <label class="input-label-modern">
                                    <i class="fas fa-building" style="margin-right: 6px; color: var(--primary-color);"></i>
                                    CNPJ (opcional)
                                </label>
                                <input type="text" id="input-cnpj" class="input-modern" placeholder="99.999.999/9999-99" maxlength="18">
                            </div>
                        </div>

                        <div style="display: flex; gap: var(--spacing-lg); align-items: center; margin-bottom: var(--spacing-lg);">
                            <label style="display: flex; align-items: center; gap: var(--spacing-sm); font-size: 14px; color: var(--text-primary); cursor: pointer; font-weight: 500;">
                                <input type="radio" name="tipo_busca" value="contem" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                                Contratos ou palavras
                            </label>
                            <label style="display: flex; align-items: center; gap: var(--spacing-sm); font-size: 14px; color: var(--text-primary); cursor: pointer; font-weight: 500;">
                                <input type="radio" name="tipo_busca" value="exata" style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                                Expressão exata
                            </label>
                        </div>

                        <button type="button" id="btn-pesquisar-cotacao" class="btn-modern btn-modern-primary">
                            <i class="fas fa-search"></i>
                            <span>Pesquisar Agora</span>
                        </button>
                    </div>

                    {{-- Conteúdo Aba 2: CATMAT/CATSER --}}
                    <div id="content-catmat" class="tab-content-pesquisa" style="display: none;">
                        <div style="display: grid; grid-template-columns: 3fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                            <div class="input-group-modern">
                                <label class="input-label-modern">
                                    <i class="fas fa-barcode" style="margin-right: 6px; color: var(--primary-color);"></i>
                                    Código CATMAT ou CATSER
                                </label>
                                <input type="text" id="input-catmat" class="input-modern" placeholder="Ex: 123456">
                            </div>
                            <div class="input-group-modern">
                                <label class="input-label-modern">
                                    <i class="fas fa-building" style="margin-right: 6px; color: var(--primary-color);"></i>
                                    CNPJ (opcional)
                                </label>
                                <input type="text" id="input-cnpj-catmat" class="input-modern" placeholder="99.999.999/9999-99" maxlength="18">
                            </div>
                        </div>

                        <button type="button" id="btn-pesquisar-catmat" class="btn-modern btn-modern-primary">
                            <i class="fas fa-search"></i>
                            <span>Pesquisar Agora</span>
                        </button>
                    </div>
                </div>

                {{-- ========================================
                     LAYOUT 2 COLUNAS: REFINAR + RESULTADOS
                     ======================================== --}}
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px;">

                    {{-- ========================================
                         COLUNA ESQUERDA: REFINAR - REDESIGN v3.0
                         ======================================== --}}
                    <div>
                        <div class="sidebar-modern animate-fadein">
                            <div class="sidebar-modern-title">
                                <i class="fas fa-sliders-h"></i>
                                <span>Refinar Resultados</span>
                            </div>

                            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 14px; border-radius: var(--border-radius-sm); border-left: 4px solid #f59e0b; margin-bottom: var(--spacing-lg);">
                                <div style="display: flex; align-items: flex-start; gap: 10px;">
                                    <i class="fas fa-info-circle" style="color: #92400e; font-size: 14px; margin-top: 2px;"></i>
                                    <p style="margin: 0; font-size: 12px; color: #78350f; line-height: 1.6; font-weight: 500;">
                                        Após realizar a pesquisa, mais opções de filtros ficarão disponíveis aqui.
                                    </p>
                                </div>
                            </div>

                            {{-- Filtros dinâmicos (aparecem após pesquisa) --}}
                            <div id="filtros-dinamicos" style="display: none;">

                                {{-- Fonte de Dados --}}
                                <div class="filter-group-modern">
                                    <div class="filter-group-modern-title">
                                        <i class="fas fa-database" style="font-size: 12px; margin-right: 6px; color: var(--primary-color);"></i>
                                        Fonte de Dados
                                    </div>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 8px; padding: 8px; border-radius: 6px; transition: background 0.2s;">
                                        <input type="checkbox" name="filtro_fonte" value="PNCP" checked style="width: 17px; height: 17px; accent-color: var(--primary-color); cursor: pointer;">
                                        <span style="font-size: 13px; color: var(--text-primary); font-weight: 500;">PNCP</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 8px; padding: 8px; border-radius: 6px; transition: background 0.2s;">
                                        <input type="checkbox" name="filtro_fonte" value="COMPRAS_GOV" checked style="width: 17px; height: 17px; accent-color: var(--primary-color); cursor: pointer;">
                                        <span style="font-size: 13px; color: var(--text-primary); font-weight: 500;">COMPRAS.GOV</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 8px; padding: 8px; border-radius: 6px; transition: background 0.2s;">
                                        <input type="checkbox" name="filtro_fonte" value="LICITACON" checked style="width: 17px; height: 17px; accent-color: var(--primary-color); cursor: pointer;">
                                        <span style="font-size: 13px; color: var(--text-primary); font-weight: 500;">TCE-RS (LicitaCon)</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px; border-radius: 6px; transition: background 0.2s;">
                                        <input type="checkbox" name="filtro_fonte" value="CMED" checked style="width: 17px; height: 17px; accent-color: var(--secondary-color); cursor: pointer;">
                                        <span style="font-size: 13px; color: var(--text-primary); font-weight: 500;">CMED</span>
                                    </label>
                                    <button type="button" id="btn-aplicar-filtro-fonte" class="btn-modern btn-modern-primary" style="width: 100%; margin-top: 12px; padding: 10px; font-size: 12px;">
                                        <i class="fas fa-check"></i>
                                        APLICAR
                                    </button>
                                </div>

                                <div style="border-top: 1px solid var(--border-color); margin: var(--spacing-lg) 0;"></div>

                                {{-- Porte da Empresa --}}
                                <div class="filter-group-modern">
                                    <div class="filter-group-modern-title">
                                        <i class="fas fa-building" style="font-size: 12px; margin-right: 6px; color: var(--primary-color);"></i>
                                        Porte da Empresa
                                    </div>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 8px; padding: 8px; border-radius: 6px; transition: background 0.2s;">
                                        <input type="radio" name="filtro_porte" value="todas" checked style="width: 17px; height: 17px; accent-color: var(--primary-color);">
                                        <span style="font-size: 13px; color: var(--text-primary); font-weight: 500;">TODAS</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px; border-radius: 6px; transition: background 0.2s;">
                                        <input type="radio" name="filtro_porte" value="me_epp" style="width: 17px; height: 17px; accent-color: var(--primary-color);">
                                        <span style="font-size: 13px; color: var(--text-primary); font-weight: 500;">ME/EPP</span>
                                    </label>
                                </div>

                                <div style="border-top: 1px solid var(--border-color); margin: var(--spacing-lg) 0;"></div>

                                {{-- Unidade de Medida --}}
                                <div class="filter-group-modern">
                                    <div class="filter-group-modern-title">
                                        <i class="fas fa-balance-scale" style="font-size: 12px; margin-right: 6px; color: var(--primary-color);"></i>
                                        Unidade de Medida
                                    </div>
                                    <small style="display: block; font-size: 11px; color: var(--text-secondary); margin-bottom: var(--spacing-sm); line-height: 1.5;">
                                        Ex: CX (Caixa), UN (Unidade)
                                    </small>
                                    <div id="container-filtro-unidades" style="max-height: 150px; overflow-y: auto; background: var(--bg-light); border: 2px solid var(--border-color); border-radius: var(--border-radius-sm); padding: var(--spacing-sm);">
                                        {{-- Será preenchido via JavaScript --}}
                                    </div>
                                </div>

                                <div style="border-top: 1px solid var(--border-color); margin: var(--spacing-lg) 0;"></div>

                                {{-- Unidade Federativa --}}
                                <div class="filter-group-modern">
                                    <div class="filter-group-modern-title">
                                        <i class="fas fa-map-marker-alt" style="font-size: 12px; margin-right: 6px; color: var(--primary-color);"></i>
                                        Unidade Federativa
                                    </div>
                                    <div id="container-filtro-ufs" style="max-height: 130px; overflow-y: auto; background: var(--bg-light); border: 2px solid var(--border-color); border-radius: var(--border-radius-sm); padding: var(--spacing-sm);">
                                        {{-- Será preenchido via JavaScript --}}
                                    </div>
                                </div>

                            </div>

                            <div style="border-top: 2px solid var(--border-color); margin: var(--spacing-lg) 0;"></div>

                            {{-- Preços dos Últimos --}}
                            <div class="filter-group-modern">
                                <div class="filter-group-modern-title">
                                    <i class="fas fa-calendar-alt" style="font-size: 12px; margin-right: 6px; color: var(--primary-color);"></i>
                                    Período
                                </div>
                                <select id="filtro-periodo-meses" class="input-modern" style="padding: 12px; font-size: 13px; font-weight: 500;">
                                    <option value="12" selected>Últimos 12 meses</option>
                                    <option value="6">Últimos 6 meses</option>
                                    <option value="3">Últimos 3 meses</option>
                                    <option value="1">Último mês</option>
                                </select>
                            </div>

                            <div style="border-top: 2px solid var(--border-color); margin: var(--spacing-lg) 0;"></div>

                            {{-- Variação de Preço --}}
                            <div class="filter-group-modern">
                                <div class="filter-group-modern-title">
                                    <i class="fas fa-dollar-sign" style="font-size: 12px; margin-right: 6px; color: var(--primary-color);"></i>
                                    Faixa de Preço
                                </div>
                                <div style="background: var(--bg-light); padding: var(--spacing-md); border: 2px solid var(--border-color); border-radius: var(--border-radius-sm);">
                                    <div class="input-group-modern">
                                        <label class="input-label-modern" style="font-size: 12px;">Mínimo:</label>
                                        <input type="text" id="filtro-preco-min" class="input-modern" placeholder="R$ 0,00" style="padding: 10px;">
                                    </div>
                                    <div class="input-group-modern">
                                        <label class="input-label-modern" style="font-size: 12px;">Máximo:</label>
                                        <input type="text" id="filtro-preco-max" class="input-modern" placeholder="R$ 99.999,99" style="padding: 10px;">
                                    </div>
                                    <button type="button" id="btn-aplicar-filtro-preco" class="btn-modern btn-modern-success" style="width: 100%; padding: 10px; font-size: 12px;">
                                        <i class="fas fa-check"></i>
                                        APLICAR
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- ========================================
                         COLUNA DIREITA: RESULTADO DA PESQUISA - REDESIGN v3.0
                         ======================================== --}}
                    <div>
                        <div class="card-modern animate-fadein">
                            {{-- Cabeçalho Moderno --}}
                            <div class="card-modern-header">
                                <div class="card-modern-icon">
                                    <i class="fas fa-th-list"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                                            <h2 class="card-modern-title">Resultado da Pesquisa</h2>
                                            <span id="contador-resultados-filtrados" class="badge-modern badge-modern-info" style="display: none;">
                                                {{-- Preenchido via JavaScript --}}
                                            </span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                            <label class="input-label-modern" style="margin: 0; font-size: 12px;">Ordenar:</label>
                                            <select id="select-ordenar" class="input-modern" style="padding: 8px 12px; font-size: 12px; min-width: 200px;">
                                                <option value="menor_preco" selected>Menor Preço</option>
                                                <option value="maior_preco">Maior Preço</option>
                                                <option value="data_recente">Mais Recente</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Botões de Ação Rápida --}}
                            <div style="display: flex; gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                                <button type="button" id="btn-ir-mediana" class="btn-modern btn-modern-secondary">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>IR PARA MEDIANA</span>
                                </button>
                                <button type="button" id="btn-selecionar-6-mediana" class="btn-modern btn-modern-secondary">
                                    <i class="fas fa-check-square"></i>
                                    <span>SELECIONAR 6 DA MEDIANA</span>
                                </button>
                            </div>

                            {{-- Estatísticas Modernas --}}
                            <div id="stats-cards" class="stats-grid-modern" style="display: none; margin-bottom: var(--spacing-lg);">
                                <div class="stat-card-modern" style="border-left-color: var(--primary-color);">
                                    <div class="stat-card-modern-label">Amostras</div>
                                    <div id="stat-quantidade" class="stat-card-modern-value">0</div>
                                </div>
                                <div class="stat-card-modern" style="border-left-color: var(--secondary-color);">
                                    <div class="stat-card-modern-label">Mínimo</div>
                                    <div id="stat-minimo" class="stat-card-modern-value" style="color: var(--secondary-color);">R$ 0,00</div>
                                </div>
                                <div class="stat-card-modern" style="border-left-color: #dc2626;">
                                    <div class="stat-card-modern-label">Máximo</div>
                                    <div id="stat-maximo" class="stat-card-modern-value" style="color: #dc2626;">R$ 0,00</div>
                                </div>
                                <div class="stat-card-modern" style="border-left-color: #3b82f6;">
                                    <div class="stat-card-modern-label">Média</div>
                                    <div id="stat-media-valor" class="stat-card-modern-value" style="color: #3b82f6;">R$ 0,00</div>
                                </div>
                                <div class="stat-card-modern" style="border-left-color: #8b5cf6;">
                                    <div class="stat-card-modern-label">Mediana</div>
                                    <div id="stat-mediana-valor" class="stat-card-modern-value" style="color: #8b5cf6;">R$ 0,00</div>
                                </div>
                            </div>

                            {{-- Estados e Tabela --}}
                            <div>
                                {{-- Estado Vazio --}}
                                <div id="estado-vazio" class="loading-modern">
                                    <div style="background: var(--bg-light); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-md);">
                                        <i class="fas fa-search" style="font-size: 32px; color: var(--text-muted);"></i>
                                    </div>
                                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary); text-align: center; max-width: 400px; line-height: 1.6;">
                                        Preencha o argumento de pesquisa acima e clique em <strong style="color: var(--primary-color);">Pesquisar Agora</strong> para encontrar amostras de preços.
                                    </p>
                                </div>

                                {{-- Estado Loading --}}
                                <div id="estado-loading" class="loading-modern" style="display: none; text-align: center;">
                                    <div class="loading-modern-spinner"></div>
                                    <p style="margin: 0; font-size: 14px; color: var(--text-primary); font-weight: 600; text-align: center;">
                                        Buscando amostras de preços...
                                    </p>
                                </div>

                                {{-- Tabela Moderna de Resultados --}}
                                <div id="container-tabela-resultados" style="display: none;">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th>Produto/Serviço</th>
                                                <th>Órgão / Fonte</th>
                                                <th style="text-align: center; width: 85px;">Unid.</th>
                                                <th style="text-align: right; width: 75px;">Quant.</th>
                                                <th style="text-align: right; width: 110px;">Valor Unit.</th>
                                                <th style="text-align: center; width: 120px;">
                                                    <i class="fas fa-cog" style="color: var(--primary-color);"></i> Ações
                                                </th>
                                                <th style="text-align: center; width: 65px;">
                                                    <i class="fas fa-check-square" style="color: var(--primary-color);"></i> Sel.
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-resultados-pesquisa">
                                            {{-- Linhas geradas via JavaScript --}}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                {{-- ========================================
                     SEÇÃO 3: ANÁLISE CRÍTICA DAS AMOSTRAS - REDESIGN v3.0
                     Aparece quando há amostras selecionadas (checkboxes)
                     ======================================== --}}
                <div id="secao-analise-critica" style="margin-top: var(--spacing-lg); display: block;">
                    <div class="card-modern animate-fadein">

                        {{-- Cabeçalho Moderno --}}
                        <div class="card-modern-header">
                            <div class="card-modern-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h2 class="card-modern-title">Análise Crítica das Amostras</h2>
                                <p class="card-modern-subtitle">Tratamento estatístico das amostras selecionadas</p>
                            </div>
                        </div>

                        {{-- Conteúdo --}}
                        <div>
                            {{-- JUÍZO CRÍTICO --}}
                            <div style="margin-bottom: var(--spacing-xl);">
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                                    <i class="fas fa-gavel" style="color: var(--primary-color); font-size: 14px;"></i>
                                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Juízo Crítico
                                    </h3>
                                </div>
                                <div style="overflow-x: auto;">
                                    <table class="table-modern" style="font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>Nº Amostras</th>
                                                <th style="text-align: right;">Média</th>
                                                <th style="text-align: right;">Desvio-Padrão</th>
                                                <th style="text-align: right;">Lim. Inferior</th>
                                                <th style="text-align: right;">Lim. Superior</th>
                                                <th style="text-align: center;">Críticas</th>
                                                <th style="text-align: center;">Expurgadas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td id="juizo-num-amostras" style="font-weight: 700; color: var(--text-primary); font-size: 13px;">0</td>
                                                <td id="juizo-media" style="text-align: right; font-weight: 700; color: var(--primary-color); font-size: 13px;">R$ 0,00</td>
                                                <td id="juizo-desvio-padrao" style="text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 12px;">0,00</td>
                                                <td id="juizo-limite-inferior" style="text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 12px;">R$ 0,00</td>
                                                <td id="juizo-limite-superior" style="text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 12px;">R$ 0,00</td>
                                                <td id="juizo-criticas" style="text-align: center; font-weight: 700; color: #dc2626; font-size: 13px;">0</td>
                                                <td id="juizo-expurgadas" style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: 13px;">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- MÉTODO ESTATÍSTICO --}}
                            <div style="margin-bottom: var(--spacing-xl);">
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                                    <i class="fas fa-calculator" style="color: var(--secondary-color); font-size: 14px;"></i>
                                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Método Estatístico Aplicado às Amostras Saneadas
                                    </h3>
                                </div>
                                <div style="overflow-x: auto;">
                                    <table class="table-modern" style="font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>Nº Válidas</th>
                                                <th style="text-align: right;">Desvio-Padrão</th>
                                                <th style="text-align: right;">Coef. Variação</th>
                                                <th style="text-align: right;">Menor Preço</th>
                                                <th style="text-align: right;">Média</th>
                                                <th style="text-align: right;">Mediana</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td id="metodo-num-validas" style="font-weight: 700; color: var(--text-primary); font-size: 13px;">0</td>
                                                <td id="metodo-desvio" style="text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 12px;">0,00</td>
                                                <td id="metodo-coef-variacao" style="text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 12px;">0,00%</td>
                                                <td id="metodo-menor" style="text-align: right; font-weight: 700; color: var(--secondary-color); font-size: 13px;">R$ 0,00</td>
                                                <td id="metodo-media" style="text-align: right; font-weight: 700; color: var(--primary-color); font-size: 13px;">R$ 0,00</td>
                                                <td id="metodo-mediana" style="text-align: right; font-weight: 700; color: #8b5cf6; font-size: 13px;">R$ 0,00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- SÉRIE DE PREÇOS COLETADOS --}}
                            <div style="margin-bottom: var(--spacing-xl);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--spacing-md);">
                                    <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                        <i class="fas fa-list-ol" style="color: #f59e0b; font-size: 14px;"></i>
                                        <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Série de Preços Coletados
                                        </h3>
                                    </div>
                                    <span id="contador-amostras-serie" class="badge-modern badge-modern-info">0 amostras</span>
                                </div>

                                <div id="tbody-serie-precos" style="max-height: 420px; overflow-y: auto; background: var(--bg-light); padding: var(--spacing-md); border-radius: var(--border-radius-sm); border: 2px solid var(--border-color); display: flex; flex-direction: column; gap: var(--spacing-md);">
                                    {{-- Estado vazio --}}
                                    <div class="loading-modern">
                                        <i class="fas fa-inbox" style="font-size: 42px; opacity: 0.2; color: var(--text-muted);"></i>
                                        <p style="margin: var(--spacing-sm) 0 0 0; font-size: 14px; font-weight: 600; color: var(--text-secondary);">Nenhuma amostra selecionada</p>
                                        <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">Marque os checkboxes acima para adicionar amostras</p>
                                    </div>
                                </div>
                            </div>

                            {{-- RESULTADO FINAL - PREÇO DE REFERÊNCIA --}}
                            <div style="margin-bottom: var(--spacing-lg);">
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                                    <i class="fas fa-award" style="color: #8b5cf6; font-size: 14px;"></i>
                                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Resultado Final - Preço de Referência
                                    </h3>
                                </div>
                                <div style="background: linear-gradient(135deg, var(--bg-light) 0%, #ffffff 100%); padding: var(--spacing-lg); border-radius: var(--border-radius-md); border: 2px solid var(--border-color); box-shadow: var(--shadow-md);">
                                    <table class="table-modern" style="font-size: 11px;">
                                        <thead>
                                            <tr style="background: transparent;">
                                                <th style="text-align: center;">Mediana</th>
                                                <th style="text-align: center;">Média Recomendada</th>
                                                <th style="text-align: center;">Menor Preço</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr style="background: transparent;">
                                                <td id="resumo-mediana" style="padding: 16px 12px; text-align: center; font-weight: 700; color: #8b5cf6; font-size: 18px;">R$ 0,00</td>
                                                <td id="resumo-media" style="padding: 16px 12px; text-align: center; font-weight: 800; color: var(--primary-color); font-size: 20px;">R$ 0,00</td>
                                                <td id="resumo-menor" style="padding: 16px 12px; text-align: center; font-weight: 700; color: var(--secondary-color); font-size: 18px;">R$ 0,00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- CRÍTICA DOS DADOS --}}
                            <div>
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                                    <i class="fas fa-clipboard-check" style="color: #3b82f6; font-size: 14px;"></i>
                                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Crítica dos Dados
                                    </h3>
                                </div>
                                <div style="background: var(--bg-light); padding: var(--spacing-md); border-radius: var(--border-radius-sm); border-left: 4px solid #3b82f6;">
                                    <p style="margin: 0 0 var(--spacing-md) 0; font-size: 13px; color: var(--text-primary); line-height: 1.7; font-weight: 500;">
                                        Análise estatística concluída. Os dados foram tratados conforme metodologia estabelecida, com expurgo de amostras discrepantes.
                                    </p>
                                    <button type="button" onclick="abrirModalJustificativa()" class="btn-modern btn-modern-secondary" style="padding: 10px 16px; font-size: 12px;">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>ADICIONAR JUSTIFICATIVA (OPCIONAL)</span>
                                    </button>

                                    {{-- Área para exibir justificativa quando preenchida --}}
                                    <div id="area-justificativa-exibicao" style="display: none; background: white; border-left: 4px solid var(--primary-color); padding: var(--spacing-md); border-radius: var(--border-radius-sm); margin-top: var(--spacing-md); box-shadow: var(--shadow-sm);">
                                        <div style="display: flex; align-items: start; gap: var(--spacing-sm);">
                                            <i class="fas fa-comment-dots" style="color: var(--primary-color); font-size: 14px; margin-top: 2px;"></i>
                                            <div style="flex: 1;">
                                                <p style="margin: 0 0 6px 0; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                                                    Justificativa da Cotação:
                                                </p>
                                                <p id="texto-justificativa-exibicao" style="margin: 0; font-size: 13px; color: var(--text-primary); line-height: 1.6; white-space: pre-wrap;">
                                                    {{-- Preenchido via JavaScript --}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- ========================================
                 RODAPÉ
                 ======================================== --}}
            <div class="modal-footer" style="background: #f3f4f6; border-top: 2px solid #d1d5db; padding: 12px 28px; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 9px 20px; font-size: 12px; font-weight: 700; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.3px;">
                    <i class="fas fa-times-circle"></i> FECHAR
                </button>
                <button type="button" id="btn-concluir-cotacao" class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; padding: 9px 20px; font-size: 12px; font-weight: 700; border-radius: 4px; box-shadow: 0 2px 6px rgba(59,130,246,0.4); text-transform: uppercase; letter-spacing: 0.3px;">
                    <i class="fas fa-check-double"></i> CONCLUIR COTAÇÃO E FECHAR JANELA
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ========================================
     ESTILOS CSS PERSONALIZADOS
     ======================================== --}}
<style>
/* Abas de Pesquisa */
.tab-pesquisa {
    transition: all 0.25s ease;
}
.tab-pesquisa:hover {
    background: rgba(255,255,255,0.5) !important;
    transform: translateY(-1px);
}
.tab-pesquisa.active {
    background: white !important;
    color: #1f2937 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Scrollbars Personalizadas */
#container-tabela-resultados::-webkit-scrollbar,
#container-filtro-unidades::-webkit-scrollbar,
#container-filtro-marcas::-webkit-scrollbar,
#container-filtro-ufs::-webkit-scrollbar,
#container-filtro-origens::-webkit-scrollbar,
#tbody-serie-precos::-webkit-scrollbar {
    width: 7px;
    height: 7px;
}
#container-tabela-resultados::-webkit-scrollbar-track,
#container-filtro-unidades::-webkit-scrollbar-track,
#container-filtro-marcas::-webkit-scrollbar-track,
#container-filtro-ufs::-webkit-scrollbar-track,
#container-filtro-origens::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}
#container-tabela-resultados::-webkit-scrollbar-thumb,
#container-filtro-unidades::-webkit-scrollbar-thumb,
#container-filtro-marcas::-webkit-scrollbar-thumb,
#container-filtro-ufs::-webkit-scrollbar-thumb,
#container-filtro-origens::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 4px;
}
#container-tabela-resultados::-webkit-scrollbar-thumb:hover,
#container-filtro-unidades::-webkit-scrollbar-thumb:hover,
#container-filtro-marcas::-webkit-scrollbar-thumb:hover,
#container-filtro-ufs::-webkit-scrollbar-thumb:hover,
#container-filtro-origens::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Tabela de Resultados - Linhas Zebradas e Hover */
#tbody-resultados-pesquisa tr:nth-child(even) {
    background: #fafafa !important;
}
#tbody-resultados-pesquisa tr:hover {
    background: #eff6ff !important;
    transition: background 0.15s ease;
}

/* Highlight de termos de busca */
.termo-destacado {
    background: #fef08a;
    font-weight: 800;
    padding: 1px 4px;
    border-radius: 2px;
    color: #1f2937;
}

/* Badges */
.badge-fonte {
    display: inline-block;
    background: #dc2626;
    color: white;
    padding: 3px 9px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-top: 4px;
}

.badge-expurgado {
    display: inline-block;
    background: #dc2626;
    color: white;
    padding: 4px 11px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-valido {
    display: inline-block;
    background: #10b981;
    color: white;
    padding: 4px 11px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Botões de ação na tabela */
.btn-acao-tabela {
    padding: 5px 9px;
    border: none;
    border-radius: 3px;
    font-size: 9px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    margin: 0 2px;
}

.btn-acao-tabela:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

/* Ajuste de unidade - linha amarela */
.linha-ajuste-unidade {
    background: #fef3c7 !important;
    border-left: 3px solid #f59e0b !important;
}

/* Responsividade */
@media (max-width: 1400px) {
    #stats-cards {
        font-size: 9px;
    }
    #stats-cards > div > div:first-child {
        font-size: 7px;
    }
    #stats-cards > div > div:last-child {
        font-size: 13px;
    }
}

/* Animações suaves */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animated-fade-in {
    animation: fadeIn 0.3s ease-out;
}
</style>

{{-- ================================================
     MODAL: DETALHES DA FONTE CONSULTADA
     ================================================ --}}
<div class="modal fade" id="modalDetalhesFonte" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Cabeçalho --}}
            <div class="modal-header" style="background: linear-gradient(135deg, #426a94 0%, #2d4f73 100%); color: white; padding: 14px 20px;">
                <h5 class="modal-title" style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
                    DETALHES DA FONTE CONSULTADA
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            {{-- Corpo --}}
            <div class="modal-body" style="padding: 24px; background: #f9fafb;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tbody>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; width: 180px;">Fonte:</td>
                            <td id="detalhe-fonte" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Identificação:</td>
                            <td id="detalhe-identificacao" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Nº do Pregão:</td>
                            <td id="detalhe-pregao" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Nº da Ata:</td>
                            <td id="detalhe-ata" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Data/Homologação:</td>
                            <td id="detalhe-data-homologacao" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Órgão:</td>
                            <td id="detalhe-orgao" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Objeto:</td>
                            <td id="detalhe-objeto" style="padding: 10px 12px; color: #1f2937; line-height: 1.5;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Lote/Item/Subitem:</td>
                            <td id="detalhe-lote-item" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Vencedor:</td>
                            <td id="detalhe-vencedor" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Descrição:</td>
                            <td id="detalhe-descricao" style="padding: 10px 12px; color: #1f2937; line-height: 1.5;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Marca:</td>
                            <td id="detalhe-marca" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Unidade:</td>
                            <td id="detalhe-unidade" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Quantidade:</td>
                            <td id="detalhe-quantidade" style="padding: 10px 12px; color: #1f2937;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #6b7280; text-transform: uppercase;">Valor Unitário:</td>
                            <td id="detalhe-valor-unitario" style="padding: 10px 12px; color: #1f2937; font-weight: 700; font-size: 13px;">-</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Botão Download da ARP (se disponível) --}}
                <div id="container-download-arp" style="margin-top: 20px; display: none;">
                    <a id="link-download-arp" href="#" target="_blank" class="btn btn-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-download"></i> DOWNLOAD DA ARP
                    </a>
                </div>
            </div>

            {{-- Rodapé --}}
            <div class="modal-footer" style="background: #f3f4f6; padding: 12px 20px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 16px; font-size: 11px; font-weight: 600;">
                    <i class="fas fa-times"></i> FECHAR
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ================================================
     MODAL: AJUSTE DE EMBALAGEM
     ================================================ --}}
<div class="modal fade" id="modalAjusteEmbalagem" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Cabeçalho --}}
            <div class="modal-header" style="background: linear-gradient(135deg, #426a94 0%, #2d4f73 100%); color: white; padding: 14px 20px;">
                <h5 class="modal-title" style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
                    AJUSTE DE EMBALAGEM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            {{-- Corpo --}}
            <div class="modal-body" style="padding: 24px; background: #f9fafb;">

                {{-- Descrição da Amostra --}}
                <div style="margin-bottom: 20px; background: white; padding: 14px; border-radius: 6px; border-left: 3px solid #3b82f6;">
                    <label style="display: block; font-size: 10px; font-weight: 700; color: #6b7280; margin-bottom: 6px; text-transform: uppercase;">
                        Descrição da Amostra:
                    </label>
                    <p id="ajuste-descricao-amostra" style="margin: 0; font-size: 11px; color: #1f2937; line-height: 1.5;">-</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

                    {{-- MEDIDA DE FORNECIMENTO ORIGINAL --}}
                    <div style="background: #e5e7eb; padding: 16px; border-radius: 6px;">
                        <h6 style="font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Medida de Fornecimento Original
                        </h6>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 10px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                Med. de Fornecimento:
                            </label>
                            <input type="text" id="ajuste-unidade-original" readonly style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; background: white; color: #1f2937; font-weight: 600;">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 10px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                A embalagem é:
                            </label>
                            <select id="ajuste-tipo-embalagem-original" disabled style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 11px; background: white; color: #6b7280; font-weight: 600; cursor: not-allowed;">
                                <option value="PRIMARIA" selected>PRIMÁRIA</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 10px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                Preço Unitário Original:
                            </label>
                            <input type="text" id="ajuste-preco-original" readonly style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; background: white; color: #059669; font-weight: 700;">
                        </div>
                    </div>

                    {{-- MEDIDA DE FORNECIMENTO DESEJADA --}}
                    <div style="background: #dbeafe; padding: 16px; border-radius: 6px;">
                        <h6 style="font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Medida de Fornecimento Desejada
                        </h6>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 10px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                Medida Desejada:
                            </label>
                            <select id="ajuste-medida-desejada" style="width: 100%; padding: 8px 12px; border: 1px solid #3b82f6; border-radius: 4px; font-size: 11px; background: white; color: #1f2937; font-weight: 600; cursor: pointer;">
                                <option value="">UNIDADE</option>
                                <option value="UN">UN - UNIDADE</option>
                                <option value="CX">CX - CAIXA</option>
                                <option value="PCT">PCT - PACOTE</option>
                                <option value="KG">KG - QUILOGRAMA</option>
                                <option value="LT">LT - LITRO</option>
                                <option value="MT">MT - METRO</option>
                                <option value="M2">M² - METRO QUADRADO</option>
                                <option value="M3">M³ - METRO CÚBICO</option>
                                <option value="DUZIA">DÚZIA</option>
                                <option value="CENTENA">CENTENA</option>
                                <option value="MILHEIRO">MILHEIRO</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 10px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                Essa embalagem é:
                            </label>
                            <select id="ajuste-tipo-embalagem-desejada" style="width: 100%; padding: 8px 12px; border: 1px solid #3b82f6; border-radius: 4px; font-size: 11px; background: white; color: #1f2937; font-weight: 600; cursor: pointer;">
                                <option value="SECUNDARIA">SECUNDÁRIA</option>
                                <option value="PRIMARIA">PRIMÁRIA</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 10px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                Fator de <strong>MULTIPLICAÇÃO</strong>:
                            </label>
                            <input type="number" id="ajuste-fator-multiplicacao" placeholder="0,00" step="0.01" min="0" style="width: 100%; padding: 8px 12px; border: 2px solid #3b82f6; border-radius: 4px; font-size: 12px; background: white; color: #1f2937; font-weight: 700;">
                            <small style="display: block; margin-top: 4px; font-size: 9px; color: #6b7280;">
                                Ex: Se a embalagem contém 100 unidades, digite <strong>100</strong>
                            </small>
                        </div>
                    </div>

                </div>

                {{-- Resultado do Ajuste --}}
                <div id="resultado-ajuste" style="margin-top: 20px; background: #ecfdf5; border: 2px solid #10b981; padding: 16px; border-radius: 6px; display: none;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <i class="fas fa-check-circle" style="color: #10b981; font-size: 16px;"></i>
                        <h6 style="font-size: 11px; font-weight: 700; color: #065f46; margin: 0; text-transform: uppercase;">
                            Preço Unitário Ajustado
                        </h6>
                    </div>
                    <p id="resultado-preco-ajustado" style="margin: 0; font-size: 20px; color: #059669; font-weight: 800;">
                        R$ 0,00
                    </p>
                </div>

            </div>

            {{-- Rodapé --}}
            <div class="modal-footer" style="background: #f3f4f6; padding: 12px 20px; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 16px; font-size: 11px; font-weight: 600;">
                    <i class="fas fa-times"></i> FECHAR
                </button>
                <button type="button" id="btn-concluir-ajuste" class="btn btn-success" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; padding: 8px 16px; font-size: 11px; font-weight: 700;">
                    <i class="fas fa-check"></i> CONCLUIR
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ================================================
     MODAL DE DETALHES DA AMOSTRA
     ================================================ --}}
<div class="modal fade" id="modalDetalhesAmostra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; padding: 14px 20px;">
                <h5 class="modal-title" style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
                    <i class="fas fa-info-circle"></i> DETALHES DA AMOSTRA
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" style="padding: 20px; background: #f9fafb; max-height: 70vh; overflow-y: auto;">

                {{-- Seção 1: Informações do Item --}}
                <div style="margin-bottom: 20px;">
                    <h6 style="font-size: 12px; font-weight: 700; color: #1f2937; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                        <i class="fas fa-box" style="color: #3b82f6;"></i> Informações do Item
                    </h6>
                    <div style="background: white; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #3b82f6;">
                        <div class="row g-2">
                            <div class="col-12">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Descrição:</small>
                                <p id="detalhe-descricao" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937; font-weight: 500;"></p>
                            </div>
                            <div class="col-4">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Marca:</small>
                                <p id="detalhe-marca" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-4">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Unidade:</small>
                                <p id="detalhe-unidade" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-4">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Quantidade:</small>
                                <p id="detalhe-quantidade" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção 2: Valores --}}
                <div style="margin-bottom: 20px;">
                    <h6 style="font-size: 12px; font-weight: 700; color: #1f2937; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                        <i class="fas fa-dollar-sign" style="color: #10b981;"></i> Valores
                    </h6>
                    <div id="detalhe-valores-container" style="background: white; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #10b981;">
                        {{-- Preenchido dinamicamente --}}
                    </div>
                </div>

                {{-- Seção 3: Origem da Amostra --}}
                <div style="margin-bottom: 20px;">
                    <h6 style="font-size: 12px; font-weight: 700; color: #1f2937; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                        <i class="fas fa-building" style="color: #f59e0b;"></i> Origem da Amostra
                    </h6>
                    <div style="background: white; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #f59e0b;">
                        <div class="row g-2">
                            <div class="col-12">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Órgão:</small>
                                <p id="detalhe-orgao" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937; font-weight: 500;"></p>
                            </div>
                            <div class="col-6">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Código:</small>
                                <p id="detalhe-codigo-orgao" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-6">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">CNPJ:</small>
                                <p id="detalhe-cnpj" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção 4: Dados da Contratação --}}
                <div style="margin-bottom: 20px;">
                    <h6 style="font-size: 12px; font-weight: 700; color: #1f2937; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                        <i class="fas fa-file-contract" style="color: #8b5cf6;"></i> Dados da Contratação
                    </h6>
                    <div style="background: white; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #8b5cf6;">
                        <div class="row g-2">
                            <div class="col-6">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Número:</small>
                                <p id="detalhe-numero" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-6">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Modalidade:</small>
                                <p id="detalhe-modalidade" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-6">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Data Vigência:</small>
                                <p id="detalhe-data" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-6">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Número Item:</small>
                                <p id="detalhe-numero-item" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção 5: Localização --}}
                <div style="margin-bottom: 20px;">
                    <h6 style="font-size: 12px; font-weight: 700; color: #1f2937; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                        <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> Localização
                    </h6>
                    <div style="background: white; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #ef4444;">
                        <div class="row g-2">
                            <div class="col-9">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Município:</small>
                                <p id="detalhe-municipio" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-3">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">UF:</small>
                                <p id="detalhe-uf" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção 6: Classificação --}}
                <div id="detalhe-classificacao-container" style="margin-bottom: 0; display: none;">
                    <h6 style="font-size: 12px; font-weight: 700; color: #1f2937; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                        <i class="fas fa-tag" style="color: #06b6d4;"></i> Classificação
                    </h6>
                    <div style="background: white; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #06b6d4;">
                        <div class="row g-2">
                            <div class="col-8">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Código CATMAT:</small>
                                <p id="detalhe-catmat" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                            <div class="col-4">
                                <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">PDM Tipo:</small>
                                <p id="detalhe-pdm" style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;"></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="background: #f3f4f6; padding: 12px 20px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 16px; font-size: 11px; font-weight: 600;">
                    <i class="fas fa-times"></i> FECHAR
                </button>
            </div>
        </div>
    </div>
</div>
