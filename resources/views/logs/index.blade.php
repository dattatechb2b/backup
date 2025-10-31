@extends('layouts.app')

@section('title', 'Sistema de Logs Detalhado - Cesta de Preços')

@section('content')
<style>
    .log-viewer-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 24px;
        margin-bottom: 20px;
    }

    .log-viewer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e5e7eb;
    }

    .log-viewer-title {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .log-filters {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .log-filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .log-filter-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .log-filter-select,
    .log-filter-input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .log-filter-select:hover,
    .log-filter-input:hover {
        border-color: #3b82f6;
    }

    .log-filter-select:focus,
    .log-filter-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .log-content {
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
        font-size: 13px;
        line-height: 1.6;
        overflow-x: auto;
        max-height: 600px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .log-empty {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }

    .log-empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .log-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-log-action {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-log-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-log-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
    }

    .btn-log-success {
        background: #10b981;
        color: white;
    }

    .btn-log-success:hover {
        background: #059669;
        transform: translateY(-1px);
    }

    .btn-log-danger {
        background: #ef4444;
        color: white;
    }

    .btn-log-danger:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    .log-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .log-stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .log-stat-card.browser {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .log-stat-card.server {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .log-stat-card.combined {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .log-stat-label {
        font-size: 12px;
        opacity: 0.9;
        font-weight: 600;
    }

    .log-stat-value {
        font-size: 32px;
        font-weight: 700;
    }
</style>

<!-- Cabeçalho -->
<div class="welcome-section">
    <h1 class="welcome-title">SISTEMA DE LOGS DETALHADO</h1>
    <p class="welcome-subtitle">Visualização completa de logs do servidor e navegador</p>
</div>

<!-- Container principal -->
<div class="log-viewer-container">
    <!-- Estatísticas -->
    <div class="log-stats">
        <div class="log-stat-card browser">
            <div class="log-stat-label">LOGS DO NAVEGADOR</div>
            <div class="log-stat-value">{{ count($available_dates) > 0 ? count($available_dates) : '0' }}</div>
        </div>
        <div class="log-stat-card server">
            <div class="log-stat-label">TIPO ATUAL</div>
            <div class="log-stat-value">{{ strtoupper($type) }}</div>
        </div>
        <div class="log-stat-card combined">
            <div class="log-stat-label">DATA SELECIONADA</div>
            <div class="log-stat-value" style="font-size: 18px;">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" action="{{ route('logs.index') }}" class="log-filters">
        <div class="log-filter-group">
            <label class="log-filter-label">Tipo de Log</label>
            <select name="type" class="log-filter-select" onchange="this.form.submit()">
                <option value="browser" {{ $type === 'browser' ? 'selected' : '' }}>Navegador</option>
                <option value="server" {{ $type === 'server' ? 'selected' : '' }}>Servidor</option>
                <option value="combined" {{ $type === 'combined' ? 'selected' : '' }}>Combinado</option>
            </select>
        </div>

        <div class="log-filter-group">
            <label class="log-filter-label">Data</label>
            @if(count($available_dates) > 0)
                <select name="date" class="log-filter-select" onchange="this.form.submit()">
                    @foreach($available_dates as $availableDate)
                        <option value="{{ $availableDate }}" {{ $date === $availableDate ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($availableDate)->format('d/m/Y') }}
                        </option>
                    @endforeach
                </select>
            @else
                <input type="date" name="date" value="{{ $date }}" class="log-filter-input" onchange="this.form.submit()">
            @endif
        </div>

        <div class="log-filter-group" style="padding-top: 20px;">
            <button type="submit" class="btn-log-action btn-log-primary">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
    </form>

    <!-- Ações -->
    <div class="log-actions">
        <a href="{{ route('logs.download', ['type' => $type, 'date' => $date]) }}"
           class="btn-log-action btn-log-success">
            <i class="fas fa-download"></i> Download
        </a>

        <button onclick="confirmarLimpeza()" class="btn-log-action btn-log-danger">
            <i class="fas fa-trash"></i> Limpar Logs Antigos (>7 dias)
        </button>

        <button onclick="copiarConteudo()" class="btn-log-action btn-log-primary">
            <i class="fas fa-copy"></i> Copiar Conteúdo
        </button>
    </div>
</div>

<!-- Conteúdo do Log -->
<div class="log-viewer-container">
    @if(trim($content) === 'Nenhum log encontrado para esta data.')
        <div class="log-empty">
            <div class="log-empty-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <h3>Nenhum log encontrado</h3>
            <p>Não há logs para {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} no tipo "{{ strtoupper($type) }}"</p>
        </div>
    @else
        <pre class="log-content" id="logContent">{{ $content }}</pre>
    @endif
</div>

<script>
function copiarConteudo() {
    const content = document.getElementById('logContent');
    if (!content) {
        alert('Nenhum conteúdo para copiar');
        return;
    }

    navigator.clipboard.writeText(content.textContent).then(() => {
        alert('Conteúdo copiado para a área de transferência!');
    }).catch(err => {
        console.error('Erro ao copiar:', err);
        alert('Erro ao copiar conteúdo');
    });
}

function confirmarLimpeza() {
    if (confirm('Tem certeza que deseja remover todos os logs com mais de 7 dias?\n\nEsta ação não pode ser desfeita.')) {
        fetch('{{ route("logs.clean") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro ao limpar logs: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao limpar logs');
        });
    }
}

// Auto-scroll para o final do log
document.addEventListener('DOMContentLoaded', function() {
    const logContent = document.getElementById('logContent');
    if (logContent) {
        logContent.scrollTop = logContent.scrollHeight;
    }
});
</script>
@endsection
