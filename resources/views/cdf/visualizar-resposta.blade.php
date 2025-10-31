@extends('layouts.app')

@section('title', 'Visualizar Resposta CDF')

@section('content')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-file-invoice text-primary"></i>
                        Resposta CDF #{{ $resposta->id }}
                    </h2>
                    <p class="text-muted mb-0">
                        Recebida em {{ $resposta->data_resposta->format('d/m/Y \à\s H:i') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('orcamentos.elaborar', $solicitacao->orcamento_id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar ao Orçamento
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Principal -->
        <div class="col-lg-8">
            <!-- Card: Dados do Fornecedor -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Dados do Fornecedor</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>CNPJ:</strong><br>
                            {{ $fornecedor->cnpj }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Razão Social:</strong><br>
                            {{ $fornecedor->razao_social }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>E-mail:</strong><br>
                            <a href="mailto:{{ $fornecedor->email }}">{{ $fornecedor->email }}</a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Telefone:</strong><br>
                            {{ $fornecedor->telefone }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Dados da Proposta -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-file-contract"></i> Dados da Proposta</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Validade da Proposta:</strong><br>
                            {{ $resposta->validade_proposta }} dias
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Forma de Pagamento:</strong><br>
                            {{ $resposta->forma_pagamento }}
                        </div>
                        @if($resposta->observacoes_gerais)
                            <div class="col-12 mb-3">
                                <strong>Observações Gerais:</strong><br>
                                <div class="p-3 bg-light rounded">
                                    {{ $resposta->observacoes_gerais }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card: Itens Cotados -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Itens Cotados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Descrição</th>
                                    <th>Qtd</th>
                                    <th>Marca</th>
                                    <th>Preço Unit.</th>
                                    <th>Preço Total</th>
                                    <th>Prazo Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resposta->itens as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $item->itemOrcamento->descricao ?? 'N/A' }}
                                            @if($item->observacoes)
                                                <br><small class="text-muted"><em>Obs: {{ $item->observacoes }}</em></small>
                                            @endif
                                        </td>
                                        <td>{{ $item->itemOrcamento->quantidade ?? 'N/A' }}</td>
                                        <td>{{ $item->marca }}</td>
                                        <td>R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                                        <td><strong>R$ {{ number_format($item->preco_total, 2, ',', '.') }}</strong></td>
                                        <td>{{ $item->prazo_entrega }} dias</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">VALOR TOTAL DA PROPOSTA:</th>
                                    <th colspan="2">
                                        <span class="text-success fs-5">
                                            R$ {{ number_format($resposta->valor_total, 2, ',', '.') }}
                                        </span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Card: Anexos -->
            @if($resposta->anexos->count() > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-paperclip"></i> Anexos</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($resposta->anexos as $anexo)
                                <a href="{{ $anexo->url }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        {{ $anexo->nome_arquivo }}
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">{{ $anexo->tamanho_formatado }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Coluna Lateral -->
        <div class="col-lg-4">
            <!-- Card: Assinatura Digital -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-signature"></i> Assinatura Digital</h5>
                </div>
                <div class="card-body text-center">
                    @if($resposta->assinatura_digital)
                        <img src="{{ $resposta->assinatura_digital }}" alt="Assinatura" class="img-fluid border rounded p-2">
                        <p class="text-muted mt-2 mb-0"><small>Assinado digitalmente</small></p>
                    @else
                        <p class="text-muted">Sem assinatura digital</p>
                    @endif
                </div>
            </div>

            <!-- Card: Resumo da Solicitação -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Resumo da Solicitação</h5>
                </div>
                <div class="card-body">
                    <p><strong>Número da Solicitação:</strong><br>#{{ $solicitacao->id }}</p>
                    <p><strong>Orçamento:</strong><br>{{ $solicitacao->orcamento->numero ?? 'N/A' }}</p>
                    <p><strong>Status:</strong><br>
                        <span class="badge bg-success">Respondida</span>
                    </p>
                    <p><strong>Data de Envio:</strong><br>
                        {{ $solicitacao->created_at->format('d/m/Y H:i') }}
                    </p>
                    <p><strong>Data de Resposta:</strong><br>
                        {{ $solicitacao->data_resposta_fornecedor->format('d/m/Y H:i') }}
                    </p>
                    <p class="mb-0"><strong>Prazo Solicitado:</strong><br>
                        {{ $solicitacao->prazo_resposta_dias }} dias
                    </p>
                </div>
            </div>

            <!-- Card: Ações -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Ações</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-success w-100 mb-2" onclick="aceitarProposta()">
                        <i class="fas fa-check"></i> Aceitar Proposta
                    </button>
                    <button class="btn btn-danger w-100 mb-2" onclick="recusarProposta()">
                        <i class="fas fa-times"></i> Recusar Proposta
                    </button>
                    <button class="btn btn-info w-100" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function aceitarProposta() {
        if (confirm('Deseja aceitar esta proposta e importar os preços para o orçamento?')) {
            // TODO: Implementar importação de preços
            alert('Funcionalidade em desenvolvimento');
        }
    }

    function recusarProposta() {
        const motivo = prompt('Digite o motivo da recusa:');
        if (motivo) {
            // TODO: Implementar recusa
            alert('Funcionalidade em desenvolvimento');
        }
    }
</script>

<style>
    @media print {
        .sidebar, .btn, .card-header { display: none !important; }
        .col-lg-4 { display: none !important; }
        .col-lg-8 { flex: 0 0 100% !important; max-width: 100% !important; }
    }
</style>
@endsection
