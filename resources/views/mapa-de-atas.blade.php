@extends('layouts.app')

@section('title', 'Mapa de Atas de Registro de Preços')

@section('content')
<div style="padding: 25px;">
    <!-- Título da Página -->
    <h1 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 25px;">
        MAPA DE ATAS DE REGISTRO DE PREÇOS
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
            <!-- Campo Descrição/Código (obrigatório) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                    Parte da descrição do item/serviço ou código CATMAT/CATSER:<span style="color: #ef4444;">*</span>
                </label>
                <input type="text" id="descricao_ata" placeholder="Digite a descrição ou código" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
            </div>

            <!-- UASG e Nome do Órgão (lado a lado) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                        UASG:
                    </label>
                    <input type="text" id="uasg" placeholder="Digite a UASG" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                        Nome do órgão:
                    </label>
                    <input type="text" id="nome_orgao" placeholder="Digite o nome do órgão" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                </div>
            </div>

            <!-- Botão Consultar -->
            <button type="button" id="btn-consultar" style="background: #3b82f6; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-search"></i>
                CONSULTAR
            </button>
        </div>
    </div>

    <!-- Layout 2 Colunas: Refinar + Resultados -->
    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 20px;">

        <!-- SEÇÃO 2: REFINAR PESQUISA (Esquerda) -->
        <div>
            <div style="background: #f3f4f6; padding: 20px; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="fas fa-filter" style="color: #6b7280;"></i>
                    <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                        REFINAR PESQUISA
                    </h2>
                </div>

                <!-- Filtros Avançados -->
                <div id="filtros-avancados" style="display: none;">
                    <!-- Período -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 6px;">
                            Período:
                        </label>
                        <select id="filtro_periodo" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                            <option value="30">Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                            <option value="180">Últimos 6 meses</option>
                            <option value="365" selected>Último ano (padrão)</option>
                        </select>
                    </div>

                    <!-- UF -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 6px;">
                            UF:
                        </label>
                        <select id="filtro_uf" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                            <option value="">Todas</option>
                            <option value="AC">AC - Acre</option>
                            <option value="AL">AL - Alagoas</option>
                            <option value="AP">AP - Amapá</option>
                            <option value="AM">AM - Amazonas</option>
                            <option value="BA">BA - Bahia</option>
                            <option value="CE">CE - Ceará</option>
                            <option value="DF">DF - Distrito Federal</option>
                            <option value="ES">ES - Espírito Santo</option>
                            <option value="GO">GO - Goiás</option>
                            <option value="MA">MA - Maranhão</option>
                            <option value="MT">MT - Mato Grosso</option>
                            <option value="MS">MS - Mato Grosso do Sul</option>
                            <option value="MG">MG - Minas Gerais</option>
                            <option value="PA">PA - Pará</option>
                            <option value="PB">PB - Paraíba</option>
                            <option value="PR">PR - Paraná</option>
                            <option value="PE">PE - Pernambuco</option>
                            <option value="PI">PI - Piauí</option>
                            <option value="RJ">RJ - Rio de Janeiro</option>
                            <option value="RN">RN - Rio Grande do Norte</option>
                            <option value="RS">RS - Rio Grande do Sul</option>
                            <option value="RO">RO - Rondônia</option>
                            <option value="RR">RR - Roraima</option>
                            <option value="SC">SC - Santa Catarina</option>
                            <option value="SP">SP - São Paulo</option>
                            <option value="SE">SE - Sergipe</option>
                            <option value="TO">TO - Tocantins</option>
                        </select>
                    </div>

                    <!-- Município -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 6px;">
                            Município:
                        </label>
                        <input type="text" id="filtro_municipio" placeholder="Nome do município" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                    </div>

                    <!-- Valor Mínimo/Máximo -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 6px;">
                            Faixa de Valor:
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <input type="number" id="filtro_valor_min" placeholder="Mín. (R$)" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                            <input type="number" id="filtro_valor_max" placeholder="Máx. (R$)" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                        </div>
                    </div>

                    <!-- Botão Aplicar Filtros -->
                    <button type="button" id="btn-aplicar-filtros" style="width: 100%; background: #059669; color: white; border: none; padding: 10px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-check"></i>
                        APLICAR FILTROS
                    </button>

                    <!-- Botão Limpar Filtros -->
                    <button type="button" id="btn-limpar-filtros" style="width: 100%; margin-top: 8px; background: #6b7280; color: white; border: none; padding: 8px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-times"></i>
                        LIMPAR FILTROS
                    </button>
                </div>

                <!-- Mensagem Inicial -->
                <p id="mensagem-inicial-filtros" style="font-size: 12px; color: #6b7280; line-height: 1.6; margin: 0;">
                    Quando você preencher o campo de busca em <strong>argumentos da pesquisa</strong> e depois clicar no <strong>botão pesquisar</strong>, o sistema disponibilizará mais opções de filtros.
                </p>
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
                    <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <p style="color: #9ca3af; font-size: 14px; margin: 0;">
                        Sem amostras para exibir.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Ver Detalhes -->
<div id="modal-detalhes" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="background: white; max-width: 900px; margin: 50px auto; border-radius: 8px; position: relative;">
        <!-- Header Modal -->
        <div style="background: #1f2937; color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600;">
                <i class="fas fa-file-contract"></i> DETALHES DO CONTRATO
            </h3>
            <button onclick="fecharModalDetalhes()" style="background: transparent; border: none; color: white; font-size: 20px; cursor: pointer; padding: 0; width: 30px; height: 30px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div id="modal-detalhes-body" style="padding: 30px; max-height: 70vh; overflow-y: auto;">
            <!-- Conteúdo carregado dinamicamente -->
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
let contratosFiltrados = [];

document.addEventListener('DOMContentLoaded', function() {
    // Botão Consultar
    document.getElementById('btn-consultar').addEventListener('click', buscarContratos);

    // Botão Aplicar Filtros
    document.getElementById('btn-aplicar-filtros').addEventListener('click', aplicarFiltros);

    // Botão Limpar Filtros
    document.getElementById('btn-limpar-filtros').addEventListener('click', limparFiltros);

    // Enter nos campos
    ['descricao_ata', 'uasg', 'nome_orgao'].forEach(id => {
        document.getElementById(id).addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarContratos();
            }
        });
    });
});

async function buscarContratos() {
    const descricao = document.getElementById('descricao_ata').value;
    const uasg = document.getElementById('uasg').value;
    const orgao = document.getElementById('nome_orgao').value;
    const resultadosArea = document.getElementById('area-resultados');
    const btn = document.getElementById('btn-consultar');

    // Validar - pelo menos um campo preenchido
    if (!descricao.trim() && !uasg.trim() && !orgao.trim()) {
        resultadosArea.innerHTML = `
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #fbbf24; margin-bottom: 15px;"></i>
            <p style="color: #92400e; font-size: 14px; margin: 0;">
                Por favor, preencha ao menos um filtro: <strong>Descrição, UASG ou Órgão</strong>.
            </p>
        `;
        return;
    }

    // Mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> CONSULTANDO...';
    resultadosArea.innerHTML = `
        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #3b82f6; margin-bottom: 15px;"></i>
        <p style="color: #1e40af; font-size: 14px; margin: 0; font-weight: 600;">
            Buscando contratos...
        </p>
    `;

    try {
        // Construir parâmetros
        const params = new URLSearchParams();
        if (descricao.trim()) params.append('descricao', descricao.trim());
        if (uasg.trim()) params.append('uasg', uasg.trim());
        if (orgao.trim()) params.append('cnpj_orgao', orgao.trim());

        // Adicionar filtros avançados (se existirem)
        const periodo = document.getElementById('filtro_periodo')?.value;
        if (periodo) params.append('periodo', periodo);

        const uf = document.getElementById('filtro_uf')?.value;
        if (uf) params.append('uf', uf);

        const municipio = document.getElementById('filtro_municipio')?.value;
        if (municipio && municipio.trim()) params.append('municipio', municipio.trim());

        const valorMin = document.getElementById('filtro_valor_min')?.value;
        if (valorMin) params.append('valor_min', valorMin);

        const valorMax = document.getElementById('filtro_valor_max')?.value;
        if (valorMax) params.append('valor_max', valorMax);

        // Buscar via backend (busca multi-fonte)
        const response = await fetch(`${window.APP_BASE_PATH}/mapa-de-atas/buscar?${params}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        const result = await response.json();

        if (result.success && result.contratos && result.contratos.length > 0) {
            // Armazenar contratos para filtros
            contratosFiltrados = result.contratos;

            // Mostrar filtros avançados
            document.getElementById('filtros-avancados').style.display = 'block';
            document.getElementById('mensagem-inicial-filtros').style.display = 'none';

            // Renderizar resultados
            renderizarResultados(result);

        } else {
            // Nenhum resultado
            let filtros = [];
            if (descricao.trim()) filtros.push(`Descrição: <strong>${descricao}</strong>`);
            if (uasg.trim()) filtros.push(`UASG: <strong>${uasg}</strong>`);
            if (orgao.trim()) filtros.push(`Órgão/CNPJ: <strong>${orgao}</strong>`);

            resultadosArea.innerHTML = `
                <i class="fas fa-search" style="font-size: 48px; color: #6b7280; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="color: #374151; font-size: 14px; margin-bottom: 10px;">
                    Nenhum contrato encontrado com os filtros:
                </p>
                <p style="color: #6b7280; font-size: 12px; margin: 0;">
                    ${filtros.join(' | ')}
                </p>
                <p style="color: #9ca3af; font-size: 12px; margin-top: 15px;">
                    Tente buscar com outros termos ou ajuste os filtros.
                </p>
            `;
        }

    } catch (error) {
        console.error('Erro ao buscar contratos:', error);
        resultadosArea.innerHTML = `
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
            <p style="color: #dc2626; font-size: 14px; margin: 0;">
                Erro ao consultar APIs. Tente novamente.
            </p>
            <p style="color: #9ca3af; font-size: 11px; margin-top: 8px;">
                ${error.message}
            </p>
        `;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> CONSULTAR';
    }
}

function renderizarResultados(result) {
    const resultadosArea = document.getElementById('area-resultados');
    let html = '<div style="width: 100%;">';

    // Header com total e fontes
    const fontes = result.fontes_consultadas || {};
    const totalPNCP = fontes.PNCP || 0;
    const totalComprasGov = fontes['COMPRAS.GOV'] || 0;
    const totalCMED = fontes.CMED || 0;

    html += `<div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; margin-bottom: 20px;">`;
    html += `<p style="margin: 0 0 8px 0; font-size: 14px; color: #065f46; font-weight: 600;">`;
    html += `<i class="fas fa-file-contract"></i> ${result.total} contrato(s) encontrado(s)`;
    html += `</p>`;
    html += `<p style="margin: 0 0 5px 0; font-size: 11px; color: #047857;">`;
    html += `Período: ${formatarData(result.periodo.inicio)} a ${formatarData(result.periodo.fim)} (${result.periodo.dias || 365} dias)`;
    html += `</p>`;
    html += `<div style="display: flex; gap: 10px; margin-top: 8px; flex-wrap: wrap;">`;
    if (totalPNCP > 0) {
        html += `<span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600;">PNCP: ${totalPNCP}</span>`;
    }
    if (totalComprasGov > 0) {
        html += `<span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600;">Compras.gov: ${totalComprasGov}</span>`;
    }
    if (totalCMED > 0) {
        html += `<span style="background: #fce7f3; color: #831843; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600;">CMED: ${totalCMED}</span>`;
    }
    html += `</div>`;
    html += `</div>`;

    // Listar contratos
    result.contratos.forEach((contrato, index) => {
        html += renderizarContrato(contrato, index);
    });

    html += `</div>`;
    resultadosArea.innerHTML = html;
}

function renderizarContrato(contrato, index) {
    let html = `<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 15px;">`;

    // Cabeçalho com Badge de Fonte
    html += `<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">`;
    html += `<div style="flex: 1;">`;

    // Badge da Fonte
    const badgeFonte = getBadgeFonte(contrato.fonte);
    html += `<div style="margin-bottom: 8px;">${badgeFonte}</div>`;

    html += `<h3 style="margin: 0 0 8px 0; font-size: 14px; color: #1f2937; font-weight: 600; line-height: 1.4;">${contrato.objeto}</h3>`;

    // Badges de categoria e tipo
    if (contrato.categoria) {
        const badgeCategoria = contrato.categoria === 'MEDICAMENTO' ?
            `<span style="background: #fce7f3; color: #831843; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-right: 6px;">💊 ${contrato.categoria}</span>` :
            `<span style="background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-right: 6px;">${contrato.categoria}</span>`;
        html += badgeCategoria;
    }

    if (contrato.modalidade_nome && contrato.fonte === 'PNCP') {
        html += `<span style="background: #f3f4f6; color: #4b5563; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 500;">${contrato.modalidade_nome}</span>`;
    }

    html += `<p style="margin: 8px 0 0 0; font-size: 11px; color: #6b7280;">`;
    html += `<strong>Nº Controle:</strong> ${contrato.numero_contrato}`;
    html += `</p>`;
    html += `</div>`;

    // Valor em destaque
    html += `<span style="background: #dbeafe; color: #1e40af; padding: 12px 16px; border-radius: 6px; font-size: 13px; font-weight: 700; white-space: nowrap;">`;
    html += `R$ ${parseFloat(contrato.valor_unitario || contrato.valor_global || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
    html += `</span>`;
    html += `</div>`;

    // Órgão
    html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 12px;">`;
    html += `<p style="margin: 0 0 6px 0; font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Órgão Contratante:</p>`;
    html += `<p style="margin: 0; font-size: 12px; color: #374151; font-weight: 500;">${contrato.orgao_nome}</p>`;
    if (contrato.orgao_cnpj) {
        html += `<p style="margin: 3px 0 0 0; font-size: 11px; color: #6b7280;">CNPJ: ${formatarCNPJ(contrato.orgao_cnpj)}</p>`;
    }
    if (contrato.orgao_municipio && contrato.orgao_uf) {
        html += `<p style="margin: 3px 0 0 0; font-size: 11px; color: #6b7280;"><i class="fas fa-map-marker-alt"></i> ${contrato.orgao_municipio}/${contrato.orgao_uf}</p>`;
    }
    if (contrato.uasg) {
        html += `<p style="margin: 3px 0 0 0; font-size: 11px; color: #6b7280;">UASG: ${contrato.uasg}</p>`;
    }
    html += `</div>`;

    // Fornecedor
    html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 12px;">`;
    html += `<p style="margin: 0 0 6px 0; font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Fornecedor:</p>`;
    html += `<p style="margin: 0; font-size: 12px; color: #374151; font-weight: 500;">${contrato.fornecedor_nome}</p>`;
    if (contrato.fornecedor_cnpj) {
        html += `<p style="margin: 3px 0 0 0; font-size: 11px; color: #6b7280;">CNPJ: ${formatarCNPJ(contrato.fornecedor_cnpj)}</p>`;
    }
    html += `</div>`;

    // Detalhes adicionais (quantidade, unidade, datas)
    if (contrato.quantidade || contrato.unidade_medida || contrato.data_publicacao_pncp) {
        html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 12px;">`;
        html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; font-size: 11px;">`;

        if (contrato.quantidade && contrato.quantidade > 1) {
            html += `<div><strong>Quantidade:</strong><br>${contrato.quantidade} ${contrato.unidade_medida || 'UN'}</div>`;
        }

        if (contrato.data_publicacao_pncp || contrato.data_assinatura) {
            html += `<div><strong>Data:</strong><br>${formatarData(contrato.data_publicacao_pncp || contrato.data_assinatura)}</div>`;
        }

        if (contrato.situacao) {
            const corSituacao = contrato.situacao.includes('VIGENTE') ? '#059669' : '#6b7280';
            html += `<div><strong>Situação:</strong><br><span style="color: ${corSituacao}; font-weight: 600;">${contrato.situacao}</span></div>`;
        }

        html += `</div>`;
        html += `</div>`;
    }

    // Ações
    html += `<div style="border-top: 1px solid #e5e7eb; padding-top: 12px; display: flex; gap: 10px; flex-wrap: wrap;">`;

    // Botão Ver Detalhes
    html += `<button onclick="abrirModalDetalhes(${index})" style="display: inline-flex; align-items: center; gap: 6px; background: #6366f1; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">`;
    html += `<i class="fas fa-info-circle"></i> VER DETALHES`;
    html += `</button>`;

    // Link PNCP (se existir)
    if (contrato.link_pncp) {
        html += `<a href="${contrato.link_pncp}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #3b82f6; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">`;
        html += `<i class="fas fa-external-link-alt"></i> PNCP`;
        html += `</a>`;
    }

    // Link Compras.gov - Painel de Preços
    if (contrato.fonte === 'COMPRAS.GOV' && contrato.link_ata) {
        html += `<a href="${contrato.link_ata}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #b45309; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">`;
        html += `<i class="fas fa-external-link-alt"></i> PAINEL DE PREÇOS`;
        html += `</a>`;
    }

    // Link CMED (se for medicamento)
    if (contrato.fonte === 'CMED' && contrato.link_edital) {
        html += `<a href="${contrato.link_edital}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #ec4899; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">`;
        html += `<i class="fas fa-external-link-alt"></i> ANVISA`;
        html += `</a>`;
    }

    html += `</div>`;

    html += `</div>`;
    return html;
}

function getBadgeFonte(fonte) {
    switch(fonte) {
        case 'PNCP':
            return `<span style="background: #1e40af; color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block;">🏛️ PNCP</span>`;
        case 'COMPRAS.GOV':
            return `<span style="background: #b45309; color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block;">📊 COMPRAS.GOV</span>`;
        case 'CMED':
            return `<span style="background: #9f1239; color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block;">💊 CMED/ANVISA</span>`;
        default:
            return `<span style="background: #6b7280; color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block;">${fonte}</span>`;
    }
}

function abrirModalDetalhes(index) {
    const contrato = contratosFiltrados[index];
    const modal = document.getElementById('modal-detalhes');
    const body = document.getElementById('modal-detalhes-body');

    let html = '';

    // Badge Fonte
    html += `<div style="margin-bottom: 20px;">${getBadgeFonte(contrato.fonte)}</div>`;

    // Título
    html += `<h4 style="margin: 0 0 20px 0; font-size: 16px; color: #1f2937; font-weight: 600; line-height: 1.5;">${contrato.objeto}</h4>`;

    // Grid de informações
    html += `<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">`;

    // Coluna 1
    html += `<div>`;
    html += gerarCampoDetalhe('Número do Contrato', contrato.numero_contrato);
    html += gerarCampoDetalhe('Tipo de Documento', contrato.tipo_documento);
    html += gerarCampoDetalhe('Categoria', contrato.categoria);
    html += gerarCampoDetalhe('Modalidade', contrato.modalidade_nome);
    html += gerarCampoDetalhe('Valor Global', formatarMoeda(contrato.valor_global));
    html += gerarCampoDetalhe('Valor Unitário', formatarMoeda(contrato.valor_unitario));
    html += gerarCampoDetalhe('Quantidade', contrato.quantidade);
    html += gerarCampoDetalhe('Unidade de Medida', contrato.unidade_medida);
    html += gerarCampoDetalhe('Situação', contrato.situacao);
    html += `</div>`;

    // Coluna 2
    html += `<div>`;
    html += gerarCampoDetalhe('Órgão', contrato.orgao_nome);
    html += gerarCampoDetalhe('CNPJ do Órgão', formatarCNPJ(contrato.orgao_cnpj));
    html += gerarCampoDetalhe('UF', contrato.orgao_uf);
    html += gerarCampoDetalhe('Município', contrato.orgao_municipio);
    html += gerarCampoDetalhe('UASG', contrato.uasg);
    html += gerarCampoDetalhe('Fornecedor', contrato.fornecedor_nome);
    html += gerarCampoDetalhe('CNPJ Fornecedor', formatarCNPJ(contrato.fornecedor_cnpj));
    html += `</div>`;

    html += `</div>`;

    // Datas (linha completa)
    html += `<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">`;
    html += `<h5 style="margin: 0 0 12px 0; font-size: 13px; color: #374151; font-weight: 600; text-transform: uppercase;">Datas</h5>`;
    html += `<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">`;
    html += gerarCampoDetalhe('Publicação PNCP', formatarData(contrato.data_publicacao_pncp));
    html += gerarCampoDetalhe('Assinatura', formatarData(contrato.data_assinatura));
    html += gerarCampoDetalhe('Vigência', formatarPeriodo(contrato.data_vigencia_inicio, contrato.data_vigencia_fim));
    html += `</div>`;
    html += `</div>`;

    // Auditoria
    html += `<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">`;
    html += `<h5 style="margin: 0 0 12px 0; font-size: 13px; color: #374151; font-weight: 600; text-transform: uppercase;">Auditoria</h5>`;
    html += gerarCampoDetalhe('Hash SHA-256', `<code style="font-size: 10px; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; display: block; overflow-wrap: break-word;">${contrato.hash_sha256}</code>`);
    html += gerarCampoDetalhe('Coletado em', contrato.coletado_em);
    html += `</div>`;

    // Links (contextuais por fonte)
    if (contrato.link_pncp || contrato.link_edital || contrato.link_ata) {
        html += `<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px;">`;

        if (contrato.link_pncp) {
            html += `<a href="${contrato.link_pncp}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #3b82f6; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600;"><i class="fas fa-external-link-alt"></i> Ver no PNCP</a>`;
        }

        if (contrato.link_edital) {
            const textoEdital = contrato.fonte === 'CMED' ? 'Portal ANVISA' : 'Ver Edital';
            html += `<a href="${contrato.link_edital}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #059669; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600;"><i class="fas fa-file-alt"></i> ${textoEdital}</a>`;
        }

        if (contrato.link_ata) {
            const textoAta = contrato.fonte === 'COMPRAS.GOV' ? 'Painel de Preços' : (contrato.fonte === 'CMED' ? 'Consulta ANVISA' : 'Ver ATA');
            const corAta = contrato.fonte === 'COMPRAS.GOV' ? '#b45309' : '#7c3aed';
            html += `<a href="${contrato.link_ata}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: ${corAta}; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600;"><i class="fas fa-chart-line"></i> ${textoAta}</a>`;
        }

        html += `</div>`;
    }

    body.innerHTML = html;
    modal.style.display = 'block';
}

function fecharModalDetalhes() {
    document.getElementById('modal-detalhes').style.display = 'none';
}

function gerarCampoDetalhe(label, valor) {
    if (!valor || valor === 'null' || valor === 'N/A') return '';
    return `
        <div style="margin-bottom: 12px;">
            <p style="margin: 0 0 3px 0; font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase;">${label}:</p>
            <p style="margin: 0; font-size: 13px; color: #1f2937;">${valor}</p>
        </div>
    `;
}

function aplicarFiltros() {
    buscarContratos();
}

function limparFiltros() {
    document.getElementById('filtro_periodo').value = '365';
    document.getElementById('filtro_uf').value = '';
    document.getElementById('filtro_municipio').value = '';
    document.getElementById('filtro_valor_min').value = '';
    document.getElementById('filtro_valor_max').value = '';
    buscarContratos();
}

function formatarCNPJ(cnpj) {
    if (!cnpj) return '-';
    cnpj = cnpj.replace(/\D/g, '');
    if (cnpj.length === 14) {
        return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }
    return cnpj;
}

function formatarData(data) {
    if (!data || data === 'null') return '-';
    try {
        if (data.includes('-')) {
            const [ano, mes, dia] = data.split(' ')[0].split('-');
            return `${dia}/${mes}/${ano}`;
        }
        return data;
    } catch {
        return data;
    }
}

function formatarPeriodo(inicio, fim) {
    const dataInicio = formatarData(inicio);
    const dataFim = formatarData(fim);
    if (dataInicio === '-' && dataFim === '-') return '-';
    return `${dataInicio} a ${dataFim}`;
}

function formatarMoeda(valor) {
    if (!valor || valor === 0) return 'R$ 0,00';
    return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Fechar modal ao clicar fora
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-detalhes');
    if (e.target === modal) {
        fecharModalDetalhes();
    }
});
</script>

@endsection
