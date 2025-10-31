<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cesta de Preços')</title>

    <!-- Base path para requisições via proxy -->
    <script>
        window.APP_BASE_PATH = '/module-proxy/price_basket';
    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-size: 13px;
            overflow-x: hidden;
        }

        /* Override Bootstrap container */
        body > .container {
            display: flex;
            min-height: 100vh;
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Sidebar */
        .sidebar {
            width: 180px;
            background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
            color: white;
            flex-shrink: 0;
            min-height: 100vh;
        }

        .sidebar-section {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-title {
            padding: 10px 12px;
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 0;
            padding: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: white;
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.9);
            color: #2c5282;
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.9);
            color: #2c5282;
            font-weight: 600;
        }

        .sidebar-menu .icon {
            width: 20px;
            margin-right: 10px;
            display: inline-block;
            text-align: center;
            font-style: normal;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .header-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header-link:hover {
            color: #3b82c4;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
        }

        /* Welcome Section */
        .welcome-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .welcome-title {
            font-size: 25px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .welcome-subtitle {
            font-size: 14px;
            color: #9ca3af;
            font-weight: 400;
        }

        /* Action Button */
        .btn-primary {
            background: linear-gradient(135deg, #3b82c4 0%, #2563eb 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Table Section */
        .table-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            letter-spacing: 0.3px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background-color: #f9fafb;
        }

        .data-table th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
            letter-spacing: 0.3px;
        }

        .data-table td {
            padding: 10px 12px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .data-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .data-table .link {
            color: #3b82c4;
            text-decoration: none;
        }

        .data-table .link:hover {
            text-decoration: underline;
        }

        .data-table .status-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 5px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .empty-state-subtext {
            font-size: 14px;
            color: #9ca3af;
        }

        /* Media query para telas com altura menor (como 1440x900) */
        @media (max-height: 1000px) {
            .content {
                padding: 20px;
            }

            .header {
                padding: 10px 20px;
            }

            .welcome-section {
                padding: 20px;
                margin-bottom: 20px;
            }

            .sidebar-section {
                padding: 10px 0;
            }

            .sidebar-title {
                padding: 8px 12px;
                font-size: 10px;
            }

            .sidebar-menu a {
                padding: 8px 12px;
                font-size: 10px;
            }
        }

    </style>

    <script>
        // Garantir que navegação funciona dentro do iframe
        document.addEventListener('DOMContentLoaded', function() {
            // Interceptar cliques em links para forçar navegação correta no contexto do proxy
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');

                if (link && link.getAttribute('href')) {
                    const href = link.getAttribute('href');

                    // Se é um link relativo (sem / inicial e sem http)
                    if (!href.startsWith('#') && !href.startsWith('http')) {
                        e.preventDefault();

                        // Construir URL correta com base no proxy
                        const base = '/module-proxy/price_basket/';
                        let targetPath = href;

                        // Remover / inicial se existir
                        if (targetPath.startsWith('/')) {
                            targetPath = targetPath.substring(1);
                        }

                        const fullPath = base + targetPath;

                        // Navegar dentro do iframe
                        window.location.href = fullPath;
                    }
                }
            }, true);
        });
    </script>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">ORÇAMENTAÇÃO</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="/dashboard" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="icon fas fa-home"></i>
                            PAINEL
                        </a>
                    </li>
                    <li>
                        <a href="/orcamentos/novo" class="{{ request()->routeIs('orcamentos.create') ? 'active' : '' }}">
                            <i class="icon fas fa-file-alt"></i>
                            NOVO ORÇAMENTO
                        </a>
                    </li>
                    <li>
                        <a href="/orcamentos/pendentes" class="{{ request()->routeIs('orcamentos.pendentes') ? 'active' : '' }}">
                            <i class="icon fas fa-clipboard-list"></i>
                            PENDENTES
                        </a>
                    </li>
                    <li>
                        <a href="/orcamentos/realizados" class="{{ request()->routeIs('orcamentos.realizados', 'orcamentos.concluidos') ? 'active' : '' }}">
                            <i class="icon fas fa-check-circle"></i>
                            REALIZADOS
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-title">OUTRAS PESQUISAS</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="pesquisa-rapida" class="{{ request()->routeIs('pesquisa.rapida') ? 'active' : '' }}">
                            <i class="icon fas fa-search"></i>
                            PESQUISA RÁPIDA
                        </a>
                    </li>
                    <li>
                        <a href="mapa-de-atas" class="{{ request()->routeIs('mapa.atas') ? 'active' : '' }}">
                            <i class="icon fas fa-map"></i>
                            MAPA DE ATAS
                        </a>
                    </li>
                    <li>
                        <a href="mapa-de-fornecedores" class="{{ request()->routeIs('mapa.fornecedores') ? 'active' : '' }}">
                            <i class="icon fas fa-boxes"></i>
                            MAPA FORNECEDORES
                        </a>
                    </li>
                    <li>
                        <a href="catalogo" class="{{ request()->routeIs('catalogo') ? 'active' : '' }}">
                            <i class="icon fas fa-boxes"></i>
                            CATÁLOGO DE PRODUTOS
                        </a>
                    </li>
                    <li>
                        <a href="orientacoes-tecnicas" class="{{ request()->routeIs('orientacoes.index') ? 'active' : '' }}">
                            <i class="icon fas fa-comments"></i>
                            ORIENTAÇÕES TÉC.
                        </a>
                    </li>
                    <li>
                        <a href="/cdfs-enviadas" class="{{ request()->routeIs('cdfs.enviadas') ? 'active' : '' }}">
                            <i class="icon fas fa-paper-plane"></i>
                            CDFS ENVIADAS
                        </a>
                    </li>
                    <li>
                        <a href="fornecedores" class="{{ request()->routeIs('fornecedores.index') ? 'active' : '' }}">
                            <i class="icon fas fa-building"></i>
                            FORNECEDORES
                        </a>
                    </li>
                    <li>
                        <a href="/cotacao-externa" class="{{ request()->routeIs('cotacao-externa.index') ? 'active' : '' }}">
                            <i class="icon fas fa-file-invoice"></i>
                            COTAÇÃO
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-title">ADMINISTRAÇÃO</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="/configuracoes" class="{{ request()->routeIs('configuracoes.index') ? 'active' : '' }}">
                            <i class="icon fas fa-cog"></i>
                            CONFIGURAÇÕES
                        </a>
                    </li>
                </ul>
            </div>

        </aside>

        <main class="main-content">
        <header class="header">
            <h1 class="header-title">Painel de Bordo</h1>
            <div class="header-actions">
                <span class="header-link">
                    <i class="fas fa-building"></i>
                    {{ strtoupper(request()->attributes->get('tenant')['name'] ?? 'ORGÃO') }}
                </span>
                <span class="header-link">
                    <i class="fas fa-user"></i>
                    {{ strtoupper(request()->attributes->get('user')['name'] ?? 'USUÁRIO') }}
                </span>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </main>

    <!-- Bootstrap JavaScript Bundle (inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Sistema de Logs Detalhado - DESABILITADO para evitar overhead de requisições --}}
    {{-- <script src="{{ asset('js/sistema-logs.js') }}"></script> --}}
</body>
</html>