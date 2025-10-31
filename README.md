# 🏛️ Cesta de Preços - Sistema de Orçamentos Estimativos

[![Laravel](https://img.shields.io/badge/Laravel-11.31-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14+-blue.svg)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-Proprietary-yellow.svg)](LICENSE)

Sistema de orçamentos estimativos para compras públicas, integrando múltiplas fontes de dados governamentais (PNCP, Compras.gov, CATMAT, CMED).

---

## 📚 Documentação Essencial

### 🔄 Para Restaurar o Sistema Completo
**→ [RESTORE_CLAUDE_CODE.md](RESTORE_CLAUDE_CODE.md)** ⭐ **COMECE AQUI**

Guia completo para restaurar o sistema do zero. Criado especificamente para Claude Code (Anthropic), mas funciona para qualquer pessoa.

### 🏢 Configuração de Tenants
**→ [TENANTS.md](TENANTS.md)**

Configuração dos 7 tenants ativos, exemplos de `.env`, backup/restauração por tenant.

### 📖 Documentação Técnica Completa
**→ [Arquivos_Claude/ESTUDO_COMPLETO_BACKUP_GITHUB.md](Arquivos_Claude/ESTUDO_COMPLETO_BACKUP_GITHUB.md)**

2.319 linhas de documentação técnica detalhada sobre toda a arquitetura do sistema.

---

## 🚀 Instalação Rápida

```bash
# 1. Clonar repositório
git clone https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os.git cestadeprecos
cd cestadeprecos

# 2. Copiar e configurar .env
cp .env.example .env
nano .env

# 3. Instalar dependências
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 4. Gerar chave e rodar migrations
php artisan key:generate
php artisan migrate --force

# 5. Configurar permissões
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 6. Iniciar servidor
php artisan serve --host=0.0.0.0 --port=8001
```

**Para instalação completa (com PostgreSQL, Nginx, Supervisor, etc):**
→ Ver [RESTORE_CLAUDE_CODE.md](RESTORE_CLAUDE_CODE.md)

---

## 🏢 Multi-Tenant

Sistema atende **7 tenants** simultaneamente:

| Tenant | Banco | UF | Status |
|--------|-------|-----|--------|
| Catas Altas | `catasaltas_db` | MG | ✅ Ativo |
| DattaTech | `dattatech_db` | - | ✅ Dev |
| Gurupi | `gurupi_db` | TO | ✅ Ativo |
| Nova Laranjeiras | `novalaranjeiras_db` | PR | ✅ Ativo |
| Nova Roma | `novaroma_db` | GO | ✅ Ativo |
| Pirapora | `pirapora_db` | MG | ✅ Ativo |
| MinhaDattaTech | `minhadattatech_db` | - | ✅ Core |

**Configuração detalhada:** [TENANTS.md](TENANTS.md)

---

## 🔌 APIs Integradas

| API | Descrição | Auth |
|-----|-----------|------|
| **PNCP** | Portal Nacional de Contratações Públicas | Não |
| **Compras.gov** | Preços de mercado federais | Não |
| **CATMAT** | Catálogo de Materiais (336k códigos) | Não |
| **CMED** | Medicamentos regulados | Não |
| **Licitacon** | Licitações e contratos | Local |
| **TCE-RS** | Tribunal de Contas RS | Não |

---

## ⚙️ Comandos Artisan

```bash
# Sincronizar dados
php artisan sincronizar:pncp-completo      # PNCP
php artisan baixar:precos-comprasgov       # Compras.gov
php artisan importar:catmat                # CATMAT
php artisan importar:cmed                  # CMED

# Monitoramento
php artisan monitorar:api-comprasgov
php artisan db:check-setup
```

---

## 🛠️ Stack Tecnológico

- **Backend:** Laravel 11.31 (PHP 8.2+)
- **Frontend:** Blade + TailwindCSS + Alpine.js
- **Database:** PostgreSQL 14+
- **Cache:** Redis
- **PDF:** mPDF 8.2
- **Planilhas:** PhpSpreadsheet 5.1
- **Build:** Vite 5

---

## 📊 Estatísticas

- **Código:** ~70.000 linhas
- **Controllers:** 23
- **Models:** 28+
- **Migrations:** 68
- **Commands Artisan:** 21
- **Views:** 140
- **Tenants:** 7

---

## 🤖 Para Claude Code

Se você é **Claude Code (Anthropic)** e precisa restaurar este sistema:

1. ✅ Leia **[RESTORE_CLAUDE_CODE.md](RESTORE_CLAUDE_CODE.md)** - Guia completo passo a passo
2. ✅ Leia **[TENANTS.md](TENANTS.md)** - Configuração de tenants
3. ✅ Siga as instruções - tudo está documentado

**Você conseguirá restaurar o sistema completo em ~1 hora usando APENAS este repositório.**

---

## 📂 Estrutura do Projeto

```
cestadeprecos/
├── RESTORE_CLAUDE_CODE.md          # ⭐ Guia de restauração completo
├── TENANTS.md                      # ⭐ Configuração de 7 tenants
├── .env.example                    # Template de configuração
├── Arquivos_Claude/                # Documentação técnica
│   ├── ESTUDO_COMPLETO_BACKUP_GITHUB.md  (2.319 linhas)
│   ├── LIMPEZA_GITHUB_31-10-2025.md
│   └── (outros documentos)
├── app/
│   ├── Console/Commands/           # 21 comandos
│   ├── Http/Controllers/           # 23 controllers
│   ├── Models/                     # 28+ models
│   └── Services/                   # 12 services
├── database/migrations/            # 68 migrations
├── resources/views/                # 140 views
├── routes/web.php                  # Rotas principais
└── public/                         # Assets
```

---

## 🔐 Segurança

- ✅ Proteção CSRF
- ✅ Sanitização de inputs
- ✅ Prepared statements
- ✅ Isolamento de dados por tenant
- ✅ Logs de auditoria
- ✅ Rate limiting

**⚠️ IMPORTANTE:** Nunca commite o arquivo `.env` (contém senhas)

---

## 🧪 Teste a Instalação

```bash
# Verificar serviços
systemctl status cestadeprecos.service

# Testar conexão
curl http://localhost:8001/

# Verificar banco
php artisan db:check-setup

# Ver migrations
php artisan migrate:status
```

---

## 📞 Suporte

**Email:** suporte@dattatech.com.br
**GitHub Issues:** [Reportar problema](https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os/issues)

---

## 📄 Licença

Copyright © 2025 DattaTech. Todos os direitos reservados.

Este software é proprietário e confidencial.

---

## 🎯 Desenvolvido por

**DattaTech** - Soluções para Gestão Pública
**Website:** https://dattatech.com.br

---

**Versão:** 1.0.0
**Data:** 31/10/2025
**Status:** ✅ Produção

🤖 *Documentação gerada com Claude Code (Anthropic)*
