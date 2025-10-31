@extends('layouts.app')

@section('title', 'Cesta de Preços - Painel de Bordo')

@section('content')
    <!-- Seção de Boas-vindas -->
    <div class="welcome-section">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 class="welcome-title">OLÁ, {{ strtoupper(Auth::user()->name ?? 'USUÁRIO') }}!</h1>
                <p class="welcome-subtitle">O que você deseja fazer hoje?</p>
            </div>
            <a href="/orcamentos/novo" class="btn-primary" style="text-decoration: none;">
                <i class="fas fa-plus"></i>
                CRIAR UM NOVO ORÇAMENTO.
            </a>
        </div>
    </div>

    <!-- Tabela de Orçamentos Pendentes -->
    <div class="table-section">
        <h2 class="table-header">ÚLTIMOS ORÇAMENTOS PENDENTES</h2>

        @if($orcamentosPendentes->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NÚMERO</th>
                        <th>DATA</th>
                        <th>NOME</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orcamentosPendentes as $orcamento)
                        <tr>
                            <td>
                                <i class="status-icon fas fa-edit"></i>
                                <a href="{{ route('orcamentos.elaborar', $orcamento->id) }}" class="link">{{ $orcamento->numero }}</a>
                            </td>
                            <td>{{ $orcamento->created_at->format('d/m/Y') }}</td>
                            <td>{{ $orcamento->nome }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <i class="empty-state-icon fas fa-clipboard-list"></i>
                <p class="empty-state-text">Nenhum orçamento pendente</p>
                <p class="empty-state-subtext">Crie um novo orçamento para começar</p>
            </div>
        @endif
    </div>

    <!-- Tabela de Orçamentos Realizados -->
    <div class="table-section">
        <h2 class="table-header">ÚLTIMOS ORÇAMENTOS REALIZADOS</h2>

        @if($orcamentosRealizados->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NÚMERO</th>
                        <th>DATA</th>
                        <th>CONCLUSÃO</th>
                        <th>NOME</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orcamentosRealizados as $orcamento)
                        <tr>
                            <td>
                                <i class="status-icon fas fa-check-circle"></i>
                                <a href="{{ route('orcamentos.show', $orcamento->id) }}" class="link">{{ $orcamento->numero }}</a>
                            </td>
                            <td>{{ $orcamento->created_at->format('d/m/Y') }}</td>
                            <td>{{ $orcamento->data_conclusao ? $orcamento->data_conclusao->format('d/m/Y') : '-' }}</td>
                            <td>{{ $orcamento->nome }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <i class="empty-state-icon fas fa-check-circle"></i>
                <p class="empty-state-text">Nenhum orçamento realizado</p>
                <p class="empty-state-subtext">Complete orçamentos pendentes para vê-los aqui</p>
            </div>
        @endif
    </div>
@endsection