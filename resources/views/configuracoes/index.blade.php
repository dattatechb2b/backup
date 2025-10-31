@extends('layouts.app')

@section('title', 'Configurações do Órgão - Cesta de Preços')

@section('content')
<style>
    .config-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 32px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .config-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .config-title {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .config-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin-top: 8px;
    }

    .config-form {
        display: grid;
        gap: 24px;
    }

    .form-section {
        background: #f9fafb;
        padding: 24px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section-title i {
        color: #3b82f6;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-row.single {
        grid-template-columns: 1fr;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .form-label.required::after {
        content: " *";
        color: #ef4444;
    }

    .form-input {
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s;
        background: white;
    }

    .form-input:hover {
        border-color: #3b82f6;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-input:disabled {
        background: #f3f4f6;
        cursor: not-allowed;
    }

    .cnpj-group {
        display: flex;
        gap: 12px;
        align-items: end;
    }

    .cnpj-group .form-input {
        flex: 1;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .brasao-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 32px;
        text-align: center;
        transition: all 0.2s;
        cursor: pointer;
        background: white;
    }

    .brasao-upload-area:hover {
        border-color: #3b82f6;
        background: #f0f9ff;
    }

    .brasao-upload-area.drag-over {
        border-color: #3b82f6;
        background: #dbeafe;
    }

    .brasao-preview {
        max-width: 300px;
        max-height: 300px;
        margin: 20px auto;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .brasao-info {
        color: #6b7280;
        font-size: 13px;
        margin-top: 12px;
    }

    .brasao-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 16px;
    }

    .alert {
        padding: 14px 18px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-spinner {
        background: white;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
    }

    .spinner {
        border: 4px solid #f3f4f6;
        border-top: 4px solid #3b82f6;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .save-button-container {
        display: flex;
        justify-content: flex-end;
        padding-top: 24px;
        border-top: 2px solid #e5e7eb;
        margin-top: 24px;
    }

    #brasaoFile {
        display: none;
    }

    /* Modal de Sucesso */
    .success-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }

    .success-modal.active {
        display: flex;
    }

    .success-modal-content {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        text-align: center;
        max-width: 450px;
        width: 90%;
        animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success-modal-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        animation: successPulse 0.6s ease-out;
    }

    @keyframes successPulse {
        0% {
            transform: scale(0);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }

    .success-modal-icon i {
        font-size: 40px;
        color: white;
    }

    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }

    .success-modal-message {
        font-size: 15px;
        color: #6b7280;
        margin-bottom: 32px;
        line-height: 1.6;
    }

    .success-modal-button {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 14px 40px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .success-modal-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .success-modal-button:active {
        transform: translateY(0);
    }
</style>

<div class="config-container">
    <div class="config-header">
        <div>
            <h1 class="config-title">Configurações do Órgão</h1>
            <p class="config-subtitle">Configure os dados da sua organização que aparecerão nos orçamentos e relatórios</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <form id="configForm" class="config-form">
        @csrf

        <!-- Seção 1: Identificação -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="fas fa-building"></i>
                Identificação do Órgão
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="cnpj">CNPJ</label>
                    <div class="cnpj-group">
                        <input
                            type="text"
                            id="cnpj"
                            name="cnpj"
                            class="form-input"
                            placeholder="00.000.000/0000-00"
                            value="{{ $orgao->cnpj ?? '' }}"
                            maxlength="18"
                        >
                        <button type="button" class="btn btn-secondary" id="btnBuscarCNPJ">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="razao_social">Razão Social</label>
                    <input
                        type="text"
                        id="razao_social"
                        name="razao_social"
                        class="form-input"
                        placeholder="Nome completo do órgão"
                        value="{{ $orgao->razao_social ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="nome_fantasia">Nome Fantasia</label>
                    <input
                        type="text"
                        id="nome_fantasia"
                        name="nome_fantasia"
                        class="form-input"
                        placeholder="Nome fantasia"
                        value="{{ $orgao->nome_fantasia ?? '' }}"
                    >
                </div>
            </div>
        </div>

        <!-- Seção 2: Endereço -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="fas fa-map-marker-alt"></i>
                Endereço
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="cep">CEP</label>
                    <input
                        type="text"
                        id="cep"
                        name="cep"
                        class="form-input"
                        placeholder="00000-000"
                        value="{{ $orgao->cep ?? '' }}"
                        maxlength="10"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="endereco">Logradouro</label>
                    <input
                        type="text"
                        id="endereco"
                        name="endereco"
                        class="form-input"
                        placeholder="Rua, avenida, etc."
                        value="{{ $orgao->endereco ?? '' }}"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="numero">Número</label>
                    <input
                        type="text"
                        id="numero"
                        name="numero"
                        class="form-input"
                        placeholder="Nº"
                        value="{{ $orgao->numero ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="complemento">Complemento</label>
                    <input
                        type="text"
                        id="complemento"
                        name="complemento"
                        class="form-input"
                        placeholder="Sala, andar, etc."
                        value="{{ $orgao->complemento ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="bairro">Bairro</label>
                    <input
                        type="text"
                        id="bairro"
                        name="bairro"
                        class="form-input"
                        placeholder="Bairro"
                        value="{{ $orgao->bairro ?? '' }}"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="cidade">Cidade</label>
                    <input
                        type="text"
                        id="cidade"
                        name="cidade"
                        class="form-input"
                        placeholder="Município"
                        value="{{ $orgao->cidade ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="uf">UF</label>
                    <input
                        type="text"
                        id="uf"
                        name="uf"
                        class="form-input"
                        placeholder="UF"
                        value="{{ $orgao->uf ?? '' }}"
                        maxlength="2"
                        style="text-transform: uppercase;"
                    >
                </div>
            </div>
        </div>

        <!-- Seção 3: Contato -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="fas fa-phone"></i>
                Contato
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="telefone">Telefone</label>
                    <input
                        type="text"
                        id="telefone"
                        name="telefone"
                        class="form-input"
                        placeholder="(00) 0000-0000"
                        value="{{ $orgao->telefone ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="contato@orgao.gov.br"
                        value="{{ $orgao->email ?? '' }}"
                    >
                </div>
            </div>
        </div>

        <!-- Seção 4: Brasão -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="fas fa-image"></i>
                Brasão do Órgão
            </h2>

            <div id="brasaoUploadArea" class="brasao-upload-area">
                @if($orgao->brasao_path)
                    <img src="storage/{{ $orgao->brasao_path }}" alt="Brasão" class="brasao-preview" id="brasaoPreview">
                    <div class="brasao-actions">
                        <button type="button" class="btn btn-secondary" id="btnAlterarBrasao">
                            <i class="fas fa-edit"></i>
                            Alterar Brasão
                        </button>
                        <button type="button" class="btn btn-danger" id="btnDeletarBrasao">
                            <i class="fas fa-trash"></i>
                            Remover
                        </button>
                    </div>
                @else
                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px;"></i>
                    <p style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        Clique ou arraste para fazer upload do brasão
                    </p>
                    <p class="brasao-info">
                        Formatos suportados: PNG, JPG, GIF, SVG<br>
                        A imagem será automaticamente redimensionada e otimizada
                    </p>
                @endif
            </div>

            <input type="file" id="brasaoFile" name="brasao" accept="image/png,image/jpeg,image/jpg,image/gif,image/svg+xml">
        </div>

        <!-- Seção 5: Assinatura Institucional -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="fas fa-file-signature"></i>
                Assinatura Institucional
            </h2>
            <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">
                Dados do responsável institucional que aparecerão na assinatura final dos PDFs de orçamento.
            </p>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="responsavel_nome">Nome do Responsável</label>
                    <input
                        type="text"
                        id="responsavel_nome"
                        name="responsavel_nome"
                        class="form-input"
                        placeholder="Nome completo do responsável"
                        value="{{ $orgao->responsavel_nome ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="responsavel_matricula_siape">Matrícula/SIAPE</label>
                    <input
                        type="text"
                        id="responsavel_matricula_siape"
                        name="responsavel_matricula_siape"
                        class="form-input"
                        placeholder="Nº de matrícula ou SIAPE"
                        value="{{ $orgao->responsavel_matricula_siape ?? '' }}"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="responsavel_cargo">Cargo/Função</label>
                    <input
                        type="text"
                        id="responsavel_cargo"
                        name="responsavel_cargo"
                        class="form-input"
                        placeholder="Cargo ou função do responsável"
                        value="{{ $orgao->responsavel_cargo ?? '' }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="responsavel_portaria">Portaria nº</label>
                    <input
                        type="text"
                        id="responsavel_portaria"
                        name="responsavel_portaria"
                        class="form-input"
                        placeholder="Número da portaria de designação"
                        value="{{ $orgao->responsavel_portaria ?? '' }}"
                    >
                </div>
            </div>
        </div>

        <!-- Botão Salvar -->
        <div class="save-button-container">
            <button type="submit" class="btn btn-primary" id="btnSalvar">
                <i class="fas fa-save"></i>
                Salvar Configurações
            </button>
        </div>
    </form>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p style="color: #374151; font-weight: 600;">Processando...</p>
    </div>
</div>

<!-- Modal de Sucesso -->
<div class="success-modal" id="successModal">
    <div class="success-modal-content">
        <div class="success-modal-icon">
            <i class="fas fa-check"></i>
        </div>
        <h2 class="success-modal-title">Configurações Salvas!</h2>
        <p class="success-modal-message">
            As configurações do seu órgão foram salvas com sucesso e já estarão disponíveis em todos os orçamentos e relatórios.
        </p>
        <button type="button" class="success-modal-button" id="btnModalOk">
            OK
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = window.APP_BASE_PATH || '';

    // Helper para pegar CSRF token do cookie
    function getCsrfToken() {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                return decodeURIComponent(value);
            }
        }
        // Fallback para meta tag
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    // Máscaras
    const cnpjInput = document.getElementById('cnpj');
    const cepInput = document.getElementById('cep');
    const telefoneInput = document.getElementById('telefone');

    // Máscara CNPJ
    cnpjInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 14) {
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        }
    });

    // Máscara CEP
    cepInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 8) {
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        }
    });

    // Máscara Telefone
    telefoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            if (value.length <= 10) {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        }
    });

    // Buscar CNPJ
    document.getElementById('btnBuscarCNPJ').addEventListener('click', async function() {
        const cnpj = cnpjInput.value.replace(/\D/g, '');

        if (cnpj.length !== 14) {
            showAlert('Por favor, insira um CNPJ válido com 14 dígitos.', 'error');
            return;
        }

        showLoading(true);

        try {
            const formData = new FormData();
            formData.append('cnpj', cnpj);
            formData.append('_token', getCsrfToken());

            const response = await fetch(basePath + '/configuracoes/buscar-cnpj', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Preencher campos
                document.getElementById('razao_social').value = data.data.razao_social || '';
                document.getElementById('nome_fantasia').value = data.data.nome_fantasia || '';
                document.getElementById('endereco').value = data.data.endereco || '';
                document.getElementById('numero').value = data.data.numero || '';
                document.getElementById('complemento').value = data.data.complemento || '';
                document.getElementById('bairro').value = data.data.bairro || '';
                document.getElementById('cep').value = data.data.cep || '';
                document.getElementById('cidade').value = data.data.cidade || '';
                document.getElementById('uf').value = data.data.uf || '';
                document.getElementById('telefone').value = data.data.telefone || '';
                document.getElementById('email').value = data.data.email || '';

                showAlert('Dados do CNPJ carregados com sucesso! Revise e salve as alterações.', 'success');
            } else {
                showAlert(data.message || 'Erro ao buscar CNPJ.', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showAlert('Erro ao buscar CNPJ. Tente novamente.', 'error');
        } finally {
            showLoading(false);
        }
    });

    // Upload de Brasão
    const brasaoFile = document.getElementById('brasaoFile');
    const brasaoUploadArea = document.getElementById('brasaoUploadArea');

    brasaoUploadArea.addEventListener('click', function(e) {
        if (!e.target.closest('button')) {
            brasaoFile.click();
        }
    });

    brasaoUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        brasaoUploadArea.classList.add('drag-over');
    });

    brasaoUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        brasaoUploadArea.classList.remove('drag-over');
    });

    brasaoUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        brasaoUploadArea.classList.remove('drag-over');

        if (e.dataTransfer.files.length > 0) {
            brasaoFile.files = e.dataTransfer.files;
            uploadBrasao();
        }
    });

    brasaoFile.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadBrasao();
        }
    });

    async function uploadBrasao() {
        const file = brasaoFile.files[0];

        if (!file) return;

        // Validar tipo
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml'];
        if (!validTypes.includes(file.type)) {
            showAlert('Formato inválido. Use PNG, JPG, GIF ou SVG.', 'error');
            return;
        }

        showLoading(true);

        const formData = new FormData();
        formData.append('brasao', file);
        formData.append('_token', getCsrfToken());

        try {
            const response = await fetch(basePath + '/configuracoes/upload-brasao', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showAlert('Brasão enviado com sucesso!', 'success');

                // Atualizar preview
                brasaoUploadArea.innerHTML = `
                    <img src="${data.brasao_url}" alt="Brasão" class="brasao-preview" id="brasaoPreview">
                    <div class="brasao-actions">
                        <button type="button" class="btn btn-secondary" id="btnAlterarBrasao">
                            <i class="fas fa-edit"></i>
                            Alterar Brasão
                        </button>
                        <button type="button" class="btn btn-danger" id="btnDeletarBrasao">
                            <i class="fas fa-trash"></i>
                            Remover
                        </button>
                    </div>
                `;

                // Revinciular eventos
                bindBrasaoButtons();
            } else {
                showAlert(data.message || 'Erro ao enviar brasão.', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showAlert('Erro ao enviar brasão. Tente novamente.', 'error');
        } finally {
            showLoading(false);
            brasaoFile.value = '';
        }
    }

    // Deletar Brasão
    function bindBrasaoButtons() {
        const btnAlterar = document.getElementById('btnAlterarBrasao');
        const btnDeletar = document.getElementById('btnDeletarBrasao');

        if (btnAlterar) {
            btnAlterar.addEventListener('click', function() {
                brasaoFile.click();
            });
        }

        if (btnDeletar) {
            btnDeletar.addEventListener('click', async function() {
                if (!confirm('Tem certeza que deseja remover o brasão?')) {
                    return;
                }

                showLoading(true);

                try {
                    const formData = new FormData();
                    formData.append('_token', getCsrfToken());
                    formData.append('_method', 'DELETE');

                    const response = await fetch(basePath + '/configuracoes/deletar-brasao', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showAlert('Brasão removido com sucesso!', 'success');

                        // Resetar área de upload
                        brasaoUploadArea.innerHTML = `
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px;"></i>
                            <p style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Clique ou arraste para fazer upload do brasão
                            </p>
                            <p class="brasao-info">
                                Formatos suportados: PNG, JPG, GIF, SVG<br>
                                A imagem será automaticamente redimensionada e otimizada
                            </p>
                        `;
                    } else {
                        showAlert(data.message || 'Erro ao remover brasão.', 'error');
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    showAlert('Erro ao remover brasão. Tente novamente.', 'error');
                } finally {
                    showLoading(false);
                }
            });
        }
    }

    // Inicializar botões do brasão se existirem
    bindBrasaoButtons();

    // Salvar Configurações
    document.getElementById('configForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        showLoading(true);

        const formData = new FormData(this);
        formData.append('_token', getCsrfToken());

        try {
            const response = await fetch(basePath + '/configuracoes', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Mostrar modal de sucesso
                showSuccessModal();
            } else {
                showAlert(result.message || 'Erro ao salvar configurações.', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showAlert('Erro ao salvar configurações. Tente novamente.', 'error');
        } finally {
            showLoading(false);
        }
    });

    // Funções auxiliares
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info';
        const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

        alertContainer.innerHTML = `
            <div class="alert ${alertClass}">
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            </div>
        `;

        // Auto-remover após 5 segundos
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.toggle('active', show);
    }

    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.add('active');
    }

    // Evento do botão OK do modal - Redirecionar para o dashboard
    document.getElementById('btnModalOk').addEventListener('click', function() {
        window.location.href = basePath + '/dashboard';
    });
});
</script>
@endsection
