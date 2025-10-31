# ESTUDO COMPLETO E DETALHADO - MÓDULO CESTA DE PREÇOS

**Data:** 30 de outubro de 2025  
**Versão:** 1.0  
**Status:** ATIVO E EM DESENVOLVIMENTO  

---

## ÍNDICE

1. [Visão Geral do Módulo](#visão-geral)
2. [Arquitetura e Estrutura](#arquitetura)
3. [Controllers - Análise Detalhada](#controllers)
4. [Models - Relacionamentos e Estrutura](#models)
5. [Database - Tabelas e Migrations](#database)
6. [Views e Frontend](#views)
7. [APIs Integradas](#apis)
8. [Commands Artisan](#commands)
9. [JavaScript e Funcionalidades](#javascript)
10. [Rotas Web](#rotas)
11. [Services Especializados](#services)
12. [Estatísticas do Projeto](#estatísticas)

---

## 1. VISÃO GERAL DO MÓDULO <a name="visão-geral"></a>

### O que é Cesta de Preços?

Um **módulo Laravel para elaboração de orçamentos públicos** que integra múltiplas fontes de dados de preços:

- **PNCP** (Portal Nacional de Contratações Públicas)
- **Compras.gov** (Base de preços praticados em contratos federais)
- **CMED** (Tabela de medicamentos da ANVISA)
- **Catmat** (Catálogo de Materiais)
- **TCE-RS** (Tribunal de Contas do Estado do Rio Grande do Sul)
- **Comprasnet** (Sistema de compras SIASG)
- **Portal da Transparência** (CGU)

### Objetivo Principal

Facilitar que órgãos públicos criem orçamentos estimativos com **referências de preços reais** praticados no mercado, baseadas em:

- Contratos anteriores (histórico)
- Licitações abertas/publicadas
- Tabelas oficiais (CMED)
- Dados de e-commerce
- Cotações de fornecedores

### Usuários Finais

- **Orcamentistas** (elaboram orçamentos)
- **Analistas** (revisam e aprovam)
- **Órgãos públicos** (usam como referência)

---

## 2. ARQUITETURA E ESTRUTURA <a name="arquitetura"></a>

### Estrutura de Diretórios

```
/home/dattapro/modulos/cestadeprecos/
├── app/
│   ├── Http/
│   │   ├── Controllers/        (17.4 KB de código)
│   │   └── Middleware/
│   ├── Models/                 (3.4 KB - 34 models)
│   ├── Services/               (17 serviços especializados)
│   ├── Console/Commands/       (19 comandos artisan)
│   └── Helpers/
├── database/
│   └── migrations/             (69 migrations com prefixo cp_)
├── resources/
│   └── views/orcamentos/       (13 blade templates)
├── public/
│   ├── js/                     (4 arquivos JavaScript)
│   └── css/
├── routes/
│   └── web.php                 (Rotas principais)
└── config/                     (Configurações)
```

### Padrão de Prefixo de Tabelas

**IMPORTANTE:** Todas as tabelas do módulo devem ter o prefixo `cp_`

Exemplo correto:
- `cp_orcamentos`
- `cp_itens_orcamento`
- `cp_contratos_pncp`
- `cp_fornecedores`

---

## 3. CONTROLLERS - ANÁLISE DETALHADA <a name="controllers"></a>

### 3.1 OrcamentoController (8.133 linhas)

**Maior controller do módulo** - Controla todo o fluxo de orçamentos

#### Principais Métodos:

```php
// Listar orçamentos
public function index()

// Visualizar orçamento completo (Etapas 1-7)
public function elaborar($id)

// Criar novo orçamento
public function create()

// Salvar orçamento
public function store(Request $request)

// Editar orçamento
public function edit($id)

// Atualizar orçamento
public function update(Request $request, $id)

// Gerar PDF
public function gerarPDF($id)

// Preview público
public function preview($id)

// Buscar dados via AJAX
public function buscarPNCP(Request $request)
public function importarPlanilha(Request $request)
```

#### Etapas de um Orçamento:

1. **Etapa 1:** Identificação Geral (nome, objeto, órgão)
2. **Etapa 2:** Metodologia de Análise (tipo de avaliação, parâmetros)
3. **Etapa 3:** Cadastramento de Itens (produtos/serviços)
4. **Etapa 4:** Pesquisa de Preços (modal de cotação)
5. **Etapa 5:** Contratações Similares (análise comparativa)
6. **Etapa 6:** Dados do Orcamentista (identificação do responsável)
7. **Etapa 7:** Análise Crítica (comentários finais)

### 3.2 PesquisaRapidaController (1.518 linhas)

**Busca multi-fonte** em tempo real

#### Principais Métodos:

```php
public function buscar(Request $request)           // API principal
public function criarOrcamento(Request $request)   // Criar a partir de resultados
```

#### Fontes de Busca (Ordem de Prioridade):

1. **CMED** (medicamentos)
2. **CATMAT+API ComprasGov** (preços praticados)
3. **Banco Local PNCP** (contratos armazenados)
4. **API PNCP** (contratos em tempo real)
5. **TCE-RS** (licitações estaduais)
6. **Comprasnet** (contratos federais)
7. **Portal Transparência** (CGU)

#### Resposta:

```json
{
  "success": true,
  "resultados": [
    {
      "numero_controle_pncp": "202500001",
      "tipo": "contrato",
      "objeto_contrato": "Arroz tipo 1 5kg",
      "valor_unitario": 125.50,
      "unidade_medida": "UN",
      "fornecedor_vencedor": "Empresa XYZ",
      "orgao": "Ministério da Educação",
      "data_publicacao": "2025-01-15",
      "origem": "COMPRAS.GOV"
    }
  ],
  "total": 42,
  "fontes": {
    "COMPRAS.GOV": 15,
    "PNCP_CONTRATOS": 20,
    "CMED": 7
  }
}
```

### 3.3 CatalogoController (887 linhas)

**Gerenciamento de catálogo de produtos**

#### Principais Métodos:

```php
public function index(Request $request)            // Listar produtos
public function show($id)                          // Detalhes do produto
public function store(Request $request)            // Criar produto
public function update(Request $request, $id)      // Atualizar produto
public function destroy($id)                       // Desativar produto (soft delete)
public function referenciasPreco($id)              // Buscar ARPs relacionadas
public function adicionarPreco(Request $request, $id)  // Adicionar preço manual
public function buscarPNCP(Request $request)       // Buscar no PNCP
public function orcamentosRealizados(Request $request) // Histórico de orçamentos
public function produtosLocais(Request $request)   // Produtos mais usados
```

#### Busca CATMAT+API:

```
PASSO 1: Buscar no CATMAT local (FTS português)
PASSO 2: Validar com word boundaries (evita false matches)
PASSO 3: Para cada código CATMAT, consultar API de preços
PASSO 4: Retornar preços REAIS praticados (últimos 12 meses)
```

### 3.4 FornecedorController (2.483 linhas)

**Gerenciamento de fornecedores cadastrados**

#### Principais Métodos:

```php
public function index(Request $request)            // Listar/buscar fornecedores
public function store(Request $request)            // Cadastrar fornecedor
public function update(Request $request, $id)      // Atualizar dados
public function destroy($id)                       // Desativar fornecedor
public function importarPlanilha(Request $request) // Importar de Excel
```

#### Importação de Planilha:

- Suporta XLSX/XLS (PhpSpreadsheet)
- Valida CNPJ/CPF
- Busca complementares via ViaCEP
- Detecta duplicatas
- Gera relatório de erros

### 3.5 MapaAtasController (1.020 linhas)

**Busca avançada em Atas de Registro de Preço (ARPs)**

#### Principais Métodos:

```php
public function buscar(Request $request)           // Busca multi-fonte
public function detalhe($id)                       // Detalhes de uma ARP
```

#### Filtros Avançados:

- Descrição (word boundaries)
- UASG (unidade administrativa)
- CNPJ do órgão
- Data (período configurável)
- UF, Município
- Valor (mín/máx)

### 3.6 NotificacaoController (168 linhas)

**Sistema de notificações**

#### Principais Métodos:

```php
public function naoLidas(Request $request)        // GET notificações não lidas
public function marcarComoLida($id)               // POST marcar como lida
public function marcarTodasComoLidas()            // POST marcar todas
```

**IMPORTANTE:** Usa mapeamento por email (não por ID) para sincronização cross-tenant

---

## 4. MODELS - RELACIONAMENTOS E ESTRUTURA <a name="models"></a>

### 4.1 Orcamento (265 linhas)

**Model central do módulo**

```php
class Orcamento extends Model {
    // Relacionamentos
    belongsTo(User::class)
    hasMany(OrcamentoItem::class)
    hasMany(Lote::class)
    hasMany(SolicitacaoCDF::class)
    hasMany(ContratacaoSimilar::class)
    hasMany(ColetaEcommerce::class)
    belongsTo(Orgao::class)
    
    // Scopes
    scopePendentes()
    scopeRealizados()
    scopeTipoCriacao($tipo)
}
```

#### Campos Principais:

```
Identificação:
- numero (formato: 00001/2025)
- nome, objeto, status
- referencia_externa

Etapa 6 - Orcamentista:
- orcamentista_nome, cpf_cnpj, matricula
- orcamentista_razao_social
- orcamentista_endereco, cep, cidade, uf
- orcamentista_setor
- brasao_path

Metodologia (Etapa 2):
- metodologia_analise_critica
- medida_tendencia_central
- prazo_validade_amostras
- numero_minimo_amostras
- aceitar_fontes_alternativas
- usou_similares, usou_cdf, usou_ecommerce

Controle:
- user_id, status, data_conclusao
- orgao_id
- metodo_juizo_critico, metodo_obtencao_preco
```

#### Auto-geração de Número:

```php
// Boot automático: 00001/2025, 00002/2025, ...
protected static function boot() {
    parent::boot();
    static::creating(function($orc) {
        if (empty($orc->numero)) {
            $proximoId = self::withTrashed()->max('id') + 1;
            $orc->numero = str_pad($proximoId, 5, '0', STR_PAD_LEFT) . '/' . date('Y');
        }
    });
}
```

### 4.2 OrcamentoItem (108 linhas)

**Itens dentro de um orçamento**

```php
class OrcamentoItem extends Model {
    belongsTo(Orcamento::class)
    
    // Campos
    descricao           // Produto/serviço
    unidade_medida      // UN, KG, L, etc
    quantidade          // Quantidade solicitada
    preco_unitario      // Preço levantado
    preco_total         // quantidade * preco_unitario
    
    // Rastreabilidade
    numero_item         // Sequência do item (1, 2, 3, ...)
    orcamento_id        // FK para orcamento
    
    // Dados de origem
    origem_dados        // PESQUISA_RAPIDA, MANUAL, IMPORTACAO
    orgao_referencia    // Órgão que praticou preço similar
    uf_referencia
    
    // Amostras coletadas
    amostras_selecionadas   // JSON: [amostra1, amostra2, ...]
    
    // Críticas
    criticas            // JSON: [crítica1, crítica2, ...]
    import_log          // Log de importação
}
```

### 4.3 ContratoPNCP (126 linhas)

**Contratos sincronizados da API PNCP**

```php
class ContratoPNCP extends Model {
    // Busca por termo (ILIKE)
    public static function buscarPorTermo($termo, $mesesAtras, $limite)
    
    // Campos
    numero_controle_pncp    // ID único PNCP
    tipo                    // contrato, ata, edital, etc
    objeto_contrato         // Descrição
    valor_global            // Valor total
    valor_unitario_estimado // Unitário (quando aplica)
    unidade_medida
    
    // Órgão
    orgao_cnpj
    orgao_razao_social
    orgao_uf, orgao_municipio
    
    // Datas
    data_publicacao_pncp    // Publicação no PNCP
    data_vigencia_inicio
    data_vigencia_fim
    sincronizado_em
    
    // Qualidade
    confiabilidade          // alta, media, baixa
    valor_estimado          // boolean
}
```

### 4.4 MedicamentoCmed (180 linhas)

**Tabela CMED da ANVISA (26.046 medicamentos)**

```php
class MedicamentoCmed extends Model {
    // Busca rápida
    public static function buscarPorTermo($termo, $limite)
    
    // Campos principais
    substancia
    cnpj_laboratorio
    laboratorio
    codigo_ggrem
    ean1, ean2, ean3
    produto
    apresentacao
    classe_terapeutica
    regime_preco
    
    // Preços (múltiplas margens)
    pmc_0       // 0% de margem
    pmc_12      // 12% de margem
    pmc_17      // 17% de margem
    pmc_18      // 18% de margem
    pmc_20      // 20% de margem
    
    // Controle
    mes_referencia  // Julho 2025
    data_importacao
}
```

### 4.5 PrecoComprasGov (267 linhas)

**Preços praticados em compras.gov**

```php
class PrecoComprasGov extends Model {
    // Campos principais
    catmat_codigo           // Código do catálogo
    descricao_item
    preco_unitario          // Preço real praticado
    quantidade              // Quantidade comprada
    unidade_fornecimento
    
    // Fornecedor
    fornecedor_nome
    fornecedor_cnpj
    
    // Órgão
    orgao_nome
    orgao_uf
    municipio
    uf
    
    // Data
    data_compra
    
    // Integração com Catmat
    catmat_relacionado      // FK para Catmat (se existir)
}
```

### 4.6 Catmat (98 linhas)

**Catálogo de Materiais (Compras.gov)**

```php
class Catmat extends Model {
    // Campos
    codigo              // Código único (ex: 123456)
    titulo              // Descrição do material
    caminho_hierarquia  // Categoria: "CATEGORIA > SUBCATEGORIA > ..."
    ativo
    
    // Integração com ComprasGov
    tem_preco_comprasgov    // boolean - tem preços na API?
    contador_ocorrencias    // Quantas vezes foi procurado
    
    // FTS (Full-Text Search)
    // Índice: to_tsvector('portuguese', titulo)
}
```

### 4.7 Fornecedor (91 linhas)

```php
class Fornecedor extends Model {
    hasMany(FornecedorItem::class)
    
    // Identificação
    tipo_documento      // CNPJ ou CPF
    numero_documento    // Número limpo (sem formatação)
    razao_social
    nome_fantasia
    
    // Inscrições
    inscricao_estadual
    inscricao_municipal
    
    // Contato
    telefone    // VARCHAR(50) - suporta ramal
    celular
    email
    site
    
    // Endereço
    cep, logradouro, numero, complemento
    bairro, cidade, uf
    
    // Controle
    observacoes
}
```

### 4.8 Outros Models Importantes

```
ArpCabecalho (138)      → Atas de Registro de Preço
ArpItem (111)           → Itens das ARPs
Lote (?)                → Lotes dentro de orçamentos
SolicitacaoCDF (126)    → Solicitações de Cotação do Fornecedor
Notificacao (?)         → Sistema de notificações
User (?)                → Usuários do módulo
Orgao (?)               → Órgãos públicos cadastrados
ContratacaoSimilar (?)  → Contratos similares encontrados
ColetaEcommerce (?)     → Cotações de e-commerce
ContratoExterno (87)    → Contratos não-PNCP
Anexo (106)             → Arquivos anexados
LogImportacao (97)      → Registro de importações
```

---

## 5. DATABASE - TABELAS E MIGRATIONS <a name="database"></a>

### 5.1 Lista de Migrations (69 no total)

**Todas com prefixo `cp_`:**

```
Tabelas Principais (com FK):
✓ cp_orcamentos
✓ cp_itens_orcamento
✓ cp_lotes
✓ cp_fornecedores
✓ cp_fornecedor_itens
✓ cp_contratos_pncp
✓ cp_catmat
✓ cp_precos_comprasgov
✓ cp_medicamentos_cmed
✓ cp_historico_precos
✓ cp_arp_cabecalhos
✓ cp_arp_itens
✓ cp_orgaos
✓ cp_notificacoes
✓ cp_solicitacoes_cdf
✓ cp_respostas_cdf
✓ cp_anexos
✓ cp_contratacoes_similares
✓ cp_contatos_ecommerce
✓ cp_contratos_externos
✓ cp_itens_contrato_externo
✓ cp_audit_log_itens
✓ cp_audit_snapshots
✓ cp_checkpoint_importacao

Cache e Sistema:
✓ cp_users
✓ cp_cache
✓ cp_jobs
✓ consulta_pncp_cache
✓ licitacon_cache
```

### 5.2 Estrutura de Campos Padrão

**Cada tabela possui:**

```sql
id                  → PRIMARY KEY (auto-increment)
tenant_id           → Para multi-tenancy
created_at, updated_at  → Timestamps
deleted_at          → Soft delete (quando aplicável)
```

### 5.3 Migrations Críticas

#### 2025_10_29_113814_create_cp_precos_comprasgov_table.php

```php
// Tabela com FTS (Full-Text Search)
$table->fullText('descricao_item'); // para busca rápida

// Índices para performance
$table->index('catmat_codigo');
$table->index('preco_unitario');
$table->index('data_compra');
```

#### 2025_10_18_213955_add_tenant_id_to_all_tables.php

```php
// Adicionou suporte multi-tenancy
ALTER TABLE cp_orcamentos ADD COLUMN tenant_id BIGINT;
ALTER TABLE cp_fornecedores ADD COLUMN tenant_id BIGINT;
// ... para todas as tabelas principais
```

#### 2025_10_27_150000_increase_telefone_length_all_tables.php

```php
// Aumentou campo telefone para VARCHAR(50)
// Motivo: suportar números com ramal
ALTER TABLE cp_fornecedores MODIFY COLUMN telefone VARCHAR(50);
```

---

## 6. VIEWS E FRONTEND <a name="views"></a>

### 6.1 Estrutura de Views

```
resources/views/orcamentos/
├── create.blade.php              # Novo orçamento (Etapas 1-7)
├── elaborar.blade.php            # Editar orçamento (Etapas 1-7)
├── show.blade.php                # Visualizar (leitura)
├── edit.blade.php                # Editor completo
├── cotar-precos.blade.php        # Modal de cotação (Etapa 4)
├── preview.blade.php             # Preview público
├── pdf.blade.php                 # Template PDF
├── espelho-cnpj.blade.php        # Download CNPJ/CEP
├── pendentes.blade.php           # Lista orçamentos pendentes
├── concluidos.blade.php          # Lista orçamentos concluídos
├── realizados.blade.php          # Lista orçamentos realizados
├── _modal-cotacao.blade.php      # Modal de busca (reutilizável)
└── _modal_cabecalho_orgao.blade.php  # Dados do órgão
```

### 6.2 Tela Principal - create.blade.php

**Layout com Abas (7 Etapas):**

```
┌─────────────────────────────────────────────┐
│ Etapa 1 │ Etapa 2 │ Etapa 3 │ Etapa 4 │ ... │
└─────────────────────────────────────────────┘
│                                             │
│  Conteúdo da Etapa Selecionada              │
│  (Formulário ou tabela)                     │
│                                             │
└─────────────────────────────────────────────┘
```

### 6.3 Modal de Cotação

**File:** `_modal-cotacao.blade.php`

Apresenta:
1. Campo de busca (Pesquisa Rápida multi-fonte)
2. Tabela de resultados filtráveis
3. Seleção de itens
4. Adição ao orçamento

---

## 7. APIS INTEGRADAS <a name="apis"></a>

### 7.1 PNCP (Portal Nacional de Contratações Públicas)

**Endpoint Principal:** `https://pncp.gov.br/api/search/`

```
GET /api/search/?q=TERMO&tipos_documento=TIPO&pagina=1&tamanhoPagina=10

Tipos de Documento:
- contrato: Contratos assinados
- edital: Licitações/Contratações
- ata_registro_preco: ARPs

Response:
{
  "items": [
    {
      "numero_controle_pncp": "202500001",
      "description": "...",
      "valor_global": 10000.00,
      "orgao_nome": "...",
      "uf": "SP"
    }
  ]
}
```

**Cache:** 4 horas (reduz requisições)  
**Rate Limit:** Não informado (usa delays de 1-2 segundos)

### 7.2 Compras.gov

**Dois endpoints:**

#### A) Busca de CATMAT

```
GET /catalogo/1_consultarCatalogo

Retorna códigos de materiais com FTS português
```

#### B) Busca de Preços

```
GET /modulo-pesquisa-preco/1_consultarMaterial?codigoItemCatalogo=123456&pagina=1

Response:
{
  "resultado": [
    {
      "precoUnitario": 125.50,
      "descricaoItem": "Arroz tipo 1",
      "nomeFornecedor": "XYZ Ltda",
      "niFornecedor": "12345678901234",
      "dataCompra": "2025-01-15"
    }
  ]
}
```

**Integração:** Armazenado em `cp_precos_comprasgov`

### 7.3 CMED (Câmara de Regulação de Medicamentos)

**Tipo:** Base local (planilha Excel importada)  
**Total:** 26.046 medicamentos (Julho 2025)  
**Atualização:** Manual via comando artisan

```
php artisan cmed:import arquivo.xlsx --mes="Outubro 2025"
```

### 7.4 TCE-RS (Tribunal de Contas - RS)

**Serviço:** `App\Services\TceRsApiService`

Busca em tempo real:
- Itens de contratos
- Itens de licitações
- Filtra por termo

### 7.5 Comprasnet (SIASG)

**Serviço:** `App\Services\ComprasnetApiService`

Busca:
- Contratos federais
- Itens contratados
- Enriquecimento com dados de órgãos

### 7.6 Portal da Transparência (CGU)

**API:** `/api-de-dados/`  
**Status:** Temporariamente desabilitado (exige `codigoOrgao` obrigatório)

---

## 8. COMMANDS ARTISAN <a name="commands"></a>

### 8.1 Importação de Dados

```bash
# CMED - Medicamentos ANVISA
php artisan cmed:import arquivo.xlsx --mes="Outubro 2025" --limpar

# Catmat - Catálogo de Materiais
php artisan catmat:import

# PNCP - Sincronização
php artisan pncp:sincronizar
php artisan pncp:sincronizar-completo

# Compras.gov - Preços
php artisan comprasgov:baixar-precos --limite-gb=3
php artisan comprasgov:worker        # Processamento paralelo

# TCE-RS - Contratações
php artisan tcers:import-tabelao
php artisan tcers:import-licitacoes
```

### 8.2 Sincronização

```bash
# PNCP Completo
php artisan pncp:sincronizar-completo

# Orientações Técnicas
php artisan orientacoes:import

# Orientações TCE-RS
php artisan tcers:import-tabelao

# Traz contratos já publicados
php artisan pncp:contratos-publicados
```

### 8.3 Utilitários

```bash
# Verificar setup do banco
php artisan check-database-setup

# Monitorar API Compras.gov
php artisan comprasgov:monitor

# Importar Licitación TCE-RS
php artisan licitacon:sincronizar
```

---

## 9. JAVASCRIPT E FUNCIONALIDADES <a name="javascript"></a>

### 9.1 Arquivos JavaScript

```
public/js/
├── modal-cotacao.js                 (117 KB) - Maior arquivo
├── modal-cotacao-performance-patch.js (8.6 KB) - Otimizações
├── performance-utils.js              (7.0 KB) - Utilitários
└── sistema-logs.js                   (7.7 KB) - Sistema de logs
```

### 9.2 modal-cotacao.js (117 KB)

**Responsabilidades:**

1. **Busca Multi-Fonte:**
   - Pesquisa Rápida (endpoint `/pesquisa-rapida/buscar`)
   - Busca PNCP (endpoint `/pncp/buscar`)
   - Busca Compras.gov (endpoint `/compras-gov/buscar`)
   - Busca CMED (medicamentos)
   - Busca TCE-RS
   - Busca Contratações Similares

2. **Manipulação de DOM:**
   - Renderização de tabela de resultados
   - Filtros e paginação
   - Seleção de itens (checkboxes)

3. **Adição ao Orçamento:**
   - Valida dados
   - Envia para backend
   - Atualiza tabela de itens

4. **Cache e Performance:**
   - Cache de resultados (60 segundos)
   - Debounce de busca (300ms)
   - Lazy loading de tabelas

### 9.3 Fluxo de uma Busca

```
USUÁRIO DIGITA "ARROZ" NA MODAL
            ↓
   Debounce (300ms)
            ↓
   Pesquisa Rápida (/pesquisa-rapida/buscar)
            ↓
   MÚLTIPLAS FONTES EM PARALELO:
   - CMED (medicamentos)
   - CATMAT+API (preços reais)
   - Banco Local PNCP
   - API PNCP
   - TCE-RS
   - Comprasnet
   - Portal Transparência
            ↓
   Consolidação de Resultados
            ↓
   Remoção de Duplicatas
            ↓
   Filtro de Valor > 0
            ↓
   Renderização de Tabela
            ↓
   USUÁRIO SELECIONA ITENS E CLICA "ADICIONAR"
            ↓
   Validação Frontend
            ↓
   POST /orcamentos/{id}/adicionar-itens
            ↓
   Backend salva OrcamentoItem
            ↓
   Atualiza tabela de itens
```

---

## 10. ROTAS WEB <a name="rotas"></a>

### 10.1 Rotas Públicas (sem autenticação)

```php
GET /health                    # Health check do módulo
GET /login                     # Tela de login
POST /login                    # Autenticar
POST /logout                   # Logout
GET /orcamentos/{id}/preview   # Preview público
GET /orcamentos/{id}/pdf       # Download PDF
GET /orcamentos/buscar         # AJAX busca pública
GET /pncp/buscar              # AJAX busca PNCP
GET /compras-gov/buscar       # AJAX busca Compras.gov
```

### 10.2 Rotas Autenticadas

```php
GET  /orcamentos              # Listar orçamentos
GET  /orcamentos/criar        # Tela nova orçamento
POST /orcamentos              # Salvar novo
GET  /orcamentos/{id}         # Detalhes
GET  /orcamentos/{id}/editar  # Tela editar
PUT  /orcamentos/{id}         # Atualizar
DELETE /orcamentos/{id}       # Deletar (soft delete)

# Etapas
POST /orcamentos/{id}/etapa-{num}/save  # Salvar etapa
GET  /orcamentos/{id}/etapa-{num}       # Carregar etapa

# Itens
POST /orcamentos/{id}/itens    # Adicionar item
PUT  /orcamentos/{id}/itens/{item_id}   # Atualizar item
DELETE /orcamentos/{id}/itens/{item_id} # Deletar item

# Importação
POST /orcamentos/importar-planilha  # Upload de Excel

# Fornecedores
GET  /fornecedores            # Listar
POST /fornecedores            # Cadastrar
PUT  /fornecedores/{id}       # Atualizar
DELETE /fornecedores/{id}     # Deletar

# APIs AJAX
GET  /api/notificacoes/nao-lidas                    # Polling
POST /api/notificacoes/{id}/marcar-lida
POST /api/pesquisa-rapida/buscar
POST /api/pesquisa-rapida/criar-orcamento
GET  /api/catalogo                                   # Listar produtos
POST /api/catalogo                                   # Criar produto
GET  /api/catalogo/{id}                              # Detalhes
PUT  /api/catalogo/{id}                              # Atualizar
DELETE /api/catalogo/{id}                            # Deletar
GET  /api/catalogo/{id}/referencias-preco
POST /api/catalogo/{id}/adicionar-preco
```

---

## 11. SERVICES ESPECIALIZADOS <a name="services"></a>

### 11.1 Services de API

```php
Services/
├── TceRsApiService.php           # Busca TCE-RS
├── ComprasnetApiService.php      # Busca Comprasnet
├── ComprasnetApiNovaService.php  # Versão melhorada
├── CnpjService.php               # Validação CNPJ via Receita
└── LicitaconService.php          # Integração LicitaCon
```

### 11.2 Services de PDF

```php
Services/PDF/
├── PDFDetectorManager.php        # Detecta formato de PDF
├── FormatoDetector.php           # Detecta tabelas
├── FormatoExtrator.php           # Extrai dados
└── Detectores/
    ├── GenericoDetector.php
    ├── MapaApuracaoDetector.php
    └── TabelaHorizontalDetector.php
```

### 11.3 Services de Análise

```php
Services/
├── EstatisticaService.php        # Cálculos estatísticos
├── CurvaABCService.php           # Análise Curva ABC
├── DataNormalizationService.php  # Normalização de dados
```

---

## 12. ESTATÍSTICAS DO PROJETO <a name="estatísticas"></a>

### 12.1 Métricas de Código

```
CONTROLLERS:        17.429 linhas (8 arquivos)
  - OrcamentoController        8.133 linhas ⭐ MAIOR
  - FornecedorController       2.483 linhas
  - PesquisaRapidaController   1.518 linhas
  - MapaAtasController         1.020 linhas
  - CatalogoController          887 linhas
  - Outros                      388 linhas

MODELS:             3.434 linhas (34 models)
  - Orcamento                   265 linhas
  - PrecoComprasGov             267 linhas
  - MedicamentoCmed             180 linhas
  - AuditLogItem                158 linhas
  - Outros                     2.564 linhas

VIEWS:              13 blade templates
JAVASCRIPT:         140 KB total
  - modal-cotacao.js            117 KB
  - sistema-logs.js             7.7 KB
  - performance-utils.js        7.0 KB
  - modal-patch.js              8.6 KB

MIGRATIONS:         69 arquivos com prefixo cp_
COMMANDS:           19 comandos Artisan
SERVICES:           17 classes especializadas
```

### 12.2 Banco de Dados

```
Tabelas Principais:        25+
Prefixo Obrigatório:       cp_
Suporte Multi-tenancy:     SIM (tenant_id adicionado)

Registros Estimados:
- cp_medicamentos_cmed:         26.046 medicamentos
- cp_precos_comprasgov:         100.000+ preços
- cp_contratos_pncp:            1.000+ contratos locais
- cp_catmat:                    50.000+ materiais
```

### 12.3 APIs Integradas

```
ATIVAS (tempo real):
  1. PNCP - /api/search/
  2. Compras.gov - /modulo-pesquisa-preco/
  3. TCE-RS - CKAN API
  4. Comprasnet - SIASG
  5. ViaCEP - Busca de endereços

IMPORTADAS (local):
  1. CMED - 26.046 medicamentos (Julho 2025)
  2. CATMAT - 50.000+ materiais
  3. PNCP - Sincronização diária

DESABILITADAS:
  1. Portal Transparência - CGU (exige codigoOrgao obrigatório)
```

### 12.4 Performance

```
Modal de Cotação:
- Busca multi-fonte em paralelo: 3-5 segundos
- Cache de resultados: 60 segundos
- Debounce de busca: 300ms

Importação de Preços:
- Limite padrão: 3 GB
- Velocidade: ~1000 registros/segundo
- Processamento paralelo: 4 workers

Busca no Banco Local:
- FTS (Full-Text Search): < 100ms
- Índices: catmat_codigo, preco_unitario, data_compra
```

---

## RESUMO EXECUTIVO

### O que o módulo faz bem:

1. ✅ **Multi-fonte:** Busca em 7 fontes de dados diferentes
2. ✅ **Otimizado:** Cache, FTS, índices de banco de dados
3. ✅ **Completo:** 7 etapas de elaboração de orçamento
4. ✅ **PDF:** Geração de orçamento estimativo
5. ✅ **Rastreável:** Logs, auditoria, histórico
6. ✅ **Escalável:** Suporte multi-tenancy
7. ✅ **Integrado:** Sincronização com sistemas externos

### Desafios Atuais:

1. ⚠️ OrcamentoController muito grande (8.133 linhas)
2. ⚠️ Modal de Cotação complexo (117 KB JS)
3. ⚠️ API PNCP com rate limits implícitos
4. ⚠️ Compras.gov apenas últimos 12 meses
5. ⚠️ Portal Transparência necessita configuração

### Próximas Melhorias:

1. Refatorar OrcamentoController em Services
2. Separar JavaScript em módulos
3. Implementar WebSocket para busca em tempo real
4. Cache distribuído (Redis)
5. Análise de preços com IA (sugestões automáticas)

---

**Documento gerado:** 30/10/2025  
**Versão do módulo:** 1.0 (ativo)  
**Status:** Em desenvolvimento contínuo
