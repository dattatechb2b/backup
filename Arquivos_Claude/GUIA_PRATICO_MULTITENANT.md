# GUIA PRÁTICO - OPERAÇÕES MULTITENANT

**Complemento ao Estudo Especializado**  
**Data:** 29/10/2025

---

## 1. COMO ADICIONAR UM NOVO TENANT

### Passo 1: Criar Banco de Dados

```bash
# Conectar como postgres
sudo -u postgres psql

# Criar banco e usuário
CREATE DATABASE santarita_db;
CREATE USER santarita_user WITH PASSWORD 'senha_segura_aqui';
GRANT ALL PRIVILEGES ON DATABASE santarita_db TO santarita_user;
\q
```

### Passo 2: Registrar no Sistema

```php
// Via Tinker ou Seeder
php artisan tinker

use App\Models\Tenant;

$tenant = Tenant::create([
    'subdomain' => 'santarita',
    'company_name' => 'Prefeitura de Santa Rita',
    'database_name' => 'santarita_db',
    'db_host' => '127.0.0.1',
    'db_user' => 'santarita_user',
    'db_password_encrypted' => encrypt('senha_segura_aqui'),
    'status' => 'active',
    'max_users' => 10,
]);
```

### Passo 3: Instalar Módulo Cesta de Preços

```php
// Via ModuleInstaller
use App\Services\ModuleInstaller;

$installer = new ModuleInstaller();
$installer->install($tenant, 'price_basket');

// Ou via Livewire no Technical Panel
// Desktop → Gerenciar Módulos → Ativar "Cesta de Preços"
```

### Passo 4: Configurar DNS/Caddy

```bash
# Adicionar ao Caddyfile
santarita.dattapro.online {
    reverse_proxy localhost:8000
}

# Recarregar Caddy
sudo systemctl reload caddy
```

### Passo 5: Testar

```bash
# Acessar
https://santarita.dattapro.online

# Verificar banco
sudo -u postgres psql -d santarita_db -c "\dt cp_*"
```

---

## 2. COMO MIGRAR UM TENANT PARA OUTRO SERVIDOR

### Cenário: Mover novaroma_db para servidor dedicado

### Passo 1: Backup do Banco Atual

```bash
# Dump do banco
sudo -u postgres pg_dump novaroma_db > /tmp/novaroma_backup.sql

# Copiar para servidor destino
scp /tmp/novaroma_backup.sql user@servidor-destino:/tmp/
```

### Passo 2: Restaurar no Servidor Destino

```bash
# No servidor destino
sudo -u postgres psql -c "CREATE DATABASE novaroma_db;"
sudo -u postgres psql novaroma_db < /tmp/novaroma_backup.sql
```

### Passo 3: Atualizar Registro do Tenant

```php
php artisan tinker

$tenant = Tenant::where('subdomain', 'novaroma')->first();
$tenant->update([
    'db_host' => '192.168.1.50',  // IP do servidor destino
    'db_password_encrypted' => encrypt('nova_senha_se_necessario')
]);
```

### Passo 4: Testar Conectividade

```bash
# Testar conexão do MinhaDattaTech para servidor destino
psql -h 192.168.1.50 -U novaroma_user -d novaroma_db -c "SELECT COUNT(*) FROM cp_orcamentos;"
```

---

## 3. TROUBLESHOOTING COMUM

### Problema: "Cross-tenant access attempt BLOCKED!"

**Causa:** Usuário tentou acessar outro tenant sem reautenticar

**Solução:**
```bash
# Verificar logs
grep "Cross-tenant" /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Limpar sessão do usuário
php artisan tinker
>>> session()->flush()

# Usuário deve fazer logout e login novamente
```

### Problema: "Tenant not identified"

**Causa:** Subdomínio não encontrado ou sessão corrompida

**Solução:**
```php
// Verificar se tenant existe
php artisan tinker
>>> Tenant::where('subdomain', 'pirapora')->first()

// Se não existir, criar registro
>>> Tenant::create([...])

// Verificar DNS
>>> ping pirapora.dattapro.online
```

### Problema: "Connection refused to database"

**Causa:** Banco inativo ou credenciais incorretas

**Solução:**
```bash
# Verificar se banco existe
sudo -u postgres psql -l | grep pirapora_db

# Testar conexão manual
sudo -u postgres psql -d pirapora_db

# Verificar credenciais no tenant
php artisan tinker
>>> $tenant = Tenant::find(3);
>>> $config = $tenant->getDatabaseConfig();
>>> print_r($config);

# Testar conexão programaticamente
>>> $tenant->testDatabaseConnection()  // true/false
```

### Problema: "Tabelas cp_* não existem"

**Causa:** Módulo não instalado no tenant

**Solução:**
```php
// Reinstalar módulo
php artisan tinker
use App\Services\ModuleInstaller;

$tenant = Tenant::where('subdomain', 'pirapora')->first();
$installer = new ModuleInstaller();
$installer->install($tenant, 'price_basket');
```

---

## 4. MONITORAMENTO E MÉTRICAS

### Script de Monitoramento

```bash
#!/bin/bash
# monitor_tenants.sh

echo "=== Status dos Tenants ==="
echo ""

# Lista de bancos
DATABASES=("catasaltas_db" "novaroma_db" "pirapora_db" "gurupi_db" "novalaranjeiras_db" "dattatech_db")

for db in "${DATABASES[@]}"; do
    echo "Tenant: $db"
    
    # Tamanho do banco
    SIZE=$(sudo -u postgres psql -d "$db" -t -c "SELECT pg_size_pretty(pg_database_size('$db'));")
    echo "  Tamanho: $SIZE"
    
    # Contagem de orçamentos
    ORCAMENTOS=$(sudo -u postgres psql -d "$db" -t -c "SELECT COUNT(*) FROM cp_orcamentos;")
    echo "  Orçamentos: $ORCAMENTOS"
    
    # Conexões ativas
    CONNECTIONS=$(sudo -u postgres psql -d "$db" -t -c "SELECT count(*) FROM pg_stat_activity WHERE datname='$db';")
    echo "  Conexões ativas: $CONNECTIONS"
    
    echo ""
done
```

### Executar Monitoramento

```bash
chmod +x monitor_tenants.sh
./monitor_tenants.sh
```

### Saída Esperada

```
=== Status dos Tenants ===

Tenant: catasaltas_db
  Tamanho: 12 MB
  Orçamentos: 8
  Conexões ativas: 2

Tenant: novaroma_db
  Tamanho: 45 MB
  Orçamentos: 63
  Conexões ativas: 5
...
```

---

## 5. BACKUP E RESTORE

### Script de Backup Automatizado

```bash
#!/bin/bash
# backup_tenants.sh

BACKUP_DIR="/backup/tenants"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

DATABASES=("catasaltas_db" "novaroma_db" "pirapora_db" "gurupi_db" "novalaranjeiras_db" "dattatech_db")

for db in "${DATABASES[@]}"; do
    echo "Backing up $db..."
    sudo -u postgres pg_dump "$db" | gzip > "$BACKUP_DIR/${db}_${DATE}.sql.gz"
    echo "✓ $db backed up"
done

# Limpar backups antigos (> 30 dias)
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete

echo "Backup completo!"
```

### Restaurar de Backup

```bash
# Descompactar
gunzip /backup/tenants/novaroma_db_20251029_120000.sql.gz

# Dropar banco atual (CUIDADO!)
sudo -u postgres psql -c "DROP DATABASE novaroma_db;"

# Recriar e restaurar
sudo -u postgres psql -c "CREATE DATABASE novaroma_db;"
sudo -u postgres psql novaroma_db < /backup/tenants/novaroma_db_20251029_120000.sql
```

---

## 6. PERFORMANCE E OTIMIZAÇÃO

### Índices Recomendados

```sql
-- Em cada banco de tenant

-- Orçamentos
CREATE INDEX idx_cp_orcamentos_status ON cp_orcamentos(status);
CREATE INDEX idx_cp_orcamentos_created_at ON cp_orcamentos(created_at DESC);
CREATE INDEX idx_cp_orcamentos_numero ON cp_orcamentos(numero);

-- Fornecedores
CREATE INDEX idx_cp_fornecedores_cnpj ON cp_fornecedores(cnpj);
CREATE INDEX idx_cp_fornecedores_ativo ON cp_fornecedores(ativo);

-- Itens de orçamento
CREATE INDEX idx_cp_orcamento_itens_orcamento_id ON cp_orcamento_itens(orcamento_id);
CREATE INDEX idx_cp_orcamento_itens_descricao ON cp_orcamento_itens(descricao);
```

### Vacuum e Analyze

```bash
# Executar em cada banco periodicamente
sudo -u postgres psql -d novaroma_db -c "VACUUM ANALYZE;"
```

### Monitorar Queries Lentas

```sql
-- Habilitar log de queries lentas (> 1s)
ALTER DATABASE novaroma_db SET log_min_duration_statement = 1000;

-- Ver queries lentas nos logs
tail -f /var/log/postgresql/postgresql-*.log | grep "duration:"
```

---

## 7. SEGURANÇA

### Rotação de Senhas

```php
// Trocar senha do banco de um tenant
php artisan tinker

$tenant = Tenant::where('subdomain', 'pirapora')->first();

// Gerar nova senha forte
$novaSenha = Str::random(32);

// Atualizar no PostgreSQL
DB::statement("ALTER USER pirapora_user WITH PASSWORD '{$novaSenha}'");

// Atualizar no registro do tenant
$tenant->update([
    'db_password_encrypted' => encrypt($novaSenha)
]);

echo "Senha rotacionada com sucesso!";
```

### Auditoria de Acessos

```bash
# Ver logs de autenticação
grep "ProxyAuth: Autenticação via proxy" /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Filtrar por tenant específico
grep "tenant_subdomain.*pirapora" /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Ver tentativas de cross-tenant
grep "Cross-tenant access attempt BLOCKED" /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log
```

---

## 8. TESTES DE ISOLAMENTO

### Script de Teste

```php
<?php
// test_tenant_isolation.php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

// Teste 1: Verificar isolamento de dados
$pirapora = Tenant::where('subdomain', 'pirapora')->first();
$novaroma = Tenant::where('subdomain', 'novaroma')->first();

// Configurar para Pirapora
$configPirapora = $pirapora->getDatabaseConfig();
config(['database.connections.tenant_test' => $configPirapora]);
DB::purge('tenant_test');

$countPirapora = DB::connection('tenant_test')->table('cp_orcamentos')->count();
echo "Orçamentos Pirapora: $countPirapora\n";

// Configurar para Nova Roma
$configNovaRoma = $novaroma->getDatabaseConfig();
config(['database.connections.tenant_test' => $configNovaRoma]);
DB::purge('tenant_test');
DB::reconnect('tenant_test');

$countNovaRoma = DB::connection('tenant_test')->table('cp_orcamentos')->count();
echo "Orçamentos Nova Roma: $countNovaRoma\n";

// Validar
if ($countPirapora != $countNovaRoma) {
    echo "✓ SUCESSO: Dados isolados entre tenants!\n";
} else {
    echo "✗ FALHA: Dados podem estar vazando!\n";
}
```

---

## 9. CHECKLIST DE ONBOARDING

### Novo Tenant - Lista de Verificação

- [ ] Criar banco PostgreSQL
- [ ] Criar usuário com senha forte
- [ ] Registrar tenant na tabela `tenants`
- [ ] Criptografar senha no registro
- [ ] Instalar módulo Cesta de Preços
- [ ] Verificar criação de tabelas `cp_*`
- [ ] Configurar DNS/Caddy
- [ ] Testar acesso via subdomínio
- [ ] Criar usuário administrador inicial
- [ ] Importar dados iniciais (se houver)
- [ ] Configurar brasão da prefeitura
- [ ] Testar criação de orçamento
- [ ] Validar isolamento de dados
- [ ] Adicionar ao script de backup
- [ ] Documentar credenciais (cofre seguro)
- [ ] Notificar cliente sobre acesso

---

## 10. COMANDOS RÁPIDOS

### PostgreSQL

```bash
# Listar bancos
sudo -u postgres psql -l

# Conectar em banco
sudo -u postgres psql -d pirapora_db

# Ver tabelas
\dt cp_*

# Contar registros
SELECT COUNT(*) FROM cp_orcamentos;

# Ver tamanho do banco
SELECT pg_size_pretty(pg_database_size('pirapora_db'));

# Ver conexões ativas
SELECT * FROM pg_stat_activity WHERE datname='pirapora_db';
```

### Laravel

```bash
# Listar tenants
php artisan tinker
>>> Tenant::all(['id', 'subdomain', 'company_name'])

# Testar conexão de tenant
>>> $tenant = Tenant::find(3);
>>> $tenant->testDatabaseConnection()

# Ver config de banco
>>> $tenant->getDatabaseConfig()

# Instalar módulo
>>> app(ModuleInstaller::class)->install($tenant, 'price_basket')
```

### Logs

```bash
# Tail logs em tempo real
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Filtrar por tenant
grep "pirapora" /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Ver erros apenas
grep "ERROR" /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Limpar logs antigos
> /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log
```

---

## 11. CENÁRIOS DE ERRO E RECUPERAÇÃO

### Cenário 1: Banco Corrompido

**Sintomas:** Erros ao acessar dados, queries travando

**Recuperação:**
```bash
# 1. Backup imediato
sudo -u postgres pg_dump novaroma_db > /tmp/emergency_backup.sql

# 2. Reindexar
sudo -u postgres psql -d novaroma_db -c "REINDEX DATABASE novaroma_db;"

# 3. Vacuum full
sudo -u postgres psql -d novaroma_db -c "VACUUM FULL;"

# 4. Se não resolver, restore de backup
sudo -u postgres psql -d novaroma_db < /backup/tenants/novaroma_db_20251028.sql
```

### Cenário 2: Credenciais Perdidas

**Sintomas:** "password authentication failed"

**Recuperação:**
```bash
# 1. Resetar senha no PostgreSQL
sudo -u postgres psql -c "ALTER USER novaroma_user WITH PASSWORD 'nova_senha_temporaria';"

# 2. Atualizar no tenant
php artisan tinker
>>> $tenant = Tenant::where('subdomain', 'novaroma')->first();
>>> $tenant->update(['db_password_encrypted' => encrypt('nova_senha_temporaria')]);

# 3. Testar conexão
>>> $tenant->fresh()->testDatabaseConnection()  // deve retornar true
```

### Cenário 3: Migrations Falharam

**Sintomas:** Tabelas faltando, estrutura incompleta

**Recuperação:**
```php
php artisan tinker
use App\Services\ModuleInstaller;

$tenant = Tenant::where('subdomain', 'pirapora')->first();
$installer = new ModuleInstaller();

try {
    $installer->install($tenant, 'price_basket');
    echo "✓ Reinstalado com sucesso!";
} catch (\Exception $e) {
    echo "✗ Erro: " . $e->getMessage();
}
```

---

## 12. REFERÊNCIA RÁPIDA

### URLs Importantes

```
Portal Universal:    https://minha.dattatech.com.br
Technical Panel:     https://minha.dattatech.com.br/technical
Catas Altas:         https://catasaltas.dattapro.online
Nova Roma:           https://novaroma.dattapro.online
Pirapora:            https://pirapora.dattapro.online
```

### Portas

```
MinhaDattaTech:      8000
Cesta de Preços:     8001
PostgreSQL:          5432
Redis:               6379
```

### Caminhos

```
MinhaDattaTech:      /home/dattapro/minhadattatech
Cesta de Preços:     /home/dattapro/modulos/cestadeprecos
Logs:                /home/dattapro/modulos/cestadeprecos/storage/logs
Backups:             /backup/tenants
```

---

**FIM DO GUIA PRÁTICO**

Este guia complementa o estudo teórico com procedimentos práticos de operação, manutenção e troubleshooting da arquitetura multitenant.
