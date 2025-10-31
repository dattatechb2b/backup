/**
 * ============================================================================
 * REFACTOR COMPLETO DOS MODALS - CRIADO DO ZERO
 * ============================================================================
 * Data: 16/10/2025
 * Modals refatorados:
 * 1. Modal de Análise Crítica das Amostras
 * 2. Modal de Alterar Item
 * 3. Modal "Busque o Melhor Fornecedor" (PNCP)
 * 4. Modal "Importar Cadastro Local"
 * ============================================================================
 */

(function() {
    'use strict';

    console.log('[MODAL-REFACTOR] Iniciando refactor completo dos modals...');

    // ========================================================================
    // UTILIDADES GLOBAIS
    // ========================================================================

    const utils = {
        // Formatar moeda BRL
        formatarMoeda: function(valor) {
            if (!valor && valor !== 0) return 'R$ 0,00';
            return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        // Remover listeners antigos clonando elemento
        limparEventos: function(elemento) {
            if (!elemento) return null;
            const novo = elemento.cloneNode(true);
            elemento.parentNode.replaceChild(novo, elemento);
            return novo;
        },

        // Fetch com tratamento de erro
        fetchAPI: async function(url, options = {}) {
            try {
                const response = await fetch(url, options);
                const data = await response.json();
                return { ok: response.ok, status: response.status, data };
            } catch (error) {
                console.error('[FETCH-ERROR]', error);
                return { ok: false, error: error.message };
            }
        },

        // Mostrar loading
        showLoading: function(elemento, texto = 'Carregando...') {
            if (!elemento) return;
            elemento.dataset.originalHtml = elemento.innerHTML;
            elemento.disabled = true;
            elemento.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${texto}`;
        },

        // Esconder loading
        hideLoading: function(elemento) {
            if (!elemento) return;
            elemento.disabled = false;
            elemento.innerHTML = elemento.dataset.originalHtml || elemento.innerHTML;
        }
    };

    // ========================================================================
    // 1. MODAL: ANÁLISE CRÍTICA DAS AMOSTRAS
    // ========================================================================

    const ModalAnaliseCritica = {
        modal: null,
        modalElement: null,
        currentItemId: null,

        init: function() {
            console.log('[ANALISE-CRITICA] Inicializando modal...');

            this.modalElement = document.getElementById('modalAnaliseCritica');
            if (!this.modalElement) {
                console.error('[ANALISE-CRITICA] Modal element not found!');
                return;
            }

            this.modal = new bootstrap.Modal(this.modalElement, {
                backdrop: 'static',
                keyboard: false
            });

            this.setupEventListeners();
            console.log('[ANALISE-CRITICA] Modal inicializado!');
        },

        setupEventListeners: function() {
            // Botões de abrir modal (delegação de eventos)
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-analise-critica');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const itemId = btn.dataset.itemId || btn.getAttribute('data-item-id');
                    if (itemId) {
                        console.log('[ANALISE-CRITICA] Abrindo para item:', itemId);
                        this.abrir(itemId);
                    }
                }
            });

            // Botão remover amostra individual
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-remover-amostra');
                if (btn) {
                    e.preventDefault();
                    const amostraId = btn.dataset.amostraId;
                    if (amostraId && confirm('Deseja remover esta amostra?')) {
                        this.removerAmostra(amostraId);
                    }
                }
            });

            // Botão remover todas as amostras
            const btnRemoverTodas = document.getElementById('analise-btn-remover-todas-amostras');
            if (btnRemoverTodas) {
                const newBtn = utils.limparEventos(btnRemoverTodas);
                newBtn.addEventListener('click', () => {
                    if (confirm('Deseja remover TODAS as amostras? Esta ação não pode ser desfeita.')) {
                        this.removerTodasAmostras();
                    }
                });
            }

            // Checkboxes de críticas
            const checkbox1 = document.getElementById('analise-critica-medidas-desiguais');
            const checkbox2 = document.getElementById('analise-critica-valores-discrepantes');

            if (checkbox1) {
                const newCheck1 = utils.limparEventos(checkbox1);
                newCheck1.addEventListener('change', () => this.salvarCriticas());
            }

            if (checkbox2) {
                const newCheck2 = utils.limparEventos(checkbox2);
                newCheck2.addEventListener('change', () => this.salvarCriticas());
            }

            console.log('[ANALISE-CRITICA] Event listeners configurados!');
        },

        abrir: async function(itemId) {
            this.currentItemId = itemId;

            // Limpar dados anteriores
            this.limparDados();

            // Carregar dados do item
            await this.carregarDados(itemId);

            // Mostrar modal
            this.modal.show();
        },

        limparDados: function() {
            document.getElementById('analise-item-descricao').textContent = '-';
            document.getElementById('analise-juizo-num-amostras').textContent = '0';
            document.getElementById('analise-juizo-media').textContent = 'R$ 0,00';
            document.getElementById('analise-juizo-desvio').textContent = '0,00';
            document.getElementById('analise-juizo-limite-inf').textContent = 'R$ 0,00 (DP - média)';
            document.getElementById('analise-juizo-limite-sup').textContent = 'R$ 0,00 (DP + média)';
            document.getElementById('analise-juizo-criticas').textContent = '0';
            document.getElementById('analise-juizo-expurgadas').textContent = '0';
            document.getElementById('analise-serie-tbody').innerHTML = '';
            document.getElementById('analise-serie-vazia').style.display = 'block';
            document.getElementById('analise-serie-tabela').style.display = 'none';
        },

        carregarDados: async function(itemId) {
            console.log('[ANALISE-CRITICA] Carregando dados do item:', itemId);

            const url = `${window.APP_BASE_PATH}/orcamentos/${window.ORCAMENTO_ID}/itens/${itemId}/analise-critica`;

            const result = await utils.fetchAPI(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!result.ok) {
                console.error('[ANALISE-CRITICA] Erro ao carregar:', result);
                alert('Erro ao carregar análise crítica. Tente novamente.');
                return;
            }

            const dados = result.data;
            console.log('[ANALISE-CRITICA] Dados recebidos:', dados);

            // Preencher descrição do item
            document.getElementById('analise-item-descricao').textContent = dados.item?.descricao || 'Item sem descrição';

            // Preencher juízo crítico
            if (dados.juizo_critico) {
                const jc = dados.juizo_critico;
                document.getElementById('analise-juizo-num-amostras').textContent = jc.num_amostras || 0;
                document.getElementById('analise-juizo-media').textContent = utils.formatarMoeda(jc.media);
                document.getElementById('analise-juizo-desvio').textContent = (jc.desvio_padrao || 0).toFixed(2);
                document.getElementById('analise-juizo-limite-inf').textContent = utils.formatarMoeda(jc.limite_inferior) + ' (DP - média)';
                document.getElementById('analise-juizo-limite-sup').textContent = utils.formatarMoeda(jc.limite_superior) + ' (DP + média)';
                document.getElementById('analise-juizo-criticas').textContent = jc.num_criticas || 0;
                document.getElementById('analise-juizo-expurgadas').textContent = jc.num_expurgadas || 0;
            }

            // Preencher método estatístico
            if (dados.metodo_estatistico) {
                const me = dados.metodo_estatistico;
                document.getElementById('analise-metodo-num-validas').textContent = me.num_validas || 0;
                document.getElementById('analise-metodo-desvio').textContent = (me.desvio_padrao || 0).toFixed(2);
                document.getElementById('analise-metodo-coef-var').textContent = (me.coeficiente_variacao || 0).toFixed(2) + '%';
                document.getElementById('analise-metodo-menor').textContent = utils.formatarMoeda(me.menor_preco);
                document.getElementById('analise-metodo-media').textContent = utils.formatarMoeda(me.media);
                document.getElementById('analise-metodo-mediana').textContent = utils.formatarMoeda(me.mediana);
            }

            // Preencher valores finais
            if (dados.valores_finais) {
                const vf = dados.valores_finais;
                document.getElementById('analise-final-mediana').textContent = utils.formatarMoeda(vf.mediana);
                document.getElementById('analise-final-media').textContent = utils.formatarMoeda(vf.media);
                document.getElementById('analise-final-menor').textContent = utils.formatarMoeda(vf.menor_preco);
                document.getElementById('analise-final-tendencia').textContent = utils.formatarMoeda(vf.tendencia_central);
            }

            // Preencher amostras
            this.preencherAmostras(dados.amostras || []);

            // Preencher checkboxes de críticas
            if (dados.criticas) {
                document.getElementById('analise-critica-medidas-desiguais').checked = !!dados.criticas.medidas_desiguais;
                document.getElementById('analise-critica-valores-discrepantes').checked = !!dados.criticas.valores_discrepantes;
            }
        },

        preencherAmostras: function(amostras) {
            const tbody = document.getElementById('analise-serie-tbody');
            tbody.innerHTML = '';

            if (!amostras || amostras.length === 0) {
                document.getElementById('analise-serie-vazia').style.display = 'block';
                document.getElementById('analise-serie-tabela').style.display = 'none';
                return;
            }

            document.getElementById('analise-serie-vazia').style.display = 'none';
            document.getElementById('analise-serie-tabela').style.display = 'block';

            amostras.forEach((amostra, index) => {
                const tr = document.createElement('tr');

                // Determinar cor da situação
                let situacaoBadge = '<span class="badge bg-success">VÁLIDA</span>';
                if (amostra.situacao === 'expurgada') {
                    situacaoBadge = '<span class="badge bg-danger">EXPURGADA</span>';
                } else if (amostra.situacao === 'critica') {
                    situacaoBadge = '<span class="badge bg-warning text-dark">CRÍTICA</span>';
                }

                tr.innerHTML = `
                    <td style="text-align: center;">${index + 1}</td>
                    <td style="text-align: center;">${situacaoBadge}</td>
                    <td>${amostra.fonte || 'N/A'}</td>
                    <td>${amostra.marca || '-'}</td>
                    <td>${amostra.data || '-'}</td>
                    <td>${amostra.medida || '-'}</td>
                    <td style="text-align: right;">${amostra.quantidade_original || '-'}</td>
                    <td style="text-align: right; font-weight: 600;">${utils.formatarMoeda(amostra.valor_unitario)}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn btn-sm btn-danger btn-remover-amostra" data-amostra-id="${amostra.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        },

        removerAmostra: async function(amostraId) {
            console.log('[ANALISE-CRITICA] Removendo amostra:', amostraId);

            const url = `${window.APP_BASE_PATH}/orcamentos/${window.ORCAMENTO_ID}/amostras/${amostraId}`;

            const result = await utils.fetchAPI(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!result.ok) {
                console.error('[ANALISE-CRITICA] Erro ao remover amostra:', result);
                alert('Erro ao remover amostra. Tente novamente.');
                return;
            }

            alert('Amostra removida com sucesso!');

            // Recarregar dados
            await this.carregarDados(this.currentItemId);
        },

        removerTodasAmostras: async function() {
            console.log('[ANALISE-CRITICA] Removendo todas as amostras do item:', this.currentItemId);

            const url = `${window.APP_BASE_PATH}/orcamentos/${window.ORCAMENTO_ID}/itens/${this.currentItemId}/amostras`;

            const result = await utils.fetchAPI(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!result.ok) {
                console.error('[ANALISE-CRITICA] Erro ao remover todas as amostras:', result);
                alert('Erro ao remover amostras. Tente novamente.');
                return;
            }

            alert('Todas as amostras foram removidas!');

            // Recarregar dados
            await this.carregarDados(this.currentItemId);
        },

        salvarCriticas: async function() {
            console.log('[ANALISE-CRITICA] Salvando críticas...');

            const medidasDesiguais = document.getElementById('analise-critica-medidas-desiguais').checked;
            const valoresDiscrepantes = document.getElementById('analise-critica-valores-discrepantes').checked;

            const url = `${window.APP_BASE_PATH}/orcamentos/${window.ORCAMENTO_ID}/itens/${this.currentItemId}/criticas`;

            const result = await utils.fetchAPI(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    medidas_desiguais: medidasDesiguais,
                    valores_discrepantes: valoresDiscrepantes
                })
            });

            if (!result.ok) {
                console.error('[ANALISE-CRITICA] Erro ao salvar críticas:', result);
                alert('Erro ao salvar críticas. Tente novamente.');
                return;
            }

            console.log('[ANALISE-CRITICA] Críticas salvas!');
        }
    };

    // ========================================================================
    // 2. MODAL: ALTERAR ITEM
    // ========================================================================

    const ModalAlterarItem = {
        modal: null,
        modalElement: null,
        currentItemId: null,

        init: function() {
            console.log('[ALTERAR-ITEM] Inicializando modal...');

            this.modalElement = document.getElementById('modalEditarItem');
            if (!this.modalElement) {
                console.error('[ALTERAR-ITEM] Modal element not found!');
                return;
            }

            this.modal = new bootstrap.Modal(this.modalElement, {
                backdrop: 'static',
                keyboard: false
            });

            this.setupEventListeners();
            console.log('[ALTERAR-ITEM] Modal inicializado!');
        },

        setupEventListeners: function() {
            // Botões de abrir modal (delegação de eventos)
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-editar-item');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const itemId = btn.dataset.itemId || btn.getAttribute('data-item-id');
                    if (itemId) {
                        console.log('[ALTERAR-ITEM] Abrindo para item:', itemId);
                        this.abrir(itemId);
                    }
                }
            });

            // Botão salvar
            const btnSalvar = document.getElementById('btnSalvarEdicao');
            if (btnSalvar) {
                const newBtn = utils.limparEventos(btnSalvar);
                newBtn.addEventListener('click', () => this.salvar());
            }

            console.log('[ALTERAR-ITEM] Event listeners configurados!');
        },

        abrir: async function(itemId) {
            this.currentItemId = itemId;

            // Limpar formulário
            this.limparFormulario();

            // Carregar dados do item
            await this.carregarDados(itemId);

            // Mostrar modal
            this.modal.show();
        },

        limparFormulario: function() {
            const form = document.getElementById('formEditarItem');
            if (form) form.reset();
        },

        carregarDados: async function(itemId) {
            console.log('[ALTERAR-ITEM] Carregando dados do item:', itemId);

            const url = `${window.APP_BASE_PATH}/orcamentos/${window.ORCAMENTO_ID}/itens/${itemId}`;

            const result = await utils.fetchAPI(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!result.ok) {
                console.error('[ALTERAR-ITEM] Erro ao carregar:', result);
                alert('Erro ao carregar dados do item. Tente novamente.');
                this.modal.hide();
                return;
            }

            const item = result.data;
            console.log('[ALTERAR-ITEM] Dados recebidos:', item);

            // Preencher campos
            document.getElementById('edit-descricao').value = item.descricao || '';
            document.getElementById('edit-medida').value = item.medida_fornecimento || '';
            document.getElementById('edit-quantidade').value = item.quantidade || '';

            // Tipo de quantidade
            const tipoQtd = item.tipo_quantidade || 'inteiro';
            const radioQtd = document.querySelector(`input[name="edit-tipo-quantidade"][value="${tipoQtd}"]`);
            if (radioQtd) radioQtd.checked = true;

            // Tipo de marca
            const tipoMarca = item.tipo_marca || 'referencia';
            const radioMarca = document.querySelector(`input[name="edit-tipo-marca"][value="${tipoMarca}"]`);
            if (radioMarca) radioMarca.checked = true;

            // Tipo (produto/serviço)
            const tipo = item.tipo || 'produto';
            const radioTipo = document.querySelector(`input[name="edit-tipo"][value="${tipo}"]`);
            if (radioTipo) radioTipo.checked = true;

            // Alterar CDF
            const alterarCdf = item.alterar_cdf ? '1' : '0';
            const radioAlterar = document.querySelector(`input[name="edit-alterar-cdf"][value="${alterarCdf}"]`);
            if (radioAlterar) radioAlterar.checked = true;
        },

        salvar: async function() {
            console.log('[ALTERAR-ITEM] Salvando alterações...');

            const form = document.getElementById('formEditarItem');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const dados = {
                descricao: document.getElementById('edit-descricao').value,
                medida_fornecimento: document.getElementById('edit-medida').value,
                quantidade: document.getElementById('edit-quantidade').value,
                tipo_quantidade: document.querySelector('input[name="edit-tipo-quantidade"]:checked')?.value || 'inteiro',
                tipo_marca: document.querySelector('input[name="edit-tipo-marca"]:checked')?.value || 'referencia',
                tipo: document.querySelector('input[name="edit-tipo"]:checked')?.value || 'produto',
                alterar_cdf: document.querySelector('input[name="edit-alterar-cdf"]:checked')?.value === '1'
            };

            console.log('[ALTERAR-ITEM] Dados a enviar:', dados);

            const btnSalvar = document.getElementById('btnSalvarEdicao');
            utils.showLoading(btnSalvar, 'Salvando...');

            const url = `${window.APP_BASE_PATH}/orcamentos/${window.ORCAMENTO_ID}/itens/${this.currentItemId}`;

            const result = await utils.fetchAPI(url, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(dados)
            });

            utils.hideLoading(btnSalvar);

            if (!result.ok) {
                console.error('[ALTERAR-ITEM] Erro ao salvar:', result);
                const errorMsg = result.data?.message || 'Erro ao salvar alterações. Tente novamente.';
                alert(errorMsg);
                return;
            }

            console.log('[ALTERAR-ITEM] Item alterado com sucesso!');
            alert('Item alterado com sucesso!');

            this.modal.hide();

            // Recarregar lista de itens
            if (typeof carregarItens === 'function') {
                carregarItens();
            } else {
                window.location.reload();
            }
        }
    };

    // ========================================================================
    // 3. MODAL: BUSQUE O MELHOR FORNECEDOR (PNCP)
    // ========================================================================

    const ModalBuscaFornecedorPNCP = {
        modal: null,
        modalElement: null,

        init: function() {
            console.log('[BUSCA-PNCP] Inicializando modal...');

            this.modalElement = document.getElementById('modalBuscaFornecedorPNCP');
            if (!this.modalElement) {
                console.error('[BUSCA-PNCP] Modal element not found!');
                return;
            }

            this.modal = new bootstrap.Modal(this.modalElement, {
                backdrop: 'static',
                keyboard: false
            });

            this.setupEventListeners();
            console.log('[BUSCA-PNCP] Modal inicializado!');
        },

        setupEventListeners: function() {
            // Botão de buscar
            const btnBuscar = document.getElementById('btn-buscar-fornecedor-pncp');
            if (btnBuscar) {
                const newBtn = utils.limparEventos(btnBuscar);
                newBtn.addEventListener('click', () => this.buscar());
            }

            // Enter no campo de busca
            const inputBusca = document.getElementById('busca-fornecedor-pncp-input');
            if (inputBusca) {
                const newInput = utils.limparEventos(inputBusca);
                newInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.buscar();
                    }
                });
            }

            // Delegação: Botões de selecionar fornecedor
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-selecionar-fornecedor-pncp');
                if (btn) {
                    e.preventDefault();
                    const fornecedorData = btn.dataset.fornecedor;
                    if (fornecedorData) {
                        try {
                            const fornecedor = JSON.parse(fornecedorData);
                            this.selecionarFornecedor(fornecedor);
                        } catch (err) {
                            console.error('[BUSCA-PNCP] Erro ao parsear fornecedor:', err);
                        }
                    }
                }
            });

            console.log('[BUSCA-PNCP] Event listeners configurados!');
        },

        buscar: async function() {
            const input = document.getElementById('busca-fornecedor-pncp-input');
            const termo = input?.value.trim();

            if (!termo || termo.length < 3) {
                alert('Digite pelo menos 3 caracteres para buscar.');
                return;
            }

            console.log('[BUSCA-PNCP] Buscando:', termo);

            const btnBuscar = document.getElementById('btn-buscar-fornecedor-pncp');
            const resultsDiv = document.getElementById('busca-fornecedor-pncp-input-results');

            utils.showLoading(btnBuscar, 'Buscando...');
            resultsDiv.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #3b82f6; margin-bottom: 15px;"></i>
                    <p style="font-size: 14px; color: #6b7280;">Buscando no PNCP...</p>
                </div>
            `;

            const url = `${window.APP_BASE_PATH}/fornecedores/buscar-pncp?termo=${encodeURIComponent(termo)}`;

            const result = await utils.fetchAPI(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            utils.hideLoading(btnBuscar);

            if (!result.ok) {
                console.error('[BUSCA-PNCP] Erro na busca:', result);
                resultsDiv.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc2626; margin-bottom: 15px;"></i>
                        <p style="font-size: 14px; color: #dc2626;">Erro ao buscar fornecedores. Tente novamente.</p>
                    </div>
                `;
                return;
            }

            const fornecedores = result.data.fornecedores || [];
            console.log('[BUSCA-PNCP] Encontrados:', fornecedores.length, 'fornecedores');

            this.renderizarResultados(fornecedores, resultsDiv);
        },

        renderizarResultados: function(fornecedores, container) {
            if (!fornecedores || fornecedores.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #9ca3af; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p style="font-size: 14px; color: #6b7280;">Nenhum fornecedor encontrado.</p>
                        <p style="font-size: 12px; color: #9ca3af;">Tente usar termos diferentes na busca.</p>
                    </div>
                `;
                return;
            }

            let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';

            fornecedores.forEach(f => {
                const cnpj = f.cnpj || f.cpf || 'N/A';
                const razaoSocial = f.razao_social || f.nome || 'Sem nome';
                const cidade = f.cidade || f.municipio || '-';
                const uf = f.uf || f.estado || '-';

                html += `
                    <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; background: white;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 14px; color: #1f2937; margin-bottom: 4px;">
                                    ${razaoSocial}
                                </div>
                                <div style="font-size: 12px; color: #6b7280;">
                                    <strong>CNPJ:</strong> ${cnpj}
                                </div>
                                <div style="font-size: 12px; color: #6b7280;">
                                    <strong>Município:</strong> ${cidade} - ${uf}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary btn-selecionar-fornecedor-pncp"
                                data-fornecedor='${JSON.stringify(f)}'
                                style="font-size: 12px; white-space: nowrap;"
                            >
                                <i class="fas fa-check"></i> Selecionar
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        },

        selecionarFornecedor: function(fornecedor) {
            console.log('[BUSCA-PNCP] Fornecedor selecionado:', fornecedor);

            // Preencher campos do formulário principal
            const cnpj = fornecedor.cnpj || fornecedor.cpf || '';
            const razaoSocial = fornecedor.razao_social || fornecedor.nome || '';
            const cidade = fornecedor.cidade || fornecedor.municipio || '';
            const uf = fornecedor.uf || fornecedor.estado || '';

            // Campos do wizard CDF
            const inputCNPJ = document.getElementById('fornecedor_cnpj') || document.querySelector('input[name="fornecedor_cnpj"]');
            const inputRazao = document.getElementById('fornecedor_razao_social') || document.querySelector('input[name="fornecedor_razao_social"]');
            const inputCidade = document.getElementById('fornecedor_cidade') || document.querySelector('input[name="fornecedor_cidade"]');
            const inputUF = document.getElementById('fornecedor_uf') || document.querySelector('select[name="fornecedor_uf"]');

            if (inputCNPJ) inputCNPJ.value = cnpj;
            if (inputRazao) inputRazao.value = razaoSocial;
            if (inputCidade) inputCidade.value = cidade;
            if (inputUF) inputUF.value = uf;

            alert(`Fornecedor "${razaoSocial}" selecionado com sucesso!`);

            // Fechar modal
            this.modal.hide();
        }
    };

    // ========================================================================
    // 4. MODAL: IMPORTAR CADASTRO LOCAL
    // ========================================================================

    const ModalImportarLocal = {
        modal: null,
        modalElement: null,

        init: function() {
            console.log('[IMPORTAR-LOCAL] Inicializando modal...');

            this.modalElement = document.getElementById('modalBuscaFornecedorLocal');
            if (!this.modalElement) {
                console.error('[IMPORTAR-LOCAL] Modal element not found!');
                return;
            }

            this.modal = new bootstrap.Modal(this.modalElement, {
                backdrop: 'static',
                keyboard: false
            });

            this.setupEventListeners();
            this.carregarFornecedoresAoAbrir();
            console.log('[IMPORTAR-LOCAL] Modal inicializado!');
        },

        setupEventListeners: function() {
            // Botão de buscar
            const btnBuscar = document.getElementById('btn-buscar-fornecedor-local');
            if (btnBuscar) {
                const newBtn = utils.limparEventos(btnBuscar);
                newBtn.addEventListener('click', () => this.buscar());
            }

            // Enter no campo de busca
            const inputBusca = document.getElementById('busca-fornecedor-local-input');
            if (inputBusca) {
                const newInput = utils.limparEventos(inputBusca);
                newInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.buscar();
                    }
                });
            }

            // Delegação: Botões de selecionar fornecedor
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-selecionar-fornecedor-local');
                if (btn) {
                    e.preventDefault();
                    const fornecedorData = btn.dataset.fornecedor;
                    if (fornecedorData) {
                        try {
                            const fornecedor = JSON.parse(fornecedorData);
                            this.selecionarFornecedor(fornecedor);
                        } catch (err) {
                            console.error('[IMPORTAR-LOCAL] Erro ao parsear fornecedor:', err);
                        }
                    }
                }
            });

            console.log('[IMPORTAR-LOCAL] Event listeners configurados!');
        },

        carregarFornecedoresAoAbrir: function() {
            // Quando o modal for mostrado, carregar todos os fornecedores
            this.modalElement.addEventListener('shown.bs.modal', async () => {
                console.log('[IMPORTAR-LOCAL] Modal aberto - carregando fornecedores...');

                const resultsDiv = document.getElementById('busca-fornecedor-local-input-results');
                resultsDiv.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #0ea5e9; margin-bottom: 15px;"></i>
                        <p style="font-size: 14px; color: #6b7280;">Carregando fornecedores cadastrados...</p>
                    </div>
                `;

                await this.carregarTodos();
            });
        },

        carregarTodos: async function() {
            const url = `${window.APP_BASE_PATH}/fornecedores/listar-local`;

            const result = await utils.fetchAPI(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const resultsDiv = document.getElementById('busca-fornecedor-local-input-results');

            if (!result.ok) {
                console.error('[IMPORTAR-LOCAL] Erro ao carregar:', result);
                resultsDiv.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc2626; margin-bottom: 15px;"></i>
                        <p style="font-size: 14px; color: #dc2626;">Erro ao carregar fornecedores. Tente novamente.</p>
                    </div>
                `;
                return;
            }

            const fornecedores = result.data.fornecedores || [];
            console.log('[IMPORTAR-LOCAL] Carregados:', fornecedores.length, 'fornecedores');

            this.renderizarResultados(fornecedores, resultsDiv);
        },

        buscar: async function() {
            const input = document.getElementById('busca-fornecedor-local-input');
            const termo = input?.value.trim();

            if (!termo || termo.length < 2) {
                alert('Digite pelo menos 2 caracteres para buscar.');
                return;
            }

            console.log('[IMPORTAR-LOCAL] Buscando:', termo);

            const btnBuscar = document.getElementById('btn-buscar-fornecedor-local');
            const resultsDiv = document.getElementById('busca-fornecedor-local-input-results');

            utils.showLoading(btnBuscar, 'Buscando...');
            resultsDiv.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #0ea5e9; margin-bottom: 15px;"></i>
                    <p style="font-size: 14px; color: #6b7280;">Buscando...</p>
                </div>
            `;

            const url = `${window.APP_BASE_PATH}/fornecedores/buscar-local?termo=${encodeURIComponent(termo)}`;

            const result = await utils.fetchAPI(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            utils.hideLoading(btnBuscar);

            if (!result.ok) {
                console.error('[IMPORTAR-LOCAL] Erro na busca:', result);
                resultsDiv.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc2626; margin-bottom: 15px;"></i>
                        <p style="font-size: 14px; color: #dc2626;">Erro ao buscar fornecedores. Tente novamente.</p>
                    </div>
                `;
                return;
            }

            const fornecedores = result.data.fornecedores || [];
            console.log('[IMPORTAR-LOCAL] Encontrados:', fornecedores.length, 'fornecedores');

            this.renderizarResultados(fornecedores, resultsDiv);
        },

        renderizarResultados: function(fornecedores, container) {
            if (!fornecedores || fornecedores.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #9ca3af; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p style="font-size: 14px; color: #6b7280;">Nenhum fornecedor encontrado no cadastro local.</p>
                        <p style="font-size: 12px; color: #9ca3af;">Cadastre novos fornecedores ou tente buscar no PNCP.</p>
                    </div>
                `;
                return;
            }

            let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';

            fornecedores.forEach(f => {
                const cnpj = f.cnpj || f.cpf_cnpj || 'N/A';
                const razaoSocial = f.razao_social || f.nome || 'Sem nome';
                const email = f.email || '-';
                const telefone = f.telefone || '-';
                const cidade = f.cidade || '-';
                const uf = f.uf || '-';

                html += `
                    <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; background: white;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 14px; color: #1f2937; margin-bottom: 4px;">
                                    ${razaoSocial}
                                </div>
                                <div style="font-size: 12px; color: #6b7280;">
                                    <strong>CNPJ:</strong> ${cnpj}
                                </div>
                                <div style="font-size: 12px; color: #6b7280;">
                                    <strong>E-mail:</strong> ${email} | <strong>Telefone:</strong> ${telefone}
                                </div>
                                <div style="font-size: 12px; color: #6b7280;">
                                    <strong>Município:</strong> ${cidade} - ${uf}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary btn-selecionar-fornecedor-local"
                                data-fornecedor='${JSON.stringify(f)}'
                                style="font-size: 12px; white-space: nowrap;"
                            >
                                <i class="fas fa-check"></i> Selecionar
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        },

        selecionarFornecedor: function(fornecedor) {
            console.log('[IMPORTAR-LOCAL] Fornecedor selecionado:', fornecedor);

            // Preencher campos do formulário principal
            const cnpj = fornecedor.cnpj || fornecedor.cpf_cnpj || '';
            const razaoSocial = fornecedor.razao_social || fornecedor.nome || '';
            const email = fornecedor.email || '';
            const telefone = fornecedor.telefone || '';
            const endereco = fornecedor.endereco || '';
            const cidade = fornecedor.cidade || '';
            const uf = fornecedor.uf || '';
            const cep = fornecedor.cep || '';

            // Campos do wizard CDF
            const inputCNPJ = document.getElementById('fornecedor_cnpj') || document.querySelector('input[name="fornecedor_cnpj"]');
            const inputRazao = document.getElementById('fornecedor_razao_social') || document.querySelector('input[name="fornecedor_razao_social"]');
            const inputEmail = document.getElementById('fornecedor_email') || document.querySelector('input[name="fornecedor_email"]');
            const inputTelefone = document.getElementById('fornecedor_telefone') || document.querySelector('input[name="fornecedor_telefone"]');
            const inputEndereco = document.getElementById('fornecedor_endereco') || document.querySelector('input[name="fornecedor_endereco"]');
            const inputCidade = document.getElementById('fornecedor_cidade') || document.querySelector('input[name="fornecedor_cidade"]');
            const inputUF = document.getElementById('fornecedor_uf') || document.querySelector('select[name="fornecedor_uf"]');
            const inputCEP = document.getElementById('fornecedor_cep') || document.querySelector('input[name="fornecedor_cep"]');

            if (inputCNPJ) inputCNPJ.value = cnpj;
            if (inputRazao) inputRazao.value = razaoSocial;
            if (inputEmail) inputEmail.value = email;
            if (inputTelefone) inputTelefone.value = telefone;
            if (inputEndereco) inputEndereco.value = endereco;
            if (inputCidade) inputCidade.value = cidade;
            if (inputUF) inputUF.value = uf;
            if (inputCEP) inputCEP.value = cep;

            alert(`Fornecedor "${razaoSocial}" importado com sucesso!`);

            // Fechar modal
            this.modal.hide();
        }
    };

    // ========================================================================
    // INICIALIZAÇÃO GERAL
    // ========================================================================

    function inicializarTodosOsModais() {
        console.log('[MODAL-REFACTOR] Inicializando todos os modais...');

        // Aguardar Bootstrap estar carregado
        if (typeof bootstrap === 'undefined') {
            console.warn('[MODAL-REFACTOR] Bootstrap não carregado ainda. Aguardando...');
            setTimeout(inicializarTodosOsModais, 300);
            return;
        }

        // Inicializar cada modal
        ModalAnaliseCritica.init();
        ModalAlterarItem.init();
        ModalBuscaFornecedorPNCP.init();
        ModalImportarLocal.init();

        console.log('[MODAL-REFACTOR] ✅ Todos os modals inicializados com sucesso!');
    }

    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(inicializarTodosOsModais, 600);
        });
    } else {
        setTimeout(inicializarTodosOsModais, 600);
    }

})();
