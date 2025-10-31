# RESUMO EXECUTIVO: ARQUITETURA MULTITENANT

## O QUE É ESTE SISTEMA?

Sistema de **gerenciamento de compras públicas** que atende múltiplas prefeituras (tenants) com **isolamento total de dados**.

```
Curitiba             São Paulo           Brasília
(tenant_id=5)       (tenant_id=6)       (tenant_id=7)
     │                   │                    │
     │                   │                    │
     ▼                   ▼                    ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Bank Próprio │    │ Bank Próprio │    │ Bank Próprio │
│ (isolado)   │    │ (isolado)   │    │ (isolado)   │
└─────────────┘    └─────────────┘    └─────────────┘

Data de Curitiba NUNCA aparece em São Paulo
Data de São Paulo NUNCA aparece em Brasília
Cada prefeitura vê apenas seus dados
```

---

## ESTRUTURA SIMPLIFICADA

### 3 Camadas Principais:

```
┌─────────────────────────────────────┐
│ 1. NAVEGADOR DO USUÁRIO              │
│    curitiba.sistemacompras.gov.br    │
│    (User está autenticado?)           │
└──────────────┬──────────────────────┘
               │
               │ HTTP Request
               │ (/modulo-proxy/cestadeprecos/...)
               ▼
┌─────────────────────────────────────┐
│ 2. SISTEMA CENTRAL (MinhaDataTech)   │
│    - Valida autenticação              │
│    - Detecta tenant pelo domínio      │
│    - Busca credenciais DB do tenant   │
│    - Monta headers X-* com credenciais│
│    - Proxy HTTP para módulo           │
└──────────────┬──────────────────────┘
               │
               │ HTTP Proxy
               │ Headers: X-Tenant-Id, X-DB-Name, X-DB-User, X-DB-Password
               │ Porta: 8001 (cestadeprecos)
               ▼
┌─────────────────────────────────────┐
│ 3. MÓDULO (cestadeprecos)             │
│    - ProxyAuth autentica via headers  │
│    - Configura banco dinamicamente    │
│    - Validação cross-tenant           │
│    - Controller executa no BD correto  │
└──────────────┬──────────────────────┘
               │
               │ Query ao banco
               │ (do tenant correto)
               ▼
        ┌──────────────┐
        │ PostgreSQL   │
        │              │
        ├─ minhadatat. │ (compartilhado)
        ├─ curitiba_db │ (isolado)
        ├─ saopaulo_db │ (isolado)
        └─ brasilia_db │ (isolado)
```

---

## OS 5 CONCEITOS-CHAVE

### 1. BANCOS SEPARADOS POR TENANT

```
Um banco para cada prefeitura:

minhadattatech_db          ← Banco PRINCIPAL (compartilhado)
  └─ cp_catmat               Catálogo de 38.000 materiais
  └─ cp_precos_comprasgov    Preços da API Compras.gov
  └─ cp_medicamentos_cmed    Medicamentos do CMED

prefeitura_curitiba_db     ← Banco EXCLUSIVO de Curitiba
  └─ cp_orcamentos           Orçamentos (50 registros)
  └─ cp_fornecedores         Fornecedores (120 cadastrados)
  └─ ... (50+ tabelas)

prefeitura_saopaulo_db     ← Banco EXCLUSIVO de São Paulo
  └─ cp_orcamentos           Orçamentos (80 registros - DIFERENTE!)
  └─ cp_fornecedores         Fornecedores (200 cadastrados - MAIS!)
  └─ ... (mesma estrutura)
```

**Implicação**: Curitiba nunca vê dados de São Paulo (banco diferente!)

---

### 2. PREFIXO 'cp_' ISOLA MÓDULO

```
Mesmo banco pode ter múltiplas aplicações:

minhadattatech_db
├─ users              ← Sistema MinhaDataTech
├─ tenants            ← Sistema MinhaDataTech
├─ cp_catmat          ← Módulo Cesta de Preços
├─ cp_orcamentos      ← Módulo Cesta de Preços
└─ nf_notas_fiscais   ← Módulo NFe (prefixo 'nf_')

Cada módulo tem seu prefixo:
- cestadeprecos: cp_*
- nfe: nf_*
- crm: crm_*
- technical: t_*
```

**Implicação**: Mesmo se compartilhassem banco, dados estariam isolados

---

### 3. CONFIGURAÇÃO DINÂMICA DE BD

```
A cada requisição HTTP, o banco é RECONFIGURADO:

Requisição 1: Curitiba
┌─────────────────────────────┐
│ Headers HTTP recebidos:      │
│ X-DB-Name: prefeitura_curit. │
│ X-DB-User: tenant_user       │
│ X-DB-Password: senha123      │
└──────────┬──────────────────┘
           │
           ▼ ProxyAuth Middleware
    config['pgsql'] = [
        'database' => 'prefeitura_curitiba_db',
        'username' => 'tenant_user',
        'password' => 'senha123'
    ];
    DB::reconnect('pgsql');
           │
           ▼
    Queries vão para: prefeitura_curitiba_db ✓

Requisição 2 (próximo usuário): São Paulo
┌─────────────────────────────┐
│ Headers HTTP recebidos:      │
│ X-DB-Name: prefeitura_sao.   │
│ X-DB-User: tenant_user       │
│ X-DB-Password: senha456      │
└──────────┬──────────────────┘
           │
           ▼ ProxyAuth Middleware
    config['pgsql'] = [
        'database' => 'prefeitura_saopaulo_db',
        'username' => 'tenant_user',
        'password' => 'senha456'
    ];
    DB::reconnect('pgsql');
           │
           ▼
    Queries vão para: prefeitura_saopaulo_db ✓
```

**Implicação**: Conexão padrão ('pgsql') é reutilizada, mas aponta para BD diferente

---

### 4. MODELOS APONTAM PARA BD CERTO

```
Dois tipos de modelos:

TIPO 1: Tenant-Specific (maioria)
class Orcamento extends Model
{
    protected $table = 'cp_orcamentos';
    // NÃO define $connection
    // Usa 'pgsql' padrão (dinâmico por tenant)
}

QUANDO USAR: $orcamento = Orcamento::all();
├─ Se BD é prefeitura_curitiba_db → retorna orcamentos de Curitiba
└─ Se BD é prefeitura_saopaulo_db → retorna orcamentos de São Paulo

TIPO 2: Compartilhado (poucos)
class Catmat extends Model
{
    protected $connection = 'pgsql_main';  // ← FIXO!
    protected $table = 'cp_catmat';
}

QUANDO USAR: $catmat = Catmat::all();
├─ SEMPRE usa minhadattatech_db (banco principal)
├─ Curitiba vê materiais = São Paulo vê materiais
└─ Sincronização acontece UMA VEZ, beneficia TODOS
```

**Implicação**: Model escolhe banco automaticamente

---

### 5. BLOQUEIO DE CROSS-TENANT

```
Proteção contra User A acessar dados de User B (outro tenant):

Cenário de Ataque:
├─ User A está em Curitiba (tenant_id=5)
├─ session['proxy_tenant.id'] = 5
├─ User A muda domínio para São Paulo (tenant_id=6)
└─ Headers X-Tenant-Id: 6 chegam no módulo

Detecção:
├─ ProxyAuth::handle() recebe X-Tenant-Id: 6
├─ Verifica session['proxy_tenant.id'] = 5
├─ 6 != 5? ❌ MISMATCH!
├─ Log::critical("Cross-tenant access BLOCKED!")
├─ session()->forget([...])
├─ User é deslogado
└─ Redirecionado para /login

Resultado: ✓ User A não consegue acessar dados de São Paulo

Log registrado:
{
  "session_tenant_id": 5,
  "current_tenant_id": 6,
  "user_email": "user@curitiba.gov.br",
  "uri": "/module-proxy/cestadeprecos/orcamentos",
  "timestamp": "2025-10-30 14:32:15"
}
```

**Implicação**: Segurança garantida, cross-tenant é bloqueado

---

## FLUXO SIMPLIFICADO DE UMA REQUISIÇÃO

```
1. User clica em "Novo Orçamento" (em curitiba....)
   │
   ├─ Browser: GET /modulo-proxy/cestadeprecos/orcamentos
   │           Host: curitiba.sistemacompras.gov.br
   │
2. MinhaDataTech recebe
   │
   ├─ Verifica Auth: ✓ User está autenticado
   ├─ Detecta tenant: subdomain='curitiba' → tenant_id=5
   ├─ Busca DB: prefeitura_curitiba_db
   ├─ Monta headers X-*
   │
3. Proxy HTTP para módulo
   │
   ├─ POST localhost:8001/orcamentos
   │  Headers: X-Tenant-Id: 5, X-DB-Name: prefeitura_curitiba_db, ...
   │
4. Módulo recebe
   │
   ├─ ProxyAuth::handle()
   ├─ Valida X-Tenant-Id
   ├─ Configura pgsql para prefeitura_curitiba_db
   ├─ Passa para OrcamentoController
   │
5. Controller executa
   │
   ├─ $orcamentos = Orcamento::all();
   │  Executa: SELECT * FROM cp_orcamentos
   │           NO banco: prefeitura_curitiba_db ✓
   │
6. View renderiza
   │
   ├─ Mostra 50 orçamentos (apenas Curitiba)
   │
7. Browser exibe página
   │
   └─ User vê seus dados (isolados)
```

---

## POR QUÊ DESTA FORMA?

### Isolamento Total (LGPD Compliant)
```
Cada prefeitura tem:
├─ Banco separado
├─ Usuários próprios
├─ Dados confidenciais isolados
└─ Backup independente

Curitiba não consegue ver São Paulo
São Paulo não consegue ver Brasília
```

### Escalabilidade
```
Adicionar novo tenant:
├─ Criar novo banco (5 minutos)
├─ Rodar migrations (2 minutos)
├─ Ativar módulos (1 minuto)
└─ Zero impacto em outros tenants
```

### Performance
```
Banco pequeno = Query rápida
prefeitura_curitiba_db (50 orçamentos) → RÁPIDO
vs
todos_orcamentos_do_brasil (5.000 orçamentos) → LENTO
```

### Compartilhamento Inteligente
```
Dados compartilhados (CATMAT, preços):
├─ Sincronizados UMA VEZ
├─ Beneficia 100+ tenants
└─ Reduz 99% de banda de API
```

---

## COMO NOVOS DESENVOLVEDORES APRENDEM?

### Regra 1: Checar `$connection` do Model
```php
// Este modelo usa qual banco?
class Orcamento extends Model {}  // pgsql (dinâmico)
class Catmat extends Model {
    protected $connection = 'pgsql_main';  // fixo!
}
```

### Regra 2: Migrations Especificam Conexão
```php
// Compartilhado
Schema::connection('pgsql_main')->create('cp_catmat', ...);

// Tenant-specific
Schema::create('cp_orcamentos', ...);  // sem especificar
```

### Regra 3: Models Tenant Usam Prefixo
```php
class Orcamento extends Model {
    protected $table = 'cp_orcamentos';  // com prefixo!
}
```

### Regra 4: Controllers Confiam no Middleware
```php
// ProxyAuth já configurou o BD certo
// Não precisa se preocupar:
$orcamentos = Orcamento::all();  // ✓ banco correto automaticamente
```

---

## CHECKLIST DE IMPLEMENTAÇÃO

```
✓ Nova Feature que acessa dados tenant-specific:
  ├─ Usar Model com prefixo cp_
  ├─ Model NÃO deve definir $connection
  ├─ Confiar que ProxyAuth configurou pgsql
  └─ Testar com múltiplos tenants

✓ Nova Feature que usa dados compartilhados:
  ├─ Usar Model com $connection = 'pgsql_main'
  ├─ Tabela deve ter prefixo cp_
  ├─ Usar Schema::connection('pgsql_main') na migration
  └─ Dados serão iguais para todos os tenants

✓ Migração nova:
  ├─ Especificar connection se for pgsql_main
  ├─ Usar prefixo cp_ nos nomes das tabelas
  ├─ Testar que roda sem erros
  └─ Testar que dados isolam por tenant

✓ Cross-Tenant Security:
  ├─ Log::info com tenant_id em operações sensíveis
  ├─ Validar X-Tenant-Id em controllers críticos
  ├─ Nunca confiar em input do cliente para DB
  └─ Testar tentativa de mudança de tenant
```

---

## DOCUMENTAÇÃO COMPLETA

Consultar:
1. **ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md** - Análise detalhada
2. **DIAGRAMA_MULTITENANT_VISUAL.md** - Diagramas e fluxos
3. **ProxyAuth.php** - Código do middleware (comentado)
4. **Tenant.php** - Modelo de tenant
5. **ModuleProxyController.php** - Proxy inteligente

---

## RESUMO EM UMA FRASE

**Sistema multitenant com isolamento total: cada prefeitura tem seu banco, dados compartilhados em banco principal, segurança garantida por validação cross-tenant no middleware.**

