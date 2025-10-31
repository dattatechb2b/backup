# ÍNDICE - DOCUMENTAÇÃO ARQUITETURA MULTITENANT

**Data de Criação:** 29/10/2025  
**Última Atualização:** 29/10/2025

---

## DOCUMENTOS DISPONÍVEIS

### 📘 1. ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md

**Tipo:** Estudo Teórico Detalhado  
**Tamanho:** ~500 linhas  
**Público:** Desenvolvedores, Arquitetos, DevOps

**Conteúdo:**
- Conceitos fundamentais de multitenant
- Estrutura completa de bancos de dados
- Fluxo de identificação de tenants
- Sistema de proxy e comunicação
- Segurança e validações cross-tenant
- Dados compartilhados (CATMAT, CMED)
- Instalação de módulos por tenant
- Migrations e prefixo de tabelas
- Sessões e cache isolados
- Diagramas de arquitetura
- Vantagens e desvantagens
- Comandos de debugging
- Glossário técnico

**Ideal para:**
- Entender como funciona a arquitetura
- Onboarding de novos desenvolvedores
- Documentação técnica completa
- Auditoria de segurança
- Planejamento de escalabilidade

---

### 📗 2. GUIA_PRATICO_MULTITENANT.md

**Tipo:** Guia Operacional  
**Tamanho:** ~400 linhas  
**Público:** DevOps, Administradores, Suporte

**Conteúdo:**
- Como adicionar novo tenant (passo a passo)
- Como migrar tenant para outro servidor
- Troubleshooting de problemas comuns
- Scripts de monitoramento
- Backup e restore automatizado
- Performance e otimização
- Rotação de senhas
- Testes de isolamento
- Checklist de onboarding
- Comandos rápidos
- Cenários de erro e recuperação
- Referência rápida (URLs, portas, caminhos)

**Ideal para:**
- Operações do dia a dia
- Resolver problemas rapidamente
- Adicionar novos clientes
- Manutenção preventiva
- Suporte técnico

---

## NAVEGAÇÃO RÁPIDA

### Por Tópico

**Conceitos e Teoria:**
→ ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md
- Seção 1: Conceitos Fundamentais
- Seção 2: Estrutura de Bancos
- Seção 3: Identificação de Tenants
- Seção 12: Diagrama Completo

**Operações:**
→ GUIA_PRATICO_MULTITENANT.md
- Seção 1: Adicionar Tenant
- Seção 3: Troubleshooting
- Seção 4: Monitoramento
- Seção 10: Comandos Rápidos

**Segurança:**
→ ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md
- Seção 5: Segurança Cross-Tenant
- Seção 14: Boas Práticas
→ GUIA_PRATICO_MULTITENANT.md
- Seção 7: Rotação de Senhas
- Seção 8: Testes de Isolamento

**Performance:**
→ GUIA_PRATICO_MULTITENANT.md
- Seção 6: Otimização
- Seção 4: Monitoramento

**Migrations:**
→ ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md
- Seção 7: Instalação de Módulos
- Seção 8: Migrations e Prefixos

**Dados Compartilhados:**
→ ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md
- Seção 6: Dados Compartilhados
- Exemplos: CATMAT, CMED, Compras.gov

---

## CENÁRIOS DE USO

### "Preciso adicionar uma nova prefeitura"
📖 Leia: GUIA_PRATICO_MULTITENANT.md → Seção 1

### "Estou com erro 'Cross-tenant access blocked'"
📖 Leia: GUIA_PRATICO_MULTITENANT.md → Seção 3

### "Como funciona o isolamento de dados?"
📖 Leia: ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md → Seções 2, 5, 10

### "Preciso fazer backup dos tenants"
📖 Leia: GUIA_PRATICO_MULTITENANT.md → Seção 5

### "Como migrar tenant para outro servidor?"
📖 Leia: GUIA_PRATICO_MULTITENANT.md → Seção 2

### "Quero entender a arquitetura completa"
📖 Leia: ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md (completo)

### "Preciso otimizar performance de um tenant"
📖 Leia: GUIA_PRATICO_MULTITENANT.md → Seção 6

### "Como funcionam os dados compartilhados (CATMAT)?"
📖 Leia: ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md → Seção 6

---

## INFORMAÇÕES DE SISTEMA

### Tenants Cadastrados (29/10/2025)

| ID | Tenant          | Banco              | Orçamentos | Status |
|----|-----------------|-------------------|------------|--------|
| 1  | catasaltas      | catasaltas_db     | 8          | ✅     |
| 2  | novaroma        | novaroma_db       | 63         | ✅     |
| 3  | pirapora        | pirapora_db       | 0          | ✅     |
| 4  | gurupi          | gurupi_db         | 0          | ✅     |
| 5  | novalaranjeiras | novalaranjeiras_db| 0          | ✅     |
| 6  | dattatech       | dattatech_db      | 2          | ✅     |

### Módulos Instalados

**Cesta de Preços (price_basket):**
- catasaltas ✅
- novaroma ✅
- dattatech ✅

**NF-e (nf):**
- novaroma ✅
- dattatech ✅

### Banco Central (Dados Compartilhados)

**Database:** minhadattatech_db

**Tabelas Compartilhadas:**
- cp_catmat (300MB) - Catálogo de Materiais
- cp_medicamentos_cmed (50MB) - Preços CMED
- cp_precos_comprasgov (100MB) - Histórico Compras.gov

---

## ARQUIVOS DE CÓDIGO PRINCIPAIS

### MinhaDattaTech (Sistema Central)

```
/home/dattapro/minhadattatech/
├── app/
│   ├── Models/
│   │   └── Tenant.php                          # Modelo principal
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── DetectTenant.php                # Detecta tenant por subdomínio
│   │   │   ├── TenantAuthMiddleware.php        # Valida autenticação do tenant
│   │   │   └── DynamicSessionDomain.php        # Isola cookies por domínio
│   │   └── Controllers/
│   │       └── ModuleProxyController.php       # Proxy para módulos
│   └── Services/
│       └── ModuleInstaller.php                 # Instala módulos em tenants
```

### Módulo Cesta de Preços

```
/home/dattapro/modulos/cestadeprecos/
├── app/
│   ├── Http/
│   │   └── Middleware/
│   │       └── ProxyAuth.php                   # Recebe headers e configura DB
│   ├── Models/
│   │   ├── Catmat.php                          # Usa pgsql_main
│   │   └── MedicamentoCmed.php                 # Usa pgsql_main
│   └── ...
├── config/
│   ├── database.php                            # Conexões pgsql, pgsql_main
│   └── session.php                             # Sessões isoladas
└── database/
    └── migrations/                              # Migrations com prefixo cp_
        ├── 2025_09_30_143011_create_orcamentos_table.php
        └── ...
```

---

## COMANDOS ESSENCIAIS

### Listar Tenants
```bash
php artisan tinker
>>> Tenant::all(['id', 'subdomain', 'company_name'])
```

### Verificar Banco de Tenant
```bash
sudo -u postgres psql -d pirapora_db -c "\dt cp_*"
```

### Instalar Módulo em Tenant
```php
php artisan tinker
>>> app(ModuleInstaller::class)->install(Tenant::find(3), 'price_basket')
```

### Ver Logs em Tempo Real
```bash
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log
```

### Backup de Tenant
```bash
sudo -u postgres pg_dump pirapora_db > /backup/pirapora_$(date +%Y%m%d).sql
```

---

## DIAGRAMAS

### Fluxo de Requisição

```
Cliente
   ↓
Caddy (proxy reverso)
   ↓
MinhaDattaTech:8000
   ├→ DetectTenant (extrai subdomain)
   ├→ TenantAuthMiddleware (valida sessão)
   └→ ModuleProxyController (prepara headers)
       ↓ Headers: X-Tenant-Id, X-DB-Name, X-DB-User, etc.
       ↓
Módulo Cesta de Preços:8001
   └→ ProxyAuth (configura DB dinâmico)
       ↓
PostgreSQL (banco do tenant)
```

### Estrutura de Bancos

```
┌─────────────────────────────────────┐
│   minhadattatech_db (Central)       │
│   ├── tenants                        │
│   ├── users                          │
│   ├── cp_catmat (compartilhado)     │
│   └── cp_medicamentos_cmed          │
└─────────────────────────────────────┘

┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│  pirapora_db  │  │  novaroma_db  │  │ catasaltas_db │
│  (isolado)    │  │  (isolado)    │  │  (isolado)    │
│  50 tabelas   │  │  50 tabelas   │  │  50 tabelas   │
│  cp_*         │  │  cp_*         │  │  cp_*         │
└───────────────┘  └───────────────┘  └───────────────┘
```

---

## CHECKLIST DE VERIFICAÇÃO

### ✅ Segurança
- [ ] Validação cross-tenant implementada
- [ ] Cookies isolados por domínio
- [ ] Senhas criptografadas no banco central
- [ ] Logs de auditoria habilitados
- [ ] Conexões SSL habilitadas

### ✅ Performance
- [ ] Índices criados nas tabelas principais
- [ ] Vacuum periódico configurado
- [ ] Cache Redis funcionando
- [ ] Monitoramento de queries lentas

### ✅ Backup
- [ ] Script de backup automatizado
- [ ] Retenção de 30 dias
- [ ] Backup do banco central
- [ ] Teste de restore realizado

### ✅ Documentação
- [ ] Credenciais documentadas (cofre)
- [ ] Procedimentos de emergência
- [ ] Contatos de suporte
- [ ] Diagramas atualizados

---

## CONTATOS E SUPORTE

**Desenvolvedor Responsável:** [Nome]  
**Email:** [email]  
**Documentação Técnica:** /home/dattapro/modulos/cestadeprecos/Arquivos_Claude/

**Em caso de emergência:**
1. Verificar logs: `tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log`
2. Consultar GUIA_PRATICO_MULTITENANT.md → Seção 3 (Troubleshooting)
3. Verificar status dos bancos: `monitor_tenants.sh`
4. Contatar suporte técnico

---

## HISTÓRICO DE REVISÕES

| Data       | Versão | Alterações                                    |
|------------|--------|-----------------------------------------------|
| 29/10/2025 | 1.0    | Criação inicial da documentação multitenant   |

---

**FIM DO ÍNDICE**

Esta documentação cobre 100% da arquitetura multitenant implementada no sistema MinhaDataTech + Módulo Cesta de Preços.
