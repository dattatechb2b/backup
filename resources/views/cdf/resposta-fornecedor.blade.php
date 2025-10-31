<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Formulário de Cotação - {{ $solicitacao->orcamento->numero ?? 'CDF' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fa;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
        }

        /* Header Profissional */
        .page-header {
            background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
            color: white;
            padding: 40px 0 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .page-header .container {
            max-width: 1100px;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2c5282;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .company-name {
            font-size: 15px;
            font-weight: 500;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Container Principal */
        .main-container {
            max-width: 1100px;
            margin: -30px auto 60px;
            padding: 0 20px;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Alertas de Informação */
        .info-banner {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #3b82f6;
            padding: 20px 25px;
            border-radius: 0;
        }

        .info-banner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Seções do Formulário */
        .form-section {
            padding: 35px 40px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .section-description {
            font-size: 14px;
            color: #64748b;
            margin: -20px 0 25px 56px;
        }

        /* Campos de Formulário */
        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            display: block;
        }

        .form-label.required::after {
            content: " *";
            color: #ef4444;
            font-weight: 700;
        }

        .form-control, .form-select {
            height: 46px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 15px;
            transition: all 0.2s;
            background: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82c4;
            box-shadow: 0 0 0 3px rgba(59, 130, 196, 0.1);
            outline: none;
        }

        textarea.form-control {
            height: auto;
            min-height: 100px;
            resize: vertical;
        }

        .form-hint {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-hint i {
            color: #3b82c4;
        }

        /* Badge de Carregamento CNPJ */
        .cnpj-loading {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }

        .cnpj-wrapper {
            position: relative;
        }

        .cnpj-success {
            color: #10b981;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            display: none;
        }

        /* Itens de Cotação */
        .items-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: start;
            gap: 12px;
        }

        .items-warning i {
            color: #f59e0b;
            font-size: 20px;
            margin-top: 2px;
        }

        .items-warning-content {
            flex: 1;
        }

        .items-warning strong {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 4px;
        }

        .items-warning p {
            font-size: 13px;
            color: #78350f;
            margin: 0;
        }

        .item-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.2s;
        }

        .item-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .item-header {
            display: flex;
            align-items: start;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 2px solid #e2e8f0;
        }

        .item-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .item-specs {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .item-spec {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #475569;
        }

        .item-spec i {
            color: #3b82c4;
            width: 16px;
        }

        .item-spec strong {
            font-weight: 600;
            color: #334155;
        }

        /* Canvas de Assinatura */
        .signature-wrapper {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            background: #f8fafc;
            text-align: center;
        }

        .signature-canvas {
            width: 100%;
            max-width: 600px;
            height: 200px;
            background: white;
            border-radius: 8px;
            cursor: crosshair;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
            display: block;
        }

        .signature-controls {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .signature-note {
            font-size: 12px;
            color: #64748b;
            margin-top: 12px;
            font-style: italic;
        }

        /* Botões */
        .btn {
            height: 48px;
            padding: 0 32px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 196, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 196, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-lg {
            height: 56px;
            font-size: 16px;
            padding: 0 40px;
        }

        /* Footer do Formulário */
        .form-footer {
            background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
            padding: 30px 40px;
            text-align: center;
            border-top: 2px solid #e2e8f0;
        }

        /* Mensagens de Alerta */
        .alert {
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 25px;
            border: none;
            display: none;
            align-items: start;
            gap: 12px;
        }

        .alert.show {
            display: flex;
        }

        .alert i {
            font-size: 22px;
            margin-top: 2px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .alert-success i {
            color: #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        .alert-danger i {
            color: #ef4444;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .alert ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }

        /* Loading Overlay */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .page-header {
                padding: 30px 0 40px;
            }

            .page-title {
                font-size: 24px;
            }

            .main-container {
                margin-top: -20px;
                padding: 0 15px;
            }

            .form-section {
                padding: 25px 20px;
            }

            .section-header {
                flex-direction: column;
                align-items: start;
                gap: 10px;
            }

            .section-description {
                margin-left: 0;
            }

            .item-header {
                flex-direction: column;
            }

            .info-banner-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        /* Animações suaves */
        .form-control, .item-card, .btn {
            transition: all 0.2s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p class="loading-text">Enviando sua proposta...</p>
            <p style="font-size: 14px; opacity: 0.8; margin-top: 8px;">Por favor, aguarde</p>
        </div>
    </div>

    <!-- Header Profissional -->
    <div class="page-header">
        <div class="container">
            <div class="company-info">
                <div class="company-logo">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <div>
                    <div class="company-name">CESTA DE PREÇOS</div>
                </div>
            </div>
            <h1 class="page-title">Formulário de Cotação</h1>
            <p class="page-subtitle">Preencha os dados abaixo para enviar sua proposta comercial</p>
        </div>
    </div>

    <!-- Container Principal -->
    <div class="main-container">
        <div class="form-card">
            <!-- Banner de Informações -->
            <div class="info-banner">
                <div class="info-banner-grid">
                    <div class="info-item">
                        <span class="info-label">Número da Solicitação</span>
                        <span class="info-value">#{{ str_pad($solicitacao->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Orçamento</span>
                        <span class="info-value">{{ $solicitacao->orcamento->numero ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Prazo de Resposta</span>
                        <span class="info-value">{{ $solicitacao->prazo_resposta_dias }} dias</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Válido Até</span>
                        <span class="info-value">{{ $solicitacao->valido_ate->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Mensagens -->
            <div style="padding: 25px 40px 0;">
                <div id="success-message" class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div class="alert-content">
                        <div class="alert-title">Proposta enviada com sucesso!</div>
                        <p style="margin: 0;">Obrigado por sua cotação. Entraremos em contato em breve.</p>
                    </div>
                </div>

                <div id="error-message" class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="alert-content" id="error-content"></div>
                </div>
            </div>

            <form id="form-resposta-cdf" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="token" value="{{ $solicitacao->token_resposta }}">

                <!-- SEÇÃO 1: Dados do Fornecedor -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h2 class="section-title">Dados do Fornecedor</h2>
                    </div>
                    <p class="section-description">Informe os dados cadastrais da sua empresa</p>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="cnpj" class="form-label required">CNPJ</label>
                            <div class="cnpj-wrapper">
                                <input type="text" class="form-control" id="cnpj" name="cnpj"
                                       placeholder="00.000.000/0000-00" required maxlength="18">
                                <span class="cnpj-loading">
                                    <i class="fas fa-spinner fa-spin text-primary"></i>
                                </span>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                <span>Digite o CNPJ para buscar dados automaticamente</span>
                            </div>
                            <div class="cnpj-success">
                                <i class="fas fa-check-circle"></i> Dados encontrados e preenchidos automaticamente
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="razao_social" class="form-label required">Razão Social</label>
                            <input type="text" class="form-control" id="razao_social" name="razao_social"
                                   placeholder="Nome da Empresa Ltda" required>
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <div class="col-md-6">
                            <label for="email" class="form-label required">E-mail Corporativo</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="contato@empresa.com.br" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label required">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone"
                                   placeholder="(00) 00000-0000" required maxlength="15">
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 2: Dados da Proposta -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h2 class="section-title">Dados da Proposta Comercial</h2>
                    </div>
                    <p class="section-description">Defina as condições comerciais da sua proposta</p>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="validade_proposta" class="form-label required">Validade da Proposta (dias)</label>
                            <input type="number" class="form-control" id="validade_proposta"
                                   name="validade_proposta" min="1" max="365" value="30" required>
                            <div class="form-hint">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Por quanto tempo esta proposta será válida</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="forma_pagamento" class="form-label required">Condições de Pagamento</label>
                            <input type="text" class="form-control" id="forma_pagamento"
                                   name="forma_pagamento" placeholder="Ex: 30 dias após entrega" required>
                            <div class="form-hint">
                                <i class="fas fa-credit-card"></i>
                                <span>Informe prazo e forma de pagamento</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="observacoes_gerais" class="form-label">Observações Gerais</label>
                        <textarea class="form-control" id="observacoes_gerais" name="observacoes_gerais"
                                  rows="4" placeholder="Informações adicionais sobre sua proposta, prazos, condições especiais, etc."></textarea>
                    </div>
                </div>

                <!-- SEÇÃO 3: Itens Solicitados -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-list-check"></i>
                        </div>
                        <h2 class="section-title">Itens da Cotação</h2>
                    </div>
                    <p class="section-description">Preencha os valores e informações para cada item solicitado</p>

                    <div class="items-warning">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="items-warning-content">
                            <strong>Atenção</strong>
                            <p>Todos os campos marcados com asterisco (*) são obrigatórios. Preencha os dados de cada item com atenção.</p>
                        </div>
                    </div>

                    <div id="itens-container">
                        @foreach($itens as $index => $item)
                            <div class="item-card">
                                <div class="item-header">
                                    <div class="item-number">{{ $index + 1 }}</div>
                                    <div class="item-details">
                                        <div class="item-name">{{ $item->item->descricao ?? 'Produto/Serviço' }}</div>
                                        <div class="item-specs">
                                            <div class="item-spec">
                                                <i class="fas fa-box"></i>
                                                <span><strong>Quantidade:</strong> {{ number_format($item->item->quantidade ?? 0, 2, ',', '.') }}</span>
                                            </div>
                                            <div class="item-spec">
                                                <i class="fas fa-ruler"></i>
                                                <span><strong>Unidade:</strong> {{ $item->item->unidade ?? 'UN' }}</span>
                                            </div>
                                            @if($item->item->codigo_item)
                                            <div class="item-spec">
                                                <i class="fas fa-barcode"></i>
                                                <span><strong>Código:</strong> {{ $item->item->codigo_item }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="itens[{{ $index }}][item_orcamento_id]"
                                       value="{{ $item->orcamento_item_id }}">

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label required">Preço Unitário (R$)</label>
                                        <input type="number" class="form-control preco-unitario"
                                               name="itens[{{ $index }}][preco_unitario]"
                                               step="0.01" min="0.01" required
                                               data-index="{{ $index }}"
                                               data-quantidade="{{ $item->item->quantidade ?? 1 }}"
                                               placeholder="0,00">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Preço Total (R$)</label>
                                        <input type="number" class="form-control preco-total"
                                               name="itens[{{ $index }}][preco_total]"
                                               step="0.01" min="0.01" required readonly
                                               data-index="{{ $index }}"
                                               placeholder="0,00"
                                               style="background-color: #f1f5f9;">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Marca/Fabricante</label>
                                        <input type="text" class="form-control"
                                               name="itens[{{ $index }}][marca]"
                                               placeholder="Ex: Marca X" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Prazo de Entrega (dias)</label>
                                        <input type="number" class="form-control"
                                               name="itens[{{ $index }}][prazo_entrega]"
                                               min="0" max="365" required
                                               placeholder="0">
                                    </div>
                                </div>

                                <div class="row g-3 mt-2">
                                    <div class="col-12">
                                        <label class="form-label">Observações do Item</label>
                                        <textarea class="form-control"
                                                  name="itens[{{ $index }}][observacoes]"
                                                  rows="2"
                                                  placeholder="Informações adicionais sobre este item específico"></textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- SEÇÃO 4: Anexos -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-paperclip"></i>
                        </div>
                        <h2 class="section-title">Documentos Anexos</h2>
                    </div>
                    <p class="section-description">Anexe documentos complementares (catálogos, certificados, fichas técnicas)</p>

                    <div>
                        <label for="anexos" class="form-label">Arquivos Anexos (Opcional)</label>
                        <input type="file" class="form-control" id="anexos" name="anexos[]" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                               style="height: auto; padding: 12px;">
                        <div class="form-hint">
                            <i class="fas fa-file-pdf"></i>
                            <span>Formatos aceitos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (máximo 10MB por arquivo)</span>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 5: Assinatura Digital -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-signature"></i>
                        </div>
                        <h2 class="section-title">Assinatura Digital</h2>
                    </div>
                    <p class="section-description">Assine digitalmente para confirmar a veracidade das informações</p>

                    <div class="signature-wrapper">
                        <label class="form-label required" style="margin-bottom: 15px;">Assine no campo abaixo</label>
                        <canvas id="signature-canvas" class="signature-canvas" width="1200" height="400"></canvas>
                        <div class="signature-controls">
                            <button type="button" class="btn btn-secondary" id="btn-clear-signature">
                                <i class="fas fa-eraser"></i>
                                Limpar Assinatura
                            </button>
                        </div>
                        <p class="signature-note">
                            <i class="fas fa-shield-alt"></i>
                            Sua assinatura digital confirma que as informações prestadas são verdadeiras e que você possui autorização para representar a empresa.
                        </p>
                    </div>
                    <input type="hidden" id="assinatura_digital" name="assinatura_digital" required>
                </div>
            </form>

            <!-- Footer com Botão de Envio -->
            <div class="form-footer">
                <button type="submit" form="form-resposta-cdf" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Proposta Comercial
                </button>
                <p style="font-size: 13px; color: #64748b; margin-top: 15px; margin-bottom: 0;">
                    Ao enviar, você concorda com os termos e condições da solicitação
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================
        // MÁSCARAS DE FORMATAÇÃO
        // ============================================
        function maskCNPJ(value) {
            return value
                .replace(/\D/g, '')
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .substring(0, 18);
        }

        function maskTelefone(value) {
            return value
                .replace(/\D/g, '')
                .replace(/^(\d{2})(\d)/g, '($1) $2')
                .replace(/(\d)(\d{4})$/, '$1-$2')
                .substring(0, 15);
        }

        // Aplicar máscaras
        const cnpjInput = document.getElementById('cnpj');
        const telefoneInput = document.getElementById('telefone');

        cnpjInput.addEventListener('input', function(e) {
            e.target.value = maskCNPJ(e.target.value);
        });

        telefoneInput.addEventListener('input', function(e) {
            e.target.value = maskTelefone(e.target.value);
        });

        // ============================================
        // CONSULTA AUTOMÁTICA DE CNPJ
        // ============================================
        const razaoSocialInput = document.getElementById('razao_social');
        const emailInput = document.getElementById('email');
        const cnpjLoading = document.querySelector('.cnpj-loading');
        const cnpjSuccess = document.querySelector('.cnpj-success');

        cnpjInput.addEventListener('blur', async function() {
            const cnpj = this.value.replace(/\D/g, '');

            if (cnpj.length === 14) {
                cnpjLoading.style.display = 'block';
                cnpjSuccess.style.display = 'none';

                try {
                    const basePath = '/module-proxy/price_basket';
                    const response = await fetch(`${basePath}/api/cdf/consultar-cnpj/${cnpj}`);
                    const data = await response.json();

                    console.log('=== DEBUG CNPJ AUTO-FILL ===');
                    console.log('1. Resposta completa da API:', data);
                    console.log('2. Campo telefone ANTES:', telefoneInput.value);

                    if (data.success) {
                        console.log('3. Telefone recebido da API:', data.data.telefone);
                        console.log('4. Tipo do telefone recebido:', typeof data.data.telefone);
                        console.log('5. Telefone é vazio?', data.data.telefone === '');
                        console.log('6. Telefone é null?', data.data.telefone === null);
                        console.log('7. Telefone é undefined?', data.data.telefone === undefined);

                        // Preencher campos
                        razaoSocialInput.value = data.data.razao_social || '';
                        emailInput.value = data.data.email || '';

                        // Preencher telefone e verificar
                        const telefoneRecebido = data.data.telefone || '';
                        console.log('8. Telefone a ser preenchido (após || ""):', telefoneRecebido);

                        telefoneInput.value = telefoneRecebido;

                        console.log('9. Campo telefone DEPOIS:', telefoneInput.value);
                        console.log('10. Campo está disabled?', telefoneInput.disabled);
                        console.log('11. Campo está readonly?', telefoneInput.readOnly);
                        console.log('12. Campo tem display none?', window.getComputedStyle(telefoneInput).display === 'none');

                        // Disparar evento input para atualizar qualquer listener
                        telefoneInput.dispatchEvent(new Event('input', { bubbles: true }));

                        console.log('13. Campo telefone APÓS dispatchEvent:', telefoneInput.value);
                        console.log('=== FIM DEBUG ===');

                        cnpjSuccess.style.display = 'block';

                        // Ocultar mensagem de sucesso após 5 segundos
                        setTimeout(() => {
                            cnpjSuccess.style.display = 'none';
                        }, 5000);
                    }
                } catch (error) {
                    console.error('Erro ao consultar CNPJ:', error);
                } finally {
                    cnpjLoading.style.display = 'none';
                }
            }
        });

        // ============================================
        // CÁLCULO AUTOMÁTICO DE PREÇO TOTAL
        // ============================================
        document.querySelectorAll('.preco-unitario').forEach(input => {
            input.addEventListener('input', function() {
                const index = this.dataset.index;
                const quantidade = parseFloat(this.dataset.quantidade) || 1;
                const precoUnitario = parseFloat(this.value) || 0;
                const precoTotal = (quantidade * precoUnitario).toFixed(2);

                document.querySelector(`input[name="itens[${index}][preco_total]"]`).value = precoTotal;
            });
        });

        // ============================================
        // ASSINATURA DIGITAL (CANVAS)
        // ============================================
        const canvas = document.getElementById('signature-canvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let hasSignature = false;

        // Configurar canvas
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        function getCoordinates(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;

            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;

            return {
                x: x * scaleX,
                y: y * scaleY
            };
        }

        function startDrawing(e) {
            isDrawing = true;
            hasSignature = true;
            const coords = getCoordinates(e);
            ctx.beginPath();
            ctx.moveTo(coords.x, coords.y);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const coords = getCoordinates(e);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();
        }

        function stopDrawing() {
            isDrawing = false;
        }

        // Mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        // Touch events
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);

        // Limpar assinatura
        document.getElementById('btn-clear-signature').addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
        });

        // ============================================
        // ENVIO DO FORMULÁRIO
        // ============================================
        const form = document.getElementById('form-resposta-cdf');
        const loadingOverlay = document.getElementById('loading-overlay');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const errorContent = document.getElementById('error-content');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validar assinatura
            if (!hasSignature) {
                errorContent.innerHTML = '<div class="alert-title">Assinatura obrigatória</div><p style="margin: 0;">Por favor, assine o documento antes de enviar.</p>';
                errorMessage.classList.add('show');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Capturar assinatura em base64
            const assinaturaBase64 = canvas.toDataURL('image/png');
            document.getElementById('assinatura_digital').value = assinaturaBase64;

            // Preparar FormData
            const formData = new FormData(this);

            // Mostrar loading
            loadingOverlay.style.display = 'flex';
            successMessage.classList.remove('show');
            errorMessage.classList.remove('show');

            try {
                const basePath = '/module-proxy/price_basket';
                const response = await fetch(`${basePath}/api/cdf/responder`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();
                loadingOverlay.style.display = 'none';

                if (data.success) {
                    // Mostrar modal de sucesso bonito
                    Swal.fire({
                        icon: 'success',
                        title: 'Proposta Enviada com Sucesso!',
                        html: `
                            <p style="font-size: 16px; margin-top: 15px;">
                                Obrigado por sua cotação!<br>
                                Entraremos em contato em breve.
                            </p>
                        `,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#2c5282',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Fechar a janela/aba
                            window.close();

                            // Se não conseguir fechar (algumas browsers bloqueiam), redirecionar
                            setTimeout(() => {
                                window.location.href = 'about:blank';
                            }, 500);
                        }
                    });

                    // Desabilitar formulário
                    form.querySelectorAll('input, textarea, button, select, canvas').forEach(el => {
                        el.disabled = true;
                        if (el.tagName === 'CANVAS') {
                            el.style.pointerEvents = 'none';
                            el.style.opacity = '0.6';
                        }
                    });
                } else {
                    let errorHtml = '<div class="alert-title">Erro ao enviar proposta</div>';
                    errorHtml += `<p style="margin: 8px 0 0 0;">${data.message}</p>`;

                    if (data.errors) {
                        errorHtml += '<ul style="margin: 8px 0 0 20px; padding: 0;">';
                        Object.values(data.errors).forEach(errors => {
                            errors.forEach(error => {
                                errorHtml += `<li>${error}</li>`;
                            });
                        });
                        errorHtml += '</ul>';
                    }

                    errorContent.innerHTML = errorHtml;
                    errorMessage.classList.add('show');
                    successMessage.classList.remove('show');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

            } catch (error) {
                loadingOverlay.style.display = 'none';
                errorContent.innerHTML = '<div class="alert-title">Erro de conexão</div><p style="margin: 0;">Não foi possível enviar sua proposta. Verifique sua conexão com a internet e tente novamente.</p>';
                errorMessage.classList.add('show');
                successMessage.classList.remove('show');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>
