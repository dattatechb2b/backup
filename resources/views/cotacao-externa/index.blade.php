@extends('layouts.app')

@section('title', 'Cotação Externa')

@section('content')
<div style="padding: 25px;">
    <!-- Título da Página -->
    <h1 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 25px;">
        COTAÇÃO EXTERNA
    </h1>

    <!-- SEÇÃO 1: UPLOAD DE ARQUIVO -->
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fas fa-file-upload" style="color: #6b7280;"></i>
            <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                ENVIAR COTAÇÃO
            </h2>
        </div>

        <div style="background: white; padding: 20px; border-radius: 6px;">
            <p style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">
                Envie um arquivo de cotação (PDF, Excel ou Word) e o sistema lerá automaticamente os dados.
            </p>

            <div style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 40px; text-align: center; background: #f9fafb; margin-bottom: 15px;" id="upload-area">
                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9ca3af; margin-bottom: 15px;"></i>
                <p style="font-size: 14px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                    Arraste o arquivo aqui ou clique para selecionar
                </p>
                <p style="font-size: 12px; color: #9ca3af;">
                    Formatos aceitos: PDF, Excel (.xlsx, .xls), Word (.docx, .doc) - Máx: 50MB
                </p>
                <input type="file" id="file-input" accept=".pdf,.xlsx,.xls,.docx,.doc" style="display: none;">
            </div>

            <div id="loading-area" style="display: none; text-align: center; padding: 20px;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                <p style="margin-top: 15px; font-size: 13px; color: #6b7280;">Lendo arquivo... Aguarde.</p>
            </div>

            <div id="file-info" style="display: none; padding: 15px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file" style="color: #3b82f6; font-size: 24px;"></i>
                    <div style="flex: 1;">
                        <p style="font-size: 13px; font-weight: 600; color: #374151; margin: 0;" id="file-name"></p>
                        <p style="font-size: 12px; color: #6b7280; margin: 0;" id="file-size"></p>
                    </div>
                </div>
            </div>

            <button type="button" id="btn-upload" style="display: none; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-upload"></i> PROCESSAR ARQUIVO
            </button>
        </div>
    </div>

    <!-- SEÇÃO 2: DADOS EXTRAÍDOS (Aparece após upload) -->
    <div id="secao-dados" style="display: none; background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-table" style="color: #6b7280;"></i>
                <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                    DADOS EXTRAÍDOS
                </h2>
            </div>
            <span id="total-itens-badge" style="background: #10b981; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                0 itens
            </span>
        </div>

        <div style="background: white; padding: 20px; border-radius: 6px;">
            <div style="margin-bottom: 15px; padding: 12px; background: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                <p style="font-size: 13px; color: #1e40af; margin: 0;">
                    <i class="fas fa-info-circle"></i> Verifique se os dados foram extraídos corretamente. Você pode editar os campos abaixo.
                </p>
            </div>

            <div style="overflow-x: auto; max-height: 500px; overflow-y: auto;">
                <table class="table table-sm table-hover" style="font-size: 12px; width: 100%;">
                    <thead style="position: sticky; top: 0; background: #f3f4f6;">
                        <tr>
                            <th style="padding: 10px; width: 40px;">#</th>
                            <th style="padding: 10px; min-width: 500px;">Descrição Completa</th>
                            <th style="padding: 10px; width: 80px;">Qtd</th>
                            <th style="padding: 10px; width: 80px;">Unid</th>
                            <th style="padding: 10px; width: 120px;">Média Aritmética</th>
                            <th style="padding: 10px; width: 120px;">Valor Total</th>
                            <th style="padding: 10px; width: 100px;">% Diferença</th>
                            <th style="padding: 10px; width: 60px;">Lote</th>
                            <th style="padding: 10px; width: 60px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-itens"></tbody>
                </table>
            </div>

            <!-- TABELA DE FORNECEDORES (aparece se houver fornecedores extraídos) -->
            <div id="secao-fornecedores" style="display: none; margin-top: 20px; background: #fffbeb; border: 1px solid #fcd34d; padding: 15px; border-radius: 6px;">
                <h3 style="font-size: 13px; font-weight: 600; color: #92400e; margin-bottom: 10px;">
                    <i class="fas fa-store"></i> FORNECEDORES E PREÇOS INDIVIDUAIS
                </h3>
                <p style="font-size: 12px; color: #78350f; margin-bottom: 10px;">
                    Abaixo estão os fornecedores encontrados no documento e seus preços individuais para cada item:
                </p>
                <div id="container-fornecedores"></div>
            </div>

            <!-- VALOR TOTAL GERAL -->
            <div id="secao-valor-total" style="display: none; margin-top: 15px; background: #dbeafe; border: 2px solid #3b82f6; padding: 15px; border-radius: 6px; text-align: right;">
                <span style="font-size: 14px; font-weight: 600; color: #1e40af; margin-right: 15px;">VALOR TOTAL GERAL:</span>
                <span id="valor-total-geral" style="font-size: 18px; font-weight: 700; color: #1e3a8a;">R$ 0,00</span>
            </div>

            <div style="margin-top: 15px; display: flex; justify-content: space-between;">
                <button type="button" id="btn-adicionar-item" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>
                <button type="button" id="btn-continuar-orcamentista" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    Continuar <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 3: DADOS DO ORÇAMENTISTA (Aparece após validar dados) -->
    <div id="secao-orcamentista" style="display: none; background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fas fa-user" style="color: #6b7280;"></i>
            <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                DADOS DO ORÇAMENTISTA
            </h2>
        </div>

        <div style="background: white; padding: 20px; border-radius: 6px;">
            <form id="form-orcamentista">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Nome Completo *</label>
                        <input type="text" name="orcamentista_nome" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">CPF</label>
                        <input type="text" name="orcamentista_cpf" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Setor</label>
                        <input type="text" name="orcamentista_setor" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">CNPJ do Órgão</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="orcamentista_cnpj" id="cnpj-input" class="form-control">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="buscarCNPJ()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Razão Social</label>
                        <input type="text" name="orcamentista_razao_social" id="razao-social" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Endereço</label>
                        <input type="text" name="orcamentista_endereco" id="endereco" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Cidade</label>
                        <input type="text" name="orcamentista_cidade" id="cidade" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">UF</label>
                        <input type="text" name="orcamentista_uf" id="uf" class="form-control form-control-sm" maxlength="2">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">CEP</label>
                        <input type="text" name="orcamentista_cep" id="cep" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Brasão do Órgão (opcional)</label>
                        <input type="file" name="brasao" class="form-control form-control-sm" accept="image/*">
                    </div>
                </div>

                <!-- SELEÇÃO DE TEMPLATE -->
                <div style="margin-top: 20px; padding: 15px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                        <i class="fas fa-file-pdf"></i> Modelo de Layout do PDF
                    </label>
                    <select id="template-selector" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: white;">
                        <option value="padrao">📄 Padrão (Layout Atual)</option>
                        <option value="mapa-apuracao">📊 Mapa de Apuração de Preços</option>
                    </select>
                    <small style="display: block; margin-top: 6px; color: #6b7280; font-size: 11px;">
                        Escolha o formato visual que o PDF será gerado
                    </small>
                </div>

                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button type="button" id="btn-voltar-dados" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="button" id="btn-gerar-preview" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Ver Preview <i class="fas fa-eye"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SEÇÃO 4: PREVIEW E CONCLUSÃO -->
    <div id="secao-preview" style="display: none; background: #f3f4f6; padding: 20px; border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i class="fas fa-file-pdf" style="color: #6b7280;"></i>
            <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                PREVIEW DO PDF
            </h2>
        </div>

        <div style="background: white; padding: 20px; border-radius: 6px;">
            <div style="margin-bottom: 15px; padding: 12px; background: #d1fae5; border-left: 4px solid #10b981; border-radius: 4px;">
                <p style="font-size: 13px; color: #065f46; margin: 0;">
                    <i class="fas fa-check-circle"></i> Tudo pronto! Clique em "Concluir" para gerar o PDF final.
                </p>
            </div>

            <iframe id="pdf-preview-iframe" style="width: 100%; height: 600px; border: 1px solid #e5e7eb; border-radius: 6px;"></iframe>

            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <button type="button" id="btn-voltar-orcamentista" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <button type="button" id="btn-concluir" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-check"></i> Concluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const basePath = window.APP_BASE_PATH || '';
    let cotacaoAtual = null;
    let dadosExtraidos = null;

    // Upload Area - Drag & Drop
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');

    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#3b82f6';
        uploadArea.style.background = '#eff6ff';
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#d1d5db';
        uploadArea.style.background = '#f9fafb';
    });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#d1d5db';
        uploadArea.style.background = '#f9fafb';
        if (e.dataTransfer.files.length) {
            handleFileSelect(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFileSelect(e.target.files[0]);
        }
    });

    function handleFileSelect(file) {
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = formatBytes(file.size);
        document.getElementById('file-info').style.display = 'block';
        document.getElementById('btn-upload').style.display = 'inline-block';

        // Fazer upload automaticamente
        uploadFile(file);
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('arquivo', file);

        document.getElementById('upload-area').style.display = 'none';
        document.getElementById('file-info').style.display = 'none';
        document.getElementById('btn-upload').style.display = 'none';
        document.getElementById('loading-area').style.display = 'block';

        // Usar XMLHttpRequest em vez de fetch para melhor compatibilidade com CSRF
        const xhr = new XMLHttpRequest();
        xhr.open('POST', basePath + '/cotacao-externa/upload', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.onload = function() {
            document.getElementById('loading-area').style.display = 'none';

            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        cotacaoAtual = data.cotacao_id;
                        dadosExtraidos = data.dados;
                        preencherTabela(data.dados.itens);

                        // Exibir fornecedores se existirem
                        if (data.dados.fornecedores && data.dados.fornecedores.length > 0) {
                            preencherFornecedores(data.dados.fornecedores, data.dados.itens);
                        }

                        // Exibir valor total geral
                        if (data.dados.valor_total_geral) {
                            document.getElementById('valor-total-geral').textContent =
                                'R$ ' + parseFloat(data.dados.valor_total_geral).toFixed(2).replace('.', ',');
                            document.getElementById('secao-valor-total').style.display = 'block';
                        }

                        document.getElementById('secao-dados').style.display = 'block';
                        alert('✅ ' + data.message);
                    } else {
                        alert('❌ Erro: ' + data.message);
                        resetUpload();
                    }
                } catch (e) {
                    alert('❌ Erro ao processar resposta: ' + e);
                    resetUpload();
                }
            } else {
                alert('❌ Erro HTTP ' + xhr.status + ': ' + xhr.statusText);
                resetUpload();
            }
        };

        xhr.onerror = function() {
            document.getElementById('loading-area').style.display = 'none';
            alert('❌ Erro de rede ao enviar arquivo');
            resetUpload();
        };

        xhr.send(formData);
    }

    function resetUpload() {
        document.getElementById('upload-area').style.display = 'block';
        document.getElementById('file-info').style.display = 'none';
        document.getElementById('btn-upload').style.display = 'none';
        fileInput.value = '';
    }

    function preencherTabela(itens) {
        const tbody = document.getElementById('tabela-itens');
        tbody.innerHTML = '';

        itens.forEach((item, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="padding: 8px; width: 40px;">${item.numero}</td>
                <td style="padding: 8px; min-width: 500px;"><textarea class="form-control form-control-sm" rows="3" data-index="${index}" data-field="descricao" style="width: 100%; resize: vertical;">${item.descricao || ''}</textarea></td>
                <td style="padding: 8px; width: 80px;"><input type="number" class="form-control form-control-sm" value="${item.quantidade || 0}" data-index="${index}" data-field="quantidade"></td>
                <td style="padding: 8px; width: 80px;"><input type="text" class="form-control form-control-sm" value="${item.unidade || 'UN'}" data-index="${index}" data-field="unidade"></td>
                <td style="padding: 8px; width: 120px;"><input type="number" step="0.01" class="form-control form-control-sm" value="${item.preco_unitario || 0}" data-index="${index}" data-field="preco_unitario"></td>
                <td style="padding: 8px; width: 120px;">R$ ${(item.preco_total || 0).toFixed(2)}</td>
                <td style="padding: 8px; width: 100px;"><input type="text" class="form-control form-control-sm" value="${item.percentual_diferenca || ''}" data-index="${index}" data-field="percentual_diferenca" placeholder="Ex: 5,93%"></td>
                <td style="padding: 8px; width: 60px;"><input type="text" class="form-control form-control-sm" value="${item.lote || '1'}" data-index="${index}" data-field="lote"></td>
                <td style="padding: 8px; width: 60px;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removerItem(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.getElementById('total-itens-badge').textContent = itens.length + ' itens';

        // Atualizar dados ao editar
        tbody.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                const field = this.dataset.field;
                dadosExtraidos.itens[index][field] = this.value;
            });
        });
    }

    function removerItem(index) {
        if (confirm('Deseja remover este item?')) {
            dadosExtraidos.itens.splice(index, 1);
            preencherTabela(dadosExtraidos.itens);
        }
    }

    function preencherFornecedores(fornecedores, itens) {
        const container = document.getElementById('container-fornecedores');
        container.innerHTML = '';

        // Criar tabela de fornecedores
        itens.forEach((item, index) => {
            if (!item.precos_fornecedores || Object.keys(item.precos_fornecedores).length === 0) {
                return; // Pular item sem preços de fornecedores
            }

            const divItem = document.createElement('div');
            divItem.style = 'margin-bottom: 20px; background: white; padding: 12px; border-radius: 4px;';

            let html = `
                <div style="font-weight: 600; font-size: 12px; color: #374151; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">
                    Item ${item.numero} - ${item.descricao.substring(0, 80)}${item.descricao.length > 80 ? '...' : ''}
                </div>
                <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                    <thead style="background: #f3f4f6;">
                        <tr>
                            <th style="padding: 6px 10px; text-align: left; border: 1px solid #d1d5db;">Fornecedor</th>
                            <th style="padding: 6px 10px; text-align: right; border: 1px solid #d1d5db; width: 120px;">Preço Unit.</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // Adicionar linha para cada fornecedor
            for (const [fornecedor, preco] of Object.entries(item.precos_fornecedores)) {
                html += `
                    <tr>
                        <td style="padding: 6px 10px; border: 1px solid #d1d5db;">${fornecedor}</td>
                        <td style="padding: 6px 10px; text-align: right; border: 1px solid #d1d5db; font-weight: 500;">R$ ${parseFloat(preco).toFixed(2).replace('.', ',')}</td>
                    </tr>
                `;
            }

            html += `
                    </tbody>
                </table>
            `;

            divItem.innerHTML = html;
            container.appendChild(divItem);
        });

        // Mostrar seção de fornecedores
        document.getElementById('secao-fornecedores').style.display = 'block';
    }

    document.getElementById('btn-adicionar-item').addEventListener('click', function() {
        const novoItem = {
            numero: dadosExtraidos.itens.length + 1,
            descricao: '',
            fornecedor: '',
            quantidade: 1,
            unidade: 'UN',
            preco_unitario: 0,
            preco_total: 0,
            percentual_diferenca: '',
            lote: '1',
            marca: ''
        };
        dadosExtraidos.itens.push(novoItem);
        preencherTabela(dadosExtraidos.itens);
    });

    document.getElementById('btn-continuar-orcamentista').addEventListener('click', function() {
        document.getElementById('secao-dados').style.display = 'none';
        document.getElementById('secao-orcamentista').style.display = 'block';
    });

    document.getElementById('btn-voltar-dados').addEventListener('click', function() {
        document.getElementById('secao-orcamentista').style.display = 'none';
        document.getElementById('secao-dados').style.display = 'block';
    });

    function buscarCNPJ() {
        const cnpj = document.getElementById('cnpj-input').value.replace(/\D/g, '');
        if (cnpj.length !== 14) {
            alert('CNPJ inválido');
            return;
        }

        console.log('Buscando CNPJ:', cnpj);

        fetch(basePath + `/orcamentos/consultar-cnpj/${cnpj}`)
            .then(response => response.json())
            .then(data => {
                console.log('Resposta CNPJ:', data);
                if (data.success) {
                    // Preencher razão social (tentar múltiplos campos)
                    const razaoSocial = data.data.nome || data.data.razao_social || data.data.fantasia || '';
                    document.getElementById('razao-social').value = razaoSocial;
                    console.log('Razão Social preenchida:', razaoSocial);

                    // Preencher endereço
                    const endereco = (data.data.logradouro || '') + (data.data.numero ? ', ' + data.data.numero : '') + (data.data.bairro ? ' - ' + data.data.bairro : '');
                    document.getElementById('endereco').value = endereco;

                    // Preencher cidade, UF, CEP
                    document.getElementById('cidade').value = data.data.municipio || data.data.cidade || '';
                    document.getElementById('uf').value = data.data.uf || '';
                    document.getElementById('cep').value = data.data.cep || '';

                    alert('✅ CNPJ consultado com sucesso!');
                } else {
                    alert('❌ Erro ao consultar CNPJ: ' + (data.message || 'Desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro ao buscar CNPJ:', error);
                alert('❌ Erro ao consultar CNPJ: ' + error);
            });
    }

    document.getElementById('btn-gerar-preview').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('form-orcamentista'));
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        console.log('Enviando dados do orçamentista:', Object.fromEntries(formData));

        fetch(basePath + `/cotacao-externa/salvar-orcamentista/${cotacaoAtual}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Status da resposta:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Resposta do servidor:', data);
            if (data.success) {
                // Pegar template selecionado
                const templateSelecionado = document.getElementById('template-selector').value;

                // ABRIR PREVIEW EM NOVA ABA com template selecionado
                const previewUrl = basePath + `/cotacao-externa/preview/${cotacaoAtual}?template=${templateSelecionado}`;
                window.open(previewUrl, '_blank');

                // Mostrar mensagem de sucesso
                alert('✅ Dados salvos! Preview aberto em nova aba.');

                // Opcional: Mostrar botão de concluir
                document.getElementById('secao-orcamentista').style.display = 'none';
                document.getElementById('secao-preview').style.display = 'block';
            } else {
                alert('❌ Erro ao salvar dados: ' + (data.message || 'Desconhecido'));
                console.error('Erro detalhado:', data);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('❌ Erro: ' + error);
        });
    });

    document.getElementById('btn-voltar-orcamentista').addEventListener('click', function() {
        document.getElementById('secao-preview').style.display = 'none';
        document.getElementById('secao-orcamentista').style.display = 'block';
    });

    document.getElementById('btn-concluir').addEventListener('click', function() {
        // Desabilitar botão para evitar double-click
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Concluindo...';

        fetch(basePath + `/cotacao-externa/concluir/${cotacaoAtual}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                _token: document.querySelector('meta[name="csrf-token"]').content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirecionar direto para Realizados (sem alert)
                window.location.href = basePath + '/orcamentos/realizados';
            } else {
                alert('Erro ao concluir: ' + (data.message || 'Desconhecido'));
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check"></i> Concluir';
            }
        })
        .catch(error => {
            alert('Erro: ' + error);
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check"></i> Concluir';
        });
    });
</script>
@endsection
