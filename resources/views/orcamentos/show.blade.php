@extends('layouts.app')

@section('title', 'Detalhes do Orçamento - Cesta de Preços')

@section('content')
<!-- Seção de Cabeçalho -->
<div class="welcome-section">
    <h1 class="welcome-title">DETALHES DO ORÇAMENTO</h1>
    <p class="welcome-subtitle">{{ $orcamento->nome }}</p>
</div>

<!-- Informações do Orçamento -->
<div class="table-section">
    <div style="background: white; padding: 30px; border-radius: 8px;">
        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 15px;">
            <div style="font-weight: 600; color: #6b7280;">Número:</div>
            <div>{{ $orcamento->numero ?? 'Não gerado' }}</div>

            <div style="font-weight: 600; color: #6b7280;">Nome:</div>
            <div>{{ $orcamento->nome }}</div>

            <div style="font-weight: 600; color: #6b7280;">Referência Externa:</div>
            <div>{{ $orcamento->referencia_externa ?? '-' }}</div>

            <div style="font-weight: 600; color: #6b7280;">Objeto:</div>
            <div>{{ $orcamento->objeto }}</div>

            <div style="font-weight: 600; color: #6b7280;">Órgão Interessado:</div>
            <div>{{ $orcamento->orgao_interessado ?? '-' }}</div>

            <div style="font-weight: 600; color: #6b7280;">Status:</div>
            <div>
                @if($orcamento->status === 'pendente')
                    <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">PENDENTE</span>
                @else
                    <span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">REALIZADO</span>
                @endif
            </div>

            <div style="font-weight: 600; color: #6b7280;">Criado por:</div>
            <div>{{ $orcamento->user->name }}</div>

            <div style="font-weight: 600; color: #6b7280;">Data de Criação:</div>
            <div>{{ $orcamento->created_at->format('d/m/Y H:i') }}</div>

            @if($orcamento->data_conclusao)
            <div style="font-weight: 600; color: #6b7280;">Data de Conclusão:</div>
            <div>{{ $orcamento->data_conclusao->format('d/m/Y H:i') }}</div>
            @endif
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 12px;">
            @if($orcamento->status === 'pendente')
            <a href="/orcamentos/{{ $orcamento->id }}/elaborar" class="btn" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fas fa-edit"></i> ELABORAR ORÇAMENTO
            </a>
            <a href="/orcamentos/{{ $orcamento->id }}/editar" class="btn" style="background: #6b7280; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fas fa-edit"></i> EDITAR DADOS
            </a>
            @endif

            <a href="/orcamentos/{{ $orcamento->status === 'pendente' ? 'pendentes' : 'realizados' }}" class="btn" style="background: #f3f4f6; color: #374151; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid #d1d5db;">
                <i class="fas fa-arrow-left"></i> VOLTAR
            </a>
        </div>
    </div>
</div>
@endsection
