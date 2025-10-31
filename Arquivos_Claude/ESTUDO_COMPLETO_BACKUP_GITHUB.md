# ESTUDO COMPLETO - PREPARAÇÃO BACKUP GITHUB
**Data:** 31/10/2025  
**Objetivo:** Mapear TODA a estrutura do sistema para criar repositório GitHub organizado por tenants

---

## 1. VISÃO GERAL DA ARQUITETURA

### 1.1 Estrutura Multi-Tenant
O sistema utiliza uma arquitetura **modular multi-tenant** com as seguintes características:

- **Aplicação Core:** `/home/dattapro/minhadattatech` (Painel de gerenciamento central)
- **Módulos:** `/home/dattapro/modulos/` (Módulos isolados por funcionalidade)
  - `cestadeprecos/` - Módulo principal de orçamentos (Porta 8001)
  - `nfe/` - Módulo de Notas Fiscais Eletrônicas (Porta 8002)

### 1.2 Banco de Dados
- **PostgreSQL:** `minhadattatech_db`
- **Schema Único:** `public` (compartilhado)
- **Separação por Prefixo:**
  - `cp_*` - Tabelas do módulo Cesta de Preços (3 tabelas)
  - `nf_*` - Tabelas do módulo NFe (9 tabelas)
  - Sem prefixo - Tabelas do core (16 tabelas)

### 1.3 Tenants Identificados
```
1. catasaltas_db      (Catas Altas - MG)
2. dattatech_db       (DattaTech - Desenvolvimento)
3. gurupi_db          (Gurupi - TO)
4. novalaranjeiras_db (Nova Laranjeiras - PR)
5. novaroma_db        (Nova Roma - GO)
6. pirapora_db        (Pirapora - MG)
7. minhadattatech_db  (Sistema Principal/Core)
```

**IMPORTANTE:** Aparentemente os tenants compartilham o mesmo banco de dados físico (`minhadattatech_db`), mas a separação lógica pode estar em outro nível (schemas separados ou tabelas com tenant_id).

---

## 2. ESTRUTURA DE DIRETÓRIOS

### 2.1 Módulo Cesta de Preços (606MB total)

```
/home/dattapro/modulos/cestadeprecos/
├── app/                          # Código da aplicação
│   ├── Console/Commands/         # 21 comandos Artisan
│   ├── Http/
│   │   ├── Controllers/         # 23 controllers
│   │   └── Middleware/          # Middlewares
│   ├── Models/                  # 28+ models
│   ├── Services/                # 10 services
│   │   └── PDF/                 # Serviços de geração PDF
│   ├── Mail/                    # Templates de email
│   └── Helpers/                 # Funções auxiliares
│
├── database/
│   ├── migrations/              # 68 migrations
│   ├── seeders/                 # Seeds
│   └── factories/               # Factories
│
├── resources/
│   └── views/                   # 140 arquivos .blade.php
│       ├── orcamentos/
│       ├── pdfs/
│       ├── emails/
│       └── layouts/
│
├── routes/
│   ├── web.php                  # Rotas principais (43KB)
│   └── console.php              # Comandos console
│
├── public/
│   ├── css/                     # Estilos
│   ├── js/                      # Scripts JavaScript
│   ├── build/                   # Assets compilados (Vite)
│   └── storage/                 # Link simbólico
│
├── storage/
│   ├── app/
│   │   ├── private/catmat/      # JSONs CATMAT (>10MB cada)
│   │   └── mpdf_temp/          # PDFs temporários
│   ├── logs/                    # Logs do sistema (>10MB)
│   └── framework/               # Cache, sessions, views
│
├── config/                      # 12 arquivos de configuração
├── vendor/                      # Dependências (149MB)
├── node_modules/                # Dependências Node (calculado)
├── backups/                     # 11 backups históricos
├── CMED_EXTRAIDO/              # 5 JSONs CMED (>10MB cada)
├── Arquivos_Claude/            # Documentação e estudos
│
├── composer.json                # Dependências PHP
├── package.json                 # Dependências Node.js
├── .env                         # Configurações (NÃO VERSIONAR)
├── .env.example                 # Template de configuração
└── .gitignore                   # Já existe

Total de arquivos PHP: 9,658
Total de arquivos Blade: 140
Total de migrations: 68
```

### 2.2 Aplicação Core MinhaDattaTech (149MB total)

```
/home/dattapro/minhadattatech/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/             # Controllers de API
│   │   │   ├── Admin/           # Controllers Admin
│   │   │   └── Auth/            # Autenticação
│   │   └── Middleware/
│   │       ├── TenantAuthMiddleware.php
│   │       ├── DynamicSessionDomain.php
│   │       └── ProxyAuth.php
│   ├── Models/                  # 9 models principais
│   │   ├── Tenant.php           # Modelo de Tenant
│   │   ├── User.php
│   │   ├── ModuleConfiguration.php
│   │   └── TenantActiveModule.php
│   ├── Services/                # 8 services
│   │   ├── ModuleInstaller.php
│   │   ├── TechnicalPanelAPI.php
│   │   └── TenantAuthService.php
│   └── Livewire/                # Componentes Livewire
│
├── database/
│   ├── migrations/              # 17 migrations
│   └── seeders/
│
├── resources/
│   └── views/
│       └── desktop/             # Interface desktop
│
├── routes/
│   ├── web.php                  # Rotas principais (6KB)
│   └── api.php                  # Rotas de API
│
├── public/                      # Assets públicos
├── config/                      # 13 arquivos de configuração
├── vendor/                      # Dependências (69MB)
├── Arquivos_Claude/            # Documentação
│
├── composer.json
├── package.json
├── .env
└── .gitignore

Total de arquivos PHP: 7,669
Total de migrations: 17
```

### 2.3 Módulo NFe (estrutura similar)

```
/home/dattapro/modulos/nfe/
├── app/
├── database/migrations/
├── resources/views/
├── routes/
├── config/
├── .env
└── composer.json
```

---

## 3. DEPENDÊNCIAS E TECNOLOGIAS

### 3.1 Cesta de Preços - composer.json

**PHP:** ^8.2  
**Laravel:** ^11.31

**Dependências Principais:**
```json
{
    "barryvdh/laravel-dompdf": "^3.1",      // Geração de PDF
    "mpdf/mpdf": "^8.2",                     // Geração de PDF avançada
    "mpdf/qrcode": "^1.2",                   // QR Codes
    "phpoffice/phpspreadsheet": "^5.1",      // Excel/Planilhas
    "phpoffice/phpword": "^1.4",             // Documentos Word
    "simplesoftwareio/simple-qrcode": "^4.2", // QR Codes
    "smalot/pdfparser": "^2.12",             // Parse de PDFs
    "thiagoalessio/tesseract_ocr": "^2.13"   // OCR (Tesseract)
}
```

**Dev Dependencies:**
```json
{
    "laravel/pail": "^1.1",
    "laravel/pint": "^1.13",
    "laravel/sail": "^1.26",
    "phpunit/phpunit": "^11.0.1"
}
```

### 3.2 MinhaDattaTech Core - composer.json

**PHP:** ^8.2  
**Laravel:** ^11.31

**Dependências Principais:**
```json
{
    "livewire/livewire": "^3.6"  // Framework Livewire
}
```

### 3.3 Dependências Node.js (package.json)

Ambos os projetos usam:
- Vite (bundler)
- TailwindCSS (framework CSS)
- Alpine.js (framework JS)

---

## 4. CONFIGURAÇÕES (.env)

### 4.1 Cesta de Preços - Exemplo

```env
APP_NAME="Cesta de Preços"
APP_ENV=local
APP_KEY=base64:wicqkfWquBvYK6ClrBYle0GNWpCcUp5ONhcZ3obexGg=
APP_DEBUG=true
APP_URL=http://localhost:8001

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minhadattatech_db
DB_USERNAME=minhadattatech_user
DB_PASSWORD=MinhaDataTech2024SecureDB

# Sessions
SESSION_DRIVER=database
SESSION_TABLE=cp_sessions

# Cache
CACHE_STORE=redis
CACHE_PREFIX=cesta_precos_

# APIs Externas
PNCP_CONNECT_TIMEOUT=5
PNCP_TIMEOUT=20
PORTALTRANSPARENCIA_API_KEY=319215bff3b6753f5e1e4105c58a55e9

# Email
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_FROM_ADDRESS="suporte@dattatech.com.br"
```

### 4.2 MinhaDattaTech Core - Exemplo

```env
APP_NAME="Minha Datta Tech"
APP_ENV=development
APP_KEY=base64:wicqkfWquBvYK6ClrBYle0GNWpCcUp5ONhcZ3obexGg=
APP_URL=https://minha.dattatech.com.br

# Technical Panel API
TECHNICAL_PANEL_URL=http://localhost:8080
TECHNICAL_PANEL_API_TOKEN=temp_dev_token_hybrid

# Database (mesmo banco!)
DB_CONNECTION=pgsql
DB_DATABASE=minhadattatech_db
DB_USERNAME=minhadattatech_user
DB_PASSWORD=MinhaDataTech2024SecureDB

# Sessions
SESSION_DRIVER=database
SESSION_DOMAIN=.dattapro.online
SESSION_COOKIE=minhadattatech_session_v2

# PostgreSQL Superuser (criação de tenants)
DB_POSTGRES_PASSWORD=MinhaDataTech2024SecureDB
```

---

## 5. BANCO DE DADOS

### 5.1 Tabelas por Módulo

**Core (16 tabelas):**
```
- cache
- cache_locks
- email_verifications
- failed_jobs
- job_batches
- jobs
- module_configurations
- password_reset_tokens
- permissions
- role_permissions
- roles
- sessions
- tenant_active_modules
- tenant_auth_tokens
- tenants
- users
```

**Cesta de Preços (3 tabelas - schema public):**
```
- cp_catmat                  # Catálogo de Materiais
- cp_medicamentos_cmed       # Medicamentos CMED
- cp_precos_comprasgov       # Preços do Compras.gov
```

**NFe (9 tabelas):**
```
- nf_certificados
- nf_configuracoes
- nf_documentos
- nf_emitentes
- nf_itens
- nf_notificacoes
- nf_provedores_nfse
- nf_sessions
- nf_sincronizacao_logs
```

### 5.2 Migrations

**Cesta de Preços:** 68 migrations
**MinhaDattaTech:** 17 migrations

**NOTA CRÍTICA:** Aparentemente as migrations devem ser executadas no banco compartilhado, respeitando os prefixos de tabela.

---

## 6. ARQUIVOS CRÍTICOS vs GERADOS

### 6.1 DEVEM ser versionados (.gitignore já configurado)

**Código-fonte:**
- `app/**/*.php`
- `database/migrations/*.php`
- `database/seeders/*.php`
- `resources/**/*.blade.php`
- `resources/**/*.js`
- `resources/**/*.css`
- `routes/*.php`
- `config/*.php`
- `public/*.php` (exceto index.php padrão)
- `public/css/**` (CSS customizado)
- `public/js/**` (JS customizado)

**Configuração:**
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `.env.example`
- `phpunit.xml`
- `vite.config.js`
- `tailwind.config.js`
- `postcss.config.js`

**Documentação:**
- `README.md`
- `MODULE_INFO.md`
- `Arquivos_Claude/*.md`

### 6.2 NÃO DEVEM ser versionados (já no .gitignore)

**Dependências:**
- `vendor/` (149MB cesta, 69MB core)
- `node_modules/` (tamanho variável)

**Gerados/Temporários:**
- `storage/logs/**` (>10MB logs)
- `storage/framework/cache/**`
- `storage/framework/sessions/**`
- `storage/framework/views/**`
- `storage/app/temp/**`
- `storage/app/mpdf_temp/**`
- `public/build/**` (assets compilados)
- `public/storage` (link simbólico)

**Dados de Produção:**
- `storage/app/private/catmat/*.json` (>10MB cada)
- `CMED_EXTRAIDO/*.json` (>10MB cada)
- `*.xlsx` arquivos de teste/dados
- `*.pdf` arquivos gerados
- `backups/**`

**Configurações Sensíveis:**
- `.env`
- `.env.backup*`
- `auth.json`
- `*.key` (chaves SSL/certificados)

**IDEs:**
- `.idea/`
- `.vscode/`
- `.nova/`
- `.fleet/`
- `.zed/`
- `.claude/settings.local.json`

**Outros:**
- `.phpunit.cache/`
- `.phpunit.result.cache`
- `*.log`
- `*.png` (prints de tela)
- `*.jpg` (imagens de teste)

### 6.3 .gitignore Atual (COMPLETO)

```gitignore
/.phpunit.cache
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/storage/pail
/vendor
.env
.env.backup
.env.production
.phpactor.json
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/auth.json
/.fleet
/.idea
/.nova
/.vscode
/.zed
.claude/settings.local.json
```

**ADICIONAR ao .gitignore para GitHub:**
```gitignore
# Dados e Cache
/storage/logs/
/storage/app/private/
/storage/app/mpdf_temp/
/CMED_EXTRAIDO/
/backups/

# Arquivos de teste/temporários
*.xlsx
*.pdf
*.png
*.jpg
*.PNG
*.log
*.jpeg

# Backups de código
*.backup
*.backup-*
*.old

# Específico do projeto
/Tabela*.xlsx
/CMED*.xlsx
/tests/
/docs/
```

---

## 7. COMANDOS ARTISAN (Cesta de Preços)

### 7.1 Comandos Personalizados (21 total)

**Sincronização/Download:**
```
php artisan baixar:catmat                    # Baixa catálogo CATMAT
php artisan baixar:contratos-pncp            # Baixa contratos PNCP
php artisan baixar:precos-comprasgov         # Baixa preços Compras.gov
php artisan baixar:precos-comprasgov-paralelo # Versão paralela
php artisan baixar:tce-rs                    # Importa dados TCE-RS
```

**Importação:**
```
php artisan importar:catmat                  # Importa CATMAT
php artisan importar:cmed                    # Importa medicamentos CMED
php artisan importar:licitacon-completo      # Importa Licitacon
php artisan importar:orientacoes-tecnicas    # Importa orientações
```

**Sincronização PNCP:**
```
php artisan sincronizar:pncp                 # Sincroniza PNCP
php artisan sincronizar:pncp-completo        # Sincronização completa
php artisan licitacon:sincronizar            # Sincroniza Licitacon
```

**Monitoramento:**
```
php artisan monitorar:api-comprasgov         # Monitora API Compras.gov
```

**Processamento Focado:**
```
php artisan comprasgov:scout                 # Scout de produtos
php artisan comprasgov:scout-worker          # Worker do scout
php artisan comprasgov:worker                # Worker genérico
php artisan comprasgov:baixar-focado         # Download focado
```

**Utilitários:**
```
php artisan db:check-setup                   # Verifica configuração DB
php artisan fornecedores:atualizar-contratos # Atualiza fornecedores
php artisan fornecedores:popular-pncp        # Popula fornecedores PNCP
```

---

## 8. ROTAS PRINCIPAIS

### 8.1 Cesta de Preços (web.php - 43KB)

**Dashboard:**
- `GET /` - Dashboard principal

**Orçamentos:**
- `GET /orcamentos/create` - Criar orçamento
- `GET /orcamentos/elaborar/{id}` - Elaborar orçamento
- `POST /orcamentos/store` - Salvar orçamento
- `GET /orcamentos/{id}/preview` - Preview PDF

**Pesquisa:**
- `GET /pesquisa-rapida` - Pesquisa rápida de preços
- `POST /api/pesquisa-rapida` - API de pesquisa

**Fornecedores:**
- `GET /fornecedores` - Listar fornecedores
- `POST /fornecedores/importar` - Importar fornecedores

**Catálogo:**
- `GET /catalogo` - Catálogo de produtos
- `POST /catalogo/buscar` - Buscar no catálogo

**Mapa:**
- `GET /mapa-de-fornecedores` - Mapa de fornecedores
- `GET /mapa-de-atas` - Mapa de atas

**CDF:**
- `GET /cdf/solicitar` - Solicitar Cotação de Fornecedor
- `POST /cdf/enviar` - Enviar solicitação CDF

**APIs Externas:**
- `POST /api/pncp/search` - Busca PNCP
- `POST /api/comprasgov/search` - Busca Compras.gov
- `POST /api/catmat/search` - Busca CATMAT

### 8.2 MinhaDattaTech Core (web.php - 6KB)

**Desktop:**
- `GET /desktop` - Interface desktop do tenant

**Módulos:**
- `GET /module-proxy/{module}/{path?}` - Proxy para módulos

**Admin:**
- `GET /admin/tenants` - Gerenciar tenants
- `GET /admin/modules` - Gerenciar módulos

**Auth:**
- `POST /login` - Login
- `POST /logout` - Logout

---

## 9. CONTROLLERS PRINCIPAIS

### 9.1 Cesta de Preços (23 controllers)

**Principais:**
```
OrcamentoController.php      (349KB) - Maior controller, gerencia orçamentos
FornecedorController.php     (100KB) - Gerencia fornecedores
PesquisaRapidaController.php  (66KB) - Pesquisa de preços
MapaAtasController.php        (42KB) - Mapa de atas de registro
CatalogoController.php        (35KB) - Catálogo de produtos
CotacaoExternaController.php  (50KB) - Cotações externas
CdfRespostaController.php     (24KB) - Respostas de CDF
ConfiguracaoController.php    (14KB) - Configurações do sistema
```

**Utilitários:**
```
AuthController.php
CatmatController.php
CnpjController.php
ContratosExternosController.php
LogController.php
NotificacaoController.php
OrgaoController.php
OrientacaoTecnicaController.php
TceRsController.php
```

### 9.2 MinhaDattaTech Core

**Principais:**
```
ModuleProxyController.php         (24KB) - Proxy para módulos
TechnicalModuleController.php     (18KB) - Gerencia módulos
EmailVerificationController.php
```

**API:**
```
API/ModuleController.php
```

**Auth:**
```
Auth/AuthController.php
```

---

## 10. MODELS E RELACIONAMENTOS

### 10.1 Cesta de Preços (28+ models)

**Principais:**
```
Orcamento.php              - Orçamentos estimativos
OrcamentoItem.php          - Itens dos orçamentos
Fornecedor.php             - Fornecedores/Empresas
FornecedorItem.php         - Produtos dos fornecedores
CatalogoProduto.php        - Catálogo de produtos
Catmat.php                 - Catálogo de Materiais (CATMAT)
MedicamentoCmed.php        - Medicamentos CMED
ContratoPNCP.php           - Contratos do PNCP
HistoricoPreco.php         - Histórico de preços
SolicitacaoCdf.php         - Solicitações CDF
```

**Utilitários:**
```
Notificacao.php
Anexo.php
Orgao.php
OrientacaoTecnica.php
AuditLogItem.php
AuditSnapshot.php
LogImportacao.php
```

**Cache/Controle:**
```
ConsultaPncpCache.php
CheckpointImportacao.php
CrosswalkFonte.php
```

### 10.2 MinhaDattaTech Core (9 models)

```
Tenant.php                  - Tenants/Clientes
User.php                    - Usuários
TenantActiveModule.php      - Módulos ativos por tenant
ModuleConfiguration.php     - Configurações de módulos
Role.php                    - Papéis/Permissões
Permission.php              - Permissões
EmailVerification.php       - Verificação de email
```

---

## 11. SERVICES E HELPERS

### 11.1 Cesta de Preços - Services (12 services)

**APIs Externas:**
```
ComprasnetApiService.php        (14KB) - API Compras.gov
ComprasnetApiNovaService.php    (12KB) - Nova API Compras.gov
LicitaconService.php            (12KB) - Licitacon
TceRsApiService.php             (21KB) - TCE-RS
CnpjService.php                  (9KB) - Consulta CNPJ
```

**Processamento:**
```
DataNormalizationService.php     (6KB) - Normalização de dados
CurvaABCService.php              (5KB) - Análise Curva ABC
EstatisticaService.php          (11KB) - Estatísticas
```

**PDF:**
```
PDF/PDFService.php               - Geração de PDFs
PDF/PDFTemplateService.php       - Templates de PDF
PDF/PDFHeaderFooterService.php   - Cabeçalhos/Rodapés
```

### 11.2 MinhaDattaTech Core - Services (8 services)

```
TechnicalPanelAPI.php       (19KB) - Integração com painel técnico
ModuleInstaller.php          (9KB) - Instalação de módulos
TenantAuthService.php        (4KB) - Autenticação de tenants
EmailVerificationService.php (8KB) - Verificação de email
CaddyConfigGenerator.php     (5KB) - Geração de config Caddy
```

---

## 12. MIDDLEWARE

### 12.1 Cesta de Preços

```
InternalOnly.php            - Bloqueia acesso externo
ValidateApiToken.php        - Valida tokens de API
CheckTenantAccess.php       - Verifica acesso do tenant
```

### 12.2 MinhaDattaTech Core

```
TenantAuthMiddleware.php       - Autenticação multi-tenant
DynamicSessionDomain.php       - Gerencia domínios de sessão
ProxyAuth.php                  - Autenticação via proxy
```

---

## 13. VIEWS E TEMPLATES

### 13.1 Cesta de Preços (140 arquivos .blade.php)

**Principais:**
```
dashboard.blade.php              - Dashboard
orcamentos/create.blade.php      - Criar orçamento
orcamentos/elaborar.blade.php    - Elaborar orçamento
pesquisa-rapida.blade.php        - Pesquisa rápida
fornecedores.blade.php           - Gerenciar fornecedores
catalogo.blade.php               - Catálogo de produtos
mapa-de-fornecedores.blade.php   - Mapa de fornecedores
mapa-de-atas.blade.php           - Mapa de atas
```

**PDFs:**
```
pdfs/orcamento-template.blade.php
pdfs/cdf-email.blade.php
pdfs/relatorio-template.blade.php
```

**Emails:**
```
emails/cdf-solicitacao.blade.php
emails/notificacao.blade.php
```

**Layouts:**
```
layouts/app.blade.php
layouts/pdf.blade.php
```

---

## 14. ASSETS PÚBLICOS

### 14.1 JavaScript

**Cesta de Preços:**
```
public/js/modal-cotacao.js                   - Modal de cotação
public/js/modal-cotacao-performance-patch.js - Patch de performance
public/js/performance-utils.js               - Utilitários
public/js/sistema-logs.js                    - Sistema de logs
```

### 14.2 CSS

**Cesta de Preços:**
```
public/css/modal-cotacao-modern.css          - Estilos do modal
public/build/assets/app-*.css                - CSS compilado (Vite)
```

### 14.3 Build Assets (Vite)

```
public/build/assets/app-*.js                 - JS compilado
public/build/assets/app-*.css                - CSS compilado
public/build/manifest.json                   - Manifest Vite
```

---

## 15. SCRIPTS E AUTOMAÇÃO

### 15.1 Cesta de Preços

**Bash Scripts:**
```
fazer_backup.sh                  - Script de backup
restaurar_backup.sh              - Script de restauração
auto_monitor.sh                  - Monitoramento automático
monitor_downloads.sh             - Monitor de downloads
monitor_notificacao.sh           - Monitor de notificações
```

**PHP Scripts:**
```
download_comprasgov_completo.php - Download Compras.gov
extrair_cmed.php                 - Extração CMED
importar_catmat.php              - Importação CATMAT
coleta_precos_comprasgov_hibrida.php
```

### 15.2 NFe

```
instalar-cron-sincronizacao.sh   - Instala cron de sincronização
```

---

## 16. ARQUIVOS DE DADOS (NÃO VERSIONAR)

### 16.1 Arquivos Grandes (>10MB)

**CMED (Medicamentos):**
```
CMED_EXTRAIDO/cmed_janeiro_2025.json    (>10MB)
CMED_EXTRAIDO/cmed_fevereiro_2025.json  (>10MB)
CMED_EXTRAIDO/cmed_marco_2025.json      (>10MB)
CMED_EXTRAIDO/cmed_abril_2025.json      (>10MB)
CMED_EXTRAIDO/cmed_maio_2025.json       (>10MB)

Tabela CMED Abril 25 - SimTax.xlsx      (12MB)
Tabela CMED Maio 25 - SimTax.xlsx       (11MB)
Tabela CMED Junho 25 - SimTax.xlsx      (12MB)
Tabela CMED Julho 25 - SimTax.xlsx      (12MB)
CMED Setembro 25 - Modificada.xlsx      (12MB)
CMED Outubro 25 - Modificada.xlsx       (12MB)
```

**CATMAT:**
```
storage/app/private/catmat/catmat_completo_2025-10-16_08-52-34.json (>10MB)
storage/app/private/catmat/catmat_completo_2025-10-29_12-02-18.json (>10MB)
```

**Logs:**
```
storage/logs/laravel-2025-10-29.log           (>10MB)
storage/logs/laravel-2025-10-24.log           (>10MB)
storage/logs/importacao_catmat.log            (>10MB)
storage/logs/caddy-access.log                 (>10MB)
storage/logs/sistema_detalhado/browser/browser-2025-10-*.log (>5MB)
```

**Vendor:**
```
vendor/laravel/pint/builds/pint               (>10MB)
vendor/mpdf/mpdf/ttfonts/Sun-ExtA.ttf         (>10MB)
vendor/mpdf/mpdf/ttfonts/Sun-ExtB.ttf         (>10MB)
```

### 16.2 Backups Existentes

```
backups/backup_20250930_172300/
backups/backup_20250930_172954/
backups/backup_20250930_175418/
backups/backup_20250930_175701/
backups/backup_20250930_180802/
backups/backup_20250930_182017/
backups/backup_20250930_182335/
backups/modal_cotacao_20251023_152031/
backups/modal_cotacao_20251023_152106/
```

---

## 17. DOCUMENTAÇÃO EXISTENTE

### 17.1 Cesta de Preços - Arquivos_Claude/

**Índices e Guias:**
```
00_LEIA_PRIMEIRO.txt
COMECE_AQUI.md
LEIA-ME-PRIMEIRO.txt
INDEX_ESTUDO_COMPLETO.md
INDEX_MULTITENANT.md
LEIA_ISTO_PRIMEIRO_MULTITENANT.md
```

**Estudos Técnicos:**
```
ESTUDO_COMPLETO_MODULO_CESTA_PRECOS.md
ESTUDO_COMPLETO_SISTEMA_30-10-2025.md
ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md
```

**Resumos Executivos:**
```
RESUMO_COMPLETO_DIA_30-10-2025.md
RESUMO_EXECUTIVO_MULTITENANT.md
RESUMO_TECNICO_ESTATISTICAS.json
```

**Guias:**
```
GUIA_PRATICO_MULTITENANT.md
GUIA_MONITORAMENTO_AUTOMATICO.md
```

**Diagramas:**
```
DIAGRAMA_MULTITENANT_VISUAL.md
DIAGRAMA_RELACIONAMENTOS_E_FLUXO.md
```

### 17.2 Raiz do Projeto

```
README.md                               - Descrição geral
MODULE_INFO.md                          - Info do módulo
PREPARAR_GITHUB.md                      - Guia para GitHub
README_GITHUB.md                        - README para GitHub
ANALISE_COMPLETA_ARQUITETURA_2025-10-22.md
ANALISE_CRIACAO_TENANTS.md
```

---

## 18. ESTRUTURA MULTI-TENANT

### 18.1 Como Funciona

1. **Aplicação Core (MinhaDattaTech):**
   - Gerencia tenants, usuários e módulos
   - Controla autenticação e sessões
   - Faz proxy para os módulos

2. **Módulos (Cesta de Preços, NFe):**
   - Rodam em portas separadas (8001, 8002)
   - Recebem requisições via proxy do core
   - Compartilham banco de dados com prefixos de tabela

3. **Tenants:**
   - Cada cliente é um "tenant"
   - Dados isolados logicamente (não fisicamente)
   - Acesso via subdomínio: `{tenant}.dattapro.online`

### 18.2 Tabelas Relacionadas a Tenants

**Core:**
```sql
-- Tabela principal de tenants
CREATE TABLE tenants (
    id, 
    nome, 
    dominio, 
    banco_dados, 
    ativo,
    ...
);

-- Módulos ativos por tenant
CREATE TABLE tenant_active_modules (
    tenant_id,
    module_name,
    porta,
    ...
);

-- Configurações de módulos
CREATE TABLE module_configurations (
    id,
    module_name,
    config_key,
    config_value,
    ...
);
```

### 18.3 Esquema de Separação

**Opção Atual (Aparente):**
- Banco único: `minhadattatech_db`
- Schema único: `public`
- Separação por prefixo: `cp_*`, `nf_*`
- Possível filtro por `tenant_id` nas queries

**Possíveis Implementações:**
1. **Por Prefixo:** `{tenant}_{tabela}` (não confirmado)
2. **Por Schema:** `{tenant}_schema` (não encontrado)
3. **Por Banco:** `{tenant}_db` (listados, mas não confirmados)
4. **Por Coluna:** `tenant_id` em todas as tabelas (mais provável)

---

## 19. INTEGRAÇÕES EXTERNAS

### 19.1 APIs Governamentais

**PNCP (Portal Nacional de Contratações Públicas):**
- Endpoint: API oficial PNCP
- Autenticação: Não requer chave
- Uso: Consulta de contratos e licitações
- Configuração: `.env` (timeouts, limites)

**Compras.gov (Comprasnet):**
- Endpoint: API pública
- Autenticação: Não requer chave
- Uso: Preços de mercado
- Service: `ComprasnetApiService.php`

**Portal da Transparência (CGU):**
- API Key: `PORTALTRANSPARENCIA_API_KEY`
- Uso: Dados de transparência
- Configuração: `.env`

**Licitacon:**
- Banco local
- Sincronização via comando
- Service: `LicitaconService.php`

**TCE-RS:**
- API TCE Rio Grande do Sul
- Service: `TceRsApiService.php`
- Comando: `php artisan importar:tce-rs`

### 19.2 APIs Internas

**CATMAT (Catálogo de Materiais):**
- Download via `php artisan baixar:catmat`
- Armazenamento: JSON local
- Tabela: `cp_catmat`

**CMED (Medicamentos):**
- Importação via `php artisan importar:cmed`
- Fonte: Planilhas Excel
- Tabela: `cp_medicamentos_cmed`

### 19.3 Serviços Externos

**CNPJ (Receita Federal):**
- Service: `CnpjService.php`
- Uso: Validação e consulta CNPJ

**CEP (Correios):**
- Integração para busca de endereços
- Via API pública

---

## 20. COMANDOS DE DEPLOYMENT

### 20.1 Instalação Inicial

```bash
# 1. Clonar repositórios
git clone <repo-core> /home/dattapro/minhadattatech
git clone <repo-cestadeprecos> /home/dattapro/modulos/cestadeprecos
git clone <repo-nfe> /home/dattapro/modulos/nfe

# 2. Instalar dependências Core
cd /home/dattapro/minhadattatech
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Instalar dependências Cesta de Preços
cd /home/dattapro/modulos/cestadeprecos
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 4. Instalar dependências NFe
cd /home/dattapro/modulos/nfe
composer install --no-dev --optimize-autoloader

# 5. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 6. Permissões
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 7. Migrations
php artisan migrate --force

# 8. Link simbólico storage
php artisan storage:link

# 9. Cache de configuração
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 20.2 Atualização

```bash
# 1. Backup
./fazer_backup.sh

# 2. Pull do repositório
git pull origin main

# 3. Dependências
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 4. Migrations
php artisan migrate --force

# 5. Cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Reiniciar serviços
systemctl restart cestadeprecos.service
systemctl restart nfe.service
```

---

## 21. ESTRUTURA PROPOSTA PARA GITHUB

### 21.1 Repositórios Separados

**Opção 1: Por Aplicação (RECOMENDADO)**

```
dattapro/minhadattatech-core
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── composer.json
├── package.json
├── .env.example
├── .gitignore
└── README.md

dattapro/modulo-cestadeprecos
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── public/
├── composer.json
├── package.json
├── .env.example
├── .gitignore
├── MODULE_INFO.md
└── README.md

dattapro/modulo-nfe
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── composer.json
├── .env.example
├── .gitignore
└── README.md
```

**Opção 2: Monorepo (Alternativa)**

```
dattapro/sistema-multitenant
├── core/                  # MinhaDattaTech
│   ├── app/
│   ├── ...
│   └── composer.json
│
├── modulos/
│   ├── cestadeprecos/    # Módulo Cesta
│   │   ├── app/
│   │   ├── ...
│   │   └── composer.json
│   │
│   └── nfe/              # Módulo NFe
│       ├── app/
│       ├── ...
│       └── composer.json
│
├── docs/                  # Documentação compartilhada
│   ├── ARQUITETURA.md
│   ├── INSTALACAO.md
│   └── DEPLOYMENT.md
│
├── scripts/               # Scripts compartilhados
│   ├── deploy.sh
│   └── backup.sh
│
└── README.md
```

### 21.2 Branches Sugeridas

```
main              # Produção estável
develop           # Desenvolvimento
feature/*         # Features específicas
hotfix/*          # Correções urgentes
release/*         # Preparação de releases
tenant/{nome}     # Customizações por tenant (se necessário)
```

### 21.3 Tags e Versionamento

**Semantic Versioning (MAJOR.MINOR.PATCH):**
```
v1.0.0            # Release inicial
v1.1.0            # Nova feature
v1.1.1            # Bug fix
v2.0.0            # Breaking change
```

**Tags por Módulo:**
```
core-v1.0.0
cestadeprecos-v1.0.0
nfe-v1.0.0
```

---

## 22. CHECKLIST DE RESTAURAÇÃO

### 22.1 Pré-requisitos do Servidor

**Software Necessário:**
- [ ] PHP 8.2+
- [ ] Composer 2.x
- [ ] Node.js 18+ e NPM
- [ ] PostgreSQL 14+
- [ ] Redis
- [ ] Nginx ou Apache
- [ ] Supervisor (para queues)
- [ ] Git

**Extensões PHP:**
- [ ] php8.2-cli
- [ ] php8.2-fpm
- [ ] php8.2-pgsql
- [ ] php8.2-mbstring
- [ ] php8.2-xml
- [ ] php8.2-curl
- [ ] php8.2-zip
- [ ] php8.2-gd
- [ ] php8.2-intl
- [ ] php8.2-redis
- [ ] php8.2-bcmath

**Ferramentas Opcionais:**
- [ ] Tesseract OCR (para OCR de PDFs)
- [ ] Imagick (para manipulação de imagens)

### 22.2 Configuração do Banco

```sql
-- 1. Criar banco
CREATE DATABASE minhadattatech_db;

-- 2. Criar usuário
CREATE USER minhadattatech_user WITH PASSWORD 'MinhaDataTech2024SecureDB';

-- 3. Conceder privilégios
GRANT ALL PRIVILEGES ON DATABASE minhadattatech_db TO minhadattatech_user;
GRANT ALL ON SCHEMA public TO minhadattatech_user;

-- 4. Configurar superuser para criação de tenants (se necessário)
ALTER USER minhadattatech_user WITH SUPERUSER;
```

### 22.3 Estrutura de Diretórios

```bash
# Criar estrutura base
mkdir -p /home/dattapro
mkdir -p /home/dattapro/modulos
cd /home/dattapro

# Criar diretórios de logs
mkdir -p /var/log/cestadeprecos
mkdir -p /var/log/minhadattatech
mkdir -p /var/log/nfe

# Permissões
chown -R www-data:www-data /home/dattapro
chmod -R 755 /home/dattapro
```

### 22.4 Clonagem e Configuração

**Core:**
```bash
cd /home/dattapro
git clone <repo-url> minhadattatech
cd minhadattatech

cp .env.example .env
# Editar .env com configurações corretas

composer install --no-dev --optimize-autoloader
php artisan key:generate
npm install && npm run build

php artisan migrate --force
php artisan db:seed (se houver seeders)
php artisan storage:link

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Módulo Cesta de Preços:**
```bash
cd /home/dattapro/modulos
git clone <repo-url> cestadeprecos
cd cestadeprecos

cp .env.example .env
# Editar .env com configurações corretas

composer install --no-dev --optimize-autoloader
php artisan key:generate
npm install && npm run build

php artisan migrate --force
php artisan storage:link

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Módulo NFe:**
```bash
cd /home/dattapro/modulos
git clone <repo-url> nfe
cd nfe

cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 22.5 Configuração de Serviços

**Systemd Services:**

**/etc/systemd/system/cestadeprecos.service**
```ini
[Unit]
Description=Cesta de Preços Module
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/dattapro/modulos/cestadeprecos
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8001
Restart=always

[Install]
WantedBy=multi-user.target
```

**/etc/systemd/system/nfe.service**
```ini
[Unit]
Description=NFe Module
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/dattapro/modulos/nfe
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8002
Restart=always

[Install]
WantedBy=multi-user.target
```

**Habilitar e iniciar:**
```bash
systemctl daemon-reload
systemctl enable cestadeprecos.service
systemctl enable nfe.service
systemctl start cestadeprecos.service
systemctl start nfe.service
```

**Supervisor (Queues):**

**/etc/supervisor/conf.d/cestadeprecos-worker.conf**
```ini
[program:cestadeprecos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/dattapro/modulos/cestadeprecos/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/cestadeprecos/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start cestadeprecos-worker:*
```

### 22.6 Configuração Nginx/Apache

**Nginx:**

**/etc/nginx/sites-available/minhadattatech**
```nginx
server {
    listen 80;
    server_name *.dattapro.online dattapro.online;
    root /home/dattapro/minhadattatech/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/minhadattatech /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 22.7 Cron Jobs

```bash
crontab -e -u www-data
```

```cron
# Laravel Scheduler (Core)
* * * * * cd /home/dattapro/minhadattatech && php artisan schedule:run >> /dev/null 2>&1

# Laravel Scheduler (Cesta de Preços)
* * * * * php artisan schedule:run >> /dev/null 2>&1

# Sincronização PNCP (diária às 2h)
0 2 * * * cd /home/dattapro/modulos/cestadeprecos && php artisan sincronizar:pncp-completo >> /var/log/cestadeprecos/sincronizacao.log 2>&1

# Importação CATMAT (semanal - domingo às 3h)
0 3 * * 0 cd /home/dattapro/modulos/cestadeprecos && php artisan importar:catmat >> /var/log/cestadeprecos/catmat.log 2>&1

# Limpeza de logs (mensal)
0 0 1 * * find /home/dattapro/*/storage/logs -type f -mtime +30 -delete
```

### 22.8 SSL/HTTPS (Opcional)

```bash
# Certbot para Let's Encrypt
apt-get install certbot python3-certbot-nginx
certbot --nginx -d dattapro.online -d *.dattapro.online
```

### 22.9 Verificação Final

```bash
# Verificar serviços
systemctl status cestadeprecos
systemctl status nfe
systemctl status nginx
systemctl status postgresql
systemctl status redis
supervisorctl status

# Verificar conexões
curl http://localhost:8001/health
curl http://localhost:8002/health
curl http://localhost/

# Verificar banco
php artisan db:check-setup

# Verificar logs
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

---

## 23. VARIÁVEIS DE AMBIENTE CRÍTICAS

### 23.1 Segurança (DEVEM ser alteradas)

```env
# CORE
APP_KEY=                     # Gerar: php artisan key:generate
DB_PASSWORD=                 # Senha forte do banco
DB_POSTGRES_PASSWORD=        # Senha do superuser PostgreSQL
TECHNICAL_PANEL_API_TOKEN=   # Token de segurança único

# CESTA DE PREÇOS
APP_KEY=                     # Gerar: php artisan key:generate
DB_PASSWORD=                 # Mesma senha do core

# APIs Externas (se aplicável)
PORTALTRANSPARENCIA_API_KEY= # Chave da API CGU
```

### 23.2 Configuração de Domínios

```env
# CORE
APP_URL=https://minha.dattatech.com.br
SESSION_DOMAIN=.dattatech.com.br

# CESTA DE PREÇOS
APP_URL=http://localhost:8001  # Ou URL interna
```

### 23.3 Email

```env
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```

---

## 24. MONITORAMENTO E LOGS

### 24.1 Logs Importantes

**Laravel (Cesta de Preços):**
```
storage/logs/laravel-{date}.log
storage/logs/importacao_catmat.log
storage/logs/sistema_detalhado/browser/browser-{date}.log
```

**Sistema:**
```
/var/log/nginx/access.log
/var/log/nginx/error.log
/var/log/postgresql/postgresql-14-main.log
/var/log/cestadeprecos/worker.log
```

### 24.2 Comandos de Monitoramento

```bash
# Verificar erros Laravel
tail -f storage/logs/laravel.log | grep ERROR

# Verificar uso de CPU/Memória
htop

# Verificar processos PHP
ps aux | grep php

# Verificar queues
php artisan queue:work --once
supervisorctl status cestadeprecos-worker:*

# Verificar banco
psql -U minhadattatech_user -d minhadattatech_db -c "SELECT COUNT(*) FROM tenants;"

# Health check
curl http://localhost:8001/health
```

### 24.3 Alertas Sugeridos

- Espaço em disco < 20%
- Memória disponível < 1GB
- Erros no log > 100/hora
- Queue jobs failed > 50
- Serviço down > 1 minuto

---

## 25. TAMANHOS E ESTATÍSTICAS

### 25.1 Resumo de Tamanhos

| Item | Tamanho |
|------|---------|
| Módulo Cesta de Preços (total) | 606 MB |
| - vendor/ | 149 MB |
| - node_modules/ | ~100 MB (estimado) |
| - storage/logs/ | ~50 MB |
| - CMED_EXTRAIDO/ | ~100 MB |
| - Código-fonte | ~10 MB |
| MinhaDattaTech Core (total) | 149 MB |
| - vendor/ | 69 MB |
| - node_modules/ | ~50 MB (estimado) |
| - Código-fonte | ~5 MB |
| Módulo NFe | ~100 MB (estimado) |

### 25.2 Contadores

| Métrica | Cesta de Preços | Core | NFe |
|---------|----------------|------|-----|
| Arquivos PHP | 9,658 | 7,669 | N/A |
| Arquivos Blade | 140 | ~20 | N/A |
| Migrations | 68 | 17 | N/A |
| Controllers | 23 | 9 | N/A |
| Models | 28+ | 9 | N/A |
| Services | 12 | 8 | N/A |
| Commands | 21 | 0 | N/A |
| Tabelas (DB) | 3 | 16 | 9 |

### 25.3 Linhas de Código (Estimativa)

**Cesta de Preços:**
- PHP: ~50,000 linhas
- Blade: ~15,000 linhas
- JavaScript: ~5,000 linhas
- CSS: ~2,000 linhas

**Core:**
- PHP: ~20,000 linhas
- Blade: ~3,000 linhas
- JavaScript: ~2,000 linhas

---

## 26. DEPENDÊNCIAS CRÍTICAS

### 26.1 Dependências Compartilhadas

**Ambos (Core + Módulos):**
```
Laravel Framework: ^11.31
PHP: ^8.2
PostgreSQL: 14+
Redis: (opcional mas recomendado)
```

### 26.2 Dependências Específicas - Cesta de Preços

**Críticas:**
```
barryvdh/laravel-dompdf: ^3.1     # PDF
mpdf/mpdf: ^8.2                    # PDF avançado
phpoffice/phpspreadsheet: ^5.1     # Excel
```

**Importantes:**
```
simplesoftwareio/simple-qrcode: ^4.2
smalot/pdfparser: ^2.12
thiagoalessio/tesseract_ocr: ^2.13
```

### 26.3 Dependências Específicas - Core

```
livewire/livewire: ^3.6            # Framework Livewire
```

---

## 27. PROBLEMAS CONHECIDOS E SOLUÇÕES

### 27.1 Migrations Duplicadas

**Problema:** Algumas migrations podem ter prefixos inconsistentes ou duplicadas.

**Solução:**
```bash
# Auditar migrations
php artisan migrate:status

# Rollback seletivo se necessário
php artisan migrate:rollback --step=1

# Verificar integridade
SELECT * FROM migrations ORDER BY batch, migration;
```

### 27.2 Prefixo de Tabelas

**Problema:** Inconsistência entre `cp_*` esperado e tabelas sem prefixo.

**Solução:**
- Padronizar todas as migrations para usar prefixo `cp_`
- Atualizar models para refletir nomes corretos
- Revisar migration: `2025_10_24_160533_corrigir_prefixo_tabelas_inconsistentes.php`

### 27.3 Arquivos Grandes no Repositório

**Problema:** Arquivos >10MB (CMED, CATMAT, logs) não devem estar no Git.

**Solução:**
```bash
# Adicionar ao .gitignore
echo "/CMED_EXTRAIDO/" >> .gitignore
echo "/storage/logs/" >> .gitignore
echo "*.xlsx" >> .gitignore
echo "*.log" >> .gitignore

# Remover do histórico (se já commitados)
git filter-branch --force --index-filter \
  "git rm -rf --cached --ignore-unmatch CMED_EXTRAIDO/" \
  --prune-empty --tag-name-filter cat -- --all
```

### 27.4 Permissões de Arquivo

**Problema:** Erros de permissão em `storage/` e `bootstrap/cache/`.

**Solução:**
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 27.5 Node Modules e Vendor

**Problema:** Tamanho excessivo do repositório se incluir dependências.

**Solução:**
- NUNCA commitar `vendor/` ou `node_modules/`
- Sempre incluir `composer.lock` e `package-lock.json`
- Documentar comandos de instalação no README

---

## 28. PLANO DE ORGANIZAÇÃO GITHUB

### 28.1 Estrutura Recomendada (Repositórios Separados)

**Repositório 1: dattapro/minhadattatech-core**
```
README.md                    # Descrição do core
INSTALL.md                   # Guia de instalação
ARCHITECTURE.md              # Documentação da arquitetura
CHANGELOG.md                 # Histórico de versões
.env.example                 # Template de configuração
.gitignore                   # Já existe e está completo
composer.json
package.json
app/
config/
database/
resources/
routes/
public/
```

**Repositório 2: dattapro/modulo-cestadeprecos**
```
README.md                    # Descrição do módulo
MODULE_INFO.md               # Info técnica do módulo
INSTALL.md                   # Guia de instalação
API_DOCS.md                  # Documentação das APIs
CHANGELOG.md                 # Histórico de versões
.env.example                 # Template de configuração
.gitignore                   # Adicionar regras extras
composer.json
package.json
app/
config/
database/
resources/
routes/
public/
scripts/                     # Scripts úteis
docs/                        # Documentação adicional
```

**Repositório 3: dattapro/modulo-nfe**
```
README.md
MODULE_INFO.md
.env.example
.gitignore
composer.json
app/
config/
database/
resources/
routes/
```

### 28.2 README.md Sugerido (Core)

```markdown
# MinhaDattaTech - Plataforma Multi-Tenant

Sistema de gerenciamento multi-tenant com arquitetura modular.

## Descrição

O MinhaDattaTech é uma plataforma SaaS que permite gerenciar múltiplos clientes (tenants) através de uma interface unificada, com módulos plugáveis para diferentes funcionalidades.

## Características

- ✅ Multi-tenancy com isolamento de dados
- ✅ Arquitetura modular (microservices)
- ✅ Autenticação e autorização robusta
- ✅ Proxy interno para comunicação entre módulos
- ✅ Interface desktop responsiva

## Tecnologias

- **Backend:** Laravel 11 + Livewire 3
- **Frontend:** TailwindCSS + Alpine.js
- **Database:** PostgreSQL 14+
- **Cache:** Redis
- **Web Server:** Nginx + Caddy

## Requisitos

- PHP 8.2+
- PostgreSQL 14+
- Composer 2.x
- Node.js 18+
- Redis (opcional)

## Instalação

Ver [INSTALL.md](INSTALL.md)

## Documentação

- [Arquitetura](ARCHITECTURE.md)
- [Módulos Disponíveis](MODULES.md)
- [API Reference](API.md)

## Licença

Proprietário - DattaTech © 2025
```

### 28.3 README.md Sugerido (Módulo Cesta de Preços)

```markdown
# Módulo Cesta de Preços

Sistema de orçamentos estimativos para compras públicas.

## Descrição

O módulo Cesta de Preços permite criar orçamentos estimativos baseados em múltiplas fontes de dados governamentais (PNCP, Compras.gov, CATMAT, etc).

## Características

- ✅ Pesquisa em 5+ fontes de dados governamentais
- ✅ Geração automática de PDFs
- ✅ Importação de planilhas Excel
- ✅ Solicitação de Cotação de Fornecedores (CDF)
- ✅ Mapa de fornecedores e atas de registro
- ✅ Análise crítica de preços

## Fontes de Dados

- PNCP (Portal Nacional de Contratações Públicas)
- Compras.gov (Comprasnet)
- CATMAT (Catálogo de Materiais)
- CMED (Medicamentos)
- Licitacon
- TCE-RS

## Instalação

Ver [INSTALL.md](INSTALL.md)

## Uso

```bash
# Iniciar o módulo
php artisan serve --port=8001

# Sincronizar dados
php artisan sincronizar:pncp-completo

# Importar CATMAT
php artisan importar:catmat
```

## Documentação

- [API Endpoints](docs/API.md)
- [Comandos Artisan](docs/COMMANDS.md)
- [Modelos de Dados](docs/MODELS.md)

## Licença

Proprietário - DattaTech © 2025
```

### 28.4 .gitignore Melhorado (Cesta de Preços)

```gitignore
# Laravel
/.phpunit.cache
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/storage/pail
/vendor
.env
.env.backup
.env.production
.phpactor.json
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/auth.json

# IDEs
/.fleet
/.idea
/.nova
/.vscode
/.zed
/.claude/settings.local.json

# Logs e Cache
/storage/logs/*.log
/storage/framework/cache/*
!/storage/framework/cache/.gitkeep
/storage/framework/sessions/*
!/storage/framework/sessions/.gitkeep
/storage/framework/views/*
!/storage/framework/views/.gitkeep
/storage/app/temp/*
!/storage/app/temp/.gitkeep
/storage/app/mpdf_temp/*
!/storage/app/mpdf_temp/.gitkeep

# Dados e Importações
/CMED_EXTRAIDO/*.json
/storage/app/private/catmat/*.json
/backups/*
!/backups/.gitkeep

# Arquivos grandes e temporários
*.xlsx
*.xls
*.pdf
*.PNG
*.png
*.jpg
*.jpeg
*.gif
*.log
*.csv

# Exceções (arquivos necessários)
!public/favicon.ico
!docs/**/*.png
!docs/**/*.jpg

# Backups de código
*.backup
*.backup-*
*.old
*-old.*
*.bak

# Específicos do projeto
/Tabela*.xlsx
/CMED*.xlsx
/tests/temp/*
/docs/prints/*
```

### 28.5 GitHub Actions (CI/CD) - Exemplo

**.github/workflows/tests.yml**
```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  laravel-tests:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:14
        env:
          POSTGRES_USER: test_user
          POSTGRES_PASSWORD: test_password
          POSTGRES_DB: test_db
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pgsql, mbstring, xml, curl, zip, gd, intl

      - name: Copy .env
        run: cp .env.example .env

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Generate key
        run: php artisan key:generate

      - name: Run Migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: test_db
          DB_USERNAME: test_user
          DB_PASSWORD: test_password

      - name: Execute tests
        run: vendor/bin/phpunit
```

---

## 29. CHECKLIST FINAL - PREPARAÇÃO GITHUB

### 29.1 Antes de Commitar

**Limpeza:**
- [ ] Remover arquivos `.backup`, `.old`, etc.
- [ ] Remover arquivos de teste (`.xlsx`, `.pdf`, `.png`)
- [ ] Limpar `storage/logs/`
- [ ] Limpar `storage/app/private/`
- [ ] Remover `CMED_EXTRAIDO/`
- [ ] Remover `backups/` (manter estrutura vazia)

**Verificação:**
- [ ] `.env` NÃO está no repositório
- [ ] `.env.example` está atualizado
- [ ] `.gitignore` está completo
- [ ] `composer.lock` está presente
- [ ] `package-lock.json` está presente
- [ ] README.md está escrito
- [ ] Documentação básica existe

**Segurança:**
- [ ] Nenhuma senha ou chave API no código
- [ ] Tokens e secrets em variáveis de ambiente
- [ ] APP_KEY não está hardcoded

### 29.2 Primeiro Commit

```bash
# 1. Inicializar repositório
git init

# 2. Adicionar remote
git remote add origin <repo-url>

# 3. Criar .gitignore completo
# (usar versão melhorada acima)

# 4. Adicionar arquivos
git add .

# 5. Verificar o que será commitado
git status
git diff --cached --stat

# 6. Commit inicial
git commit -m "feat: Initial commit - MinhaDattaTech Core v1.0.0

- Sistema multi-tenant completo
- Gerenciamento de módulos
- Autenticação e autorização
- Proxy interno para comunicação
- Interface desktop Livewire

Inclui:
- 17 migrations
- 9 models principais
- 8 services
- Configuração completa Laravel 11
- Documentação básica"

# 7. Push
git branch -M main
git push -u origin main

# 8. Tag da versão
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

### 29.3 Estrutura de Commits Sugerida

**Conventional Commits:**
```
feat: Nova funcionalidade
fix: Correção de bug
docs: Documentação
style: Formatação
refactor: Refatoração
test: Testes
chore: Manutenção
perf: Performance
```

**Exemplos:**
```
feat(orcamento): Adicionar geração de PDF
fix(api): Corrigir timeout na API PNCP
docs(readme): Atualizar guia de instalação
refactor(models): Simplificar relacionamentos
chore(deps): Atualizar Laravel para 11.31
```

---

## 30. PRÓXIMOS PASSOS

### 30.1 Curto Prazo (Imediato)

1. **Criar .env.example completo e documentado**
   - Incluir todas as variáveis necessárias
   - Adicionar comentários explicativos
   - Remover valores sensíveis

2. **Escrever README.md para cada repositório**
   - Descrição clara do projeto
   - Requisitos e instalação
   - Comandos básicos
   - Links para documentação

3. **Limpar arquivos desnecessários**
   - Remover backups de código
   - Remover arquivos de teste
   - Remover dados importados

4. **Criar INSTALL.md detalhado**
   - Passo a passo de instalação
   - Configuração do ambiente
   - Troubleshooting comum

5. **Primeiro commit e push**
   - Seguir checklist acima
   - Criar tag v1.0.0
   - Push para GitHub

### 30.2 Médio Prazo (1-2 semanas)

6. **Documentação Técnica**
   - ARCHITECTURE.md (diagramas da arquitetura)
   - API.md (endpoints e exemplos)
   - MODELS.md (estrutura do banco)
   - COMMANDS.md (comandos Artisan)

7. **Configurar CI/CD**
   - GitHub Actions para testes
   - Deploy automatizado (opcional)
   - Code quality checks

8. **Testes Automatizados**
   - Testes unitários principais
   - Testes de integração
   - Coverage report

9. **Organizar Documentação Claude**
   - Mover para `/docs`
   - Criar índice organizado
   - Manter apenas arquivos relevantes

### 30.3 Longo Prazo (1+ mês)

10. **Separação por Tenant**
    - Se necessário, criar branches por tenant
    - Documentar customizações específicas
    - Estratégia de merge

11. **Wiki e Documentação Online**
    - GitHub Wiki
    - GitHub Pages para docs
    - Tutoriais em vídeo (opcional)

12. **Versionamento Semântico**
    - Estabelecer política de releases
    - Changelog automatizado
    - Tags por módulo

13. **Backup e Disaster Recovery**
    - Scripts de backup automatizados
    - Documentação de restauração
    - Testes de recuperação

---

## 31. CONTATOS E SUPORTE

**Desenvolvedor Principal:**
- Nome: [A definir]
- Email: [A definir]

**Repositórios:**
- Core: https://github.com/dattapro/minhadattatech-core
- Cesta de Preços: https://github.com/dattapro/modulo-cestadeprecos
- NFe: https://github.com/dattapro/modulo-nfe

**Documentação:**
- Docs Online: [A definir]
- Wiki: [A definir]

---

## CONCLUSÃO

Este estudo completo mapeou toda a estrutura do sistema MinhaDattaTech multi-tenant, incluindo:

- ✅ 2 aplicações principais (Core + Módulos)
- ✅ 3 módulos identificados
- ✅ 7 tenants mapeados
- ✅ 85+ migrations
- ✅ 17,000+ arquivos PHP
- ✅ 160+ arquivos Blade
- ✅ 28 tabelas de banco de dados
- ✅ Estrutura completa de diretórios
- ✅ Dependências e tecnologias
- ✅ Configurações e variáveis de ambiente
- ✅ Plano completo de organização GitHub
- ✅ Checklist de restauração
- ✅ Guias de deployment

**O sistema está PRONTO para ser versionado no GitHub.**

**Próximo passo:** Executar checklist de preparação e fazer primeiro commit.

---

**Data do Estudo:** 31/10/2025  
**Versão:** 1.0.0  
**Autor:** Claude Code (Anthropic)  
**Status:** COMPLETO ✅
