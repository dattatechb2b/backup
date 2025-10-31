@extends('layouts.app')

@section('title', 'Mapa de Fornecedores')

@section('content')
<div style="padding: 25px;">
    <!-- Título da Página -->
    <h1 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 25px;">
        MAPA DE FORNECEDORES
    </h1>

    <!-- SEÇÃO 1: ARGUMENTOS DE PESQUISA -->
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fas fa-search" style="color: #6b7280;"></i>
            <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                ARGUMENTOS DE PESQUISA
            </h2>
        </div>

        <div style="background: white; padding: 20px; border-radius: 6px;">
            <!-- Campo Descrição/Código/CNPJ (obrigatório) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                    Busca por Palavra, Código CATMAT/CATSER ou CNPJ:<span style="color: #ef4444;">*</span>
                </label>
                <input type="text" id="descricao_fornecedor" placeholder="Digite qualquer palavra (ex: medicamento, caneta, seringa, caminhonete)" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                <p style="font-size: 11px; color: #6b7280; margin: 8px 0 0 0;">
                    <i class="fas fa-info-circle"></i> Sistema busca em tempo real no PNCP qualquer fornecedor que tenha essa palavra nos contratos
                </p>
            </div>

            <!-- Botão Consultar -->
            <button type="button" id="btn-consultar" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-search"></i>
                BUSCAR FORNECEDORES
            </button>
        </div>
    </div>

    <!-- Layout 2 Colunas: Refinar + Resultados -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px;">

        <!-- SEÇÃO 2: REFINAR PESQUISA (Esquerda) -->
        <div>
            <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; max-height: calc(100vh - 200px); overflow-y: auto;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <i class="fas fa-filter" style="color: #6b7280;"></i>
                    <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                        REFINAR RESULTADOS
                    </h2>
                </div>

                <!-- ========== FILTROS BÁSICOS ========== -->

                <!-- Filtro de Fonte de Dados -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <i class="fas fa-database" style="margin-right: 4px;"></i>
                        FONTE DE DADOS
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-fonte" value="CMED" checked style="width: 14px; height: 14px; cursor: pointer;">
                            💊 CMED (Medicamentos)
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-fonte" value="LOCAL" checked style="width: 14px; height: 14px; cursor: pointer;">
                            🏠 Banco Local
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-fonte" value="COMPRAS.GOV" checked style="width: 14px; height: 14px; cursor: pointer;">
                            🛒 Compras.gov
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; cursor: pointer;">
                            <input type="checkbox" class="filtro-fonte" value="PNCP" checked style="width: 14px; height: 14px; cursor: pointer;">
                            🏛️ PNCP
                        </label>
                    </div>
                </div>

                <!-- Divisor -->
                <div style="border-top: 1px solid #e5e7eb; margin: 15px 0;"></div>

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

                <!-- Filtro de Estado -->
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

                <!-- Botão Aplicar Filtros -->
                <button type="button" id="btn-aplicar-filtros" style="width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-check"></i>
                    APLICAR FILTROS
                </button>

                <!-- Botão Limpar Filtros -->
                <button type="button" id="btn-limpar-filtros" style="width: 100%; background: #6b7280; color: white; border: none; padding: 8px; border-radius: 6px; font-size: 10px; font-weight: 600; cursor: pointer; margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-eraser"></i>
                    LIMPAR FILTROS
                </button>

                <!-- Informação sobre a busca -->
                <div style="margin-top: 20px; padding: 12px; background: #eff6ff; border: 1px solid #93c5fd; border-radius: 5px;">
                    <p style="font-size: 10px; color: #1e40af; line-height: 1.5; margin: 0;">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                        <strong>Sobre a busca:</strong><br>
                        O sistema busca fornecedores que já forneceram produtos/serviços relacionados ao termo pesquisado em múltiplas bases de dados.
                    </p>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: RESULTADO DA PESQUISA (Direita) -->
        <div>
            <div style="background: #f3f4f6; padding: 20px; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <i class="fas fa-list" style="color: #6b7280;"></i>
                    <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                        RESULTADO DA PESQUISA
                    </h2>
                </div>

                <!-- Área de Resultados -->
                <div id="area-resultados" style="background: white; padding: 40px; border-radius: 6px; text-align: center; min-height: 200px;">
                    <i class="fas fa-store-slash" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <p style="color: #9ca3af; font-size: 14px; margin: 0;">
                        Digite um termo e clique em <strong>CONSULTAR PNCP</strong> para listar fornecedores.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Detalhes do Fornecedor -->
<div class="modal fade" id="modalDetalhesFornecedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #426a94 0%, #2d4f73 100%); color: white;">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 600; margin: 0;">
                    <i class="fas fa-building"></i> DETALHES DO FORNECEDOR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-size: 13px; color: #6b7280; font-weight: 600; width: 180px;">CNPJ:</td>
                        <td id="modal-cnpj" style="padding: 12px; font-size: 13px; color: #1f2937;"></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-size: 13px; color: #6b7280; font-weight: 600;">Razão Social:</td>
                        <td id="modal-razao-social" style="padding: 12px; font-size: 13px; color: #1f2937; font-weight: 600;"></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-size: 13px; color: #6b7280; font-weight: 600;">Nome Fantasia:</td>
                        <td id="modal-nome-fantasia" style="padding: 12px; font-size: 13px; color: #1f2937;"></td>
                    </tr>
                    <tr id="row-telefone" style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-size: 13px; color: #6b7280; font-weight: 600;">Telefone:</td>
                        <td id="modal-telefone" style="padding: 12px; font-size: 13px; color: #1f2937;"></td>
                    </tr>
                    <tr id="row-email" style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-size: 13px; color: #6b7280; font-weight: 600;">E-mail:</td>
                        <td id="modal-email" style="padding: 12px; font-size: 13px; color: #1f2937;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; font-size: 13px; color: #6b7280; font-weight: 600; vertical-align: top;">Endereço:</td>
                        <td id="modal-endereco" style="padding: 12px; font-size: 13px; color: #1f2937; line-height: 1.6;"></td>
                    </tr>
                </table>

                <!-- Produtos que o fornecedor já forneceu -->
                <div id="modal-produtos" style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                    <!-- Preenchido dinamicamente -->
                </div>
            </div>
            <div class="modal-footer" style="background: #f9fafb; padding: 15px 20px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-size: 13px;">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Flag global para prevenir múltiplos cliques
let buscandoFornecedores = false;

document.addEventListener('DOMContentLoaded', function() {
    // Botão Consultar
    document.getElementById('btn-consultar').addEventListener('click', async function() {
        // PREVENIR DUPLO CLIQUE
        if (buscandoFornecedores) {
            console.log('[MapaFornecedores] Busca já em andamento, aguarde...');
            return;
        }

        const descricao = document.getElementById('descricao_fornecedor').value;
        const resultadosArea = document.getElementById('area-resultados');
        const btn = this;

        // Validar campo
        if (!descricao.trim() || descricao.trim().length < 3) {
            resultadosArea.innerHTML = `
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #fbbf24; margin-bottom: 15px;"></i>
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    Por favor, preencha o campo com pelo menos 3 caracteres para pesquisar.
                </p>
            `;
            return;
        }

        // Marcar como buscando (PREVENIR DUPLO CLIQUE)
        buscandoFornecedores = true;

        // Mostrar loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> BUSCANDO...';
        resultadosArea.innerHTML = `
            <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #3b82f6; margin-bottom: 15px;"></i>
            <p style="color: #1e40af; font-size: 14px; margin: 0; font-weight: 600;">
                Buscando fornecedores em tempo real...
            </p>
            <p style="color: #6b7280; font-size: 12px; margin: 10px 0 0 0;">
                Aguarde de 5 a 8 segundos...
            </p>
        `;

        // BUSCA TRADICIONAL (Fetch - retorna todos os resultados de uma vez)
        fetch(`${window.APP_BASE_PATH}/api/fornecedores/buscar-por-produto?termo=${encodeURIComponent(descricao)}`)
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || 'Erro ao buscar fornecedores');
                }

                const fornecedores = result.fornecedores || [];

                // Armazenar na variável global para os filtros
                todosFornecedores = fornecedores;

                if (fornecedores.length === 0) {
                    resultadosArea.innerHTML = `
                        <i class="fas fa-search" style="font-size: 48px; color: #6b7280; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p style="color: #374151; font-size: 14px; margin-bottom: 10px;">Nenhum fornecedor encontrado para:</p>
                        <p style="color: #6b7280; font-size: 13px; font-weight: 600; margin: 0;">"${descricao}"</p>
                        <p style="color: #9ca3af; font-size: 12px; margin-top: 15px;">Tente buscar com outros termos ou sincronize mais dados do PNCP.</p>
                    `;
                    return;
                }

                // Usar a função renderizarFornecedores (reutilizável com filtros)
                renderizarFornecedores(fornecedores);
            })
            .catch(error => {
                console.error('Erro ao buscar fornecedores:', error);
                resultadosArea.innerHTML = `
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
                    <p style="color: #dc2626; font-size: 14px; margin: 0;">Erro ao buscar fornecedores. Tente novamente.</p>
                    <p style="color: #9ca3af; font-size: 12px; margin-top: 10px;">${error.message}</p>
                `;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search"></i> BUSCAR FORNECEDORES';
                buscandoFornecedores = false;
            });
    });

    // Buscar ao pressionar Enter (usando keydown para melhor compatibilidade)
    document.getElementById('descricao_fornecedor').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault(); // Prevenir comportamento padrão do formulário
            e.stopPropagation(); // Evitar propagação do evento
            console.log('⌨️ Enter pressionado no campo de busca de fornecedores');
            const btn = document.getElementById('btn-consultar');
            if (btn) {
                console.log('🖱️ Simulando click no botão de consulta...');
                btn.click();
            } else {
                console.error('❌ Botão btn-consultar não encontrado!');
            }
        }
    });

    // ========== FUNCIONALIDADES DOS FILTROS ==========

    // Variável global para armazenar todos os fornecedores
    let todosFornecedores = [];

    // Botão Aplicar Filtros
    document.getElementById('btn-aplicar-filtros').addEventListener('click', function() {
        aplicarFiltros();
    });

    // Botão Limpar Filtros
    document.getElementById('btn-limpar-filtros').addEventListener('click', function() {
        // Resetar checkboxes de fonte
        document.querySelectorAll('.filtro-fonte').forEach(checkbox => {
            checkbox.checked = true;
        });

        // Resetar selects
        document.getElementById('filtro-regiao').value = '';
        document.getElementById('filtro-uf').value = '';

        // Reaplicar filtros (mostrar todos)
        aplicarFiltros();
    });

    // Função para aplicar os filtros
    function aplicarFiltros() {
        if (todosFornecedores.length === 0) {
            console.log('[Filtros] Nenhum fornecedor para filtrar ainda.');
            return;
        }

        // Coletar fontes selecionadas
        const fontesSelecionadas = [];
        document.querySelectorAll('.filtro-fonte:checked').forEach(checkbox => {
            fontesSelecionadas.push(checkbox.value);
        });

        // Coletar filtros geográficos
        const regiaoSelecionada = document.getElementById('filtro-regiao').value.toLowerCase();
        const ufSelecionada = document.getElementById('filtro-uf').value.toUpperCase();

        console.log('[Filtros] Aplicando filtros:', { fontes: fontesSelecionadas, regiao: regiaoSelecionada, uf: ufSelecionada });

        // Filtrar fornecedores
        const fornecedoresFiltrados = todosFornecedores.filter(fornecedor => {
            // Filtro de fonte
            let origemMatch = false;
            if (fornecedor.origem === 'LOCAL' && fontesSelecionadas.includes('LOCAL')) origemMatch = true;
            if ((fornecedor.origem === 'CMED' || fornecedor.origem?.includes('CMED')) && fontesSelecionadas.includes('CMED')) origemMatch = true;
            if ((fornecedor.origem === 'COMPRAS.GOV' || fornecedor.origem?.includes('COMPRAS.GOV')) && fontesSelecionadas.includes('COMPRAS.GOV')) origemMatch = true;
            if ((fornecedor.origem === 'PNCP' || (!fornecedor.origem?.includes('CMED') && !fornecedor.origem?.includes('COMPRAS.GOV') && fornecedor.origem !== 'LOCAL')) && fontesSelecionadas.includes('PNCP')) origemMatch = true;

            if (!origemMatch) return false;

            // Filtro de UF (se selecionado)
            if (ufSelecionada && fornecedor.uf !== ufSelecionada) {
                return false;
            }

            // Filtro de região (se selecionado e UF não estiver selecionado)
            if (regiaoSelecionada && !ufSelecionada) {
                const uf = fornecedor.uf;
                let regiaoFornecedor = '';

                // Mapeamento UF -> Região
                const regioes = {
                    'norte': ['AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO'],
                    'nordeste': ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
                    'centro-oeste': ['DF', 'GO', 'MT', 'MS'],
                    'sudeste': ['ES', 'MG', 'RJ', 'SP'],
                    'sul': ['PR', 'RS', 'SC']
                };

                for (const [regiao, ufs] of Object.entries(regioes)) {
                    if (ufs.includes(uf)) {
                        regiaoFornecedor = regiao;
                        break;
                    }
                }

                if (regiaoFornecedor !== regiaoSelecionada) {
                    return false;
                }
            }

            return true;
        });

        console.log(`[Filtros] ${fornecedoresFiltrados.length} de ${todosFornecedores.length} fornecedores após filtros`);

        // Renderizar fornecedores filtrados
        renderizarFornecedores(fornecedoresFiltrados);
    }

    // Função para renderizar fornecedores (reutilizável)
    function renderizarFornecedores(fornecedores) {
        const resultadosArea = document.getElementById('area-resultados');

        if (fornecedores.length === 0) {
            resultadosArea.innerHTML = `
                <i class="fas fa-filter" style="font-size: 48px; color: #6b7280; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="color: #374151; font-size: 14px; margin-bottom: 10px;">Nenhum fornecedor encontrado com os filtros aplicados</p>
                <p style="color: #9ca3af; font-size: 12px; margin-top: 15px;">Tente ajustar os filtros ou limpar todos.</p>
            `;
            return;
        }

        let html = `<div style="width: 100%;">`;
        html += `<div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; margin-bottom: 20px;">`;
        html += `<p style="margin: 0; font-size: 13px; color: #065f46; font-weight: 600;">`;
        html += `<i class="fas fa-check-circle"></i> ${fornecedores.length} fornecedor(es) encontrado(s)`;
        html += `</p></div>`;

        fornecedores.forEach((fornecedor, index) => {
            html += `<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.2s;" class="card-fornecedor" data-index="${index}">`;

            html += `<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">`;
            html += `<div style="flex: 1;">`;
            html += `<h3 style="margin: 0 0 5px 0; font-size: 16px; color: #1f2937; font-weight: 600;">${fornecedor.razao_social || 'Nome não informado'}</h3>`;
            html += `<p style="margin: 0 0 5px 0; font-size: 13px; color: #374151;"><strong>CNPJ:</strong> ${fornecedor.cnpj || 'Não informado'}</p>`;

            // Badge de origem
            let origemBadge;
            if (fornecedor.origem === 'LOCAL') {
                origemBadge = `<span style="display: inline-block; background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-top: 5px;">📋 CADASTRADO LOCALMENTE</span>`;
            } else if (fornecedor.origem === 'CMED' || fornecedor.origem?.includes('CMED')) {
                origemBadge = `<span style="display: inline-block; background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-top: 5px;">💊 CMED</span>`;
            } else if (fornecedor.origem === 'COMPRAS.GOV' || fornecedor.origem?.includes('COMPRAS.GOV')) {
                origemBadge = `<span style="display: inline-block; background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-top: 5px;">🛒 COMPRAS.GOV</span>`;
            } else {
                origemBadge = `<span style="display: inline-block; background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-top: 5px;">🌐 PNCP</span>`;
            }
            html += `<div>${origemBadge}</div>`;
            html += `</div>`;
            html += `<button type="button" class="btn-ver-detalhes" data-index="${index}" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 6px;"><i class="fas fa-info-circle"></i> VER DETALHES</button>`;
            html += `</div>`;

            if (fornecedor.telefone || fornecedor.email) {
                html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 12px;">`;
                html += `<p style="margin: 0 0 6px 0; font-size: 12px; color: #6b7280;"><strong>Contato:</strong></p>`;
                if (fornecedor.telefone) html += `<p style="margin: 0 0 3px 0; font-size: 12px; color: #374151;"><i class="fas fa-phone" style="width: 16px;"></i> ${fornecedor.telefone}</p>`;
                if (fornecedor.email) html += `<p style="margin: 0; font-size: 12px; color: #374151;"><i class="fas fa-envelope" style="width: 16px;"></i> ${fornecedor.email}</p>`;
                html += `</div>`;
            }

            const totalProdutos = fornecedor.produtos ? fornecedor.produtos.length : 0;
            if (totalProdutos > 0) {
                html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 10px;"><p style="margin: 0; font-size: 11px; color: #6b7280;"><i class="fas fa-boxes"></i> <strong>${totalProdutos}</strong> produto(s)/serviço(s) encontrado(s)</p></div>`;
            }

            html += `</div>`;
        });

        html += `</div>`;
        resultadosArea.innerHTML = html;

        // Adicionar event listeners
        document.querySelectorAll('.card-fornecedor').forEach(card => {
            card.addEventListener('mouseenter', function() { this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)'; this.style.borderColor = '#3b82f6'; });
            card.addEventListener('mouseleave', function() { this.style.boxShadow = ''; this.style.borderColor = '#e5e7eb'; });
            card.addEventListener('click', function(e) {
                if (e.target.closest('.btn-ver-detalhes')) return;
                abrirModalDetalhes(fornecedores[parseInt(this.getAttribute('data-index'))]);
            });
        });

        document.querySelectorAll('.btn-ver-detalhes').forEach(btnDetalhes => {
            btnDetalhes.addEventListener('click', function(e) {
                e.stopPropagation();
                abrirModalDetalhes(fornecedores[parseInt(this.getAttribute('data-index'))]);
            });
        });
    }

    // ========== FIM DAS FUNCIONALIDADES DOS FILTROS ==========

    // Função para abrir modal com detalhes do fornecedor
    function abrirModalDetalhes(fornecedor) {
        // Preencher dados básicos
        document.getElementById('modal-cnpj').textContent = fornecedor.cnpj || '-';
        document.getElementById('modal-razao-social').textContent = fornecedor.razao_social || '-';
        document.getElementById('modal-nome-fantasia').textContent = fornecedor.nome_fantasia || '-';

        // Telefone - ocultar linha se não houver
        const rowTelefone = document.getElementById('row-telefone');
        if (fornecedor.telefone && fornecedor.telefone !== '-' && fornecedor.telefone.trim() !== '') {
            rowTelefone.style.display = '';
            document.getElementById('modal-telefone').textContent = fornecedor.telefone;
        } else {
            rowTelefone.style.display = 'none';
        }

        // E-mail - ocultar linha se não houver
        const rowEmail = document.getElementById('row-email');
        if (fornecedor.email && fornecedor.email !== '-' && fornecedor.email.trim() !== '') {
            rowEmail.style.display = '';
            document.getElementById('modal-email').textContent = fornecedor.email;
        } else {
            rowEmail.style.display = 'none';
        }

        // Endereço completo
        let endereco = '';
        if (fornecedor.logradouro) endereco += fornecedor.logradouro;
        if (fornecedor.numero) endereco += `, ${fornecedor.numero}`;
        if (fornecedor.complemento) endereco += ` - ${fornecedor.complemento}`;
        if (fornecedor.bairro) endereco += `<br>${fornecedor.bairro}`;
        if (fornecedor.cidade || fornecedor.uf) {
            endereco += `<br>${fornecedor.cidade || ''} - ${fornecedor.uf || ''}`;
        }
        if (fornecedor.cep) endereco += `<br>CEP: ${fornecedor.cep}`;
        document.getElementById('modal-endereco').innerHTML = endereco || '-';

        // Produtos/Contratos
        const produtosDiv = document.getElementById('modal-produtos');
        if (fornecedor.produtos && fornecedor.produtos.length > 0) {
            let produtosHtml = `<h6 style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 15px;">`;
            produtosHtml += `<i class="fas fa-boxes"></i> Produtos/Serviços Fornecidos (${fornecedor.produtos.length})`;
            produtosHtml += `</h6>`;
            produtosHtml += `<div style="max-height: 400px; overflow-y: auto; display: grid; gap: 10px;">`;

            fornecedor.produtos.forEach(produto => {
                produtosHtml += `<div style="background: #f9fafb; padding: 12px; border-radius: 6px; border-left: 3px solid #3b82f6;">`;
                produtosHtml += `<p style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937; font-weight: 500; line-height: 1.4;">${produto.descricao || '-'}</p>`;
                produtosHtml += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; font-size: 11px; color: #6b7280;">`;

                if (produto.valor) {
                    const valor = parseFloat(produto.valor);
                    if (!isNaN(valor)) {
                        produtosHtml += `<span><strong>Valor:</strong> R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
                    }
                }

                if (produto.unidade) {
                    produtosHtml += `<span><strong>Unidade:</strong> ${produto.unidade}</span>`;
                }

                if (produto.data) {
                    // Formatar data se vier em formato ISO
                    let dataFormatada = produto.data;
                    if (dataFormatada && dataFormatada.includes('-')) {
                        const partes = dataFormatada.split('-');
                        if (partes.length === 3) {
                            dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`;
                        }
                    }
                    produtosHtml += `<span><strong>Data:</strong> ${dataFormatada}</span>`;
                }

                if (produto.orgao) {
                    produtosHtml += `<span style="grid-column: 1 / -1;"><strong>Órgão:</strong> ${produto.orgao}</span>`;
                }

                produtosHtml += `</div>`;
                produtosHtml += `</div>`;
            });

            produtosHtml += `</div>`;
            produtosDiv.innerHTML = produtosHtml;
        } else {
            produtosDiv.innerHTML = `
                <p style="font-size: 12px; color: #6b7280; margin: 0;">
                    Nenhum produto/serviço registrado para este fornecedor.
                </p>
            `;
        }

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetalhesFornecedor'));
        modal.show();
    }
});
</script>

@endsection
