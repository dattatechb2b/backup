@extends('layouts.app')

@section('title', 'Orçamentos Concluídos - Cesta de Preços')

@section('content')
    <div class="welcome-section">
        <h1 class="welcome-title">ORÇAMENTOS CONCLUÍDOS</h1>
        <p class="welcome-subtitle">Histórico de todos os orçamentos finalizados</p>
    </div>

    <div class="table-section">
        <div class="empty-state">
            <i class="empty-state-icon fas fa-check-circle"></i>
            <p class="empty-state-text">Nenhum orçamento concluído</p>
            <p class="empty-state-subtext">Complete orçamentos pendentes para vê-los aqui</p>
        </div>
    </div>
@endsection