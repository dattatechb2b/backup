@extends('layouts.app')

@section('title', 'Orçamentos Pendentes - Cesta de Preços')

@section('content')
<!-- Mensagens de sucesso/erro -->
@if(session('success'))
    <div style="padding: 12px 16px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 6px; margin-bottom: 20px;">
        {{ session('success') }}
    </div>
@endif

<!-- Seção de Cabeçalho -->
<div class="welcome-section">
    <h1 class="welcome-title">ORÇAMENTOS PENDENTES</h1>
    <p class="welcome-subtitle">Lista de todos os orçamentos aguardando conclusão</p>
</div>

<!-- Tabela de Orçamentos -->
<div class="table-section">
    @if($orcamentos->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>NOME</th>
                    <th>REFERÊNCIA</th>
                    <th>DATA CRIAÇÃO</th>
                    <th>CRIADO POR</th>
                    <th style="text-align: center; width: 280px;">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orcamentos as $orcamento)
                    <tr>
                        <td>
                            <i class="status-icon fas fa-edit" style="color: #f59e0b;"></i>
                            {{ $orcamento->nome }}
                        </td>
                        <td>{{ $orcamento->referencia_externa ?? '-' }}</td>
                        <td>{{ $orcamento->created_at->format('d/m/Y') }}</td>
                        <td>{{ $orcamento->user->name }}</td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <!-- Botão REALIZAR ORÇAMENTO -->
                                <a href="{{ route('orcamentos.elaborar', $orcamento->id) }}"
                                   style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap;"
                                   title="Ir para elaboração do orçamento">
                                    <i class="fas fa-clipboard-check"></i>
                                    REALIZAR
                                </a>

                                <!-- Botão APAGAR ORÇAMENTO -->
                                <form action="{{ route('orcamentos.destroy', $orcamento->id) }}"
                                      method="POST"
                                      style="display: inline; margin: 0;"
                                      onsubmit="return confirm('⚠️ ATENÇÃO: Deseja realmente apagar este orçamento?\n\nTodos os itens, coletas e dados serão permanentemente excluídos.\n\nEsta ação NÃO pode ser desfeita!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;"
                                            title="Apagar orçamento permanentemente">
                                        <i class="fas fa-trash-alt"></i>
                                        APAGAR
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Paginação -->
        @if($orcamentos->hasPages())
            <div style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                {{-- Botão Anterior --}}
                @if($orcamentos->onFirstPage())
                    <span style="display: inline-flex; align-items: center; padding: 8px 12px; background: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 13px; cursor: not-allowed;">
                        <i class="fas fa-chevron-left" style="margin-right: 6px;"></i>
                        Anterior
                    </span>
                @else
                    <a href="{{ $orcamentos->previousPageUrl() }}" style="display: inline-flex; align-items: center; padding: 8px 12px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500;">
                        <i class="fas fa-chevron-left" style="margin-right: 6px;"></i>
                        Anterior
                    </a>
                @endif

                {{-- Informação da página atual --}}
                <span style="padding: 8px 12px; color: #374151; font-size: 13px; font-weight: 500;">
                    Página {{ $orcamentos->currentPage() }} de {{ $orcamentos->lastPage() }}
                </span>

                {{-- Botão Próximo --}}
                @if($orcamentos->hasMorePages())
                    <a href="{{ $orcamentos->nextPageUrl() }}" style="display: inline-flex; align-items: center; padding: 8px 12px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500;">
                        Próximo
                        <i class="fas fa-chevron-right" style="margin-left: 6px;"></i>
                    </a>
                @else
                    <span style="display: inline-flex; align-items: center; padding: 8px 12px; background: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 13px; cursor: not-allowed;">
                        Próximo
                        <i class="fas fa-chevron-right" style="margin-left: 6px;"></i>
                    </span>
                @endif
            </div>

            {{-- Informação de registros --}}
            <div style="margin-top: 12px; text-align: center; color: #6b7280; font-size: 12px;">
                Mostrando {{ $orcamentos->firstItem() }} a {{ $orcamentos->lastItem() }} de {{ $orcamentos->total() }} orçamentos
            </div>
        @endif
    @else
        <div class="empty-state">
            <i class="empty-state-icon fas fa-clipboard-list"></i>
            <p class="empty-state-text">Nenhum orçamento pendente</p>
            <p class="empty-state-subtext">Crie um novo orçamento para começar</p>
            <a href="orcamentos/novo"
               style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px;">
                <i class="fas fa-plus"></i> Criar Novo Orçamento
            </a>
        </div>
    @endif
</div>
@endsection
