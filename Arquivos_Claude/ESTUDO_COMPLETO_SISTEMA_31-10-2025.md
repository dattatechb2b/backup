# ESTUDO COMPLETO DO SISTEMA - CESTA DE PREÇOS

**Data do Estudo:** 31/10/2025
**Realizado por:** Claude (Assistente de IA)
**Objetivo:** Estudo completo e detalhado do sistema para fins de memorização

---

## ÍNDICE

1. [Visão Geral do Sistema](#1-visão-geral-do-sistema)
2. [Arquitetura Multitenant](#2-arquitetura-multitenant)
3. [Estrutura de Código](#3-estrutura-de-código)
4. [Integrações com APIs Externas](#4-integrações-com-apis-externas)
5. [Sistema de Rotas](#5-sistema-de-rotas)
6. [Middleware e Segurança](#6-middleware-e-segurança)
7. [Banco de Dados](#7-banco-de-dados)
8. [Funcionalidades Principais](#8-funcionalidades-principais)
9. [Sistema de Documentação](#9-sistema-de-documentação)
10. [Comandos Artisan](#10-comandos-artisan)

---

## 1. VISÃO GERAL DO SISTEMA

### 1.1. Descrição

O **Cesta de Preços** é um módulo Laravel para gestão de orçamentos públicos, integrado ao sistema **MinhaDattaTech** através de uma arquitetura multitenant híbrida.

### 1.2. Características Principais

- **Multitenant**: Cada prefeitura tem banco de dados PostgreSQL independente
- **Modular**: Sistema isolado que se comunica via proxy com o sistema central
- **Integrado**: Conecta-se a múltiplas APIs públicas (PNCP, ComprasGov, CMED, TCE-RS)
- **Completo**: Desde a criação do orçamento até a geração de PDFs finais

### 1.3. Tecnologias

- **Backend**: Laravel (PHP 8.x)
- **Banco de Dados**: PostgreSQL 15+
- **Frontend**: Blade Templates + JavaScript (Vanilla)
- **Cache**: Redis (sessões e cache)
- **Servidor Web**: Caddy (proxy reverso)
- **Autenticação**: Sistema de proxy com headers personalizados

---

## 2. ARQUITETURA MULTITENANT

### 2.1. Conceito Fundamental

Cada prefeitura (tenant) possui:
- Banco de dados PostgreSQL **independente**
- Estrutura de tabelas **idêntica** (prefixo `cp_`)
- Dados **completamente isolados**
- Acesso via subdomínio único (ex: `pirapora.dattapro.online`)

### 2.2. Bancos de Dados

#### Banco Central (Dados Compartilhados)
```
Database: minhadattatech_db
Connection: 'pgsql_main' (FIXA)

Tabelas Compartilhadas:
- tenants (registro de prefeituras)
- users (usuários do sistema central)
- cp_catmat (~300MB - Catálogo de Materiais)
- cp_medicamentos_cmed (~50MB - Preços CMED)
- cp_precos_comprasgov (~100MB - Histórico Compras.gov)
```

#### Bancos dos Tenants (Isolados)
```
Databases:
- pirapora_db
- novaroma_db
- catasaltas_db
- gurupi_db
- novalaranjeiras_db
- dattatech_db

Connection: 'pgsql' (DINÂMICA - configurada por request)

Tabelas (~50 tabelas com prefixo cp_):
- cp_orcamentos
- cp_orcamento_itens
- cp_fornecedores
- cp_fornecedor_itens
- cp_lotes
- cp_solicitacoes_cdf
- ... (todas as tabelas do módulo)
```

### 2.3. Fluxo de Requisição

```
Cliente (Browser)
    ↓
Caddy (Proxy Reverso) :443
    ↓
MinhaDattaTech :8000
    ├── DetectTenant (extrai subdomain)
    ├── TenantAuthMiddleware (valida sessão)
    └── ModuleProxyController (prepara headers)
        ↓ [Headers X-*]
        ↓
Módulo Cesta de Preços :8001
    └── ProxyAuth (configura DB dinâmico)
        ↓
PostgreSQL (banco do tenant específico)
```

### 2.4. Headers de Proxy

O **ModuleProxyController** (sistema central) envia os seguintes headers:

```php
X-Tenant-Id: 3
X-Tenant-Subdomain: pirapora
X-Tenant-Name: Prefeitura de Pirapora
X-DB-Host: 127.0.0.1
X-DB-Name: pirapora_db
X-DB-User: pirapora_user
X-DB-Password: senha_criptografada
X-User-Id: 42
X-User-Email: usuario@pirapora.gov.br
X-User-Name: Nome do Usuário
```

### 2.5. Middleware ProxyAuth

**Arquivo:** `/home/dattapro/modulos/cestadeprecos/app/Http/Middleware/ProxyAuth.php`

**Responsabilidades:**

1. **Rotas Públicas**: Permite acesso sem autenticação para:
   - `/responder-cdf/*` (formulário CDF público)
   - `/storage/*` (arquivos estáticos)
   - `/brasao/*` (brasões das prefeituras)

2. **Validação Cross-Tenant** (CRÍTICO):
   ```php
   if ($currentTenantId != $sessionTenantId) {
       // BLOQUEIO: Cross-tenant access attempt!
       Log::critical('Cross-tenant access attempt BLOCKED!');
       session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);
   }
   ```

3. **Configuração Dinâmica do Banco**:
   ```php
   config(['database.connections.pgsql' => [
       'host' => X-DB-Host,
       'database' => X-DB-Name,
       'username' => X-DB-User,
       'password' => X-DB-Password,
   ]]);
   DB::purge('pgsql');
   DB::reconnect('pgsql');
   ```

4. **Persistência de Sessão**:
   - Salva dados na sessão: `proxy_tenant`, `proxy_user_data`, `proxy_db_config`
   - Evita reconfigurar o banco a cada request

### 2.6. Segurança Cross-Tenant

**Problema:** Usuário autenticado no tenant A tenta acessar dados do tenant B

**Solução Implementada:**
1. Toda requisição valida `X-Tenant-Id` vs. `session('proxy_tenant.id')`
2. Se divergir: BLOQUEIO + Log CRITICAL + Limpeza de sessão
3. Força reautenticação via headers do proxy

**Exemplo de Log:**
```
[CRITICAL] Cross-tenant access attempt BLOCKED!
session_tenant_id: 2 (novaroma)
current_tenant_id: 3 (pirapora)
user_email: usuario@novaroma.gov.br
uri: /orcamentos/123/elaborar
```

---

## 3. ESTRUTURA DE CÓDIGO

### 3.1. Estatísticas

```
Controllers:  18 arquivos (~17.429 linhas)
Models:       37 arquivos (~3.434 linhas)
Views:        34 arquivos .blade.php
Services:     17 arquivos
Commands:     20 arquivos
Migrations:   ~20 migrations (todas com prefixo cp_)
```

### 3.2. Controllers Principais

**Localização:** `/home/dattapro/modulos/cestadeprecos/app/Http/Controllers/`

1. **OrcamentoController.php** (~2.500 linhas)
   - CRUD de orçamentos
   - Elaboração (6 etapas)
   - Geração de PDFs
   - Importação de planilhas
   - Sistema de CDF (Cotação Direta com Fornecedor)
   - Salvamento de preços via AJAX
   - Concluir cotação

2. **FornecedorController.php** (~800 linhas)
   - CRUD de fornecedores
   - Consulta CNPJ (ReceitaWS)
   - Importação de planilha
   - Busca por item/CATMAT

3. **PesquisaRapidaController.php** (~1.200 linhas)
   - **Busca multi-fonte em 7 APIs simultâneas**
   - Priorização: CMED → CATMAT+API → PNCP → TCE-RS → Comprasnet → CGU
   - Remoção de duplicatas
   - Filtro de valores zerados

4. **MapaAtasController.php** (~500 linhas)
   - Busca de ARPs (Atas de Registro de Preço) no PNCP
   - Visualização de itens das atas

5. **CatalogoController.php** (~600 linhas)
   - CRUD de produtos locais
   - Busca no PNCP para referências de preço
   - Histórico de orçamentos realizados

6. **CdfRespostaController.php** (~700 linhas)
   - Listagem de CDFs enviadas
   - Formulário público de resposta (via token)
   - Salvamento de respostas
   - Visualização de respostas

7. **NotificacaoController.php** (~400 linhas)
   - Sistema de notificações
   - Contador de não lidas
   - Marcar como lida

8. **CotacaoExternaController.php** (~500 linhas)
   - Upload de planilhas de cotação externa
   - Preview e concluir

9. **Outros Controllers:**
   - CatmatController.php (autocomplete, sugestões)
   - ConfiguracaoController.php (config do órgão)
   - OrientacaoTecnicaController.php (orientações técnicas)
   - ContratosExternosController.php (contratos TCE-RS/PNCP)
   - CnpjController.php (consulta CNPJ)
   - LogController.php (sistema de logs)
   - OrgaoController.php (CRUD de órgãos)
   - TceRsController.php (integração TCE-RS)
   - AuthController.php (autenticação - herdado do sistema central)

### 3.3. Models

**Localização:** `/home/dattapro/modulos/cestadeprecos/app/Models/`

#### Models Tenant-Specific (usa connection 'pgsql' dinâmica)

**Total: 34 models**

1. **Orçamentos:**
   - Orcamento.php
   - OrcamentoItem.php
   - Lote.php

2. **Fornecedores:**
   - Fornecedor.php
   - FornecedorItem.php

3. **CDF (Cotação Direta com Fornecedor):**
   - SolicitacaoCDF.php
   - SolicitacaoCDFItem.php
   - RespostaCDF.php
   - RespostaCDFItem.php
   - RespostaCDFAnexo.php

4. **Contratos:**
   - ContratoPNCP.php
   - ContratacaoSimilar.php
   - ContratacaoSimilarItem.php
   - ContratoExterno.php
   - ItemContratoExterno.php

5. **E-commerce/Coleta:**
   - ColetaEcommerce.php
   - ColetaEcommerceItem.php

6. **ARP (Ata de Registro de Preço):**
   - ArpCabecalho.php
   - ArpItem.php

7. **Sistema:**
   - User.php
   - Orgao.php
   - Anexo.php
   - Notificacao.php
   - CatalogoProduto.php
   - OrientacaoTecnica.php
   - CotacaoExterna.php

8. **Auditoria:**
   - AuditSnapshot.php
   - AuditLogItem.php
   - HistoricoPreco.php

9. **Logs/Cache:**
   - LogImportacao.php
   - ConsultaPncpCache.php
   - CheckpointImportacao.php

10. **Qualidade de Dados:**
    - CrosswalkFonte.php
    - DataQualityRule.php

#### Models Compartilhados (usa connection 'pgsql_main' fixa)

**Total: 3 models**

1. **Catmat.php**
   ```php
   protected $connection = 'pgsql_main';
   protected $table = 'cp_catmat';
   // ~300MB - Catálogo de Materiais do Governo Federal
   ```

2. **MedicamentoCmed.php**
   ```php
   protected $connection = 'pgsql_main';
   protected $table = 'cp_medicamentos_cmed';
   // ~50MB - Preços de medicamentos (ANVISA/CMED)
   ```

3. **PrecoComprasGov.php**
   ```php
   protected $connection = 'pgsql_main';
   protected $table = 'cp_precos_comprasgov';
   // ~100MB - Histórico de preços do Compras.gov
   ```

### 3.4. Services

**Localização:** `/home/dattapro/modulos/cestadeprecos/app/Services/`

**Total: 17 services**

#### APIs Externas

1. **ComprasnetApiService.php**
   - API Clássica SIASG: `api.compras.dados.gov.br`
   - API Nova: `dadosabertos.compras.gov.br`
   - Métodos:
     - `buscarPrecosPraticados()` - preços min/méd/máx
     - `buscarContratos()` - contratos SIASG
     - `buscarItens()` - itens com preços unitários
   - Cache: 15 minutos
   - Timeout: 30s
   - Retry: 2 tentativas

2. **TceRsApiService.php**
   - API CKAN: `https://dados.tce.rs.gov.br/api/3/action`
   - Métodos:
     - `buscarDatasets()` - busca packages no catálogo
     - `buscarDataStore()` - busca em dados estruturados
   - Cache: 15 minutos
   - Timeout: 30s

3. **LicitaconService.php**
   - URL: `https://dados.tce.rs.gov.br/dados/licitacon/licitacao/ano/`
   - Baixa e processa CSVs (ITEM.csv, LICITACAO.csv)
   - Cache: 24 horas
   - Busca local nos CSVs

#### Processamento de Dados

4. **CurvaABCService.php** - Cálculo de curva ABC

5. **EstatisticaService.php** - Estatísticas de preços

6. **DataNormalizationService.php** - Normalização de dados

7. **CnpjService.php** - Consulta e validação de CNPJ

#### Processamento de PDFs

8. **PDF/FormatoDetector.php** - Detecta formato de PDF
9. **PDF/FormatoExtrator.php** - Extrai dados de PDF
10. **PDF/PDFDetectorManager.php** - Gerencia detecção

11-13. **PDF/Detectores/** (3 detectores):
    - GenericoDetector.php
    - MapaApuracaoDetector.php
    - TabelaHorizontalDetector.php

14-16. **PDF/Extratores/** (3 extratores):
    - GenericoExtrator.php
    - MapaApuracaoExtrator.php
    - TabelaHorizontalExtrator.php

17. **ComprasnetApiNovaService.php** - API nova Comprasnet (alternativa)

---

## 4. INTEGRAÇÕES COM APIs EXTERNAS

### 4.1. Visão Geral

O sistema integra **7 APIs públicas** para coleta de preços de referência:

1. CMED (ANVISA) - **PRIORIDADE 1**
2. CATMAT + Compras.gov API - **PRIORIDADE 2**
3. Banco Local PNCP - **PRIORIDADE 3**
4. API PNCP em Tempo Real - **PRIORIDADE 4**
5. LicitaCon (TCE-RS) - **PRIORIDADE 5**
6. Comprasnet (SIASG) - **PRIORIDADE 6**
7. Portal da Transparência (CGU) - **PRIORIDADE 7**

### 4.2. CMED (Câmara de Regulação do Mercado de Medicamentos)

**Tipo:** Dados locais (importados via Excel)
**Banco:** `pgsql_main` (compartilhado)
**Tabela:** `cp_medicamentos_cmed`

#### Command: ImportarCmed.php

**Localização:** `/home/dattapro/modulos/cestadeprecos/app/Console/Commands/ImportarCmed.php`

**Comando:**
```bash
php artisan cmed:import [arquivo.xlsx] [--mes="Outubro 2025"] [--limpar] [--teste=100]
```

**Características:**
- Importa Excel com **74 colunas** (A-BV)
- Mapeamento completo: substância, CNPJ, laboratório, EAN, preços PF/PMC
- Preços PF (Preço Fábrica) vs. PMC (Preço Máximo ao Consumidor)
- 23 variações de preços por ICMS/estado
- Batch insert: 5.000 registros por vez
- Dados tributários: restrição hospitalar, CAP, CONFAZ

**Estrutura de Preços:**
```
PMC_0  - PMC sem impostos
PMC_12 - PMC com ICMS 12%
PMC_17 - PMC com ICMS 17%
PMC_18 - PMC com ICMS 18%
PMC_20 - PMC com ICMS 20%
... (23 variações)
```

**Busca na Pesquisa Rápida:**
```php
$medicamentos = MedicamentoCmed::buscarPorTermo($termo, 100);
// Retorna: produto, substância, laboratório, PMC_0, PMC_12, etc.
```

### 4.3. CATMAT + Compras.gov API

**Tipo:** Híbrido (CATMAT local + API em tempo real)
**Banco CATMAT:** `pgsql_main` (compartilhado)
**Tabela CATMAT:** `cp_catmat`

#### PASSO 1: Buscar CATMAT Local

```php
$materiais = DB::connection('pgsql_main')
    ->table('cp_catmat')
    ->where('ativo', true)
    ->where(function($q) use ($termo) {
        // Full-text search OU busca por múltiplas palavras
        $q->whereRaw("to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)", [$termo])
          ->orWhere(function($subq) use ($palavras) {
              foreach ($palavras as $palavra) {
                  $subq->where('titulo', 'ILIKE', "%{$palavra}%");
              }
          });
    })
    ->orderBy('contador_ocorrencias', 'desc')
    ->limit(30)
    ->get();
```

#### PASSO 2: Para Cada CATMAT, Buscar Preços na API

**Endpoint:** `https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial`

**Requisição:**
```php
$response = Http::withHeaders([
    'Accept' => '*/*',
    'User-Agent' => 'DattaTech-CestaPrecos/1.0'
])
->timeout(10)
->get($urlPrecos, [
    'codigoItemCatalogo' => $material->codigo, // Código CATMAT
    'pagina' => 1,
    'tamanhoPagina' => 100
]);
```

**Resposta:**
```json
{
  "resultado": [
    {
      "descricaoItem": "ARROZ TIPO 1",
      "precoUnitario": 25.50,
      "quantidade": 1,
      "siglaUnidadeFornecimento": "KG",
      "nomeFornecedor": "Empresa ABC Ltda",
      "niFornecedor": "12345678000190",
      "nomeOrgao": "Prefeitura Municipal de XYZ",
      "codigoOrgao": "123456",
      "ufOrgao": "MG",
      "dataCompra": "2025-09-15",
      "municipioFornecedor": "Belo Horizonte",
      "ufFornecedor": "MG"
    }
  ]
}
```

**Filtros Aplicados:**
- Remove valores zerados: `($preco['precoUnitario'] ?? 0) > 0`
- Limite de 300 resultados total
- Delay entre requisições: 0.2s

#### Command: BaixarPrecosComprasGov.php

**Comando:**
```bash
php artisan comprasgov:baixar-precos [--limite-gb=3]
```

**Características:**
- Sincroniza preços dos **últimos 12 meses**
- Limita tamanho total a 3GB (padrão)
- Processa top 10.000 códigos CATMAT mais usados
- Batch insert: 100 registros por vez
- Delay: 0.05s entre requisições
- Cria índices: catmat, data, UF, full-text search

**Tabela:** `cp_precos_comprasgov` (pgsql_main - compartilhada)

### 4.4. PNCP (Portal Nacional de Contratações Públicas)

**Tipo:** Híbrido (banco local + API em tempo real)

#### 4.4.1. Banco Local PNCP

**Tabela:** `cp_contratos_pncp` (tenant-specific!)

**Sincronização:** Command `SincronizarPNCP.php`

**Comando:**
```bash
php artisan pncp:sincronizar [--meses=6] [--paginas=50]
```

**API:** `https://pncp.gov.br/api/consulta/v1/contratos`

**Características:**
- Sincroniza contratos dos últimos 6 meses (padrão)
- Paginação: 50 páginas (padrão)
- Cria/atualiza fornecedores automaticamente
- Calcula valor unitário estimado: `valor_global / numero_parcelas`
- Extrai UF do órgão
- Confiabilidade: 'baixa' (valor global) ou 'media' (com parcelas)

**Estrutura de Dados:**
```php
[
    'numero_controle_pncp' => '2025-1234567890',
    'tipo' => 'contrato',
    'objeto_contrato' => 'Aquisição de...',
    'valor_global' => 100000.00,
    'numero_parcelas' => 12,
    'valor_unitario_estimado' => 8333.33, // calculado
    'fornecedor_cnpj' => '12345678000190',
    'fornecedor_nome' => 'Empresa ABC Ltda',
    'orgao_razao_social' => 'Prefeitura de...',
    'orgao_uf' => 'MG',
    'fornecedor_id' => 42, // FK para cp_fornecedores
    'created_at' => now()
]
```

#### 4.4.2. API PNCP em Tempo Real

**API Search:** `https://pncp.gov.br/api/search/`

**Parâmetros:**
```php
[
    'q' => $termo,                      // Termo de busca
    'tipos_documento' => 'contrato',    // ou 'edital', 'ata_registro_preco'
    'pagina' => 1,
    'tamanhoPagina' => 10
]
```

**Tipos de Documento:**
- `contrato` - Contratos assinados
- `edital` - Licitações/Contratações publicadas
- `ata_registro_preco` - Atas de registro de preço

**Uso no PesquisaRapidaController:**
```php
private function pncpSearch(string $termo, string $tipoDocumento = 'contrato', int $pagina = 1)
{
    $url = 'https://pncp.gov.br/api/search/';
    $resp = Http::withHeaders(['Accept' => 'application/json'])
        ->connectTimeout(5)
        ->timeout(15)
        ->get($url, [
            'q' => $termo,
            'tipos_documento' => $tipoDocumento,
            'pagina' => $pagina,
            'tamanhoPagina' => 10
        ]);

    return $resp->successful() ? $resp->json() : null;
}
```

### 4.5. TCE-RS (Tribunal de Contas do Estado do Rio Grande do Sul)

**Tipo:** Híbrido (API CKAN + CSVs locais)

#### 4.5.1. API CKAN

**Service:** `TceRsApiService.php`

**Base URL:** `https://dados.tce.rs.gov.br/api/3/action`

**Endpoints:**
1. `/package_search` - Buscar datasets
2. `/datastore_search` - Buscar em DataStore

**Características:**
- Cache: 15 minutos
- Timeout: 30s
- Retry: 2 tentativas

**Exemplo de Uso:**
```php
$tceRsApi = new TceRsApiService();

// Buscar datasets
$resultado = $tceRsApi->buscarDatasets('material de escritório', 20);

// Buscar em DataStore (quando resource tem datastore_active=true)
$resultado = $tceRsApi->buscarDataStore(
    $resourceId,
    $termo,
    ['campo' => 'valor'], // filtros
    10, // limite
    0   // offset
);
```

#### 4.5.2. LicitaCon (CSVs)

**Service:** `LicitaconService.php`

**URL Base:** `https://dados.tce.rs.gov.br/dados/licitacon/licitacao/ano/`

**Fluxo:**
1. Baixa ZIP do ano: `https://dados.tce.rs.gov.br/dados/licitacon/licitacao/ano/2025.csv.zip`
2. Extrai: `ITEM.csv`, `LICITACAO.csv`
3. Parseia CSVs e busca por termo
4. Enriquece itens com dados das licitações
5. Cache: 24 horas

**Exemplo de Uso:**
```php
$licitaconService = new LicitaconService();
$itens = $licitaconService->buscar('notebook', 50);
```

### 4.6. Comprasnet (SIASG)

**Service:** `ComprasnetApiService.php`

**API Clássica:** `https://api.compras.dados.gov.br`

**Endpoint:** `/contratos/v1/contratos.json`

**Características:**
- Cache: 15 minutos
- Timeout: 30s
- Retry: 2 tentativas
- Paginação via `offset`

**Exemplo de Uso:**
```php
$comprasnetApi = new ComprasnetApiService();
$resultado = $comprasnetApi->buscarContratos(
    ['descricao' => 'notebook'], // filtros
    1,  // página
    50  // limite
);
```

### 4.7. Portal da Transparência (CGU)

**Tipo:** API em tempo real (com chave de API)

**Implementação:** `PesquisaRapidaController::buscarNoPortalTransparencia()`

**Nota:** Requer chave de API da CGU

---

## 5. SISTEMA DE ROTAS

**Arquivo:** `/home/dattapro/modulos/cestadeprecos/routes/web.php`

**Total:** ~857 linhas

### 5.1. Rotas Públicas (Sem Autenticação)

```php
// Login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

// Health Check (para proxy)
Route::get('/health', function() {
    return response()->json(['status' => 'ok']);
});

// Preview de Orçamento (público)
Route::get('/orcamentos/{id}/preview', [OrcamentoController::class, 'preview'])->name('orcamentos.preview');
Route::get('/orcamentos/{id}/pdf', [OrcamentoController::class, 'gerarPDF'])->name('orcamentos.pdf');

// Busca PNCP (público para modal de cotação funcionar)
Route::get('/pncp/buscar', [OrcamentoController::class, 'buscarPNCP'])->name('pncp.buscar');

// Busca Compras.gov (público)
Route::get('/compras-gov/buscar', function(Request $request) {
    // Busca CATMAT local + API de preços
})->name('compras-gov.buscar.public');

// Busca Multi-Fonte (público)
Route::get('/pesquisa/buscar', [PesquisaRapidaController::class, 'buscar'])->name('pesquisa.buscar.public');

// Consulta CNPJ (público)
Route::post('/api/cnpj/consultar', [CnpjController::class, 'consultar'])->name('cnpj.consultar');

// CDF - Resposta de Fornecedores (público via token)
Route::get('/responder-cdf/{token}', [CdfRespostaController::class, 'exibirFormulario'])->name('cdf.responder');
Route::post('/api/cdf/responder', [CdfRespostaController::class, 'salvarResposta'])->name('api.cdf.salvarResposta');
Route::get('/api/cdf/consultar-cnpj/{cnpj}', [CdfRespostaController::class, 'consultarCnpj'])->name('api.cdf.consultarCnpj');
```

### 5.2. Rotas Protegidas (Com Autenticação)

#### 5.2.1. Dashboard

```php
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
```

#### 5.2.2. Configurações do Órgão

```php
Route::get('/configuracoes', [ConfiguracaoController::class, 'index']);
Route::post('/configuracoes', [ConfiguracaoController::class, 'update']);
Route::post('/configuracoes/buscar-cnpj', [ConfiguracaoController::class, 'buscarCNPJ']);
Route::post('/configuracoes/upload-brasao', [ConfiguracaoController::class, 'uploadBrasao']);
Route::delete('/configuracoes/deletar-brasao', [ConfiguracaoController::class, 'deletarBrasao']);
```

#### 5.2.3. Pesquisa Rápida

```php
Route::get('/pesquisa-rapida', function() {
    return view('pesquisa-rapida');
})->name('pesquisa.rapida');

Route::post('/pesquisa-rapida/criar-orcamento', [PesquisaRapidaController::class, 'criarOrcamento']);
```

#### 5.2.4. CDFs Enviadas

```php
Route::get('/cdfs-enviadas', [CdfRespostaController::class, 'listarCdfs']);
```

#### 5.2.5. Mapa de Atas

```php
Route::get('/mapa-de-atas', function() {
    return view('mapa-de-atas');
})->name('mapa.atas');

Route::get('/mapa-de-atas/buscar', [MapaAtasController::class, 'buscar']);
```

#### 5.2.6. Mapa de Fornecedores

```php
Route::get('/mapa-de-fornecedores', function() {
    return view('mapa-de-fornecedores');
})->name('mapa.fornecedores');
```

#### 5.2.7. Catálogo de Produtos

```php
Route::get('/catalogo', function() {
    return view('catalogo');
})->name('catalogo');

Route::get('/catalogo/produtos-locais', [CatalogoController::class, 'produtosLocais']);
Route::get('/catalogo/buscar-pncp', [CatalogoController::class, 'buscarPNCP']);
```

#### 5.2.8. Fornecedores (CRUD Completo)

```php
Route::prefix('fornecedores')->name('fornecedores.')->group(function() {
    Route::get('/', [FornecedorController::class, 'index'])->name('index');
    Route::post('/', [FornecedorController::class, 'store'])->name('store');
    Route::get('/consultar-cnpj/{cnpj}', [FornecedorController::class, 'consultarCNPJ']);
    Route::get('/modelo-planilha', [FornecedorController::class, 'downloadModelo']);
    Route::post('/importar', [FornecedorController::class, 'importarPlanilha']);
    Route::get('/buscar-por-item', [FornecedorController::class, 'buscarPorItem']);
    Route::get('/listar-local', [FornecedorController::class, 'listarLocal']);
    Route::get('/buscar-por-codigo', [FornecedorController::class, 'buscarPorCodigo']);
    Route::get('/{id}', [FornecedorController::class, 'show'])->name('show');
    Route::put('/{id}', [FornecedorController::class, 'update'])->name('update');
    Route::delete('/{id}', [FornecedorController::class, 'destroy'])->name('destroy');
});
```

#### 5.2.9. CMED (Medicamentos)

```php
Route::prefix('cmed')->name('cmed.')->group(function() {
    Route::get('/buscar', function(Request $request) {
        $medicamentos = MedicamentoCmed::buscarPorTermo($termo, 100);
        // Retorna medicamentos com preços PMC
    })->name('buscar');
});
```

#### 5.2.10. Cotação Externa

```php
Route::prefix('cotacao-externa')->name('cotacao-externa.')->group(function() {
    Route::get('/', [CotacaoExternaController::class, 'index']);
    Route::post('/upload', [CotacaoExternaController::class, 'upload']);
    Route::post('/atualizar-dados/{id}', [CotacaoExternaController::class, 'atualizarDados']);
    Route::post('/salvar-orcamentista/{id}', [CotacaoExternaController::class, 'salvarOrcamentista']);
    Route::get('/preview/{id}', [CotacaoExternaController::class, 'preview']);
    Route::post('/concluir/{id}', [CotacaoExternaController::class, 'concluir']);
});
```

#### 5.2.11. Orçamentos (CRUD Completo + Elaboração)

```php
Route::prefix('orcamentos')->name('orcamentos.')->group(function() {
    // Criação
    Route::get('/novo', [OrcamentoController::class, 'create'])->name('create');
    Route::post('/novo', [OrcamentoController::class, 'store'])->name('store');

    // Importação de documento
    Route::post('/processar-documento', [OrcamentoController::class, 'importarDocumento']);

    // Listagens
    Route::get('/pendentes', [OrcamentoController::class, 'pendentes']);
    Route::get('/realizados', [OrcamentoController::class, 'realizados']);

    // Elaboração (CRÍTICO!)
    Route::get('/{id}/elaborar', [OrcamentoController::class, 'elaborar'])->name('elaborar');

    // Geração de arquivos
    Route::get('/{id}/imprimir', [OrcamentoController::class, 'imprimir']);
    Route::get('/{id}/exportar-excel', [OrcamentoController::class, 'exportarExcel']);

    // Gerenciar itens
    Route::post('/{id}/itens', [OrcamentoController::class, 'storeItem']);
    Route::patch('/{id}/itens/{item_id}', [OrcamentoController::class, 'updateItem']);
    Route::patch('/{id}/itens/{item_id}/fornecedor', [OrcamentoController::class, 'updateItemFornecedor']);
    Route::post('/{id}/itens/{item_id}/criticas', [OrcamentoController::class, 'updateItemCriticas']);
    Route::delete('/{id}/itens/{item_id}', [OrcamentoController::class, 'destroyItem']);
    Route::patch('/{id}/itens/{item_id}/renumerar', [OrcamentoController::class, 'renumerarItem']);
    Route::post('/{id}/itens/{item_id}/salvar-amostras', [OrcamentoController::class, 'salvarAmostras']);

    // FASE 2: Estatísticas e Saneamento
    Route::post('/{id}/itens/{item_id}/aplicar-saneamento', [OrcamentoController::class, 'aplicarSaneamento']);
    Route::post('/{id}/itens/{item_id}/fixar-snapshot', [OrcamentoController::class, 'fixarSnapshot']);
    Route::post('/{id}/calcular-e-salvar-curva-abc', [OrcamentoController::class, 'calcularESalvarCurvaABC']);

    // Buscar dados do item
    Route::get('/{id}/itens/{item_id}/amostras', [OrcamentoController::class, 'obterAmostras']);
    Route::get('/{id}/itens/{item_id}/justificativas', [OrcamentoController::class, 'buscarJustificativasItem']);
    Route::get('/{id}/itens/{item_id}/audit-logs', [OrcamentoController::class, 'getAuditLogs']);
    Route::get('/{id}/itens/{item_id}/snapshot', [OrcamentoController::class, 'getSnapshot']);

    // Lotes
    Route::post('/{id}/lotes', [OrcamentoController::class, 'storeLote']);

    // Importar planilha
    Route::post('/{id}/importar-planilha', [OrcamentoController::class, 'importPlanilha']);

    // Coleta E-commerce
    Route::post('/{id}/coleta-ecommerce', [OrcamentoController::class, 'storeColetaEcommerce']);

    // CDF
    Route::post('/{id}/solicitar-cdf', [OrcamentoController::class, 'storeSolicitarCDF']);
    Route::get('/{id}/cdf/{cdf_id}', [OrcamentoController::class, 'getCDF']);
    Route::delete('/{id}/cdf/{cdf_id}', [OrcamentoController::class, 'destroyCDF']);
    Route::post('/{id}/cdf/{cdf_id}/primeiro-passo', [OrcamentoController::class, 'primeiroPassoCDF']);
    Route::post('/{id}/cdf/{cdf_id}/segundo-passo', [OrcamentoController::class, 'segundoPassoCDF']);
    Route::get('/{id}/cdf/{cdf_id}/baixar-oficio', [OrcamentoController::class, 'baixarOficioCDF']);
    Route::get('/{id}/cdf/{cdf_id}/baixar-formulario', [OrcamentoController::class, 'baixarFormularioCDF']);
    Route::get('/{id}/cdf/{cdf_id}/baixar-cnpj', [OrcamentoController::class, 'baixarEspelhoCNPJ']);
    Route::get('/{id}/cdf/{cdf_id}/baixar-comprovante', [OrcamentoController::class, 'baixarComprovanteCDF']);
    Route::get('/{id}/cdf/{cdf_id}/baixar-cotacao', [OrcamentoController::class, 'baixarCotacaoCDF']);

    // Contratações similares
    Route::post('/{id}/contratacoes-similares', [OrcamentoController::class, 'storeContratacoesSimilares']);

    // Salvar preço via AJAX (modal de cotação)
    Route::post('/{id}/salvar-preco-item', [OrcamentoController::class, 'salvarPrecoItem']);

    // Salvar orcamentista (Seção 6)
    Route::post('/{id}/salvar-orcamentista', [OrcamentoController::class, 'salvarOrcamentista']);

    // Consultar CNPJ
    Route::get('/consultar-cnpj/{cnpj}', [OrcamentoController::class, 'consultarCNPJ']);

    // Metodologias (Seção 2)
    Route::patch('/{id}/metodologias', [OrcamentoController::class, 'updateMetodologias']);

    // Concluir cotação
    Route::post('/{id}/concluir', [OrcamentoController::class, 'concluir']);

    // Visualizar, editar, excluir
    Route::get('/{id}', [OrcamentoController::class, 'show'])->name('show');
    Route::get('/{id}/editar', [OrcamentoController::class, 'edit'])->name('edit');
    Route::put('/{id}', [OrcamentoController::class, 'update'])->name('update');

    // Ações
    Route::post('/{id}/marcar-realizado', [OrcamentoController::class, 'marcarRealizado']);
    Route::post('/{id}/marcar-pendente', [OrcamentoController::class, 'marcarPendente']);
    Route::delete('/{id}', [OrcamentoController::class, 'destroy'])->name('destroy');
});
```

### 5.3. APIs

#### 5.3.1. Status

```php
Route::get('/api/status', function() {
    return response()->json([
        'message' => 'API do módulo Cesta de Preços',
        'status' => 'ready',
        'tenant' => request()->attributes->get('tenant')['subdomain'] ?? 'unknown'
    ]);
});
```

#### 5.3.2. CATMAT

```php
Route::prefix('api/catmat')->name('api.catmat.')->group(function() {
    Route::get('/suggest', [CatmatController::class, 'suggest']);
    Route::get('/{codigo}', [CatmatController::class, 'show']);
    Route::get('/', [CatmatController::class, 'index']);
    Route::post('/auto-registro', [CatmatController::class, 'autoRegistro']);
});
```

#### 5.3.3. Mapa de Atas

```php
Route::prefix('api/mapa-atas')->name('api.mapa-atas.')->group(function() {
    Route::get('/buscar-arps', [MapaAtasController::class, 'buscarArps']);
    Route::get('/itens/{ataId}', [MapaAtasController::class, 'itensDaAta']);
});
```

#### 5.3.4. Catálogo

```php
Route::prefix('api/catalogo')->name('api.catalogo.')->group(function() {
    Route::get('/', [CatalogoController::class, 'index']);
    Route::post('/', [CatalogoController::class, 'store']);
    Route::get('/buscar-pncp', [CatalogoController::class, 'buscarPNCP']);
    Route::get('/produtos-locais', [CatalogoController::class, 'produtosLocais']);
    Route::get('/orcamentos-realizados', [CatalogoController::class, 'orcamentosRealizados']);
    Route::get('/{id}', [CatalogoController::class, 'show']);
    Route::put('/{id}', [CatalogoController::class, 'update']);
    Route::delete('/{id}', [CatalogoController::class, 'destroy']);
    Route::get('/{id}/referencias-preco', [CatalogoController::class, 'referenciasPreco']);
    Route::post('/{id}/adicionar-preco', [CatalogoController::class, 'adicionarPreco']);
});
```

#### 5.3.5. Fornecedores

```php
Route::prefix('api/fornecedores')->name('api.fornecedores.')->group(function() {
    Route::get('/sugerir', [FornecedorController::class, 'sugerir']);
    Route::post('/atualizar-pncp', [FornecedorController::class, 'atualizarPNCP']);
    Route::get('/buscar-pncp', [FornecedorController::class, 'buscarPNCP']);
    Route::get('/buscar-por-produto', [FornecedorController::class, 'buscarPorProduto']);
    Route::get('/buscar-progressivo', [FornecedorController::class, 'buscarPorProdutoProgressivo']);
});
```

#### 5.3.6. CDF (Interno)

```php
Route::prefix('api/cdf')->name('api.cdf.')->group(function() {
    Route::get('/resposta/{id}', [CdfRespostaController::class, 'visualizarResposta']);
    Route::delete('/{id}', [CdfRespostaController::class, 'apagarCDF']);
});
```

#### 5.3.7. Órgãos

```php
Route::prefix('api/orgaos')->name('orgaos.')->group(function() {
    Route::get('/', [OrgaoController::class, 'index']);
    Route::post('/', [OrgaoController::class, 'store']);
    Route::get('/{id}', [OrgaoController::class, 'show']);
});
```

#### 5.3.8. Notificações (PÚBLICO)

```php
Route::prefix('api/notificacoes')->name('api.notificacoes.')->group(function() {
    Route::get('/contador', [NotificacaoController::class, 'contador']);
    Route::get('/', [NotificacaoController::class, 'index']);
    Route::put('/{id}/marcar-lida', [NotificacaoController::class, 'marcarLida']);
    Route::put('/marcar-todas-lidas', [NotificacaoController::class, 'marcarTodasLidas']);
});
```

#### 5.3.9. Contratos Externos (TCE-RS/PNCP)

```php
Route::get('/api/contratos-externos/buscar', [ContratosExternosController::class, 'buscarPorDescricao']);
Route::get('/api/contratos-externos/catmat/{catmat}', [ContratosExternosController::class, 'buscarPorCatmat']);
Route::get('/api/contratos-externos/estatisticas', [ContratosExternosController::class, 'estatisticas']);
Route::get('/api/contratos-externos', [ContratosExternosController::class, 'listarContratos']);
Route::get('/api/contratos-externos/{id}', [ContratosExternosController::class, 'detalhes']);
```

### 5.4. Sistema de Logs

```php
// Receber logs do navegador
Route::post('/api/logs/browser', [LogController::class, 'storeBrowserLog']);

// Visualizar logs (protegido)
Route::middleware(['auth'])->group(function() {
    Route::get('/logs', [LogController::class, 'index']);
    Route::get('/logs/download', [LogController::class, 'download']);
    Route::post('/logs/clean', [LogController::class, 'cleanOldLogs']);
});
```

### 5.5. Orientações Técnicas

```php
Route::get('/orientacoes-tecnicas', [OrientacaoTecnicaController::class, 'index']);
Route::get('/orientacoes-tecnicas/buscar', [OrientacaoTecnicaController::class, 'buscar']);
```

### 5.6. Arquivos Estáticos

```php
// CSS
Route::get('/css/{filename}', function($filename) {
    return response()->file(public_path('css/' . $filename), ['Content-Type' => 'text/css']);
});

// JavaScript
Route::get('/js/{filename}', function($filename) {
    return response()->file(public_path('js/' . $filename), ['Content-Type' => 'application/javascript']);
});

// Imagens
Route::get('/images/{filename}', function($filename) {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/' . $extension
    };
    return response()->file(public_path('images/' . $filename), ['Content-Type' => $mimeType]);
});

// Fontes
Route::get('/fonts/{filename}', function($filename) {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        default => 'application/octet-stream'
    };
    return response()->file(public_path('fonts/' . $filename), ['Content-Type' => $mimeType]);
});

// Assets compilados
Route::get('/build/{filename}', function($filename) {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'js' => 'application/javascript',
        'css' => 'text/css',
        'map' => 'application/json',
        default => 'application/octet-stream'
    };
    return response()->file(public_path('build/' . $filename), ['Content-Type' => $mimeType]);
});
```

---

## 6. MIDDLEWARE E SEGURANÇA

### 6.1. ProxyAuth (CRÍTICO!)

**Arquivo:** `/home/dattapro/modulos/cestadeprecos/app/Http/Middleware/ProxyAuth.php`

**Registrado globalmente em:** `bootstrap/app.php`

#### 6.1.1. Responsabilidades

1. **Autenticação via Proxy**
2. **Configuração Dinâmica do Banco**
3. **Validação Cross-Tenant** (SEGURANÇA CRÍTICA)
4. **Persistência de Sessão**
5. **Rotas Públicas (whitelist)**

#### 6.1.2. Rotas Públicas (Sem Autenticação)

```php
$publicRoutes = [
    '/responder-cdf',           // Formulário CDF público
    '/api/cdf/responder',       // Salvar resposta CDF
    '/api/cdf/consultar-cnpj',  // Consulta CNPJ para CDF
    '/storage/',                // Arquivos estáticos (brasões, PDFs)
    '/brasao/'                  // Brasões das prefeituras
];
```

#### 6.1.3. Validação Cross-Tenant (Linhas 80-102)

**Problema:** Usuário logado no tenant A tenta acessar dados do tenant B

**Implementação:**
```php
// Tenant da sessão
$sessionTenantId = session('proxy_tenant.id');

// Tenant da requisição atual
$currentTenantId = $request->header('X-Tenant-Id');

// VALIDAÇÃO
if ($currentTenantId && $sessionTenantId && $currentTenantId != $sessionTenantId) {
    // 🚨 BLOQUEIO ATIVADO!
    Log::critical('Cross-tenant access attempt BLOCKED!', [
        'session_tenant_id' => $sessionTenantId,
        'session_tenant_subdomain' => $tenantData['subdomain'],
        'session_tenant_db' => $dbConfig['database'],
        'current_tenant_id' => $currentTenantId,
        'current_tenant_subdomain' => $request->header('X-Tenant-Subdomain'),
        'current_tenant_db' => $request->header('X-DB-Name'),
        'user_email' => $userData['email'],
        'uri' => $request->getRequestUri()
    ]);

    // Limpar sessão do módulo (forçar reautenticação)
    session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);

    // Continuar para reautenticar via headers
}
```

**Log de Exemplo:**
```
[2025-10-31 14:35:22] production.CRITICAL: Cross-tenant access attempt BLOCKED!
{
    "session_tenant_id": 2,
    "session_tenant_subdomain": "novaroma",
    "session_tenant_db": "novaroma_db",
    "current_tenant_id": 3,
    "current_tenant_subdomain": "pirapora",
    "current_tenant_db": "pirapora_db",
    "user_email": "joao@novaroma.gov.br",
    "uri": "/orcamentos/123/elaborar"
}
```

#### 6.1.4. Configuração Dinâmica do Banco (Linhas 106-141)

**Passo 1:** Ler headers do proxy

```php
$dbConfig = [
    'database' => $request->header('X-DB-Name'),        // pirapora_db
    'host' => $request->header('X-DB-Host', '127.0.0.1'),
    'username' => $request->header('X-DB-User'),        // pirapora_user
    'password' => $request->header('X-DB-Password'),    // senha
];
```

**Passo 2:** Salvar na sessão

```php
session([
    'proxy_tenant' => [
        'id' => $tenantId,
        'subdomain' => $tenantSubdomain,
        'name' => $tenantName
    ],
    'proxy_user_data' => [
        'id' => $userId,
        'email' => $userEmail,
        'name' => $userName
    ],
    'proxy_db_config' => $dbConfig
]);
```

**Passo 3:** Configurar conexão 'pgsql' (DINÂMICA)

```php
private function configureDynamicDatabaseConnection(Request $request): void
{
    $dbConfig = [
        'driver' => 'pgsql',
        'host' => $request->header('X-DB-Host', '127.0.0.1'),
        'database' => $request->header('X-DB-Name'),    // ← DINÂMICO!
        'username' => $request->header('X-DB-User'),    // ← DINÂMICO!
        'password' => $request->header('X-DB-Password'), // ← DINÂMICO!
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
    ];

    // Substituir configuração da conexão 'pgsql'
    config(['database.connections.pgsql' => $dbConfig]);

    // Limpar e reconectar
    DB::purge('pgsql');
    DB::reconnect('pgsql');
}
```

#### 6.1.5. Persistência de Sessão

**Benefício:** Evita reconfigurar o banco a cada request

**Fluxo:**

```
Request 1 (com headers X-*):
    → Configura banco
    → Salva na sessão
    → Continua

Request 2 (sem headers X-*):
    → Lê da sessão
    → Valida tenant
    → Restaura banco da sessão
    → Continua

Request 3 (polling notificações):
    → Lê da sessão
    → Valida tenant
    → Restaura banco da sessão
    → Continua
```

### 6.2. Outros Middlewares

**Registrados em:** `bootstrap/app.php`

1. **EnsureAuthenticated** - Valida autenticação Laravel
2. **DynamicSessionDomain** (sistema central) - Isola cookies por domínio
3. **DetectTenant** (sistema central) - Detecta tenant por subdomínio
4. **TenantAuthMiddleware** (sistema central) - Valida autenticação do tenant

---

## 7. BANCO DE DADOS

### 7.1. Configuração

**Arquivo:** `/home/dattapro/modulos/cestadeprecos/config/database.php`

```php
'connections' => [
    // Conexão DINÂMICA (tenant-specific)
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'laravel'),  // Substituído dinamicamente!
        'username' => env('DB_USERNAME', 'root'),     // Substituído dinamicamente!
        'password' => env('DB_PASSWORD', ''),         // Substituído dinamicamente!
        'charset' => 'utf8',
        'prefix' => '',  // Vazio! Tabelas já têm cp_ explícito
        'schema' => 'public',
    ],

    // Conexão FIXA (dados compartilhados)
    'pgsql_main' => [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'database' => 'minhadattatech_db',  // FIXO!
        'username' => 'minhadattatech_user',
        'password' => 'MinhaDataTech2024SecureDB',
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
    ],
]
```

### 7.2. Migrations

**Localização:** `/home/dattapro/modulos/cestadeprecos/database/migrations/`

**Total:** ~20 migrations

**REGRA OBRIGATÓRIA:** Todas as tabelas devem ter prefixo `cp_`

#### 7.2.1. Migrations Tenant-Specific (Connection Padrão)

**Exemplos:**

1. **2025_09_30_143011_create_orcamentos_table.php**
   ```php
   Schema::create('cp_orcamentos', function(Blueprint $table) {
       $table->id();
       $table->string('numero')->unique();
       $table->string('titulo');
       $table->text('objeto')->nullable();
       $table->enum('status', ['pendente', 'em_elaboracao', 'realizado'])->default('pendente');
       $table->decimal('valor_total', 15, 2)->nullable();
       $table->timestamps();
       $table->softDeletes();
   });
   ```

2. **2025_09_30_143012_create_orcamento_itens_table.php**
   ```php
   Schema::create('cp_orcamento_itens', function(Blueprint $table) {
       $table->id();
       $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');
       $table->integer('numero_item');
       $table->text('descricao');
       $table->string('unidade_medida', 50);
       $table->decimal('quantidade', 15, 3);
       $table->decimal('preco_unitario', 15, 2)->nullable();
       $table->timestamps();
   });
   ```

3. **2025_09_30_143013_create_fornecedores_table.php**
   ```php
   Schema::create('cp_fornecedores', function(Blueprint $table) {
       $table->id();
       $table->string('numero_documento')->unique(); // CNPJ/CPF
       $table->enum('tipo_pessoa', ['fisica', 'juridica']);
       $table->string('razao_social');
       $table->string('situacao_cadastral')->nullable();
       $table->boolean('ativo')->default(true);
       $table->timestamps();
   });
   ```

4. **2025_10_01_create_solicitacoes_cdf_table.php**
   ```php
   Schema::create('cp_solicitacoes_cdf', function(Blueprint $table) {
       $table->id();
       $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');
       $table->string('token_publico', 64)->unique();
       $table->enum('status', ['pendente', 'respondida', 'cancelada'])->default('pendente');
       $table->timestamps();
   });
   ```

**Lista Completa de Tabelas Tenant-Specific (prefixo cp_):**

1. cp_orcamentos
2. cp_orcamento_itens
3. cp_lotes
4. cp_fornecedores
5. cp_fornecedor_itens
6. cp_solicitacoes_cdf
7. cp_solicitacoes_cdf_itens
8. cp_respostas_cdf
9. cp_respostas_cdf_itens
10. cp_respostas_cdf_anexos
11. cp_contratos_pncp
12. cp_contratacoes_similares
13. cp_contratacoes_similares_itens
14. cp_coletas_ecommerce
15. cp_coletas_ecommerce_itens
16. cp_contratos_externos
17. cp_itens_contratos_externos
18. cp_arp_cabecalho
19. cp_arp_itens
20. cp_anexos
21. cp_audit_snapshots
22. cp_audit_logs_items
23. cp_historico_precos
24. cp_logs_importacao
25. cp_notificacoes
26. cp_catalogos_produtos
27. cp_orientacoes_tecnicas
28. cp_cotacoes_externas
29. cp_checkpoints_importacao
30. cp_consultas_pncp_cache
31. cp_crosswalk_fontes
32. cp_data_quality_rules

#### 7.2.2. Migrations Compartilhadas (Connection 'pgsql_main')

**Exemplos:**

1. **2025_10_29_114457_create_cp_catmat_main_table.php**
   ```php
   Schema::connection('pgsql_main')->create('cp_catmat', function(Blueprint $table) {
       $table->id();
       $table->string('codigo', 20)->unique();
       $table->text('titulo');
       $table->boolean('ativo')->default(true);
       $table->integer('contador_ocorrencias')->default(0);
       $table->boolean('tem_preco_comprasgov')->nullable();
       $table->timestamps();
   });

   // Índices
   Schema::connection('pgsql_main')->table('cp_catmat', function(Blueprint $table) {
       $table->index('codigo');
       $table->index('ativo');
       $table->index('contador_ocorrencias');
       // Full-text search
       DB::connection('pgsql_main')->statement(
           "CREATE INDEX cp_catmat_titulo_fulltext ON cp_catmat USING gin(to_tsvector('portuguese', titulo))"
       );
   });
   ```

2. **2025_10_29_create_cp_medicamentos_cmed_table.php**
   ```php
   Schema::connection('pgsql_main')->create('cp_medicamentos_cmed', function(Blueprint $table) {
       $table->id();
       $table->string('substancia')->nullable();
       $table->string('cnpj_laboratorio', 14)->nullable();
       $table->string('laboratorio')->nullable();
       $table->string('codigo_ggrem')->nullable();
       $table->string('registro')->nullable();
       $table->string('ean1')->nullable();
       $table->string('produto');
       $table->string('apresentacao')->nullable();
       $table->string('classe_terapeutica')->nullable();
       $table->string('tipo_produto')->nullable();

       // Preços PMC (23 variações por ICMS/estado)
       $table->decimal('pmc_0', 10, 2)->nullable();
       $table->decimal('pmc_12', 10, 2)->nullable();
       $table->decimal('pmc_17', 10, 2)->nullable();
       // ... (mais 20 colunas de preços)

       $table->string('mes_referencia')->nullable();
       $table->date('data_importacao')->nullable();

       $table->timestamps();
   });

   // Índices
   Schema::connection('pgsql_main')->table('cp_medicamentos_cmed', function(Blueprint $table) {
       $table->index('laboratorio');
       $table->index('tipo_produto');
       // Full-text search
       DB::connection('pgsql_main')->statement(
           "CREATE INDEX cp_medicamentos_cmed_produto_fulltext ON cp_medicamentos_cmed USING gin(to_tsvector('portuguese', produto || ' ' || COALESCE(substancia, '')))"
       );
   });
   ```

3. **2025_10_29_113814_create_cp_precos_comprasgov_table.php**
   ```php
   Schema::connection('pgsql_main')->create('cp_precos_comprasgov', function(Blueprint $table) {
       $table->id();
       $table->string('catmat_codigo', 20);
       $table->text('descricao_item');
       $table->decimal('preco_unitario', 15, 2);
       $table->decimal('quantidade', 15, 3)->default(1);
       $table->string('unidade_fornecimento', 50)->nullable();
       $table->string('fornecedor_nome')->nullable();
       $table->string('fornecedor_cnpj', 14)->nullable();
       $table->string('orgao_nome')->nullable();
       $table->string('orgao_codigo', 50)->nullable();
       $table->string('orgao_uf', 2)->nullable();
       $table->string('municipio', 100)->nullable();
       $table->string('uf', 2)->nullable();
       $table->date('data_compra')->nullable();
       $table->timestamp('sincronizado_em');
       $table->timestamps();
   });

   // Índices
   Schema::connection('pgsql_main')->table('cp_precos_comprasgov', function(Blueprint $table) {
       $table->index('catmat_codigo');
       $table->index('data_compra');
       $table->index('uf');
       // Full-text search
       DB::connection('pgsql_main')->statement(
           "CREATE INDEX cp_precos_comprasgov_desc_fulltext ON cp_precos_comprasgov USING gin(to_tsvector('portuguese', descricao_item))"
       );
   });
   ```

### 7.3. Índices Importantes

#### 7.3.1. Full-Text Search (PostgreSQL)

**CATMAT:**
```sql
CREATE INDEX cp_catmat_titulo_fulltext
ON cp_catmat
USING gin(to_tsvector('portuguese', titulo));
```

**Uso:**
```php
$query = DB::connection('pgsql_main')
    ->table('cp_catmat')
    ->whereRaw("to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)", [$termo]);
```

**CMED:**
```sql
CREATE INDEX cp_medicamentos_cmed_produto_fulltext
ON cp_medicamentos_cmed
USING gin(to_tsvector('portuguese', produto || ' ' || COALESCE(substancia, '')));
```

**Compras.gov:**
```sql
CREATE INDEX cp_precos_comprasgov_desc_fulltext
ON cp_precos_comprasgov
USING gin(to_tsvector('portuguese', descricao_item));
```

#### 7.3.2. Índices de Performance

**Orçamentos:**
```sql
CREATE INDEX idx_cp_orcamentos_status ON cp_orcamentos(status);
CREATE INDEX idx_cp_orcamentos_created_at ON cp_orcamentos(created_at DESC);
CREATE INDEX idx_cp_orcamentos_numero ON cp_orcamentos(numero);
```

**Fornecedores:**
```sql
CREATE INDEX idx_cp_fornecedores_cnpj ON cp_fornecedores(numero_documento);
CREATE INDEX idx_cp_fornecedores_ativo ON cp_fornecedores(ativo);
```

**Itens de Orçamento:**
```sql
CREATE INDEX idx_cp_orcamento_itens_orcamento_id ON cp_orcamento_itens(orcamento_id);
CREATE INDEX idx_cp_orcamento_itens_descricao ON cp_orcamento_itens(descricao);
```

### 7.4. Comandos de Manutenção

#### Vacuum e Analyze

```bash
# Para cada banco de tenant
sudo -u postgres psql -d pirapora_db -c "VACUUM ANALYZE;"

# Para banco central
sudo -u postgres psql -d minhadattatech_db -c "VACUUM ANALYZE;"
```

#### Verificar Tamanho

```bash
# Tamanho do banco
sudo -u postgres psql -d pirapora_db -c "SELECT pg_size_pretty(pg_database_size('pirapora_db'));"

# Tamanho de uma tabela
sudo -u postgres psql -d pirapora_db -c "SELECT pg_size_pretty(pg_total_relation_size('cp_orcamentos'));"
```

#### Monitorar Queries Lentas

```sql
-- Habilitar log de queries > 1s
ALTER DATABASE pirapora_db SET log_min_duration_statement = 1000;

-- Ver logs
tail -f /var/log/postgresql/postgresql-*.log | grep "duration:"
```

---

## 8. FUNCIONALIDADES PRINCIPAIS

### 8.1. Pesquisa Rápida (Busca Multi-Fonte)

**Localização:** `app/Http/Controllers/PesquisaRapidaController.php`

**Rota:** `/pesquisa/buscar?termo=TERMO`

**View:** `resources/views/pesquisa-rapida.blade.php`

#### 8.1.1. Fluxo de Busca

**Prioridades:**

1. **CMED** (medicamentos ANVISA) - Prioridade máxima
2. **CATMAT + API Compras.gov** - Preços em tempo real
3. **Banco Local PNCP** - Contratos sincronizados
4. **API PNCP** - Contratos em tempo real
5. **LicitaCon (TCE-RS)** - API CKAN + CSVs
6. **Comprasnet (SIASG)** - API clássica
7. **Portal da Transparência (CGU)** - Requer chave API

#### 8.1.2. Método Principal

```php
public function buscar(Request $request)
{
    $termo = trim($request->get('termo', ''));

    if (strlen($termo) < 3) {
        return response()->json([
            'success' => false,
            'message' => 'Digite pelo menos 3 caracteres'
        ]);
    }

    $resultados = [];
    $fontes = [];

    try {
        // 1. CMED
        $resultadosCMED = $this->buscarNoCMED($termo);
        if (!empty($resultadosCMED)) {
            $resultados = array_merge($resultados, $resultadosCMED);
            $fontes['CMED'] = count($resultadosCMED);
        }

        // 2. CATMAT + API Preços
        $resultadosComprasGov = $this->buscarNoCATMATComPrecos($termo);
        if (!empty($resultadosComprasGov)) {
            $resultados = array_merge($resultados, $resultadosComprasGov);
            $fontes['COMPRAS_GOV'] = count($resultadosComprasGov);
        }

        // 3. Banco Local PNCP
        $resultadosLocal = $this->buscarNoBancoLocal($termo);
        if (!empty($resultadosLocal)) {
            $resultados = array_merge($resultados, $resultadosLocal);
            $fontes['LOCAL'] = count($resultadosLocal);
        }

        // 4. API PNCP
        $resultadosContratos = $this->buscarContratosPNCP($termo);
        if (!empty($resultadosContratos)) {
            $resultados = array_merge($resultados, $resultadosContratos);
            $fontes['PNCP_CONTRATOS'] = count($resultadosContratos);
        }

        // 5. LicitaCon (TCE-RS)
        $resultadosLicitaCon = $this->buscarNoLicitaCon($termo);
        if (!empty($resultadosLicitaCon)) {
            $resultados = array_merge($resultados, $resultadosLicitaCon);
            $fontes['LICITACON'] = count($resultadosLicitaCon);
        }

        // 6. Comprasnet (SIASG)
        $resultadosComprasnet = $this->buscarNoComprasnet($termo);
        if (!empty($resultadosComprasnet)) {
            $resultados = array_merge($resultados, $resultadosComprasnet);
            $fontes['COMPRASNET'] = count($resultadosComprasnet);
        }

        // 7. Portal da Transparência (CGU)
        $resultadosPortalCGU = $this->buscarNoPortalTransparencia($termo);
        if (!empty($resultadosPortalCGU)) {
            $resultados = array_merge($resultados, $resultadosPortalCGU);
            $fontes['PORTAL_TRANSPARENCIA'] = count($resultadosPortalCGU);
        }

        // Filtrar valores zerados
        $resultados = array_filter($resultados, function($item) {
            $valor = $item['valor_homologado_item'] ?? $item['valor_unitario'] ?? $item['valor_global'] ?? 0;
            return $valor > 0;
        });
        $resultados = array_values($resultados);

        // Remover duplicatas
        $resultados = $this->removerDuplicatas($resultados);

        return response()->json([
            'success' => true,
            'message' => 'Busca concluída',
            'resultados' => $resultados,
            'total' => count($resultados),
            'termo' => $termo,
            'fontes' => $fontes
        ]);

    } catch (\Exception $e) {
        Log::error('PesquisaRapida: ERRO GERAL', [
            'erro' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erro na busca: ' . $e->getMessage()
        ], 500);
    }
}
```

#### 8.1.3. Tratamento de Erros

- **Try-catch individual** por fonte: se uma falhar, as outras continuam
- **Logging detalhado** de cada etapa
- **Resposta sempre retorna** mesmo com erros parciais
- **Fontes bem-sucedidas** são retornadas na resposta

#### 8.1.4. Formato de Resposta

```json
{
  "success": true,
  "message": "Busca concluída",
  "resultados": [
    {
      "id": "cmed_123",
      "descricao": "DIPIRONA SÓDICA 500MG - GENÉRICO",
      "laboratorio": "EMS S/A",
      "valor_unitario": 12.50,
      "unidade_medida": "UN",
      "quantidade": 1,
      "fonte": "CMED",
      "orgao": "ANVISA/CMED - Brasília/DF",
      "data": "15/10/2025",
      "municipio": "Brasília",
      "uf": "DF",
      "marca": "EMS"
    },
    {
      "id": "comprasgov_456",
      "descricao": "ARROZ TIPO 1",
      "laboratorio": "Fornecedor ABC Ltda",
      "valor_unitario": 25.50,
      "unidade_medida": "KG",
      "quantidade": 1,
      "fonte": "COMPRAS.GOV",
      "orgao": "Prefeitura Municipal de XYZ",
      "data": "20/09/2025",
      "municipio": "Belo Horizonte",
      "uf": "MG",
      "cnpj": "12345678000190"
    }
  ],
  "total": 2,
  "termo": "dipirona",
  "fontes": {
    "CMED": 1,
    "COMPRAS_GOV": 1
  }
}
```

### 8.2. Elaboração de Orçamento (6 Etapas)

**Localização:** `app/Http/Controllers/OrcamentoController.php`

**Rota:** `/orcamentos/{id}/elaborar`

**View:** `resources/views/orcamentos/elaborar.blade.php`

#### 8.2.1. Etapas do Orçamento

**1. Dados Básicos**
   - Número, título, objeto
   - Importação de documento (PDF/Excel)
   - Criação manual de itens

**2. Metodologias**
   - Definir metodologias de pesquisa de preços
   - Pesquisa Rápida multi-fonte
   - Modal de Cotação

**3. Fornecedores**
   - CRUD de fornecedores locais
   - Importação de planilha
   - Consulta CNPJ (ReceitaWS)
   - CDF (Cotação Direta com Fornecedor)

**4. Análise Crítica**
   - Justificativas agregadas por item
   - Observações técnicas
   - Curva ABC

**5. Orientações Técnicas**
   - Anexar orientações técnicas
   - Upload de documentos

**6. Orcamentista**
   - Nome, CPF, matrícula
   - Upload de brasão
   - Dados do órgão

**7. Concluir**
   - Gerar PDF final
   - Exportar Excel
   - Marcar como realizado

#### 8.2.2. Modal de Cotação

**Localização:** `resources/views/orcamentos/elaborar.blade.php` (linhas ~1000-2000)

**Funcionalidades:**
- Busca em tempo real (PNCP, CMED, Compras.gov)
- Filtros: fonte, unidade, UF, município
- Visualização de amostras coletadas
- Salvamento de preços via AJAX
- QR Code para rastreabilidade

**Endpoints:**
```
GET  /pncp/buscar?termo=TERMO
GET  /cmed/buscar?termo=TERMO
GET  /compras-gov/buscar?termo=TERMO
POST /orcamentos/{id}/salvar-preco-item
```

### 8.3. CDF (Cotação Direta com Fornecedor)

**Fluxo Completo:**

1. **Criação da CDF** (usuário interno)
   ```
   POST /orcamentos/{id}/solicitar-cdf
   ```
   - Seleciona itens
   - Sistema gera token único
   - Cria registros em cp_solicitacoes_cdf

2. **Primeiro Passo** (usuário interno)
   ```
   POST /orcamentos/{id}/cdf/{cdf_id}/primeiro-passo
   ```
   - Cadastra fornecedor
   - Define itens e quantidades
   - Gera ofício (PDF)

3. **Segundo Passo** (usuário interno)
   ```
   POST /orcamentos/{id}/cdf/{cdf_id}/segundo-passo
   ```
   - Envia e-mail para fornecedor
   - E-mail contém link com token: `/responder-cdf/{token}`

4. **Resposta do Fornecedor** (público)
   ```
   GET  /responder-cdf/{token}
   POST /api/cdf/responder
   ```
   - Formulário público (sem login)
   - Fornecedor preenche preços
   - Pode anexar documentos
   - Gera notificação para usuário interno

5. **Visualização da Resposta** (usuário interno)
   ```
   GET /api/cdf/resposta/{id}
   ```
   - Visualiza dados respondidos
   - Pode importar preços para o orçamento

6. **Downloads**
   ```
   GET /orcamentos/{id}/cdf/{cdf_id}/baixar-oficio
   GET /orcamentos/{id}/cdf/{cdf_id}/baixar-formulario
   GET /orcamentos/{id}/cdf/{cdf_id}/baixar-cnpj
   GET /orcamentos/{id}/cdf/{cdf_id}/baixar-comprovante
   GET /orcamentos/{id}/cdf/{cdf_id}/baixar-cotacao
   ```

### 8.4. Importação de Planilhas

**Endpoint:** `POST /orcamentos/{id}/importar-planilha`

**Formato Aceito:**
- Excel (.xlsx, .xls)
- CSV

**Detecção Automática de Colunas:**
- Descrição/Item/Produto
- Quantidade
- Unidade/Unid/UN
- Preço Unitário/Valor/VL Unit

**Processamento:**
1. Lê arquivo Excel/CSV
2. Detecta cabeçalho (pula linhas vazias)
3. Mapeia colunas automaticamente
4. Valida dados
5. Cria itens do orçamento em batch

**Exemplo de Código:**
```php
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load($arquivo);
$worksheet = $spreadsheet->getActiveSheet();

// Detectar cabeçalho
$headerRow = $this->detectarCabecalho($worksheet);

// Mapear colunas
$colunas = $this->mapearColunas($worksheet->rangeToArray($headerRow));

// Processar linhas
for ($linha = $headerRow + 1; $linha <= $highestRow; $linha++) {
    $descricao = $worksheet->getCellByColumnAndRow($colunas['descricao'], $linha)->getValue();
    $quantidade = $worksheet->getCellByColumnAndRow($colunas['quantidade'], $linha)->getValue();
    $unidade = $worksheet->getCellByColumnAndRow($colunas['unidade'], $linha)->getValue();
    $preco = $worksheet->getCellByColumnAndRow($colunas['preco'], $linha)->getValue();

    // Criar item
    OrcamentoItem::create([
        'orcamento_id' => $orcamento->id,
        'descricao' => $descricao,
        'quantidade' => $quantidade,
        'unidade_medida' => $unidade,
        'preco_unitario' => $preco
    ]);
}
```

### 8.5. Geração de PDFs

**Controller:** `app/Http/Controllers/OrcamentoController.php`

**Método:** `gerarPDF()`

**Biblioteca:** TCPDF (via composer)

**Endpoint:** `/orcamentos/{id}/pdf`

**Estrutura do PDF:**

1. **Cabeçalho**
   - Brasão do órgão (se houver)
   - Nome do órgão
   - Título do orçamento
   - Número e data

2. **Metodologia**
   - Metodologias utilizadas
   - Fontes de pesquisa

3. **Itens do Orçamento**
   - Tabela com:
     - Item, Descrição, Unidade, Quantidade
     - Preço Unitário, Preço Total
     - Fornecedor (se definido)

4. **Análise Crítica** (se houver)
   - Justificativas por item
   - Observações técnicas

5. **Curva ABC** (se calculada)
   - Classificação A/B/C
   - Percentuais acumulados

6. **Orientações Técnicas** (se houver)
   - Links ou anexos

7. **Rodapé**
   - Nome e matrícula do orcamentista
   - Data de elaboração

**QR Codes:**
- QR Code para rastreabilidade de amostras
- Link para amostras coletadas

**Exemplo de Código:**
```php
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('Cesta de Preços');
$pdf->SetAuthor($orgao->razao_social);
$pdf->SetTitle('Orçamento ' . $orcamento->numero);
$pdf->SetSubject('Orçamento de Preços');

// Adicionar página
$pdf->AddPage();

// Cabeçalho
if ($brasao) {
    $pdf->Image($brasao, 15, 15, 30, 0, 'PNG');
}
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $orgao->razao_social, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, $orcamento->titulo, 0, 1, 'C');

// Tabela de itens
$html = '<table border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Item</th>
            <th>Descrição</th>
            <th>Unidade</th>
            <th>Quantidade</th>
            <th>Preço Unit.</th>
            <th>Preço Total</th>
        </tr>
    </thead>
    <tbody>';

foreach ($orcamento->itens as $item) {
    $html .= '<tr>
        <td>' . $item->numero_item . '</td>
        <td>' . $item->descricao . '</td>
        <td>' . $item->unidade_medida . '</td>
        <td>' . number_format($item->quantidade, 2, ',', '.') . '</td>
        <td>R$ ' . number_format($item->preco_unitario, 2, ',', '.') . '</td>
        <td>R$ ' . number_format($item->preco_unitario * $item->quantidade, 2, ',', '.') . '</td>
    </tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Rodapé
$pdf->SetY(-30);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Orcamentista: ' . $orcamentista->nome . ' - Mat. ' . $orcamentista->matricula, 0, 1, 'L');
$pdf->Cell(0, 10, 'Data: ' . now()->format('d/m/Y H:i'), 0, 1, 'L');

// Salvar
$pdf->Output(storage_path('app/public/orcamentos/' . $orcamento->numero . '.pdf'), 'F');
```

### 8.6. Sistema de Notificações

**Controller:** `app/Http/Controllers/NotificacaoController.php`

**Model:** `app/Models/Notificacao.php`

**Tabela:** `cp_notificacoes` (tenant-specific!)

**Tipos de Notificação:**
- CDF Respondida
- Orçamento Concluído
- Fornecedor Cadastrado
- Alerta do Sistema

**Endpoints:**
```
GET  /api/notificacoes/contador          # Contador de não lidas
GET  /api/notificacoes                   # Listar todas
PUT  /api/notificacoes/{id}/marcar-lida  # Marcar individual
PUT  /api/notificacoes/marcar-todas-lidas # Marcar todas
```

**Estrutura da Notificação:**
```php
[
    'id' => 123,
    'tipo' => 'cdf_respondida',
    'titulo' => 'CDF Respondida',
    'mensagem' => 'O fornecedor ABC Ltda respondeu a CDF #45',
    'data' => '2025-10-31 14:30:00',
    'lida' => false,
    'link' => '/api/cdf/resposta/45'
]
```

**Polling (Frontend):**
```javascript
setInterval(function() {
    fetch('/api/notificacoes/contador')
        .then(res => res.json())
        .then(data => {
            document.querySelector('.badge-notificacoes').textContent = data.count;
        });
}, 30000); // A cada 30 segundos
```

---

## 9. SISTEMA DE DOCUMENTAÇÃO

### 9.1. Pasta Arquivos_Claude

**Localização:** `/home/dattapro/modulos/cestadeprecos/Arquivos_Claude/`

**Descrição:** Repositório central de toda a documentação do sistema

**Estrutura:**

```
Arquivos_Claude/
├── FUNDAMENTAIS/
│   ├── ⚠️_INSTRUCOES_PRIORITARIAS.md
│   ├── CONTEXTO_PROJETO.md
│   ├── CODIGO_CRITICO_NAO_MEXER.md
│   ├── STATUS_GERAL_PROJETO.md
│   ├── GAPS_INTEGRACAO.md
│   └── APIS_IMPLEMENTADAS.md
│
├── IMPLEMENTACOES_ATIVAS/
│   └── (documentação de features em desenvolvimento)
│
├── STATUS_ATUAL/
│   └── (status de cada funcionalidade)
│
├── INDEX_MULTITENANT.md
├── GUIA_PRATICO_MULTITENANT.md
├── ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md
├── DIAGRAMA_MULTITENANT_VISUAL.md
├── ESTUDO_COMPLETO_MODULO_CESTA_PRECOS.md
├── RESUMO_COMPLETO_DIA_30-10-2025.md
├── RESUMO_TECNICO_ESTATISTICAS.json
└── README.md
```

### 9.2. Documentos Principais

#### 9.2.1. FUNDAMENTAIS/⚠️_INSTRUCOES_PRIORITARIAS.md

**Conteúdo:**
- Instruções prioritárias para desenvolvimento
- Código crítico que NÃO PODE ser modificado
- Regras de prefixo de tabelas (cp_ obrigatório)
- Validações de segurança cross-tenant

#### 9.2.2. ESTUDO_ARQUITETURA_MULTITENANT_COMPLETO.md

**Conteúdo:**
- Conceitos fundamentais de multitenant
- Estrutura completa de bancos de dados
- Fluxo de identificação de tenants
- Sistema de proxy e comunicação
- Segurança e validações cross-tenant
- Dados compartilhados (CATMAT, CMED)
- Migrations e prefixo de tabelas
- Diagramas de arquitetura
- Comandos de debugging

**Tamanho:** ~1.135 linhas

#### 9.2.3. GUIA_PRATICO_MULTITENANT.md

**Conteúdo:**
- Como adicionar novo tenant (passo a passo)
- Como migrar tenant para outro servidor
- Troubleshooting de problemas comuns
- Scripts de monitoramento
- Backup e restore automatizado
- Performance e otimização
- Segurança (rotação de senhas, auditoria)
- Comandos rápidos

**Tamanho:** ~600 linhas

#### 9.2.4. APIS_IMPLEMENTADAS.md

**Conteúdo:**
- Lista completa de APIs integradas
- Endpoints utilizados
- Parâmetros de cada API
- Exemplos de requisições e respostas
- Taxa de sucesso
- Troubleshooting por API

### 9.3. Regras de Documentação

**DO:**
- Documentar TODAS as mudanças em Arquivos_Claude/
- Criar arquivo de resumo ao final de cada sessão
- Atualizar STATUS_ATUAL/ com progresso
- Incluir exemplos de código

**DON'T:**
- NÃO modificar arquivos da pasta FUNDAMENTAIS/ sem autorização
- NÃO deletar documentação histórica
- NÃO fazer alterações sem consultar CODIGO_CRITICO_NAO_MEXER.md

---

## 10. COMANDOS ARTISAN

**Localização:** `/home/dattapro/modulos/cestadeprecos/app/Console/Commands/`

**Total:** 20 comandos

### 10.1. Sincronização de Dados Compartilhados

#### 10.1.1. ImportarCatmat

```bash
php artisan catmat:import [arquivo.zip] [--limpar] [--teste=100]
```

**Descrição:** Importa catálogo CATMAT (Governo Federal)

**Características:**
- Importa arquivo ZIP (formato oficial)
- Batch insert: 1.000 registros por vez
- Cria índice full-text search
- Atualiza contador de ocorrências

#### 10.1.2. ImportarCmed

```bash
php artisan cmed:import [arquivo.xlsx] [--mes="Outubro 2025"] [--limpar] [--teste=100]
```

**Descrição:** Importa medicamentos da Tabela CMED (ANVISA)

**Características:**
- Excel com 74 colunas
- Batch insert: 5.000 registros
- 23 variações de preços (PF/PMC por ICMS)
- Full-text search em produto + substância

#### 10.1.3. BaixarPrecosComprasGov

```bash
php artisan comprasgov:baixar-precos [--limite-gb=3]
```

**Descrição:** Baixa preços do Compras.gov (últimos 12 meses)

**Características:**
- Limita tamanho a 3GB (padrão)
- Top 10k códigos CATMAT mais usados
- Batch insert: 100 registros
- Cria índices: catmat, data, UF, full-text

### 10.2. Sincronização de Dados Tenant-Specific

#### 10.2.1. SincronizarPNCP

```bash
php artisan pncp:sincronizar [--meses=6] [--paginas=50]
```

**Descrição:** Sincroniza contratos do PNCP para banco local

**Características:**
- Últimos 6 meses (padrão)
- 50 páginas (padrão)
- Cria/atualiza fornecedores automaticamente
- Calcula valor unitário estimado

#### 10.2.2. SincronizarPNCPCompleto

```bash
php artisan pncp:sincronizar-completo
```

**Descrição:** Versão estendida do SincronizarPNCP

**Características:**
- Busca até 12 meses
- Sem limite de páginas
- Mais lento, mais completo

#### 10.2.3. BaixarContratosPNCP

```bash
php artisan pncp:baixar-contratos
```

**Descrição:** Baixa detalhes de contratos específicos

#### 10.2.4. PopularFornecedoresPNCP

```bash
php artisan pncp:popular-fornecedores
```

**Descrição:** Extrai fornecedores dos contratos PNCP

**Características:**
- Cria registros em cp_fornecedores
- Evita duplicatas (CNPJ único)

#### 10.2.5. AtualizarFornecedoresContratos

```bash
php artisan pncp:atualizar-fornecedores-contratos
```

**Descrição:** Atualiza FKs fornecedor_id nos contratos

### 10.3. Importação de Dados Externos

#### 10.3.1. ImportarTceRs

```bash
php artisan tcers:importar [--ano=2025]
```

**Descrição:** Importa contratos do TCE-RS (LicitaCon)

#### 10.3.2. ImportarLicitaconCompleto

```bash
php artisan licitacon:importar-completo [--anos=2023,2024,2025]
```

**Descrição:** Importa múltiplos anos do LicitaCon

#### 10.3.3. LicitaconSincronizar

```bash
php artisan licitacon:sincronizar
```

**Descrição:** Sincronização incremental do LicitaCon

#### 10.3.4. ImportarOrientacoesTecnicas

```bash
php artisan orientacoes:importar [arquivo.xlsx]
```

**Descrição:** Importa orientações técnicas de planilha

### 10.4. Monitoramento e Manutenção

#### 10.4.1. CheckDatabaseSetup

```bash
php artisan db:check-setup
```

**Descrição:** Verifica configuração dos bancos de dados

**Testa:**
- Conexão 'pgsql' (dinâmica)
- Conexão 'pgsql_main' (compartilhada)
- Existência de tabelas críticas
- Índices full-text

#### 10.4.2. MonitorarAPIComprasGov

```bash
php artisan comprasgov:monitorar
```

**Descrição:** Monitora disponibilidade da API Compras.gov

**Testa:**
- Tempo de resposta
- Taxa de sucesso
- Erros comuns

### 10.5. Workers (Processamento Paralelo)

#### 10.5.1. ComprasGovWorker

```bash
php artisan comprasgov:worker
```

**Descrição:** Worker para processar filas de download

#### 10.5.2. ComprasGovScout

```bash
php artisan comprasgov:scout
```

**Descrição:** Scout para descobrir novos códigos CATMAT

#### 10.5.3. ComprasGovScoutWorker

```bash
php artisan comprasgov:scout-worker
```

**Descrição:** Worker do scout

#### 10.5.4. ComprasGovBaixarFocado

```bash
php artisan comprasgov:baixar-focado [--codigo=12345]
```

**Descrição:** Baixa preços de código CATMAT específico

#### 10.5.5. BaixarPrecosComprasGovParalelo

```bash
php artisan comprasgov:baixar-paralelo [--workers=5]
```

**Descrição:** Download paralelo com múltiplos workers

**Características:**
- 5 workers simultâneos (padrão)
- Distribuição de carga
- Retry automático

---

## CONCLUSÃO

Este documento apresentou um estudo completo e detalhado do sistema **Cesta de Preços**, abrangendo:

1. **Arquitetura Multitenant**: Entendimento profundo do isolamento de dados por prefeitura
2. **Estrutura de Código**: Controllers, Models, Services, Commands
3. **Integrações com APIs**: 7 APIs públicas integradas (CMED, Compras.gov, PNCP, TCE-RS, etc)
4. **Sistema de Rotas**: ~857 linhas de rotas públicas e protegidas
5. **Middleware de Segurança**: ProxyAuth com validação cross-tenant
6. **Banco de Dados**: PostgreSQL com 50+ tabelas (prefixo cp_)
7. **Funcionalidades**: Pesquisa multi-fonte, CDF, importação de planilhas, geração de PDFs
8. **Documentação**: Sistema organizado em Arquivos_Claude/
9. **Comandos Artisan**: 20 comandos para sincronização e manutenção

**Total de Arquivos Estudados:**
- 18 Controllers (~17.429 linhas)
- 37 Models (~3.434 linhas)
- 34 Views Blade
- 17 Services
- 20 Commands
- ~20 Migrations
- 1 Middleware crítico (ProxyAuth)
- 857 linhas de rotas

**Documentação Consultada:**
- 10+ documentos em Arquivos_Claude/
- Guias práticos de multitenant
- Estudos de arquitetura
- Diagramas visuais

---

**Data de Conclusão:** 31/10/2025
**Status:** ✅ ESTUDO COMPLETO REALIZADO

---

Este documento será mantido em:
`/home/dattapro/modulos/cestadeprecos/Arquivos_Claude/ESTUDO_COMPLETO_SISTEMA_31-10-2025.md`
