@extends('layouts.app')

@section('title', 'Orientações Técnicas - Cesta de Preços')

@section('content')

<style>
    /* Container principal */
    .orientacoes-container {
        background: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Campo de busca */
    .busca-container {
        margin-bottom: 30px;
    }

    .busca-input {
        width: 100%;
        padding: 15px 20px;
        font-size: 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .busca-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Lista de orientações (accordion) */
    .lista-orientacoes {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .orientacao-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s;
    }

    .orientacao-item:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    /* Header do accordion */
    .orientacao-header {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        padding: 18px 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s;
        user-select: none;
    }

    .orientacao-header:hover {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    }

    .orientacao-header.active {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .orientacao-titulo {
        font-size: 15px;
        font-weight: 600;
        color: #374151;
        flex: 1;
        margin: 0;
    }

    .orientacao-header.active .orientacao-titulo {
        color: white;
    }

    .orientacao-icone {
        font-size: 20px;
        color: #9ca3af;
        transition: transform 0.3s;
    }

    .orientacao-header.active .orientacao-icone {
        color: white;
        transform: rotate(180deg);
    }

    /* Conteúdo do accordion */
    .orientacao-conteudo {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    .orientacao-conteudo.active {
        max-height: 5000px;
        transition: max-height 0.5s ease-in;
    }

    .orientacao-conteudo-inner {
        padding: 25px;
        background: white;
        color: #374151;
        line-height: 1.7;
    }

    /* Formatação do conteúdo HTML */
    .orientacao-conteudo-inner p {
        margin-bottom: 15px;
    }

    .orientacao-conteudo-inner ul,
    .orientacao-conteudo-inner ol {
        margin-left: 20px;
        margin-bottom: 15px;
    }

    .orientacao-conteudo-inner li {
        margin-bottom: 8px;
    }

    .orientacao-conteudo-inner strong {
        color: #1f2937;
        font-weight: 700;
    }

    .orientacao-conteudo-inner a {
        color: #3b82f6;
        text-decoration: underline;
    }

    .orientacao-conteudo-inner a:hover {
        color: #2563eb;
    }

    .orientacao-conteudo-inner img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 15px 0;
    }

    /* Mensagem de "nenhuma orientação encontrada" */
    .nenhuma-orientacao {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }

    .nenhuma-orientacao i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }

    /* Contador de resultados */
    .contador-resultados {
        margin-bottom: 20px;
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
    }

    .contador-numero {
        color: #3b82f6;
        font-weight: 700;
    }
</style>

<div class="orientacoes-container">
    <!-- Título da página -->
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0 0 10px 0;">
            <i class="fas fa-comments" style="color: #3b82f6; margin-right: 10px;"></i>
            Orientações Técnicas
        </h1>
        <p style="color: #6b7280; font-size: 14px; margin: 0;">
            Digite parte da sua dúvida para filtrar as orientações técnicas disponíveis.
        </p>
    </div>

    <!-- Campo de busca -->
    <div class="busca-container">
        <input
            type="text"
            id="busca-orientacoes"
            class="busca-input"
            placeholder="Digite parte da sua dúvida aqui... (ex: 'orçamento estimativo', 'IN 65/2021')"
        >
    </div>

    <!-- Contador de resultados -->
    <div class="contador-resultados" id="contador">
        Exibindo <span class="contador-numero" id="contador-numero">{{ count($orientacoes) }}</span>
        {{ count($orientacoes) == 1 ? 'orientação' : 'orientações' }}
    </div>

    <!-- Lista de orientações (accordion) -->
    <div class="lista-orientacoes" id="lista-orientacoes">
        @forelse($orientacoes as $orientacao)
            <div class="orientacao-item" data-id="{{ $orientacao->id }}">
                <div class="orientacao-header" onclick="toggleOrientacao(this)">
                    <p class="orientacao-titulo">
                        <strong style="color: #3b82f6;">{{ $orientacao->numero }}</strong> - {{ $orientacao->titulo }}
                    </p>
                    <i class="fas fa-chevron-down orientacao-icone"></i>
                </div>
                <div class="orientacao-conteudo">
                    <div class="orientacao-conteudo-inner">
                        {!! $orientacao->conteudo !!}
                    </div>
                </div>
            </div>
        @empty
            <div class="nenhuma-orientacao">
                <i class="fas fa-info-circle"></i>
                <p style="font-size: 16px; margin: 0;">Nenhuma orientação técnica cadastrada.</p>
            </div>
        @endforelse
    </div>

    <!-- Mensagem quando busca não retorna resultados -->
    <div class="nenhuma-orientacao" id="nenhuma-encontrada" style="display: none;">
        <i class="fas fa-search"></i>
        <p style="font-size: 16px; margin: 0;">
            Nenhuma orientação encontrada com o termo "<span id="termo-buscado"></span>".
        </p>
        <p style="font-size: 14px; color: #9ca3af; margin-top: 10px;">
            Tente utilizar palavras-chave diferentes.
        </p>
    </div>
</div>

<script>
// Função para abrir/fechar accordion
function toggleOrientacao(header) {
    const content = header.nextElementSibling;
    const isActive = header.classList.contains('active');

    // Fechar todos os outros
    document.querySelectorAll('.orientacao-header.active').forEach(h => {
        h.classList.remove('active');
        h.nextElementSibling.classList.remove('active');
    });

    // Toggle do item clicado
    if (!isActive) {
        header.classList.add('active');
        content.classList.add('active');
    }
}

// Função de busca/filtro
document.getElementById('busca-orientacoes').addEventListener('input', function(e) {
    const termo = this.value.toLowerCase().trim();
    const items = document.querySelectorAll('.orientacao-item');
    const nenhumaEncontrada = document.getElementById('nenhuma-encontrada');
    const lista = document.getElementById('lista-orientacoes');
    const contador = document.getElementById('contador-numero');
    const termoSpan = document.getElementById('termo-buscado');

    let encontrados = 0;

    items.forEach(item => {
        const header = item.querySelector('.orientacao-titulo').textContent.toLowerCase();
        const content = item.querySelector('.orientacao-conteudo-inner').textContent.toLowerCase();

        if (termo === '' || header.includes(termo) || content.includes(termo)) {
            item.style.display = 'block';
            encontrados++;
        } else {
            item.style.display = 'none';
        }
    });

    // Atualizar contador
    contador.textContent = encontrados;

    // Mostrar mensagem se nenhuma orientação foi encontrada
    if (encontrados === 0 && termo !== '') {
        lista.style.display = 'none';
        nenhumaEncontrada.style.display = 'block';
        termoSpan.textContent = termo;
    } else {
        lista.style.display = 'flex';
        nenhumaEncontrada.style.display = 'none';
    }
});

// Permitir expandir todas com Ctrl + E
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        const allHeaders = document.querySelectorAll('.orientacao-header');
        const anyActive = document.querySelector('.orientacao-header.active');

        if (anyActive) {
            // Fechar todas
            allHeaders.forEach(header => {
                header.classList.remove('active');
                header.nextElementSibling.classList.remove('active');
            });
        } else {
            // Abrir todas
            allHeaders.forEach(header => {
                header.classList.add('active');
                header.nextElementSibling.classList.add('active');
            });
        }
    }
});
</script>

@endsection
