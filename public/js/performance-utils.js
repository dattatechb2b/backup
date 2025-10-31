/**
 * ================================================
 * PERFORMANCE UTILITIES - OTIMIZAÇÃO DE PERFORMANCE
 * Data: 21/10/2025
 * Objetivo: Melhorar performance do sistema com debounce, throttle e loading states
 * ================================================
 */

console.log('[PERF-UTILS] Carregando utilitários de performance...');

/**
 * DEBOUNCE - Atrasa execução até que o usuário pare de interagir
 * Útil para: campos de busca, inputs, auto-save
 *
 * @param {Function} func - Função a ser executada
 * @param {Number} wait - Tempo de espera em ms (padrão: 300ms)
 * @returns {Function}
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * THROTTLE - Limita execuções a um intervalo específico
 * Útil para: scroll, resize, eventos de mouse frequentes
 *
 * @param {Function} func - Função a ser executada
 * @param {Number} limit - Intervalo mínimo em ms (padrão: 100ms)
 * @returns {Function}
 */
function throttle(func, limit = 100) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * LOADING OVERLAY - Mostra overlay de carregamento
 *
 * @param {String} message - Mensagem a ser exibida (padrão: 'Carregando...')
 * @param {String} targetSelector - Seletor do elemento pai (padrão: 'body')
 * @returns {HTMLElement} - Elemento do overlay criado
 */
function showLoadingOverlay(message = 'Carregando...', targetSelector = 'body') {
    const target = document.querySelector(targetSelector);
    if (!target) return null;

    // Remover overlay existente (se houver)
    removeLoadingOverlay(targetSelector);

    // Criar overlay
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay-perf';
    overlay.innerHTML = `
        <div class="loading-spinner-perf">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="loading-message-perf mt-3">${message}</div>
        </div>
    `;

    // Adicionar estilos inline (caso CSS não esteja carregado)
    overlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(2px);
    `;

    overlay.querySelector('.loading-spinner-perf').style.cssText = `
        text-align: center;
        color: #333;
    `;

    target.style.position = 'relative';
    target.appendChild(overlay);

    console.log(`[PERF-UTILS] Loading overlay criado em: ${targetSelector}`);
    return overlay;
}

/**
 * REMOVE LOADING OVERLAY - Remove overlay de carregamento
 *
 * @param {String} targetSelector - Seletor do elemento pai (padrão: 'body')
 */
function removeLoadingOverlay(targetSelector = 'body') {
    const target = document.querySelector(targetSelector);
    if (!target) return;

    const overlay = target.querySelector('.loading-overlay-perf');
    if (overlay) {
        overlay.remove();
        console.log(`[PERF-UTILS] Loading overlay removido de: ${targetSelector}`);
    }
}

/**
 * LOADING BUTTON - Desabilita botão e mostra spinner
 *
 * @param {HTMLElement|String} button - Elemento ou seletor do botão
 * @param {String} loadingText - Texto durante carregamento (padrão: 'Processando...')
 * @returns {Function} - Função para reverter ao estado original
 */
function setLoadingButton(button, loadingText = 'Processando...') {
    const btn = typeof button === 'string' ? document.querySelector(button) : button;
    if (!btn) return () => {};

    // Salvar estado original
    const originalText = btn.innerHTML;
    const wasDisabled = btn.disabled;

    // Aplicar loading
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        ${loadingText}
    `;

    console.log(`[PERF-UTILS] Loading state aplicado ao botão`);

    // Retornar função para reverter
    return function() {
        btn.disabled = wasDisabled;
        btn.innerHTML = originalText;
        console.log(`[PERF-UTILS] Loading state removido do botão`);
    };
}

/**
 * BATCH DOM UPDATES - Agrupa múltiplas atualizações do DOM
 * Usa DocumentFragment para melhor performance
 *
 * @param {HTMLElement} container - Elemento container
 * @param {Function} updateFn - Função que retorna array de elementos
 */
function batchDOMUpdate(container, updateFn) {
    const fragment = document.createDocumentFragment();
    const elements = updateFn();

    elements.forEach(el => fragment.appendChild(el));

    // Limpar container e adicionar tudo de uma vez
    container.innerHTML = '';
    container.appendChild(fragment);

    console.log(`[PERF-UTILS] Batch DOM update: ${elements.length} elementos`);
}

/**
 * DEFER HEAVY TASK - Adia tarefa pesada para não bloquear UI
 * Usa requestIdleCallback ou setTimeout como fallback
 *
 * @param {Function} task - Tarefa a ser executada
 * @param {Object} options - Opções (timeout em ms)
 */
function deferHeavyTask(task, options = { timeout: 2000 }) {
    if ('requestIdleCallback' in window) {
        requestIdleCallback(task, options);
    } else {
        setTimeout(task, 0);
    }
    console.log(`[PERF-UTILS] Tarefa pesada adiada`);
}

/**
 * MEASURE PERFORMANCE - Mede tempo de execução de uma função
 *
 * @param {String} label - Label para identificar
 * @param {Function} fn - Função a ser medida
 * @returns {*} - Resultado da função
 */
async function measurePerf(label, fn) {
    const start = performance.now();
    console.log(`[PERF] 🏁 Iniciando: ${label}`);

    try {
        const result = await fn();
        const end = performance.now();
        const duration = (end - start).toFixed(2);

        if (duration > 1000) {
            console.warn(`[PERF] ⚠️ ${label} demorou ${duration}ms (>1s)`);
        } else if (duration > 500) {
            console.log(`[PERF] ⏱️ ${label} demorou ${duration}ms`);
        } else {
            console.log(`[PERF] ✅ ${label} concluído em ${duration}ms`);
        }

        return result;
    } catch (error) {
        console.error(`[PERF] ❌ Erro em ${label}:`, error);
        throw error;
    }
}

// Exportar para window (global)
window.perfUtils = {
    debounce,
    throttle,
    showLoadingOverlay,
    removeLoadingOverlay,
    setLoadingButton,
    batchDOMUpdate,
    deferHeavyTask,
    measurePerf
};

console.log('[PERF-UTILS] ✅ Utilitários carregados e disponíveis em window.perfUtils');
