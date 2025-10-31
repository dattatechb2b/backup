# TENANTS - Configurações por Cliente

**Data:** 31/10/2025
**Sistema:** Cesta de Preços - Módulo Multi-Tenant
**Versão:** 1.0.0

---

## VISÃO GERAL

Este módulo atende **7 tenants** (clientes) diferentes, todos compartilhando o mesmo código-fonte mas com dados isolados.

### Arquitetura Multi-Tenant

- **Código:** Compartilhado entre todos os tenants
- **Banco de Dados:** `minhadattatech_db` (compartilhado)
- **Isolamento:** Por `tenant_id` nas tabelas
- **Acesso:** Via subdomínio `{tenant}.dattapro.online`
- **Porta:** 8001 (módulo rodando)

---

## TENANT 1: CATAS ALTAS - MG

### Informações Básicas
```
Nome: Prefeitura Municipal de Catas Altas
UF: Minas Gerais
Banco de Dados: catasaltas_db
Subdomínio: catasaltas.dattapro.online
Status: Ativo
```

### Configurações Específicas (.env)
```env
# Tenant: Catas Altas
DB_TENANT_ID=catasaltas
DB_DATABASE=catasaltas_db
APP_NAME="Cesta de Preços - Catas Altas/MG"
APP_URL=https://catasaltas.dattapro.online

# Identificação
TENANT_NOME="Prefeitura Municipal de Catas Altas"
TENANT_UF=MG
TENANT_MUNICIPIO="Catas Altas"
```

### Módulos Ativos
- ✅ Cesta de Preços
- ✅ NFe

---

## TENANT 2: DATTATECH (DESENVOLVIMENTO)

### Informações Básicas
```
Nome: DattaTech - Ambiente de Desenvolvimento
UF: N/A
Banco de Dados: dattatech_db
Subdomínio: dattatech.dattapro.online
Status: Ativo (Desenvolvimento)
```

### Configurações Específicas (.env)
```env
# Tenant: DattaTech (Dev)
DB_TENANT_ID=dattatech
DB_DATABASE=dattatech_db
APP_NAME="Cesta de Preços - DattaTech DEV"
APP_URL=https://dattatech.dattapro.online
APP_ENV=development
APP_DEBUG=true

# Identificação
TENANT_NOME="DattaTech - Desenvolvimento"
TENANT_UF=
TENANT_MUNICIPIO=
```

### Módulos Ativos
- ✅ Cesta de Preços
- ✅ NFe
- ✅ Todos (ambiente de testes)

---

## TENANT 3: GURUPI - TO

### Informações Básicas
```
Nome: Prefeitura Municipal de Gurupi
UF: Tocantins
Banco de Dados: gurupi_db
Subdomínio: gurupi.dattapro.online
Status: Ativo
```

### Configurações Específicas (.env)
```env
# Tenant: Gurupi
DB_TENANT_ID=gurupi
DB_DATABASE=gurupi_db
APP_NAME="Cesta de Preços - Gurupi/TO"
APP_URL=https://gurupi.dattapro.online

# Identificação
TENANT_NOME="Prefeitura Municipal de Gurupi"
TENANT_UF=TO
TENANT_MUNICIPIO="Gurupi"
```

### Módulos Ativos
- ✅ Cesta de Preços
- ✅ NFe

---

## TENANT 4: NOVA LARANJEIRAS - PR

### Informações Básicas
```
Nome: Prefeitura Municipal de Nova Laranjeiras
UF: Paraná
Banco de Dados: novalaranjeiras_db
Subdomínio: novalaranjeiras.dattapro.online
Status: Ativo
```

### Configurações Específicas (.env)
```env
# Tenant: Nova Laranjeiras
DB_TENANT_ID=novalaranjeiras
DB_DATABASE=novalaranjeiras_db
APP_NAME="Cesta de Preços - Nova Laranjeiras/PR"
APP_URL=https://novalaranjeiras.dattapro.online

# Identificação
TENANT_NOME="Prefeitura Municipal de Nova Laranjeiras"
TENANT_UF=PR
TENANT_MUNICIPIO="Nova Laranjeiras"
```

### Módulos Ativos
- ✅ Cesta de Preços
- ✅ NFe

---

## TENANT 5: NOVA ROMA - GO

### Informações Básicas
```
Nome: Prefeitura Municipal de Nova Roma
UF: Goiás
Banco de Dados: novaroma_db
Subdomínio: novaroma.dattapro.online
Status: Ativo
```

### Configurações Específicas (.env)
```env
# Tenant: Nova Roma
DB_TENANT_ID=novaroma
DB_DATABASE=novaroma_db
APP_NAME="Cesta de Preços - Nova Roma/GO"
APP_URL=https://novaroma.dattapro.online

# Identificação
TENANT_NOME="Prefeitura Municipal de Nova Roma"
TENANT_UF=GO
TENANT_MUNICIPIO="Nova Roma"
```

### Módulos Ativos
- ✅ Cesta de Preços
- ✅ NFe

---

## TENANT 6: PIRAPORA - MG

### Informações Básicas
```
Nome: Prefeitura Municipal de Pirapora
UF: Minas Gerais
Banco de Dados: pirapora_db
Subdomínio: pirapora.dattapro.online
Status: Ativo
```

### Configurações Específicas (.env)
```env
# Tenant: Pirapora
DB_TENANT_ID=pirapora
DB_DATABASE=pirapora_db
APP_NAME="Cesta de Preços - Pirapora/MG"
APP_URL=https://pirapora.dattapro.online

# Identificação
TENANT_NOME="Prefeitura Municipal de Pirapora"
TENANT_UF=MG
TENANT_MUNICIPIO="Pirapora"
```

### Módulos Ativos
- ✅ Cesta de Preços
- ✅ NFe

---

## TENANT 7: MINHADATTATECH (SISTEMA PRINCIPAL)

### Informações Básicas
```
Nome: MinhaDattaTech - Sistema Core
UF: N/A
Banco de Dados: minhadattatech_db
Subdomínio: minha.dattatech.com.br
Status: Ativo (Core)
```

### Configurações Específicas (.env)
```env
# Tenant: Core System
DB_TENANT_ID=minhadattatech
DB_DATABASE=minhadattatech_db
APP_NAME="Minha Datta Tech"
APP_URL=https://minha.dattatech.com.br

# Identificação
TENANT_NOME="MinhaDattaTech - Sistema Principal"
TENANT_UF=
TENANT_MUNICIPIO=
```

### Módulos Ativos
- ✅ Sistema Core (gerenciamento de tenants)
- ✅ Painel administrativo
- ✅ Proxy para módulos

---

## COMO ADICIONAR UM NOVO TENANT

### 1. Criar Banco de Dados

```sql
-- No PostgreSQL como superuser
CREATE DATABASE novotenants_db;
CREATE USER novotenants_user WITH PASSWORD 'senha_forte_aqui';
GRANT ALL PRIVILEGES ON DATABASE novotenants_db TO novotenants_user;
GRANT ALL ON SCHEMA public TO novotenants_user;
```

### 2. Registrar no Sistema Core

```php
// Via MinhaDattaTech Core
use App\Models\Tenant;

Tenant::create([
    'nome' => 'Prefeitura Municipal de Novo Tenant',
    'dominio' => 'novotenant.dattapro.online',
    'banco_dados' => 'novotenants_db',
    'uf' => 'XX',
    'municipio' => 'Novo Tenant',
    'ativo' => true,
]);
```

### 3. Configurar Variáveis de Ambiente

Adicionar ao `.env` do módulo (ou configurar via painel):

```env
# Tenant: Novo Tenant
DB_TENANT_ID=novotenant
DB_DATABASE=novotenants_db
APP_NAME="Cesta de Preços - Novo Tenant/XX"
APP_URL=https://novotenant.dattapro.online
```

### 4. Executar Migrations

```bash
# Rodar migrations no banco do novo tenant
cd /home/dattapro/modulos/cestadeprecos
php artisan migrate --database=novotenant
```

### 5. Ativar Módulos

```php
// Via MinhaDattaTech Core
use App\Models\TenantActiveModule;

TenantActiveModule::create([
    'tenant_id' => $tenant->id,
    'module_name' => 'cestadeprecos',
    'porta' => 8001,
    'ativo' => true,
]);
```

### 6. Configurar Nginx/Caddy

Adicionar configuração de subdomínio:

```nginx
server {
    listen 80;
    server_name novotenant.dattapro.online;

    # Proxy para o core (que faz proxy para o módulo)
    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## ISOLAMENTO DE DADOS

### Estratégia Atual

O sistema utiliza **isolamento por tenant_id** nas tabelas:

```php
// Em todos os models que precisam isolamento
protected static function boot()
{
    parent::boot();

    // Adicionar tenant_id automaticamente
    static::creating(function ($model) {
        if (empty($model->tenant_id)) {
            $model->tenant_id = session('tenant_id');
        }
    });

    // Filtrar por tenant_id automaticamente
    static::addGlobalScope('tenant', function ($builder) {
        if (session('tenant_id')) {
            $builder->where('tenant_id', session('tenant_id'));
        }
    });
}
```

### Tabelas com Isolamento

**Tabelas que DEVEM ter tenant_id:**
- `cp_orcamentos`
- `cp_orcamento_itens`
- `cp_fornecedores`
- `cp_fornecedor_itens`
- `cp_notificacoes`
- `cp_anexos`
- `cp_solicitacoes_cdf`

**Tabelas COMPARTILHADAS (sem tenant_id):**
- `cp_catmat` (catálogo nacional)
- `cp_medicamentos_cmed` (tabela CMED nacional)
- `cp_precos_comprasgov` (preços de mercado nacionais)

---

## CONFIGURAÇÃO DE SESSÕES

### Isolamento por Domínio

Cada tenant tem sessão isolada baseada no domínio:

```env
# Core
SESSION_DOMAIN=.dattapro.online
SESSION_COOKIE=minhadattatech_session_v2

# Módulo Cesta de Preços
SESSION_DOMAIN=.dattapro.online
SESSION_COOKIE=cestadeprecos_session
SESSION_TABLE=cp_sessions
```

### Middleware de Autenticação

O sistema usa `TenantAuthMiddleware.php` para garantir que cada tenant só acesse seus próprios dados:

```php
// app/Http/Middleware/TenantAuthMiddleware.php
public function handle($request, Closure $next)
{
    $tenant = $this->identifyTenant($request);

    if (!$tenant) {
        return redirect()->route('login');
    }

    // Setar tenant_id na sessão
    session(['tenant_id' => $tenant->id]);

    // Configurar conexão do banco (se necessário)
    config(['database.connections.pgsql.database' => $tenant->banco_dados]);

    return $next($request);
}
```

---

## BACKUP E RESTAURAÇÃO POR TENANT

### Backup Individual

```bash
# Backup de um tenant específico
pg_dump -U minhadattatech_user \
    -d catasaltas_db \
    -F custom \
    -f /backups/catasaltas_$(date +%Y%m%d_%H%M%S).dump

# Backup de todos os tenants
for db in catasaltas_db dattatech_db gurupi_db novalaranjeiras_db novaroma_db pirapora_db minhadattatech_db
do
    echo "Backup: $db"
    pg_dump -U minhadattatech_user \
        -d $db \
        -F custom \
        -f /backups/${db}_$(date +%Y%m%d_%H%M%S).dump
done
```

### Restauração Individual

```bash
# Restaurar um tenant específico
pg_restore -U minhadattatech_user \
    -d catasaltas_db \
    -c \
    /backups/catasaltas_20251031_120000.dump
```

---

## MONITORAMENTO POR TENANT

### Logs Separados

Cada tenant pode ter logs identificados:

```php
// No código
Log::channel('tenant')->info('Ação executada', [
    'tenant_id' => session('tenant_id'),
    'tenant_name' => $tenant->nome,
    'action' => 'criar_orcamento',
]);
```

### Métricas

```sql
-- Contar orçamentos por tenant
SELECT
    t.nome AS tenant,
    COUNT(o.id) AS total_orcamentos
FROM tenants t
LEFT JOIN cp_orcamentos o ON o.tenant_id = t.id
GROUP BY t.id, t.nome
ORDER BY total_orcamentos DESC;

-- Espaço usado por tenant (aproximado)
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

---

## SEGURANÇA E ISOLAMENTO

### Checklist de Segurança

- [x] Cada tenant só acessa seus próprios dados (via tenant_id)
- [x] Sessões isoladas por domínio
- [x] Middleware valida tenant em cada requisição
- [x] Queries automáticas filtram por tenant_id
- [x] Logs identificam ações por tenant
- [x] Backups podem ser feitos individualmente
- [x] Senhas e tokens únicos por tenant

### Prevenção de Vazamento de Dados

```php
// SEMPRE usar global scope em models com tenant_id
protected static function booted()
{
    static::addGlobalScope('tenant', function (Builder $query) {
        if ($tenantId = session('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
    });
}

// NUNCA fazer queries diretas sem filtro de tenant
// ❌ ERRADO:
$orcamentos = DB::table('cp_orcamentos')->get();

// ✅ CORRETO:
$orcamentos = DB::table('cp_orcamentos')
    ->where('tenant_id', session('tenant_id'))
    ->get();

// ✅ MELHOR: Usar Eloquent (aplica global scope automaticamente)
$orcamentos = Orcamento::all();
```

---

## RESUMO

| Tenant | Banco | UF | Status | Usuários Est. |
|--------|-------|-----|--------|---------------|
| Catas Altas | catasaltas_db | MG | ✅ Ativo | ~10 |
| DattaTech | dattatech_db | - | ✅ Dev | ~5 |
| Gurupi | gurupi_db | TO | ✅ Ativo | ~15 |
| Nova Laranjeiras | novalaranjeiras_db | PR | ✅ Ativo | ~8 |
| Nova Roma | novaroma_db | GO | ✅ Ativo | ~6 |
| Pirapora | pirapora_db | MG | ✅ Ativo | ~12 |
| MinhaDattaTech | minhadattatech_db | - | ✅ Core | ~3 |

**Total:** 7 tenants ativos
**Total Usuários:** ~59 usuários estimados

---

## SUPORTE

Para adicionar, modificar ou remover tenants, entre em contato com a equipe de desenvolvimento.

**Desenvolvedor Responsável:** [A definir]
**Email:** suporte@dattatech.com.br
**Documentação Completa:** Ver `ESTUDO_COMPLETO_BACKUP_GITHUB.md`

---

**Última Atualização:** 31/10/2025
**Versão do Documento:** 1.0.0
**Autor:** Claude Code (Anthropic)
