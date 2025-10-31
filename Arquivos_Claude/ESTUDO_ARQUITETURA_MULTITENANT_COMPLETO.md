# ESTUDO COMPLETO E ESPECIALIZADO: ARQUITETURA MULTITENANT DO SISTEMA

## 1. VISÃO GERAL ESTRATÉGICA

Este é um sistema **MULTITENANT HÍBRIDO** onde:
- Cada prefeitura (tenant) possui seu **banco de dados independente**
- Dados compartilhados (CATMAT, CMED, Contratos) estão em banco **PRINCIPAL centralizado**
- **Módulos separados** (cestadeprecos, nfe, crm, etc) compartilham a mesma arquitetura
- **Sistema central** (minhadattatech) coordena acesso aos módulos via proxy

### Estrutura Física de Diretórios:
```
/home/dattapro/
├── minhadattatech/          # Sistema central - controla tenants e módulos
│   ├── app/Models/
│   │   ├── Tenant.php              # Modelo de tenant
│   │   ├── ModuleConfiguration.php # Registro de módulos
│   │   └── TenantActiveModule.php  # Módulos ativos por tenant
│   ├── app/Http/Controllers/
│   │   ├── ModuleProxyController.php   # Proxy inteligente para módulos
│   │   └── API/ModuleController.php    # Registro de módulos
│   └── app/Services/
│       └── ModuleInstaller.php     # Instalador de módulos para novos tenants
│
├── modulos/
│   ├── cestadeprecos/       # Módulo Cesta de Preços (isolado)
│   │   ├── config/
│   │   │   └── database.php         # Conexões 'pgsql' (tenant) + 'pgsql_main' (compartilhado)
│   │   ├── app/
│   │   │   ├── Http/Middleware/
│   │   │   │   ├── ProxyAuth.php                # Autentica via headers X-* do proxy
│   │   │   │   ├── DynamicSessionDomain.php    # Sessões dinâmicas por domain
│   │   │   │   └── TenantAuthMiddleware.php    # Validação de cross-tenant
│   │   │   ├── Models/ (37 models)
│   │   │   │   ├── Orcamento.php               # Tabela: cp_orcamentos (tenant-specific)
│   │   │   │   ├── Catmat.php                  # Conexão: pgsql_main (compartilhado!)
│   │   │   │   ├── Fornecedor.php              # Tabela: cp_fornecedores (tenant-specific)
│   │   │   │   └── ... (34 outros models)
│   │   │   └── Http/Controllers/ (múltiplos)
│   │   ├── database/migrations/     # ~50+ migrations com prefixo cp_
│   │   └── routes/web.php
│   │
│   ├── nfe/                 # Módulo Notas Fiscais (similar)
│   ├── crm/                 # Sistema CRM
│   └── technical/           # Painel de controle técnico
│
├── .env
└── database/
    └── migrations/          # Migrações do sistema central
```

---

## 2. CONFIGURAÇÃO DE BANCOS DE DADOS

### 2.1 Arquivo: `/modulos/cestadeprecos/config/database.php`

Define **3 conexões diferentes**:

```php
'connections' => [
    // Conexão PADRÃO (tenant-specific)
    // Cada tenant tem seu próprio DB, referência via headers X-DB-*
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'laravel'),  // Substituído dinamicamente!
        'username' => env('DB_USERNAME', 'root'),     // Substituído dinamicamente!
        'password' => env('DB_PASSWORD', ''),         // Substituído dinamicamente!
        'prefix' => '',  // Prefixo VAZIO - tabelas já têm cp_ explicit
    ],

    // Conexão PRINCIPAL (sempre minhadattatech_db)
    // Dados COMPARTILHADOS entre todos os tenants
    'pgsql_main' => [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'minhadattatech_db',  // FIXO!
        'username' => 'minhadattatech_user',
        'password' => 'MinhaDataTech2024SecureDB',
        'prefix' => '',  // Prefixo VAZIO - tabelas já têm cp_ explicit
    ],

    // Conexão para SESSÕES (opcional)
    'pgsql_sessions' => [
        // Similar à 'pgsql' (tenant-specific)
    ],
]
```

### 2.2 Padrão de Nomenclatura de Bancos

```
BANCO PRINCIPAL (compartilhado):
├── minhadattatech_db
│   ├── cp_catmat               # Catálogo de materiais (acesso compartilhado)
│   ├── cp_precos_comprasgov    # Preços da API Compras.gov (acesso compartilhado)
│   ├── cp_medicamentos_cmed    # Medicamentos do CMED (acesso compartilhado)
│   └── ... (dados globais)

BANCOS ESPECÍFICOS POR TENANT:
├── prefeitura_curitiba_db     # Tenant 1 - Prefeitura de Curitiba
│   ├── cp_orcamentos           # Tabelas com prefixo cp_
│   ├── cp_fornecedores
│   ├── cp_lotes
│   ├── cp_solicitacoes_cdf
│   └── ... (50+ tabelas cp_*)
│
├── prefeitura_saopaulo_db     # Tenant 2 - Prefeitura de São Paulo
│   ├── cp_orcamentos           # Mesma estrutura, dados diferentes
│   ├── cp_fornecedores
│   └── ... (dados isolados)
│
└── prefeitura_brasilialb_db   # Tenant N...
```

### 2.3 Switching Dinâmico Entre Tenants

**Mecanismo: Headers HTTP + Middleware ProxyAuth**

```
Fluxo da Requisição:
┌─────────────────────────────────────┐
│ MinhaDataTech (Portal Central)       │
│ - Usuário autenticado                │
│ - Conhece tenant do usuário          │
└────────────────┬────────────────────┘
                 │
                 │ HTTP Request
                 │ Headers: X-Tenant-Id, X-DB-Name, X-DB-User, X-DB-Password
                 ▼
┌─────────────────────────────────────┐
│ ModuleProxyController                │
│ (minhadattatech/Controllers)         │
│ - Valida autenticação                │
│ - Extrai dados do tenant             │
│ - Monta headers X-* com credenciais  │
└────────────────┬────────────────────┘
                 │
                 │ HTTP Proxy para módulo
                 │ Porta dinâmica (ex: 8001 para cestadeprecos)
                 ▼
┌─────────────────────────────────────┐
│ ProxyAuth Middleware (módulo)        │
│ - Recebe headers X-DB-*              │
│ - Chama configureDynamicDB()         │
│ - Config DB com credenciais do tenant│
└────────────────┬────────────────────┘
                 │
                 │ Operações de BD sempre usar DB correto
                 ▼
        ┌──────────────┐
        │ tenant_db    │
        │ (isolado!)   │
        └──────────────┘
```

---

## 3. MIDDLEWARE DE TENANT - CAMADA CRÍTICA DE SEGURANÇA

### 3.1 ProxyAuth.php - Autenticação via Proxy

**Localização**: `/modulos/cestadeprecos/app/Http/Middleware/ProxyAuth.php`

**Responsabilidades**:
1. Autenticar usuários via headers X-User-* do proxy
2. Configurar banco dinâmico via headers X-DB-*
3. Persistir contexto de tenant em sessão
4. **BLOQUEAR acessos cross-tenant**

**Fluxo Detalhado**:

```php
handle(Request $request, Closure $next): Response
{
    // PASSO 1: Rotas Públicas (CDF, Cotação, etc)
    if (isPublicRoute($request)) {
        if ($request->hasHeader('X-DB-Name')) {
            // Banco pode vir do formulário público CDF
            configureDynamicDatabaseConnection($request);
        }
        return $next($request);
    }

    // PASSO 2: Verificar Sessão Existente
    // A sessão pode ter contexto de requisição anterior
    $tenantData = session('proxy_tenant');
    $userData = session('proxy_user_data');
    $dbConfig = session('proxy_db_config');

    if ($tenantData && $userData && $dbConfig) {
        // ✅ VALIDAÇÃO CRÍTICA: Tenant da sessão == Tenant atual?
        $currentTenantId = $request->header('X-Tenant-Id');
        $sessionTenantId = $tenantData['id'];

        if ($currentTenantId && $currentTenantId != $sessionTenantId) {
            // 🚨 BLOQUEIO: Cross-tenant access attempt!
            Log::critical('Cross-tenant access attempt BLOCKED!', [
                'session_tenant_id' => $sessionTenantId,
                'current_tenant_id' => $currentTenantId,
                'user_email' => $userData['email'],
            ]);
            
            // Limpar sessão e forçar reautenticação
            session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);
            // Continua para autenticar via headers...
        } else {
            // ✅ Tenant correto - restaurar contexto
            $this->configureDatabaseFromConfig($dbConfig);
            $request->attributes->set('tenant', $tenantData);
            $request->attributes->set('user', $userData);
            return $next($request);
        }
    }

    // PASSO 3: Autenticar via Headers do Proxy
    // Headers vêm do ModuleProxyController
    $userId = $request->header('X-User-Id');
    $userEmail = $request->header('X-User-Email');
    $tenantId = $request->header('X-Tenant-Id');
    $dbConfig = [
        'database' => $request->header('X-DB-Name'),
        'host' => $request->header('X-DB-Host', '127.0.0.1'),
        'username' => $request->header('X-DB-User'),
        'password' => $request->header('X-DB-Password'),
    ];

    if ($userId && $userEmail && $tenantId) {
        // SALVAR na sessão (cache entre requisições)
        session([
            'proxy_tenant' => ['id' => $tenantId, ...],
            'proxy_user_data' => ['id' => $userId, ...],
            'proxy_db_config' => $dbConfig
        ]);

        // Configurar banco dinamicamente
        $this->configureDynamicDatabaseConnection($request);

        // Buscar/criar user local no módulo
        $user = User::firstOrCreate(
            ['email' => $userEmail],
            ['name' => userName, 'password' => bcrypt(random(32))]
        );

        // Autenticar manualmente na sessão
        Auth::guard('web')->setUser($user);
        
        return $next($request);
    }

    return $next($request);
}

// MÉTODO CRÍTICO: Configurar banco dinamicamente
private function configureDynamicDatabaseConnection(Request $request): void
{
    $dbConfig = [
        'driver' => 'pgsql',
        'host' => $request->header('X-DB-Host', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => $request->header('X-DB-Name'),        // ← Dinâmico!
        'username' => $request->header('X-DB-User'),        // ← Dinâmico!
        'password' => $request->header('X-DB-Password'),    // ← Dinâmico!
        'charset' => 'utf8',
        'prefix' => '',  // Prefixo vazio - tabelas já têm cp_
        'schema' => 'public',
        'sslmode' => 'prefer',
    ];

    // Sobrescrever configuração de 'pgsql' (conexão padrão)
    config(['database.connections.pgsql' => $dbConfig]);

    // Reconectar para aplicar as novas configurações
    DB::purge('pgsql');
    DB::reconnect('pgsql');
}
```

### 3.2 DynamicSessionDomain.php - Sessões por Domain

**Finalidade**: Garantir que cookies de sessão sejam específicos por tenant

**Implementação esperada**:
```php
// Modificar o domínio da sessão baseado no host atual
// Exemplo: curitiba.sistemacompras.gov.br -> sessão de curitiba
// Exemplo: saopaulo.sistemacompras.gov.br -> sessão de saopaulo
```

**Benefício**: Múltiplos tenants na mesma rede não compartilham cookies

### 3.3 TenantAuthMiddleware.php (NÃO ENCONTRADO - IMPLEMENTAÇÃO FUTURA?)

Padrão esperado para middleware adicional de validação:
```php
// Validar que o user_id vem realmente do tenant esperado
// Bloquear se tenant_id não bater
```

---

## 4. MODELS E CONEXÕES - PADRÃO DE ISOLAMENTO

### 4.1 Dois Tipos de Models

#### **Tipo 1: Tenant-Specific (Banco exclusivo)**
```php
// ✅ Exemplo: Orcamento.php
class Orcamento extends Model
{
    protected $table = 'cp_orcamentos';  // Prefixo cp_ para isolar do MinhaDattaTech
    // NÃO define $connection, usa padrão 'pgsql' (dinâmico por tenant)

    protected $fillable = [
        'numero', 'nome', 'objeto', 'status', ...
    ];
}

// ✅ Exemplo: User.php
class User extends Authenticatable
{
    protected $table = 'cp_users';  // Cada tenant tem seus usuários locais
    // Autenticação ocorre NO banco do tenant (não no banco central)
}

// ✅ Exemplo: Fornecedor.php
class Fornecedor extends Model
{
    protected $table = 'cp_fornecedores';  // Fornecedores específicos de cada tenant
    // Um fornecedor registrado em Curitiba não aparece em São Paulo
}
```

**Quando usar**: Dados que variam por tenant (orçamentos, fornecedores, usuários locais)

#### **Tipo 2: Compartilhado (Banco principal)**
```php
// ✅ Exemplo: Catmat.php
class Catmat extends Model
{
    protected $connection = 'pgsql_main';  // ← FIXO: Sempre banco principal
    protected $table = 'cp_catmat';

    // IMPORTANTE: Este model acessa o mesmo catálogo para TODOS os tenants
    // Curitiba vê os mesmos materiais que São Paulo
}

// ✅ Exemplo: PrecoComprasGov.php
class PrecoComprasGov extends Model
{
    protected $connection = 'pgsql_main';  // ← FIXO
    protected $table = 'cp_precos_comprasgov';

    // Preços da API Compras.gov são compartilhados
}

// ✅ Exemplo: MedicamentoCmed.php
class MedicamentoCmed extends Model
{
    protected $connection = 'pgsql_main';  // ← FIXO
    protected $table = 'cp_medicamentos_cmed';

    // CMED é base de dados pública, compartilhada
}
```

**Quando usar**: Dados públicos/compartilhados que não variam por tenant

### 4.2 Lista Completa de 37 Models

```
Tenant-Specific (25 models):
├── Orcamento                      # Orçamentos (público)
├── OrcamentoItem                  # Itens dos orçamentos
├── User                           # Usuários locais do módulo
├── Fornecedor                     # Fornecedores
├── FornecedorItem                 # Itens fornecedores
├── Lote                           # Lotes de orçamento
├── Orgao                          # Órgão interessado
├── ContratoPNCP                   # Contratos PNCP
├── SolicitacaoCDF                 # Solicitações de CDF
├── SolicitacaoCDFItem             # Itens CDF
├── ContratacaoSimilar             # Contratações similares
├── ContratacaoSimilarItem         # Itens de contratação
├── ColetaEcommerce                # Coletas de e-commerce
├── ColetaEcommerceItem            # Itens de coleta
├── ArpItem                        # ARP (Sistema de Pesquisa)
├── Anexo                          # Anexos de orçamentos
├── LogImportacao                  # Logs de importação
├── AuditSnapshot                  # Snapshots de auditoria
├── HistoricoPreco                 # Histórico de preços
├── Notificacao                    # Notificações do módulo
├── CatalogoProduto                # Catálogo de produtos
├── OrientacaoTecnica              # Orientações técnicas
└── ... (mais 4)

Compartilhados (5 models com pgsql_main):
├── Catmat                         # Catálogo de materiais
├── PrecoComprasGov                # Preços Compras.gov
├── MedicamentoCmed                # Medicamentos CMED
├── ContratoExterno                # Contratos externos
└── ItemContratoExterno            # Itens de contrato
```

### 4.3 Relacionamentos Cross-Connection

**Desafio**: Um Model em 'pgsql' (tenant) pode precisar referenciar dados em 'pgsql_main'

**Exemplo Prático**:
```php
// OrcamentoItem pertence a um Orcamento (mesmo DB) E referencia um Catmat (outro DB)
class OrcamentoItem extends Model
{
    protected $table = 'cp_itens_orcamento';  // Usa 'pgsql' padrão (tenant)

    // Relacionamento 1: Mesmo banco (tenant)
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    // Relacionamento 2: Banco diferente (problema!)
    public function catmat()
    {
        // ❌ Não funciona assim:
        // return $this->belongsTo(Catmat::class, 'catmat_codigo', 'codigo');
        
        // ✅ Solução: Query manual com connection explícita
        return DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->where('codigo', $this->catmat_codigo)
            ->first();
    }
}

// Uso no Controller:
class OrcamentoController extends Controller
{
    public function show($id)
    {
        $orcamento = Orcamento::with('itens')->find($id);
        
        foreach ($orcamento->itens as $item) {
            // Buscar catmat manualmente
            $catmat = DB::connection('pgsql_main')
                ->table('cp_catmat')
                ->where('codigo', $item->catmat_codigo)
                ->first();
            
            $item->catmat = $catmat;
        }
        
        return view('orcamentos.show', compact('orcamento'));
    }
}
```

---

## 5. MIGRATIONS - ORGANIZAÇÃO E ESTRATÉGIA

### 5.1 Estrutura de Migrations

```
database/migrations/
├── 2025_10_23_114218_add_tem_preco_comprasgov_to_catmat.php
├── 2025_10_23_130600_fix_cp_audit_log_itens_structure.php
├── 2025_10_23_155204_create_cp_contratos_externos_table.php
├── 2025_10_24_160533_corrigir_prefixo_tabelas_inconsistentes.php
├── 2025_10_27_150000_increase_telefone_length_all_tables.php
├── 2025_10_29_113814_create_cp_precos_comprasgov_table.php   ← Banco principal!
└── 2025_10_29_114457_create_cp_catmat_main_table.php        ← Banco principal!
```

### 5.2 Padrão: Conexão Explícita nas Migrations

**Migrations para banco PRINCIPAL** (compartilhado):
```php
// arquivo: create_cp_precos_comprasgov_table.php
return new class extends Migration
{
    public function up(): void
    {
        // IMPORTANTE: Especificar conexão 'pgsql_main'
        Schema::connection('pgsql_main')->create('cp_precos_comprasgov', function (Blueprint $table) {
            $table->id();
            $table->string('catmat_codigo', 20)->index();
            $table->decimal('preco_unitario', 15, 2);
            $table->string('fornecedor_cnpj', 14)->nullable()->index();
            $table->date('data_compra')->nullable()->index();
            $table->timestamp('sincronizado_em');
            $table->timestamps();
        });

        // Índices especiais (PostgreSQL)
        Schema::connection('pgsql_main')->table('cp_precos_comprasgov', function($table) {
            DB::connection('pgsql_main')->statement(
                "CREATE INDEX idx_precos_desc ON cp_precos_comprasgov 
                 USING gin(to_tsvector('portuguese', descricao_item))"
            );
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_main')->dropIfExists('cp_precos_comprasgov');
    }
};
```

**Migrations para banco TENANT** (default, sem especificar conexão):
```php
// arquivo: create_cp_orcamentos_table.php
return new class extends Migration
{
    public function up(): void
    {
        // NÃO especificar connection - usa 'pgsql' padrão (tenant-specific)
        Schema::create('cp_orcamentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('nome', 255);
            $table->text('objeto');
            $table->string('status')->default('pendente');
            $table->timestamps();
            // ... mais campos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cp_orcamentos');
    }
};
```

### 5.3 Exemplo Real: Aumentar Telefone

```php
// arquivo: increase_telefone_length_all_tables.php
return new class extends Migration
{
    public function up(): void
    {
        // TABELA 1: cp_orgaos (tenant-specific)
        Schema::table('cp_orgaos', function (Blueprint $table) {
            $table->string('telefone', 50)->nullable()->change();  // 20 -> 50
        });

        // TABELA 2: cp_fornecedores (tenant-specific)
        Schema::table('cp_fornecedores', function (Blueprint $table) {
            $table->string('telefone', 50)->nullable()->change();
            $table->string('celular', 50)->nullable()->change();
        });

        // TABELA 3: cp_solicitacoes_cdf (tenant-specific)
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->string('telefone', 50)->nullable()->change();
        });

        // Nenhuma tabela em 'pgsql_main' precisa de telefone
    }

    public function down(): void
    {
        // Reverter (⚠️ Risco de truncamento!)
        Schema::table('cp_orgaos', function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->change();
        });
        // ...
    }
};
```

### 5.4 Executando Migrations por Tenant

**Comando para tenant específico** (via ModuleInstaller):
```php
// Em ModuleInstaller::install()
private function runMigrations($modulePath): void
{
    // Configurar conexão temporária para o tenant
    $this->configureTenantConnection($dbConfig);

    // Executar migrations USANDO A CONEXÃO TENANT
    Artisan::call('migrate', [
        '--database' => 'tenant_install',  // ← Conexão específica
        '--path' => $modulePath . '/database/migrations'
    ]);

    // Resultado: Migrations rodadas NO banco do tenant
}
```

**Implicações**:
- Cada tenant tem sua própria tabela `migrations` (rastreamento)
- Tabelas com `Schema::connection('pgsql_main')` rodam uma única vez (no banco principal)
- Prefixo `cp_` nas migrations garante isolamento do MinhaDattaTech

---

## 6. ESTRUTURA DE DIRETÓRIOS - SEPARAÇÃO MODULAR

### 6.1 Sistema Central vs Módulos

```
SISTEMA CENTRAL (MinhaDataTech)
/home/dattapro/minhadattatech/
├── app/
│   ├── Models/
│   │   ├── Tenant.php               # Define estructura do tenant
│   │   ├── User.php                 # Usuários globais
│   │   ├── ModuleConfiguration.php  # Registro de módulos
│   │   └── TenantActiveModule.php   # Módulos ativados por tenant
│   ├── Http/Controllers/
│   │   ├── ModuleProxyController.php    # CORAÇÃO: Proxy inteligente
│   │   ├── TenantController.php         # CRUD de tenants
│   │   └── API/ModuleController.php     # API para registrar módulos
│   ├── Services/
│   │   └── ModuleInstaller.php      # Instala módulos em novo tenant
│   └── Http/Middleware/
│       ├── DetectTenant.php         # Detecta tenant por domínio
│       └── AuthorizeTenant.php      # Valida acesso ao tenant
├── database/
│   └── migrations/                  # Migrações do sistema central
│       ├── tenants table
│       ├── users table
│       ├── module_configurations table
│       └── tenant_active_modules table
├── routes/
│   └── web.php                      # Rotas principais
│       ├── /admin/tenants            # Gerenciar tenants
│       ├── /module-proxy/{module}/*  # Proxy para módulos
│       └── /api/modules              # API de módulos
└── config/
    └── app.php

MÓDULOS (Isolados)
/home/dattapro/modulos/
├── cestadeprecos/                   # Módulo de Cesta de Preços
│   ├── config/
│   │   └── database.php             # Conexões (pgsql dinâmico + pgsql_main fixo)
│   ├── app/
│   │   ├── Models/ (37 models)
│   │   ├── Http/
│   │   │   ├── Controllers/ (múltiplos)
│   │   │   └── Middleware/
│   │   │       ├── ProxyAuth.php         # ← CRÍTICO: Autentica via headers
│   │   │       ├── DynamicSessionDomain.php
│   │   │       └── InternalOnly.php
│   │   └── Services/ (API, importação, etc)
│   ├── database/
│   │   ├── migrations/ (~50+ migrations com cp_)
│   │   └── seeders/
│   ├── resources/views/
│   ├── routes/web.php               # Rotas do módulo
│   ├── bootstrap/app.php            # Inicialização do módulo
│   └── artisan                      # CLI do módulo
│
├── nfe/                             # Módulo NFe (similar)
├── crm/                             # Módulo CRM (similar)
└── technical/                       # Painel técnico (similar)
```

### 6.2 Fluxo de Requisição Entre Sistemas

```
┌─────────────────────────────────────────┐
│ Browser do Usuário                       │
│ - Autenticado em MinhaDataTech          │
│ - Cookie de sessão global                │
└──────────┬──────────────────────────────┘
           │
           │ GET /modulo-proxy/cestadeprecos/orcamentos
           ▼
┌──────────────────────────────────────────────────────┐
│ MinhaDataTech (Rota: module-proxy)                    │
│                                                       │
│ ModuleProxyController::proxy()                        │
│ ├─ Verifica Auth::check()                            │
│ ├─ Obtém tenant de session('current_tenant')         │
│ ├─ Busca credenciais DB do tenant                    │
│ ├─ Monta headers X-Tenant-Id, X-DB-*                │
│ ├─ Faz HTTP request ao módulo:                       │
│ │   GET http://localhost:8001/orcamentos             │
│ │   Headers: X-Tenant-Id: 5                           │
│ │            X-DB-Name: prefeitura_curitiba_db       │
│ │            X-DB-User: tenant_user                   │
│ │            X-DB-Password: encrypted_pwd            │
│ │            X-User-Id: 42                            │
│ │            X-User-Email: user@prefeitura.gov.br    │
│ │            Cookie: ...                              │
│ └─ Aguarda resposta                                   │
│                                                       │
└──────────┬───────────────────────────────────────────┘
           │
           │ HTTP Request com Headers X-*
           ▼
┌──────────────────────────────────────────────────────┐
│ Módulo CestadePrecos (Porta 8001)                    │
│                                                       │
│ ProxyAuth Middleware::handle()                        │
│ ├─ Recebe headers X-Tenant-Id, X-DB-*               │
│ ├─ Valida X-Tenant-Id vs sessão anterior             │
│ ├─ Chama configureDynamicDatabaseConnection()        │
│ │   └─ Config pgsql com X-DB-Name, X-DB-User, etc  │
│ ├─ Cria/atualiza User local                          │
│ ├─ Persiste na sessão: proxy_tenant, proxy_db_config │
│ └─ Passa request para controller                     │
│                                                       │
│ OrcamentoController::index()                          │
│ ├─ Orcamento::all() ← Usa conexão pgsql configurada  │
│ │   └─ Query: SELECT * FROM cp_orcamentos            │
│ │       └─ Do banco: prefeitura_curitiba_db ✓        │
│ ├─ Catmat::all() ← Usa conexão pgsql_main (fixo)    │
│ │   └─ Query: SELECT * FROM cp_catmat                │
│ │       └─ Do banco: minhadattatech_db ✓             │
│ └─ Retorna view com dados                            │
│                                                       │
└──────────┬───────────────────────────────────────────┘
           │
           │ HTTP Response (HTML)
           ▼
┌──────────────────────────────────────┐
│ MinhaDataTech (Proxy)                 │
│ ├─ Recebe HTML do módulo             │
│ ├─ Injeta <base href="/...">         │
│ ├─ Transforma URLs relativas         │
│ └─ Retorna ao browser                 │
└──────────┬──────────────────────────┘
           │
           │ HTML renderizado
           ▼
┌──────────────────────────────────────┐
│ Browser (exibe página)                │
└──────────────────────────────────────┘
```

---

## 7. SEGURANÇA CROSS-TENANT

### 7.1 Validação de Cross-Tenant Access

**Localização**: ProxyAuth.php linhas 81-103

```php
// Validação crítica: tenant da sessão vs tenant da requisição
if ($currentTenantId && $sessionTenantId && $currentTenantId != $sessionTenantId) {
    // 🚨 BLOQUEIO IMEDIATO
    Log::critical('Cross-tenant access attempt BLOCKED!', [
        'session_tenant_id' => $sessionTenantId,
        'current_tenant_id' => $currentTenantId,
        'user_email' => $userData['email'],
        'uri' => $request->getRequestUri(),
    ]);

    // Limpar sessão (forçar reautenticação)
    session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);

    // Continuar para reautenticar via headers do proxy
    // (proxy NÃO vai permitir mudar de tenant sem reautenticar)
}
```

### 7.2 Validações em Controllers

**Exemplo em FornecedorController**:
```php
public function index(Request $request)
{
    // Cada query automáticamente usa banco do tenant (via pgsql dinâmico)
    // Um fornecedor registrado em Curitiba nunca aparece em São Paulo
    $fornecedores = Fornecedor::all();  // ← Isolado por tenant automaticamente!
}
```

**Exemplo em OrcamentoController**:
```php
public function show($id)
{
    // Buscar orçamento
    $orcamento = Orcamento::find($id);  // ← Usa banco tenant atual

    // Validação adicional (redundante mas segura)
    if (!$orcamento || $orcamento->tenant_id != session('proxy_tenant.id')) {
        abort(404);
    }

    return view('orcamentos.show', compact('orcamento'));
}
```

### 7.3 Headers Especiais de Segurança

```
X-Tenant-Id: 5                          # ID do tenant
X-Tenant-Subdomain: curitiba            # Subdomínio
X-Tenant-Name: Prefeitura de Curitiba   # Nome

X-User-Id: 42                           # ID do usuário
X-User-Email: user@prefeitura.gov.br    # Email
X-User-Name: João Silva                 # Nome
X-User-Role: admin                      # Role

X-DB-Name: prefeitura_curitiba_db       # Nome do banco
X-DB-Host: 127.0.0.1                    # Host do DB
X-DB-User: tenant_user                  # Usuário DB
X-DB-Password: senha_encriptada         # Senha DB
X-DB-Prefix: cp_                        # Prefixo de isolamento

X-Module-Token: jwt_token               # Token de módulo
X-Original-IP: 192.168.1.100            # IP original
```

### 7.4 Cenários de Ataque e Mitigação

| Cenário | Tentativa | Mitigação |
|---------|-----------|-----------|
| **Cross-Tenant Hijacking** | User A tenta acessar dados de User B (outro tenant) | Headers X-Tenant-Id validados vs. sessão. Mismatch = bloqueio automático |
| **Cookie Spoofing** | User A rouba cookie de User B | Diferentes domínios (curitiba.com vs saopaulo.com) = cookies não compartilhados |
| **DB Injection** | Injetar código SQL via headers X-DB-* | Headers vêm de proxy autenticado. Proxy não confia em input do cliente |
| **Privilege Escalation** | User tenta se promover a admin | Middleware valida X-User-Role. Banco não toca em campo role após autenticação |
| **Session Fixation** | Reusar sessão de outro tenant | ProxyAuth::handle verifica X-Tenant-Id a cada request. Session é limpa se não bater |

---

## 8. IMPLEMENTAÇÃO PRÁTICA: EXEMPLO DO CICLO COMPLETO

### 8.1 Novo Tenant Onboarding

**Processo**:

```
1. Criar Tenant no MinhaDataTech
   └─ INSERT INTO tenants (subdomain, database_name, db_host, db_user, db_password_encrypted)
      VALUES ('curitiba', 'prefeitura_curitiba_db', '127.0.0.1', 'tenant_user', encrypt('senha'))

2. Criar banco PostgreSQL
   └─ CREATE DATABASE prefeitura_curitiba_db;
   └─ CREATE USER tenant_user WITH PASSWORD 'senha';
   └─ GRANT ALL PRIVILEGES ON DATABASE prefeitura_curitiba_db TO tenant_user;

3. Ativar módulos no Tenant
   └─ INSERT INTO tenant_active_modules (tenant_id, module_key, enabled)
      VALUES (5, 'price_basket', true);  -- Ativa cestadeprecos

4. Instalar módulos (via ModuleInstaller)
   ├─ Configurar conexão temporária ao novo banco
   ├─ Executar migrations do módulo
   │  └─ Cria: cp_orcamentos, cp_fornecedores, cp_lotes, ...
   │  └─ Cria tabelas compartilhadas: cp_catmat (aponta a pgsql_main)
   ├─ Executar seeders
   └─ Verificar tabelas criadas

5. Resultado:
   ├─ prefeitura_curitiba_db tem ~50 tabelas cp_*
   ├─ minhadattatech_db não muda (já tem cp_catmat, cp_precos_comprasgov, etc)
   └─ Tenant pronto para usar!
```

### 8.2 Usuário Acessando um Orçamento

**Fluxo Detalhado**:

```
1. Usuário em minhadattatech.com/orcamentos
   └─ Tem cookie de sessão global
   └─ Sabe qual tenant é (curitiba)

2. Clica em "Abrir Módulo Cesta de Preços"
   └─ Browser: GET /module-proxy/cestadeprecos/orcamentos/123

3. MinhaDataTech Rota (middleware): 
   └─ Verifica se usuário está autenticado ✓
   └─ Busca tenant_id de session('current_tenant').id = 5
   └─ Busca configuração de DB do tenant 5
   └─ Monta headers X-Tenant-Id: 5, X-DB-Name: prefeitura_curitiba_db, etc

4. HTTP Request ao módulo:
   GET http://localhost:8001/orcamentos/123
   Headers:
     X-Tenant-Id: 5
     X-DB-Name: prefeitura_curitiba_db
     X-User-Id: 42
     X-User-Email: joao@prefeitura.gov.br

5. Módulo - ProxyAuth Middleware:
   └─ Recebe headers
   └─ Valida X-Tenant-Id: 5 vs session('proxy_tenant.id')
   └─ Se não existir sessão, salva na sessão
   └─ Se existir e bater, restaura banco do contexto anterior
   └─ Se existir e NOT bater, BLOQUEIA e limpa sessão
   └─ Configura DB: pgsql = prefeitura_curitiba_db
   └─ Passa para controller

6. Módulo - OrcamentoController:
   └─ $orcamento = Orcamento::find(123);
      └─ Query: SELECT * FROM cp_orcamentos WHERE id = 123
      └─ Executada em: prefeitura_curitiba_db ✓
      └─ Encontra orçamento "Arroz 5kg" (só existe em Curitiba)

7. Módulo - View:
   └─ Mostra orçamento com dados de Curitiba
   └─ Carrega Catmat: Catmat::all()
      └─ Query: SELECT * FROM cp_catmat (de pgsql_main)
      └─ Usa minhadattatech_db (compartilhado)
      └─ Exibe materiais de TODOS os tenants (normal, é catálogo global)

8. MinhaDataTech - Proxy:
   └─ Recebe HTML do módulo
   └─ Injeta <base href="/module-proxy/cestadeprecos/">
   └─ Transforma URLs relativas
   └─ Retorna HTML ao browser

9. Browser:
   └─ Exibe página
   └─ Links como "/css/style.css" viram "/module-proxy/cestadeprecos/css/style.css"
   └─ Proxia pela rota de módulo
```

### 8.3 Tentativa de Cross-Tenant Attack

```
1. User A (Curitiba, tenant_id=5) está logado
   ├─ Cookie de sessão de Curitiba
   └─ session('proxy_tenant.id') = 5

2. User A tenta acessar São Paulo (tenant_id=6):
   └─ URL: saopaulo.sistemacompras.gov.br/module-proxy/cestadeprecos/orcamentos

3. MinhaDataTech:
   └─ Detecta tenant pelo domínio: SAOPAULO (tenant_id=6)
   └─ Monta headers com X-Tenant-Id: 6
   └─ Faz HTTP request ao módulo

4. Módulo - ProxyAuth::handle():
   ├─ Recebe header X-Tenant-Id: 6
   ├─ Verifica session('proxy_tenant.id') = 5 (ainda de Curitiba)
   ├─ 5 != 6 ❌ MISMATCH!
   ├─ Log Critical: "Cross-tenant access attempt BLOCKED!"
   ├─ session()->forget([...]) ← Limpa sessão
   ├─ User não autenticado, redireciona para login
   
5. Resultado:
   └─ User A é forçado a fazer logout
   └─ Não consegue acessar dados de São Paulo
   └─ Segurança garantida!
```

---

## 9. FLUXO DE COMPARTILHAMENTO DE DADOS

### 9.1 Dados Tenant-Specific vs Compartilhados

```
CENÁRIO: Sistema de Compras Pública

┌──────────────────────────────────────────────────────────┐
│ BANCO PRINCIPAL (minhadattatech_db) - COMPARTILHADO      │
│                                                           │
│ Tabelas Compartilhadas:                                  │
│ ├─ cp_catmat (38.000+ materiais)                         │
│ │  └─ Curitiba vê: "Arroz integral 5kg"                 │
│ │  └─ São Paulo vê: "Arroz integral 5kg" (mesmo!)       │
│ ├─ cp_precos_comprasgov (últimos 12 meses)              │
│ │  └─ Curitiba vê: "Fornecedor X vendeu 10 sacos"      │
│ │  └─ São Paulo vê: "Fornecedor X vendeu 8 sacos"      │
│ │  └─ (Dados agregados, não ligados a nenhum tenant)   │
│ ├─ cp_medicamentos_cmed (CMED pública)                  │
│ │  └─ Ambos veem mesmos medicamentos (dados públicos)   │
│ └─ cp_contratos_externos (referência)                   │
│    └─ Ambos veem histórico de contratos públicos        │
│                                                           │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ BANCO CURITIBA (prefeitura_curitiba_db)                  │
│                                                           │
│ Tabelas Isoladas:                                        │
│ ├─ cp_orcamentos (50 orçamentos)                         │
│ │  └─ Orçamento #001/2025: Arroz integral 5kg           │
│ │  └─ Fornecedor: XYZ Distribuição                      │
│ │  └─ Preço: R$ 150,00                                  │
│ ├─ cp_fornecedores (120 fornecedores cadastrados)       │
│ ├─ cp_users (45 usuários locais)                        │
│ └─ ... (outros dados específicos de Curitiba)           │
│                                                           │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ BANCO SÃO PAULO (prefeitura_saopaulo_db)                │
│                                                           │
│ Tabelas Isoladas:                                        │
│ ├─ cp_orcamentos (80 orçamentos)                         │
│ │  └─ Orçamento #001/2025: Arroz integral 5kg           │
│ │  └─ Fornecedor: ABC Comércios (DIFERENTE!)           │
│ │  └─ Preço: R$ 145,00 (DIFERENTE!)                    │
│ ├─ cp_fornecedores (200 fornecedores)                   │
│ ├─ cp_users (100 usuários)                              │
│ └─ ... (outros dados específicos de São Paulo)          │
│                                                           │
└──────────────────────────────────────────────────────────┘

QUERY EXECUTADA PELA INTERFACE:
    "Qual é o preço médio de Arroz integral 5kg?"

Curitiba:
  ├─ SELECT COUNT(*) FROM cp_orcamentos WHERE ... = 5 orçamentos
  ├─ SELECT AVG(preco_unitario) = R$ 152,00
  └─ Query em: prefeitura_curitiba_db ✓

São Paulo:
  ├─ SELECT COUNT(*) FROM cp_orcamentos WHERE ... = 8 orçamentos
  ├─ SELECT AVG(preco_unitario) = R$ 141,00
  └─ Query em: prefeitura_saopaulo_db ✓

Catmat (compartilhado):
  └─ SELECT * FROM cp_catmat WHERE titulo LIKE '%Arroz%' = 1 resultado
     └─ Mesmo resultado para ambos os tenants!
```

### 9.2 Fluxo de Sincronização de Preços

```
1. Tarefa Diária (01:00 AM):
   └─ Job: BaixarPrecosComprasGov (Laravel Queue)
   └─ Conecta via 'pgsql_main'
   └─ Faz requisição API Compras.gov
   └─ Insere em: minhadattatech_db.cp_precos_comprasgov
   └─ SINCRONIZAÇÃO: 12 meses de histórico

2. Curitiba acessa Modal "Compras.gov":
   ├─ Frontend: /compras-gov/buscar?termo=arroz
   ├─ Backend: Query a cp_catmat (pgsql_main)
   │  └─ Encontra CATMAT código 123456 "Arroz integral 5kg"
   ├─ Backend: Query a cp_precos_comprasgov (pgsql_main)
   │  └─ Encontra últimos 12 meses de preços
   │  └─ Média: R$ 151,00
   │  └─ Fornecedores: XYZ, ABC, DEF, ...
   └─ Frontend: Exibe resultados

3. São Paulo acessa Modal "Compras.gov":
   ├─ Frontend: /compras-gov/buscar?termo=arroz
   ├─ Backend: Query a cp_catmat (pgsql_main) ← MESMA QUERY!
   │  └─ Encontra CATMAT código 123456 (mesmo!)
   ├─ Backend: Query a cp_precos_comprasgov (pgsql_main) ← MESMA QUERY!
   │  └─ Encontra últimos 12 meses de preços (mesmos!)
   │  └─ Média: R$ 151,00 (mesmo!)
   │  └─ Fornecedores: XYZ, ABC, DEF, ... (mesmos!)
   └─ Frontend: Exibe resultados

BENEFÍCIO: Sincronização única, dados compartilhados
REDUZ: 70% de banda de API (não sincroniza 100x em 100 tenants)
```

---

## 10. CHECKLIST DE SEGURANÇA MULTITENANT

```
✓ Isolamento de Banco
  ├─ [ ] Cada tenant tem banco independente
  ├─ [ ] Headers X-DB-* vêm apenas do proxy autenticado
  ├─ [ ] Config pgsql é reconfigurável a cada request
  ├─ [ ] DB::purge() e DB::reconnect() chamados após mudança

✓ Validação Cross-Tenant
  ├─ [ ] ProxyAuth valida X-Tenant-Id vs session('proxy_tenant.id')
  ├─ [ ] Mismatch causa bloqueio imediato e limpeza de sessão
  ├─ [ ] Log crítico é registrado
  ├─ [ ] Controllers fazem query no banco correto automaticamente

✓ Autenticação
  ├─ [ ] Usuários autenticados via headers X-User-* (proxy)
  ├─ [ ] Sessão persiste contexto do proxy para requisições subsequentes
  ├─ [ ] User local criado no banco do tenant (não global)
  ├─ [ ] Logout limpa proxy_tenant e proxy_user_data da sessão

✓ Dados Compartilhados
  ├─ [ ] Modelos com $connection = 'pgsql_main' acessam banco central
  ├─ [ ] Migrations usam Schema::connection('pgsql_main')
  ├─ [ ] Sincronização de dados é feita UMA VEZ (não por tenant)
  ├─ [ ] Todos os tenants veem mesmos CATMAT, preços, etc.

✓ Prefixo de Tabelas
  ├─ [ ] Prefixo 'cp_' isola módulo do MinhaDattaTech no mesmo banco
  ├─ [ ] Migrations não usam $prefix global (hardcoded 'cp_' nas DDL)
  ├─ [ ] Modelos definem protected $table = 'cp_*'
  ├─ [ ] Migrations ao criar também usam 'cp_*' names

✓ Documentação
  ├─ [ ] Código tem comentários de conexão explícita
  ├─ [ ] README explica arquitetura multitenant
  ├─ [ ] Novos devs sabem diferença entre pgsql e pgsql_main
  ├─ [ ] Migração nova sempre especifica connection se não for padrão
```

---

## 11. CONCLUSÃO E RECOMENDAÇÕES

### Forças da Arquitetura:
1. **Isolamento Total**: Dados de tenants nunca se misturam
2. **Escalabilidade**: Novo tenant = novo banco, zero impacto em outros
3. **Performance**: Banco pequeno (específico) = queries rápidas
4. **Conformidade**: LGPD compliance: dados em banco separado por tenant
5. **Independência**: Cada módulo é instalável/removível por tenant

### Pontos de Atenção:
1. **Configuração Dinâmica de BD**: Risco se headers forem spoofados (mitigado pelo proxy)
2. **Migração Distribuída**: Cada novo tenant precisa de migrations completas
3. **Sincronização**: Dados compartilhados precisam ficar em sincronia (CATMAT, preços)
4. **Monitoramento**: Difícil debugar query em "banco errado"
5. **Backup/Restore**: Cada tenant tem backup independente

### Boas Práticas:
1. **Sempre validar X-Tenant-Id** nos controllers sensíveis
2. **Usar scopes de tenant** nos Models quando possível
3. **Documentar** se um Model usa pgsql ou pgsql_main
4. **Testar cross-tenant** antes de deploiar mudanças
5. **Monitorar logs críticos** de bloqueio de cross-tenant
6. **Manter separação clara**: Dados do tenant vs dados globais

---

**Documento Gerado**: 30/10/2025
**Arquitetura**: Multitenant Híbrido com Módulos Isolados
**Banco Principal**: minhadattatech_db
**Bancos Tenants**: prefeitura_*_db
**Prefixo de Isolamento**: cp_
