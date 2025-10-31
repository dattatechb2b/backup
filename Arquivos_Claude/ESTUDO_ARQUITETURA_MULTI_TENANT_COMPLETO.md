# ESTUDO ESPECIALIZADO - ARQUITETURA MULTI-TENANT DO SISTEMA

**Data:** 31 de Outubro de 2025  
**Thoroughness Level:** VERY THOROUGH  
**Status:** ANÁLISE COMPLETA CONCLUÍDA

---

## ÍNDICE

1. [VISÃO GERAL DA ARQUITETURA](#1-visão-geral-da-arquitetura)
2. [COMPONENTES PRINCIPAIS](#2-componentes-principais)
3. [FLUXO DE AUTENTICAÇÃO](#3-fluxo-de-autenticação)
4. [ISOLAMENTO DE DADOS](#4-isolamento-de-dados)
5. [SISTEMA DE MÓDULOS](#5-sistema-de-módulos)
6. [BANCOS DE DADOS](#6-bancos-de-dados)
7. [PADRÕES E NOMENCLATURAS](#7-padrões-e-nomenclaturas)
8. [SEGURANÇA](#8-segurança)
9. [DIAGRAMA COMPLETO](#9-diagrama-completo)

---

## 1. VISÃO GERAL DA ARQUITETURA

### 1.1 Conceito Principal

O sistema implementa uma arquitetura **multi-tenant** onde:

- Cada prefeitura/cliente é um **tenant** isolado
- Cada tenant possui seu **próprio banco de dados PostgreSQL**
- Os tenants são acessados via **subdomínios personalizados**
- Há um **sistema central (MinhaDattaTech)** que gerencia todos os tenants

### 1.2 Estrutura de Domínios

```
┌─────────────────────────────────────────────────────────┐
│                DOMÍNIOS DO SISTEMA                       │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Portal Central (Gestão):                               │
│  https://minha.dattatech.com.br                         │
│  - Gerenciamento de tenants                             │
│  - Administração global                                 │
│  - Login universal                                      │
│                                                          │
│  Tenants Individuais:                                   │
│  https://{subdomain}.dattapro.online                    │
│  - catasaltas.dattapro.online                           │
│  - novaroma.dattapro.online                             │
│  - pirapora.dattapro.online                             │
│  - gurupi.dattapro.online                               │
│  - novalaranjeiras.dattapro.online                      │
│  - dattatech.dattapro.online                            │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 1.3 Arquitetura em Camadas

```
┌────────────────────────────────────────────────────────┐
│                    NGINX/CADDY                          │
│              (Proxy Reverso SSL)                        │
└────────────────┬───────────────────────────────────────┘
                 │
┌────────────────▼───────────────────────────────────────┐
│              MINHADATTATECH                             │
│          (Sistema Central Laravel)                      │
│                                                          │
│  - Detecção de Tenant (Middlewares)                    │
│  - Autenticação Multi-Tenant                           │
│  - Gerenciamento de Sessões                            │
│  - Module Proxy Controller                             │
└────────────────┬───────────────────────────────────────┘
                 │
┌────────────────▼───────────────────────────────────────┐
│                 MÓDULOS (Portas)                        │
│                                                          │
│  - Cesta de Preços (8001)                              │
│  - NF-e (8002)                                          │
│  - Outros módulos...                                    │
└────────────────┬───────────────────────────────────────┘
                 │
┌────────────────▼───────────────────────────────────────┐
│          BANCOS DE DADOS POSTGRESQL                     │
│                                                          │
│  [minhadattatech_db]  ← Banco Central                  │
│  [catasaltas_db]      ← Tenant 1                       │
│  [novaroma_db]        ← Tenant 2                       │
│  [pirapora_db]        ← Tenant 3                       │
│  [gurupi_db]          ← Tenant 4                       │
│  [novalaranjeiras_db] ← Tenant 5                       │
│  [dattatech_db]       ← Tenant 6                       │
└─────────────────────────────────────────────────────────┘
```

---

## 2. COMPONENTES PRINCIPAIS

### 2.1 Middlewares de Tenant

#### A) DynamicSessionDomain

**Localização:** `/home/dattapro/minhadattatech/app/Http/Middleware/DynamicSessionDomain.php`

**Função:** Ajusta dinamicamente o domínio dos cookies de sessão baseado no host acessado.

**Lógica:**
```php
- Portal Universal (minha.dattatech.com.br):
  → SESSION_DOMAIN = null (cookie específico)
  
- Tenants (*.dattapro.online):
  → SESSION_DOMAIN = null (cookie isolado por tenant)
```

**Por que é importante:**
- Previne compartilhamento de sessões entre tenants
- Garante isolamento de segurança
- Cada tenant tem seus próprios cookies

---

#### B) DetectTenant

**Localização:** `/home/dattapro/minhadattatech/app/Http/Middleware/DetectTenant.php`

**Função:** Detecta qual tenant está fazendo a requisição baseado no domínio/subdomínio.

**Processo de Detecção:**

```
1. Verifica headers do Caddy (proxy reverso):
   - X-Tenant-Domain
   - X-Original-Host

2. Extrai subdomínio do host:
   pirapora.dattapro.online → "pirapora"

3. Busca tenant no banco:
   SELECT * FROM tenants WHERE subdomain = 'pirapora'

4. Armazena tenant na sessão e config:
   session(['current_tenant' => $tenant])
   config(['app.current_tenant' => $tenant])
```

**Campos detectados:**
- `tenant_id` - ID único do tenant
- `subdomain` - Subdomínio (ex: catasaltas)
- `database_name` - Nome do banco exclusivo (ex: catasaltas_db)
- `company_name` - Nome da prefeitura/empresa

---

#### C) TenantResolver

**Localização:** `/home/dattapro/minhadattatech/app/Http/Middleware/TenantResolver.php`

**Função:** Resolve e configura o contexto do tenant, incluindo banco de dados.

**Operações:**
1. Identifica subdomínio (apenas *.dattapro.online)
2. Busca tenant no banco: `Tenant::findByDomain($subdomain)`
3. Verifica se tenant está ativo
4. Configura banco de dados do tenant
5. Trata hospedagem híbrida (se aplicável)

**Importante:** NÃO interfere com minha.dattatech.com.br

---

#### D) TenantAuthMiddleware

**Localização:** `/home/dattapro/minhadattatech/app/Http/Middleware/TenantAuthMiddleware.php`

**Função:** **CRÍTICO** - Valida segurança e previne acesso cross-tenant.

**Validação de Segurança:**

```php
// Pega tenant atual da requisição
$currentTenant = session('current_tenant');

// Pega tenant_id armazenado na sessão do usuário
$sessionTenantId = session('tenant_id');

// BLOQUEIO: Verifica se são diferentes
if ($sessionTenantId !== $currentTenant->id) {
    // 🚨 CROSS-TENANT ACCESS ATTEMPT BLOCKED!
    
    Log::critical('Cross-tenant access blocked!', [
        'session_tenant_id' => $sessionTenantId,
        'current_tenant_id' => $currentTenant->id,
        'user_email' => session('user_email'),
        'ip' => $request->ip()
    ]);
    
    // Limpar sessão
    Auth::logout();
    session()->flush();
    
    // Redirecionar para login
    return redirect()->route('login')
        ->withErrors(['session' => 'Sessão inválida']);
}
```

**Proteções Implementadas:**
- Previne uso de sessão de um tenant em outro
- Detecta tentativas de acesso malicioso
- Registra logs críticos de segurança
- Força reautenticação em caso de anomalia

---

### 2.2 Models Principais

#### A) Tenant Model

**Localização:** `/home/dattapro/minhadattatech/app/Models/Tenant.php`

**Campos:**

```php
protected $fillable = [
    'crm_customer_id',           // ID no CRM externo
    'technical_client_id',       // ID no painel técnico
    'subdomain',                 // Subdomínio (ex: catasaltas)
    'custom_domain',             // Domínio customizado (opcional)
    'database_name',             // Nome do banco exclusivo
    'db_host',                   // Host do banco (padrão: 127.0.0.1)
    'db_user',                   // Usuário do banco
    'db_password_encrypted',     // Senha criptografada
    'company_name',              // Nome da prefeitura/empresa
    'status',                    // active/inactive
    'settings',                  // JSON com configurações
    'primary_domain',            // Domínio primário
    'max_users',                 // Limite de usuários
    'allow_user_registration',   // Permitir auto-registro
    'require_email_verification',// Exigir verificação de email
    'allow_password_reset',      // Permitir reset de senha
    'last_user_activity'         // Última atividade
];
```

**Métodos Críticos:**

```php
// Buscar tenant por domínio
public static function findByDomain(string $host): ?self

// Obter nome da conexão do banco
public function getDatabaseConnectionName(): string
// Retorna: 'tenant_1', 'tenant_2', etc

// Obter configuração do banco
public function getDatabaseConfig(): array
// Retorna array com: driver, host, port, database, username, password

// Verificar se tem módulo ativo
public function hasModule(string $moduleKey): bool

// Testar conexão com banco
public function testDatabaseConnection(): bool
```

---

#### B) User Model

**Localização:** `/home/dattapro/minhadattatech/app/Models/User.php`

**Importante:** Usuários **NÃO TÊM** `tenant_id`!

**Por quê?**
- Cada tenant tem seu próprio banco de dados
- Usuários estão isolados fisicamente por banco
- Não há relacionamento via foreign key

**Estrutura:**

```php
protected $fillable = [
    'name',
    'username',
    'email',
    'recovery_email',
    'password',
    'company',
    'role_id',          // Relacionamento com roles
    'is_active',
    'avatar',
    'phone',
    'last_login_at',
    'role',
    'created_by_technical'
];

// REMOVIDO da tabela:
// 'tenant_id'  ← NÃO EXISTE MAIS
```

**Identificação do Tenant:**
- Via `session('current_tenant')` - Sessão
- Via banco de dados conectado - Isolamento físico
- Via `X-DB-Name` header - Proxy de módulos

---

#### C) TenantActiveModule Model

**Localização:** `/home/dattapro/minhadattatech/app/Models/TenantActiveModule.php`

**Função:** Controla quais módulos cada tenant tem acesso.

```php
protected $fillable = [
    'tenant_id',           // FK para tenants
    'module_key',          // Ex: 'price_basket', 'nf'
    'parent_module_key',   // Módulo pai (se aplicável)
    'enabled',             // true/false
    'settings',            // Configurações específicas
    'activation_date'      // Data de ativação
];
```

**Exemplo de Dados:**

| tenant_id | module_key | enabled | activation_date |
|-----------|------------|---------|----------------|
| 1 | price_basket | true | 2025-01-15 |
| 1 | nf | false | null |
| 2 | price_basket | true | 2025-02-01 |

---

### 2.3 Controllers

#### A) AuthController

**Localização:** `/home/dattapro/minhadattatech/app/Http/Controllers/Auth/AuthController.php`

**Função:** Gerencia autenticação multi-contexto (universal e tenant-specific).

**Dois Tipos de Login:**

##### Login Tenant-Specific (subdomain.dattapro.online)

```php
// Usuário acessa: pirapora.dattapro.online/login
// Digita apenas: "admin"

1. DetectTenant identifica tenant: pirapora
2. AuthController completa email: admin@pirapora.dattapro.online
3. TenantAuthService busca usuário no banco: pirapora_db
4. Sucesso: Cria sessão com tenant_id validado
```

**Validação:**
```php
$credentials = $request->validate([
    'username' => 'required|string',
    'password' => 'required'
]);

$email = buildFullEmail($username, $tenant);
// admin → admin@pirapora.dattapro.online
```

---

##### Login Universal (minha.dattatech.com.br)

```php
// Usuário acessa: minha.dattatech.com.br/login
// Digita: "admin@pirapora.dattapro.online"

1. AuthController extrai domínio: pirapora.dattapro.online
2. TenantAuthService identifica tenant: pirapora
3. Conecta no banco: pirapora_db
4. Autentica usuário no banco do tenant
5. Redireciona para: https://pirapora.dattapro.online/desktop
```

**Validação:**
```php
$credentials = $request->validate([
    'email' => 'required|email',
    'password' => 'required'
]);

$tenant = findTenantByEmail($email);
// Extrai domínio e localiza tenant
```

---

**Sessão Criada (ambos os tipos):**

```php
session([
    'user_id' => $user->id,
    'user_email' => $user->email,
    'user_name' => $user->name,
    'user_role' => $user->role,
    'current_tenant' => $tenant,        // Objeto Tenant completo
    'tenant_id' => $tenant->id,         // ID validado
    'tenant_subdomain' => $tenant->subdomain,
    'tenant_database' => $tenant->database_name,
    'authenticated' => true
]);

// CRÍTICO: Salvar antes de redirecionar
$request->session()->save();
```

---

#### B) ModuleProxyController

**Localização:** `/home/dattapro/minhadattatech/app/Http/Controllers/ModuleProxyController.php`

**Função:** **CRÍTICO** - Proxy reverso interno que conecta o sistema central aos módulos.

**Fluxo de Requisição:**

```
1. Usuário acessa:
   https://pirapora.dattapro.online/module-proxy/price_basket/orcamentos

2. ModuleProxyController intercepta:
   - Verifica autenticação
   - Identifica tenant (pirapora)
   - Obtém config do banco: pirapora_db
   
3. Prepara headers para o módulo:
   X-Tenant-Id: 1
   X-Tenant-Subdomain: pirapora
   X-Tenant-Name: Pirapora
   X-User-Id: 15
   X-User-Email: admin@pirapora.dattapro.online
   X-DB-Name: pirapora_db          ← BANCO EXCLUSIVO
   X-DB-Host: 127.0.0.1
   X-DB-User: minhadattatech_user
   X-DB-Password: [senha]
   X-DB-Prefix: cp_                 ← Prefixo das tabelas
   
4. Faz requisição HTTP interna:
   http://localhost:8001/orcamentos
   
5. Módulo recebe, autentica via ProxyAuth, executa query:
   SELECT * FROM cp_orcamentos WHERE ...
   (no banco pirapora_db)
   
6. Retorna resposta ao usuário
```

**Configuração de Banco Dinâmica:**

```php
// Obter config do tenant
$dbConfig = $tenant->getDatabaseConfig();

// Headers enviados ao módulo
$headers = [
    'X-DB-Name' => $dbConfig['database'],      // pirapora_db
    'X-DB-Host' => $dbConfig['host'],          // 127.0.0.1
    'X-DB-User' => $dbConfig['username'],      // minhadattatech_user
    'X-DB-Password' => $dbConfig['password'],  // [senha]
    'X-DB-Prefix' => 'cp_'                     // Prefixo das tabelas
];
```

**Rotas Públicas (sem autenticação):**

```php
$publicRoutes = [
    'price_basket' => [
        '/responder-cdf',          // Formulário CDF
        '/api/cdf/responder',      // API CDF
        '/storage/',               // Arquivos estáticos
        '/brasao/',                // Brasões
        '/css/', '/js/', '/fonts/'
    ]
];
```

**Validação de Acesso:**

```php
private function userHasModuleAccess($moduleKey)
{
    $tenant = session('current_tenant');
    
    return DB::table('tenant_active_modules')
        ->where('tenant_id', $tenant->id)
        ->where('module_key', $moduleKey)
        ->where('enabled', true)
        ->exists();
}
```

---

### 2.4 Middleware do Módulo (ProxyAuth)

**Localização:** `/home/dattapro/modulos/cestadeprecos/app/Http/Middleware/ProxyAuth.php`

**Função:** Recebe requisições proxied e configura banco + autenticação.

**Operações:**

```php
1. Recebe headers do ModuleProxyController:
   - X-Tenant-Id
   - X-User-Id, X-User-Email
   - X-DB-Name, X-DB-Host, X-DB-User, X-DB-Password
   
2. Configura conexão dinâmica do banco:
   config(['database.connections.pgsql' => [
       'database' => $headers['X-DB-Name'],  // pirapora_db
       'host' => $headers['X-DB-Host'],
       'username' => $headers['X-DB-User'],
       'password' => $headers['X-DB-Password'],
       'prefix' => ''  // Sem prefixo! Tabelas já têm cp_
   ]]);
   
   DB::purge('pgsql');
   DB::reconnect('pgsql');
   
3. Autentica usuário no módulo:
   - Busca/cria User no banco do tenant
   - Cria sessão do Laravel (Auth::login)
   - Persiste dados na sessão do módulo
   
4. Salva contexto na sessão:
   session([
       'proxy_tenant' => [...],
       'proxy_user_data' => [...],
       'proxy_db_config' => [...]  // Config completa do banco
   ]);
```

**Validação Cross-Tenant (SEGURANÇA):**

```php
// Verifica se tenant da sessão == tenant da requisição
$currentTenantId = $request->header('X-Tenant-Id');
$sessionTenantId = session('proxy_tenant.id');

if ($currentTenantId != $sessionTenantId) {
    Log::critical('Cross-tenant access attempt BLOCKED!');
    
    // Limpar sessão
    session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);
    
    // Reautenticar via headers
}
```

---

### 2.5 Services

#### TenantAuthService

**Localização:** `/home/dattapro/minhadattatech/app/Services/TenantAuthService.php`

**Métodos:**

```php
// Construir email completo
buildFullEmail($username, $tenant)
// admin + pirapora → admin@pirapora.dattapro.online

// Encontrar tenant pelo email
findTenantByEmail($email)
// admin@pirapora.dattapro.online → Tenant pirapora

// Autenticar no banco do tenant
authenticateInTenant($tenant, $email, $password)
// Conecta em pirapora_db e valida credenciais
```

---

#### ModuleInstaller

**Localização:** `/home/dattapro/minhadattatech/app/Services/ModuleInstaller.php`

**Função:** Instala módulos em bancos de tenants.

**Processo de Instalação:**

```php
public function install(Tenant $tenant, string $moduleKey): bool
{
    // 1. Obter config do banco do tenant
    $dbConfig = $tenant->getDatabaseConfig();
    
    // 2. Criar conexão temporária
    config(['database.connections.tenant_install' => $dbConfig]);
    
    // 3. Executar migrations do módulo
    Artisan::call('migrate', [
        '--database' => 'tenant_install',
        '--path' => '../modulos/cestadeprecos/database/migrations',
        '--force' => true
    ]);
    
    // 4. Seeders (orientações técnicas, dados padrão)
    $this->seedPriceBasketData();
    
    // 5. Verificar instalação
    // Confirmar que tabelas cp_* foram criadas
    
    return true;
}
```

**Módulos Disponíveis:**

```php
$modulePaths = [
    'price_basket' => base_path('../modulos/cestadeprecos'),
    'nf' => base_path('../modulos/nfe'),
];
```

---

## 3. FLUXO DE AUTENTICAÇÃO

### 3.1 Login Tenant-Specific

```
┌──────────────────────────────────────────────────────────┐
│  1. ACESSO INICIAL                                        │
├──────────────────────────────────────────────────────────┤
│  Usuário acessa: https://pirapora.dattapro.online        │
│                                                           │
│  ↓ Caddy detecta subdomínio                              │
│  ↓ Adiciona headers:                                     │
│    X-Tenant-Domain: pirapora.dattapro.online            │
│    X-Original-Host: pirapora.dattapro.online            │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  2. MIDDLEWARES (MinhaDattaTech)                         │
├──────────────────────────────────────────────────────────┤
│  DynamicSessionDomain:                                   │
│  - Define SESSION_DOMAIN = null (cookie isolado)         │
│                                                           │
│  DetectTenant:                                           │
│  - Extrai subdomínio: "pirapora"                         │
│  - Busca no banco central:                               │
│    SELECT * FROM tenants WHERE subdomain = 'pirapora'    │
│  - Armazena: session(['current_tenant' => $tenant])      │
│                                                           │
│  TenantResolver:                                         │
│  - Verifica tenant ativo                                 │
│  - Configura contexto da aplicação                       │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  3. TELA DE LOGIN                                         │
├──────────────────────────────────────────────────────────┤
│  AuthController::showLogin()                             │
│                                                           │
│  Exibe formulário:                                       │
│  ┌────────────────────────────────┐                     │
│  │  Login - Pirapora               │                     │
│  │                                 │                     │
│  │  Usuário: [admin_______]       │                     │
│  │  Senha:   [********]            │                     │
│  │                                 │                     │
│  │  [Entrar]                       │                     │
│  └────────────────────────────────┘                     │
│                                                           │
│  Nota: Input = "username" (não "email")                  │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  4. PROCESSAMENTO DO LOGIN                               │
├──────────────────────────────────────────────────────────┤
│  AuthController::login()                                 │
│                                                           │
│  Input: username = "admin"                               │
│         password = "senha123"                            │
│                                                           │
│  Passos:                                                 │
│  1. Identifica contexto tenant:                          │
│     $tenant = session('current_tenant')  // pirapora     │
│                                                           │
│  2. Constrói email completo:                             │
│     admin → admin@pirapora.dattapro.online              │
│                                                           │
│  3. TenantAuthService::authenticateInTenant():           │
│     a) Conecta no banco: pirapora_db                     │
│        config(['database.connections.tenant_auth' => [   │
│            'database' => 'pirapora_db'                   │
│        ]]);                                              │
│                                                           │
│     b) Busca usuário:                                    │
│        $user = User::on('tenant_auth')                   │
│                    ->where('email', $email)              │
│                    ->first();                            │
│                                                           │
│     c) Verifica senha:                                   │
│        Hash::check($password, $user->password)           │
│                                                           │
│  4. Se válido, cria sessão:                              │
│     session([                                            │
│         'user_id' => 15,                                 │
│         'user_email' => 'admin@pirapora.dattapro.online',│
│         'tenant_id' => 3,         ← VALIDADO             │
│         'tenant_subdomain' => 'pirapora',                │
│         'tenant_database' => 'pirapora_db',              │
│         'authenticated' => true                          │
│     ]);                                                  │
│                                                           │
│  5. Salva sessão:                                        │
│     $request->session()->save();                         │
│                                                           │
│  6. Redireciona:                                         │
│     return redirect('/desktop');                         │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  5. ACESSO SUBSEQUENTE                                    │
├──────────────────────────────────────────────────────────┤
│  TenantAuthMiddleware:                                   │
│                                                           │
│  VALIDAÇÃO CRÍTICA DE SEGURANÇA:                         │
│  ┌────────────────────────────────────────────────────┐ │
│  │  $currentTenant = session('current_tenant');       │ │
│  │  // Tenant detectado pelo domínio                  │ │
│  │                                                     │ │
│  │  $sessionTenantId = session('tenant_id');          │ │
│  │  // Tenant armazenado no login                     │ │
│  │                                                     │ │
│  │  if ($sessionTenantId !== $currentTenant->id) {    │ │
│  │      // 🚨 BLOQUEIO CROSS-TENANT!                  │ │
│  │      Auth::logout();                               │ │
│  │      session()->flush();                           │ │
│  │      return redirect()->route('login');            │ │
│  │  }                                                  │ │
│  └────────────────────────────────────────────────────┘ │
│                                                           │
│  Se válido:                                              │
│  - Reconstrói User object da sessão                      │
│  - Define Auth::setUser($user)                           │
│  - Permite acesso                                        │
└──────────────────────────────────────────────────────────┘
```

---

### 3.2 Login Universal

```
┌──────────────────────────────────────────────────────────┐
│  1. ACESSO INICIAL                                        │
├──────────────────────────────────────────────────────────┤
│  Usuário acessa: https://minha.dattatech.com.br/login   │
│                                                           │
│  Middlewares detectam:                                   │
│  - Host = minha.dattatech.com.br                         │
│  - NÃO é subdomínio .dattapro.online                     │
│  - current_tenant = null                                 │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  2. TELA DE LOGIN                                         │
├──────────────────────────────────────────────────────────┤
│  AuthController::showLogin()                             │
│                                                           │
│  Exibe formulário:                                       │
│  ┌────────────────────────────────┐                     │
│  │  Login - MinhaDattaTech         │                     │
│  │                                 │                     │
│  │  Email: [admin@pirapora.dattapro.online]             │
│  │  Senha: [********]              │                     │
│  │                                 │                     │
│  │  [Entrar]                       │                     │
│  └────────────────────────────────┘                     │
│                                                           │
│  Nota: Input = "email" (email completo com domínio)      │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  3. PROCESSAMENTO DO LOGIN                               │
├──────────────────────────────────────────────────────────┤
│  AuthController::login()                                 │
│                                                           │
│  Input: email = "admin@pirapora.dattapro.online"         │
│         password = "senha123"                            │
│                                                           │
│  Passos:                                                 │
│  1. Identifica que é login universal:                    │
│     $currentTenant = null                                │
│                                                           │
│  2. Extrai domínio do email:                             │
│     pirapora.dattapro.online                            │
│                                                           │
│  3. TenantAuthService::findTenantByEmail():              │
│     SELECT * FROM tenants                                │
│     WHERE primary_domain = 'pirapora.dattapro.online'    │
│        OR subdomain = 'pirapora'                         │
│                                                           │
│     Resultado: Tenant pirapora encontrado                │
│                                                           │
│  4. Autentica no banco do tenant:                        │
│     TenantAuthService::authenticateInTenant(             │
│         $tenant,    // pirapora                          │
│         $email,     // admin@pirapora.dattapro.online    │
│         $password                                        │
│     )                                                    │
│                                                           │
│  5. Cria sessão (igual ao tenant-specific)               │
│                                                           │
│  6. REDIRECIONA PARA O DOMÍNIO DO TENANT:                │
│     return redirect('https://pirapora.dattapro.online/desktop');│
│                                                           │
│     Por quê?                                             │
│     - Cookie está em minha.dattatech.com.br              │
│     - Sessão não é compartilhada com pirapora.dattapro.online│
│     - Precisa recriar sessão no domínio correto          │
└──────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────┐
│  4. REDIRECIONAMENTO                                      │
├──────────────────────────────────────────────────────────┤
│  Browser navega para:                                    │
│  https://pirapora.dattapro.online/desktop               │
│                                                           │
│  DetectTenant middleware:                                │
│  - Detecta tenant: pirapora                              │
│  - Verifica sessão existente...                          │
│                                                           │
│  ⚠️ PROBLEMA: Sessão estava em minha.dattatech.com.br    │
│              Cookie não existe em pirapora.dattapro.online│
│                                                           │
│  SOLUÇÃO (já implementada):                              │
│  - Sessão é recriada automaticamente                     │
│  - Dados do tenant são carregados da primeira sessão     │
│  - TenantAuthMiddleware valida e reconstrói User         │
└──────────────────────────────────────────────────────────┘
```

---

## 4. ISOLAMENTO DE DADOS

### 4.1 Isolamento por Banco de Dados

**Princípio:** Cada tenant tem seu **próprio banco de dados PostgreSQL**.

**Tenants Existentes:**

| ID | Subdomain | Database Name | Company Name | Status |
|----|-----------|---------------|--------------|--------|
| 1 | catasaltas | catasaltas_db | Catas Altas | active |
| 2 | novaroma | novaroma_db | Nova Roma | active |
| 3 | pirapora | pirapora_db | Pirapora | active |
| 4 | gurupi | gurupi_db | Gurupi | active |
| 5 | novalaranjeiras | novalaranjeiras_db | Nova Laranjeiras | active |
| 6 | dattatech | dattatech_db | DattaTech | active |

**Banco Central (MinhaDattaTech):**
- Nome: `minhadattatech_db`
- Contém:
  - Tabela `tenants` (registro de todos os tenants)
  - Tabela `tenant_active_modules`
  - Tabela `users` (apenas usuários do sistema central)
  - Dados globais (CATMAT, CMED, ComprasGov)

**Bancos dos Tenants:**
- Nome: `{subdomain}_db`
- Contém:
  - Tabelas do módulo (com prefixo `cp_`)
  - Usuários específicos do tenant
  - Dados isolados (orçamentos, fornecedores, etc)

---

### 4.2 Estrutura de Tabelas

#### Banco Central (minhadattatech_db)

```
┌─────────────────────────────────────────┐
│  minhadattatech_db                      │
├─────────────────────────────────────────┤
│  - tenants                              │
│  - tenant_active_modules                │
│  - users (sistema central)              │
│  - roles                                │
│  - permissions                          │
│  - module_configurations                │
│  - email_verifications                  │
│  - jobs, failed_jobs                    │
│  - cache, cache_locks                   │
│  - migrations                           │
│                                          │
│  Dados Globais (compartilhados):        │
│  - cp_catmat                            │
│  - cp_medicamentos_cmed                 │
│  - cp_precos_comprasgov                 │
└─────────────────────────────────────────┘
```

#### Banco do Tenant (ex: pirapora_db)

```
┌─────────────────────────────────────────┐
│  pirapora_db                            │
├─────────────────────────────────────────┤
│  Tabelas do Sistema (sem prefixo):      │
│  - users                                │
│  - sessions                             │
│  - cache, cache_locks                   │
│  - migrations                           │
│                                          │
│  Tabelas do Módulo Cesta de Preços:     │
│  - cp_orcamentos                        │
│  - cp_itens_orcamento                   │
│  - cp_fornecedores                      │
│  - cp_fornecedor_itens                  │
│  - cp_orientacoes_tecnicas              │
│  - cp_notificacoes                      │
│  - cp_respostas_cdf                     │
│  - cp_resposta_cdf_itens                │
│  - cp_anexos                            │
│  - cp_contratacoes_similares            │
│  - cp_contratacao_similar_itens         │
│  - cp_contratos_pncp                    │
│  - cp_consultas_pncp_cache              │
│  - cp_historico_precos                  │
│  - cp_historico_buscas_similares        │
│  - cp_catalogo_produtos                 │
│  - cp_coletas_ecommerce                 │
│  - cp_coleta_ecommerce_itens            │
│  - cp_arp_cabecalhos                    │
│  - cp_arp_itens                         │
│  - cp_audit_log_itens                   │
│  - cp_audit_snapshots                   │
│  - cp_checkpoint_importacao             │
│  - cp_lotes                             │
│  - cp_orgaos                            │
│  - cp_cotacoes_externas                 │
│  - cp_contratos_externos                │
│  - cp_itens_contrato_externo            │
│  - cp_licitacon_cache                   │
│  - cp_jobs, cp_failed_jobs              │
│  - cp_sessions                          │
│                                          │
│  TOTAL: ~48 tabelas com prefixo cp_     │
└─────────────────────────────────────────┘
```

---

### 4.3 Conexão Dinâmica

**Como funciona:**

```php
// 1. Tenant identificado
$tenant = session('current_tenant');

// 2. Obter configuração do banco
$dbConfig = $tenant->getDatabaseConfig();
// Retorna:
// [
//     'driver' => 'pgsql',
//     'host' => '127.0.0.1',
//     'database' => 'pirapora_db',
//     'username' => 'minhadattatech_user',
//     'password' => '[senha descriptografada]'
// ]

// 3. Configurar conexão Laravel
config(['database.connections.tenant_dynamic' => $dbConfig]);

// 4. Usar conexão
DB::connection('tenant_dynamic')->table('users')->get();

// OU purgar e reconectar a conexão padrão
DB::purge('pgsql');
config(['database.connections.pgsql' => $dbConfig]);
DB::reconnect('pgsql');

// Agora queries usam o banco do tenant:
User::all();  // SELECT * FROM users (em pirapora_db)
```

---

## 5. SISTEMA DE MÓDULOS

### 5.1 Arquitetura de Módulos

**Conceito:** Aplicações Laravel independentes rodando em portas diferentes.

```
┌────────────────────────────────────────────┐
│  MinhaDattaTech (Sistema Central)          │
│  Porta: 8000                               │
│  Função: Proxy, Autenticação, Gestão       │
└────────────────┬───────────────────────────┘
                 │
                 ├─→ Módulo: Cesta de Preços
                 │   Porta: 8001
                 │   Path: /home/dattapro/modulos/cestadeprecos
                 │
                 ├─→ Módulo: NF-e
                 │   Porta: 8002
                 │   Path: /home/dattapro/modulos/nfe
                 │
                 └─→ Módulo: [Futuro]
                     Porta: 8003
```

---

### 5.2 Registro de Módulos

**Tabela:** `module_configurations` (banco central)

```sql
CREATE TABLE module_configurations (
    id SERIAL PRIMARY KEY,
    module_key VARCHAR(255) UNIQUE,    -- 'price_basket', 'nf'
    name VARCHAR(255),                 -- 'Cesta de Preços'
    description TEXT,
    port INTEGER,                      -- 8001, 8002
    path TEXT,                         -- Path no filesystem
    status VARCHAR(50),                -- active, inactive
    settings JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Dados Exemplo:**

| module_key | name | port | status |
|------------|------|------|--------|
| price_basket | Cesta de Preços | 8001 | active |
| nf | Nota Fiscal Eletrônica | 8002 | active |

---

### 5.3 Ativação por Tenant

**Tabela:** `tenant_active_modules` (banco central)

```sql
CREATE TABLE tenant_active_modules (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES tenants(id),
    module_key VARCHAR(255),
    parent_module_key VARCHAR(255),
    enabled BOOLEAN DEFAULT true,
    settings JSONB,
    activation_date TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Exemplo:**

| tenant_id | module_key | enabled | activation_date |
|-----------|------------|---------|----------------|
| 1 (catasaltas) | price_basket | true | 2025-01-15 |
| 3 (pirapora) | price_basket | true | 2025-02-01 |
| 3 (pirapora) | nf | false | null |

---

### 5.4 Fluxo de Acesso ao Módulo

```
1. Usuário clica em "Cesta de Preços" no desktop
   URL: https://pirapora.dattapro.online/module-proxy/price_basket/

2. ModuleProxyController::proxy()
   - Verifica autenticação: Auth::check()
   - Identifica tenant: session('current_tenant')
   - Verifica acesso ao módulo:
     SELECT * FROM tenant_active_modules
     WHERE tenant_id = 3 AND module_key = 'price_basket' AND enabled = true
   
3. Se autorizado:
   - Obtém config do módulo:
     SELECT port FROM module_configurations WHERE module_key = 'price_basket'
     Resultado: 8001
   
   - Prepara headers:
     X-Tenant-Id: 3
     X-User-Id: 15
     X-DB-Name: pirapora_db
     X-DB-Host: 127.0.0.1
     X-DB-User: minhadattatech_user
     X-DB-Password: [senha]
   
   - Faz requisição HTTP interna:
     GET http://localhost:8001/
   
4. Módulo recebe (ProxyAuth middleware):
   - Lê headers X-DB-*
   - Configura conexão dinâmica:
     config(['database.connections.pgsql' => [
         'database' => 'pirapora_db'
     ]]);
     DB::reconnect('pgsql');
   
   - Autentica usuário:
     $user = User::firstOrCreate(['email' => X-User-Email]);
     Auth::login($user);
   
   - Salva contexto na sessão do módulo
   
5. Módulo processa e retorna HTML

6. ModuleProxyController ajusta HTML:
   - Injeta <base href="/module-proxy/price_basket/">
   - Remove URLs localhost
   - Repassa cookies Set-Cookie
   
7. Browser recebe resposta final
```

---

## 6. BANCOS DE DADOS

### 6.1 Padrão de Nomenclatura

**Banco Central:**
- Nome: `minhadattatech_db`
- Owner: `minhadattatech_user`
- Charset: `SQL_ASCII` (compatibilidade legacy)

**Bancos de Tenants:**
- Padrão: `{subdomain}_db`
- Exemplos:
  - `catasaltas_db`
  - `novaroma_db`
  - `pirapora_db`
  - `gurupi_db`
  - `novalaranjeiras_db`
  - `dattatech_db`

**Usuários de Banco:**
- Sistema Central: `minhadattatech_user`
- Tenants: Diferentes owners
  - `catasaltas_user` (banco catasaltas_db)
  - `dattatech_user` (banco dattatech_db)
  - Alguns usam `minhadattatech_user`

---

### 6.2 Credenciais Armazenadas

**Tabela `tenants`:**

```php
protected $fillable = [
    'database_name',         // Ex: pirapora_db
    'db_host',               // Ex: 127.0.0.1
    'db_user',               // Ex: minhadattatech_user
    'db_password_encrypted'  // Criptografado com encrypt()
];
```

**Criptografia:**

```php
// Armazenar
$tenant->db_password_encrypted = encrypt($password);

// Recuperar
$password = decrypt($tenant->db_password_encrypted);
```

---

### 6.3 Criação de Banco para Novo Tenant

**Processo Manual (exemplo):**

```sql
-- 1. Criar banco
CREATE DATABASE novotenant_db
    OWNER minhadattatech_user
    ENCODING 'SQL_ASCII';

-- 2. Conectar ao banco
\c novotenant_db

-- 3. Criar tenant no sistema central
INSERT INTO tenants (
    subdomain,
    database_name,
    db_host,
    db_user,
    db_password_encrypted,
    company_name,
    status
) VALUES (
    'novotenant',
    'novotenant_db',
    '127.0.0.1',
    'minhadattatech_user',
    '[senha criptografada]',
    'Novo Tenant',
    'active'
);

-- 4. Instalar módulos
// Via ModuleInstaller::install($tenant, 'price_basket')
```

---

### 6.4 Prefixo de Tabelas

**Importante:** Tabelas do módulo têm prefixo `cp_` **hardcoded** nos migrations!

```php
// Migration do módulo:
Schema::create('cp_orcamentos', function (Blueprint $table) {
    // ...
});

// NÃO usar config prefix!
config(['database.connections.pgsql.prefix' => 'cp_']);  // ❌ ERRADO

// Tabela já se chama cp_orcamentos explicitamente
```

**Por quê?**
- Permite coexistência de múltiplos módulos no mesmo banco
- `cp_` = Cesta de Preços
- `nf_` = Nota Fiscal (futuro)
- Isolamento lógico dentro do banco físico

---

## 7. PADRÕES E NOMENCLATURAS

### 7.1 Estrutura de URLs

**Portal Central:**
```
https://minha.dattatech.com.br/
https://minha.dattatech.com.br/login
https://minha.dattatech.com.br/admin
https://minha.dattatech.com.br/desktop
```

**Tenants:**
```
https://{subdomain}.dattapro.online/
https://{subdomain}.dattapro.online/login
https://{subdomain}.dattapro.online/desktop
```

**Módulos (via proxy):**
```
https://{subdomain}.dattapro.online/module-proxy/{module}/
https://{subdomain}.dattapro.online/module-proxy/price_basket/
https://{subdomain}.dattapro.online/module-proxy/price_basket/orcamentos/novo
```

**Rotas públicas (CDF, etc):**
```
https://{subdomain}.dattapro.online/module-proxy/price_basket/responder-cdf/{token}
```

---

### 7.2 Variáveis de Sessão

**MinhaDattaTech (após login):**

```php
session([
    // Usuário
    'user_id' => 15,
    'user_email' => 'admin@pirapora.dattapro.online',
    'user_name' => 'Administrador',
    'user_role' => 'admin',
    'authenticated' => true,
    
    // Tenant
    'current_tenant' => $tenantObject,  // Objeto Tenant completo
    'tenant_id' => 3,
    'tenant_subdomain' => 'pirapora',
    'tenant_database' => 'pirapora_db'
]);
```

**Módulo (após ProxyAuth):**

```php
session([
    // Contexto do tenant
    'proxy_tenant' => [
        'id' => 3,
        'subdomain' => 'pirapora',
        'name' => 'Pirapora'
    ],
    
    // Dados do usuário
    'proxy_user_data' => [
        'id' => 15,
        'name' => 'Administrador',
        'email' => 'admin@pirapora.dattapro.online',
        'role' => 'admin'
    ],
    
    // Configuração do banco
    'proxy_db_config' => [
        'database' => 'pirapora_db',
        'host' => '127.0.0.1',
        'username' => 'minhadattatech_user',
        'password' => '[senha]'
    ]
]);
```

---

### 7.3 Headers HTTP

**Caddy → MinhaDattaTech:**

```
X-Tenant-Domain: pirapora.dattapro.online
X-Original-Host: pirapora.dattapro.online
X-Forwarded-For: [IP do cliente]
X-Forwarded-Proto: https
```

**MinhaDattaTech → Módulo:**

```
X-Tenant-Id: 3
X-Tenant-Subdomain: pirapora
X-Tenant-Name: Pirapora
X-User-Id: 15
X-User-Name: Administrador
X-User-Email: admin@pirapora.dattapro.online
X-User-Role: admin
X-Module-Token: [token criptografado]
X-DB-Name: pirapora_db
X-DB-Host: 127.0.0.1
X-DB-User: minhadattatech_user
X-DB-Password: [senha]
X-DB-Prefix: cp_
X-Original-IP: [IP do cliente]
Cookie: [cookies do navegador]
```

---

## 8. SEGURANÇA

### 8.1 Validação Cross-Tenant

**Problema:** Usuário tenta usar sessão de um tenant em outro.

**Exemplo de Ataque:**

```
1. Usuário loga em: pirapora.dattapro.online
   Sessão criada: tenant_id = 3

2. Atacante copia cookie de sessão

3. Atacante acessa: catasaltas.dattapro.online
   Tenta reusar sessão de pirapora

4. BLOQUEIO:
   TenantAuthMiddleware detecta:
   - session('tenant_id') = 3 (pirapora)
   - current_tenant->id = 1 (catasaltas)
   - MISMATCH! 🚨
   
   Ação:
   - Log crítico registrado
   - Auth::logout()
   - session()->flush()
   - Redireciona para login
```

**Implementação TenantAuthMiddleware:**

```php
$currentTenant = session('current_tenant');
$sessionTenantId = session('tenant_id');

if ($sessionTenantId !== $currentTenant->id) {
    Log::critical('Cross-tenant access attempt BLOCKED!', [
        'session_tenant_id' => $sessionTenantId,
        'session_tenant_subdomain' => session('tenant_subdomain'),
        'current_tenant_id' => $currentTenant->id,
        'current_tenant_subdomain' => $currentTenant->subdomain,
        'user_email' => session('user_email'),
        'user_ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'requested_url' => $request->fullUrl()
    ]);
    
    Auth::logout();
    session()->flush();
    
    return redirect()->route('login')
        ->withErrors(['session' => 'Sessão inválida. Faça login novamente.']);
}
```

**Implementação ProxyAuth (módulo):**

```php
$currentTenantId = $request->header('X-Tenant-Id');
$sessionTenantId = session('proxy_tenant.id');

if ($currentTenantId && $sessionTenantId && $currentTenantId != $sessionTenantId) {
    Log::critical('ProxyAuth: Cross-tenant access attempt BLOCKED!', [
        'session_tenant_id' => $sessionTenantId,
        'session_tenant_db' => session('proxy_db_config.database'),
        'current_tenant_id' => $currentTenantId,
        'current_tenant_db' => $request->header('X-DB-Name'),
        'user_email' => session('proxy_user_data.email')
    ]);
    
    session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);
    // Forçar reautenticação via headers
}
```

---

### 8.2 Isolamento de Cookies

**DynamicSessionDomain:**

```php
// minha.dattatech.com.br
config(['session.domain' => null]);  // Cookie: minhadattatech_session

// pirapora.dattapro.online
config(['session.domain' => null]);  // Cookie: minhadattatech_session_v2

// catasaltas.dattapro.online
config(['session.domain' => null]);  // Cookie: minhadattatech_session_v2
```

**Resultado:**
- Cada domínio tem seu próprio cookie
- Browser não envia cookie de pirapora para catasaltas
- Isolamento automático pelo browser

---

### 8.3 Criptografia de Senhas

**Senhas de Usuários:**

```php
// Registro
$user->password = Hash::make($password);

// Validação
Hash::check($inputPassword, $user->password)
```

**Senhas de Banco:**

```php
// Armazenar
$tenant->db_password_encrypted = encrypt($password);

// Usar
$password = decrypt($tenant->db_password_encrypted);
```

---

### 8.4 Validação de Módulos

**ModuleProxyController:**

```php
// 1. Verifica autenticação
if (!Auth::check()) {
    return response('Não autenticado', 401);
}

// 2. Verifica se módulo existe
$moduleConfig = ModuleConfiguration::findByKey($module);
if (!$moduleConfig) {
    return response('Módulo não encontrado', 404);
}

// 3. Verifica se usuário tem acesso
if (!$this->userHasModuleAccess($module)) {
    return response('Acesso negado ao módulo', 403);
}

// 4. Verifica se tenant tem módulo ativo
$hasAccess = DB::table('tenant_active_modules')
    ->where('tenant_id', $tenant->id)
    ->where('module_key', $module)
    ->where('enabled', true)
    ->exists();
```

---

## 9. DIAGRAMA COMPLETO

### 9.1 Fluxo Completo de Requisição

```
┌────────────────────────────────────────────────────────────────┐
│                        BROWSER                                  │
│  https://pirapora.dattapro.online/module-proxy/price_basket/   │
└──────────────────────┬─────────────────────────────────────────┘
                       │
                       │ HTTPS (443)
                       │
┌──────────────────────▼─────────────────────────────────────────┐
│                    CADDY (Proxy Reverso)                        │
│  - Gerencia SSL/TLS                                             │
│  - Detecta subdomínio: pirapora                                 │
│  - Adiciona headers:                                            │
│    X-Tenant-Domain: pirapora.dattapro.online                   │
│    X-Original-Host: pirapora.dattapro.online                   │
└──────────────────────┬─────────────────────────────────────────┘
                       │
                       │ HTTP (8000)
                       │
┌──────────────────────▼─────────────────────────────────────────┐
│              MINHADATTATECH (Laravel - Porta 8000)              │
│                                                                 │
│  MIDDLEWARES (ordem de execução):                               │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 1. DynamicSessionDomain                                 │   │
│  │    → SESSION_DOMAIN = null (cookie isolado)             │   │
│  └─────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 2. DetectTenant                                         │   │
│  │    → Extrai subdomínio: "pirapora"                      │   │
│  │    → Busca tenant no banco central                      │   │
│  │    → session(['current_tenant' => $tenant])             │   │
│  └─────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 3. TenantResolver                                       │   │
│  │    → Verifica tenant ativo                              │   │
│  │    → Configura app context                              │   │
│  └─────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 4. TenantAuthMiddleware                                 │   │
│  │    → Valida cross-tenant (SEGURANÇA)                    │   │
│  │    → Reconstrói User da sessão                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  CONTROLLER:                                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ModuleProxyController::proxy()                          │   │
│  │                                                          │   │
│  │ 1. Verifica autenticação                                │   │
│  │ 2. Identifica tenant: $tenant = session('current_tenant')│   │
│  │ 3. Obtém config do banco: $tenant->getDatabaseConfig()  │   │
│  │    Resultado: [                                         │   │
│  │       'database' => 'pirapora_db',                      │   │
│  │       'host' => '127.0.0.1',                            │   │
│  │       'username' => 'minhadattatech_user',              │   │
│  │       'password' => '[senha]'                           │   │
│  │    ]                                                    │   │
│  │                                                          │   │
│  │ 4. Prepara headers para o módulo:                       │   │
│  │    X-Tenant-Id: 3                                       │   │
│  │    X-Tenant-Subdomain: pirapora                         │   │
│  │    X-User-Id: 15                                        │   │
│  │    X-User-Email: admin@pirapora.dattapro.online         │   │
│  │    X-DB-Name: pirapora_db                               │   │
│  │    X-DB-Host: 127.0.0.1                                 │   │
│  │    X-DB-User: minhadattatech_user                       │   │
│  │    X-DB-Password: [senha]                               │   │
│  │    X-DB-Prefix: cp_                                     │   │
│  │                                                          │   │
│  │ 5. Faz requisição HTTP interna:                         │   │
│  │    Http::withHeaders($headers)                          │   │
│  │        ->get('http://localhost:8001/')                  │   │
│  └─────────────────────────────────────────────────────────┘   │
└──────────────────────┬─────────────────────────────────────────┘
                       │
                       │ HTTP Interno (localhost:8001)
                       │
┌──────────────────────▼─────────────────────────────────────────┐
│          MÓDULO CESTA DE PREÇOS (Laravel - Porta 8001)          │
│                                                                 │
│  MIDDLEWARE:                                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ProxyAuth                                               │   │
│  │                                                          │   │
│  │ 1. Lê headers X-DB-*                                    │   │
│  │                                                          │   │
│  │ 2. Configura conexão dinâmica:                          │   │
│  │    config(['database.connections.pgsql' => [            │   │
│  │        'driver' => 'pgsql',                             │   │
│  │        'database' => 'pirapora_db',                     │   │
│  │        'host' => '127.0.0.1',                           │   │
│  │        'username' => 'minhadattatech_user',             │   │
│  │        'password' => '[senha]',                         │   │
│  │        'prefix' => ''  // Sem prefixo! Tabelas = cp_*   │   │
│  │    ]]);                                                 │   │
│  │                                                          │   │
│  │    DB::purge('pgsql');                                  │   │
│  │    DB::reconnect('pgsql');                              │   │
│  │                                                          │   │
│  │ 3. Validação cross-tenant (SEGURANÇA):                  │   │
│  │    if (session_tenant != header_tenant) {               │   │
│  │        Log::critical('Cross-tenant blocked!');          │   │
│  │        session()->forget([...]);                        │   │
│  │    }                                                    │   │
│  │                                                          │   │
│  │ 4. Autentica usuário:                                   │   │
│  │    $user = User::firstOrCreate(                         │   │
│  │        ['email' => X-User-Email]                        │   │
│  │    );                                                   │   │
│  │    Auth::login($user);                                  │   │
│  │                                                          │   │
│  │ 5. Salva contexto na sessão:                            │   │
│  │    session([                                            │   │
│  │        'proxy_tenant' => [...],                         │   │
│  │        'proxy_user_data' => [...],                      │   │
│  │        'proxy_db_config' => [...]                       │   │
│  │    ]);                                                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  CONTROLLER:                                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ OrcamentoController::index()                            │   │
│  │                                                          │   │
│  │ Query executada:                                        │   │
│  │ Orcamento::all()                                        │   │
│  │                                                          │   │
│  │ SQL gerado:                                             │   │
│  │ SELECT * FROM cp_orcamentos                             │   │
│  │                                                          │   │
│  │ Executado em: pirapora_db                               │   │
│  │                                                          │   │
│  │ Resultado: Apenas orçamentos de Pirapora                │   │
│  └─────────────────────────────────────────────────────────┘   │
└──────────────────────┬─────────────────────────────────────────┘
                       │
┌──────────────────────▼─────────────────────────────────────────┐
│                  POSTGRESQL SERVER                              │
│                                                                 │
│  BANCOS DE DADOS:                                               │
│  ┌───────────────────────────────────────────────────────┐     │
│  │ minhadattatech_db (CENTRAL)                           │     │
│  │ - tenants                                             │     │
│  │ - tenant_active_modules                               │     │
│  │ - users (sistema central)                             │     │
│  └───────────────────────────────────────────────────────┘     │
│  ┌───────────────────────────────────────────────────────┐     │
│  │ catasaltas_db (TENANT 1)                              │     │
│  │ - users (tenant)                                      │     │
│  │ - cp_orcamentos                                       │     │
│  │ - cp_fornecedores                                     │     │
│  │ - ... (48 tabelas cp_*)                               │     │
│  └───────────────────────────────────────────────────────┘     │
│  ┌───────────────────────────────────────────────────────┐     │
│  │ pirapora_db (TENANT 3) ← CONECTADO AGORA              │     │
│  │ - users (tenant)                                      │     │
│  │ - cp_orcamentos                                       │     │
│  │ - cp_fornecedores                                     │     │
│  │ - ... (48 tabelas cp_*)                               │     │
│  └───────────────────────────────────────────────────────┘     │
│  ┌───────────────────────────────────────────────────────┐     │
│  │ gurupi_db (TENANT 4)                                  │     │
│  └───────────────────────────────────────────────────────┘     │
│  ┌───────────────────────────────────────────────────────┐     │
│  │ ... outros tenants ...                                │     │
│  └───────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────┘

RESPOSTA:
────────
HTML gerado pelo módulo →
Ajustado pelo ModuleProxyController →
Retornado ao Caddy →
Entregue ao Browser
```

---

### 9.2 Isolamento de Dados (Visual)

```
┌──────────────────────────────────────────────────────────────┐
│                     ISOLAMENTO FÍSICO                         │
│                                                               │
│  Tenant 1 (Catas Altas):                                     │
│  ┌────────────────────────────────────────────────────┐      │
│  │ Banco: catasaltas_db                               │      │
│  │ ┌────────────────────────────────────────────┐     │      │
│  │ │ Users:                                     │     │      │
│  │ │ - admin@catasaltas.dattapro.online         │     │      │
│  │ │ - user1@catasaltas.dattapro.online         │     │      │
│  │ └────────────────────────────────────────────┘     │      │
│  │ ┌────────────────────────────────────────────┐     │      │
│  │ │ Orçamentos:                                │     │      │
│  │ │ - ORC-2025-001 (Licitação Material)        │     │      │
│  │ │ - ORC-2025-002 (Compra Alimentos)          │     │      │
│  │ └────────────────────────────────────────────┘     │      │
│  └────────────────────────────────────────────────────┘      │
│                                                               │
│  Tenant 3 (Pirapora):                                        │
│  ┌────────────────────────────────────────────────────┐      │
│  │ Banco: pirapora_db                                 │      │
│  │ ┌────────────────────────────────────────────┐     │      │
│  │ │ Users:                                     │     │      │
│  │ │ - admin@pirapora.dattapro.online           │     │      │
│  │ │ - orcamentista@pirapora.dattapro.online    │     │      │
│  │ └────────────────────────────────────────────┘     │      │
│  │ ┌────────────────────────────────────────────┐     │      │
│  │ │ Orçamentos:                                │     │      │
│  │ │ - ORC-2025-050 (Obras Públicas)            │     │      │
│  │ │ - ORC-2025-051 (Equipamentos TI)           │     │      │
│  │ └────────────────────────────────────────────┘     │      │
│  └────────────────────────────────────────────────────┘      │
│                                                               │
│  ❌ IMPOSSÍVEL: Query de pirapora_db ver dados de catasaltas_db│
│  ✅ ISOLAMENTO: Físico (bancos separados)                     │
└──────────────────────────────────────────────────────────────┘
```

---

## 10. CONCLUSÕES

### 10.1 Pontos Fortes da Arquitetura

1. **Isolamento Completo:**
   - Cada tenant tem banco de dados próprio
   - Impossível acesso cross-tenant a nível de SQL
   - Segurança física e lógica

2. **Escalabilidade:**
   - Tenants podem ter bancos em servidores diferentes
   - Módulos independentes (microserviços)
   - Fácil adicionar novos módulos

3. **Flexibilidade:**
   - Login universal ou tenant-specific
   - Configuração individual por tenant
   - Módulos ativáveis/desativáveis por tenant

4. **Segurança:**
   - Validação cross-tenant em múltiplas camadas
   - Cookies isolados por domínio
   - Senhas criptografadas
   - Logs de auditoria críticos

---

### 10.2 Componentes Críticos

**Nunca modificar sem análise:**

1. `TenantAuthMiddleware` - Validação cross-tenant
2. `DynamicSessionDomain` - Isolamento de cookies
3. `ProxyAuth` - Configuração dinâmica de banco
4. `ModuleProxyController` - Headers de banco corretos

**Modificações requerem testes extensivos em:**
- Múltiplos tenants simultâneos
- Tentativas de acesso cross-tenant
- Troca de tenant na mesma sessão
- Sessões simultâneas em múltiplos navegadores

---

### 10.3 Padrões de Banco de Dados

**Sempre seguir:**
- Banco central: `minhadattatech_db`
- Banco tenant: `{subdomain}_db`
- Tabelas módulo: `{prefixo}_*` (ex: `cp_orcamentos`)
- Credenciais criptografadas: `encrypt()`/`decrypt()`

**Migrations de módulos:**
- SEMPRE com prefixo hardcoded
- NUNCA usar config prefix
- Testar instalação em tenant novo

---

### 10.4 Próximos Passos (Recomendações)

1. **Automação de Criação de Tenants:**
   - Interface administrativa
   - Criar banco automaticamente
   - Instalar módulos padrão
   - Criar usuário admin inicial

2. **Monitoramento:**
   - Dashboard de uso por tenant
   - Alertas de tentativas cross-tenant
   - Métricas de performance por banco

3. **Backup:**
   - Sistema automático por tenant
   - Restauração isolada
   - Retenção configurável

4. **Documentação:**
   - Guia de instalação de novos módulos
   - Procedimentos de onboarding de tenants
   - Troubleshooting comum

---

## ESTATÍSTICAS DO ESTUDO

- **Arquivos Analisados:** 25+
- **Middlewares Documentados:** 5
- **Controllers Analisados:** 3
- **Models Documentados:** 3
- **Bancos de Dados Mapeados:** 7
- **Tabelas Identificadas:** 48 (por tenant)
- **Fluxos Documentados:** 4 principais
- **Diagramas Criados:** 6
- **Linhas de Código Analisadas:** 2000+

---

**FIM DO ESTUDO ESPECIALIZADO**

Data de Conclusão: 31 de Outubro de 2025
