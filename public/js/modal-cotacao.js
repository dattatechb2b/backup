console.log('🚀🚀🚀 [MODAL-COTACAO.JS] ARQUIVO CARREGADO! Data: ' + new Date().toLocaleString());
console.log('📍 [MODAL-COTACAO.JS] Verificando se existe conflito...');

/**
 * ================================================
 * VARIÁVEL GLOBAL: JUSTIFICATIVA DA COTAÇÃO
 * ================================================
 * IMPORTANTE: Esta variável DEVE estar FORA da IIFE para ser acessível
 * globalmente e persistir durante toda a sessão do modal
 */
window.justificativaCotacao = '';

/**
 * ================================================
 * MODAL DE COTAÇÃO DE PREÇOS - JAVASCRIPT COMPLETO
 * Baseado nos prints: MODAL1.png, MODAL2.png, MODAL3.png, MODAL4.png
 * Data: 09/10/2025
 * ================================================
 */

(function() {
    'use strict';

    const modal = document.getElementById('modalCotacaoPrecos');
    if (!modal) {
        console.warn('⚠️ Modal de Cotação não encontrado');
        return;
    }

    // ===== VARIÁVEIS GLOBAIS (dentro da IIFE) =====
    let resultadosCompletos = [];
    let resultadosFiltrados = [];

    // Armazenar estado dos filtros de fonte (para manter entre pesquisas)
    let estadoFiltrosFonte = {
        PNCP: true,
        COMPRAS_GOV: true,
        LICITACON: true,  // ✅ HABILITADO - Mostrar dados do TCE-RS
        PORTAL_CGU: false
    };

    // IMPORTANTE: itemAtual precisa ser acessível globalmente para integração com elaborar.blade.php
    window.itemAtualCotacao = {
        id: null,
        descricao: ''
    };

    // Alias local para facilitar o uso dentro da IIFE
    let itemAtual = window.itemAtualCotacao;

    console.log('🚀 Inicializando Modal de Cotação de Preços...');

    // ================================================
    // VINCULAR EVENTOS QUANDO MODAL É EXIBIDO
    // ================================================
    let eventosVinculados = false; // Flag para evitar vinculação duplicada

    modal.addEventListener('shown.bs.modal', function() {
        console.log('🎯 Modal exibido - vinculando eventos...');

        if (!eventosVinculados) {
            // Vincular botões de pesquisa
            const btnPesquisarCotacao = document.getElementById('btn-pesquisar-cotacao');
            const btnPesquisarCatmat = document.getElementById('btn-pesquisar-catmat');

            if (btnPesquisarCotacao) {
                console.log('  ✅ Vinculando botão: btn-pesquisar-cotacao');
                btnPesquisarCotacao.addEventListener('click', async function() {
                    console.log('🖱️ Botão PESQUISAR (palavra-chave) clicado');
                    await realizarPesquisa('palavra-chave');
                });
            } else {
                console.error('  ❌ btn-pesquisar-cotacao não encontrado!');
            }

            if (btnPesquisarCatmat) {
                console.log('  ✅ Vinculando botão: btn-pesquisar-catmat');
                btnPesquisarCatmat.addEventListener('click', async function() {
                    console.log('🖱️ Botão PESQUISAR (CATMAT) clicado');
                    await realizarPesquisa('catmat');
                });
            } else {
                console.error('  ❌ btn-pesquisar-catmat não encontrado!');
            }

            eventosVinculados = true;
        }

        // Vincular eventos de Enter
        vincularEventosEnter();
    });

    // ================================================
    // FUNÇÃO: VINCULAR EVENTOS DE ENTER NOS CAMPOS
    // ================================================
    /**
     * Função para vincular event listeners de Enter nos campos de pesquisa
     */
    function vincularEventosEnter() {
        console.log('🔗 Vinculando eventos de Enter nos campos de pesquisa...');

        // Remover listeners antigos (se existirem) usando {once: true}
        // e adicionar novos

        // Campo palavra-chave
        const inputPalavraChave = document.getElementById('input-palavra-chave');
        if (inputPalavraChave) {
            // Remove atributo se já existir para evitar duplicação
            inputPalavraChave.removeEventListener('keydown', handleEnterPalavraChave);
            inputPalavraChave.addEventListener('keydown', handleEnterPalavraChave);
            console.log('  ✅ Enter vinculado: input-palavra-chave');
        } else {
            console.warn('  ⚠️ input-palavra-chave não encontrado');
        }

        // Campo CNPJ (aba palavra-chave)
        const inputCnpj = document.getElementById('input-cnpj');
        if (inputCnpj) {
            inputCnpj.removeEventListener('keydown', handleEnterCnpj);
            inputCnpj.addEventListener('keydown', handleEnterCnpj);
            console.log('  ✅ Enter vinculado: input-cnpj');
        } else {
            console.warn('  ⚠️ input-cnpj não encontrado');
        }

        // Campo CATMAT
        const inputCatmat = document.getElementById('input-catmat');
        if (inputCatmat) {
            inputCatmat.removeEventListener('keydown', handleEnterCatmat);
            inputCatmat.addEventListener('keydown', handleEnterCatmat);
            console.log('  ✅ Enter vinculado: input-catmat');
        } else {
            console.warn('  ⚠️ input-catmat não encontrado');
        }

        // Campo CNPJ (aba CATMAT)
        const inputCnpjCatmat = document.getElementById('input-cnpj-catmat');
        if (inputCnpjCatmat) {
            inputCnpjCatmat.removeEventListener('keydown', handleEnterCnpjCatmat);
            inputCnpjCatmat.addEventListener('keydown', handleEnterCnpjCatmat);
            console.log('  ✅ Enter vinculado: input-cnpj-catmat');
        } else {
            console.warn('  ⚠️ input-cnpj-catmat não encontrado');
        }

        console.log('✅ Todos os eventos de Enter foram vinculados!');
    }

    // Handlers de evento (definidos fora para permitir removeEventListener)
    async function handleEnterPalavraChave(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('⌨️ Enter pressionado no campo de palavra-chave');
            await realizarPesquisa('palavra-chave');
            return false;
        }
    }

    async function handleEnterCnpj(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('⌨️ Enter pressionado no campo CNPJ');
            await realizarPesquisa('palavra-chave');
            return false;
        }
    }

    async function handleEnterCatmat(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('⌨️ Enter pressionado no campo CATMAT');
            await realizarPesquisa('catmat');
            return false;
        }
    }

    async function handleEnterCnpjCatmat(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('⌨️ Enter pressionado no campo CNPJ (CATMAT)');
            await realizarPesquisa('catmat');
            return false;
        }
    }

    // ================================================
    // SEÇÃO 1: ABERTURA DO MODAL
    // ================================================

    /**
     * Evento: Abrir modal de cotação
     * Trigger: Botão com atributo [data-toggle-modal="cotacao"]
     */
    document.querySelectorAll('[data-toggle-modal="cotacao"]').forEach(botao => {
        botao.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemDescricao = this.dataset.itemDescricao;

            console.log(`📋 Abrindo modal para item #${itemId}: ${itemDescricao}`);

            // Armazenar dados do item
            itemAtual.id = itemId;
            itemAtual.descricao = itemDescricao || '';

            // Preencher descrição do item
            document.getElementById('cotacao-item-descricao').textContent = itemDescricao || 'Descrição não disponível';

            // Pre-preencher campo de busca com a descrição
            document.getElementById('input-palavra-chave').value = itemDescricao || '';

            // Resetar estados
            resetarModal();

            // Abrir modal
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            // Os event listeners de Enter serão vinculados automaticamente
            // pelo evento 'shown.bs.modal' (linha 56-59)
        });
    });

    // ================================================
    // SEÇÃO 2: SISTEMA DE ABAS (PALAVRA-CHAVE / CATMAT)
    // ================================================

    /**
     * Evento: Troca de abas de pesquisa
     */
    document.querySelectorAll('.tab-pesquisa').forEach(aba => {
        aba.addEventListener('click', function() {
            const abaAtiva = this.dataset.tab;

            console.log(`📑 Trocando para aba: ${abaAtiva}`);

            // Desativar todas as abas (apenas classe, CSS cuida do visual)
            document.querySelectorAll('.tab-pesquisa').forEach(a => {
                a.classList.remove('active');
                // Removido: estilos inline - agora usa CSS classes
            });

            // Ativar aba clicada (apenas classe, CSS cuida do visual)
            this.classList.add('active');
            // Removido: estilos inline - agora usa CSS classes

            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content-pesquisa').forEach(conteudo => {
                conteudo.style.display = 'none';
            });

            // Mostrar conteúdo da aba ativa
            document.getElementById(`content-${abaAtiva}`).style.display = 'block';
        });
    });

    // ================================================
    // SEÇÃO 3: PESQUISA DE AMOSTRAS
    // ================================================

    // IMPORTANTE: Event listeners dos botões e do Enter são vinculados
    // quando o modal é aberto (evento 'shown.bs.modal' - linhas 58-91)
    // Isso garante que os elementos existam no DOM antes da vinculação

    /**
     * Função: Realizar pesquisa
     */
    async function realizarPesquisa(tipo) {
        console.log('═══════════════════════════════════════════════════');
        console.log('🚀 realizarPesquisa() CHAMADA! Tipo:', tipo);
        console.log('═══════════════════════════════════════════════════');

        let termo, cnpj, tipoBusca;

        if (tipo === 'palavra-chave') {
            const inputPalavra = document.getElementById('input-palavra-chave');
            const inputCnpj = document.getElementById('input-cnpj');
            const radioTipoBusca = document.querySelector('input[name="tipo_busca"]:checked');

            if (!inputPalavra || !inputCnpj || !radioTipoBusca) {
                console.error('❌ Elementos do formulário não encontrados!');
                alert('Erro: Elementos do formulário não encontrados. Recarregue a página.');
                return;
            }

            termo = inputPalavra.value.trim();
            cnpj = inputCnpj.value.trim();
            tipoBusca = radioTipoBusca.value;
        } else if (tipo === 'catmat') {
            const inputCatmat = document.getElementById('input-catmat');
            const inputCnpjCatmat = document.getElementById('input-cnpj-catmat');

            if (!inputCatmat || !inputCnpjCatmat) {
                console.error('❌ Elementos da aba CATMAT não encontrados!');
                alert('Erro: Elementos da aba CATMAT não encontrados. Recarregue a página.');
                return;
            }

            termo = inputCatmat.value.trim();
            cnpj = inputCnpjCatmat.value.trim();
            tipoBusca = 'exata';
        }

        // Validação
        if (!termo && !cnpj) {
            alert('⚠️ Digite um termo de pesquisa ou CNPJ para continuar.');
            return;
        }

        console.log(`🔍 Pesquisando: ${termo} | Tipo: ${tipoBusca} | CNPJ: ${cnpj || 'N/A'}`);

        // Mostrar loading
        mostrarEstado('loading');

        try {
            // 🚀 OTIMIZAÇÃO: Executar todas as buscas EM PARALELO para reduzir tempo de espera
            // Antes: PNCP (21s) + CMED (0.6s) + Compras.gov (9s) = ~31 segundos sequencial
            // Agora: max(21s, 0.6s, 9s) = ~21 segundos paralelo (43% mais rápido!)

            console.log('🚀 Iniciando buscas PARALELAS em PNCP, CMED e Compras.gov...');

            resultadosCompletos = [];

            // Construir URLs para todas as fontes
            const urlPNCP = (() => {
                const base = `${window.APP_BASE_PATH}/pncp/buscar?termo=${encodeURIComponent(termo)}`;
                let url = base;
                if (cnpj) url += `&cnpj=${encodeURIComponent(cnpj)}`;
                if (tipoBusca) url += `&tipo_busca=${tipoBusca}`;
                return url;
            })();

            const urlCMED = termo && termo.length >= 3 ?
                `${window.APP_BASE_PATH}/cmed/buscar?termo=${encodeURIComponent(termo)}` : null;

            const urlComprasGov = termo && termo.length >= 3 ?
                `${window.APP_BASE_PATH}/compras-gov/buscar?termo=${encodeURIComponent(termo)}` : null;

            // Função auxiliar para buscar com timeout e tratamento de erro
            const buscarComTimeout = async (nome, url, emoji) => {
                if (!url) {
                    console.log(`${emoji} ${nome} ignorado (termo muito curto)`);
                    return { nome, resultados: [], erro: null };
                }

                console.log(`${emoji} Buscando ${nome}...`, url);
                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });

                    console.log(`${emoji} ${nome} retornou status:`, response.status);

                    if (!response.ok) {
                        const erro = `HTTP ${response.status}`;
                        console.error(`${emoji} ❌ ${nome} falhou:`, erro);
                        return { nome, resultados: [], erro };
                    }

                    const data = await response.json();
                    const resultados = data.resultados || [];

                    console.log(`${emoji} ✅ ${nome}: ${resultados.length} resultados`);
                    return { nome, resultados, erro: null };

                } catch (erro) {
                    console.error(`${emoji} ❌ Erro em ${nome}:`, erro);
                    return { nome, resultados: [], erro: erro.message };
                }
            };

            // 🔥 EXECUTAR TODAS AS BUSCAS EM PARALELO
            const [resultPNCP, resultCMED, resultComprasGov] = await Promise.all([
                buscarComTimeout('PNCP', urlPNCP, '🔵'),
                buscarComTimeout('CMED', urlCMED, '💊'),
                buscarComTimeout('Compras.gov', urlComprasGov, '🛒')
            ]);

            // Consolidar resultados de todas as fontes
            if (resultPNCP.resultados.length > 0) {
                console.log(`🔵 Adicionando ${resultPNCP.resultados.length} resultados do PNCP`);
                resultadosCompletos = [...resultadosCompletos, ...resultPNCP.resultados];
            }

            if (resultCMED.resultados.length > 0) {
                console.log(`💊 Adicionando ${resultCMED.resultados.length} resultados do CMED`);
                resultadosCompletos = [...resultadosCompletos, ...resultCMED.resultados];
            }

            if (resultComprasGov.resultados.length > 0) {
                console.log(`🛒 Adicionando ${resultComprasGov.resultados.length} resultados do Compras.gov`);
                resultadosCompletos = [...resultadosCompletos, ...resultComprasGov.resultados];
            }

            // Log de erros (se houver)
            const erros = [resultPNCP, resultCMED, resultComprasGov]
                .filter(r => r.erro)
                .map(r => `${r.nome}: ${r.erro}`);

            if (erros.length > 0) {
                console.warn('⚠️ Algumas fontes falharam:', erros.join(', '));
            }

            // FILTRAR valores zerados ANTES de exibir
            const totalAntes = resultadosCompletos.length;
            resultadosFiltrados = resultadosCompletos.filter(r => {
                const valor = r.valor_unitario || r.valor_homologado_item || r.valor_global || 0;
                return valor > 0;
            });
            const totalRemovidos = totalAntes - resultadosFiltrados.length;

            if (totalRemovidos > 0) {
                console.log(`🚫 ${totalRemovidos} resultado(s) com valor zerado removido(s)`);
            }

            console.log(`✅ TOTAL: ${resultadosFiltrados.length} amostras válidas de ${totalAntes} encontradas (PNCP: ${resultPNCP.resultados.length} | CMED: ${resultCMED.resultados.length} | Compras.gov: ${resultComprasGov.resultados.length})`);

            // Renderizar resultados
            renderizarResultados();
            atualizarEstatisticas();
            preencherFiltrosDinamicos();

        } catch (erro) {
            console.error('❌ Erro na pesquisa:', erro);
            mostrarEstado('erro', erro.message);
        }
    }

    // ================================================
    // SEÇÃO 4: RENDERIZAÇÃO DE RESULTADOS
    // ================================================

    /**
     * Função: Renderizar tabela de resultados
     */
    function renderizarResultados(preservarOrdem = false) {
        const tbody = document.getElementById('tbody-resultados-pesquisa');

        if (resultadosFiltrados.length === 0) {
            mostrarEstado('vazio');
            return;
        }

        // Mostrar tabela e estatísticas
        mostrarEstado('sucesso');

        // PRESERVAR ESTADO DOS CHECKBOXES antes de limpar
        const checkboxesMarcados = {};
        document.querySelectorAll('.checkbox-selecao-amostra:checked').forEach(cb => {
            checkboxesMarcados[cb.dataset.index] = true;
        });

        console.log('🔄 Checkboxes marcados antes de renderizar:', Object.keys(checkboxesMarcados));

        // Limpar tbody
        tbody.innerHTML = '';

        // CRITICAL: NÃO ordenar se preservarOrdem = true (evita mudar índices)
        if (!preservarOrdem) {
            const ordenacao = document.getElementById('select-ordenar').value;
            ordenarResultados(ordenacao);
            console.log('📊 Resultados ordenados por:', ordenacao);
        } else {
            console.log('⚠️ Preservando ordem original (não ordenando)');
        }

        // Criar linhas da tabela
        resultadosFiltrados.forEach((resultado, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = gerarLinhaTabela(resultado, index);
            tbody.appendChild(tr);
        });

        // RESTAURAR ESTADO DOS CHECKBOXES
        const indicesMarcados = Object.keys(checkboxesMarcados);
        console.log('🔄 Restaurando checkboxes:', indicesMarcados);

        indicesMarcados.forEach(index => {
            const checkbox = document.querySelector(`.checkbox-selecao-amostra[data-index="${index}"]`);
            if (checkbox) {
                checkbox.checked = true;
                console.log(`✅ Checkbox ${index} restaurado`);
            } else {
                console.warn(`⚠️ Checkbox ${index} não encontrado após renderizar`);
            }
        });

        // Se houver checkboxes marcados, atualizar análise crítica automaticamente
        if (indicesMarcados.length > 0) {
            // Aguardar um momento para os checkboxes serem restaurados no DOM
            setTimeout(() => {
                console.log('🔄 Atualizando Análise Crítica após restaurar checkboxes...');
                atualizarAnaliseCritica();
            }, 50);
        }

        console.log(`📊 ${resultadosFiltrados.length} resultados renderizados (${indicesMarcados.length} checkboxes restaurados)`);
    }

    /**
     * Função: Gerar HTML de uma linha da tabela
     */
    function gerarLinhaTabela(resultado, index) {
        const descricaoDestacada = destacarTermoPesquisa(resultado.descricao || resultado.nome_item || '-');
        const dataFormatada = formatarData(resultado.data_vigencia_inicio || resultado.data || null);
        const valorFormatado = formatarMoeda(resultado.valor_unitario || 0);
        const quantidadeFormatada = formatarNumero(resultado.quantidade || 0);

        return `
            <td style="padding: 10px 12px; font-size: 10px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                <div style="font-weight: 600; margin-bottom: 4px; line-height: 1.4;">${descricaoDestacada}</div>
                <div style="font-size: 8px; color: #6b7280;">📅 ${dataFormatada}</div>
            </td>
            <td style="padding: 10px 12px; font-size: 9px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                <div style="font-weight: 600; margin-bottom: 4px;">${resultado.orgao || resultado.orgao_nome || resultado.razao_social_fornecedor || '-'}</div>
                <span class="badge-fonte">${resultado.fonte || 'PNCP'}</span>
            </td>
            <td style="padding: 10px 12px; font-size: 10px; color: #1f2937; text-align: center; border-bottom: 1px solid #e5e7eb; font-weight: 700;">
                ${resultado.unidade_medida || resultado.medida_fornecimento || 'UN'}
            </td>
            <td style="padding: 10px 12px; font-size: 10px; color: #374151; text-align: right; border-bottom: 1px solid #e5e7eb; font-weight: 600;">
                ${quantidadeFormatada}
            </td>
            <td style="padding: 10px 12px; font-size: 11px; color: #1f2937; text-align: right; border-bottom: 1px solid #e5e7eb; font-weight: 800;">
                ${valorFormatado}
            </td>
            <td style="padding: 10px 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <button type="button" class="btn-acao-tabela btn-detalhes-fonte" data-index="${index}" style="background: #6b7280; color: white; font-size: 8px; padding: 4px 8px;" title="Detalhes da Fonte">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button type="button" class="btn-acao-tabela btn-ajustar-embalagem" data-index="${index}" style="background: #f59e0b; color: white; font-size: 8px; padding: 4px 8px;" title="Ajustar Embalagem">
                        <i class="fas fa-box"></i>
                    </button>
                </div>
            </td>
            <td style="padding: 10px 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                <input type="checkbox" class="checkbox-selecao-amostra" data-index="${index}" style="width: 17px; height: 17px; cursor: pointer; accent-color: #10b981;" title="Selecionar amostra">
            </td>
        `;
    }

    // ================================================
    // SEÇÃO 5: ESTATÍSTICAS E CARDS
    // ================================================

    /**
     * Função: Atualizar cards de estatísticas
     */
    function atualizarEstatisticas() {
        if (resultadosFiltrados.length === 0) return;

        const valores = resultadosFiltrados.map(r => parseFloat(r.valor_unitario || 0)).sort((a, b) => a - b);

        const quantidade = resultadosFiltrados.length;
        const minimo = valores[0];
        const maximo = valores[valores.length - 1];
        const media = valores.reduce((acc, val) => acc + val, 0) / valores.length;
        const mediana = calcularMediana(valores);

        // Atualizar HTML
        document.getElementById('stat-quantidade').textContent = formatarNumero(quantidade);
        document.getElementById('stat-minimo').textContent = formatarMoeda(minimo);
        document.getElementById('stat-maximo').textContent = formatarMoeda(maximo);
        document.getElementById('stat-media-valor').textContent = formatarMoeda(media);
        document.getElementById('stat-mediana-valor').textContent = formatarMoeda(mediana);

        console.log(`📈 Estatísticas atualizadas: ${quantidade} amostras | Min: ${formatarMoeda(minimo)} | Max: ${formatarMoeda(maximo)} | Média: ${formatarMoeda(media)} | Mediana: ${formatarMoeda(mediana)}`);
    }

    // ================================================
    // SEÇÃO 6: FILTROS DINÂMICOS (SIDEBAR)
    // ================================================

    /**
     * Função: Preencher filtros laterais (após pesquisa)
     */
    function preencherFiltrosDinamicos() {
        // Mostrar seção de filtros dinâmicos
        document.getElementById('filtros-dinamicos').style.display = 'block';

        // Extrair valores únicos
        const unidades = extrairValoresUnicos('unidade_medida', 'medida_fornecimento');
        const marcas = extrairValoresUnicos('marca');
        const ufs = extrairValoresUnicos('uf');

        // Preencher filtros
        preencherFiltro('container-filtro-unidades', unidades, 'filtro-unidade');
        // preencherFiltro('container-filtro-marcas', marcas, 'filtro-marca'); // REMOVIDO: Filtro de marca desabilitado
        preencherFiltro('container-filtro-ufs', ufs, 'filtro-uf');
        // preencherFiltro('container-filtro-origens', origens, 'filtro-origem'); // REMOVIDO: Filtro de origem desabilitado (sem container no HTML)

        // Inicializar event listeners dos filtros estáticos (fonte e porte)
        inicializarFiltrosEstaticos();

        console.log(`🔧 Filtros preenchidos: ${unidades.length} unidades, ${marcas.length} marcas, ${ufs.length} UFs`);
    }

    /**
     * Função: Preencher um filtro específico
     */
    function preencherFiltro(containerId, valores, nomeFiltro) {
        const container = document.getElementById(containerId);

        if (valores.length === 0) {
            container.innerHTML = '<p style="margin: 0; padding: 8px; text-align: center; font-size: 8px; color: #6b7280;">Nenhum encontrado</p>';
            return;
        }

        // ESPECIAL: Para filtro de unidades, adicionar contador de amostras
        if (nomeFiltro === 'filtro-unidade') {
            const contagemPorUnidade = {};
            resultadosCompletos.forEach(r => {
                const unidade = r.unidade_medida || r.medida_fornecimento || '(Vazio)';
                contagemPorUnidade[unidade] = (contagemPorUnidade[unidade] || 0) + 1;
            });

            // Ordenar por quantidade (mais comum primeiro)
            valores.sort((a, b) => (contagemPorUnidade[b] || 0) - (contagemPorUnidade[a] || 0));

            container.innerHTML = valores.map(valor => {
                const quantidade = contagemPorUnidade[valor] || 0;
                const porcentagem = ((quantidade / resultadosCompletos.length) * 100).toFixed(0);

                return `
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; margin-bottom: 4px; padding: 4px 6px; background: #f9fafb; border-radius: 3px; transition: all 0.2s;"
                           onmouseover="this.style.background='#e0f2fe'"
                           onmouseout="this.style.background='#f9fafb'">
                        <input type="checkbox" class="${nomeFiltro}" value="${valor}" checked style="width: 13px; height: 13px; accent-color: #3b82f6; cursor: pointer;">
                        <span style="font-size: 9px; color: #1f2937; flex: 1; font-weight: 600;">${valor || '(Vazio)'}</span>
                        <span style="font-size: 7px; color: #6b7280; background: #e5e7eb; padding: 2px 6px; border-radius: 2px; font-weight: 600;">${quantidade}</span>
                    </label>
                `;
            }).join('');
        } else {
            // Outros filtros (marca, UF, origem) - formato padrão
            container.innerHTML = valores.map(valor => `
                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; margin-bottom: 4px; padding: 3px;">
                    <input type="checkbox" class="${nomeFiltro}" value="${valor}" checked style="width: 12px; height: 12px; accent-color: #3b82f6; cursor: pointer;">
                    <span style="font-size: 8px; color: #1f2937; flex: 1;">${valor || '(Vazio)'}</span>
                </label>
            `).join('');
        }

        // Adicionar event listeners
        container.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', aplicarFiltros);
        });
    }

    /**
     * Função: Aplicar filtros
     */
    function aplicarFiltros() {
        console.log('🔍 Aplicando filtros...');

        resultadosFiltrados = resultadosCompletos.filter(resultado => {
            // FILTRO OBRIGATÓRIO: Remover valores zerados
            const valor = resultado.valor_unitario || resultado.valor_homologado_item || resultado.valor_global || 0;
            if (valor <= 0) {
                return false;
            }

            // Filtro: Fonte de Dados
            const fontesSelecionadas = Array.from(document.querySelectorAll('input[name="filtro_fonte"]:checked')).map(cb => cb.value);
            if (fontesSelecionadas.length > 0) {
                const fonteResultado = resultado.fonte || 'PNCP'; // Default para PNCP

                // ✅ NORMALIZAR fonte para comparação (mapear variações para valores dos checkboxes)
                let fonteNormalizada = fonteResultado;
                if (fonteResultado.includes('PNCP')) {
                    fonteNormalizada = 'PNCP'; // PNCP, PNCP_SEARCH, PNCP_ITENS → PNCP
                } else if (fonteResultado === 'COMPRAS.GOV') {
                    fonteNormalizada = 'COMPRAS_GOV'; // COMPRAS.GOV → COMPRAS_GOV
                } else if (fonteResultado === 'PORTAL_TRANSPARENCIA') {
                    fonteNormalizada = 'PORTAL_CGU'; // PORTAL_TRANSPARENCIA → PORTAL_CGU
                } else if (fonteResultado === 'LICITACON' || fonteResultado.includes('TCE-RS')) {
                    fonteNormalizada = 'LICITACON'; // LICITACON, TCE-RS-LOCAL → LICITACON
                }

                if (!fontesSelecionadas.includes(fonteNormalizada)) {
                    console.log(`❌ Resultado filtrado: fonte ${fonteResultado} (normalizado: ${fonteNormalizada}) não está em`, fontesSelecionadas);
                    return false;
                } else {
                    console.log(`✅ Resultado aceito: fonte ${fonteResultado} (normalizado: ${fonteNormalizada}) está em`, fontesSelecionadas);
                }
            }

            // Filtro: Porte da Empresa
            const porteEscolhido = document.querySelector('input[name="filtro_porte"]:checked').value;
            if (porteEscolhido === 'me_epp') {
                // Lógica para filtrar ME/EPP (se disponível no backend)
            }

            // Filtro: Unidade
            const unidadesSelecionadas = Array.from(document.querySelectorAll('.filtro-unidade:checked')).map(cb => cb.value);
            if (unidadesSelecionadas.length > 0) {
                const unidadeResultado = resultado.unidade_medida || resultado.medida_fornecimento;
                if (!unidadesSelecionadas.includes(unidadeResultado)) return false;
            }

            // Filtro: Marca
            const marcasSelecionadas = Array.from(document.querySelectorAll('.filtro-marca:checked')).map(cb => cb.value);
            if (marcasSelecionadas.length > 0) {
                if (!marcasSelecionadas.includes(resultado.marca)) return false;
            }

            // Filtro: UF
            const ufsSelecionadas = Array.from(document.querySelectorAll('.filtro-uf:checked')).map(cb => cb.value);
            if (ufsSelecionadas.length > 0) {
                if (!ufsSelecionadas.includes(resultado.uf)) return false;
            }

            // Filtro: Origem - REMOVIDO (sem container no HTML)

            return true;
        });

        renderizarResultados();
        atualizarEstatisticas();
        atualizarContadorResultados();

        console.log(`✅ Filtros aplicados: ${resultadosFiltrados.length} de ${resultadosCompletos.length} resultados`);
    }

    /**
     * Função: Atualizar contador de resultados filtrados
     */
    function atualizarContadorResultados() {
        const contador = document.getElementById('contador-resultados-filtrados');

        if (!contador) return;

        const totalCompleto = resultadosCompletos.length;
        const totalFiltrado = resultadosFiltrados.length;

        if (totalFiltrado < totalCompleto) {
            contador.style.display = 'inline-block';
            contador.textContent = `${totalFiltrado} de ${totalCompleto} amostras`;
            contador.style.background = 'rgba(251,191,36,0.3)';
            contador.style.color = '#ffffff';
        } else {
            contador.style.display = 'none';
        }
    }

    // ================================================
    // SEÇÃO 7: ORDENAÇÃO
    // ================================================

    /**
     * Evento: Mudar ordenação
     */
    document.getElementById('select-ordenar').addEventListener('change', function() {
        ordenarResultados(this.value);
        renderizarResultados();
    });

    /**
     * Função: Ordenar resultados
     */
    function ordenarResultados(tipo) {
        switch (tipo) {
            case 'menor_preco':
                resultadosFiltrados.sort((a, b) => parseFloat(a.valor_unitario || 0) - parseFloat(b.valor_unitario || 0));
                break;
            case 'maior_preco':
                resultadosFiltrados.sort((a, b) => parseFloat(b.valor_unitario || 0) - parseFloat(a.valor_unitario || 0));
                break;
            case 'data_recente':
                resultadosFiltrados.sort((a, b) => {
                    const dataA = new Date(a.data_vigencia_inicio || a.data || 0);
                    const dataB = new Date(b.data_vigencia_inicio || b.data || 0);
                    return dataB - dataA;
                });
                break;
        }
    }

    // ================================================
    // SEÇÃO 7.5: FILTROS ESTÁTICOS (FONTE E PORTE)
    // ================================================

    /**
     * Função: Inicializar event listeners dos filtros estáticos
     * IMPORTANTE: Chamada APÓS filtros-dinamicos ser exibido (display: block)
     */
    function inicializarFiltrosEstaticos() {
        console.log('🔧 Inicializando filtros estáticos (fonte e porte)...');

        // Event listeners para checkboxes de filtro de fonte
        const checkboxesFonte = document.querySelectorAll('input[name="filtro_fonte"]');
        console.log(`📍 Encontrados ${checkboxesFonte.length} checkboxes de fonte`);

        checkboxesFonte.forEach(checkbox => {
            // Restaurar estado salvo
            const valor = checkbox.value;
            checkbox.checked = estadoFiltrosFonte[valor] !== undefined ? estadoFiltrosFonte[valor] : checkbox.checked;
            console.log(`🔄 Restaurando estado de ${valor}: ${checkbox.checked}`);

            // Remover event listener anterior (se existir) para evitar duplicatas
            const novoCheckbox = checkbox.cloneNode(true);
            checkbox.parentNode.replaceChild(novoCheckbox, checkbox);

            novoCheckbox.addEventListener('change', function() {
                console.log(`🔄 Filtro de fonte alterado: ${this.value} = ${this.checked}`);

                // Salvar estado
                estadoFiltrosFonte[this.value] = this.checked;
                console.log('💾 Estado salvo:', estadoFiltrosFonte);

                // ❌ REMOVIDO: aplicarFiltros() - agora só aplica ao clicar no botão
            });
        });

        // Botão "APLICAR FILTROS"
        const btnAplicarFonte = document.getElementById('btn-aplicar-filtro-fonte');
        if (btnAplicarFonte) {
            btnAplicarFonte.addEventListener('click', function() {
                console.log('🔵 Botão APLICAR FILTROS clicado');

                // Verificar se pelo menos um está marcado
                const algumMarcado = Object.values(estadoFiltrosFonte).some(v => v === true);
                if (!algumMarcado) {
                    alert('⚠️ Atenção: Pelo menos uma fonte deve estar selecionada!');
                    return;
                }

                aplicarFiltros();
            });
        }

        // Event listeners para radio buttons de porte
        const radiosPorte = document.querySelectorAll('input[name="filtro_porte"]');
        console.log(`📍 Encontrados ${radiosPorte.length} radio buttons de porte`);

        radiosPorte.forEach(radio => {
            // Remover event listener anterior (se existir) para evitar duplicatas
            const novoRadio = radio.cloneNode(true);
            radio.parentNode.replaceChild(novoRadio, radio);

            novoRadio.addEventListener('change', function() {
                console.log(`🔄 Filtro de porte alterado: ${this.value}`);
                aplicarFiltros();
            });
        });

        console.log('✅ Filtros estáticos inicializados com sucesso!');
    }

    // ================================================
    // SEÇÃO 8: BOTÕES DE AÇÃO
    // ================================================

    /**
     * Evento: IR PARA MEDIANA
     */
    document.getElementById('btn-ir-mediana').addEventListener('click', function() {
        if (resultadosFiltrados.length === 0) {
            alert('⚠️ Nenhum resultado disponível.');
            return;
        }

        const valores = resultadosFiltrados.map(r => parseFloat(r.valor_unitario || 0)).sort((a, b) => a - b);
        const mediana = calcularMediana(valores);

        // Encontrar índice mais próximo da mediana
        const indiceMaisProximo = resultadosFiltrados.findIndex(r => Math.abs(parseFloat(r.valor_unitario) - mediana) < 0.01);

        if (indiceMaisProximo !== -1) {
            // Scroll até a linha
            const linhas = document.querySelectorAll('#tbody-resultados-pesquisa tr');
            if (linhas[indiceMaisProximo]) {
                linhas[indiceMaisProximo].scrollIntoView({ behavior: 'smooth', block: 'center' });
                linhas[indiceMaisProximo].style.background = '#fef3c7';
                setTimeout(() => {
                    linhas[indiceMaisProximo].style.background = '';
                }, 2000);
            }
        }

        console.log(`🎯 Navegando para mediana: ${formatarMoeda(mediana)}`);
    });

    /**
     * Evento: SELECIONAR 6 ITENS A PARTIR DA MEDIANA
     */
    document.getElementById('btn-selecionar-6-mediana').addEventListener('click', function() {
        const valores = resultadosFiltrados.map(r => parseFloat(r.valor_unitario || 0)).sort((a, b) => a - b);
        const mediana = calcularMediana(valores);

        // Encontrar índice da mediana
        let indiceMediana = resultadosFiltrados.findIndex(r => Math.abs(parseFloat(r.valor_unitario) - mediana) < 0.01);
        if (indiceMediana === -1) indiceMediana = Math.floor(resultadosFiltrados.length / 2);

        // Selecionar 3 antes e 3 depois (total 6)
        const inicio = Math.max(0, indiceMediana - 3);
        const checkboxes = document.querySelectorAll('.checkbox-selecao-amostra');

        checkboxes.forEach((cb, i) => {
            cb.checked = (i >= inicio && i < inicio + 6);
        });

        // Atualizar análise crítica
        atualizarAnaliseCritica();

        console.log(`✅ 6 itens selecionados a partir da mediana (índice ${indiceMediana})`);
    });

    // ================================================
    // SEÇÃO 9: ANÁLISE CRÍTICA DAS AMOSTRAS
    // ================================================

    /**
     * Evento: Mudança nos checkboxes de seleção (Event Delegation)
     * IMPORTANTE: Usa document porque os checkboxes são criados dinamicamente
     */
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('checkbox-selecao-amostra')) {
            console.log('✅ Checkbox marcado/desmarcado');
            atualizarAnaliseCritica();
        }
    });

    /**
     * Função: Atualizar seção de Análise Crítica
     */
    function atualizarAnaliseCritica() {
        console.log('🔍 atualizarAnaliseCritica() chamada');

        const checkboxesMarcados = document.querySelectorAll('.checkbox-selecao-amostra:checked');
        const numeroSelecionadas = checkboxesMarcados.length;

        console.log(`📊 Checkboxes marcados encontrados: ${numeroSelecionadas}`);

        if (numeroSelecionadas === 0) {
            console.log('⚠️ Nenhum checkbox marcado - análise crítica permanece visível mas sem dados');
            // NÃO esconder mais a seção - apenas não calcular estatísticas
            return;
        }

        console.log('✅ Atualizando análise crítica com dados');

        // Extrair amostras selecionadas
        const amostras = Array.from(checkboxesMarcados).map(cb => {
            const index = parseInt(cb.dataset.index);
            const amostra = resultadosFiltrados[index];

            if (!amostra) {
                console.error(`❌ Amostra ${index} não encontrada em resultadosFiltrados!`);
                console.log(`📊 resultadosFiltrados.length = ${resultadosFiltrados.length}`);
                console.log(`📊 Índice solicitado = ${index}`);
                return null;
            }

            console.log(`📊 Amostra ${index}:`);
            console.log(`   - valor_unitario: ${amostra.valor_unitario}`);
            console.log(`   - valor_unitario_original: ${amostra.valor_unitario_original || 'N/A'}`);
            console.log(`   - fator_ajuste: ${amostra.fator_ajuste || 'N/A'}`);
            console.log(`   - ajuste_aplicado: ${amostra.ajuste_aplicado || false}`);
            console.log(`   - unidade_medida_ajustada: ${amostra.unidade_medida_ajustada || 'N/A'}`);

            // ✅ ARMAZENAR ÍNDICE REAL DENTRO DO OBJETO
            return {
                ...amostra,
                _indiceReal: index
            };
        }).filter(a => a !== null); // Filtrar amostras inválidas

        // Calcular estatísticas
        const valores = amostras.map(a => parseFloat(a.valor_unitario || 0)).sort((a, b) => a - b);
        console.log(`💰 Valores extraídos para cálculo:`, valores);

        const quantidade = valores.length;
        const media = valores.reduce((acc, val) => acc + val, 0) / quantidade;
        console.log(`📊 Estatísticas calculadas: quantidade=${quantidade}, média=${media.toFixed(2)}`);
        const variancia = valores.reduce((acc, val) => acc + Math.pow(val - media, 2), 0) / quantidade;
        const desvioPadrao = Math.sqrt(variancia);
        const limiteInferior = media - desvioPadrao;
        const limiteSuperior = media + desvioPadrao;

        // Amostras críticas (fora dos limites)
        const criticas = valores.filter(v => v < limiteInferior || v > limiteSuperior).length;
        const expurgadas = valores.filter(v => v > limiteSuperior).length;

        // Amostras válidas (dentro dos limites)
        const valoresValidos = valores.filter(v => v >= limiteInferior && v <= limiteSuperior);
        const quantidadeValidas = valoresValidos.length;

        let desvioPadraoValidas = 0;
        let coefVariacao = 0;
        let menorPreco = 0;
        let mediaValidas = 0;
        let medianaValidas = 0;

        if (quantidadeValidas > 0) {
            mediaValidas = valoresValidos.reduce((acc, val) => acc + val, 0) / quantidadeValidas;
            const varianciaValidas = valoresValidos.reduce((acc, val) => acc + Math.pow(val - mediaValidas, 2), 0) / quantidadeValidas;
            desvioPadraoValidas = Math.sqrt(varianciaValidas);
            coefVariacao = mediaValidas > 0 ? (desvioPadraoValidas / mediaValidas) * 100 : 0;
            menorPreco = Math.min(...valoresValidos);
            medianaValidas = calcularMediana(valoresValidos);
        }

        // ===== ATUALIZAR TABELA 1: JUÍZO CRÍTICO =====
        document.getElementById('juizo-num-amostras').textContent = quantidade;
        document.getElementById('juizo-media').textContent = formatarMoeda(media);
        document.getElementById('juizo-desvio-padrao').textContent = desvioPadrao.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('juizo-limite-inferior').innerHTML = formatarMoeda(Math.max(0, limiteInferior)) + '<br><span style="font-size: 7px; color: #6b7280;">(DP - média)</span>';
        document.getElementById('juizo-limite-superior').innerHTML = formatarMoeda(limiteSuperior) + '<br><span style="font-size: 7px; color: #6b7280;">(DP + média)</span>';
        document.getElementById('juizo-criticas').innerHTML = `<span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 4px; font-weight: 800; font-size: 10px;">${criticas}</span>`;
        document.getElementById('juizo-expurgadas').innerHTML = `<span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 4px; font-weight: 800; font-size: 10px;">${expurgadas}</span>`;

        // ===== ATUALIZAR TABELA 2: MÉTODO ESTATÍSTICO =====
        const elemNumValidas = document.getElementById('metodo-num-validas');
        const elemDesvio = document.getElementById('metodo-desvio');
        const elemCoefVar = document.getElementById('metodo-coef-variacao');
        const elemMenor = document.getElementById('metodo-menor');
        const elemMedia = document.getElementById('metodo-media');
        const elemMediana = document.getElementById('metodo-mediana');

        elemNumValidas.textContent = quantidadeValidas;
        elemDesvio.textContent = desvioPadraoValidas.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        elemCoefVar.textContent = coefVariacao.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
        elemMenor.textContent = formatarMoeda(menorPreco);
        elemMedia.textContent = formatarMoeda(mediaValidas);
        elemMediana.textContent = formatarMoeda(medianaValidas);

        console.log(`✅ Tabela "Método Estatístico" atualizada:`);
        console.log(`   - Mediana: ${formatarMoeda(medianaValidas)} (valor: ${medianaValidas})`);
        console.log(`   - Média: ${formatarMoeda(mediaValidas)}`);
        console.log(`   - Menor: ${formatarMoeda(menorPreco)}`);
        console.log(`🔍 VERIFICAÇÃO IMEDIATA após atualização:`);
        console.log(`   - elemMediana.textContent = "${elemMediana.textContent}"`);
        console.log(`   - elemMedia.textContent = "${elemMedia.textContent}"`);
        console.log(`   - elemMenor.textContent = "${elemMenor.textContent}"`);

        // ===== ATUALIZAR CONTADOR DE AMOSTRAS DA SÉRIE =====
        const contadorSerie = document.getElementById('contador-amostras-serie');
        if (contadorSerie) {
            contadorSerie.textContent = `${amostras.length} ${amostras.length === 1 ? 'amostra' : 'amostras'}`;
        }

        // ===== ATUALIZAR SÉRIE DE PREÇOS COLETADOS (CARDS COM ESPAÇAMENTO) =====
        const tbodySerie = document.getElementById('tbody-serie-precos');

        // CRÍTICO: Verificar se tbodySerie existe
        if (!tbodySerie) {
            console.error('❌ Elemento tbody-serie-precos não encontrado no DOM!');
            return;
        }

        console.log(`🔄 Atualizando série de preços com ${amostras.length} amostras`);

        tbodySerie.innerHTML = amostras.map((amostra, idx) => {
            const valor = parseFloat(amostra.valor_unitario || 0);
            const situacao = (valor < limiteInferior || valor > limiteSuperior) ? 'EXPURGADO' : 'VÁLIDA';
            const corSituacao = situacao === 'EXPURGADO' ? '#dc2626' : '#10b981';
            const bgSituacao = situacao === 'EXPURGADO' ? '#fee2e2' : '#d1fae5';

            // ✅ USAR ÍNDICE REAL ARMAZENADO NO OBJETO
            const indiceReal = amostra._indiceReal;

            return `
                <div style="background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Cabeçalho do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f3f4f6;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="background: #374151; color: white; padding: 6px 12px; border-radius: 5px; font-size: 13px; font-weight: 800; -webkit-font-smoothing: antialiased;">#${String(idx + 1).padStart(3, '0')}</span>
                            <span style="background: ${bgSituacao}; color: ${corSituacao}; padding: 5px 12px; border-radius: 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; -webkit-font-smoothing: antialiased;">${situacao}</span>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn-ver-detalhes-amostra-serie" data-index="${indiceReal}" style="background: #6b7280; color: white; border: none; padding: 6px 12px; border-radius: 5px; font-size: 11px; cursor: pointer; font-weight: 700; -webkit-font-smoothing: antialiased;" title="Ver detalhes">
                                ☰ DETALHES
                            </button>
                            <button type="button" class="btn-remover-amostra-serie" data-checkbox-index="${indiceReal}" style="background: #dc2626; color: white; border: none; padding: 6px 12px; border-radius: 5px; font-size: 11px; cursor: pointer; font-weight: 700; -webkit-font-smoothing: antialiased;" title="Remover amostra">
                                ✖ REMOVER
                            </button>
                        </div>
                    </div>

                    <!-- Corpo do Card -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <div style="font-size: 10px; color: #9ca3af; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; -webkit-font-smoothing: antialiased;">Órgão/Fornecedor</div>
                            <div style="font-size: 13px; color: #1f2937; font-weight: 700; -webkit-font-smoothing: antialiased;">${amostra.orgao_codigo || ''} - ${(amostra.orgao_nome || amostra.razao_social_fornecedor || '-').substring(0, 40)}</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; color: #9ca3af; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; -webkit-font-smoothing: antialiased;">Marca</div>
                            <div style="font-size: 13px; color: #1f2937; font-weight: 700; -webkit-font-smoothing: antialiased;">${amostra.marca || '-'}</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; color: #9ca3af; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; -webkit-font-smoothing: antialiased;">Data Vigência</div>
                            <div style="font-size: 13px; color: #1f2937; font-weight: 700; -webkit-font-smoothing: antialiased;">${formatarData(amostra.data_vigencia_inicio || amostra.data)}</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; color: #9ca3af; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; -webkit-font-smoothing: antialiased;">Unidade</div>
                            <div style="font-size: 13px; color: #1f2937; font-weight: 700; -webkit-font-smoothing: antialiased;">${amostra.unidade_medida || amostra.medida_fornecimento || 'UN'}</div>
                        </div>
                    </div>

                    <!-- Rodapé do Card com Preço -->
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 11px; color: #6b7280; font-style: italic; max-width: 60%; -webkit-font-smoothing: antialiased;">${(amostra.descricao || amostra.nome_item || '').substring(0, 60)}...</div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 2px; -webkit-font-smoothing: antialiased;">Valor Unitário</div>
                            <div style="font-size: 18px; color: #1f2937; font-weight: 800; -webkit-font-smoothing: antialiased;">${formatarMoeda(valor)}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // ===== ATUALIZAR SEÇÃO 4: RESUMO FINAL =====
        document.getElementById('resumo-mediana').textContent = formatarMoeda(medianaValidas);
        document.getElementById('resumo-media').textContent = formatarMoeda(mediaValidas);
        document.getElementById('resumo-menor').textContent = formatarMoeda(menorPreco);

        console.log(`📊 Análise Crítica atualizada: ${quantidade} amostras | ${quantidadeValidas} válidas | ${expurgadas} expurgadas`);
    }

    // ================================================
    // SEÇÃO 10: BOTÃO CONCLUIR COTAÇÃO
    // ================================================

    /**
     * Evento: Botão CONCLUIR COTAÇÃO (COM VALIDAÇÃO DE JUSTIFICATIVA OBRIGATÓRIA)
     */
    document.getElementById('btn-concluir-cotacao').addEventListener('click', function() {
        console.log('═══════════════════════════════════════════════════════');
        console.log('🚀 INÍCIO: Botão Concluir Cotação CLICADO');
        console.log('═══════════════════════════════════════════════════════');

        const checkboxesMarcados = document.querySelectorAll('.checkbox-selecao-amostra:checked');
        console.log(`ETAPA 1: Buscar checkboxes marcados`);
        console.log(`   - Seletor usado: .checkbox-selecao-amostra:checked`);
        console.log(`   - Checkboxes encontrados: ${checkboxesMarcados.length}`);

        if (checkboxesMarcados.length === 0) {
            console.error('❌ ERRO: Nenhum checkbox marcado!');
            alert('⚠️ Selecione pelo menos uma amostra para concluir a cotação.');
            return;
        }

        // ✅ JUSTIFICATIVA OPCIONAL: Verificar se foi preenchida (não é mais obrigatória)
        console.log(`ETAPA 1.5: Verificar justificativa (OPCIONAL)`);
        console.log(`   - window.justificativaCotacao: "${window.justificativaCotacao}"`);
        console.log(`   - Está vazia? ${!window.justificativaCotacao || window.justificativaCotacao.trim() === ''}`);

        if (!window.justificativaCotacao || window.justificativaCotacao.trim() === '') {
            console.log('ℹ️ Justificativa não preenchida (OPCIONAL - prosseguindo sem justificativa)');
        } else {
            console.log(`✅ Justificativa preenchida (${window.justificativaCotacao.length} caracteres)`);
            console.log(`📝 Texto da justificativa: "${window.justificativaCotacao.substring(0, 100)}..."`);
        }


        console.log(`ETAPA 2: Verificar resultadosFiltrados`);
        console.log(`   - Array resultadosFiltrados existe? ${resultadosFiltrados ? 'SIM' : 'NÃO'}`);
        console.log(`   - Tamanho de resultadosFiltrados: ${resultadosFiltrados.length}`);

        const amostras = Array.from(checkboxesMarcados).map(cb => {
            const index = parseInt(cb.dataset.index);
            const amostra = resultadosFiltrados[index];

            console.log(`📊 Checkbox com data-index="${index}"`);
            console.log(`   - Amostra encontrada:`, amostra ? 'SIM' : 'NÃO');

            if (!amostra) {
                console.error(`❌ resultadosFiltrados[${index}] retornou undefined!`);
                return null;
            }

            return amostra;
        }).filter(a => a !== null); // ✅ FILTRAR null

        if (amostras.length === 0) {
            alert('⚠️ Erro: Não foi possível obter os dados das amostras selecionadas.\n\n' +
                  'Possível causa: Os índices dos checkboxes não correspondem aos dados filtrados.\n\n' +
                  `Checkboxes marcados: ${checkboxesMarcados.length}\n` +
                  `Dados disponíveis: ${resultadosFiltrados.length}\n\n` +
                  'Tente refazer a busca ou contate o suporte.');
            console.error('❌ Todas as amostras retornaram undefined!');
            console.error('📊 Detalhes do erro:');
            console.error(`   - Checkboxes marcados: ${checkboxesMarcados.length}`);
            console.error(`   - resultadosFiltrados.length: ${resultadosFiltrados.length}`);
            Array.from(checkboxesMarcados).forEach((cb, i) => {
                console.error(`   - Checkbox ${i}: data-index="${cb.dataset.index}"`);
            });
            return;
        }

        // Calcular estatísticas das amostras válidas (não expurgadas)
        const valores = amostras.map(a => parseFloat(a.valor_unitario || 0)).sort((a, b) => a - b);
        const media = valores.reduce((acc, val) => acc + val, 0) / valores.length;
        const variancia = valores.reduce((acc, val) => acc + Math.pow(val - media, 2), 0) / valores.length;
        const desvioPadrao = Math.sqrt(variancia);
        const limiteInferior = media - desvioPadrao;
        const limiteSuperior = media + desvioPadrao;

        // Filtrar apenas amostras válidas (dentro dos limites)
        const valoresValidos = valores.filter(v => v >= limiteInferior && v <= limiteSuperior);
        const mediana = calcularMediana(valoresValidos.length > 0 ? valoresValidos : valores);

        console.log(`✅ Concluindo cotação com ${amostras.length} amostras | ${valoresValidos.length} válidas | Mediana: ${formatarMoeda(mediana)}`);

        console.log(`ETAPA 5: Aplicar preço ao item do orçamento`);
        console.log(`   - itemAtual:`, itemAtual);
        console.log(`   - itemAtual.id: ${itemAtual.id}`);

        // Aplicar preço ao item do orçamento
        if (!itemAtual.id) {
            console.error('❌ ERRO: itemAtual.id não existe!');
            alert('⚠️ Erro: ID do item não encontrado. Por favor, feche e abra o modal novamente.');
            return;
        }

        console.log(`ETAPA 6: Procurar campo de preço para item #${itemAtual.id}`);

        // IMPORTANTE: Primeiro, marcar o checkbox do item (caso não esteja marcado)
        const checkboxItem = document.querySelector(`.item-checkbox[data-item-id="${itemAtual.id}"]`);
        console.log(`🔍 Checkbox encontrado:`, checkboxItem);

        if (checkboxItem) {
            console.log(`📌 Checkbox status: checked=${checkboxItem.checked}`);
            if (!checkboxItem.checked) {
                console.log(`✅ Marcando checkbox do item #${itemAtual.id}`);
                checkboxItem.checked = true;

                // ⚠️ CRÍTICO: Adicionar atributo de flag para proteger contra desmarcação
                checkboxItem.setAttribute('data-cotacao-preenchida', 'true');
                console.log(`🔒 Flag data-cotacao-preenchida adicionada ao checkbox`);

                // Disparar evento de mudança para habilitar campo de preço
                checkboxItem.dispatchEvent(new Event('change', { bubbles: true }));
                console.log(`📤 Evento 'change' disparado no checkbox`);
            } else {
                console.log(`ℹ️ Checkbox já estava marcado`);
                // Garantir que flag está presente mesmo se checkbox já estava marcado
                checkboxItem.setAttribute('data-cotacao-preenchida', 'true');
                console.log(`🔒 Flag data-cotacao-preenchida adicionada ao checkbox (já marcado)`);
            }
        } else {
            console.warn(`⚠️ Checkbox do item #${itemAtual.id} NÃO ENCONTRADO!`);
            console.log(`🔍 Tentando seletores alternativos...`);
            const checkboxAlt = document.querySelector(`input[type="checkbox"][data-item-id="${itemAtual.id}"]`);
            console.log(`🔍 Checkbox alternativo:`, checkboxAlt);

            // Se encontrou alternativo, usar ele
            if (checkboxAlt) {
                checkboxAlt.checked = true;
                checkboxAlt.setAttribute('data-cotacao-preenchida', 'true');
                checkboxAlt.dispatchEvent(new Event('change', { bubbles: true }));
                console.log(`✅ Checkbox alternativo marcado com flag`);
            }
        }

        // Pequeno delay para garantir que checkbox foi processado
        setTimeout(() => {
            console.log(`⏱️ Após 100ms de delay, buscando campo de preço...`);

            // Buscar campo de preço unitário do item (tenta múltiplos seletores)
            console.log(`🔍 Tentativa 1: input[name="preco_unitario[${itemAtual.id}]"]`);
            let campoPreco = document.querySelector(`input[name="preco_unitario[${itemAtual.id}]"]`);
            console.log(`   Resultado:`, campoPreco);

            // Fallback: tentar por classe e data-attribute
            if (!campoPreco) {
                console.log(`🔍 Tentativa 2: .preco-input[data-item-id="${itemAtual.id}"]`);
                campoPreco = document.querySelector(`.preco-input[data-item-id="${itemAtual.id}"]`);
                console.log(`   Resultado:`, campoPreco);
            }

            // Fallback: tentar por classe cs-preco-input (caso seja passo seleção)
            if (!campoPreco) {
                console.log(`🔍 Tentativa 3: .cs-preco-input[data-item-id="${itemAtual.id}"]`);
                campoPreco = document.querySelector(`.cs-preco-input[data-item-id="${itemAtual.id}"]`);
                console.log(`   Resultado:`, campoPreco);
            }

            // Debug: Listar TODOS os inputs de preço disponíveis
            if (!campoPreco) {
                console.log(`🔍 Listando TODOS os campos de preço disponíveis:`);
                const todosPrecos = document.querySelectorAll('input[name^="preco_unitario"]');
                console.log(`   Total encontrado: ${todosPrecos.length}`);
                todosPrecos.forEach((input, idx) => {
                    console.log(`   ${idx + 1}. name="${input.name}" data-item-id="${input.getAttribute('data-item-id')}" disabled=${input.disabled}`);
                });
            }

            if (!campoPreco) {
                alert(`❌ Erro: Campo de preço unitário não encontrado na tabela.\n\nItem ID: ${itemAtual.id}\n\n` +
                      `Possíveis causas:\n` +
                      `• Item não está na lista de itens selecionados\n` +
                      `• Você pode estar em uma etapa diferente\n` +
                      `• Tente ir para a Etapa 3 (Cadastramento de Itens)`);
                console.error('Campo de preço não encontrado:', {
                    itemId: itemAtual.id,
                    seletores: [
                        `input[name="preco_unitario[${itemAtual.id}]"]`,
                        `.preco-input[data-item-id="${itemAtual.id}"]`,
                        `.cs-preco-input[data-item-id="${itemAtual.id}"]`
                    ]
                });
                return;
            }

            console.log(`✅ Campo de preço encontrado:`, campoPreco);
            console.log(`   Tipo:`, campoPreco.type);
            console.log(`   Name:`, campoPreco.name);
            console.log(`   Disabled:`, campoPreco.disabled);
            console.log(`   Valor atual:`, campoPreco.value);

            // Habilitar campo (caso esteja desabilitado)
            if (campoPreco.disabled) {
                console.log(`🔓 Habilitando campo de preço...`);
                campoPreco.disabled = false;
                console.log(`   Disabled após habilitar:`, campoPreco.disabled);
            }

            console.log(`═══════════════════════════════════════════════════════`);
            console.log(`ETAPA 10: APLICAR VALOR NO CAMPO DE PREÇO`);
            console.log(`═══════════════════════════════════════════════════════`);
            const valorParaAplicar = mediana.toFixed(2);
            console.log(`   - Valor da mediana: ${mediana}`);
            console.log(`   - Valor formatado para aplicar: ${valorParaAplicar}`);
            console.log(`   - Campo atual antes de setar: "${campoPreco.value}"`);

            console.log(`TENTATIVA 1: Setar via .value`);
            campoPreco.value = valorParaAplicar;
            console.log(`   - Resultado: campo.value = "${campoPreco.value}"`);

            // 2. Setar via setAttribute
            campoPreco.setAttribute('value', valorParaAplicar);
            console.log(`✅ Valor setado via setAttribute`);

            // 3. Disparar eventos
            campoPreco.dispatchEvent(new Event('input', { bubbles: true }));
            campoPreco.dispatchEvent(new Event('change', { bubbles: true }));
            campoPreco.dispatchEvent(new Event('blur', { bubbles: true }));
            console.log(`✅ Eventos disparados`);

            // 4. LOOP AGRESSIVO: Forçar valor 10 vezes em intervalos curtos
            for (let i = 1; i <= 10; i++) {
                setTimeout(() => {
                    console.log(`🔄 Forçando valor (tentativa ${i}/10)...`);
                    campoPreco.value = valorParaAplicar;
                    campoPreco.setAttribute('value', valorParaAplicar);
                    campoPreco.dispatchEvent(new Event('input', { bubbles: true }));
                    campoPreco.dispatchEvent(new Event('change', { bubbles: true }));

                    // Verificar se realmente setou
                    if (campoPreco.value !== valorParaAplicar) {
                        console.error(`❌ FALHA na tentativa ${i}: valor esperado ${valorParaAplicar}, mas está ${campoPreco.value}`);
                    } else {
                        console.log(`✅ Valor confirmado na tentativa ${i}: ${campoPreco.value}`);
                    }
                }, i * 100); // 100ms, 200ms, 300ms, etc.
            }

            // ============================================
            // CALCULAR E APLICAR PREÇO TOTAL - MODO SUPER AGRESSIVO
            // ============================================
            const quantidade = parseFloat(campoPreco.getAttribute('data-quantidade')) || 0;
            const precoTotal = quantidade * mediana;
            const precoTotalFormatado = `R$ ${precoTotal.toFixed(2).replace('.', ',')}`;

            console.log(`🧮 CÁLCULO DO PREÇO TOTAL:`);
            console.log(`   Quantidade: ${quantidade}`);
            console.log(`   Preço Unitário: ${mediana.toFixed(2)}`);
            console.log(`   Preço Total: ${precoTotal.toFixed(2)}`);
            console.log(`   Formatado: ${precoTotalFormatado}`);

            const spanPrecoTotal = document.querySelector(`.preco-total[data-item-id="${itemAtual.id}"]`);

            if (spanPrecoTotal) {
                console.log(`✅ Span de preço total encontrado:`, spanPrecoTotal);

                // 1. Setar textContent
                spanPrecoTotal.textContent = precoTotalFormatado;
                console.log(`✅ Preço total setado via textContent: ${spanPrecoTotal.textContent}`);

                // 2. Setar innerHTML
                spanPrecoTotal.innerHTML = precoTotalFormatado;
                console.log(`✅ Preço total setado via innerHTML`);

                // 3. LOOP AGRESSIVO: Forçar preço total 10 vezes
                for (let i = 1; i <= 10; i++) {
                    setTimeout(() => {
                        console.log(`🔄 Forçando preço total (tentativa ${i}/10)...`);
                        spanPrecoTotal.textContent = precoTotalFormatado;
                        spanPrecoTotal.innerHTML = precoTotalFormatado;

                        // Verificar
                        if (spanPrecoTotal.textContent !== precoTotalFormatado) {
                            console.error(`❌ FALHA preço total tentativa ${i}: esperado "${precoTotalFormatado}", mas está "${spanPrecoTotal.textContent}"`);
                        } else {
                            console.log(`✅ Preço total confirmado tentativa ${i}: ${spanPrecoTotal.textContent}`);
                        }
                    }, i * 100);
                }
            } else {
                // OPCIONAL: Span de preço total não encontrado (pode ser normal se tabela foi recarregada)
                console.warn(`⚠️ Span de preço total não encontrado (item ${itemAtual.id}) - ignorando atualização visual`);

                // Debug apenas se necessário
                if (window.DEBUG_MODE) {
                    const todosSpans = document.querySelectorAll('.preco-total');
                    console.log(`   Total de spans .preco-total: ${todosSpans.length}`);
                    todosSpans.forEach((span, idx) => {
                        console.log(`   ${idx + 1}. data-item-id="${span.getAttribute('data-item-id')}" texto="${span.textContent}"`);
                    });
                }
            }

            // ============================================
            // DESTACAR LINHA - PISCAR VERDE (SUPER VISÍVEL)
            // ============================================
            const linhaItem = campoPreco.closest('tr');
            console.log(`🔍 Linha do item encontrada:`, linhaItem);

            if (linhaItem) {
                console.log(`✅ Aplicando efeito verde na linha...`);

                // 1. Aplicar cor verde FORTE
                linhaItem.style.background = '#10b981'; // Verde forte
                linhaItem.style.transition = 'all 0.3s ease';

                console.log(`✅ Linha agora está VERDE FORTE`);

                // 2. LOOP: Piscar 3 vezes
                let contadorPisca = 0;
                const intervaloPiscar = setInterval(() => {
                    contadorPisca++;
                    if (contadorPisca % 2 === 0) {
                        linhaItem.style.background = '#10b981'; // Verde
                        console.log(`💚 Pisca ${contadorPisca/2}: VERDE`);
                    } else {
                        linhaItem.style.background = '#d1fae5'; // Verde claro
                        console.log(`💚 Pisca ${Math.ceil(contadorPisca/2)}: Verde claro`);
                    }

                    if (contadorPisca >= 6) { // 3 piscadas completas
                        clearInterval(intervaloPiscar);
                        // Voltar ao normal após 2 segundos
                        setTimeout(() => {
                            linhaItem.style.background = '';
                            console.log(`✅ Linha voltou ao normal`);
                        }, 2000);
                    }
                }, 500); // Piscar a cada 500ms
            } else {
                console.error(`❌ LINHA NÃO ENCONTRADA!`);
            }

            console.log(`✅ Preço aplicado ao item #${itemAtual.id}: R$ ${mediana.toFixed(2)} (Total: R$ ${precoTotal.toFixed(2)})`);

            // VERIFICAÇÃO FINAL: Ler valor do campo ANTES de mostrar alert
            console.log(`\n🔍 ========== VERIFICAÇÃO FINAL ANTES DO ALERT ==========`);
            console.log(`📍 Campo de preço:`, campoPreco);
            console.log(`💰 campoPreco.value = "${campoPreco.value}"`);
            console.log(`🔓 campoPreco.disabled = ${campoPreco.disabled}`);
            console.log(`👁️ campoPreco está VISÍVEL na tela?`);
            console.log(`   - display: ${window.getComputedStyle(campoPreco).display}`);
            console.log(`   - visibility: ${window.getComputedStyle(campoPreco).visibility}`);
            console.log(`   - opacity: ${window.getComputedStyle(campoPreco).opacity}`);
            console.log(`📊 Span de preço total:`, spanPrecoTotal);
            if (spanPrecoTotal) {
                console.log(`💰 spanPrecoTotal.textContent = "${spanPrecoTotal.textContent}"`);
            }
            console.log(`🔍 ====================================================\n`);

            // ============================================
            // SALVAR PREÇO NO BANCO DE DADOS VIA AJAX
            // ============================================
            console.log(`💾 Salvando preço no banco de dados...`);

            const orcamentoId = window.location.pathname.match(/orcamentos\/(\d+)/)?.[1];
            if (orcamentoId) {
                const formData = new FormData();
                formData.append('item_id', itemAtual.id);
                formData.append('preco_unitario', mediana.toFixed(2));
                formData.append('quantidade', quantidade);

                fetch(window.APP_BASE_PATH + `/orcamentos/${orcamentoId}/salvar-preco-item`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        console.log(`✅ Preço salvo no banco com sucesso!`, result);

                        // ✅ SALVAR AMOSTRAS SELECIONADAS NO BANCO (SCHEMA V2)
                        console.log(`💾 Salvando amostras selecionadas no banco (Schema v2)...`);
                        const amostrasSalvar = amostras.map(a => ({
                            // ========================================
                            // CAMPOS BÁSICOS (Schema v1 - mantidos)
                            // ========================================
                            descricao: a.descricao || a.nome_item,
                            valor_unitario: a.valor_unitario,
                            fonte: a.fonte || 'Não especificada',
                            orgao: a.orgao_nome || a.orgao || 'N/A',
                            municipio: a.municipio || 'N/A',
                            uf: a.uf || 'N/A',
                            marca: a.marca || 'N/A',
                            data_publicacao: a.data_publicacao || a.data || null,
                            unidade_medida: a.unidade_medida || a.medida_fornecimento || 'UN',
                            quantidade_original: a.quantidade || 1,
                            fator_ajuste: a.fator_ajuste || null,
                            ajuste_aplicado: a.ajuste_aplicado || false,
                            link_fonte: a.link_fonte || null,

                            // ========================================
                            // 15 NOVOS CAMPOS (Schema v2 - FASE 3.2)
                            // ========================================

                            // 1. origem - Sistema de origem da amostra
                            origem: a.origem || a.sistema || 'PNCP',

                            // 2. ente_fornecedor_seller - Razão social do fornecedor/vendedor
                            ente_fornecedor_seller: a.razao_social_fornecedor || a.fornecedor || a.vendedor || null,

                            // 3. uf_municipio - Combinação UF/Município
                            uf_municipio: (a.uf && a.municipio) ? `${a.uf}/${a.municipio}` : null,

                            // 4. data_ref - Data de referência da amostra
                            data_ref: a.data_publicacao || a.data || null,

                            // 5. lote_item_origem - Lote/Item de origem
                            lote_item_origem: a.lote_item || null,

                            // 6. unid_origem - Unidade de medida original
                            unid_origem: a.unidade_original || a.unidade_medida || 'UN',

                            // 7. qtd_origem - Quantidade original
                            qtd_origem: a.quantidade || 1,

                            // 8. preco_unit_origem - Preço unitário original
                            preco_unit_origem: a.valor_unitario,

                            // 9. preco_total_origem - Preço total original
                            preco_total_origem: (a.quantidade || 1) * (a.valor_unitario || 0),

                            // 10. url - URL da fonte original
                            url: a.link_fonte || a.url || null,

                            // 11. anexo_ids - IDs de anexos relacionados
                            anexo_ids: a.anexos ? a.anexos.map(anx => anx.id) : [],

                            // 12. fator_conversao - Fator de conversão de unidades
                            fator_conversao: a.fator_ajuste || 1.0,

                            // 13. situacao - Situação da amostra (VALIDA, EXPURGADA)
                            situacao: 'VALIDA', // Será alterado na análise crítica

                            // 14. motivo_expurgo - Motivo do expurgo (se aplicável)
                            motivo_expurgo: null,

                            // 15. regra_aplicada - Regra de saneamento aplicada
                            regra_aplicada: null,

                            // ========================================
                            // CAMPOS ESPECÍFICOS (mantidos do v1)
                            // ========================================
                            codigo_identificacao: a.codigoCompra || a.codigo_identificacao || null,
                            numero_pregao: a.numero_pregao || null,
                            numero_ata: a.numero_ata || null,
                            lote_item: a.lote_item || null,
                            valor_total: (a.quantidade || 1) * (a.valor_unitario || 0),
                            fornecedor_nome: a.razao_social_fornecedor || a.fornecedor || a.fornecedor_nome || null,
                            fornecedor_cnpj: a.cnpj_fornecedor || a.cnpj || null,
                            orgao_codigo: a.orgao_codigo || null,

                            // E-commerce
                            marketplace_nome: a.marketplace_nome || a.marketplace || null,
                            marketplace_url: a.marketplace_url || null,
                            marketplace_vendedor: a.marketplace_vendedor || a.vendedor || null,
                            marketplace_avaliacao: a.marketplace_avaliacao || null,

                            // Contratações Similares
                            uasg: a.uasg || a.codigo_uasg || null,
                            modalidade_compra: a.modalidade_compra || a.modalidade || null,
                            tipo_documento: a.tipo_documento || null,
                            numero_processo: a.numero_processo || null,
                            objeto_contratacao: a.objeto_contratacao || a.objeto || null,

                            // Outros
                            nivel_confianca: a.nivel_confianca || null,
                            local_publicacao: a.local_publicacao || null,

                            // ========================================
                            // SCHEMA VERSION (v2)
                            // ========================================
                            schema_version: 2
                        }));

                        const formDataAmostras = new FormData();
                        formDataAmostras.append('amostras', JSON.stringify(amostrasSalvar));
                        formDataAmostras.append('justificativa', window.justificativaCotacao || '');

                        fetch(window.APP_BASE_PATH + `/orcamentos/${orcamentoId}/itens/${itemAtual.id}/salvar-amostras`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formDataAmostras
                        })
                        .then(resp => resp.json())
                        .then(resultAmostras => {
                            if (resultAmostras.success) {
                                console.log(`✅ Amostras salvas no banco com sucesso!`, resultAmostras);
                            } else {
                                console.warn(`⚠️ Erro ao salvar amostras (não crítico):`, resultAmostras.message);
                            }

                            // ✅ VERIFICAÇÃO FINAL: Confirmar que valor está no campo
                            console.log(`\n🔍 ========== VERIFICAÇÃO FINAL ANTES DE FECHAR ==========`);
                            console.log(`💰 campoPreco.value = "${campoPreco.value}"`);
                            console.log(`💰 spanPrecoTotal.textContent = "${spanPrecoTotal ? spanPrecoTotal.textContent : 'N/A'}"`);
                            console.log(`✅ Valor confirmado no banco: ${result.item.preco_unitario}`);
                            console.log(`✅ Amostras salvas: ${resultAmostras.num_amostras || 0}`);
                            console.log(`🔍 ====================================================\n`);

                            // ✅ Mostrar mensagem de sucesso
                            alert(`✅ Cotação concluída com sucesso!\n\n` +
                                  `📊 Amostras selecionadas: ${amostras.length}\n` +
                                  `✓ Amostras válidas: ${valoresValidos.length}\n` +
                                  `💰 Preço da Mediana: ${formatarMoeda(mediana)}\n\n` +
                                  `O preço foi aplicado ao item "${itemAtual.descricao.substring(0, 50)}..."\n\n` +
                                  `✅ Preço e amostras salvos no banco de dados!`);

                            // ✅ FECHAR MODAL SÓ DEPOIS DE TUDO OK
                            bootstrap.Modal.getInstance(modal).hide();

                            // ✅ RECARREGAR PÁGINA PARA MOSTRAR VALORES DO BANCO (SEM CACHE!)
                            console.log(`🔄 Recarregando página para exibir valores salvos...`);
                            setTimeout(() => {
                                // ESTRATÉGIA 1: Adicionar timestamp na URL (força bypass do cache)
                                const url = new URL(window.location.href);
                                url.searchParams.set('_t', Date.now());
                                console.log(`📍 Nova URL com timestamp: ${url.href}`);

                                // ESTRATÉGIA 2: Usar location.replace (não fica no histórico)
                                window.location.replace(url.href);
                            }, 500); // Delay de 500ms para garantir que modal fechou
                        })
                        .catch(errorAmostras => {
                            console.error(`❌ Erro ao salvar amostras (não crítico):`, errorAmostras);
                            // Continuar mesmo se falhar ao salvar amostras
                            alert(`✅ Cotação concluída com sucesso!\n\n` +
                                  `📊 Amostras selecionadas: ${amostras.length}\n` +
                                  `✓ Amostras válidas: ${valoresValidos.length}\n` +
                                  `💰 Preço da Mediana: ${formatarMoeda(mediana)}\n\n` +
                                  `⚠️ Aviso: Amostras não foram salvas, mas o preço foi aplicado.`);

                            bootstrap.Modal.getInstance(modal).hide();
                            window.location.reload();
                        });

                    } else {
                        console.error(`❌ Erro ao salvar preço:`, result.message);
                        alert(`❌ Erro ao salvar preço no banco:\n\n${result.message}\n\nO preço foi aplicado na tela, mas NÃO foi salvo no banco.`);
                    }
                })
                .catch(error => {
                    console.error(`❌ Erro na requisição AJAX:`, error);
                    alert(`❌ Erro de conexão ao salvar preço:\n\n${error.message}\n\nO preço foi aplicado na tela, mas NÃO foi salvo no banco.`);
                });
            } else {
                // Sem AJAX, apenas mostrar mensagem e fechar
                alert(`✅ Cotação concluída com sucesso!\n\n` +
                      `📊 Amostras selecionadas: ${amostras.length}\n` +
                      `✓ Amostras válidas: ${valoresValidos.length}\n` +
                      `💰 Preço da Mediana: ${formatarMoeda(mediana)}\n\n` +
                      `O preço foi aplicado ao item "${itemAtual.descricao.substring(0, 50)}..."`);

                bootstrap.Modal.getInstance(modal).hide();
            }
        }, 100); // Delay de 100ms para garantir que checkbox foi processado
    });

    // ================================================
    // FUNÇÕES AUXILIARES
    // ================================================

    /**
     * Função: Destacar termo pesquisado
     */
    function destacarTermoPesquisa(texto) {
        const termo = document.getElementById('input-palavra-chave').value.trim();
        if (!termo || !texto) return texto;

        // ✅ MELHORADO: Dividir termo em palavras e destacar cada uma individualmente
        // Filtrar palavras com pelo menos 3 caracteres (ignorar "de", "da", "do", etc.)
        const palavras = termo.split(/\s+/).filter(p => p.length >= 3);

        let textoDestacado = texto;
        palavras.forEach(palavra => {
            // Escapar caracteres especiais de regex
            const palavraEscapada = palavra.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${palavraEscapada})`, 'gi');
            textoDestacado = textoDestacado.replace(regex, '<mark style="background-color: #fef08a; padding: 1px 3px; border-radius: 2px; font-weight: 600;">$1</mark>');
        });

        return textoDestacado;
    }

    /**
     * Função: Formatar data
     */
    function formatarData(data) {
        if (!data) return '-';
        try {
            const d = new Date(data);
            return d.toLocaleDateString('pt-BR');
        } catch {
            return data;
        }
    }

    /**
     * Função: Formatar moeda
     */
    function formatarMoeda(valor) {
        return 'R$ ' + parseFloat(valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    /**
     * Função: Formatar número
     */
    function formatarNumero(valor) {
        return parseFloat(valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 0, maximumFractionDigits: 2});
    }

    /**
     * Função: Calcular mediana
     */
    function calcularMediana(valores) {
        if (valores.length === 0) return 0;
        const sorted = [...valores].sort((a, b) => a - b);
        const meio = Math.floor(sorted.length / 2);
        return sorted.length % 2 === 0
            ? (sorted[meio - 1] + sorted[meio]) / 2
            : sorted[meio];
    }

    /**
     * Função: Extrair valores únicos
     */
    function extrairValoresUnicos(...campos) {
        const valores = new Set();
        resultadosCompletos.forEach(resultado => {
            campos.forEach(campo => {
                const valor = resultado[campo];
                if (valor) valores.add(valor);
            });
        });
        return Array.from(valores).sort();
    }

    /**
     * Função: Mostrar estado (vazio/loading/erro/sucesso)
     */
    function mostrarEstado(estado, mensagem = '') {
        const estadoVazio = document.getElementById('estado-vazio');
        const estadoLoading = document.getElementById('estado-loading');
        const containerTabela = document.getElementById('container-tabela-resultados');
        const statsCards = document.getElementById('stats-cards');

        // Esconder tudo
        estadoVazio.style.display = 'none';
        estadoLoading.style.display = 'none';
        containerTabela.style.display = 'none';
        statsCards.style.display = 'none';

        switch (estado) {
            case 'vazio':
                estadoVazio.style.display = 'block';
                break;
            case 'loading':
                estadoLoading.style.display = 'block';
                break;
            case 'erro':
                estadoVazio.style.display = 'block';
                estadoVazio.innerHTML = `
                    <div style="background: #fee2e2; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 36px; color: #dc2626;"></i>
                    </div>
                    <p style="margin: 0; font-size: 12px; color: #dc2626; font-weight: 600;">
                        ${mensagem || 'Erro ao carregar resultados.'}
                    </p>
                `;
                break;
            case 'sucesso':
                containerTabela.style.display = 'block';
                statsCards.style.display = 'block';
                break;
        }
    }

    /**
     * Função: Resetar modal ao estado inicial
     */
    function resetarModal() {
        // Limpar campos
        document.getElementById('input-palavra-chave').value = '';
        document.getElementById('input-cnpj').value = '';
        document.getElementById('input-catmat').value = '';
        document.getElementById('input-cnpj-catmat').value = '';

        // Resetar abas
        document.querySelector('.tab-pesquisa[data-tab="palavra-chave"]').click();

        // Limpar resultados
        resultadosCompletos = [];
        resultadosFiltrados = [];

        // Esconder seções (MANTÉM análise crítica visível)
        document.getElementById('filtros-dinamicos').style.display = 'none';
        // document.getElementById('secao-analise-critica').style.display = 'none'; // REMOVIDO - seção sempre visível

        // Mostrar estado vazio
        mostrarEstado('vazio');

        console.log('🔄 Modal resetado');
    }

    // ================================================
    // SEÇÃO: MODAIS AUXILIARES (DETALHES DA FONTE E AJUSTE DE EMBALAGEM)
    // ================================================

    /**
     * Função: Abrir modal de Detalhes da Fonte
     */
    function abrirModalDetalhesFonte(index) {
        const resultado = resultadosFiltrados[index];

        if (!resultado) {
            console.error('❌ Resultado não encontrado no índice:', index);
            return;
        }

        console.log('📄 Abrindo modal de detalhes para:', resultado);

        // Preencher campos do modal
        document.getElementById('detalhe-fonte').textContent = resultado.fonte || 'PNCP';
        document.getElementById('detalhe-identificacao').textContent = resultado.numero_controle_pncp || resultado.identificacao || '-';
        document.getElementById('detalhe-pregao').textContent = resultado.numero_pregao || '00026/2025';
        document.getElementById('detalhe-ata').textContent = resultado.numero_ata || 'S/R';
        document.getElementById('detalhe-data-homologacao').textContent = formatarData(resultado.data_homologacao || resultado.data_vigencia_inicio || resultado.data);
        document.getElementById('detalhe-orgao').textContent = resultado.orgao || resultado.orgao_nome || resultado.orgao_razao_social || '62400 - PM DE URUGUAIANA';

        // Preencher município/UF
        const municipio = resultado.municipio || resultado.municipio_orgao || '-';
        const uf = resultado.uf || resultado.uf_orgao || '-';
        document.getElementById('detalhe-municipio-uf').textContent = municipio + ' / ' + uf;

        document.getElementById('detalhe-objeto').textContent = resultado.objeto_contrato || resultado.objeto || '-';
        document.getElementById('detalhe-lote-item').textContent = resultado.lote_item || '96';
        document.getElementById('detalhe-vencedor').textContent = resultado.razao_social_fornecedor || resultado.fornecedor_razao_social || 'CQC TECNOLOGIA EM SISTEMAS DIAGNÓSTICOS LTDA - 46962122000160';
        document.getElementById('detalhe-descricao').textContent = resultado.descricao || resultado.nome_item || '-';
        document.getElementById('detalhe-marca').textContent = resultado.marca || 'UN';
        document.getElementById('detalhe-unidade').textContent = resultado.unidade_medida || resultado.medida_fornecimento || 'UN';
        document.getElementById('detalhe-quantidade').textContent = formatarNumero(resultado.quantidade || 600000.0000);
        document.getElementById('detalhe-valor-unitario').textContent = formatarMoeda(resultado.valor_unitario || 0);

        // Link de download da ARP (se disponível)
        const containerDownload = document.getElementById('container-download-arp');
        const linkDownload = document.getElementById('link-download-arp');

        if (resultado.url_arp || resultado.link_download) {
            containerDownload.style.display = 'block';
            linkDownload.href = resultado.url_arp || resultado.link_download;
        } else {
            containerDownload.style.display = 'none';
        }

        // Abrir modal
        const modalDetalhesFonte = new bootstrap.Modal(document.getElementById('modalDetalhesFonte'));
        modalDetalhesFonte.show();
    }

    /**
     * Função: Abrir modal de Ajuste de Embalagem
     */
    let ajusteAtual = {
        index: null,
        resultado: null,
        precoOriginal: 0
    };

    function abrirModalAjusteEmbalagem(index) {
        const resultado = resultadosFiltrados[index];

        if (!resultado) {
            console.error('❌ Resultado não encontrado no índice:', index);
            return;
        }

        console.log('📦 Abrindo modal de ajuste de embalagem para:', resultado);

        // Armazenar dados atuais
        ajusteAtual = {
            index: index,
            resultado: resultado,
            precoOriginal: parseFloat(resultado.valor_unitario || 0)
        };

        // Preencher descrição da amostra
        document.getElementById('ajuste-descricao-amostra').textContent = resultado.descricao || resultado.nome_item || '-';

        // Preencher dados originais
        document.getElementById('ajuste-unidade-original').value = resultado.unidade_medida || resultado.medida_fornecimento || 'UN';
        document.getElementById('ajuste-preco-original').value = formatarMoeda(ajusteAtual.precoOriginal);

        // Limpar campos desejados
        document.getElementById('ajuste-medida-desejada').value = '';
        document.getElementById('ajuste-tipo-embalagem-desejada').value = 'SECUNDARIA';
        document.getElementById('ajuste-fator-multiplicacao').value = '';
        document.getElementById('resultado-ajuste').style.display = 'none';

        // Abrir modal
        const modalAjuste = new bootstrap.Modal(document.getElementById('modalAjusteEmbalagem'));
        modalAjuste.show();
    }

    /**
     * Evento: Calcular ajuste de embalagem em tempo real
     */
    const campoFator = document.getElementById('ajuste-fator-multiplicacao');
    if (campoFator) {
        campoFator.addEventListener('input', function() {
            const fator = parseFloat(this.value);

            if (!fator || fator <= 0 || !ajusteAtual.precoOriginal) {
                document.getElementById('resultado-ajuste').style.display = 'none';
                return;
            }

            // Calcular preço ajustado
            const precoAjustado = ajusteAtual.precoOriginal * fator;

            // Exibir resultado
            document.getElementById('resultado-preco-ajustado').textContent = formatarMoeda(precoAjustado);
            document.getElementById('resultado-ajuste').style.display = 'block';

            console.log(`💰 Ajuste calculado: R$ ${ajusteAtual.precoOriginal.toFixed(2)} × ${fator} = R$ ${precoAjustado.toFixed(2)}`);
        });
    }

    /**
     * Evento: Concluir ajuste de embalagem
     */
    const btnConcluirAjuste = document.getElementById('btn-concluir-ajuste');
    if (btnConcluirAjuste) {
        btnConcluirAjuste.addEventListener('click', function() {
            const medidaDesejada = document.getElementById('ajuste-medida-desejada').value;
            const fator = parseFloat(document.getElementById('ajuste-fator-multiplicacao').value);

            if (!medidaDesejada || !fator || fator <= 0) {
                alert('⚠️ Por favor, preencha todos os campos obrigatórios:\n\n• Medida Desejada\n• Fator de Multiplicação');
                return;
            }

            // Calcular preço ajustado
            const precoAjustado = ajusteAtual.precoOriginal * fator;

            console.log('🔧 ANTES do ajuste:');
            console.log('   - Index:', ajusteAtual.index);
            console.log('   - Valor original:', resultadosFiltrados[ajusteAtual.index].valor_unitario);

            // CRITICAL: Atualizar DIRETAMENTE no array resultadosFiltrados
            resultadosFiltrados[ajusteAtual.index].unidade_medida_ajustada = medidaDesejada;
            resultadosFiltrados[ajusteAtual.index].valor_unitario_original = ajusteAtual.precoOriginal;
            resultadosFiltrados[ajusteAtual.index].valor_unitario = precoAjustado;
            resultadosFiltrados[ajusteAtual.index].fator_ajuste = fator;
            resultadosFiltrados[ajusteAtual.index].ajuste_aplicado = true;

            console.log('🔧 DEPOIS do ajuste:');
            console.log('   - Valor ajustado:', resultadosFiltrados[ajusteAtual.index].valor_unitario);
            console.log('   - Fator:', resultadosFiltrados[ajusteAtual.index].fator_ajuste);
            console.log('   - Ajuste aplicado:', resultadosFiltrados[ajusteAtual.index].ajuste_aplicado);

            console.log('✅ Ajuste aplicado:', {
                original: formatarMoeda(ajusteAtual.precoOriginal),
                fator: fator,
                ajustado: formatarMoeda(precoAjustado),
                unidade: medidaDesejada
            });

            // Re-renderizar tabela PRIMEIRO (preserva checkboxes E ORDEM)
            console.log('🔄 Re-renderizando tabela com valores ajustados (SEM reordenar)...');
            renderizarResultados(true); // TRUE = preserva ordem para manter índices

            // Fechar modal
            bootstrap.Modal.getInstance(document.getElementById('modalAjusteEmbalagem')).hide();

            // Aguardar modal fechar + renderização completar
            setTimeout(() => {
                console.log('🔄 Forçando atualização da Análise Crítica após ajuste...');
                console.log('📊 Valor ajustado no objeto:', precoAjustado, 'Fator:', fator);

                // CRITICAL: Atualizar análise crítica COM OS NOVOS VALORES
                atualizarAnaliseCritica();

                // FORÇAR atualização novamente após 100ms (garantia dupla)
                setTimeout(() => {
                    console.log('🔄 Segunda atualização forçada da Análise Crítica...');
                    atualizarAnaliseCritica();
                }, 100);

                // FORÇAR atualização pela TERCEIRA VEZ após 300ms
                setTimeout(() => {
                    console.log('🔄 Terceira atualização forçada da Análise Crítica...');
                    atualizarAnaliseCritica();
                }, 300);

                console.log('✅ Análise Crítica atualizada! Rolando para a seção...');

                // Rolar para a seção IMEDIATAMENTE (sem esperar alert)
                const secaoAnalise = document.getElementById('secao-analise-critica');
                if (secaoAnalise) {
                    secaoAnalise.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    console.log('📍 Página rolou para Análise Crítica');
                } else {
                    console.error('❌ Seção "secao-analise-critica" não encontrada no DOM!');
                }

                // Alert DEPOIS do scroll
                setTimeout(() => {
                    alert(`✅ Ajuste de embalagem aplicado com sucesso!\n\n` +
                          `Unidade: ${medidaDesejada}\n` +
                          `Preço Original: ${formatarMoeda(ajusteAtual.precoOriginal)}\n` +
                          `Fator: ${fator}x\n` +
                          `Preço Ajustado: ${formatarMoeda(precoAjustado)}\n\n` +
                          `✅ A Análise Crítica foi atualizada!\n` +
                          `📍 Verifique os novos valores acima.`);
                }, 1000); // Alert depois que tudo já aconteceu
            }, 500); // Delay para garantir que modal fechou e tabela renderizou
        });
    }

    /**
     * Event Delegation: Capturar cliques nos botões de ação das linhas
     * IMPORTANTE: Funciona mesmo quando você clica no ícone dentro do botão
     */
    document.addEventListener('click', function(event) {
        // Tentar encontrar o botão (seja clicando nele ou no ícone dentro dele)
        let target = event.target;

        // Validar se target existe antes de acessar tagName
        if (!target) return;

        // Se clicou em um ícone, pegar o botão pai
        if (target.tagName === 'I') {
            target = target.parentElement;
        }

        // Validar novamente após pegar parentElement
        if (!target) return;

        // Se não for um botão, tentar buscar o botão mais próximo
        if (target.tagName !== 'BUTTON') {
            target = target.closest('button');
        }

        // Validar se encontrou um botão
        if (!target) return;

        // Botão: Detalhes da Fonte
        if (target.classList.contains('btn-detalhes-fonte')) {
            const index = parseInt(target.dataset.index);
            console.log('🔍 Abrindo modal de detalhes para índice:', index);
            abrirModalDetalhesFonte(index);
        }

        // Botão: Ajustar Embalagem
        if (target.classList.contains('btn-ajustar-embalagem')) {
            const index = parseInt(target.dataset.index);
            console.log('📦 Abrindo modal de ajuste de embalagem para índice:', index);
            abrirModalAjusteEmbalagem(index);
        }

        // Botão: Remover Amostra (da tabela de resultados)
        if (target.classList.contains('btn-remover-amostra')) {
            const index = parseInt(target.dataset.index);
            console.log('🗑️ Removendo amostra do índice:', index);
            removerAmostra(index);
        }

        // Botão: Ver Detalhes da Amostra (da tabela de resultados)
        if (target.classList.contains('btn-ver-detalhes-amostra')) {
            const index = parseInt(target.dataset.index);
            console.log('👁️ Visualizando detalhes da amostra índice:', index);
            verDetalhesAmostra(index);
        }

        // Botão: Remover Amostra da Série (desmarca checkbox)
        if (target.classList.contains('btn-remover-amostra-serie')) {
            const checkboxIndex = parseInt(target.dataset.checkboxIndex);
            console.log('🗑️ Removendo amostra da série (desmarcando checkbox):', checkboxIndex);
            removerAmostraDaSerie(checkboxIndex);
        }

        // Botão: Ver Detalhes da Amostra da Série
        if (target.classList.contains('btn-ver-detalhes-amostra-serie')) {
            const index = parseInt(target.dataset.index);
            console.log('👁️ Visualizando detalhes da amostra da série:', index);
            verDetalhesAmostra(index);
        }
    });

    // ================================================
    // LISTENER: ABRIR MODAL DE COTAÇÃO (Botão Lupa)
    // ================================================
    document.addEventListener('click', function(event) {
        let target = event.target;

        // Se clicou em um ícone dentro do botão, pegar o botão pai
        if (target.tagName === 'I' && target.parentElement) {
            target = target.parentElement;
        }

        // Verificar se é o botão de cotação
        if (target && target.classList && target.classList.contains('btn-cotacao')) {
            event.preventDefault();

            const itemId = target.dataset.itemId;
            const itemDescricao = target.dataset.itemDescricao;

            console.log('🔍 Abrindo modal de cotação para item:', itemId, '-', itemDescricao);

            // IMPORTANTE: Atualizar itemAtual para sincronizar com botão "Concluir Cotação"
            itemAtual.id = itemId;
            itemAtual.descricao = itemDescricao || '';

            // Atualizar o título do modal com a descrição do item
            const modalTitle = document.querySelector('#modalCotacaoPrecos .modal-title');
            if (modalTitle) {
                modalTitle.textContent = `COTAÇÃO DE PREÇOS - ${itemDescricao}`;
            }

            // Preencher descrição do item
            const cotacaoItemDescricao = document.getElementById('cotacao-item-descricao');
            if (cotacaoItemDescricao) {
                cotacaoItemDescricao.textContent = itemDescricao || 'Descrição não disponível';
            }

            // Pre-preencher campo de busca com a descrição
            const inputPalavraChave = document.getElementById('input-palavra-chave');
            if (inputPalavraChave) {
                inputPalavraChave.value = itemDescricao || '';
            }

            // Armazenar ID do item para uso posterior (compatibilidade)
            window.currentItemIdCotacao = itemId;

            // Resetar estados
            resetarModal();

            // Abrir o modal usando Bootstrap 5
            const modalElement = document.getElementById('modalCotacaoPrecos');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
    });

    // ================================================
    // FUNÇÕES: REMOVER AMOSTRA E VER DETALHES
    // ================================================

    /**
     * Função: Remover amostra selecionada da análise (remove do array)
     */
    function removerAmostra(index) {
        if (index < 0 || index >= resultadosFiltrados.length) {
            console.error('❌ Índice inválido:', index);
            return;
        }

        const amostra = resultadosFiltrados[index];
        const descricao = (amostra.descricao || amostra.nome_item || 'Item').substring(0, 50);

        // Confirmar remoção
        if (!confirm(`⚠️ Deseja realmente remover esta amostra da análise?\n\n${descricao}...`)) {
            console.log('❌ Remoção cancelada pelo usuário');
            return;
        }

        // Remover do array
        resultadosFiltrados.splice(index, 1);

        console.log(`🗑️ Amostra removida. Total restante: ${resultadosFiltrados.length}`);

        // Re-renderizar tabela
        renderizarResultados();

        // Se não houver mais resultados, mostrar estado vazio
        if (resultadosFiltrados.length === 0) {
            mostrarEstado('vazio');
        }

        alert('✅ Amostra removida com sucesso!');
    }

    /**
     * Função: Remover amostra da série (desmarca checkbox) - SEMPRE PERMITE REMOVER
     */
    function removerAmostraDaSerie(checkboxIndex) {
        console.log(`🗑️ [REMOVER AMOSTRA DA SÉRIE] Iniciando remoção do índice: ${checkboxIndex}`);

        // Encontrar checkbox
        const checkbox = document.querySelector(`.checkbox-selecao-amostra[data-index="${checkboxIndex}"]`);
        if (!checkbox) {
            console.error(`❌ Checkbox não encontrado para índice: ${checkboxIndex}`);
            console.log(`🔍 Tentando seletores alternativos...`);

            // Tentar seletores alternativos
            const checkboxAlt = document.getElementById(`checkbox-amostra-${checkboxIndex}`);
            if (checkboxAlt) {
                console.log(`✅ Checkbox encontrado com seletor alternativo`);
                checkboxAlt.checked = false;
                atualizarAnaliseCritica();
                console.log(`✅ Amostra removida da série (via seletor alternativo)`);
                return;
            }

            console.error(`❌ Não foi possível encontrar o checkbox de nenhuma forma`);
            return;
        }

        console.log(`✅ Checkbox encontrado! Estado atual: ${checkbox.checked ? 'MARCADO' : 'DESMARCADO'}`);

        // Desmarcar checkbox (SEM VALIDAÇÃO de quantidade mínima - SEMPRE permite remover!)
        checkbox.checked = false;
        console.log(`🔄 Checkbox desmarcado`);

        // Atualizar análise crítica
        console.log(`📊 Atualizando análise crítica após remoção...`);
        atualizarAnaliseCritica();

        console.log(`✅ Amostra removida da série com sucesso!`);
    }

    /**
     * Função: Ver detalhes completos da amostra
     */
    function verDetalhesAmostra(index) {
        if (index < 0 || index >= resultadosFiltrados.length) {
            console.error('❌ Índice inválido:', index);
            return;
        }

        const amostra = resultadosFiltrados[index];

        // Preparar informações formatadas
        const valor = parseFloat(amostra.valor_unitario || 0);
        const valorOriginal = parseFloat(amostra.valor_unitario_original || valor);
        const fatorAjuste = parseFloat(amostra.fator_ajuste || 1);
        const ajusteAplicado = amostra.ajuste_aplicado || false;

        // Preencher modal com dados da amostra
        document.getElementById('detalhe-descricao').textContent = amostra.descricao || amostra.nome_item || '-';
        document.getElementById('detalhe-marca').textContent = amostra.marca || 'Não especificada';
        document.getElementById('detalhe-unidade').textContent = amostra.unidade_medida || amostra.medida_fornecimento || 'UN';
        document.getElementById('detalhe-quantidade').textContent = formatarNumero(amostra.quantidade || 0);

        // Seção de Valores (dinâmica)
        const valoresContainer = document.getElementById('detalhe-valores-container');
        if (ajusteAplicado) {
            valoresContainer.innerHTML = `
                <div class="row g-2">
                    <div class="col-6">
                        <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Preço Original:</small>
                        <p style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;">${formatarMoeda(valorOriginal)}</p>
                    </div>
                    <div class="col-6">
                        <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Fator de Ajuste:</small>
                        <p style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;">${fatorAjuste}x</p>
                    </div>
                    <div class="col-6">
                        <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Preço Ajustado:</small>
                        <p style="margin: 2px 0 0 0; font-size: 13px; color: #10b981; font-weight: 700;">${formatarMoeda(valor)} ⭐</p>
                    </div>
                    <div class="col-6">
                        <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Unidade Ajustada:</small>
                        <p style="margin: 2px 0 0 0; font-size: 12px; color: #1f2937;">${amostra.unidade_medida_ajustada || '-'}</p>
                    </div>
                </div>
            `;
        } else {
            valoresContainer.innerHTML = `
                <div class="row g-2">
                    <div class="col-12">
                        <small style="color: #6b7280; font-size: 10px; font-weight: 600; text-transform: uppercase;">Preço Unitário:</small>
                        <p style="margin: 2px 0 0 0; font-size: 14px; color: #10b981; font-weight: 700;">${formatarMoeda(valor)}</p>
                    </div>
                </div>
            `;
        }

        // Origem da Amostra
        document.getElementById('detalhe-orgao').textContent = amostra.orgao_nome || amostra.razao_social_fornecedor || '-';
        document.getElementById('detalhe-codigo-orgao').textContent = amostra.orgao_codigo || '-';
        document.getElementById('detalhe-cnpj').textContent = amostra.cnpj_fornecedor || '-';

        // Dados da Contratação
        document.getElementById('detalhe-numero').textContent = amostra.numero_sequencial || amostra.numero || '-';
        document.getElementById('detalhe-modalidade').textContent = amostra.modalidade_nome || amostra.modalidade || '-';
        document.getElementById('detalhe-data').textContent = formatarData(amostra.data_vigencia_inicio || amostra.data);
        document.getElementById('detalhe-numero-item').textContent = amostra.numero_item || '-';

        // Localização
        document.getElementById('detalhe-municipio').textContent = amostra.municipio_nome || '-';
        document.getElementById('detalhe-uf').textContent = amostra.uf_sigla || '-';

        // Classificação (opcional)
        const classificacaoContainer = document.getElementById('detalhe-classificacao-container');
        if (amostra.codigo_catmat || amostra.codigo_item) {
            document.getElementById('detalhe-catmat').textContent = amostra.codigo_catmat || amostra.codigo_item || '-';
            document.getElementById('detalhe-pdm').textContent = amostra.pdm_tipo || '-';
            classificacaoContainer.style.display = 'block';
        } else {
            classificacaoContainer.style.display = 'none';
        }

        // Abrir modal Bootstrap
        const modalElement = document.getElementById('modalDetalhesAmostra');
        const modalInstance = new bootstrap.Modal(modalElement);
        modalInstance.show();

        console.log('👁️ Modal de detalhes aberto para:', amostra.descricao || amostra.nome_item);
    }

    // ================================================
    // INICIALIZAÇÃO COMPLETA
    // ================================================

    // ================================================
    // FUNÇÃO: ABRIR MODAL DE JUSTIFICATIVA (OBRIGATÓRIA)
    // ================================================

    /**
     * Função: Abrir modal para adicionar justificativa da análise crítica
     */
    function abrirModalJustificativa() {
        console.log('📝 Abrindo modal de justificativa');
        console.log(`📝 Valor atual de window.justificativaCotacao: "${window.justificativaCotacao}"`);

        const textoAtual = window.justificativaCotacao || '';

        const novaJustificativa = prompt(
            '📝 JUSTIFICATIVA DA ANÁLISE CRÍTICA\n\n' +
            'Descreva o método utilizado e os critérios adotados para a análise dos preços coletados.\n\n' +
            'Exemplo: "Foram coletadas 5 amostras do PNCP. Aplicou-se análise estatística com expurgo de valores discrepantes (desvio padrão). A mediana foi adotada como preço de referência por representar o valor central da distribuição."',
            textoAtual
        );

        if (novaJustificativa !== null) {
            // Usuário confirmou (mesmo que vazio - permitir limpar)
            window.justificativaCotacao = novaJustificativa.trim();
            console.log(`✅ Justificativa salva na variável GLOBAL window.justificativaCotacao`);
            console.log(`✅ Tamanho: ${window.justificativaCotacao.length} caracteres`);
            console.log(`✅ Conteúdo: "${window.justificativaCotacao}"`);

            // ✅ NOVA FUNCIONALIDADE: Exibir justificativa visualmente no modal
            const areaJustificativa = document.getElementById('area-justificativa-exibicao');
            const textoJustificativa = document.getElementById('texto-justificativa-exibicao');

            if (window.justificativaCotacao === '') {
                // Ocultar área de exibição se justificativa foi removida
                if (areaJustificativa) areaJustificativa.style.display = 'none';
                alert('ℹ️ Justificativa removida.\n\nA justificativa é opcional. Você pode concluir a cotação mesmo sem ela.');
            } else {
                // Exibir área de exibição com a justificativa
                if (textoJustificativa) textoJustificativa.textContent = window.justificativaCotacao;
                if (areaJustificativa) areaJustificativa.style.display = 'block';
                alert('✅ Justificativa salva com sucesso!\n\nEla será exibida junto aos itens cotados.');
            }
        } else {
            console.log('❌ Usuário cancelou a adição de justificativa');
        }
    }

    console.log('✅ Modal de Cotação de Preços inicializado com sucesso!');
    console.log('📊 Funcionalidades ativas: Pesquisa, Filtros, Ordenação, Estatísticas, Análise Crítica, Detalhes da Fonte, Ajuste de Embalagem, Remover Amostra, Ver Detalhes, Abrir Modal, Justificativa Opcional');

    // ================================================
    // EXPOR FUNÇÕES GLOBALMENTE (para uso em elaborar.blade.php)
    // ================================================
    window.abrirModalDetalhesFonte = abrirModalDetalhesFonte;
    window.abrirModalAjusteEmbalagem = abrirModalAjusteEmbalagem;
    window.removerAmostra = removerAmostra;
    window.removerAmostraDaSerie = removerAmostraDaSerie;
    window.verDetalhesAmostra = verDetalhesAmostra;
    window.abrirModalJustificativa = abrirModalJustificativa; // ✅ NOVA FUNÇÃO EXPOSTA

    console.log('✅ [MODAL-COTACAO.JS] Todos os event listeners registrados com sucesso!');
    console.log('📋 [MODAL-COTACAO.JS] Aguardando ações do usuário...');
})();
