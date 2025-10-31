/**
 * ================================================
 * RECONSTRUÇÃO: Ajuste de Embalagem + Concluir Cotação
 * Data: 13/10/2025
 * Versão: SIMPLIFICADA e FUNCIONAL
 * ================================================
 */

// Esta é a NOVA versão que substitui as funções antigas

// ===== FUNÇÃO 1: CONCLUIR AJUSTE DE EMBALAGEM =====
function concluirAjusteEmbalagem() {
    console.log('🎯 === INÍCIO: Concluir Ajuste de Embalagem ===');

    // 1. Pegar valores do form
    const medidaDesejada = document.getElementById('ajuste-medida-desejada').value.trim();
    const fator = parseFloat(document.getElementById('ajuste-fator-multiplicacao').value);

    console.log('📋 Dados do ajuste:', { medidaDesejada, fator });

    // 2. Validar
    if (!medidaDesejada || !fator || fator <= 0) {
        alert('⚠️ Por favor, preencha:\n\n• Medida Desejada\n• Fator de Multiplicação (maior que zero)');
        return;
    }

    // 3. Verificar se temos o ajusteAtual
    if (!window.ajusteAtual || !window.ajusteAtual.index === undefined) {
        alert('❌ Erro: Dados do ajuste não encontrados. Tente novamente.');
        console.error('ajusteAtual não existe:', window.ajusteAtual);
        return;
    }

    const index = window.ajusteAtual.index;
    const precoOriginal = window.ajusteAtual.precoOriginal;
    const precoAjustado = precoOriginal * fator;

    console.log(`💰 Cálculo: ${precoOriginal} × ${fator} = ${precoAjustado}`);

    // 4. CRITICAL: Modificar DIRETAMENTE o array global resultadosFiltrados
    console.log(`🔧 Modificando resultadosFiltrados[${index}]...`);
    console.log('   ANTES:', {
        valor: window.resultadosFiltrados[index].valor_unitario,
        ajuste: window.resultadosFiltrados[index].ajuste_aplicado
    });

    // Modificar o objeto
    window.resultadosFiltrados[index].valor_unitario = precoAjustado;
    window.resultadosFiltrados[index].valor_unitario_original = precoOriginal;
    window.resultadosFiltrados[index].fator_ajuste = fator;
    window.resultadosFiltrados[index].ajuste_aplicado = true;
    window.resultadosFiltrados[index].unidade_medida_ajustada = medidaDesejada;

    console.log('   DEPOIS:', {
        valor: window.resultadosFiltrados[index].valor_unitario,
        ajuste: window.resultadosFiltrados[index].ajuste_aplicado,
        fator: window.resultadosFiltrados[index].fator_ajuste
    });

    // 5. Fechar modal de ajuste
    const modalAjuste = bootstrap.Modal.getInstance(document.getElementById('modalAjusteEmbalagem'));
    if (modalAjuste) {
        modalAjuste.hide();
    }

    // 6. Re-renderizar tabela (preserva checkboxes)
    console.log('🔄 Re-renderizando tabela...');
    if (typeof window.renderizarResultadosCotacao === 'function') {
        window.renderizarResultadosCotacao();
    }

    // 7. Aguardar renderização e FORÇAR atualização da análise crítica
    setTimeout(() => {
        console.log('📊 Atualizando Análise Crítica...');

        if (typeof window.atualizarAnaliseCriticaCotacao === 'function') {
            window.atualizarAnaliseCriticaCotacao();
        }

        // 8. Scroll para análise crítica
        const secaoAnalise = document.getElementById('secao-analise-critica');
        if (secaoAnalise) {
            secaoAnalise.scrollIntoView({ behavior: 'smooth', block: 'start' });
            console.log('📍 Scroll para Análise Crítica executado');
        }

        // 9. Alert de sucesso
        setTimeout(() => {
            alert(`✅ Ajuste aplicado com sucesso!\n\n` +
                  `📦 Unidade: ${medidaDesejada}\n` +
                  `🔢 Fator: ${fator}x\n` +
                  `💰 De: R$ ${precoOriginal.toFixed(2)}\n` +
                  `💰 Para: R$ ${precoAjustado.toFixed(2)}\n\n` +
                  `✅ Análise Crítica atualizada!`);
        }, 500);

    }, 300);

    console.log('🎯 === FIM: Concluir Ajuste de Embalagem ===');
}

// ===== FUNÇÃO 2: CONCLUIR COTAÇÃO =====
function concluirCotacaoPrecosModal() {
    console.log('🎯 === INÍCIO: Concluir Cotação ===');

    // 1. Verificar se tem amostras selecionadas
    const checkboxesMarcados = document.querySelectorAll('.checkbox-selecao-amostra:checked');
    if (checkboxesMarcados.length === 0) {
        alert('⚠️ Selecione pelo menos uma amostra!');
        return;
    }

    console.log(`📊 ${checkboxesMarcados.length} amostras selecionadas`);

    // 2. Extrair valores
    const valores = Array.from(checkboxesMarcados).map(cb => {
        const index = parseInt(cb.dataset.index);
        const valor = parseFloat(window.resultadosFiltrados[index].valor_unitario || 0);
        console.log(`   Amostra ${index}: R$ ${valor.toFixed(2)}`);
        return valor;
    }).sort((a, b) => a - b);

    console.log('💰 Valores ordenados:', valores);

    // 3. Calcular mediana
    const n = valores.length;
    const mediana = n % 2 === 0
        ? (valores[n/2 - 1] + valores[n/2]) / 2
        : valores[Math.floor(n/2)];

    console.log(`📊 Mediana calculada: R$ ${mediana.toFixed(2)}`);

    // 4. Verificar se temos o item atual
    if (!window.itemAtualCotacao || !window.itemAtualCotacao.id) {
        alert('❌ Erro: Item não identificado!\n\nFeche e abra o modal novamente.');
        console.error('itemAtualCotacao inválido:', window.itemAtualCotacao);
        return;
    }

    const itemId = window.itemAtualCotacao.id;
    console.log(`🎯 Item ID: ${itemId}`);

    // 5. Marcar checkbox do item (se não estiver marcado)
    const checkboxItem = document.querySelector(`.item-checkbox[data-item-id="${itemId}"]`);
    console.log('🔍 Checkbox do item:', checkboxItem);

    if (checkboxItem && !checkboxItem.checked) {
        console.log('✅ Marcando checkbox do item...');
        checkboxItem.checked = true;
        checkboxItem.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // 6. Aguardar processamento do checkbox (habilita campo)
    setTimeout(() => {

        // 7. Buscar campo de preço com múltiplos seletores
        let campoPreco = document.querySelector(`input[name="preco_unitario[${itemId}]"]`);
        if (!campoPreco) campoPreco = document.querySelector(`.preco-input[data-item-id="${itemId}"]`);
        if (!campoPreco) campoPreco = document.querySelector(`.cs-preco-input[data-item-id="${itemId}"]`);

        console.log('🔍 Campo de preço encontrado:', campoPreco);

        if (!campoPreco) {
            alert(`❌ Campo de preço não encontrado!\n\n` +
                  `Item ID: ${itemId}\n\n` +
                  `Verifique se está na Etapa 3 (Cadastramento de Itens).`);
            console.error('Campo de preço não encontrado para item:', itemId);
            return;
        }

        // 8. Habilitar campo
        console.log(`🔓 Habilitando campo (disabled=${campoPreco.disabled})...`);
        campoPreco.disabled = false;

        // 9. Aplicar valor
        const valorAntigo = campoPreco.value;
        campoPreco.value = mediana.toFixed(2);
        console.log(`💰 Valor aplicado: "${valorAntigo}" → "${campoPreco.value}"`);

        // 10. Disparar eventos
        console.log('📤 Disparando eventos...');
        campoPreco.dispatchEvent(new Event('input', { bubbles: true }));
        campoPreco.dispatchEvent(new Event('change', { bubbles: true }));
        campoPreco.dispatchEvent(new Event('blur', { bubbles: true }));

        // 11. Calcular e atualizar preço total MANUALMENTE
        const quantidade = parseFloat(campoPreco.getAttribute('data-quantidade')) || 0;
        const precoTotal = quantidade * mediana;
        console.log(`🧮 Preço total: ${quantidade} × ${mediana.toFixed(2)} = ${precoTotal.toFixed(2)}`);

        const spanPrecoTotal = document.querySelector(`.preco-total[data-item-id="${itemId}"]`);
        console.log('🔍 Span preço total:', spanPrecoTotal);

        if (spanPrecoTotal) {
            spanPrecoTotal.textContent = `R$ ${precoTotal.toFixed(2).replace('.', ',')}`;
            console.log(`✅ Preço total atualizado: ${spanPrecoTotal.textContent}`);
        }

        // 12. Destacar linha (pisca verde)
        const linha = campoPreco.closest('tr');
        if (linha) {
            linha.style.background = '#d1fae5';
            linha.style.transition = 'background 0.3s';
            setTimeout(() => { linha.style.background = ''; }, 3000);
        }

        // 13. Alert de sucesso
        alert(`✅ Cotação concluída!\n\n` +
              `📊 Amostras: ${checkboxesMarcados.length}\n` +
              `💰 Mediana: R$ ${mediana.toFixed(2).replace('.', ',')}\n` +
              `📦 Quantidade: ${quantidade}\n` +
              `💵 Total: R$ ${precoTotal.toFixed(2).replace('.', ',')}\n\n` +
              `✅ Preços atualizados na Etapa 3!`);

        // 14. Fechar modal
        const modalCotacao = bootstrap.Modal.getInstance(document.getElementById('modalCotacaoPrecos'));
        if (modalCotacao) {
            modalCotacao.hide();
        }

        console.log('🎯 === FIM: Concluir Cotação ===');

    }, 150); // Delay para checkbox ser processado
}

// ===== EXPOR FUNÇÕES GLOBALMENTE =====
window.concluirAjusteEmbalagemNovo = concluirAjusteEmbalagem;
window.concluirCotacaoPrecosModalNovo = concluirCotacaoPrecosModal;

console.log('✅ Funções de ajuste e cotação RECONSTRUÍDAS e prontas!');
