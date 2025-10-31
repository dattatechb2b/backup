# Análise da Arquitetura - Módulo Cesta de Preços

## 📋 Situação Atual

### 1. **Estrutura do Módulo**
- Laravel 11 completo e independente
- Localização: `/home/dattapro/modulos/cestadeprecos`
- Rodando em: `http://0.0.0.0:8001` (artisan serve)
- Banco de dados: `cestadeprecos_db` (PostgreSQL)

### 2. **Multi-Tenancy Implementado**
```
Fluxo atual:
1. URL: cestadeprecos.dattapro.online/{subdomain}/dashboard
2. Middleware TenantAuth verifica token
3. Token validado com MinhaDataTech API
4. Dados do tenant armazenados na sessão
```

### 3. **Integração com Desktop**
```
Desktop (MinhaDataTech) → iframe → Módulo Cesta de Preços
   ↓
Envia: subdomain + token
   ↓
Módulo valida e carrega contexto do tenant
```

## 🔴 Problemas Identificados

### 1. **Isolamento de Dados**
- Tabelas usam `client_id` mas não há separação real por tenant
- Risco de vazamento de dados entre tenants
- Sem prefixo de schema ou database por tenant

### 2. **Servidor Web**
- Usando `artisan serve` (não adequado para produção)
- Não configurado no Caddy
- Sem domínio próprio configurado

### 3. **Autenticação**
- Token temporário não implementado
- Depende de API externa para cada requisição
- Sem SSO real com MinhaDataTech

## ✅ Proposta de Solução

### 1. **Multi-Tenancy Seguro**

#### Opção A: Schema por Tenant (Recomendado)
```sql
-- Cada tenant tem seu próprio schema
CREATE SCHEMA IF NOT EXISTS tenant_catasaltas;
CREATE SCHEMA IF NOT EXISTS tenant_barbacena;

-- Tabelas isoladas por schema
tenant_catasaltas.licitacoes
tenant_catasaltas.fornecedores
tenant_catasaltas.item_licitacoes
```

#### Opção B: Database por Tenant
```php
// Conexão dinâmica baseada no tenant
'connections' => [
    'tenant_catasaltas' => [
        'database' => 'cestadeprecos_catasaltas',
    ],
    'tenant_barbacena' => [
        'database' => 'cestadeprecos_barbacena',
    ]
]
```

### 2. **Integração com Desktop**

#### Autenticação via Token JWT
```php
// MinhaDataTech gera token JWT
$token = JWT::encode([
    'tenant_id' => 3,
    'subdomain' => 'catasaltas',
    'user_id' => 1,
    'exp' => time() + 3600
], $secret);

// Módulo valida localmente
$payload = JWT::decode($token, $secret);
```

#### URL Amigável via Caddy
```
cestadeprecos.dattapro.online {
    reverse_proxy localhost:8001

    header {
        X-Frame-Options "SAMEORIGIN"
        Content-Security-Policy "frame-ancestors 'self' *.dattapro.online"
    }
}
```

### 3. **Estrutura de Deployment**

```
/home/dattapro/modulos/cestadeprecos/
├── app/
│   ├── Services/
│   │   ├── TenantService.php      # Gerencia contexto do tenant
│   │   ├── SchemaService.php      # Troca de schema dinâmica
│   │   └── AuthService.php        # Validação JWT
│   └── Models/
│       └── Traits/
│           └── BelongsToTenant.php # Auto-filtro por tenant
├── config/
│   └── tenants.php                # Configurações multi-tenant
└── database/
    └── migrations/
        └── tenant/                 # Migrations por tenant
```

## 🚀 Próximos Passos

### Fase 1: Segurança (Urgente)
1. [ ] Implementar separação por schema
2. [ ] Adicionar validação JWT local
3. [ ] Criar TenantService para contexto

### Fase 2: Infraestrutura
1. [ ] Configurar no Caddy
2. [ ] Migrar de artisan serve para PHP-FPM
3. [ ] Configurar domínio cestadeprecos.dattapro.online

### Fase 3: Funcionalidades
1. [ ] Implementar CRUD de Licitações
2. [ ] Sistema de análise de preços
3. [ ] Relatórios e dashboards
4. [ ] APIs para integração

## 💭 Decisões Necessárias

1. **Isolamento de dados**: Schema ou Database por tenant?
2. **Autenticação**: JWT local ou continuar com API?
3. **Deploy**: Subdomínio próprio ou path no domínio principal?
4. **Dados**: Migrar dados existentes ou começar limpo?

## 🔧 Comandos Úteis

```bash
# Criar schema para novo tenant
php artisan tenant:create-schema catasaltas

# Rodar migrations em schema específico
php artisan migrate --schema=tenant_catasaltas

# Testar módulo localmente
php artisan serve --port=8001

# Verificar conexão com MinhaDataTech
php artisan tenant:verify-connection catasaltas
```

## 📝 Notas Técnicas

- **Performance**: Cache de validação de token por 1h
- **Segurança**: Sempre validar tenant_id em queries
- **Logs**: Separar logs por tenant
- **Backups**: Estratégia de backup por schema