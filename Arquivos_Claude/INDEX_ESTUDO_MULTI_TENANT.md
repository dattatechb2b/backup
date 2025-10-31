# ÍNDICE - ESTUDO ARQUITETURA MULTI-TENANT

**Documento Principal:** ESTUDO_ARQUITETURA_MULTI_TENANT_COMPLETO.md

---

## ACESSO RÁPIDO

### Componentes Críticos

1. **Middlewares** (NÃO MODIFICAR sem análise):
   - `TenantAuthMiddleware` - Validação cross-tenant
   - `DynamicSessionDomain` - Isolamento de cookies
   - `DetectTenant` - Detecção do tenant
   - `TenantResolver` - Resolução de contexto

2. **Controllers Principais**:
   - `AuthController` - Login multi-contexto
   - `ModuleProxyController` - Proxy interno

3. **Middlewares de Módulo**:
   - `ProxyAuth` - Configuração dinâmica de banco

---

## BANCOS DE DADOS

### Banco Central
- **Nome:** `minhadattatech_db`
- **Contém:** tenants, tenant_active_modules, users (central)

### Tenants Existentes

| ID | Subdomain | Database | Status |
|----|-----------|----------|--------|
| 1 | catasaltas | catasaltas_db | active |
| 2 | novaroma | novaroma_db | active |
| 3 | pirapora | pirapora_db | active |
| 4 | gurupi | gurupi_db | active |
| 5 | novalaranjeiras | novalaranjeiras_db | active |
| 6 | dattatech | dattatech_db | active |

---

## ESTRUTURA DE URLS

```
Portal Central:
  https://minha.dattatech.com.br

Tenants:
  https://{subdomain}.dattapro.online
  
Módulos (via proxy):
  https://{subdomain}.dattapro.online/module-proxy/{module}/
```

---

## VARIÁVEIS DE SESSÃO IMPORTANTES

### MinhaDattaTech
```php
session('current_tenant')      // Objeto Tenant
session('tenant_id')           // ID validado (CRÍTICO)
session('tenant_subdomain')    // Subdomínio
session('tenant_database')     // Nome do banco
session('user_id')             // ID do usuário
session('authenticated')       // true/false
```

### Módulo
```php
session('proxy_tenant')        // Dados do tenant
session('proxy_user_data')     // Dados do usuário
session('proxy_db_config')     // Config do banco
```

---

## HEADERS HTTP

### Caddy → MinhaDattaTech
```
X-Tenant-Domain: subdomain.dattapro.online
X-Original-Host: subdomain.dattapro.online
```

### MinhaDattaTech → Módulo
```
X-Tenant-Id: 3
X-Tenant-Subdomain: pirapora
X-User-Id: 15
X-User-Email: admin@pirapora.dattapro.online
X-DB-Name: pirapora_db
X-DB-Host: 127.0.0.1
X-DB-User: minhadattatech_user
X-DB-Password: [senha]
X-DB-Prefix: cp_
```

---

## PADRÕES DE NOMENCLATURA

### Bancos de Dados
- Central: `minhadattatech_db`
- Tenant: `{subdomain}_db`

### Tabelas de Módulo
- Prefixo: `cp_` (hardcoded nos migrations)
- Exemplo: `cp_orcamentos`, `cp_fornecedores`

### Usuários de Banco
- Central: `minhadattatech_user`
- Tenants: Variável (alguns usam minhadattatech_user)

---

## FLUXO DE AUTENTICAÇÃO

### Login Tenant-Specific
1. Acesso: subdomain.dattapro.online/login
2. Input: username apenas (ex: "admin")
3. Sistema completa: admin@subdomain.dattapro.online
4. Autentica no banco do tenant
5. Cria sessão com tenant_id validado

### Login Universal
1. Acesso: minha.dattatech.com.br/login
2. Input: email completo (ex: admin@subdomain.dattapro.online)
3. Sistema identifica tenant pelo domínio do email
4. Autentica no banco do tenant
5. Redireciona para: subdomain.dattapro.online/desktop

---

## SEGURANÇA CROSS-TENANT

### Validação TenantAuthMiddleware
```php
if (session('tenant_id') !== current_tenant->id) {
    // BLOQUEIO IMEDIATO
    Auth::logout();
    session()->flush();
    redirect()->route('login');
}
```

### Validação ProxyAuth (Módulo)
```php
if (session_tenant_id != header_tenant_id) {
    Log::critical('Cross-tenant blocked!');
    session()->forget([...]);
    // Reautentica via headers
}
```

---

## MÓDULOS

### Configuração
- **Tabela:** `module_configurations` (banco central)
- **Ativação:** `tenant_active_modules` (banco central)

### Módulos Disponíveis
| module_key | name | port |
|------------|------|------|
| price_basket | Cesta de Preços | 8001 |
| nf | Nota Fiscal Eletrônica | 8002 |

### Instalação de Módulo
```php
ModuleInstaller::install($tenant, 'price_basket');
// Cria tabelas cp_* no banco do tenant
```

---

## TROUBLESHOOTING RÁPIDO

### Problema: Sessão não persiste
- Verificar: SESSION_DOMAIN correto
- Verificar: Cookie não está bloqueado
- Verificar: session()->save() foi chamado

### Problema: Cross-tenant access blocked
- Normal: Sistema funcionando corretamente
- Ação: Usuário deve fazer login no tenant correto
- Causa: Tentativa de reusar sessão entre tenants

### Problema: Módulo não conecta no banco correto
- Verificar: Headers X-DB-* sendo enviados
- Verificar: ProxyAuth configurando conexão
- Verificar: DB::purge() e reconnect() executados

### Problema: Query retorna dados de outro tenant
- CRÍTICO: Verificar qual banco está conectado
- Verificar: config('database.connections.pgsql.database')
- Verificar: Logs do ProxyAuth

---

## ARQUIVOS DE LOCALIZAÇÃO

### Sistema Central (MinhaDattaTech)
```
/home/dattapro/minhadattatech/

app/Http/Middleware/
  - DynamicSessionDomain.php
  - DetectTenant.php
  - TenantResolver.php
  - TenantAuthMiddleware.php

app/Http/Controllers/
  - Auth/AuthController.php
  - ModuleProxyController.php

app/Models/
  - Tenant.php
  - User.php
  - TenantActiveModule.php

app/Services/
  - TenantAuthService.php
  - ModuleInstaller.php

.env
  DB_DATABASE=minhadattatech_db
```

### Módulo Cesta de Preços
```
/home/dattapro/modulos/cestadeprecos/

app/Http/Middleware/
  - ProxyAuth.php

.env
  DB_DATABASE=minhadattatech_db (será sobrescrito)
```

---

## COMANDOS ÚTEIS

### Listar Tenants
```bash
sudo -u postgres psql -d minhadattatech_db \
  -c "SELECT id, subdomain, database_name, status FROM tenants"
```

### Listar Bancos
```bash
sudo -u postgres psql -c "\l" | grep -E "dattatech|tenant|_db"
```

### Ver Tabelas de Tenant
```bash
sudo -u postgres psql -d pirapora_db -c "\dt" | grep cp_
```

### Testar Conexão de Tenant
```php
$tenant = Tenant::find(3);
$connected = $tenant->testDatabaseConnection();
```

---

## PRÓXIMOS PASSOS RECOMENDADOS

1. Automação de criação de tenants
2. Interface administrativa de gestão
3. Sistema de backup por tenant
4. Monitoramento de uso
5. Dashboard de métricas

---

**IMPORTANTE:** Sempre consultar o documento completo antes de modificar componentes críticos.
