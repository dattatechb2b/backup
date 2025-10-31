# DIAGRAMA VISUAL: ARQUITETURA MULTITENANT DO SISTEMA

## 1. VISÃO GERAL - CAMADAS DA ARQUITETURA

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          CAMADA 1: NAVEGADOR                            │
│ Usuário em: curitiba.sistemacompras.gov.br/modulo-proxy/cestadeprecos   │
│ Cookies: session_id=abc123 (domínio: .sistemacompras.gov.br)            │
└────────────────────────────┬────────────────────────────────────────────┘
                             │
                             │ HTTP Request
                             │ /modulo-proxy/cestadeprecos/orcamentos/123
                             ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    CAMADA 2: SISTEMA CENTRAL                             │
│              MinhaDataTech (Laravel) - Porta 80                         │
│                                                                         │
│ Routes: /modulo-proxy/{module}/{path}                                  │
│  └─ ModuleProxyController::proxy()                                     │
│     ├─ Verifica Auth (user está logado?) ✓                            │
│     ├─ Detecta tenant via domínio: curitiba → tenant_id=5             │
│     ├─ Busca DB config do tenant:                                      │
│     │  └─ database: prefeitura_curitiba_db                             │
│     │  └─ username: tenant_user                                        │
│     │  └─ password: encrypted_pwd                                      │
│     ├─ Monta headers X-*:                                              │
│     │  ├─ X-Tenant-Id: 5                                              │
│     │  ├─ X-DB-Name: prefeitura_curitiba_db                           │
│     │  ├─ X-DB-User: tenant_user                                      │
│     │  ├─ X-DB-Password: senha_decriptada                             │
│     │  ├─ X-User-Id: 42                                               │
│     │  ├─ X-User-Email: joao@prefeitura.gov.br                        │
│     │  └─ Cookie: session_id=abc123                                   │
│     └─ HTTP Request para módulo                                       │
└────────────────────────────┬────────────────────────────────────────────┘
                             │
        ┌────────────────────┴────────────────────┬──────────────────┐
        │ HTTP Proxy                              │                  │
        │ GET http://localhost:8001/...           │                  │
        │ Headers X-* + Cookie                    │                  │
        ▼                                         ▼                  ▼
┌──────────────────────┐ ┌───────────────────┐ ┌──────────────────┐
│ MÓDULO CESTADEPRECOS │ │   MÓDULO NFE      │ │   MÓDULO CRM     │
│ (Porta 8001)         │ │   (Porta 8002)    │ │ (Porta 8003)     │
└──────────────────────┘ └───────────────────┘ └──────────────────┘
        │
        │ ProxyAuth Middleware
        │ ├─ Recebe headers X-*
        │ ├─ Valida cross-tenant
        │ ├─ configureDynamicDB()
        │ └─ Passa para controller
        │
        ▼
┌────────────────────────────────────────────────────────────────────────┐
│         OrcamentoController::index()                                   │
│         ├─ $orcamentos = Orcamento::all()                             │
│         │  ├─ Query: SELECT * FROM cp_orcamentos WHERE ...            │
│         │  └─ Conexão: pgsql (configurado dinamicamente!)            │
│         │     └─ DB: prefeitura_curitiba_db ✓                        │
│         │                                                              │
│         ├─ foreach ($orcamentos as $o) {                             │
│         │    $catmat = DB::connection('pgsql_main')                 │
│         │              ->table('cp_catmat')                          │
│         │              ->where('codigo', $o->catmat_codigo)         │
│         │              ->first();                                    │
│         │    // Query: SELECT * FROM cp_catmat                       │
│         │    // Conexão: pgsql_main (FIXO!)                         │
│         │    // DB: minhadattatech_db ✓                             │
│         │  }                                                           │
│         │                                                              │
│         └─ return view('orcamentos.index', compact('orcamentos'))    │
└────────────────────────────┬───────────────────────────────────────────┘
                             │
        ┌────────────────────┴────────────────────────────┐
        │                                                │
        ▼                                                ▼
┌──────────────────────────────────────┐  ┌───────────────────────────┐
│ BANCO TENANT: prefeitura_curitiba_db │  │ BANCO PRINCIPAL (Fixo):   │
│ PostgreSQL                           │  │ minhadattatech_db         │
│                                      │  │ PostgreSQL                │
│ Tabelas:                             │  │                          │
│ ├─ cp_orcamentos (50 registros)    │  │ Tabelas Compartilhadas:   │
│ │  ├─ id: 1-123                    │  │ ├─ cp_catmat              │
│ │  ├─ numero: 001/2025             │  │ │  └─ codigo: 123456      │
│ │  ├─ nome: Arroz 5kg              │  │ │  └─ titulo: Arroz...    │
│ │  ├─ catmat_codigo: 123456        │  │ │  └─ (38k+ materiais)    │
│ │  └─ status: pendente/realizado   │  │ │                          │
│ │                                   │  │ ├─ cp_precos_comprasgov   │
│ ├─ cp_fornecedores (120)           │  │ │  └─ (últimos 12 meses)  │
│ ├─ cp_lotes (500)                  │  │ │                          │
│ ├─ cp_usuarios (45)                │  │ ├─ cp_medicamentos_cmed   │
│ └─ ... (50+ tabelas cp_*)          │  │ └─ ... (dados globais)    │
│                                      │  │                          │
└──────────────────────────────────────┘  └───────────────────────────┘

        Dados ISOLADOS por tenant          Dados COMPARTILHADOS
        - Orcamentos: 50 (Curitiba)        entre todos os tenants
        - Fornecedores: 120                - CATMAT: 38.000 materiais
        - Usuários: 45                     - Preços: sincronizados 1x
                                           - CMED: base pública
```

---

## 2. FLUXO DETALHADO: COMO UM ORÇAMENTO É PROCESSADO

```
PASSO 1: Usuário clica em "Novo Orçamento" na interface web
┌────────────────────────────────────────────────────────┐
│ Browser: GET /modulo-proxy/cestadeprecos/orcamentos    │
│ Cookies: _session=abc123; XSRF-TOKEN=xyz               │
│ Header: User-Agent: Mozilla/5.0...                     │
└────────────────────┬─────────────────────────────────┘
                     │
                     ▼
PASSO 2: MinhaDataTech recebe a requisição
┌──────────────────────────────────────────────────────┐
│ ModuleProxyController::proxy('cestadeprecos', '...')  │
│                                                       │
│ // Routa de proxy foi acionada                       │
│ // Agora precisa saber qual tenant é                 │
│                                                       │
│ $host = 'curitiba.sistemacompras.gov.br'            │
│ $subdomain = explode('.', $host)[0] = 'curitiba'     │
│ $tenant = Tenant::where('subdomain', 'curitiba')     │
│ $tenant->id = 5                                      │
│                                                       │
│ // Autenticação OK?                                  │
│ Auth::check() = true ✓                               │
│ Auth::user()->id = 42                                │
│ Auth::user()->email = 'joao@prefeitura.gov.br'       │
│                                                       │
│ // Tem acesso ao módulo?                            │
│ $tenant->activeModules()                             │
│  ->where('module_key', 'cestadeprecos')              │
│  ->where('enabled', true) = true ✓                   │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
PASSO 3: Montar headers e fazer requisição ao módulo
┌──────────────────────────────────────────────────────┐
│ // Obter credenciais do BD do tenant                 │
│ $dbConfig = $tenant->getDatabaseConfig()             │
│ $dbConfig = [                                        │
│     'host' => '127.0.0.1',                           │
│     'port' => 5432,                                  │
│     'database' => 'prefeitura_curitiba_db',         │
│     'username' => 'tenant_user',                     │
│     'password' => 'senha_decriptada',               │
│ ]                                                    │
│                                                       │
│ // Montar headers X-*                               │
│ $headers = [                                         │
│     'X-Tenant-Id' => '5',                            │
│     'X-Tenant-Subdomain' => 'curitiba',              │
│     'X-Tenant-Name' => 'Prefeitura de Curitiba',     │
│     'X-User-Id' => '42',                             │
│     'X-User-Email' => 'joao@prefeitura.gov.br',      │
│     'X-User-Name' => 'João Silva',                   │
│     'X-User-Role' => 'orcamentista',                 │
│     'X-DB-Name' => 'prefeitura_curitiba_db',        │
│     'X-DB-Host' => '127.0.0.1',                      │
│     'X-DB-User' => 'tenant_user',                    │
│     'X-DB-Password' => 'senha_decriptada',          │
│     'X-DB-Prefix' => 'cp_',                          │
│     'Cookie' => '_session=abc123; ...',              │
│ ]                                                    │
│                                                       │
│ // Fazer HTTP request ao módulo                     │
│ Http::withHeaders($headers)                          │
│    ->get('http://localhost:8001/orcamentos')         │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ HTTP GET com headers X-*
                   ▼
PASSO 4: Módulo recebe request - ProxyAuth Middleware
┌──────────────────────────────────────────────────────┐
│ // Middleware ProxyAuth::handle()                    │
│                                                       │
│ // VALIDAÇÃO 1: É rota pública?                      │
│ if (str_starts_with($path, '/responder-cdf')) {      │
│     // Sim, rota pública - pode continuar           │
│ } else {                                             │
│     // Não, rota privada                            │
│     // Precisa validar cross-tenant                 │
│ }                                                    │
│                                                       │
│ // VALIDAÇÃO 2: Sessão anterior ou headers?         │
│ $tenantData = session('proxy_tenant');              │
│ // Primeira requisição? session vazio               │
│                                                       │
│ // AUTENTICAÇÃO: Via headers do proxy               │
│ $tenantId = $request->header('X-Tenant-Id') = '5'   │
│ $userId = $request->header('X-User-Id') = '42'      │
│ $userEmail = $request->header('X-User-Email') = ... │
│                                                       │
│ // SALVAR na sessão (para próximas requisições)     │
│ session([                                            │
│     'proxy_tenant' => [                              │
│         'id' => 5,                                   │
│         'subdomain' => 'curitiba',                   │
│         'name' => 'Prefeitura de Curitiba'           │
│     ],                                               │
│     'proxy_user_data' => [                           │
│         'id' => 42,                                  │
│         'email' => 'joao@...',                       │
│         'role' => 'orcamentista'                     │
│     ],                                               │
│     'proxy_db_config' => [                           │
│         'database' => 'prefeitura_curitiba_db',     │
│         'username' => 'tenant_user',                 │
│         'password' => 'senha_decriptada'             │
│     ]                                                │
│ ]);                                                  │
│                                                       │
│ // CONFIGURAR BANCO DINAMICAMENTE                   │
│ config(['database.connections.pgsql' => [           │
│     'driver' => 'pgsql',                             │
│     'host' => '127.0.0.1',                           │
│     'port' => 5432,                                  │
│     'database' => 'prefeitura_curitiba_db', ← KEY!   │
│     'username' => 'tenant_user',                     │
│     'password' => 'senha_decriptada',               │
│ ]]);                                                 │
│                                                       │
│ DB::purge('pgsql');   // Limpar conexão antiga      │
│ DB::reconnect('pgsql'); // Conectar novo DB         │
│                                                       │
│ // USUÁRIO LOCAL                                     │
│ $user = User::firstOrCreate(                         │
│     ['email' => 'joao@prefeitura.gov.br'],           │
│     ['name' => 'João Silva', 'password' => ...]      │
│ );                                                   │
│ Auth::setUser($user);                                │
│                                                       │
│ // ✓ Request pronta para controller                 │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
PASSO 5: OrcamentoController::index()
┌──────────────────────────────────────────────────────┐
│ public function index(Request $request)              │
│ {                                                     │
│     // Buscar orçamentos                            │
│     $orcamentos = Orcamento::with('itens')          │
│                           ->orderBy('created_at')    │
│                           ->get();                   │
│                                                       │
│     // Isso executa:                                │
│     // SELECT * FROM cp_orcamentos                   │
│     //            WHERE ...                          │
│     //        ORDER BY created_at DESC               │
│     //        USING DATABASE: prefeitura_curitiba_db │
│     //                       ← Porque pgsql foi config │
│                                                       │
│     // Resultado: 50 orçamentos (apenas de Curitiba) │
│                                                       │
│     // Voltar para view                             │
│     return view('orcamentos.index', [                │
│         'orcamentos' => $orcamentos,                 │
│         'fornecedores' => ... // from BD             │
│     ]);                                              │
│ }                                                     │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
PASSO 6: View renderiza lista de orçamentos
┌──────────────────────────────────────────────────────┐
│ <table>                                               │
│   <tr>                                               │
│     <td>001/2025</td>                                │
│     <td>Arroz integral 5kg</td>                      │
│     <td>150.00</td>                                  │
│     <td>Pendente</td>                                │
│   </tr>                                              │
│   <tr>                                               │
│     <td>002/2025</td>                                │
│     <td>Feijão carioca 1kg</td>                      │
│     <td>45.00</td>                                   │
│     <td>Realizado</td>                               │
│   </tr>                                              │
│   ... (mais 48 orçamentos)                           │
│ </table>                                             │
│                                                       │
│ <!-- Dados são de: prefeitura_curitiba_db -->       │
│ <!-- São Paulo NÃO vê estes orçamentos -->          │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ HTML Response
                   ▼
PASSO 7: MinhaDataTech (Proxy) recebe HTML
┌──────────────────────────────────────────────────────┐
│ // Processar resposta do módulo                      │
│ $response = Http::get(...);                          │
│ $html = $response->body();                           │
│                                                       │
│ // Injetar <base> tag para resolver URLs             │
│ $html = str_replace(                                 │
│     '<head>',                                        │
│     '<head>\n<base href="/module-proxy/cestadeprecos/">',│
│     $html                                            │
│ );                                                   │
│                                                       │
│ // Transformar URLs absoluta em relativa             │
│ $html = preg_replace(                                │
│     '/(href|action)="\/([^"]+)"/',                   │
│     '$1="$2"',                                       │
│     $html                                            │
│ );                                                   │
│ // href="/orcamentos/123" → href="orcamentos/123"    │
│                                                       │
│ // Retornar HTML para browser                       │
│ return response($html);                              │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ HTTP Response: HTML renderizado
                   ▼
PASSO 8: Browser exibe página
┌──────────────────────────────────────────────────────┐
│ Página carregada em: curitiba.sistemacompras.gov.br  │
│ Mostra 50 orçamentos de Curitiba                     │
│ URLs relativas funcionam via <base href>            │
│                                                       │
│ Quando usuário clica em um orçamento:                │
│ Link: href="orcamentos/123/editar"                   │
│ URL Real: /module-proxy/cestadeprecos/orcamentos/123/editar
│ ← Volta ao proxy, que passa para módulo              │
│ ← ProxyAuth valida X-Tenant-Id novamente             │
│ ← Se tenant mudar, bloqueio automático!              │
└──────────────────────────────────────────────────────┘
```

---

## 3. FLUXO DE SEGURANÇA: BLOQUEIO DE CROSS-TENANT

```
CENÁRIO: User A (Curitiba, tenant_id=5) tenta acessar São Paulo (tenant_id=6)

PASSO 1: User A tem sessão de Curitiba
┌──────────────────────────────────────────┐
│ session('proxy_tenant.id') = 5            │
│ session('proxy_db_config.database')       │
│  = 'prefeitura_curitiba_db'              │
└──────────────────────────────────────────┘

PASSO 2: User A tenta acessar São Paulo
┌──────────────────────────────────────────┐
│ Browser: GET /module-proxy/cestadeprecos/ │
│                        orcamentos        │
│                                          │
│ Host: saopaulo.sistemacompras.gov.br    │
│ (Host mudou! Novo tenant?)               │
└──────────────────────────────────────────┘

PASSO 3: MinhaDataTech detecta novo tenant
┌──────────────────────────────────────────┐
│ ModuleProxyController::proxy()            │
│                                          │
│ $subdomain = 'saopaulo'                  │
│ $tenant = Tenant::where(...)             │
│ $tenant->id = 6  ← NOVO TENANT!          │
│                                          │
│ Monta headers:                            │
│ X-Tenant-Id: 6  ← DIFERENTE!             │
│ X-DB-Name: prefeitura_saopaulo_db       │
│ X-DB-User: tenant_user                   │
│ ...                                       │
│                                          │
│ HTTP Request com headers X-Tenant-Id: 6  │
└──────────────────────────────────────────┘

PASSO 4: Módulo recebe request - ProxyAuth
┌──────────────────────────────────────────┐
│ ProxyAuth::handle()                       │
│                                          │
│ // Recebe header                         │
│ $currentTenantId = $request->header(     │
│     'X-Tenant-Id'                        │
│ ) = 6                                    │
│                                          │
│ // Verifica sessão anterior              │
│ $sessionTenantId = session(               │
│     'proxy_tenant.id'                    │
│ ) = 5                                    │
│                                          │
│ // VALIDAÇÃO CRÍTICA                    │
│ if (5 != 6) {                            │
│     // ❌ MISMATCH DETECTADO!            │
│     Log::critical(                        │
│         'Cross-tenant access BLOCKED!',   │
│         [                                 │
│           'session_tenant' => 5,          │
│           'current_tenant' => 6,          │
│           'user' => 'joao@...',          │
│           'ip' => '192.168.1.100',       │
│         ]                                │
│     );                                   │
│                                          │
│     // Limpar sessão                    │
│     session()->forget([                  │
│         'proxy_tenant',                  │
│         'proxy_user_data',               │
│         'proxy_db_config'                │
│     ]);                                  │
│                                          │
│     // User não autenticado              │
│     // Será redirecionado para login     │
│ }                                         │
└──────────────────────────────────────────┘

PASSO 5: User A é bloqueado
┌──────────────────────────────────────────┐
│ ProxyAuth detecta requisição sem contexto │
│ (sessão foi limpa)                       │
│                                          │
│ Headers X-Tenant-Id: 6 (São Paulo)      │
│ Session: vazio (foi limpa)               │
│                                          │
│ User não pode ser autenticado            │
│ Redirecionado para /login                │
│                                          │
│ Resultado:                               │
│ ❌ User A NÃO consegue acessar dados    │
│    de São Paulo                          │
│ ✓ Sistema está seguro!                   │
└──────────────────────────────────────────┘

LOGS REGISTRADOS:
┌──────────────────────────────────────────┐
│ [2025-10-30 14:32:15] local.CRITICAL:   │
│ Cross-tenant access attempt BLOCKED! {   │
│   "session_tenant_id": 5,                │
│   "session_tenant_subdomain": "curitiba",│
│   "session_tenant_db": "prefeitura_...", │
│   "current_tenant_id": 6,                │
│   "current_tenant_subdomain": "saopaulo",│
│   "current_tenant_db": "prefeitura_...", │
│   "user_email": "joao@prefeitura.gov.br",│
│   "uri": "/modulo-proxy/cestadeprecos/...",
│   "method": "GET",                       │
│   "ip": "192.168.1.100"                 │
│ }                                         │
└──────────────────────────────────────────┘
```

---

## 4. ESTRUTURA DE BANCOS - ISOLAMENTO FÍSICO

```
SERVIDOR PostgreSQL 127.0.0.1:5432

┌────────────────────────────────────────────────────────────────┐
│ minhadattatech_db (Banco Principal - Compartilhado)            │
│                                                                │
│ USUÁRIOS:                                                      │
│ ├─ minhadattatech_user (proprietário, acesso total)          │
│ ├─ tenant_user (READ-ONLY em cp_*)                            │
│ │  └─ Grant: SELECT em cp_catmat, cp_precos_comprasgov, ...  │
│ └─ technical_user (administrativo, acesso completo)           │
│                                                                │
│ TABELAS (com prefixo cp_):                                     │
│ ├─ cp_catmat (38.000+ materiais)                              │
│ │  ├─ Columns: codigo, titulo, tipo, caminho_hierarquia, ... │
│ │  ├─ Índices: idx_catmat_codigo, idx_catmat_titulo_fulltext │
│ │  └─ Acesso: ✓ Curitiba, ✓ São Paulo, ✓ Brasília, ...      │
│ │             Todos veem mesmos materiais                      │
│ │                                                              │
│ ├─ cp_precos_comprasgov (últimos 12 meses)                    │
│ │  ├─ Columns: catmat_codigo, preco_unitario, fornecedor, ... │
│ │  ├─ Índices: idx_catmat_codigo, idx_data_compra             │
│ │  └─ Acesso: ✓ Curitiba, ✓ São Paulo, ...                   │
│ │             Preços atualizados 1x ao dia para todos          │
│ │                                                              │
│ ├─ cp_medicamentos_cmed (CMED pública)                        │
│ │  └─ Acesso: Read-only para todos os tenants                 │
│ │                                                              │
│ └─ ... (outras tabelas compartilhadas)                        │
│                                                                │
│ Sincronização: Atualizada via Job (BaixarPrecosComprasGov)    │
│                Executa 1x, beneficia 100+ tenants              │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ prefeitura_curitiba_db (Banco Tenant #1)                      │
│                                                                │
│ USUÁRIOS:                                                      │
│ ├─ tenant_user (acesso TOTAL - é o proprietário)              │
│ └─ reader_curitiba (READ-ONLY, para reports)                 │
│                                                                │
│ TABELAS (com prefixo cp_):                                     │
│ ├─ cp_orcamentos (50 orçamentos)                              │
│ ├─ cp_itens_orcamento (250 itens)                             │
│ ├─ cp_fornecedores (120 fornecedores locais)                  │
│ ├─ cp_usuarios (45 usuários de Curitiba)                      │
│ ├─ cp_lotes (500 lotes)                                       │
│ ├─ cp_solicitacoes_cdf (80 CDFs)                              │
│ └─ ... (50+ tabelas, TODAS com prefixo cp_)                   │
│                                                                │
│ Acesso:  ✓ Apenas Curitiba  ✗ Outros tenants                 │
│ Dados:   Completamente isolados                               │
│ Backups: Independente                                         │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ prefeitura_saopaulo_db (Banco Tenant #2)                      │
│                                                                │
│ USUÁRIOS:                                                      │
│ ├─ tenant_user (acesso TOTAL)                                 │
│ └─ reader_saopaulo (READ-ONLY)                                │
│                                                                │
│ TABELAS (com prefixo cp_):                                     │
│ ├─ cp_orcamentos (80 orçamentos)                              │
│ ├─ cp_itens_orcamento (400 itens)                             │
│ ├─ cp_fornecedores (200 fornecedores - maior que Curitiba!)  │
│ ├─ cp_usuarios (100 usuários - maior que Curitiba!)          │
│ ├─ cp_lotes (800 lotes)                                       │
│ ├─ cp_solicitacoes_cdf (120 CDFs)                             │
│ └─ ... (mesma estrutura que Curitiba, dados diferentes)       │
│                                                                │
│ Acesso:  ✓ Apenas São Paulo  ✗ Outros tenants                │
│ Dados:   Completamente isolados                               │
│ Backups: Independente                                         │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ prefeitura_brasilialb_db (Banco Tenant #N)                    │
│ ... (padrão repetido)                                         │
└────────────────────────────────────────────────────────────────┘

ISOLAMENTO GARANTIDO POR:
1. Banco separado por tenant (diferente database)
2. Usuário do banco é tenant_user (mesma credencial)
3. PostgreSQL obriga acesso apenas ao banco específico
4. Conexão é reconfigurada a cada request (ProxyAuth)
5. Cross-tenant validação no middleware
```

---

## 5. MAPA DE CONEXÕES ENTRE APLICAÇÕES

```
┌─────────────────────────────────────────────────────────────────┐
│                    USUÁRIO NO NAVEGADOR                         │
│              curitiba.sistemacompras.gov.br                     │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ HTTP
                     │ Cookies: _session=abc; domain=.sistemacompras.gov.br
                     │ (Cookie vale para TODOS os subdomínios!)
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│         MINHADATTATECH (Portal Central)                          │
│         127.0.0.1:80 (Nginx proxy)                              │
│         App: Laravel                                             │
│         DB: minhadattatech_db (usuários globais, tenants)       │
│                                                                   │
│         Routes:                                                   │
│         GET  /login                    → AuthController          │
│         GET  /dashboard                → DashboardController     │
│         GET  /module-proxy/*           → ModuleProxyController ◄─┼─ AQUI!
│         POST /admin/tenants            → TenantController        │
│                                                                   │
│         Middleware:                                              │
│         ├─ DetectTenant (por domínio ou session)                │
│         └─ AuthorizeTenant (valida acesso)                      │
│                                                                   │
└──────────────────────────┬───────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┬─────────────────┐
        │                  │                  │                 │
        │ HTTP Proxy       │ HTTP Proxy       │ HTTP Proxy      │
        │ Localhost:8001   │ Localhost:8002   │ Localhost:8003  │
        │ Header X-*       │ Header X-*       │ Header X-*      │
        ▼                  ▼                  ▼                 │
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ │
│ CESTADEPRECOS    │ │      NFE         │ │      CRM         │ │
│ Porta 8001       │ │   Porta 8002     │ │   Porta 8003     │ │
│ Laravel          │ │   Laravel        │ │   Laravel        │ │
│                  │ │                  │ │                  │ │
│ Routes:          │ │ Routes:          │ │ Routes:          │ │
│ ├─ /orcamentos   │ │ ├─ /notas-fiscais│ │ ├─ /contatos    │ │
│ ├─ /fornecedores │ │ ├─ /emissoes     │ │ ├─ /empresas    │ │
│ ├─ /cotacao      │ │ └─ /consultas    │ │ └─ /vendas      │ │
│ └─ ...           │ │                  │ │                  │ │
│                  │ │ Middleware:      │ │ Middleware:      │ │
│ Middleware:      │ │ ├─ ProxyAuth     │ │ ├─ ProxyAuth     │ │
│ ├─ ProxyAuth ◄───┼─┤ ├─ DynamicSession│ │ ├─ DynamicSession│ │
│ ├─ DynamicSession│ │ └─ InternalOnly  │ │ └─ InternalOnly  │ │
│ └─ InternalOnly  │ │                  │ │                  │ │
│                  │ │ Config:          │ │ Config:          │ │
│ Config:          │ │ ├─ pgsql (tenant)│ │ ├─ pgsql (tenant)│ │
│ ├─ pgsql (tenant)│ │ ├─ pgsql_main    │ │ ├─ pgsql_main    │ │
│ ├─ pgsql_main    │ │ └─ pgsql_sessions│ │ └─ pgsql_sessions│ │
│ └─ pgsql_sessions│ │                  │ │                  │ │
│                  │ │ DB:              │ │ DB:              │ │
│ DB:              │ │ ├─ minhadattatech│ │ ├─ minhadattatech│ │
│ ├─ minhadattatech│ │ │  _db (pgsql_main)
│ │  _db (pgsql_main)│ │ └─ nfe_tenant_db │ │ └─ crm_tenant_db │ │
│ └─ prefeitura_   │ │   (pgsql - dinâm)│ │   (pgsql - dinâm)│ │
│    curitiba_db   │ │                  │ │                  │ │
│   (pgsql-dinâm)  │ │                  │ │                  │ │
│                  │ │                  │ │                  │ │
└──────────────────┘ └──────────────────┘ └──────────────────┘

        ↓                  ↓                  ↓
        └──────────────────┼──────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │   PostgreSQL Server 127.0.0.1:5432   │
        │                                      │
        ├─ minhadattatech_db (Principal)      │
        │  ├─ cp_catmat                       │
        │  ├─ cp_precos_comprasgov            │
        │  ├─ cp_medicamentos_cmed            │
        │  ├─ tenants (3 linhas)              │
        │  ├─ module_configurations (4 linhas)│
        │  └─ users (global)                  │
        │                                      │
        ├─ prefeitura_curitiba_db (Tenant 1)  │
        │  ├─ cp_orcamentos                   │
        │  ├─ cp_fornecedores                 │
        │  ├─ cp_usuarios (local)             │
        │  └─ ... (50+ tabelas cp_*)          │
        │                                      │
        ├─ prefeitura_saopaulo_db (Tenant 2)  │
        │  └─ ... (mesma estrutura)           │
        │                                      │
        └─ ... (mais tenants)
```

---

## 6. ESTADO DA SESSÃO AO LONGO DO TEMPO

```
REQUISIÇÃO 1: GET /modulo-proxy/cestadeprecos/orcamentos
┌────────────────────────────────────────────────────────────┐
│ BROWSER SESSION STATE (antes)                              │
├────────────────────────────────────────────────────────────┤
│ $_SESSION = [                                              │
│     '_token' => 'abc123...',                               │
│     'user_id' => 42,                                       │
│     'user_email' => 'joao@prefeitura.gov.br',              │
│     'current_tenant.id' => 5,                              │
│     'current_tenant.subdomain' => 'curitiba',              │
│     // Módulos não têm dados ainda!                        │
│ ]                                                          │
└────────────────────────────────────────────────────────────┘
                        │
                        │ HTTP
                        │ Headers X-Tenant-Id: 5, X-DB-Name: ..., X-User-Id: 42
                        ▼
┌────────────────────────────────────────────────────────────┐
│ MÓDULO SESSION STATE (durante requisição 1)                │
├────────────────────────────────────────────────────────────┤
│ $_SESSION = [                                              │
│     '_token' => 'abc123...',                               │
│     'user_id' => 42,                                       │
│     'user_email' => 'joao@prefeitura.gov.br',              │
│     'current_tenant.id' => 5,                              │
│     'current_tenant.subdomain' => 'curitiba',              │
│                                                            │
│     // ← ProxyAuth adiciona:                               │
│     'proxy_tenant' => [                                    │
│         'id' => 5,                                         │
│         'subdomain' => 'curitiba',                         │
│         'name' => 'Prefeitura de Curitiba'                 │
│     ],                                                     │
│     'proxy_user_data' => [                                 │
│         'id' => 42,                                        │
│         'name' => 'João Silva',                            │
│         'email' => 'joao@prefeitura.gov.br',               │
│         'role' => 'orcamentista'                           │
│     ],                                                     │
│     'proxy_db_config' => [                                 │
│         'database' => 'prefeitura_curitiba_db',            │
│         'host' => '127.0.0.1',                             │
│         'username' => 'tenant_user',                       │
│         'password' => 'senha_decriptada'                   │
│     ]                                                      │
│ ]                                                          │
│                                                            │
│ config['database.connections.pgsql'] = [                  │
│     'driver' => 'pgsql',                                   │
│     'database' => 'prefeitura_curitiba_db', ← DINÂMICO!    │
│     'username' => 'tenant_user',                           │
│     'password' => 'senha_decriptada',                      │
│     'host' => '127.0.0.1'                                  │
│ ]                                                          │
└────────────────────────────────────────────────────────────┘

REQUISIÇÃO 2: GET /modulo-proxy/cestadeprecos/orcamentos/123/editar
┌────────────────────────────────────────────────────────────┐
│ MÓDULO SESSION STATE (antes de Req 2)                      │
├────────────────────────────────────────────────────────────┤
│ $_SESSION = [ ... mesmo que antes ...]                     │
│ (ProxyAuth verificará se dados ainda são válidos)          │
└────────────────────────────────────────────────────────────┘
                        │
                        │ ProxyAuth::handle()
                        │ ├─ Recebe header X-Tenant-Id: 5
                        │ ├─ Verifica: session['proxy_tenant.id'] = 5
                        │ ├─ 5 == 5? ✓ SIM!
                        │ ├─ Restaura DB config da sessão
                        │ └─ Continua para controller
                        ▼
┌────────────────────────────────────────────────────────────┐
│ MÓDULO SESSION STATE (durante Req 2)                       │
├────────────────────────────────────────────────────────────┤
│ Nenhuma mudança (dados ainda válidos)                      │
│ DB config já aponta para prefeitura_curitiba_db            │
│ Controller executa sem problemas                           │
└────────────────────────────────────────────────────────────┘

REQUISIÇÃO 3: GET /modulo-proxy/cestadeprecos/orcamentos (novo tenant!)
┌────────────────────────────────────────────────────────────┐
│ MÓDULO SESSION STATE (antes de Req 3)                      │
├────────────────────────────────────────────────────────────┤
│ $_SESSION = [ 'proxy_tenant.id' => 5, ... ]                │
│ (Ainda aponta para Curitiba)                               │
└────────────────────────────────────────────────────────────┘
                        │
                        │ Domínio mudou para: saopaulo.sistemacompras.gov.br
                        │ ProxyAuth recebe: X-Tenant-Id: 6 (SÃO PAULO!)
                        ▼
┌────────────────────────────────────────────────────────────┐
│ VALIDAÇÃO CRÍTICA                                          │
├────────────────────────────────────────────────────────────┤
│ ProxyAuth::handle()                                        │
│ ├─ $currentTenantId = 6 (de X-Tenant-Id header)            │
│ ├─ $sessionTenantId = 5 (de session['proxy_tenant.id'])    │
│ ├─ 6 != 5? ❌ MISMATCH!                                    │
│ ├─ Log::critical("Cross-tenant access BLOCKED!")           │
│ ├─ session()->forget(['proxy_tenant', ...])                │
│ ├─ Redirect to /login                                      │
│ └─ User não consegue acessar                               │
│                                                            │
│ RESULTADO: Sessão foi limpa!                              │
└────────────────────────────────────────────────────────────┘

REQUISIÇÃO 4: GET /login (user reautenticado)
┌────────────────────────────────────────────────────────────┐
│ MÓDULO SESSION STATE (durante Req 4)                       │
├────────────────────────────────────────────────────────────┤
│ $_SESSION = [                                              │
│     // Dados globais do browser persistem:                 │
│     '_token' => 'abc123...',                               │
│     'user_id' => 42,                                       │
│     'user_email' => 'joao@prefeitura.gov.br',              │
│     'current_tenant.id' => 6,  ← MUDOU para São Paulo!     │
│     'current_tenant.subdomain' => 'saopaulo',              │
│                                                            │
│     // Dados do módulo antigos foram limpos:               │
│     // 'proxy_tenant' → REMOVIDO                           │
│     // 'proxy_user_data' → REMOVIDO                        │
│     // 'proxy_db_config' → REMOVIDO                        │
│ ]                                                          │
│                                                            │
│ ProxyAuth::handle() processa novamente:                    │
│ ├─ Recebe headers X-Tenant-Id: 6, X-DB-Name: saopaulo_db  │
│ ├─ Nenhum proxy_tenant na sessão (foi limpo)               │
│ ├─ Autentica via headers (novo contexto)                   │
│ ├─ Salva nova sessão:                                      │
│ │  'proxy_tenant.id' => 6                                  │
│ │  'proxy_db_config.database' => prefeitura_saopaulo_db    │
│ ├─ Configura DB: pgsql = prefeitura_saopaulo_db            │
│ └─ User autenticado novamente!                             │
│                                                            │
│ RESULTADO: User agora acessa dados de São Paulo            │
└────────────────────────────────────────────────────────────┘
```

---

## 7. CHECKLIST DE VALIDAÇÃO MULTITENANT

```
✓ BANCO DE DADOS
  ├─ [ ] Cada tenant tem database separado? SIM
  │      minhadattatech_db (principal)
  │      prefeitura_*_db (N tenants)
  ├─ [ ] Prefixo 'cp_' isola módulo? SIM
  │      cp_orcamentos, cp_fornecedores, ...
  ├─ [ ] Conexão 'pgsql' é dinâmica? SIM
  │      Reconfigurable via ProxyAuth headers
  └─ [ ] Conexão 'pgsql_main' é fixa? SIM
         Sempre minhadattatech_db

✓ MIDDLEWARE
  ├─ [ ] ProxyAuth autentica via headers X-*? SIM
  ├─ [ ] Cross-tenant access é bloqueado? SIM
  │      X-Tenant-Id validado vs. session
  ├─ [ ] Sessão persiste contexto? SIM
  │      proxy_tenant, proxy_user_data, proxy_db_config
  └─ [ ] DB é reconfigurado por request? SIM
         DB::purge() + DB::reconnect()

✓ MODELS
  ├─ [ ] 25 models usam 'pgsql' (tenant)? SIM
  │      Orcamento, Fornecedor, User, ...
  ├─ [ ] 5 models usam 'pgsql_main' (compartilhado)? SIM
  │      Catmat, PrecoComprasGov, MedicamentoCmed, ...
  └─ [ ] Relacionamentos cross-DB funcionam? SIM (com DB::connection)

✓ MIGRATIONS
  ├─ [ ] Migrations com pgsql_main explícitas? SIM
  │      Schema::connection('pgsql_main')->create(...)
  ├─ [ ] Migrations padrão sem especificar? SIM
  │      Schema::create(...) usa pgsql (tenant)
  ├─ [ ] Prefixo 'cp_' em todas as tabelas? SIM
  │      cp_orcamentos, cp_fornecedores, ...
  └─ [ ] Cada tenant roda migrations próprias? SIM
         ModuleInstaller::install()

✓ SEGURANÇA
  ├─ [ ] Headers X-DB-* vêm apenas do proxy? SIM
  │      Proxy autenticado no MinhaDataTech
  ├─ [ ] User não pode mudar X-Tenant-Id? SIM
  │      Headers refletem tenant da requisição
  ├─ [ ] Cross-tenant mudança é bloqueada? SIM
  │      ProxyAuth detecta mismatch
  ├─ [ ] Logs registram tentativas? SIM
  │      Log::critical com detalhes
  └─ [ ] Cada tenant tem backup isolado? SIM
         prefeitura_*_db têm backups independentes
```

---

**Diagrama criado**: 30/10/2025
**Versão da Arquitetura**: Multitenant Híbrido v2.0
**Status**: Produção
