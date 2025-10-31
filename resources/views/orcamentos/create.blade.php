@extends('layouts.app')

@section('title', 'Novo Orçamento - Cesta de Preços')

@section('content')
<style>
    /* Estilos para as abas */
    .tabs-container {
        display: flex;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 30px;
        gap: 0;
    }

    .tab {
        padding: 12px 24px;
        background: #f9fafb;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .tab:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .tab.active {
        background: white;
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Estilos para os campos do formulário */
    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 13px;
    }

    .form-label .required {
        color: #dc2626;
        margin-left: 2px;
    }

    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #374151;
        transition: border-color 0.2s;
    }

    .form-input:focus, .form-textarea:focus, .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-textarea {
        min-height: 150px;
        resize: vertical;
        font-family: inherit;
    }

    .form-helper {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.5;
    }

    .form-error {
        margin-top: 6px;
        font-size: 12px;
        color: #dc2626;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .btn-save {
        padding: 12px 32px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-save:hover {
        background: #1d4ed8;
    }

    .btn-save:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }

    .btn-cancel {
        padding: 12px 32px;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<!-- Seção de Cabeçalho -->
<div class="welcome-section">
    <h1 class="welcome-title">NOVO ORÇAMENTO ESTIMATIVO</h1>
    <p class="welcome-subtitle">Preencha os dados abaixo para criar um novo orçamento</p>
</div>

<!-- Exibir erros de validação -->
@if ($errors->any())
    <div class="alert alert-error">
        <strong>Erro ao salvar:</strong>
        <ul style="margin: 8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Formulário -->
<div class="table-section">
    <!-- Abas -->
    <div class="tabs-container">
        <button class="tab active" data-tab="do-zero" type="button">
            CRIAR DO ZERO
        </button>
        <button class="tab" data-tab="outro-orcamento" type="button">
            CRIAR A PARTIR DE OUTRO ORÇAMENTO
        </button>
        <button class="tab" data-tab="documento" type="button">
            CRIAR A PARTIR DE UM DOCUMENTO
        </button>
    </div>

    <form method="POST" action="{{ route('orcamentos.store') }}" id="form-orcamento" enctype="multipart/form-data">
        @csrf

        <!-- Campo hidden para tipo de criação -->
        <input type="hidden" name="tipo_criacao" id="tipo_criacao" value="do_zero">

        <!-- Conteúdo Aba 1: Criar do Zero -->
        <div class="tab-content active" id="content-do-zero">
            <!-- Nome do Orçamento -->
            <div class="form-group">
                <label for="nome" class="form-label">
                    Nome do Orçamento<span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    class="form-input"
                    value="{{ old('nome') }}"
                    required
                >
                <div class="form-helper">
                    Insira um nome que seja relevante e autoexplicativo para identificar facilmente o orçamento.
                </div>
                @error('nome')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Referência Externa -->
            <div class="form-group">
                <label for="referencia_externa" class="form-label">
                    Referência Externa
                </label>
                <input
                    type="text"
                    id="referencia_externa"
                    name="referencia_externa"
                    class="form-input"
                    value="{{ old('referencia_externa') }}"
                >
                <div class="form-helper">
                    Caso este orçamento seja referente a uma Dispensa, Licitação ou documento interno, digite o respectivo número do processo de 000 orçamento. Ex: Processo de Licitação do Nº XXXX/XXXX do Órgão interessado.
                </div>
                @error('referencia_externa')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Objeto -->
            <div class="form-group">
                <label for="objeto" class="form-label">
                    Objeto<span class="required">*</span>
                </label>
                <textarea
                    id="objeto"
                    name="objeto"
                    class="form-textarea"
                    required
                >{{ old('objeto') }}</textarea>
                @error('objeto')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Órgão Interessado -->
            <div class="form-group">
                <label for="orgao_interessado" class="form-label">
                    Órgão Interessado
                </label>
                <input
                    type="text"
                    id="orgao_interessado"
                    name="orgao_interessado"
                    class="form-input"
                    value="{{ old('orgao_interessado') }}"
                >
                <div class="form-helper">
                    Caso este orçamento seja procedente de demanda de outros órgãos do Município, informar o nome do Secretaria ou Órgão solicitante, por exemplo: Sec. Municipal de educação.
                </div>
                @error('orgao_interessado')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Conteúdo Aba 2: Criar a partir de outro orçamento -->
        <div class="tab-content" id="content-outro-orcamento">
            <!-- FILTROS DA PESQUISA -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 15px; display: flex; align-items: center;">
                    <i class="fas fa-filter" style="margin-right: 8px;"></i>
                    FILTROS DA PESQUISA
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 500; color: #374151; font-size: 13px;">
                            Nome do Orçamento:
                        </label>
                        <input
                            type="text"
                            id="filtro_nome"
                            class="form-input"
                            placeholder="Digite o nome..."
                        >
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 500; color: #374151; font-size: 13px;">
                            Referência Externa
                        </label>
                        <input
                            type="text"
                            id="filtro_referencia"
                            class="form-input"
                            placeholder="Digite a referência..."
                        >
                    </div>

                    <div>
                        <button
                            type="button"
                            id="btn-consultar"
                            style="padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;"
                        >
                            <i class="fas fa-search"></i>
                            CONSULTAR
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABELA DE ORÇAMENTOS -->
            <div id="tabela-orcamentos-container" style="margin-bottom: 20px;">
                <!-- Será preenchido via AJAX -->
                <div style="text-align: center; padding: 40px; color: #9ca3af;">
                    <i class="fas fa-info-circle" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <p style="font-size: 14px;">Utilize os filtros acima e clique em CONSULTAR para buscar orçamentos</p>
                </div>
            </div>

            <!-- CAMPO HIDDEN PARA GUARDAR ID DO ORÇAMENTO SELECIONADO -->
            <input type="hidden" id="orcamento_selecionado_id" name="orcamento_origem_id">

            <!-- CAMPOS DO NOVO ORÇAMENTO (preenchidos automaticamente ao selecionar) -->
            <div id="campos-novo-orcamento" style="display: none; margin-top: 30px; padding: 20px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px;">
                <h3 style="font-size: 14px; font-weight: 600; color: #166534; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-edit"></i>
                    DADOS DO NOVO ORÇAMENTO
                </h3>

                <!-- Nome do Orçamento -->
                <div class="form-group">
                    <label for="nome_aba2" class="form-label">
                        Nome do Orçamento<span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="nome_aba2"
                        name="nome"
                        class="form-input"
                        required
                    >
                </div>

                <!-- Referência Externa -->
                <div class="form-group">
                    <label for="referencia_externa_aba2" class="form-label">
                        Referência Externa
                    </label>
                    <input
                        type="text"
                        id="referencia_externa_aba2"
                        name="referencia_externa"
                        class="form-input"
                    >
                </div>

                <!-- Objeto -->
                <div class="form-group">
                    <label for="objeto_aba2" class="form-label">
                        Objeto<span class="required">*</span>
                    </label>
                    <textarea
                        id="objeto_aba2"
                        name="objeto"
                        class="form-textarea"
                        required
                    ></textarea>
                </div>

                <!-- Órgão Interessado -->
                <div class="form-group">
                    <label for="orgao_interessado_aba2" class="form-label">
                        Órgão Interessado
                    </label>
                    <input
                        type="text"
                        id="orgao_interessado_aba2"
                        name="orgao_interessado"
                        class="form-input"
                    >
                </div>
            </div>
        </div>

        <!-- Conteúdo Aba 3: Criar a partir de documento -->
        <div class="tab-content" id="content-documento">
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; gap: 30px;">
                <!-- Texto de instrução -->
                <p style="font-size: 16px; color: #6b7280; text-align: center; margin: 0;">
                    Escolha um documento para enviar
                </p>

                <!-- Campo de upload -->
                <input
                    type="file"
                    id="documento"
                    name="documento"
                    accept=".pdf,.xls,.xlsx,.csv,.doc,.docx,.png,.jpg,.jpeg,.gif,.bmp,.webp"
                    style="display: none;"
                >

                <!-- Label estilizado como botão -->
                <label for="documento" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 32px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-upload"></i>
                    ENVIAR ARQUIVO
                </label>

                <!-- Nome do arquivo selecionado -->
                <div id="nome-arquivo-selecionado" style="font-size: 14px; color: #10b981; display: none;">
                    <i class="fas fa-file-check"></i>
                    <span id="nome-arquivo-texto"></span>
                </div>
            </div>
        </div>

        <script>
            // Upload automático e processamento inteligente do documento
            document.getElementById('documento').addEventListener('change', async function(e) {
                const nomeArquivoDiv = document.getElementById('nome-arquivo-selecionado');
                const nomeArquivoTexto = document.getElementById('nome-arquivo-texto');
                const arquivo = this.files[0];

                if (!arquivo) {
                    nomeArquivoDiv.style.display = 'none';
                    return;
                }

                // Mostrar nome do arquivo
                nomeArquivoTexto.textContent = arquivo.name;
                nomeArquivoDiv.style.display = 'flex';
                nomeArquivoDiv.style.alignItems = 'center';
                nomeArquivoDiv.style.gap = '8px';

                // Criar modal de processamento
                const modalProcessamento = document.createElement('div');
                modalProcessamento.id = 'modal-processamento';
                modalProcessamento.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';

                modalProcessamento.innerHTML = `
                    <div style="background: white; padding: 40px; border-radius: 12px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                        <div style="width: 64px; height: 64px; margin: 0 auto 20px; border: 4px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <h3 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">Processando Documento</h3>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">Analisando e extraindo dados automaticamente...</p>
                        <div id="progresso-texto" style="font-size: 13px; color: #3b82f6; font-weight: 500;">Lendo arquivo...</div>
                    </div>
                    <style>
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    </style>
                `;

                document.body.appendChild(modalProcessamento);

                try {
                    // Obter token CSRF
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                    if (!csrfToken) {
                        throw new Error('Token CSRF não encontrado');
                    }

                    // Preparar FormData
                    const formData = new FormData();
                    formData.append('documento', arquivo);
                    formData.append('_token', csrfToken);

                    // Atualizar progresso
                    document.getElementById('progresso-texto').textContent = 'Detectando colunas...';

                    // Enviar para o servidor (URL relativa para funcionar com proxy)
                    const response = await fetch(`${window.APP_BASE_PATH}/orcamentos/processar-documento`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    // Verificar se resposta é OK
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Erro HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        document.getElementById('progresso-texto').textContent = 'Criando orçamento...';

                        // Aguardar 500ms para melhor UX
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Redirecionar para a página de elaboração (URL relativa)
                        window.location.href = 'orcamentos/' + data.orcamento_id + '/elaborar';
                    } else {
                        // Remover modal
                        document.body.removeChild(modalProcessamento);

                        // Mostrar erro
                        alert('Erro ao processar documento: ' + data.message);
                    }

                } catch (error) {
                    console.error('Erro ao processar documento:', error);

                    // Remover modal se ainda existir
                    const modal = document.getElementById('modal-processamento');
                    if (modal) {
                        document.body.removeChild(modal);
                    }

                    // Mostrar mensagem de erro detalhada
                    alert('Erro ao processar documento:\n\n' + error.message + '\n\nVerifique o console para mais detalhes.');
                }
            });
        </script>

        <!-- Botões de Ação -->
        <div class="form-actions">
            <!-- Botões padrão (Aba 1 e 3) -->
            <div id="botoes-padrao">
                <button type="submit" class="btn-save" id="btn-salvar">
                    Salvar
                </button>
                <a href="dashboard" class="btn-cancel">
                    Cancelar
                </a>
            </div>

            <!-- Botões da Aba 2 (Criar a partir de outro orçamento) -->
            <div id="botoes-aba2" style="display: none; gap: 12px;">
                <button
                    type="button"
                    id="btn-criar-novo-orcamento"
                    class="btn-save"
                    style="background: #10b981;"
                >
                    <i class="fas fa-check"></i>
                    CRIAR NOVO ORÇAMENTO
                </button>
                <button
                    type="button"
                    id="btn-criar-copia"
                    class="btn-save"
                    style="background: #3b82f6;"
                >
                    <i class="fas fa-copy"></i>
                    CRIAR CÓPIA DO ORÇAMENTO
                </button>
                <a href="dashboard" class="btn-cancel">
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- JavaScript para controle das abas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    const tipoCriacaoInput = document.getElementById('tipo_criacao');
    const btnSalvar = document.getElementById('btn-salvar');
    const botoesPadrao = document.getElementById('botoes-padrao');
    const botoesAba2 = document.getElementById('botoes-aba2');
    const form = document.getElementById('form-orcamento');
    let orcamentoSelecionado = null;

    // Validação simples no submit - sem interceptação
    form.addEventListener('submit', function(e) {
        const tipoAtivo = tipoCriacaoInput.value;

        if (tipoAtivo === 'do_zero') {
            const nome = document.getElementById('nome').value.trim();
            const objeto = document.getElementById('objeto').value.trim();

            if (!nome || !objeto) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return false;
            }
        }

        if (tipoAtivo === 'documento') {
            const documento = document.getElementById('documento').files.length;
            const nome = document.getElementById('nome_doc').value.trim();
            const objeto = document.getElementById('objeto_doc').value.trim();

            if (documento === 0 || !nome || !objeto) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (Documento, Nome e Objeto).');
                return false;
            }
        }

        // Se passou na validação, permite submit normal
        // O Laravel fará o redirect para a página de elaboração
        return true;
    });

    // Função para trocar de aba
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');

            // Remover classe active de todas as abas
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            // Adicionar classe active na aba clicada
            this.classList.add('active');
            document.getElementById('content-' + tabName).classList.add('active');

            // Atualizar campo hidden com tipo de criação
            const tipoMap = {
                'do-zero': 'do_zero',
                'outro-orcamento': 'outro_orcamento',
                'documento': 'documento'
            };
            tipoCriacaoInput.value = tipoMap[tabName];

            // Alternar botões conforme a aba
            if (tabName === 'outro-orcamento') {
                botoesPadrao.style.display = 'none';
                botoesAba2.style.display = 'flex';
            } else {
                botoesPadrao.style.display = 'flex';
                botoesAba2.style.display = 'none';
            }

            // Gerenciar campos required entre abas
            gerenciarCamposRequired(tabName);
        });
    });

    // Função para gerenciar campos required e desabilitar campos inativos
    function gerenciarCamposRequired(abaAtiva) {
        // DESABILITAR todos os campos de input das 3 abas
        document.querySelectorAll('#content-do-zero input, #content-do-zero textarea').forEach(el => {
            if (el.id !== 'tipo_criacao') {
                el.disabled = true;
                el.removeAttribute('required');
            }
        });
        document.querySelectorAll('#content-outro-orcamento input[name], #content-outro-orcamento textarea[name]').forEach(el => {
            el.disabled = true;
            el.removeAttribute('required');
        });
        document.querySelectorAll('#content-documento input, #content-documento textarea').forEach(el => {
            el.disabled = true;
            el.removeAttribute('required');
        });

        // HABILITAR e adicionar required apenas nos campos da aba ativa
        if (abaAtiva === 'do-zero') {
            const nome = document.getElementById('nome');
            const refExterna = document.getElementById('referencia_externa');
            const objeto = document.getElementById('objeto');
            const orgao = document.getElementById('orgao_interessado');

            if (nome) { nome.disabled = false; nome.setAttribute('required', 'required'); }
            if (refExterna) refExterna.disabled = false;
            if (objeto) { objeto.disabled = false; objeto.setAttribute('required', 'required'); }
            if (orgao) orgao.disabled = false;

        } else if (abaAtiva === 'outro-orcamento') {
            const nomeAba2 = document.getElementById('nome_aba2');
            const refExternaAba2 = document.getElementById('referencia_externa_aba2');
            const objetoAba2 = document.getElementById('objeto_aba2');
            const orgaoAba2 = document.getElementById('orgao_interessado_aba2');
            const orcamentoOrigemId = document.getElementById('orcamento_selecionado_id');

            if (nomeAba2) { nomeAba2.disabled = false; nomeAba2.setAttribute('required', 'required'); }
            if (refExternaAba2) refExternaAba2.disabled = false;
            if (objetoAba2) { objetoAba2.disabled = false; objetoAba2.setAttribute('required', 'required'); }
            if (orgaoAba2) orgaoAba2.disabled = false;
            if (orcamentoOrigemId) orcamentoOrigemId.disabled = false;

        } else if (abaAtiva === 'documento') {
            const documento = document.getElementById('documento');
            if (documento) {
                documento.disabled = false;
                documento.setAttribute('required', 'required');
            }
        }
    }

    // ==================== ABA 2: BUSCAR ORÇAMENTOS ====================

    // Botão consultar
    document.getElementById('btn-consultar').addEventListener('click', function() {
        buscarOrcamentos(1);
    });

    // Enter nos filtros
    document.getElementById('filtro_nome').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') buscarOrcamentos(1);
    });
    document.getElementById('filtro_referencia').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') buscarOrcamentos(1);
    });

    // Função para buscar orçamentos via AJAX
    function buscarOrcamentos(pagina = 1) {
        const nome = document.getElementById('filtro_nome').value;
        const referencia = document.getElementById('filtro_referencia').value;
        const container = document.getElementById('tabela-orcamentos-container');

        // Loading
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3b82f6;"></i><p style="margin-top: 10px; color: #6b7280;">Carregando...</p></div>';

        // Fazer requisição AJAX - usar URL relativa (sem /) para funcionar com tag <base>
        fetch(`${window.APP_BASE_PATH}/orcamentos/buscar?nome=${encodeURIComponent(nome)}&referencia_externa=${encodeURIComponent(referencia)}&page=${pagina}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    renderizarTabela(data.data, data.pagination);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;"><i class="fas fa-exclamation-circle" style="font-size: 48px;"></i><p style="margin-top: 10px;">Erro ao buscar orçamentos</p></div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;"><i class="fas fa-exclamation-circle" style="font-size: 48px;"></i><p style="margin-top: 10px;">Erro ao buscar orçamentos</p></div>';
            });
    }

    // Função para renderizar tabela
    function renderizarTabela(orcamentos, pagination) {
        const container = document.getElementById('tabela-orcamentos-container');

        if (orcamentos.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #9ca3af;"><i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db;"></i><p style="margin-top: 10px;">Nenhum orçamento encontrado</p></div>';
            return;
        }

        let html = '<table class="data-table">';
        html += '<thead><tr>';
        html += '<th style="width: 50px;"></th>';
        html += '<th>NÚMERO</th>';
        html += '<th>REFERÊNCIA EXTERNA</th>';
        html += '<th>NOME</th>';
        html += '<th>ÓRGÃO INTERESSADO</th>';
        html += '<th style="text-align: center;">ITENS</th>';
        html += '<th>CADASTRO</th>';
        html += '</tr></thead><tbody>';

        orcamentos.forEach(orc => {
            // Criar objeto simplificado SEM itens nested (evita JSON gigante no HTML)
            const orcSimplificado = {
                id: orc.id,
                nome: orc.nome,
                referencia_externa: orc.referencia_externa,
                objeto: orc.objeto,
                orgao_interessado: orc.orgao_interessado
            };

            html += '<tr>';
            html += `<td style="text-align: center;"><input type="radio" name="orcamento_radio" value="${orc.id}" data-orcamento='${JSON.stringify(orcSimplificado)}'></td>`;
            html += `<td>${orc.numero || '-'}</td>`;
            html += `<td>${orc.referencia_externa || '-'}</td>`;
            html += `<td>${orc.nome}</td>`;
            html += `<td>${orc.orgao_interessado || '-'}</td>`;
            html += `<td style="text-align: center;">${orc.total_itens || 0}</td>`;
            html += `<td>${new Date(orc.created_at).toLocaleDateString('pt-BR')}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';

        // Paginação
        if (pagination.last_page > 1) {
            html += '<div style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 10px; font-size: 13px;">';
            html += '<span style="color: #6b7280;">MOSTRANDO DE ' + pagination.from + ' ATÉ ' + pagination.to + ' DE ' + pagination.total + '</span>';
            html += '<div style="display: flex; gap: 5px;">';

            for (let i = 1; i <= pagination.last_page; i++) {
                if (i === pagination.current_page) {
                    html += `<button style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">${i}</button>`;
                } else {
                    html += `<button onclick="buscarOrcamentos(${i})" style="padding: 6px 12px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">${i}</button>`;
                }
            }

            html += '</div></div>';
        }

        container.innerHTML = html;

        // Adicionar event listeners nos radio buttons
        document.querySelectorAll('input[name="orcamento_radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                orcamentoSelecionado = JSON.parse(this.getAttribute('data-orcamento'));
                document.getElementById('orcamento_selecionado_id').value = this.value;

                // Mostrar campos do novo orçamento
                const camposNovoOrcamento = document.getElementById('campos-novo-orcamento');
                camposNovoOrcamento.style.display = 'block';

                // Preencher campos automaticamente
                document.getElementById('nome_aba2').value = orcamentoSelecionado.nome + ' (Cópia)';
                document.getElementById('referencia_externa_aba2').value = orcamentoSelecionado.referencia_externa || '';
                document.getElementById('objeto_aba2').value = orcamentoSelecionado.objeto || '';
                document.getElementById('orgao_interessado_aba2').value = orcamentoSelecionado.orgao_interessado || '';

                console.log('✓ Orçamento selecionado:', orcamentoSelecionado.id);
                console.log('✓ Campos preenchidos automaticamente');
            });
        });
    }

    // ==================== BOTÕES DA ABA 2 ====================

    // Botão CRIAR NOVO ORÇAMENTO
    document.getElementById('btn-criar-novo-orcamento').addEventListener('click', function() {
        if (!orcamentoSelecionado) {
            alert('Por favor, selecione um orçamento da lista');
            return;
        }

        // Validar campos obrigatórios
        const nomeAba2 = document.getElementById('nome_aba2').value.trim();
        const objetoAba2 = document.getElementById('objeto_aba2').value.trim();

        if (!nomeAba2 || !objetoAba2) {
            alert('Por favor, preencha os campos obrigatórios: Nome e Objeto');
            return;
        }

        // Submeter formulário
        const form = document.getElementById('form-orcamento');
        form.method = 'POST';
        form.submit();
    });

    // Botão CRIAR CÓPIA DO ORÇAMENTO
    document.getElementById('btn-criar-copia').addEventListener('click', function() {
        if (!orcamentoSelecionado) {
            alert('Por favor, selecione um orçamento da lista');
            return;
        }

        // Validar campos obrigatórios
        const nomeAba2 = document.getElementById('nome_aba2').value.trim();
        const objetoAba2 = document.getElementById('objeto_aba2').value.trim();

        if (!nomeAba2 || !objetoAba2) {
            alert('Por favor, preencha os campos obrigatórios: Nome e Objeto');
            return;
        }

        if (!confirm('Deseja criar uma cópia deste orçamento?')) {
            return;
        }

        // Submeter formulário
        const form = document.getElementById('form-orcamento');
        form.method = 'POST';
        form.submit();
    });

    // Tornar função buscarOrcamentos global para paginação
    window.buscarOrcamentos = buscarOrcamentos;

    // Inicializar com aba correta
    gerenciarCamposRequired('do-zero');
});
</script>
@endsection
