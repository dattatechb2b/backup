@extends('layouts.app')

@section('title', 'Orçamentos Realizados - Cesta de Preços')

@section('content')
<!-- Mensagens de sucesso/erro -->
@if(session('success'))
    <div style="padding: 12px 16px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 6px; margin-bottom: 20px;">
        {{ session('success') }}
    </div>
@endif

<!-- Seção de Cabeçalho -->
<div class="welcome-section">
    <h1 class="welcome-title">ORÇAMENTOS REALIZADOS</h1>
    <p class="welcome-subtitle">Lista de todos os orçamentos concluídos</p>
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
                    <th>DATA CONCLUSÃO</th>
                    <th>CRIADO POR</th>
                    <th style="text-align: center;">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orcamentos as $orcamento)
                    @php
                        // Detectar se é cotação externa (não tem relação 'user' como Model Orcamento)
                        $isCotacaoExterna = !isset($orcamento->user) || get_class($orcamento) === 'App\Models\CotacaoExterna';
                    @endphp
                    <tr>
                        <td>
                            <i class="status-icon fas fa-check-circle" style="color: #10b981;"></i>
                            @if($isCotacaoExterna)
                                <i class="fas fa-file-import" style="color: #8b5cf6; margin-right: 4px;" title="Cotação Externa"></i>
                                <span class="link">{{ $orcamento->titulo }}</span>
                            @else
                                <a href="/orcamentos/{{ $orcamento->id }}" class="link">
                                    {{ $orcamento->nome }}
                                </a>
                            @endif
                        </td>
                        <td>{{ $isCotacaoExterna ? 'COTAÇÃO EXTERNA' : ($orcamento->referencia_externa ?? '-') }}</td>
                        <td>{{ $orcamento->created_at->format('d/m/Y') }}</td>
                        <td>{{ $orcamento->data_conclusao ? $orcamento->data_conclusao->format('d/m/Y') : '-' }}</td>
                        <td>{{ $isCotacaoExterna ? '-' : ($orcamento->user ? $orcamento->user->name : '-') }}</td>
                        <td>
                            <!-- Container dos botões - Todos na mesma linha -->
                            <div style="display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: nowrap;">
                                <!-- Botão IMPRIMIR -->
                                <a href="{{ $isCotacaoExterna ? Storage::url($orcamento->arquivo_pdf_path) : ltrim(env('APP_BASE_PATH', ''), '/') . '/orcamentos/' . $orcamento->id . '/imprimir' }}"
                                   target="_blank"
                                   class="btn-action"
                                   style="background: #3b82f6; color: white; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; transition: all 0.2s;"
                                   onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)';"
                                   onmouseout="this.style.background='#3b82f6'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                                   title="{{ $isCotacaoExterna ? 'Visualizar PDF' : 'Imprimir Orçamento' }}">
                                    <i class="fas fa-print"></i> IMPRIMIR
                                </a>

                                @if(!$isCotacaoExterna)
                                    <!-- Botão EXPORTAR EXCEL (apenas para orçamentos normais) -->
                                    <a href="{{ ltrim(env('APP_BASE_PATH', ''), '/') }}/orcamentos/{{ $orcamento->id }}/exportar-excel"
                                       class="btn-action"
                                       style="background: #10b981; color: white; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; transition: all 0.2s;"
                                       onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)';"
                                       onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                                       title="Exportar para Excel">
                                        <i class="fas fa-file-excel"></i> EXCEL
                                    </a>

                                    <!-- Botão ALTERAR (apenas para orçamentos normais) -->
                                    <a href="{{ ltrim(env('APP_BASE_PATH', ''), '/') }}/orcamentos/{{ $orcamento->id }}/elaborar"
                                       class="btn-action"
                                       style="background: #f59e0b; color: white; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; transition: all 0.2s;"
                                       onmouseover="this.style.background='#d97706'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.3)';"
                                       onmouseout="this.style.background='#f59e0b'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                                       title="Alterar Orçamento">
                                        <i class="fas fa-edit"></i> ALTERAR
                                    </a>
                                @endif {{-- Fecha @if(!$isCotacaoExterna) da linha 69 --}}
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Paginação -->
        @if($orcamentos->hasPages())
            <div style="margin-top: 20px; display: flex; justify-content: center;">
                {{ $orcamentos->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <i class="empty-state-icon fas fa-check-circle"></i>
            <p class="empty-state-text">Nenhum orçamento realizado</p>
            <p class="empty-state-subtext">Complete orçamentos pendentes para vê-los aqui</p>
            <a href="/orcamentos/pendentes"
               style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px;">
                <i class="fas fa-clipboard-list"></i> Ver Pendentes
            </a>
        </div>
    @endif
</div>
@endsection
