@extends('layouts.app')

@section('title', 'CDFs Enviadas')

@section('content')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">
                <i class="fas fa-paper-plane text-primary"></i>
                CDFs Enviadas
            </h2>
            <p class="text-muted">Gerencie as solicitações de cotação enviadas aos fornecedores</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="{{ route('cdfs.enviadas', ['filtro' => 'todos']) }}"
                   class="btn {{ $filtro === 'todos' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fas fa-list"></i> Todos ({{ \App\Models\SolicitacaoCDF::count() }})
                </a>
                <a href="{{ route('cdfs.enviadas', ['filtro' => 'pendentes']) }}"
                   class="btn {{ $filtro === 'pendentes' ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="fas fa-clock"></i> Pendentes ({{ \App\Models\SolicitacaoCDF::where('respondido', false)->count() }})
                </a>
                <a href="{{ route('cdfs.enviadas', ['filtro' => 'respondidos']) }}"
                   class="btn {{ $filtro === 'respondidos' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="fas fa-check-circle"></i> Respondidos ({{ \App\Models\SolicitacaoCDF::where('respondido', true)->count() }})
                </a>
            </div>
        </div>
    </div>

    <!-- Tabela de CDFs -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    @if($cdfs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fornecedor</th>
                                        <th>CNPJ</th>
                                        <th>Email</th>
                                        <th>Orçamento</th>
                                        <th>Data Envio</th>
                                        <th>Válido Até</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cdfs as $cdf)
                                        <tr>
                                            <td><strong>#{{ $cdf->id }}</strong></td>
                                            <td>{{ $cdf->razao_social }}</td>
                                            <td>{{ $cdf->cnpj }}</td>
                                            <td>{{ $cdf->email }}</td>
                                            <td>
                                                <a href="{{ route('orcamentos.elaborar', $cdf->orcamento_id) }}"
                                                   class="text-decoration-none">
                                                    {{ $cdf->orcamento->numero ?? 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ $cdf->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if($cdf->valido_ate)
                                                    @if($cdf->valido_ate->isPast())
                                                        <span class="text-danger">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            {{ $cdf->valido_ate->format('d/m/Y H:i') }}
                                                        </span>
                                                    @else
                                                        <span class="text-success">
                                                            {{ $cdf->valido_ate->format('d/m/Y H:i') }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($cdf->respondido)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i> Respondido
                                                    </span>
                                                    <br><small class="text-muted">{{ $cdf->data_resposta_fornecedor->format('d/m/Y H:i') }}</small>
                                                @else
                                                    @if($cdf->valido_ate && $cdf->valido_ate->isPast())
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times-circle"></i> Expirado
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock"></i> Aguardando
                                                        </span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    @if($cdf->respondido && $cdf->resposta)
                                                        <button type="button"
                                                                class="btn btn-sm btn-success"
                                                                onclick="abrirModalResposta({{ $cdf->resposta->id }})"
                                                                title="Ver Resposta">
                                                            <i class="fas fa-eye"></i> Ver Resposta
                                                        </button>
                                                    @endif

                                                    <button type="button"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="apagarCDF({{ $cdf->id }}, '{{ $cdf->razao_social }}')"
                                                            title="Apagar CDF">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="p-3">
                            {{ $cdfs->appends(['filtro' => $filtro])->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">
                                @if($filtro === 'pendentes')
                                    Nenhuma CDF pendente no momento.
                                @elseif($filtro === 'respondidos')
                                    Nenhuma CDF respondida ainda.
                                @else
                                    Nenhuma CDF enviada ainda.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    .table tbody tr {
        transition: background-color 0.2s;
    }
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Corrigir tamanho dos botões de paginação */
    .pagination {
        margin: 0;
    }
    .pagination .page-link {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid #dee2e6;
    }
    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    /* Estilos do modal de resposta */
    .modal-resposta-header {
        background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px 8px 0 0;
    }

    .info-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .info-section h6 {
        color: #2c5282;
        font-weight: 600;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .info-row {
        display: flex;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .info-row strong {
        min-width: 150px;
        color: #495057;
    }

    .item-resposta {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 0.75rem;
    }

    .item-resposta-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }

    .assinatura-digital {
        max-width: 300px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 0.5rem;
        background: white;
    }
</style>

<!-- Modal de Visualização da Resposta CDF -->
<div class="modal fade" id="modalRespostaCDF" tabindex="-1" aria-labelledby="modalRespostaCDFLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-resposta-header">
                <h5 class="mb-0" id="modalRespostaCDFLabel">
                    <i class="fas fa-file-invoice"></i>
                    Resposta da Solicitação de CDF
                </h5>
            </div>
            <div class="modal-body" id="modalRespostaConteudo">
                <!-- Spinner de carregamento -->
                <div class="text-center py-5" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 text-muted">Carregando resposta...</p>
                </div>

                <!-- Conteúdo será preenchido via JavaScript -->
                <div id="respostaConteudo" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Função auxiliar para limpar backdrops
function limparBackdrops() {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
}

// Função para abrir modal e carregar resposta
async function abrirModalResposta(respostaId) {
    const modalElement = document.getElementById('modalRespostaCDF');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const respostaConteudo = document.getElementById('respostaConteudo');

    // Limpar backdrops anteriores (se houver)
    limparBackdrops();

    // Criar nova instância do modal
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });

    // Adicionar listener para limpar quando fechar
    modalElement.addEventListener('hidden.bs.modal', limparBackdrops, { once: true });

    // Mostrar modal com loading
    modal.show();
    loadingSpinner.style.display = 'block';
    respostaConteudo.style.display = 'none';

    try {
        // IMPORTANTE: Usar caminho absoluto a partir da raiz para pegar o proxy correto
        // O proxy adiciona os headers X-User-* necessários para autenticação
        const baseUrl = window.location.pathname.includes('/module-proxy/price_basket/')
            ? '/module-proxy/price_basket'
            : '';

        const response = await fetch(`${baseUrl}/api/cdf/resposta/${respostaId}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        // Verificar se houve erro de autenticação
        if (response.status === 401) {
            const errorData = await response.json();
            throw new Error('Sessão expirada. Por favor, faça login novamente.');
        }

        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        // Preencher conteúdo do modal
        respostaConteudo.innerHTML = montarHtmlResposta(data);

        // Mostrar conteúdo
        loadingSpinner.style.display = 'none';
        respostaConteudo.style.display = 'block';

    } catch (error) {
        console.error('Erro ao carregar resposta:', error);
        console.error('Detalhes do erro:', {
            message: error.message,
            stack: error.stack,
            respostaId: respostaId
        });
        respostaConteudo.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Erro ao carregar resposta.</strong><br>
                <small>${error.message}</small><br>
                <small>ID da Resposta: ${respostaId}</small><br>
                <button class="btn btn-sm btn-primary mt-2" onclick="abrirModalResposta(${respostaId})">
                    <i class="fas fa-redo"></i> Tentar Novamente
                </button>
            </div>
        `;
        loadingSpinner.style.display = 'none';
        respostaConteudo.style.display = 'block';
    }
}

// Função para montar HTML da resposta
function montarHtmlResposta(data) {
    const resposta = data.resposta;
    const fornecedor = data.fornecedor;
    const itens = data.itens;

    let html = `
        <!-- Informações do Fornecedor -->
        <div class="info-section">
            <h6><i class="fas fa-building"></i> Informações do Fornecedor</h6>
            <div class="info-row">
                <strong>CNPJ:</strong>
                <span>${formatarCNPJ(fornecedor.numero_documento)}</span>
            </div>
            <div class="info-row">
                <strong>Razão Social:</strong>
                <span>${fornecedor.razao_social}</span>
            </div>
            <div class="info-row">
                <strong>Email:</strong>
                <span>${fornecedor.email}</span>
            </div>
            <div class="info-row">
                <strong>Telefone:</strong>
                <span>${fornecedor.telefone}</span>
            </div>
        </div>

        <!-- Condições da Proposta -->
        <div class="info-section">
            <h6><i class="fas fa-file-contract"></i> Condições da Proposta</h6>
            <div class="info-row">
                <strong>Data da Resposta:</strong>
                <span>${formatarData(resposta.data_resposta)}</span>
            </div>
            <div class="info-row">
                <strong>Validade da Proposta:</strong>
                <span>${resposta.validade_proposta} dias</span>
            </div>
            <div class="info-row">
                <strong>Forma de Pagamento:</strong>
                <span>${resposta.forma_pagamento}</span>
            </div>
            ${resposta.observacoes_gerais ? `
            <div class="info-row">
                <strong>Observações Gerais:</strong>
                <span>${resposta.observacoes_gerais}</span>
            </div>
            ` : ''}
        </div>

        <!-- Itens Cotados -->
        <div class="mb-3">
            <h6 style="color: #2c5282; font-weight: 600; margin-bottom: 1rem;">
                <i class="fas fa-box"></i> Itens Cotados (${itens.length})
            </h6>
            ${itens.map((item, index) => `
                <div class="item-resposta">
                    <div class="item-resposta-header">
                        <div>
                            <strong style="color: #2c5282;">Item ${index + 1}: ${item.descricao}</strong>
                            <div style="font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem;">
                                Quantidade: ${formatarNumero(item.quantidade)} ${item.unidade}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.1rem; font-weight: 600; color: #28a745;">
                                R$ ${formatarDinheiro(item.preco_total)}
                            </div>
                            <div style="font-size: 0.85rem; color: #6c757d;">
                                Unit: R$ ${formatarDinheiro(item.preco_unitario)}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Marca:</small>
                            <div><strong>${item.marca}</strong></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Prazo de Entrega:</small>
                            <div><strong>${item.prazo_entrega} dias</strong></div>
                        </div>
                        ${item.observacoes ? `
                        <div class="col-md-4">
                            <small class="text-muted">Observações:</small>
                            <div>${item.observacoes}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `).join('')}
        </div>

        <!-- Valor Total -->
        <div class="info-section" style="background: #d4edda; border: 2px solid #28a745;">
            <div class="info-row" style="font-size: 1.2rem;">
                <strong style="color: #155724;">VALOR TOTAL DA PROPOSTA:</strong>
                <span style="color: #155724; font-weight: 700;">R$ ${formatarDinheiro(resposta.valor_total)}</span>
            </div>
        </div>

        <!-- Assinatura Digital -->
        ${resposta.assinatura_digital ? `
        <div class="mt-3">
            <h6 style="color: #2c5282; font-weight: 600; margin-bottom: 0.75rem;">
                <i class="fas fa-signature"></i> Assinatura Digital
            </h6>
            <img src="${resposta.assinatura_digital}" alt="Assinatura Digital" class="assinatura-digital">
        </div>
        ` : ''}

        <!-- Anexos -->
        ${data.anexos && data.anexos.length > 0 ? `
        <div class="mt-3">
            <h6 style="color: #2c5282; font-weight: 600; margin-bottom: 0.75rem;">
                <i class="fas fa-paperclip"></i> Anexos (${data.anexos.length})
            </h6>
            <div class="list-group">
                ${data.anexos.map(anexo => `
                    <a href="/storage/${anexo.caminho}" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-pdf"></i> ${anexo.nome_arquivo}
                        <span class="badge bg-secondary float-end">${formatarTamanhoArquivo(anexo.tamanho)}</span>
                    </a>
                `).join('')}
            </div>
        </div>
        ` : ''}
    `;

    return html;
}

// Funções auxiliares de formatação
function formatarCNPJ(cnpj) {
    if (!cnpj) return 'N/A';
    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
}

function formatarData(dataStr) {
    if (!dataStr) return 'N/A';
    const data = new Date(dataStr);
    return data.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatarDinheiro(valor) {
    if (!valor) return '0,00';
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarNumero(valor) {
    if (!valor) return '0';
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarTamanhoArquivo(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

// Função para apagar CDF com confirmação
async function apagarCDF(cdfId, razaoSocial) {
    // Confirmar exclusão com SweetAlert2
    const result = await Swal.fire({
        title: 'Confirmar Exclusão',
        html: `
            <p>Tem certeza que deseja apagar esta CDF?</p>
            <p class="mb-0"><strong>Fornecedor:</strong> ${razaoSocial}</p>
            <p class="mb-0"><strong>ID:</strong> #${cdfId}</p>
            <p class="text-danger mt-3"><i class="fas fa-exclamation-triangle"></i> Esta ação não pode ser desfeita!</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Sim, Apagar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        // Mostrar loading
        Swal.fire({
            title: 'Apagando CDF...',
            html: 'Por favor, aguarde.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // IMPORTANTE: Usar caminho absoluto para pegar o proxy correto
        const baseUrl = window.location.pathname.includes('/module-proxy/price_basket/')
            ? '/module-proxy/price_basket'
            : '';

        // Fazer requisição DELETE
        const response = await fetch(`${baseUrl}/api/cdf/${cdfId}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Sucesso
            await Swal.fire({
                icon: 'success',
                title: 'CDF Apagada!',
                text: data.message || 'A CDF foi apagada com sucesso.',
                confirmButtonColor: '#28a745'
            });

            // Recarregar a página para atualizar a lista
            window.location.reload();
        } else {
            // Erro retornado pela API
            throw new Error(data.message || 'Erro ao apagar CDF.');
        }

    } catch (error) {
        console.error('Erro ao apagar CDF:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro ao Apagar',
            text: error.message || 'Não foi possível apagar a CDF. Tente novamente.',
            confirmButtonColor: '#dc3545'
        });
    }
}

// Garantir que backdrops sejam limpos ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    limparBackdrops();

    // Também limpar quando qualquer modal for fechado
    document.querySelectorAll('.modal').forEach(function(modalEl) {
        modalEl.addEventListener('hidden.bs.modal', limparBackdrops);
    });

    // 🔔 ABRIR MODAL AUTOMATICAMENTE SE VIER DE NOTIFICAÇÃO
    const urlParams = new URLSearchParams(window.location.search);
    const abrirRespostaId = urlParams.get('abrir_resposta');

    if (abrirRespostaId) {
        console.log('🔔 Abrindo modal da CDF respondida automaticamente:', abrirRespostaId);

        // Aguardar 500ms para garantir que a página carregou completamente
        setTimeout(function() {
            abrirModalResposta(parseInt(abrirRespostaId));

            // Remover parâmetro da URL sem recarregar a página
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }, 500);
    }
});
</script>
@endsection
