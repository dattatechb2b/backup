@extends('layouts.app')

@section('title', 'Pesquisa Rápida')

@section('content')
<div style="padding: 25px;">
    <!-- Título da Página -->
    <h1 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 25px;">
        PESQUISA RÁPIDA
    </h1>

    <!-- SEÇÃO 1: ARGUMENTO DE PESQUISA -->
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fas fa-search" style="color: #6b7280;"></i>
            <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                ARGUMENTO DE PESQUISA
            </h2>
        </div>

        <!-- Abas de Pesquisa -->
        <div style="margin-bottom: 20px;">
            <div style="border-bottom: 2px solid #e5e7eb; margin-bottom: 20px;">
                <button type="button" class="tab-btn active" data-tab="palavra-chave" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid #3b82f6; color: #3b82f6; font-weight: 600; font-size: 13px; cursor: pointer; margin-right: 10px;">
                    PESQUISAR POR PALAVRA-CHAVE
                </button>
                <button type="button" class="tab-btn" data-tab="catmat" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid transparent; color: #6b7280; font-weight: 600; font-size: 13px; cursor: pointer;">
                    PESQUISAR POR CATMAT/CATSER
                </button>
            </div>
        </div>

        <!-- Conteúdo Aba 1: Palavra-chave -->
        <div id="tab-palavra-chave" class="tab-content">
            <div style="background: white; padding: 20px; border-radius: 6px;">
                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            Parte da descrição técnica ou genérica do produto:
                        </label>
                        <input type="text" id="descricao_busca" placeholder="Digite o que você procura aqui" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            CNPJ:
                        </label>
                        <input type="text" id="cnpj_busca" placeholder="99.999.999/9999-99" maxlength="18" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                    </div>
                </div>

                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; cursor: pointer;">
                        <input type="radio" name="tipo_busca" value="contratos" checked style="width: 16px; height: 16px; cursor: pointer;">
                        Contratos ou palavras
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; cursor: pointer;">
                        <input type="radio" name="tipo_busca" value="expressao" style="width: 16px; height: 16px; cursor: pointer;">
                        Expressão exata
                    </label>
                </div>

                <div style="display: flex; gap: 15px; align-items: center;">
                    <button type="button" id="btn-pesquisar" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-search"></i>
                        PESQUISAR
                    </button>
                    <button type="button" id="btn-criar-orcamento" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px;">
                        <i class="fas fa-file-invoice-dollar"></i>
                        CRIAR ORÇAMENTO COM SELECIONADOS (<span id="contador-selecionados">0</span>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Conteúdo Aba 2: CATMAT/CATSER -->
        <div id="tab-catmat" class="tab-content" style="display: none;">
            <div style="background: white; padding: 20px; border-radius: 6px;">
                <div style="display: grid; grid-template-columns: 300px 1fr auto; gap: 20px; align-items: end; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            Código:
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="codigo_catmat" placeholder="Digite o código" style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <button type="button" id="btn-buscar-codigo" style="background: #9ca3af; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            Descrição:
                        </label>
                        <input type="text" id="descricao_catmat" placeholder="Descrição do item" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <button type="button" id="btn-procurar-catmat" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-search"></i>
                            PROCURAR CATMAT/CATSER
                        </button>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; align-items: center;">
                    <button type="button" id="btn-pesquisar-catmat" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-search"></i>
                        PESQUISAR
                    </button>
                    <button type="button" id="btn-criar-orcamento-catmat" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px;">
                        <i class="fas fa-file-invoice-dollar"></i>
                        CRIAR ORÇAMENTO COM SELECIONADOS (<span id="contador-selecionados-catmat">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout 2 Colunas: Filtros (Esquerda) + Resultados (Direita) -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px;">

        <!-- SEÇÃO 2: REFINAR (Esquerda) -->
        <div>
            <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; max-height: calc(100vh - 200px); overflow-y: auto;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <i class="fas fa-filter" style="color: #6b7280;"></i>
                    <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                        REFINAR RESULTADOS
                    </h2>
                </div>

                <!-- ========== FILTROS BÁSICOS ========== -->

                <!-- Filtro de Período (FIXO em 12 meses) -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-calendar-alt" style="margin-right: 4px;"></i>
                        PERÍODO DE ANÁLISE
                    </label>
                    <div style="padding: 10px; background: #eff6ff; border: 1px solid #3b82f6; border-radius: 5px; font-size: 11px; color: #1e40af; text-align: center; font-weight: 600;">
                        <i class="fas fa-info-circle"></i> Últimos 12 meses
                    </div>
                    <input type="hidden" id="filtro-periodo" value="12">
                </div>

                <!-- Filtro de Confiabilidade -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-shield-alt" style="margin-right: 4px;"></i>
                        CONFIABILIDADE
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-confiabilidade" value="alta" checked style="width: 14px; height: 14px; cursor: pointer;">
                            🟢 Alta Confiança
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-confiabilidade" value="media" checked style="width: 14px; height: 14px; cursor: pointer;">
                            🟡 Estimado
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-confiabilidade" value="baixa" checked style="width: 14px; height: 14px; cursor: pointer;">
                            🔴 Valor Global
                        </label>
                    </div>
                </div>

                <!-- Filtro de Faixa de Preço -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-dollar-sign" style="margin-right: 4px;"></i>
                        FAIXA DE PREÇO
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                        <div>
                            <label style="font-size: 9px; color: #6b7280; margin-bottom: 3px; display: block;">Mínimo (R$)</label>
                            <input type="number" id="preco-minimo" placeholder="0" min="0" step="0.01" style="width: 100%; padding: 5px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 10px;">
                        </div>
                        <div>
                            <label style="font-size: 9px; color: #6b7280; margin-bottom: 3px; display: block;">Máximo (R$)</label>
                            <input type="number" id="preco-maximo" placeholder="∞" min="0" step="0.01" style="width: 100%; padding: 5px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 10px;">
                        </div>
                    </div>
                </div>

                <!-- Divisor -->
                <div style="border-top: 1px solid #e5e7eb; margin: 15px 0;"></div>

                <!-- ========== FILTROS GEOGRÁFICOS ========== -->

                <!-- Filtro de Região -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-map" style="margin-right: 4px;"></i>
                        REGIÃO
                    </label>
                    <select id="filtro-regiao" style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151;">
                        <option value="">Todas as regiões</option>
                        <option value="norte">Norte</option>
                        <option value="nordeste">Nordeste</option>
                        <option value="centro-oeste">Centro-Oeste</option>
                        <option value="sudeste">Sudeste</option>
                        <option value="sul">Sul</option>
                    </select>
                </div>

                <!-- Filtro de UF (Estado) -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 4px;"></i>
                        ESTADO (UF)
                    </label>
                    <select id="filtro-uf" style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151;">
                        <option value="">Todos os estados</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>

                <!-- Divisor -->
                <div style="border-top: 1px solid #e5e7eb; margin: 15px 0;"></div>

                <!-- ========== FILTROS DE ENTIDADE ========== -->

                <!-- Filtro de Órgão -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-building" style="margin-right: 4px;"></i>
                        ÓRGÃO
                    </label>
                    <input type="text" id="filtro-orgao" placeholder="Digite parte do nome..." style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px;">
                    <div id="lista-orgaos" style="max-height: 150px; overflow-y: auto; background: white; border: 1px solid #d1d5db; border-radius: 5px; margin-top: 5px; display: none;"></div>
                </div>

                <!-- Filtro de Porte da Empresa -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-industry" style="margin-right: 4px;"></i>
                        PORTE DA EMPRESA
                    </label>
                    <select id="filtro-porte" style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151;">
                        <option value="">Todas</option>
                        <option value="me_epp">ME/EPP</option>
                        <option value="media">Média</option>
                        <option value="grande">Grande</option>
                    </select>
                </div>

                <!-- Divisor -->
                <div style="border-top: 1px solid #e5e7eb; margin: 15px 0;"></div>

                <!-- ========== FILTROS DE PRODUTO ========== -->

                <!-- Filtro de Unidade de Medida -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-ruler" style="margin-right: 4px;"></i>
                        MEDIDA DE FORNECIMENTO
                    </label>
                    <select id="filtro-unidade" style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151;">
                        <option value="">Todas</option>
                        <option value="UNIDAD">UNIDAD</option>
                        <option value="CX">CX</option>
                        <option value="UN">UN</option>
                        <option value="PEÇA">PEÇA</option>
                        <option value="UNIDADE">UNIDADE</option>
                        <option value="PEÇ">PEÇ</option>
                        <option value="CAIXA">CAIXA</option>
                        <option value="KG">KG</option>
                        <option value="LITRO">LITRO</option>
                        <option value="METRO">METRO</option>
                    </select>
                </div>

                <!-- Filtro de Marca -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-tag" style="margin-right: 4px;"></i>
                        MARCA
                    </label>
                    <input type="text" id="filtro-marca" placeholder="Digite a marca..." style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px;">
                </div>

                <!-- Filtro de Origem das Amostras -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-database" style="margin-right: 4px;"></i>
                        ORIGEM DAS AMOSTRAS
                    </label>
                    <select id="filtro-origem" style="width: 100%; padding: 7px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151;">
                        <option value="">Todas as origens</option>
                        <optgroup label="APIs Governamentais">
                            <option value="PNCP">PNCP - Portal Nacional de Contratações</option>
                            <option value="BANCO_PRECOS">Banco de Preços (Gov.br)</option>
                            <option value="COMPRASNET">ComprasNet (Antigo SIASG)</option>
                        </optgroup>
                        <optgroup label="Dados Locais">
                            <option value="LOCAL">Orçamentos Locais</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Divisor -->
                <div style="border-top: 2px solid #e5e7eb; margin: 15px 0;"></div>

                <!-- Botão Aplicar Filtros -->
                <button id="btn-aplicar-filtros" style="width: 100%; padding: 10px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; margin-bottom: 10px;">
                    <i class="fas fa-check"></i> APLICAR FILTROS
                </button>

                <!-- Botão Limpar Filtros -->
                <button id="btn-limpar-filtros" style="width: 100%; padding: 8px; background: #6b7280; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-times"></i> LIMPAR FILTROS
                </button>
            </div>
        </div>

        <!-- SEÇÃO 3: RESULTADO DA PESQUISA (Direita) -->
        <div>
            <div style="background: #f3f4f6; padding: 20px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-list" style="color: #6b7280;"></i>
                        <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                            RESULTADO DA PESQUISA
                        </h2>
                        <span id="contador-resultados" style="padding: 4px 10px; background: #3b82f6; color: white; border-radius: 12px; font-size: 11px; font-weight: 700; display: none;">
                            0 itens
                        </span>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #6b7280; margin-right: 8px;">ORDENAR POR:</label>
                        <select id="ordenar" style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; color: #374151;">
                            <option value="confiabilidade">CONFIABILIDADE</option>
                            <option value="menor_preco">MENOR PREÇO MÉDIO</option>
                            <option value="maior_preco">MAIOR PREÇO MÉDIO</option>
                            <option value="amostras">MAIS AMOSTRAS</option>
                        </select>
                    </div>
                </div>

                <!-- Área de Resultados -->
                <div id="area-resultados" style="background: white; padding: 30px; border-radius: 6px; text-align: center; min-height: 200px;">
                    <i class="fas fa-search" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <p style="color: #9ca3af; font-size: 14px; margin: 0;">
                        Preencha os campos acima e clique em <strong>PESQUISAR</strong> para visualizar os resultados.
                    </p>
                </div>
            </div>
    </div>
</div>

<!-- JavaScript para controle de abas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Controle de abas
    const tabButtons = document.querySelectorAll('.tab-btn');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            // Remover active de todos
            tabButtons.forEach(b => {
                b.style.borderBottomColor = 'transparent';
                b.style.color = '#6b7280';
                b.classList.remove('active');
            });

            // Adicionar active no clicado
            this.style.borderBottomColor = '#3b82f6';
            this.style.color = '#3b82f6';
            this.classList.add('active');

            // Mostrar/esconder conteúdos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });

            document.getElementById('tab-' + targetTab).style.display = 'block';
        });
    });

    // Máscara CNPJ
    const cnpjInput = document.getElementById('cnpj_busca');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }

            e.target.value = value;
        });
    }

    // Função auxiliar para buscar CATMAT (reutilizável)
    async function buscarCATMAT(codigo, descricao) {
        const resultadosArea = document.getElementById('area-resultados');
        const btn = document.getElementById('btn-pesquisar-catmat');

        // Validar entrada
        if (!codigo.trim() && !descricao.trim()) {
            resultadosArea.innerHTML = `
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #fbbf24; margin-bottom: 15px;"></i>
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    Por favor, preencha ao menos um campo (código ou descrição) para pesquisar.
                </p>
            `;
            return;
        }

        // Mostrar loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PESQUISANDO...';
        resultadosArea.innerHTML = `
            <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #3b82f6; margin-bottom: 15px;"></i>
            <p style="color: #1e40af; font-size: 14px; margin: 0;">Buscando em fornecedores locais e API externa...</p>
        `;

        try {
            // Buscar no backend (local + API externa)
            const params = new URLSearchParams();
            if (codigo) params.append('codigo', codigo);
            if (descricao) params.append('descricao', descricao);

            const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/buscar-por-codigo?${params}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (result.success) {
                let html = '<div style="width: 100%;">';

                // ========================================
                // SEÇÃO 1: FORNECEDORES LOCAIS
                // ========================================
                if (result.resultados.fornecedores_locais && result.resultados.fornecedores_locais.length > 0) {
                    html += `<div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; margin-bottom: 20px;">`;
                    html += `<p style="margin: 0; font-size: 13px; color: #065f46; font-weight: 600;"><i class="fas fa-building"></i> ${result.resultados.fornecedores_locais.length} fornecedor(es) local(is) cadastrado(s)</p>`;
                    html += `</div>`;

                    result.resultados.fornecedores_locais.forEach(fornecedor => {
                        const cnpjFormatado = fornecedor.numero_documento_formatado || fornecedor.numero_documento;
                        const itemCount = fornecedor.itens ? fornecedor.itens.length : 0;

                        html += `<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 15px;">`;
                        html += `<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">`;
                        html += `<div>`;
                        html += `<h3 style="margin: 0 0 5px 0; font-size: 16px; color: #1f2937;">${fornecedor.razao_social}</h3>`;
                        if (fornecedor.nome_fantasia) {
                            html += `<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; font-style: italic;">${fornecedor.nome_fantasia}</p>`;
                        }
                        html += `<p style="margin: 0; font-size: 13px; color: #374151;"><strong>CNPJ:</strong> ${cnpjFormatado}</p>`;
                        html += `</div>`;
                        html += `<span style="background: #eff6ff; color: #1e40af; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: 600;">${itemCount} ITEM(NS)</span>`;
                        html += `</div>`;

                        // Contato
                        if (fornecedor.telefone || fornecedor.email) {
                            html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 12px;">`;
                            html += `<p style="margin: 0 0 6px 0; font-size: 12px; color: #6b7280;"><strong>Contato:</strong></p>`;
                            if (fornecedor.telefone) html += `<p style="margin: 0 0 3px 0; font-size: 12px; color: #374151;"><i class="fas fa-phone" style="width: 16px;"></i> ${fornecedor.telefone}</p>`;
                            if (fornecedor.email) html += `<p style="margin: 0; font-size: 12px; color: #374151;"><i class="fas fa-envelope" style="width: 16px;"></i> ${fornecedor.email}</p>`;
                            html += `</div>`;
                        }

                        // Itens com código CATMAT
                        if (fornecedor.itens && fornecedor.itens.length > 0) {
                            html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px;">`;
                            html += `<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; font-weight: 600;"><strong>Itens encontrados:</strong></p>`;
                            fornecedor.itens.forEach(item => {
                                html += `<div style="background: #f9fafb; padding: 10px; border-radius: 4px; border-left: 3px solid #3b82f6; margin-bottom: 8px;">`;
                                html += `<p style="margin: 0 0 4px 0; font-size: 12px; color: #1f2937; font-weight: 500;">${item.descricao}</p>`;
                                html += `<div style="display: flex; gap: 15px; font-size: 11px; color: #6b7280;">`;
                                if (item.codigo_catmat) html += `<span><strong>CATMAT:</strong> ${item.codigo_catmat}</span>`;
                                if (item.unidade) html += `<span><strong>Unidade:</strong> ${item.unidade}</span>`;
                                if (item.preco_referencia) html += `<span><strong>Preço Ref.:</strong> R$ ${parseFloat(item.preco_referencia).toFixed(2)}</span>`;
                                html += `</div></div>`;
                            });
                            html += `</div>`;
                        }
                        html += `</div>`;
                    });
                } else {
                    html += `<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">`;
                    html += `<p style="margin: 0; font-size: 13px; color: #92400e;"><i class="fas fa-info-circle"></i> Nenhum fornecedor local encontrado com este código CATMAT.</p>`;
                    html += `</div>`;
                }

                // ========================================
                // SEÇÃO 2: API EXTERNA
                // ========================================
                if (result.resultados.api_externa) {
                    html += `<div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 6px; margin-top: 20px;">`;
                    html += `<p style="margin: 0; font-size: 13px; color: #1e40af; font-weight: 600;"><i class="fas fa-globe"></i> API Externa (ComprasNet/Gov.br)</p>`;
                    html += `<p style="margin: 8px 0 0 0; font-size: 12px; color: #1e3a8a;">${result.resultados.api_externa.mensagem}</p>`;
                    html += `</div>`;
                }

                html += `</div>`;
                resultadosArea.innerHTML = html;
            } else {
                resultadosArea.innerHTML = `
                    <i class="fas fa-search" style="font-size: 48px; color: #6b7280; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p style="color: #374151; font-size: 14px; margin: 0;">
                        ${result.message || 'Nenhum resultado encontrado.'}
                    </p>
                `;
            }
        } catch (error) {
            console.error('Erro ao buscar CATMAT:', error);
            resultadosArea.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
                <p style="color: #dc2626; font-size: 14px; margin: 0;">Erro ao buscar. Tente novamente.</p>
            `;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> PESQUISAR';
        }
    }

    // Botões da aba CATMAT/CATSER
    document.getElementById('btn-buscar-codigo').addEventListener('click', function() {
        const codigo = document.getElementById('codigo_catmat').value;
        buscarCATMAT(codigo, '');
    });

    document.getElementById('btn-procurar-catmat').addEventListener('click', function() {
        alert('Funcionalidade de procurar CATMAT/CATSER em desenvolvimento');
    });

    document.getElementById('btn-pesquisar-catmat').addEventListener('click', function() {
        const codigo = document.getElementById('codigo_catmat').value;
        const descricao = document.getElementById('descricao_catmat').value;
        buscarCATMAT(codigo, descricao);
    });

    // Enter no campo código
    document.getElementById('codigo_catmat').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarCATMAT(this.value, '');
        }
    });

    // Botão Pesquisar aba palavra-chave - Integrado com MULTI-FONTES
    document.getElementById('btn-pesquisar').addEventListener('click', async function() {
        const descricao = document.getElementById('descricao_busca').value;
        const resultadosArea = document.getElementById('area-resultados');

        if (!descricao.trim()) {
            resultadosArea.innerHTML = `
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #fbbf24; margin-bottom: 15px;"></i>
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    Por favor, preencha o campo de busca para pesquisar.
                </p>
            `;
            return;
        }

        // Mostrar loading
        resultadosArea.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px;">
                <div style="width: 48px; height: 48px; border: 4px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="color: #6b7280; font-size: 14px; margin-top: 15px;">
                    Buscando no banco de dados...
                </p>
            </div>
            <style>
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>
        `;

        try {
            // Construir URL (considerando proxy)
            const urlBase = `${window.APP_BASE_PATH}/pesquisa/buscar`;

            const response = await fetch(`${urlBase}?termo=${encodeURIComponent(descricao)}`);
            const data = await response.json();

            // Armazenar fontes consultadas
            window.fontesConsultadas = data.fontes_consultadas || 'PNCP, Banco de Preços, ComprasNet, Orçamentos Locais';

            if (!data.success || data.resultados.length === 0) {
                resultadosArea.innerHTML = `
                    <i class="fas fa-search" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <p style="color: #6b7280; font-size: 14px; margin: 0;">
                        Nenhum resultado encontrado para "<strong>${descricao}</strong>".
                    </p>
                    <p style="color: #9ca3af; font-size: 12px; margin-top: 10px;">
                        Tente buscar com termos diferentes ou mais genéricos.
                    </p>
                `;
                return;
            }

            // Exibir resultados (com aviso se for fonte local)
            exibirResultados(data.resultados, descricao, data.fonte, data.aviso);

        } catch (error) {
            console.error('Erro ao buscar:', error);
            resultadosArea.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
                <p style="color: #dc2626; font-size: 14px; margin: 0;">
                    Erro ao buscar. Tente novamente.
                </p>
            `;
        }
    });

    // Função para exibir resultados formatados
    function exibirResultados(resultados, termoBusca, fonte = 'PNCP', aviso = null) {
        const resultadosArea = document.getElementById('area-resultados');

        // FILTRAR valores zerados ANTES de processar
        resultados = resultados.filter(r => {
            const preco = r.valor_homologado_item || r.valor_unitario || r.valor_global || 0;
            return preco > 0;
        });

        // Calcular estatísticas gerais (dados INDIVIDUAIS do backend)
        let totalAmostras = resultados.length;
        let todosPrecos = []; // Para cálculo da mediana e média
        let precoMin = Number.MAX_VALUE;
        let precoMax = 0;

        resultados.forEach(r => {
            const preco = r.valor_homologado_item || r.valor_unitario || r.valor_global || 0;
            if (preco > 0) {
                todosPrecos.push(preco);
                precoMin = Math.min(precoMin, preco);
                precoMax = Math.max(precoMax, preco);
            }
        });

        // Se não houver preços válidos, zerar
        if (todosPrecos.length === 0) {
            precoMin = 0;
            precoMax = 0;
        }

        // Calcular média
        const somaPrecos = todosPrecos.reduce((acc, p) => acc + p, 0);
        const precoMedio = todosPrecos.length > 0 ? somaPrecos / todosPrecos.length : 0;

        // Calcular MEDIANA
        let mediana = 0;
        if (todosPrecos.length > 0) {
            todosPrecos.sort((a, b) => a - b);
            const meio = Math.floor(todosPrecos.length / 2);
            if (todosPrecos.length % 2 === 0) {
                mediana = (todosPrecos[meio - 1] + todosPrecos[meio]) / 2;
            } else {
                mediana = todosPrecos[meio];
            }
        }

        // HTML dos resultados
        let html = `
            <!-- Estatísticas Compactas -->
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; padding: 12px 16px; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="text-align: center; padding: 8px; background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; letter-spacing: 0.5px;">
                        QUANTIDADE DE AMOSTRAS
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #1f2937;">
                        ${totalAmostras.toLocaleString('pt-BR')}
                    </div>
                </div>
                <div style="text-align: center; padding: 8px; background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; letter-spacing: 0.5px;">
                        PREÇO MÍNIMO
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #10b981;">
                        R$ ${precoMin.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
                <div style="text-align: center; padding: 8px; background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; letter-spacing: 0.5px;">
                        PREÇO MÁXIMO
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #ef4444;">
                        R$ ${precoMax.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
                <div style="text-align: center; padding: 8px; background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; letter-spacing: 0.5px;">
                        MÉDIA DE PREÇOS
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #3b82f6;">
                        R$ ${precoMedio.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
                <div style="text-align: center; padding: 8px; background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <div style="font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; letter-spacing: 0.5px;">
                        MEDIANA
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #8b5cf6;">
                        R$ ${mediana.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
            </div>

            <!-- Tabela de Resultados -->
            <div style="overflow-x: auto;">
                <table id="tabela-resultados" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead style="background: #f9fafb;">
                        <tr>
                            <th style="padding: 10px; text-align: center; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb; width: 40px;">
                                <input type="checkbox" id="selecionar-todos" style="width: 16px; height: 16px; cursor: pointer;" title="Selecionar todos">
                            </th>
                            <th style="padding: 10px; text-align: left; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb;">PRODUTO/SERVIÇO</th>
                            <th style="padding: 10px; text-align: left; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb;">ONDE/FONTE</th>
                            <th style="padding: 10px; text-align: center; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb;">AMOSTRAS</th>
                            <th style="padding: 10px; text-align: right; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb;">PREÇO MÉDIO</th>
                            <th style="padding: 10px; text-align: right; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb;">PREÇO MÍN</th>
                            <th style="padding: 10px; text-align: right; font-weight: 600; color: #6b7280; border-bottom: 2px solid #e5e7eb;">PREÇO MÁX</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        resultados.forEach((resultado, index) => {
            // Highlighting da palavra buscada no objeto_contrato ou descricao (CMED)
            const descricao = resultado.descricao || resultado.objeto_contrato || 'Sem descrição';
            const descHighlight = descricao.replace(
                new RegExp(`(${termoBusca})`, 'gi'),
                '<mark style="background: #fef08a; padding: 2px 4px;">$1</mark>'
            );

            // Badge de confiabilidade
            const confiabilidade = resultado.confiabilidade || 'media';
            let badgeConfig = {
                'alta': { bg: '#dcfce7', color: '#166534', icon: 'check-circle', text: 'Alta Confiança' },
                'media': { bg: '#fef3c7', color: '#92400e', icon: 'info-circle', text: 'Estimado' },
                'baixa': { bg: '#fee2e2', color: '#991b1b', icon: 'exclamation-circle', text: 'Valor Global' }
            };

            const badge = badgeConfig[confiabilidade] || badgeConfig['media'];
            const tipoOrigem = resultado.tipo === 'ata' ? 'ARP' : 'Contrato';
            const unidadeMedida = resultado.unidade_medida || 'UN';
            const orgao = resultado.orgao_nome || resultado.orgao || 'Órgão não informado';
            const uf = resultado.orgao_uf || resultado.uf || '';
            const preco = resultado.valor_homologado_item || resultado.valor_unitario || resultado.valor_global || 0;

            // Badge de origem (usar campo 'fonte' do backend)
            const origem = resultado.fonte || resultado.origem || 'PNCP';
            let origemBadge = {
                'LOCAL': { bg: '#dbeafe', color: '#1e40af', text: 'Local' },
                'PNCP_SEARCH_CONTRATO': { bg: '#fce7f3', color: '#9f1239', text: 'PNCP' },
                'PNCP_CONTRATOS': { bg: '#fce7f3', color: '#9f1239', text: 'PNCP' },
                'PNCP_SEARCH_ATA': { bg: '#fef3c7', color: '#92400e', text: 'PNCP ARP' },
                'COMPRAS.GOV': { bg: '#dcfce7', color: '#166534', text: 'COMPRAS.GOV' },
                'CMED': { bg: '#dbeafe', color: '#1e40af', text: 'CMED' },
                'LICITACON': { bg: '#fef3c7', color: '#92400e', text: 'LicitaCon' }
            };
            const origemStyle = origemBadge[origem] || { bg: '#e5e7eb', color: '#374151', text: origem };

            html += `
                <tr style="border-bottom: 1px solid #f3f4f6; ${index % 2 === 0 ? 'background: #fafafa;' : ''}" data-index="${index}">
                    <td style="padding: 12px; text-align: center;">
                        <input type="checkbox"
                               class="checkbox-item"
                               data-index="${index}"
                               data-descricao="${descricao.replace(/"/g, '&quot;')}"
                               data-preco="${preco}"
                               data-unidade="${unidadeMedida}"
                               data-origem="${origem}"
                               data-orgao="${orgao.replace(/"/g, '&quot;')}"
                               data-uf="${uf}"
                               style="width: 16px; height: 16px; cursor: pointer;">
                    </td>
                    <td style="padding: 12px;">
                        <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px; font-size: 13px;">${descHighlight}</div>
                        <div style="display: flex; gap: 6px; align-items: center; margin-top: 6px;">
                            <span style="display: inline-block; padding: 2px 6px; background: ${badge.bg}; color: ${badge.color}; font-size: 9px; font-weight: 700; border-radius: 3px; text-transform: uppercase;">
                                <i class="fas fa-${badge.icon}" style="margin-right: 3px;"></i>${badge.text}
                            </span>
                            <span style="font-size: 10px; color: #6b7280;">${unidadeMedida} • ${tipoOrigem}</span>
                        </div>
                    </td>
                    <td style="padding: 12px;">
                        <div style="color: #374151; font-size: 11px; font-weight: 600; margin-bottom: 4px;">${orgao} ${uf ? '(' + uf + ')' : ''}</div>
                        <span style="display: inline-block; padding: 2px 6px; background: ${origemStyle.bg}; color: ${origemStyle.color}; font-size: 9px; font-weight: 700; border-radius: 3px;">
                            ${origemStyle.text}
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <div style="color: #6b7280; font-weight: 600;">1</div>
                    </td>
                    <td style="padding: 12px; text-align: right; font-weight: 600; color: #3b82f6; font-size: 14px;">
                        R$ ${preco.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td style="padding: 12px; text-align: right; color: #10b981; font-size: 14px;">
                        R$ ${preco.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td style="padding: 12px; text-align: right; color: #ef4444; font-size: 14px;">
                        R$ ${preco.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        // Rodapé com fonte de dados
        if (aviso) {
            // Aviso quando usando dados locais
            html += `
                <div style="margin-top: 20px; padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                    <p style="margin: 0; font-size: 12px; color: #92400e;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Atenção:</strong> ${aviso}
                    </p>
                </div>
                <div style="margin-top: 10px; padding: 12px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                    <p style="margin: 0; font-size: 12px; color: #1e40af;">
                        <i class="fas fa-database"></i>
                        <strong>Fonte:</strong> Orçamentos Locais - Últimos 12 meses
                    </p>
                </div>
            `;
        } else {
            // Rodapé normal (Multi-fonte)
            const fontesConsultadas = window.fontesConsultadas || 'PNCP, Banco de Preços, ComprasNet, Orçamentos Locais';
            html += `
                <div style="margin-top: 20px; padding: 12px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                    <p style="margin: 0; font-size: 12px; color: #1e40af;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Fontes Consultadas:</strong> ${fontesConsultadas}
                    </p>
                    <p style="margin: 5px 0 0 0; font-size: 11px; color: #3b82f6;">
                        Período de análise: Últimos 12 meses
                    </p>
                </div>

                <!-- Legenda de Confiabilidade -->
                <div style="margin-top: 12px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <div style="font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 8px; text-transform: uppercase;">
                        <i class="fas fa-lightbulb" style="margin-right: 4px;"></i>
                        Entenda os Indicadores de Confiabilidade
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        <div style="display: flex; align-items: start; gap: 6px;">
                            <span style="display: inline-block; padding: 2px 6px; background: #dcfce7; color: #166534; font-size: 9px; font-weight: 700; border-radius: 3px; white-space: nowrap;">
                                <i class="fas fa-check-circle"></i> ALTA CONFIANÇA
                            </span>
                            <span style="font-size: 10px; color: #6b7280; line-height: 1.3;">Dados de Atas de Registro de Preço (ARP) com valores unitários</span>
                        </div>
                        <div style="display: flex; align-items: start; gap: 6px;">
                            <span style="display: inline-block; padding: 2px 6px; background: #fef3c7; color: #92400e; font-size: 9px; font-weight: 700; border-radius: 3px; white-space: nowrap;">
                                <i class="fas fa-info-circle"></i> ESTIMADO
                            </span>
                            <span style="font-size: 10px; color: #6b7280; line-height: 1.3;">Valor calculado a partir de contratos com múltiplas parcelas</span>
                        </div>
                        <div style="display: flex; align-items: start; gap: 6px;">
                            <span style="display: inline-block; padding: 2px 6px; background: #fee2e2; color: #991b1b; font-size: 9px; font-weight: 700; border-radius: 3px; white-space: nowrap;">
                                <i class="fas fa-exclamation-circle"></i> VALOR GLOBAL
                            </span>
                            <span style="font-size: 10px; color: #6b7280; line-height: 1.3;">Valor total do contrato (use como referência geral)</span>
                        </div>
                    </div>
                </div>
            `;
        }

        resultadosArea.innerHTML = html;


        // Atualizar contador de resultados
        const contador = document.getElementById('contador-resultados');
        contador.textContent = `${resultados.length} ${resultados.length === 1 ? 'item' : 'itens'}`;
        contador.style.display = 'inline-block';

        // Armazenar resultados globalmente para filtros e exportação
        window.resultadosCompletos = resultados;
        window.termoBuscaAtual = termoBusca;
    }

    // ================================================================
    // SISTEMA DE FILTROS E ORDENAÇÃO
    // ================================================================

    let resultadosFiltrados = [];

    // Aplicar filtros
    document.getElementById('btn-aplicar-filtros').addEventListener('click', function() {
        if (!window.resultadosCompletos) {
            alert('Faça uma pesquisa primeiro antes de aplicar filtros!');
            return;
        }

        aplicarFiltros();
    });

    // Limpar filtros
    document.getElementById('btn-limpar-filtros').addEventListener('click', function() {
        // Resetar filtros básicos
        document.getElementById('preco-minimo').value = '';
        document.getElementById('preco-maximo').value = '';
        // Período está fixo em 12 meses, não precisa resetar
        document.querySelectorAll('.filtro-confiabilidade').forEach(cb => cb.checked = true);

        // Resetar filtros geográficos
        document.getElementById('filtro-regiao').value = '';
        document.getElementById('filtro-uf').value = '';

        // Resetar filtros de entidade
        document.getElementById('filtro-orgao').value = '';
        document.getElementById('filtro-porte').value = '';
        document.getElementById('lista-orgaos').style.display = 'none';

        // Resetar filtros de produto
        document.getElementById('filtro-unidade').value = '';
        document.getElementById('filtro-marca').value = '';
        document.getElementById('filtro-origem').value = '';

        // Limpar resultados filtrados
        resultadosFiltrados = [];

        // Reexibir todos os resultados
        if (window.resultadosCompletos) {
            exibirResultados(window.resultadosCompletos, window.termoBuscaAtual);
        }
    });

    // Ordenação
    document.getElementById('ordenar').addEventListener('change', function() {
        if (!window.resultadosCompletos) return;

        const criterio = this.value;
        let resultados = resultadosFiltrados.length > 0 ? [...resultadosFiltrados] : [...window.resultadosCompletos];

        switch(criterio) {
            case 'confiabilidade':
                const pesoConf = {'alta': 3, 'media': 2, 'baixa': 1};
                resultados.sort((a, b) => {
                    const pesoA = pesoConf[a.confiabilidade] || 0;
                    const pesoB = pesoConf[b.confiabilidade] || 0;
                    if (pesoA !== pesoB) return pesoB - pesoA;
                    return b.quantidade_amostras - a.quantidade_amostras;
                });
                break;
            case 'menor_preco':
                resultados.sort((a, b) => a.preco_medio - b.preco_medio);
                break;
            case 'maior_preco':
                resultados.sort((a, b) => b.preco_medio - a.preco_medio);
                break;
            case 'amostras':
                resultados.sort((a, b) => b.quantidade_amostras - a.quantidade_amostras);
                break;
        }

        exibirResultados(resultados, window.termoBuscaAtual);
    });

    function aplicarFiltros() {
        // ========== CAPTURAR VALORES DOS FILTROS ==========

        // Filtros básicos
        const precoMin = parseFloat(document.getElementById('preco-minimo').value) || 0;
        const precoMax = parseFloat(document.getElementById('preco-maximo').value) || Infinity;
        const periodoMeses = 12; // Sempre 12 meses

        const confiabilidadesSelecionadas = [];
        document.querySelectorAll('.filtro-confiabilidade:checked').forEach(cb => {
            confiabilidadesSelecionadas.push(cb.value);
        });

        // Filtros geográficos
        const regiaoSelecionada = document.getElementById('filtro-regiao').value;
        const ufSelecionada = document.getElementById('filtro-uf').value;

        // Filtros de entidade
        const orgaoBusca = document.getElementById('filtro-orgao').value.toLowerCase().trim();
        const porteSelecionado = document.getElementById('filtro-porte').value;

        // Filtros de produto
        const unidadeSelecionada = document.getElementById('filtro-unidade').value;
        const marcaBusca = document.getElementById('filtro-marca').value.toLowerCase().trim();
        const origemSelecionada = document.getElementById('filtro-origem').value;

        // Mapa de UF para Região
        const mapaRegioes = {
            'norte': ['AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO'],
            'nordeste': ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
            'centro-oeste': ['DF', 'GO', 'MT', 'MS'],
            'sudeste': ['ES', 'MG', 'RJ', 'SP'],
            'sul': ['PR', 'RS', 'SC']
        };

        // ========== APLICAR FILTROS ==========
        resultadosFiltrados = window.resultadosCompletos.filter(item => {

            // 1. Filtro de Preço
            if (item.preco_medio < precoMin || item.preco_medio > precoMax) {
                return false;
            }

            // 2. Filtro de Confiabilidade
            if (!confiabilidadesSelecionadas.includes(item.confiabilidade)) {
                return false;
            }

            // 3. Filtro de Período (últimos 12 meses - FIXO)
            if (item.data_publicacao) {
                const dataLimite = new Date();
                dataLimite.setMonth(dataLimite.getMonth() - 12);
                const dataAmostra = new Date(item.data_publicacao);
                if (dataAmostra < dataLimite) return false;
            }

            // 4. Filtro de UF (Estado)
            if (ufSelecionada) {
                const orgao = (item.orgao_nome || item.exemplo_orgao || item.orgao || '').toUpperCase();
                // Buscar sigla da UF no nome do órgão (ex: "PREFEITURA DE SÃO PAULO - SP")
                const temUF = orgao.includes(` ${ufSelecionada}`) ||
                              orgao.includes(`-${ufSelecionada}`) ||
                              orgao.includes(`/${ufSelecionada}`) ||
                              orgao.endsWith(ufSelecionada);
                if (!temUF) return false;
            }

            // 5. Filtro de Região
            if (regiaoSelecionada && mapaRegioes[regiaoSelecionada]) {
                const ufsRegiao = mapaRegioes[regiaoSelecionada];
                const orgao = (item.orgao_nome || item.exemplo_orgao || item.orgao || '').toUpperCase();
                const temRegiao = ufsRegiao.some(uf =>
                    orgao.includes(` ${uf}`) ||
                    orgao.includes(`-${uf}`) ||
                    orgao.includes(`/${uf}`) ||
                    orgao.endsWith(uf)
                );
                if (!temRegiao) return false;
            }

            // 6. Filtro de Órgão (busca textual)
            if (orgaoBusca) {
                const orgaoPrincipal = (item.orgao_nome || item.exemplo_orgao || item.orgao || '').toLowerCase();
                if (!orgaoPrincipal.includes(orgaoBusca)) return false;
            }

            // 7. Filtro de Porte da Empresa
            // Nota: Esse dado não vem do PNCP atualmente, então vamos deixar preparado
            if (porteSelecionado) {
                // TODO: Implementar quando backend fornecer dados de porte
                // Por enquanto, não filtra
            }

            // 8. Filtro de Unidade de Medida
            if (unidadeSelecionada) {
                const unidadeItem = item.unidade.toUpperCase();
                const unidadeFiltro = unidadeSelecionada.toUpperCase();
                if (unidadeItem !== unidadeFiltro) {
                    return false;
                }
            }

            // 9. Filtro de Marca
            if (marcaBusca) {
                const descricao = item.descricao.toLowerCase();
                if (!descricao.includes(marcaBusca)) {
                    return false;
                }
            }

            // 10. Filtro de Origem das Amostras
            if (origemSelecionada) {
                const origemFiltro = origemSelecionada.toLowerCase();

                // Mapeamento de origem para tipos aceitos
                const mapaOrigens = {
                    'pncp': ['contrato', 'ata', 'pncp'],
                    'banco_precos': ['banco_precos', 'gov_br'],
                    'comprasnet': ['comprasnet', 'siasg'],
                    'local': ['local', 'orcamento_local']
                };

                // Se tem mapeamento para essa origem
                if (mapaOrigens[origemFiltro]) {
                    const tiposAceitos = mapaOrigens[origemFiltro];
                    if (!tiposAceitos.includes(item.tipo_origem?.toLowerCase())) {
                        return false;
                    }
                }
            }

            return true;
        });

        // ========== EXIBIR RESULTADOS FILTRADOS ==========
        if (resultadosFiltrados.length === 0) {
            document.getElementById('area-resultados').innerHTML = `
                <i class="fas fa-filter" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                <p style="color: #6b7280; font-size: 14px; margin: 0;">
                    Nenhum resultado encontrado com os filtros aplicados.
                </p>
                <p style="color: #9ca3af; font-size: 12px; margin-top: 10px;">
                    Tente ajustar os filtros para ver mais resultados.
                </p>
            `;

            // Atualizar contador
            const contador = document.getElementById('contador-resultados');
            contador.textContent = '0 itens';
            contador.style.display = 'inline-block';
            return;
        }

        exibirResultados(resultadosFiltrados, window.termoBuscaAtual);
    }

    // ================================================================
    // AUTOCOMPLETE DE ÓRGÃOS
    // ================================================================

    const inputOrgao = document.getElementById('filtro-orgao');
    const listaOrgaos = document.getElementById('lista-orgaos');

    inputOrgao.addEventListener('input', function() {
        const termo = this.value.toLowerCase().trim();

        // Se vazio, ocultar lista
        if (!termo || !window.resultadosCompletos) {
            listaOrgaos.style.display = 'none';
            return;
        }

        // Extrair todos os órgãos únicos dos resultados
        const orgaosUnicos = new Set();
        window.resultadosCompletos.forEach(item => {
            // Adicionar órgão do contrato individual (suporta múltiplos formatos)
            const orgao = item.orgao_nome || item.exemplo_orgao || item.orgao;
            if (orgao) {
                orgaosUnicos.add(orgao);
            }
        });

        // Filtrar órgãos que contenham o termo buscado
        const orgaosFiltrados = Array.from(orgaosUnicos)
            .filter(orgao => orgao.toLowerCase().includes(termo))
            .sort()
            .slice(0, 10); // Limitar a 10 resultados

        // Se não houver resultados, ocultar
        if (orgaosFiltrados.length === 0) {
            listaOrgaos.style.display = 'none';
            return;
        }

        // Montar HTML da lista
        let html = '';
        orgaosFiltrados.forEach(orgao => {
            // Highlight do termo buscado
            const orgaoHighlight = orgao.replace(
                new RegExp(`(${termo})`, 'gi'),
                '<strong style="color: #3b82f6;">$1</strong>'
            );

            html += `
                <div class="opcao-orgao" data-orgao="${orgao}" style="padding: 8px; cursor: pointer; font-size: 11px; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;">
                    ${orgaoHighlight}
                </div>
            `;
        });

        listaOrgaos.innerHTML = html;
        listaOrgaos.style.display = 'block';

        // Adicionar event listeners para cada opção
        document.querySelectorAll('.opcao-orgao').forEach(opcao => {
            opcao.addEventListener('mouseenter', function() {
                this.style.background = '#eff6ff';
            });

            opcao.addEventListener('mouseleave', function() {
                this.style.background = 'white';
            });

            opcao.addEventListener('click', function() {
                const orgaoSelecionado = this.getAttribute('data-orgao');
                inputOrgao.value = orgaoSelecionado;
                listaOrgaos.style.display = 'none';
            });
        });
    });

    // Fechar lista ao clicar fora
    document.addEventListener('click', function(event) {
        if (!inputOrgao.contains(event.target) && !listaOrgaos.contains(event.target)) {
            listaOrgaos.style.display = 'none';
        }
    });

    // ================================================================
    // FUNÇÕES DE EXPORTAÇÃO REMOVIDAS (conforme solicitado)
    // ================================================================

    // ================================================================
    // CRIAR ORÇAMENTO A PARTIR DE ITENS SELECIONADOS
    // ================================================================

    // Função para atualizar contador e visibilidade do botão
    function atualizarContadorSelecionados() {
        const checkboxes = document.querySelectorAll('.checkbox-item:checked');
        const total = checkboxes.length;

        // Atualizar contadores
        const contadorPalavra = document.getElementById('contador-selecionados');
        const contadorCatmat = document.getElementById('contador-selecionados-catmat');
        if (contadorPalavra) contadorPalavra.textContent = total;
        if (contadorCatmat) contadorCatmat.textContent = total;

        // Mostrar/esconder botões
        const btnPalavra = document.getElementById('btn-criar-orcamento');
        const btnCatmat = document.getElementById('btn-criar-orcamento-catmat');

        if (total > 0) {
            if (btnPalavra) btnPalavra.style.display = 'inline-flex';
            if (btnCatmat) btnCatmat.style.display = 'inline-flex';
        } else {
            if (btnPalavra) btnPalavra.style.display = 'none';
            if (btnCatmat) btnCatmat.style.display = 'none';
        }
    }

    // Event listener para "Selecionar Todos"
    document.addEventListener('change', function(e) {
        if (e.target.id === 'selecionar-todos') {
            const checkboxes = document.querySelectorAll('.checkbox-item');
            checkboxes.forEach(cb => {
                cb.checked = e.target.checked;
            });
            atualizarContadorSelecionados();
        }

        // Event listener para checkboxes individuais
        if (e.target.classList.contains('checkbox-item')) {
            atualizarContadorSelecionados();

            // Desmarcar "Selecionar Todos" se algum item for desmarcado
            const todosSelecionados = document.querySelectorAll('.checkbox-item');
            const todosCheckbox = document.getElementById('selecionar-todos');
            if (todosCheckbox) {
                const todosChecked = Array.from(todosSelecionados).every(cb => cb.checked);
                todosCheckbox.checked = todosChecked && todosSelecionados.length > 0;
            }
        }
    });

    // Função para criar orçamento com itens selecionados
    async function criarOrcamentoComSelecionados() {
        const checkboxes = document.querySelectorAll('.checkbox-item:checked');

        if (checkboxes.length === 0) {
            alert('⚠️ Selecione pelo menos um item para criar o orçamento.');
            return;
        }

        // Coletar dados dos itens selecionados
        const itensSelecionados = Array.from(checkboxes).map(cb => {
            return {
                descricao: cb.dataset.descricao,
                preco_unitario: parseFloat(cb.dataset.preco),
                unidade_medida: cb.dataset.unidade,
                quantidade: 1, // Quantidade inicial = 1
                origem: cb.dataset.origem,
                orgao: cb.dataset.orgao,
                uf: cb.dataset.uf
            };
        });

        console.log('📋 Itens selecionados para orçamento:', itensSelecionados);

        // Confirmar com usuário
        const confirmar = confirm(
            `📋 CRIAR NOVO ORÇAMENTO\n\n` +
            `Você selecionou ${itensSelecionados.length} item(ns).\n\n` +
            `Um novo orçamento será criado e você será redirecionado para a tela de elaboração.\n\n` +
            `Deseja continuar?`
        );

        if (!confirmar) return;

        // Mostrar loading
        const btnPalavra = document.getElementById('btn-criar-orcamento');
        const btnCatmat = document.getElementById('btn-criar-orcamento-catmat');
        const btnOriginal = btnPalavra?.style.display !== 'none' ? btnPalavra : btnCatmat;

        if (btnOriginal) {
            btnOriginal.innerHTML = '<i class="fas fa-spinner fa-spin"></i> CRIANDO ORÇAMENTO...';
            btnOriginal.disabled = true;
        }

        try {
            // Enviar para backend
            const response = await fetch('/pesquisa-rapida/criar-orcamento', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    itens: itensSelecionados
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log('✅ Orçamento criado:', data.orcamento_id);

                // Redirecionar para elaboração do orçamento
                window.location.href = `/orcamentos/${data.orcamento_id}/elaborar`;
            } else {
                throw new Error(data.message || 'Erro ao criar orçamento');
            }
        } catch (error) {
            console.error('❌ Erro ao criar orçamento:', error);
            alert('❌ Erro ao criar orçamento:\n\n' + error.message + '\n\nTente novamente.');

            // Restaurar botão
            if (btnOriginal) {
                btnOriginal.innerHTML = '<i class="fas fa-file-invoice-dollar"></i> CRIAR ORÇAMENTO COM SELECIONADOS (' + checkboxes.length + ')';
                btnOriginal.disabled = false;
            }
        }
    }

    // Event listeners para os botões de criar orçamento
    document.getElementById('btn-criar-orcamento')?.addEventListener('click', criarOrcamentoComSelecionados);
    document.getElementById('btn-criar-orcamento-catmat')?.addEventListener('click', criarOrcamentoComSelecionados);
});
</script>

<style>
.tab-btn {
    transition: all 0.2s ease;
}

.tab-btn:hover {
    color: #3b82f6 !important;
}

</style>
@endsection
