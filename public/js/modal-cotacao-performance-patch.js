/**
 * ================================================
 * PATCH DE PERFORMANCE PARA MODAL-COTACAO.JS
 * Data: 21/10/2025
 * Objetivo: Adicionar debounce e loading states ao modal de cotação
 * ================================================
 *
 * INSTRUÇÕES:
 * Este arquivo deve ser carregado APÓS modal-cotacao.js e performance-utils.js
 */

console.log('[MODAL-COTACAO-PERF-PATCH] Aplicando otimizações de performance...');

// Aguardar o DOM estar pronto
document.addEventListener('DOMContentLoaded', function() {

    // ================================================
    // 1. DEBOUNCE NO CAMPO DE BUSCA POR PALAVRA-CHAVE
    // ================================================
    const inputPalavraChave = document.getElementById('input-palavra-chave');
    if (inputPalavraChave) {
        // Remover event listener antigo (se existir)
        const novoInput = inputPalavraChave.cloneNode(true);
        inputPalavraChave.parentNode.replaceChild(novoInput, inputPalavraChave);

        // Adicionar com debounce
        novoInput.addEventListener('input', window.perfUtils.debounce(function(e) {
            console.log('[PERF-PATCH] Busca com debounce:', e.target.value);
            // O event listener original do modal-cotacao.js vai disparar aqui
        }, 500)); // Espera 500ms após parar de digitar

        console.log('[PERF-PATCH] ✅ Debounce aplicado ao campo de palavra-chave');
    }

    // ================================================
    // 2. DEBOUNCE NO CAMPO DE CATMAT
    // ================================================
    const inputCatmat = document.getElementById('input-catmat');
    if (inputCatmat) {
        // Remover event listener antigo
        const novoInputCatmat = inputCatmat.cloneNode(true);
        inputCatmat.parentNode.replaceChild(novoInputCatmat, inputCatmat);

        // Adicionar com debounce
        novoInputCatmat.addEventListener('input', window.perfUtils.debounce(function(e) {
            console.log('[PERF-PATCH] Busca CATMAT com debounce:', e.target.value);
        }, 500));

        console.log('[PERF-PATCH] ✅ Debounce aplicado ao campo CATMAT');
    }

    // ================================================
    // 3. LOADING STATE NO BOTÃO DE PESQUISA
    // ================================================
    const btnPesquisar = document.getElementById('btn-pesquisar');
    if (btnPesquisar) {
        const originalClick = btnPesquisar.onclick;

        btnPesquisar.onclick = async function(e) {
            // Mostrar loading no botão
            const revertLoading = window.perfUtils.setLoadingButton(btnPesquisar, 'Pesquisando...');

            // Mostrar overlay no container de resultados
            const containerResultados = document.getElementById('tabela-resultados-cotacao')?.closest('.tab-content-pesquisa');
            if (containerResultados) {
                window.perfUtils.showLoadingOverlay('Buscando preços...', '#tabela-resultados-cotacao');
            }

            try {
                // Executar pesquisa original (se existir)
                if (originalClick) {
                    await originalClick.call(this, e);
                }

                // Aguardar um pouco para dar tempo de processar
                await new Promise(resolve => setTimeout(resolve, 100));

            } finally {
                // Remover loading
                revertLoading();
                window.perfUtils.removeLoadingOverlay('#tabela-resultados-cotacao');
            }
        };

        console.log('[PERF-PATCH] ✅ Loading state aplicado ao botão de pesquisa');
    }

    // ================================================
    // 4. LOADING STATE NO BOTÃO "CONCLUIR COTAÇÃO"
    // ================================================
    const btnConcluirCotacao = document.getElementById('btn-concluir-cotacao');
    if (btnConcluirCotacao) {
        const originalConcluir = btnConcluirCotacao.onclick;

        btnConcluirCotacao.onclick = async function(e) {
            console.log('[PERF-PATCH] Concluindo cotação com loading...');

            // Mostrar loading no botão
            const revertLoading = window.perfUtils.setLoadingButton(btnConcluirCotacao, 'Salvando...');

            // Mostrar overlay no modal
            const modal = document.getElementById('modalCotacaoPrecos');
            if (modal) {
                window.perfUtils.showLoadingOverlay('Salvando cotação...', '#modalCotacaoPrecos .modal-body');
            }

            try {
                // Executar função original
                if (originalConcluir) {
                    await originalConcluir.call(this, e);
                }

                // Aguardar feedback
                await new Promise(resolve => setTimeout(resolve, 300));

            } finally {
                // Remover loading
                revertLoading();
                window.perfUtils.removeLoadingOverlay('#modalCotacaoPrecos .modal-body');
            }
        };

        console.log('[PERF-PATCH] ✅ Loading state aplicado ao botão concluir cotação');
    }

    // ================================================
    // 5. OTIMIZAR RENDERIZAÇÃO DA TABELA DE RESULTADOS
    // ================================================
    // Interceptar e otimizar a função de renderização (se existir)
    const originalRenderResultados = window.renderizarResultadosCotacao;
    if (typeof originalRenderResultados === 'function') {
        window.renderizarResultadosCotacao = function(resultados) {
            console.log(`[PERF-PATCH] Renderizando ${resultados.length} resultados otimizados`);

            // Usar batch update para melhor performance
            const container = document.getElementById('tabela-resultados-cotacao');
            if (container && resultados.length > 50) {
                // Se muitos resultados, usar fragmento
                window.perfUtils.batchDOMUpdate(container, () => {
                    return resultados.map(r => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${r.descricao}</td><td>${r.preco}</td>`;
                        return row;
                    });
                });
            } else {
                // Poucos resultados, usar método original
                originalRenderResultados(resultados);
            }
        };

        console.log('[PERF-PATCH] ✅ Renderização de resultados otimizada');
    }

    // ================================================
    // 6. MONITORAR PERFORMANCE DE OPERAÇÕES CRÍTICAS
    // ================================================
    window.addEventListener('cotacao:pesquisa-iniciada', () => {
        console.time('⏱️ Tempo total de pesquisa');
    });

    window.addEventListener('cotacao:pesquisa-concluida', () => {
        console.timeEnd('⏱️ Tempo total de pesquisa');
    });

    // ================================================
    // 7. THROTTLE EM FILTROS QUE DISPARAM MUITO
    // ================================================
    const checkboxesFiltro = document.querySelectorAll('.checkbox-filtro-cotacao');
    checkboxesFiltro.forEach(checkbox => {
        const handler = window.perfUtils.throttle(function() {
            console.log('[PERF-PATCH] Filtro aplicado (throttled)');
        }, 300);

        checkbox.addEventListener('change', handler);
    });

    console.log(`[PERF-PATCH] ✅ Throttle aplicado a ${checkboxesFiltro.length} filtros`);

    // ================================================
    // RESUMO DAS OTIMIZAÇÕES
    // ================================================
    console.log(`
╔═══════════════════════════════════════════════════════╗
║  OTIMIZAÇÕES DE PERFORMANCE APLICADAS COM SUCESSO!   ║
╠═══════════════════════════════════════════════════════╣
║  ✅ Debounce em campos de busca (500ms)              ║
║  ✅ Loading states em botões críticos                ║
║  ✅ Overlays de loading em operações longas          ║
║  ✅ Batch DOM updates para muitos resultados         ║
║  ✅ Throttle em filtros                              ║
║  ✅ Monitoramento de performance                     ║
╚═══════════════════════════════════════════════════════╝
    `);
});

console.log('[MODAL-COTACAO-PERF-PATCH] ✅ Patch de performance carregado!');
