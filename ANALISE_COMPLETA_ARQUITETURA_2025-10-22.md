# ANÁLISE COMPLETA E DETALHADA - MÓDULO CESTA DE PREÇOS

**Data**: 2025-10-22  
**Versão**: 1.0  
**Nível de Detalhe**: MUITO DETALHADO (THOROUGH)

---

## 1. ESTRUTURA GERAL DO PROJETO

### 1.1 Diretórios Principais

```
/home/dattapro/modulos/cestadeprecos/
├── app/                          # Código da aplicação
│   ├── Console/Commands/         # Comandos CLI
│   ├── Helpers/                  # Helpers customizados
│   ├── Http/
│   │   ├── Controllers/          # 14 Controllers (8.2K linhas de código)
│   │   └── Middleware/           # 5 Middlewares
│   ├── Mail/                     # Templates de e-mail
│   ├── Models/                   # 30+ Modelos (veja seção 2)
│   ├── Providers/                # AppServiceProvider
│   └── Services/                 # 6 Services
├── bootstrap/                    # Configuração inicial do app
├── config/                       # Configurações (database, session, etc)
├── database/
│   ├── migrations/               # 54 Migrations
│   └── seeders/                  # Data seeders
├── public/                       # Arquivos públicos (CSS, JS, imagens)
├── resources/
│   ├── views/                    # Templates Blade (30+ views)
│   └── css/                      # Estilos
├── routes/                       # Definição de rotas (web.php)
├── storage/                      # Logs, caches, uploads
├── tests/                        # Testes automatizados
└── vendor/                       # Dependências via Composer
```

### 1.2 Arquivos de Configuração Críticos

| Arquivo | Propósito | Status |
|---------|-----------|--------|
| `bootstrap/app.php` | Inicialização do Laravel 11 | CRÍTICO |
| `config/database.php` | Conexões de banco (3 conexões) | CRÍTICO |
| `routes/web.php` | Todas as rotas (832 linhas) | CRÍTICO |
| `.env` | Variáveis de ambiente | SENSÍVEL |
| `config/session.php` | Configuração de sessão | IMPORTANTE |

### 1.3 Padrões de Organização

**Arquitetura**: **MULTITENANT COM ISOLAMENTO POR DATABASE**

- **Isolamento**: Cada prefeitura tem seu próprio banco PostgreSQL
- **Prefixo de Tabelas**: `cp_` para isolamento dentro do mesmo banco (se necessário)
- **Banco Principal**: `minhadattatech_db` contém dados compartilhados (CATMAT, CMED, Orientações Técnicas)
- **Autenticação**: Via proxy do MinhaDattaTech (headers X-User-*, X-Tenant-*)

---

## 2. MODELS (app/Models/)

### 2.1 Listagem Completa de Models (30 modelos)

#### Modelos de Orçamento
1. **Orcamento** (8.2K) - Entidade principal
2. **OrcamentoItem** (2.7K) - Itens do orçamento
3. **Lote** (1.1K) - Agrupamentos de itens

#### Modelos de CDF (Cotação Direta com Fornecedor)
4. **SolicitacaoCDF** (3.5K) - Solicitações enviadas
5. **SolicitacaoCDFItem** (0.6K) - Itens da solicitação
6. **RespostaCDF** (1.3K) - Resposta do fornecedor
7. **RespostaCDFItem** (0.9K) - Itens da resposta
8. **RespostaCDFAnexo** (1.2K) - Anexos da resposta

#### Modelos de Fornecedores
9. **Fornecedor** (2.2K) - Dados do fornecedor
10. **FornecedorItem** (0.6K) - Produtos fornecidos

#### Modelos de Dados Externos
11. **Catmat** (2.4K) - Catálogo de Materiais (banco pgsql_main)
12. **MedicamentoCmed** (5.9K) - Medicamentos CMED (banco pgsql_main)
13. **CatalogoProduto** (3.3K) - Catálogo local
14. **HistoricoPreco** (2.7K) - Históricos de preço

#### Modelos de Contratações
15. **ContratoPNCP** (3.7K) - Contratos PNCP
16. **ContratacaoSimilar** (1.3K) - Contratações similares
17. **ContratacaoSimilarItem** (0.9K) - Itens similares
18. **ArpCabecalho** (3.3K) - ARP (Ata de Registro de Preço)
19. **ArpItem** (2.5K) - Itens da ARP

#### Modelos de E-commerce
20. **ColetaEcommerce** (0.7K) - Coleta de dados e-commerce
21. **ColetaEcommerceItem** (0.6K) - Itens coletados

#### Modelos de Auditoria
22. **AuditLogItem** (5.1K) - Log de auditoria
23. **AuditSnapshot** (1.9K) - Snapshots de cálculos

#### Modelos de Configuração
24. **User** (1.2K) - Usuários do sistema
25. **Notificacao** (1.3K) - Notificações
26. **Anexo** (2.3K) - Anexos genéricos
27. **Orgao** (0.8K) - Órgãos/Prefeituras
28. **OrientacaoTecnica** (1.6K) - Orientações técnicas
29. **CotacaoExterna** (1.2K) - Cotações externas
30. **ConsultaPncpCache** (2.5K) - Cache de consultas PNCP

### 2.2 Relacionamentos Principais Entre Models

```
Orcamento (raiz)
├── hasMany → OrcamentoItem (LINHAS DO ORÇAMENTO)
├── hasMany → Lote (AGRUPAMENTOS)
├── hasMany → SolicitacaoCDF (CDF)
├── hasMany → ContratacaoSimilar (SIMILARES)
├── hasMany → ColetaEcommerce (E-COMMERCE)
└── belongsTo → User (CRIADOR)

OrcamentoItem
├── belongsTo → Orcamento
├── belongsTo → Lote (opcional)
└── hasMany → AuditLogItem (HISTÓRICO)

SolicitacaoCDF
├── belongsTo → Orcamento
├── hasMany → SolicitacaoCDFItem
└── hasOne → RespostaCDF (RESPOSTA DO FORNECEDOR)

RespostaCDF
├── belongsTo → SolicitacaoCDF
├── hasMany → RespostaCDFItem (ITENS DA RESPOSTA)
└── hasMany → RespostaCDFAnexo (ANEXOS)

Catmat (BANCO COMPARTILHADO: pgsql_main)
├── hasMany → ArpItem
├── hasMany → CatalogoProduto
└── hasMany → HistoricoPreco
```

### 2.3 Tabelas do Banco de Dados (54 Migrations)

#### Tabelas de Orçamento
- `orcamentos` (núcleo)
- `itens_orcamento` (itens)
- `lotes` (agrupamentos)

#### Tabelas de CDF
- `solicitacoes_cdf`
- `solicitacoes_cdf_itens`
- `respostas_cdf`
- `respostas_cdf_itens`
- `respostas_cdf_anexos`

#### Tabelas de Fornecedores
- `fornecedores`
- `fornecedor_itens`

#### Tabelas Compartilhadas (pgsql_main)
- `catmat` (Catálogo de Materiais)
- `medicamentos_cmed` (Medicamentos)
- `arp_cabecalhos` (ARPs)
- `arp_itens` (Itens ARP)
- `catalogo_produtos` (Catálogo local)
- `historico_precos` (Histórico)

#### Tabelas de Auditoria
- `audit_log_itens` (Auditoria)
- `audit_snapshots` (Snapshots)

#### Tabelas de Sistema
- `users` (Usuários)
- `notificacoes` (Notificações)
- `anexos` (Anexos genéricos)
- `orgaos` (Prefeituras/Órgãos)
- `orientacoes_tecnicas` (Orientações)

---

## 3. CONTROLLERS (app/Http/Controllers/)

### 3.1 Listagem de Controllers

| Controller | Linhas | Propósito | Status |
|-----------|--------|----------|--------|
| **OrcamentoController** | 8.259 | Orçamentos (CRUD + cálculos) | CRÍTICO |
| **FornecedorController** | 2.605 | Fornecedores (CRUD + importação) | GRANDE |
| **PesquisaRapidaController** | 1.388 | Pesquisa multi-fonte | GRANDE |
| **CotacaoExternaController** | 1.297 | Cotação externa | MÉDIO |
| **CatalogoController** | 872 | Catálogo de produtos | MÉDIO |
| **MapaAtasController** | 562 | Mapa de atas PNCP | MÉDIO |
| **CdfRespostaController** | 550 | Respostas de CDF | MÉDIO |
| **ConfiguracaoController** | 367 | Configurações do órgão | PEQUENO |
| **OrgaoController** | 185 | Gerenciar órgãos | PEQUENO |
| **LogController** | 166 | Sistema de logs | PEQUENO |
| **CatmatController** | 165 | CATMAT (autocomplete) | PEQUENO |
| **CnpjController** | 98 | Consulta CNPJ | PEQUENO |
| **AuthController** | 92 | Autenticação | PEQUENO |
| **OrientacaoTecnicaController** | 1.115 | Orientações técnicas | PEQUENO |

### 3.2 OrcamentoController - O Coração do Sistema

**Tamanho**: 8.259 linhas (GIGANTE!)

**Métodos Principais**:
- `create()` - Formulário criação
- `store()` - Salvar novo orçamento
- `elaborar()` - Tela de elaboração (MAIS COMPLEXA)
- `show()` - Visualizar orçamento
- `storeItem()` - Adicionar item
- `updateItem()` - Editar item
- `salvarPrecoItem()` - Salvar preço via AJAX
- `aplicarSaneamento()` - Saneamento estatístico
- `fixarSnapshot()` - Congelar cálculos
- `importarDocumento()` - Importar de PDF/Excel
- `gerarPDF()` - Gerar PDF para download
- `concluir()` - Finalizar orçamento

**Funcionalidades**:
1. Criação de orçamentos (do zero, cópia, importação)
2. Gerenciar itens (adicionar, editar, deletar)
3. Cotação de preços (PNCP, CMED, Compras.gov, local)
4. Saneamento de amostras (método desvio-padrão)
5. Análise estatística (média, mediana, CV, DP)
6. Curva ABC
7. Exportação (Excel, PDF)

### 3.3 FornecedorController - Gerenciamento de Fornecedores

**Tamanho**: 2.605 linhas

**Funcionalidades**:
1. CRUD de fornecedores
2. Importação via planilha Excel
3. Sincronização com PNCP
4. Busca de fornecedores por item
5. Consulta CNPJ (Receita Federal)

---

## 4. MIDDLEWARES (app/Http/Middleware/)

### 4.1 Listagem de Middlewares

1. **ProxyAuth** (282 linhas) - CRÍTICO
2. **EnsureAuthenticated** - Verifica autenticação
3. **CleanDuplicateCookies** - Remove cookies duplicados
4. **ForceSaveSession** - Força salvamento da sessão
5. **InternalOnly** - Restringe a IPs internos
6. **DetailedLoggingMiddleware** - Log detalhado

### 4.2 ProxyAuth - O Middleware de Autenticação Multitenant

**Localização**: `/app/Http/Middleware/ProxyAuth.php`

**Responsabilidades**:

1. **Autenticação via Proxy do MinhaDattaTech**
   - Recebe headers: X-User-Id, X-User-Email, X-User-Name, X-Tenant-Id, X-Tenant-Subdomain
   - Cria/atualiza usuário no banco local
   - Autentica via Laravel Auth

2. **Configuração Dinâmica do Banco de Dados**
   - Recebe: X-DB-Name, X-DB-Host, X-DB-User, X-DB-Password
   - Configura a conexão 'pgsql' DINAMICAMENTE
   - Cada requisição pode apontar para um banco diferente

3. **Persistência de Sessão**
   - Salva dados do tenant e usuário na sessão
   - Reconstrói contexto em requisições subsequentes
   - Evita reconfigurações desnecessárias

4. **Suporte a Rotas Públicas**
   - Rotas CDF não exigem autenticação completa
   - Usa apenas banco dinâmico

**Fluxo de Autenticação**:

```
Requisição do Proxy
    ↓
ProxyAuth verifica se:
  1. É rota pública CDF? SIM → Skip auth, usar BD dinâmico
  2. Tem dados de sessão? SIM → Restaurar contexto
  3. Tem headers X-User-*? SIM → Autenticar e salvar sessão
    ↓
Configura BD dinamicamente (pgsql)
    ↓
Autentica usuário (cria se não existir)
    ↓
Requisição prossegue com contexto
```

---

## 5. ROTAS (routes/web.php - 832 linhas)

### 5.1 Estrutura de Rotas

#### Rotas Públicas
```
/login                              - Formulário de login
/logout                             - Sair
/health                             - Health check para proxy
/orcamentos/{id}/preview            - Preview do orçamento (PUBLIC)
/orcamentos/{id}/pdf                - Download PDF (PUBLIC)
/pncp/buscar                        - Busca PNCP (PUBLIC)
/compras-gov/buscar                 - Busca Compras.gov (PUBLIC)
/cmed/buscar                        - Busca medicamentos (PUBLIC)
/responder-cdf/{token}              - Formulário resposta CDF (PUBLIC)
/api/cdf/responder                  - Salvar resposta CDF (PUBLIC)
/api/cdf/consultar-cnpj             - Consultar CNPJ (PUBLIC)
/api/logs/browser                   - Receber logs do navegador (PUBLIC)
```

#### Rotas Protegidas por Autenticação
```
/dashboard                          - Dashboard principal
/pesquisa-rapida                    - Busca multi-fonte
/cdfs-enviadas                      - Listar CDFs

ORCAMENTOS (16 rotas)
  /orcamentos/novo                  - Criar novo
  /orcamentos/pendentes             - Listar pendentes
  /orcamentos/realizados            - Listar realizados
  /orcamentos/{id}/elaborar         - Tela elaboração (MAIS USADA)
  /orcamentos/{id}/itens            - CRUD itens
  /orcamentos/{id}/coleta-ecommerce - Salvar coleta
  /orcamentos/{id}/solicitar-cdf    - Salvar CDF
  /orcamentos/{id}/contratacoes-similares - Salvar similares

FORNECEDORES (9 rotas)
  /fornecedores/                    - CRUD
  /fornecedores/importar            - Importar planilha
  /fornecedores/buscar-por-item     - Buscar por item

CONFIGURAÇÕES (3 rotas)
  /configuracoes/                   - Gerenciar configurações do órgão
  /configuracoes/brasao             - Upload brasão

API (28 rotas internas)
  /api/catmat/*                     - CATMAT (autocomplete, sugestões)
  /api/mapa-atas/*                  - ARPs
  /api/catalogo/*                   - Catálogo
  /api/fornecedores/*               - Fornecedores
  /api/cdf/*                        - CDF (respostas)
  /api/notificacoes/*               - Notificações
  /api/orgaos/*                     - Órgãos
```

### 5.2 Rotas de Maior Importância

1. **POST /orcamentos/novo** - Cria orçamento
2. **GET /orcamentos/{id}/elaborar** - Renderiza página de elaboração (AJAX-heavy)
3. **POST /orcamentos/{id}/itens** - Adiciona item
4. **POST /orcamentos/{id}/salvar-preco-item** - Salva preço (MODAL COTAÇÃO)
5. **POST /orcamentos/{id}/itens/{item_id}/aplicar-saneamento** - Saneamento
6. **POST /orcamentos/{id}/concluir** - Finaliza orçamento
7. **GET /responder-cdf/{token}** - Formulário CDF (PÚBLICO)
8. **POST /api/cdf/responder** - Salva resposta CDF (PÚBLICO)

---

## 6. SERVICES E HELPERS

### 6.1 Services (app/Services/)

#### 1. EstatisticaService (10.6K)
- Cálculos estatísticos: média, mediana, DP
- Saneamento de amostras (método desvio-padrão)
- Cálculo de Coeficiente de Variação (CV)
- Métodos de obtenção de preço: MÉDIA, MEDIANA, MENOR

#### 2. CurvaABCService (5.0K)
- Análise Curva ABC
- Classificação A (80%), B (15%), C (5%)
- Cálculo de participação

#### 3. CnpjService (9.1K)
- Consulta CNPJ na Receita Federal (via ReceitaWS)
- Validação de CNPJ
- Extração de dados do fornecedor

#### 4. LicitaconService (12.1K)
- Integração com API Licitação
- Busca de contratos e preços
- Cache de consultas

#### 5. PDF Service (pacote)
- FormatoDetector.php - Detecta tipo de documento
- FormatoExtrator.php - Extrai dados
- PDFDetectorManager.php - Gerencia detectores
- Detectores: GenéricoDetector, MapaApuracaoDetector, TabelaHorizontalDetector
- Extratores: GenéricoExtrator, MapaApuracaoExtrator, TabelaHorizontalExtrator

### 6.2 Helpers (app/Helpers/)

1. **NormalizadorHelper** - Normalização de dados (acentos, maiúsculas, etc)
2. **TagsFornecedorHelper** - Gerenciamento de tags de fornecedores

---

## 7. VIEWS (resources/views/)

### 7.1 Estrutura de Templates

```
resources/views/
├── layouts/
│   ├── app.blade.php              - Layout principal
│   └── ...
├── auth/
│   └── login.blade.php            - Tela de login
├── dashboard.blade.php            - Dashboard
├── orcamentos/
│   ├── create.blade.php           - Criar orçamento
│   ├── elaborar.blade.php         - TELA PRINCIPAL (HTML COMPLEXO)
│   ├── show.blade.php             - Visualizar
│   ├── edit.blade.php             - Editar
│   ├── pendentes.blade.php        - Listar pendentes
│   ├── realizados.blade.php       - Listar realizados
│   ├── pdf.blade.php              - Template PDF
│   ├── preview.blade.php          - Preview público
│   ├── _modal-cotacao.blade.php   - MODAL COTAÇÃO (AJAX)
│   └── templates/
│       ├── padrao.blade.php       - Padrão PDF
│       └── mapa-apuracao.blade.php - Mapa de apuração PDF
├── cdf/
│   ├── listar.blade.php           - Listar CDFs
│   ├── resposta-fornecedor.blade.php - Formulário resposta
│   ├── visualizar-resposta.blade.php - Ver resposta
│   └── resposta-invalida.blade.php - Erro
├── fornecedores/
│   ├── index.blade.php            - CRUD
│   └── ...
├── catalogo.blade.php             - Catálogo
├── configuracoes/
│   └── index.blade.php            - Configurações do órgão
├── pesquisa-rapida.blade.php      - Pesquisa multi-fonte
├── mapa-de-atas.blade.php         - Mapa de atas
├── mapa-de-fornecedores.blade.php - Mapa de fornecedores
└── emails/
    └── cdf-solicitacao.blade.php  - E-mail CDF
```

### 7.2 View Mais Importante: elaborar.blade.php

**Tamanho**: Arquivo grande com muita lógica JavaScript

**Seções Principais**:
1. Seção 1: Dados básicos do orçamento
2. Seção 2: Metodologia (CV, DP, medida de tendência central)
3. Seção 3: Itens (tabela principal - AJAX-heavy)
4. Seção 4: Cotação (modal de preços - BUSCA EM 4 FONTES)
5. Seção 5: Análise crítica (saneamento de amostras)
6. Seção 6: Dados do orçamentista (assinatura)
7. Seção 7: Revisão e finalização

**Tecnologias**:
- Blade (templates Laravel)
- Alpine.js (interatividade)
- Fetch API (AJAX)
- Chart.js (gráficos)

---

## 8. ARQUITETURA MULTITENANT

### 8.1 Isolamento por Banco de Dados

**Modelo**: **Database-per-Tenant**

```
Sistema Central (MinhaDattaTech)
├── minhadattatech_db (banco principal)
│   ├── cp_orcamentos              (COMPARTILHADO VIA PROXY)
│   ├── cp_catmat                  (COMPARTILHADO - dados globais)
│   ├── cp_medicamentos_cmed       (COMPARTILHADO - dados globais)
│   └── cp_orientacoes_tecnicas    (COMPARTILHADO - dados globais)
│
├── prefeitura_a_db (Prefeitura A)
│   ├── cp_orcamentos              (ISOLADO)
│   ├── cp_itens_orcamento         (ISOLADO)
│   ├── cp_fornecedores            (ISOLADO)
│   └── ... (todas as outras tabelas)
│
└── prefeitura_b_db (Prefeitura B)
    ├── cp_orcamentos              (ISOLADO)
    ├── cp_itens_orcamento         (ISOLADO)
    ├── cp_fornecedores            (ISOLADO)
    └── ... (todas as outras tabelas)
```

### 8.2 Fluxo de Identificação do Tenant

```
1. Requisição chega no Proxy (MinhaDattaTech)
   ↓
2. Proxy extrai headers X-Tenant-*, X-DB-* e X-User-*
   ↓
3. Proxy encaminha requisição para módulo com headers
   ↓
4. Middleware ProxyAuth recebe
   ↓
5. ProxyAuth configura BD dinamicamente:
   - Altera config(['database.connections.pgsql' => ...])
   - Desconecta/reconecta a conexão padrão
   ↓
6. Models usam 'pgsql' (padrão) → conectam ao banco correto
   ↓
7. Models que usam 'pgsql_main' → SEMPRE conectam ao banco principal
   (CATMAT, CMED, Orientações Técnicas)
```

### 8.3 Conexões de Banco de Dados

| Nome | Banco | Propósito | Dinâmico? |
|------|-------|-----------|-----------|
| `pgsql` | Do tenant (X-DB-Name) | Dados específicos do tenant | SIM |
| `pgsql_main` | minhadattatech_db | Dados compartilhados (CATMAT, CMED) | NÃO |
| `pgsql_sessions` | Do tenant | Sessões do Laravel | NÃO |

### 8.4 Prefixos de Tabelas

Todas as tabelas usam prefixo `cp_` para isolamento adicional:

```
cp_orcamentos
cp_itens_orcamento
cp_fornecedores
cp_solicitacoes_cdf
cp_respostas_cdf
cp_usuarios
cp_notificacoes
... etc
```

---

## 9. FLUXO DE INTEGRAÇÃO COM MINHADATTATECH

### 9.1 Arquitetura de Proxy

O módulo é acessado **APENAS** através do proxy do MinhaDattaTech:

```
Cliente
  ↓
MinhaDattaTech (Portal Central)
  ├── Valida usuário
  ├── Identifica tenant (prefeitura)
  ├── Busca configuração de BD do tenant
  ↓
  Encaminha para Módulo Cesta de Preços
  (com headers X-User-*, X-Tenant-*, X-DB-*)
  ↓
Cesta de Preços
  ├── ProxyAuth configura BD dinamicamente
  ├── Autentica usuário
  ↓
  Executa ação
  ↓
  Retorna resposta
```

### 9.2 Headers Esperados do Proxy

```
X-User-Id              → ID do usuário
X-User-Email           → E-mail do usuário
X-User-Name            → Nome do usuário
X-User-Role            → Role (admin, user, etc)
X-Tenant-Id            → ID do tenant (prefeitura)
X-Tenant-Subdomain     → Subdomínio (ex: prefeitura-a)
X-Tenant-Name          → Nome da prefeitura
X-DB-Name              → Nome do banco de dados
X-DB-Host              → Host do banco (padrão: 127.0.0.1)
X-DB-User              → Usuário do banco
X-DB-Password          → Senha do banco
```

### 9.3 Rotas de Integração com Proxy

**Health Check** (verificar se módulo está online):
```
GET /health
```

Retorna:
```json
{
  "status": "online",
  "module": "cestadeprecos",
  "version": "1.0.0",
  "timestamp": "2025-10-22T..."
}
```

**Rota de Info** (DEBUG - apenas em ambiente local):
```
GET /info
```

Retorna dados de tenant, usuário, headers, etc.

---

## 10. FLUXOS DE NEGÓCIO PRINCIPAIS

### 10.1 Fluxo Criar Orçamento

```
1. POST /orcamentos/novo (validar dados)
   ↓
2. Criar registro em cp_orcamentos
   ↓
3. Se documento: extrair dados (PDF/Excel) → criar OrcamentoItems
   ↓
4. Redirecionar para /orcamentos/{id}/elaborar
```

### 10.2 Fluxo Adicionar Item com Cotação

```
1. POST /orcamentos/{id}/salvar-preco-item (AJAX)
   ↓
2. Recebe:
   - item_id
   - preco_unitario
   - fonte (PNCP, CMED, COMPRAS.GOV, LOCAL)
   ↓
3. Salva em OrcamentoItem.preco_unitario
   ↓
4. Registra auditoria em AuditLogItem
   ↓
5. Retorna JSON com status
```

### 10.3 Fluxo Busca de Preços (MODAL COTAÇÃO)

**4 Fontes de Busca**:

```
MODAL DE COTAÇÃO (Cliente clica em "Cotar Preços")
  ↓
Cliente digita descrição (ex: "arroz 5kg")
  ↓
JavaScript dispara 4 chamadas paralelas:

1. PNCP (FIEL, SISTEMA DE PREÇOS)
   GET /pncp/buscar?termo=arroz 5kg
   → Busca em ContratoPNCP
   ↓

2. CMED (Medicamentos)
   GET /cmed/buscar?termo=arroz 5kg
   → Busca em MedicamentoCmed
   ↓

3. COMPRAS.GOV (API Externa)
   GET /compras-gov/buscar?termo=arroz 5kg
   → API: https://dadosabertos.compras.gov.br
   → Busca CATMAT local + API de preços
   ↓

4. BANCO LOCAL
   GET /pesquisa/buscar?termo=arroz 5kg
   → Busca em CatalogoProduto + HistoricoPreco
   ↓

Agrupa resultados
  ↓
Exibe tabela com checkbox
  ↓
Cliente seleciona e clica "Salvar"
  ↓
POST /orcamentos/{id}/salvar-preco-item
  ↓
Salva no OrcamentoItem
```

### 10.4 Fluxo Saneamento de Amostras (FASE 2)

```
1. Cliente clica "Aplicar Saneamento"
   ↓
2. POST /orcamentos/{id}/itens/{item_id}/aplicar-saneamento
   ↓
3. Backend carrega amostras do item
   ↓
4. EstatisticaService.aplicarSaneamentoDP()
   → Calcula média (μ) e desvio-padrão (σ)
   → Define limites: [μ-σ, μ+σ]
   → Marca amostras fora dos limites como EXPURGADAS
   → Calcula CV dos válidos
   ↓
5. Salva snapshot em OrcamentoItem:
   - calc_n_validas
   - calc_media
   - calc_mediana
   - calc_dp
   - calc_cv
   - calc_metodo (MEDIA/MEDIANA/MENOR - baseado em CV)
   ↓
6. Retorna resultado
```

### 10.5 Fluxo CDF (Cotação Direta com Fornecedor)

```
PASSO 1: Criar Solicitação CDF
  ↓
POST /orcamentos/{id}/solicitar-cdf
  ├── Dados do fornecedor (CNPJ, razão social, email)
  ├── Itens da solicitação
  ├── Justificativas (fornecedor único, produto exclusivo, urgência)
  └── Prazos

PASSO 2: Gerar Link de Resposta
  ↓
Sistema gera:
  - Token único (UUID)
  - Data de validade
  - URL pública: https://{subdomain}.dattapro.online/responder-cdf/{token}
  ↓
Email enviado para fornecedor com link

PASSO 3: Fornecedor Acessa Link (PÚBLICO)
  ↓
GET /responder-cdf/{token}
  → Valida token e data
  → Renderiza formulário de resposta (blade)
  ↓
Fornecedor preenche:
  - Preços dos itens
  - Forma de pagamento
  - Validade da proposta
  - Observações

PASSO 4: Salvar Resposta (PÚBLICO)
  ↓
POST /api/cdf/responder (validado por token)
  → Cria RespostaCDF
  → Cria RespostaCDFItem para cada item
  → Registra anexos
  ↓
Usuário interno vê resposta em /cdfs-enviadas
```

---

## 11. INTEGRAÇÃO DE APIs EXTERNAS

### 11.1 PNCP (Portal Nacional de Contratações Públicas)

**Endpoint**: Interno (busca em contrato locais)

```
GET /pncp/buscar?termo=arroz
GET /api/catalogo/buscar-pncp
```

**Dados Buscados**: ContratoPNCP (tabela local sincronizada)

### 11.2 CMED (Câmara de Medicamentos)

**Origem**: Banco local (MedicamentoCmed)

```
GET /cmed/buscar?termo=dipirona
```

**Dados**: Preço, laboratório, substância

### 11.3 Compras.gov (API Externa)

**Endpoint**: https://dadosabertos.compras.gov.br

**Dois Endpoints**:
1. Busca CATMAT: `/modulo-material/4_consultarItemMaterial`
2. Busca Preços: `/modulo-pesquisa-preco/1_consultarMaterial`

**Fluxo**:
```
Buscar termo → CATMAT local (fulltext) → 20 principais
Para cada código → API Compras.gov (preços) → Agregador
```

### 11.4 Receita Federal (ConsultaCNPJ via ReceitaWS)

**Serviço**: ReceitaWS

```
GET https://www.receitaws.com.br/v1/cnpj/{cnpj}
```

**Dados Retornados**: Razão social, nome fantasia, endereço, situação

---

## 12. SEGURANÇA E AUTENTICAÇÃO

### 12.1 Camadas de Segurança

1. **Proxy do MinhaDattaTech** - Primeira linha de defesa
2. **Middleware ProxyAuth** - Valida headers e sessão
3. **Middleware EnsureAuthenticated** - Verifica Auth Laravel
4. **CSRF Protection** - Desabilitado para APIs específicas
5. **Prefixo de Tabelas** - Isolamento adicional (cp_)

### 12.2 Rotas Públicas (Sem Autenticação)

- `/login`, `/logout` - Autenticação (não usada via proxy)
- `/health` - Health check
- `/orcamentos/{id}/preview` - Preview público do orçamento
- `/orcamentos/{id}/pdf` - Download PDF
- `/pncp/buscar`, `/cmed/buscar`, `/compras-gov/buscar` - Buscas públicas
- `/responder-cdf/{token}` - Formulário CDF (validado por token)
- `/api/cdf/responder` - Salvar resposta CDF (validado por token)
- `/api/cdf/consultar-cnpj` - Consultar CNPJ
- `/api/logs/browser` - Receber logs

### 12.3 Token de CDF

**Geração**:
```php
$token = Str::uuid()->toString();
$validoAte = now()->addDays(7); // 7 dias
```

**Validação**:
```php
if (!$solicitacao->linkValido()) {
    // Token expirado ou já respondido
}
```

---

## 13. CACHE E PERFORMANCE

### 13.1 Cache de Consultas

**ConsultaPncpCache** - Cache de buscas PNCP
**Licitacon Cache** - Cache de Licitação

**TTL**: Configurável (padrão: 24 horas)

### 13.2 Banco de Dados Compartilhado

CATMAT, CMED, Orientações Técnicas em `pgsql_main`:
- Acesso rápido
- Compartilhado entre todos os tenants
- Dados críticos para buscas

### 13.3 Fulltext Search (PostgreSQL)

CATMAT usa busca full-text em português:

```sql
WHERE to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', termo)
```

---

## 14. SISTEMA DE LOGS E AUDITORIA

### 14.1 AuditLogItem - Auditoria Detalhada

Registra TODAS as mudanças:

```
EVENT_APPLY_SANITIZATION_DP       - Saneamento DP
EVENT_APPLY_SANITIZATION_MEDIAN   - Saneamento Mediana
EVENT_PURGE_SAMPLE                - Expurgo manual
EVENT_REVALIDATE_SAMPLE           - Revalidação
EVENT_CHANGE_METHODOLOGY          - Mudança de metodologia
EVENT_ADJUST_CONVERSION           - Ajuste de conversão
EVENT_EDIT_SAMPLE                 - Edição de amostra
EVENT_ADD_ATTACHMENT              - Anexo adicionado
EVENT_REMOVE_ATTACHMENT           - Anexo removido
EVENT_UPDATE_LINK_QR              - Link/QR atualizado
EVENT_FIX_SNAPSHOT                - Snapshot fixado
EVENT_GENERATE_PDF                - PDF gerado
```

### 14.2 Logs do Navegador

**Endpoint**: POST `/api/logs/browser`

**Dados**: Nível (info, warning, error), mensagem, stack trace

---

## 15. TABELAS DE REFERÊNCIA

### 15.1 Tabelas Mais Críticas

| Tabela | Registros | Tamanho | Propósito |
|--------|-----------|--------|----------|
| `cp_orcamentos` | Centenas | MB | Núcleo |
| `cp_itens_orcamento` | Milhares | MB | Linha itens |
| `cp_catmat` | 50+ mil | MB | Catálogo |
| `cp_fornecedores` | Centenas | MB | Fornecedores |
| `cp_solicitacoes_cdf` | Centenas | MB | CDFs |
| `cp_contratos_pncp` | Milhares | MB | Contatos |
| `audit_log_itens` | Milhares | MB | Auditoria |

### 15.2 Queries Críticas

**Buscar orçamentos do tenant**:
```php
Orcamento::where('tenant_id', $tenantId)->get();
```

**Buscar items com preços**:
```php
$orcamento->itens()
    ->whereNotNull('preco_unitario')
    ->get();
```

**Buscar CATMAT por texto**:
```php
Catmat::whereRaw(
    "to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)",
    [$termo]
)->limit(20)->get();
```

---

## 16. FLUXO TÉCNICO COMPLETO DE UMA REQUISIÇÃO

```
1. CLIENTE FAZ REQUISIÇÃO
   GET /orcamentos/123/elaborar
   
2. PROXY DO MINHADATTATECH
   - Valida autenticação
   - Extrai tenant_id = 1, user_id = 42
   - Busca configuração de BD do tenant
   - Encaminha com headers:
     X-User-Id: 42
     X-Tenant-Id: 1
     X-DB-Name: prefeitura_a_db
     X-DB-Host: 127.0.0.1
     X-DB-User: prefeitura_a_user
     X-DB-Password: ***

3. MÓDULO RECEBE
   ↓
4. MIDDLEWARE: CleanDuplicateCookies
   - Remove cookies duplicados
   ↓
5. MIDDLEWARE: StartSession
   - Inicia sessão do Laravel
   ↓
6. MIDDLEWARE: ProxyAuth
   - Verifica se tem sessão válida
   - Se não: configura BD dinamicamente
   - Autentica usuário
   - Salva contexto na sessão
   ↓
7. MIDDLEWARE: ForceSaveSession
   - Força salvamento da sessão
   ↓
8. MIDDLEWARE: EnsureAuthenticated
   - Verifica se Auth::check()
   ↓
9. ROUTER
   - Encontra rota: OrcamentoController@elaborar
   ↓
10. CONTROLLER
    - Busca orçamento:
      $orcamento = Orcamento::findOrFail(123);
      (usa conexão 'pgsql' configurada dinamicamente)
    ↓
    - Busca itens:
      $orcamento->itens()->get();
    ↓
11. VIEW (elaborar.blade.php)
    - Renderiza HTML
    - Carrega JavaScript (Alpine.js, Fetch API)
    ↓
12. RESPOSTA
    - HTML com tabelas interativas
    - Scripts preparados para AJAX
    ↓
13. CLIENTE RECEBE
    - HTML renderizado
    - JavaScript pronto para interação
```

---

## 17. TECNOLOGIAS E DEPENDÊNCIAS

### 17.1 Backend
- Laravel 11.x
- PHP 8.1+
- PostgreSQL 12+
- Eloquent ORM

### 17.2 Frontend
- Blade Templates
- Alpine.js (reatividade leve)
- Fetch API (AJAX)
- Chart.js (gráficos)
- Bootstrap/TailwindCSS

### 17.3 Bibliotecas
- phpoffice/phpspreadsheet - Leitura/escrita Excel
- smalot/pdfparser - Parsing de PDFs
- simplesoftwareio/simple-qrcode - QR Code
- guzzle/guzzle - HTTP client
- mailgun/mailgun-php - Envio de e-mails

---

## 18. PONTOS CRÍTICOS E CUIDADOS

### 18.1 OrcamentoController (8.259 linhas)

⚠️ **GIGANTE** - Refatoração necessária em futuro

Métodos para refatorar em Services:
- Importação de documentos → DocumentImporterService
- Cálculos estatísticos → EstatisticaService (já existe)
- Geração de PDFs → PDFService
- Gerenciamento de CDFs → CDFService

### 18.2 Configuração Dinâmica de BD

⚠️ **CRÍTICO** - ProxyAuth modifica config em runtime

Garanta que:
1. Headers do proxy estejam sempre presentes
2. Session seja salvada após autenticação
3. Reconexão do BD seja feita corretamente

### 18.3 Multitenant

⚠️ **CRÍTICO** - Isolamento deve ser mantido

Cuidado:
- Sempre usar `tenant_id` nas queries (se adicionado)
- Models usando `pgsql_main` devem ser explícitos
- Não usar dados em cache entre requests de tenants diferentes

### 18.4 CSRF Desabilitado

⚠️ **SEGURANÇA** - CSRF desabilitado para APIs

Compensado por:
- ProxyAuth valida requests via proxy
- Token de CDF valida requisições públicas
- Header X-User-* valida autenticação

---

## 19. ESTATÍSTICAS DO PROJETO

| Métrica | Valor |
|---------|-------|
| Total de Linhas de Código | ~16.8K (controllers) + 30K+ (models/services) |
| Controllers | 14 |
| Models | 30+ |
| Migrations | 54 |
| Views | 30+ |
| Rotas | 80+ |
| Middlewares | 6 |
| Services | 6 |
| Tabelas BD | 30+ |
| Tamanho BD (estimado) | 500MB - 2GB |

---

## 20. PRÓXIMOS PASSOS / RECOMENDAÇÕES

1. **Refatorar OrcamentoController**
   - Dividir em múltiplos controllers menores
   - Movimentar lógica para services

2. **Adicionar Testes Automatizados**
   - Testes de API
   - Testes de modelos
   - Testes de services

3. **Documenta API**
   - OpenAPI/Swagger
   - Documentar endpoints

4. **Implementar Versionamento**
   - API versioning
   - Backward compatibility

5. **Monitoramento**
   - APM (Application Performance Monitoring)
   - Alertas de erros

---

**FIM DA ANÁLISE**

Documento gerado em: 2025-10-22  
Versão: 1.0 COMPLETA
