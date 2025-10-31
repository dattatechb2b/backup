@extends('layouts.app')

@section('title', 'Editar Orçamento - Cesta de Preços')

@section('content')
<style>
    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 13px;
    }

    .form-label .required {
        color: #dc2626;
        margin-left: 2px;
    }

    .form-input, .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #374151;
        transition: border-color 0.2s;
    }

    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-textarea {
        min-height: 150px;
        resize: vertical;
        font-family: inherit;
    }

    .form-helper {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.5;
    }

    .form-error {
        margin-top: 6px;
        font-size: 12px;
        color: #dc2626;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .btn-save {
        padding: 12px 32px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-save:hover {
        background: #1d4ed8;
    }

    .btn-cancel {
        padding: 12px 32px;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
    }
</style>

<!-- Seção de Cabeçalho -->
<div class="welcome-section">
    <h1 class="welcome-title">EDITAR ORÇAMENTO</h1>
    <p class="welcome-subtitle">Altere os dados básicos do orçamento</p>
</div>

<!-- Exibir erros de validação -->
@if ($errors->any())
    <div class="alert-error">
        <strong>Erro ao salvar:</strong>
        <ul style="margin: 8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Formulário -->
<div class="table-section">
    <form method="POST" action="/orcamentos/{{ $orcamento->id }}">
        @csrf
        @method('PUT')

        <!-- Nome do Orçamento -->
        <div class="form-group">
            <label for="nome" class="form-label">
                Nome do Orçamento<span class="required">*</span>
            </label>
            <input
                type="text"
                id="nome"
                name="nome"
                class="form-input"
                value="{{ old('nome', $orcamento->nome) }}"
                required
            >
            <div class="form-helper">
                Insira um nome que seja relevante e autoexplicativo para identificar facilmente o orçamento.
            </div>
            @error('nome')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Referência Externa -->
        <div class="form-group">
            <label for="referencia_externa" class="form-label">
                Referência Externa
            </label>
            <input
                type="text"
                id="referencia_externa"
                name="referencia_externa"
                class="form-input"
                value="{{ old('referencia_externa', $orcamento->referencia_externa) }}"
            >
            <div class="form-helper">
                Caso este orçamento seja referente a uma Dispensa, Licitação ou documento interno, digite o respectivo número do processo. Ex: Processo de Licitação do Nº XXXX/XXXX do Órgão interessado.
            </div>
            @error('referencia_externa')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Objeto -->
        <div class="form-group">
            <label for="objeto" class="form-label">
                Objeto<span class="required">*</span>
            </label>
            <textarea
                id="objeto"
                name="objeto"
                class="form-textarea"
                required
            >{{ old('objeto', $orcamento->objeto) }}</textarea>
            @error('objeto')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Órgão Interessado -->
        <div class="form-group">
            <label for="orgao_interessado" class="form-label">
                Órgão Interessado
            </label>
            <input
                type="text"
                id="orgao_interessado"
                name="orgao_interessado"
                class="form-input"
                value="{{ old('orgao_interessado', $orcamento->orgao_interessado) }}"
            >
            <div class="form-helper">
                Caso este orçamento seja procedente de demanda de outros órgãos do Município, informar o nome do Secretaria ou Órgão solicitante, por exemplo: Sec. Municipal de educação.
            </div>
            @error('orgao_interessado')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Botões de Ação -->
        <div class="form-actions">
            <button type="submit" class="btn-save">
                Salvar Alterações
            </button>
            <a href="/orcamentos/{{ $orcamento->id }}" class="btn-cancel">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection
