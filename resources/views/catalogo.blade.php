@extends('layouts.app')

@section('title', 'Catálogo de Produtos')

@section('content')
<div style="padding: 25px;">
    <!-- Título da Página -->
    <h1 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
        <i class="fas fa-boxes" style="color: #3b82f6;"></i>
        CATÁLOGO DE PRODUTOS
    </h1>
    <p style="font-size: 13px; color: #6b7280; margin-bottom: 25px;">
        Produtos extraídos dos orçamentos realizados no sistema
    </p>

    <!-- ============================= -->
    <!-- CONTEÚDO: PRODUTOS LOCAIS -->
    <!-- ============================= -->
    <div id="content-locais" class="tab-content">
        <!-- SEÇÃO 1: FILTROS DE PESQUISA -->
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <i class="fas fa-filter" style="color: #6b7280;"></i>
                <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                    FILTROS DE PESQUISA - PRODUTOS LOCAIS
                </h2>
            </div>

            <div style="background: white; padding: 20px; border-radius: 6px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            Buscar produto:
                        </label>
                        <input type="text" id="termo_busca_local" placeholder="Digite descrição do produto (ex: caneta, papel...)" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            Estado (UF):
                        </label>
                        <select id="filtro_uf_local" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <option value="">Todos os Estados</option>
                            @foreach(['AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins'] as $sigla => $nome)
                            <option value="{{ $sigla }}">{{ $sigla }} - {{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                            Período:
                        </label>
                        <select id="filtro_periodo_local" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <option value="">Todos os períodos</option>
                            <option value="30">Últimos 30 dias</option>
                            <option value="90">Últimos 3 meses</option>
                            <option value="180">Últimos 6 meses</option>
                            <option value="365">Último ano</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" id="btn-buscar-local" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-search"></i>
                        BUSCAR PRODUTOS
                    </button>
                    <button type="button" id="btn-limpar-local" style="background: white; color: #6b7280; border: 1px solid #d1d5db; padding: 12px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-times"></i>
                        LIMPAR FILTROS
                    </button>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 2: ESTATÍSTICAS RÁPIDAS -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-box-open" style="font-size: 32px; opacity: 0.9;"></i>
                    <span id="stat-produtos" style="font-size: 28px; font-weight: bold;">--</span>
                </div>
                <div style="font-size: 13px; opacity: 0.95; font-weight: 500;">PRODUTOS ÚNICOS</div>
            </div>
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-list" style="font-size: 32px; opacity: 0.9;"></i>
                    <span id="stat-registros" style="font-size: 28px; font-weight: bold;">--</span>
                </div>
                <div style="font-size: 13px; opacity: 0.95; font-weight: 500;">REGISTROS DE PREÇOS</div>
            </div>
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 32px; opacity: 0.9;"></i>
                    <span id="stat-orcamentos" style="font-size: 28px; font-weight: bold;">--</span>
                </div>
                <div style="font-size: 13px; opacity: 0.95; font-weight: 500;">ORÇAMENTOS</div>
            </div>
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-map-marked-alt" style="font-size: 32px; opacity: 0.9;"></i>
                    <span id="stat-estados" style="font-size: 28px; font-weight: bold;">--</span>
                </div>
                <div style="font-size: 13px; opacity: 0.95; font-weight: 500;">ESTADOS</div>
            </div>
        </div>

        <!-- SEÇÃO 3: GRID DE PRODUTOS -->
        <div id="grid-produtos-locais" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <!-- Produtos serão inseridos aqui via JavaScript -->
        </div>

        <!-- SEÇÃO 4: PAGINAÇÃO -->
        <div id="pagination-local" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px;">
            <!-- Paginação será inserida aqui via JavaScript -->
        </div>
    </div>

</div>

<!-- MODAL: Histórico de Preços -->
<div id="modal-historico" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; padding: 20px; overflow-y: auto;">
    <div style="max-width: 900px; margin: 50px auto; background: white; border-radius: 12px; box-shadow: 0 20px 25px rgba(0,0,0,0.2);">
        <div style="padding: 25px; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 0;">
                    <i class="fas fa-history" style="color: #3b82f6;"></i>
                    HISTÓRICO DE PREÇOS
                </h2>
                <button onclick="fecharModalHistorico()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    ×
                </button>
            </div>
            <div id="modal-produto-info" style="margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 6px; font-size: 13px;">
                <!-- Informações do produto -->
            </div>
        </div>
        <div id="modal-historico-content" style="padding: 25px; max-height: 500px; overflow-y: auto;">
            <!-- Conteúdo do histórico -->
        </div>
    </div>
</div>

<style>
.tab-button:hover {
    background: #f9fafb !important;
}

.tab-button.active {
    color: #3b82f6 !important;
    border-bottom-color: #3b82f6 !important;
}

.produto-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
    transition: all 0.3s;
}

.produto-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}
</style>

<script>
// Configuração base
const baseUrl = window.location.pathname.includes('/module-proxy/price_basket/')
    ? '/module-proxy/price_basket'
    : '';

// ===========================
// PRODUTOS LOCAIS
// ===========================
let paginaAtualLocal = 1;
let produtoAtual = null; // Para o modal

async function carregarProdutosLocais() {
    const termo = document.getElementById('termo_busca_local').value;
    const uf = document.getElementById('filtro_uf_local').value;
    const periodo = document.getElementById('filtro_periodo_local').value;

    try {
        const response = await fetch(`${baseUrl}/catalogo/produtos-locais?termo=${termo}&uf=${uf}&periodo=${periodo}&pagina=${paginaAtualLocal}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        const data = await response.json();

        if (data.success) {
            // Atualizar estatísticas
            const statProdutos = document.getElementById('stat-produtos');
            const statRegistros = document.getElementById('stat-registros');
            const statOrcamentos = document.getElementById('stat-orcamentos');
            const statEstados = document.getElementById('stat-estados');

            if (statProdutos) statProdutos.textContent = data.estatisticas.total_produtos;
            if (statRegistros) statRegistros.textContent = data.estatisticas.total_registros;
            if (statOrcamentos) statOrcamentos.textContent = data.estatisticas.total_orcamentos;
            if (statEstados) statEstados.textContent = data.estatisticas.estados;

            // Renderizar produtos
            renderizarProdutosLocais(data.produtos);

            // Renderizar paginação
            renderizarPaginacaoLocal(data.pagina_atual, data.total_paginas);
        }
    } catch (error) {
        console.error('Erro ao carregar produtos locais:', error);

        // Mostrar mensagem de erro amigável
        const grid = document.getElementById('grid-produtos-locais');
        if (grid) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 8px; border: 2px dashed #ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
                    <h3 style="font-size: 16px; color: #dc2626; margin-bottom: 10px;">Erro ao carregar produtos</h3>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">${error.message || 'Erro desconhecido'}</p>
                    <button onclick="carregarProdutosLocais()" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-sync"></i> Tentar Novamente
                    </button>
                </div>
            `;
        }
    }
}

function renderizarProdutosLocais(produtos) {
    const grid = document.getElementById('grid-produtos-locais');
    grid.innerHTML = '';

    if (produtos.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 8px; border: 2px dashed #d1d5db;">
                <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 20px;"></i>
                <h3 style="font-size: 16px; color: #6b7280; margin-bottom: 10px;">Nenhum produto encontrado</h3>
                <p style="font-size: 13px; color: #9ca3af;">Tente ajustar os filtros de busca</p>
            </div>
        `;
        return;
    }

    produtos.forEach((prod, index) => {
        const card = document.createElement('div');
        card.className = 'produto-card';
        card.innerHTML = `
            <div style="margin-bottom: 12px;">
                <h3 style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 8px; line-height: 1.4;">
                    ${prod.descricao}
                </h3>
                <div style="font-size: 12px; color: #6b7280;">
                    <i class="fas fa-ruler"></i> ${prod.medida_fornecimento || 'N/A'}
                </div>
            </div>

            <div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 12px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; font-size: 12px;">
                    <div>
                        <span style="color: #6b7280;">Mínimo:</span>
                        <div style="color: #10b981; font-weight: 600;">${formatarMoeda(prod.preco_minimo)}</div>
                    </div>
                    <div>
                        <span style="color: #6b7280;">Médio:</span>
                        <div style="color: #3b82f6; font-weight: 600;">${formatarMoeda(prod.preco_medio)}</div>
                    </div>
                    <div>
                        <span style="color: #6b7280;">Máximo:</span>
                        <div style="color: #ef4444; font-weight: 600;">${formatarMoeda(prod.preco_maximo)}</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="font-size: 12px;">
                    <div style="color: #6b7280; margin-bottom: 4px;">
                        <i class="fas fa-shopping-cart"></i> ${prod.quantidade_orcamentos} orçamento(s)
                    </div>
                    <div style="color: #6b7280;">
                        <i class="fas fa-database"></i> ${prod.quantidade_registros} registro(s)
                    </div>
                </div>
                <button onclick="verHistoricoPrecos(${index})" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-history"></i>
                    HISTÓRICO
                </button>
            </div>

            <div style="font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px;">
                <div style="margin-bottom: 4px;"><strong>Órgãos:</strong> ${prod.orgaos}</div>
                <div><strong>UFs:</strong> ${prod.ufs}</div>
            </div>
        `;

        // Armazenar dados do produto para o modal
        card.dataset.produtoIndex = index;
        card.dataset.produtoData = JSON.stringify(prod);

        grid.appendChild(card);
    });
}

function verHistoricoPrecos(index) {
    const card = document.querySelector(`[data-produto-index="${index}"]`);
    const produto = JSON.parse(card.dataset.produtoData);

    // Atualizar informações do produto no modal
    document.getElementById('modal-produto-info').innerHTML = `
        <div style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">${produto.descricao}</div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div>
                <span style="color: #6b7280;">Mínimo:</span>
                <span style="color: #10b981; font-weight: 600; margin-left: 5px;">${formatarMoeda(produto.preco_minimo)}</span>
            </div>
            <div>
                <span style="color: #6b7280;">Médio:</span>
                <span style="color: #3b82f6; font-weight: 600; margin-left: 5px;">${formatarMoeda(produto.preco_medio)}</span>
            </div>
            <div>
                <span style="color: #6b7280;">Máximo:</span>
                <span style="color: #ef4444; font-weight: 600; margin-left: 5px;">${formatarMoeda(produto.preco_maximo)}</span>
            </div>
        </div>
    `;

    // Renderizar histórico de preços
    let historicoHTML = '<div style="display: flex; flex-direction: column; gap: 12px;">';

    produto.historico_precos.forEach((registro, idx) => {
        historicoHTML += `
            <div style="background: #f9fafb; padding: 15px; border-radius: 6px; border-left: 4px solid #3b82f6;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                            Orçamento: ${registro.orcamento_numero}
                        </div>
                        <div style="font-size: 12px; color: #6b7280;">
                            ${registro.orgao} - ${registro.uf}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 18px; font-weight: 700; color: #10b981;">
                            ${formatarMoeda(registro.preco_unitario)}
                        </div>
                        <div style="font-size: 11px; color: #6b7280;">
                            Qtd: ${registro.quantidade}
                        </div>
                    </div>
                </div>
                <div style="font-size: 11px; color: #9ca3af;">
                    <i class="far fa-calendar"></i> ${formatarData(registro.data_conclusao)}
                </div>
            </div>
        `;
    });

    historicoHTML += '</div>';
    document.getElementById('modal-historico-content').innerHTML = historicoHTML;

    // Mostrar modal
    document.getElementById('modal-historico').style.display = 'block';
}

function fecharModalHistorico() {
    document.getElementById('modal-historico').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('modal-historico').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalHistorico();
    }
});

function renderizarPaginacaoLocal(atual, total) {
    const container = document.getElementById('pagination-local');
    container.innerHTML = '';

    if (total <= 1) return;

    // Botão anterior
    const btnPrev = document.createElement('button');
    btnPrev.innerHTML = '<i class="fas fa-chevron-left"></i>';
    btnPrev.disabled = atual === 1;
    btnPrev.style.cssText = `padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; ${atual === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''}`;
    btnPrev.onclick = () => {
        if (atual > 1) {
            paginaAtualLocal = atual - 1;
            carregarProdutosLocais();
        }
    };
    container.appendChild(btnPrev);

    // Números de página
    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= atual - 2 && i <= atual + 2)) {
            const btnPage = document.createElement('button');
            btnPage.textContent = i;
            btnPage.style.cssText = `padding: 8px 12px; border: 1px solid ${i === atual ? '#3b82f6' : '#d1d5db'}; border-radius: 6px; background: ${i === atual ? '#3b82f6' : 'white'}; color: ${i === atual ? 'white' : '#374151'}; font-weight: ${i === atual ? '600' : '400'}; cursor: pointer; min-width: 40px;`;
            btnPage.onclick = () => {
                paginaAtualLocal = i;
                carregarProdutosLocais();
            };
            container.appendChild(btnPage);
        } else if (i === atual - 3 || i === atual + 3) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.style.padding = '8px 4px';
            container.appendChild(ellipsis);
        }
    }

    // Botão próximo
    const btnNext = document.createElement('button');
    btnNext.innerHTML = '<i class="fas fa-chevron-right"></i>';
    btnNext.disabled = atual === total;
    btnNext.style.cssText = `padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; ${atual === total ? 'opacity: 0.5; cursor: not-allowed;' : ''}`;
    btnNext.onclick = () => {
        if (atual < total) {
            paginaAtualLocal = atual + 1;
            carregarProdutosLocais();
        }
    };
    container.appendChild(btnNext);
}

// ===========================
// EVENT LISTENERS
// ===========================
document.getElementById('btn-buscar-local').addEventListener('click', () => {
    paginaAtualLocal = 1;
    carregarProdutosLocais();
});

document.getElementById('btn-limpar-local').addEventListener('click', () => {
    document.getElementById('termo_busca_local').value = '';
    document.getElementById('filtro_uf_local').value = '';
    document.getElementById('filtro_periodo_local').value = '';
    paginaAtualLocal = 1;
    carregarProdutosLocais();
});

// Enter para buscar
document.getElementById('termo_busca_local').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') document.getElementById('btn-buscar-local').click();
});

// ===========================
// FUNÇÕES AUXILIARES
// ===========================
function formatarMoeda(valor) {
    if (!valor) return 'R$ 0,00';
    return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatarData(data) {
    if (!data) return 'N/A';
    const date = new Date(data);
    return date.toLocaleDateString('pt-BR');
}

// Carregar produtos locais ao iniciar
document.addEventListener('DOMContentLoaded', () => {
    carregarProdutosLocais();
});
</script>
@endsection
