# DIAGRAMA DE RELACIONAMENTOS E FLUXO - MÓDULO CESTA DE PREÇOS

---

## 1. DIAGRAMA DE ENTIDADE-RELACIONAMENTO (ER)

### Núcleo Central

```
┌──────────────────────────────────────────────────────────────┐
│                     ORCAMENTO (central)                      │
├──────────────────────────────────────────────────────────────┤
│ id, numero (00001/2025), nome, objeto, status               │
│ etapa_atual, data_conclusao, user_id, orgao_id              │
│ metodologia_analise_critica, medida_tendencia_central        │
│ prazo_validade_amostras, numero_minimo_amostras              │
│ orcamentista_nome, orcamentista_cpf_cnpj, brasao_path       │
└──────────────────────────────────────────────────────────────┘
         │        │         │           │           │
         ├────────┼─────────┼───────────┼───────────┤
         │        │         │           │           │
         ▼        ▼         ▼           ▼           ▼
      USER    ORGAO   ORCAMENTO    LOTE        SOLICITACAO
               (1)      ITEM     (muitos)       CDF
             (belongs) (hasMany) (hasMany)    (hasMany)
```

### Orcamento ↔ OrcamentoItem

```
ORCAMENTO (1) ──────────── (muitos) ORCAMENTO_ITEM
                           ├── descricao
                           ├── quantidade
                           ├── preco_unitario
                           ├── preco_total
                           ├── unidade_medida
                           ├── numero_item
                           ├── origem_dados
                           ├── orgao_referencia
                           ├── amostras_selecionadas (JSON)
                           ├── criticas (JSON)
                           └── import_log
```

### Orcamento ↔ Busca de Preços

```
ORCAMENTO ──→ MODAL_COTACAO
              ↓
        Pesquisa Multi-Fonte:
        ├── CMED (26.046 medicamentos)
        ├── CATMAT+API (50.000 materiais)
        ├── ContratoPNCP (banco local)
        ├── API PNCP (tempo real)
        ├── TCE-RS
        ├── Comprasnet
        └── Portal Transparência
              ↓
        Seleção de Itens
              ↓
        POST /orcamentos/{id}/itens
              ↓
        OrcamentoItem criado
              ↓
        Atualiza Tabela
```

### Relacionamentos Completos

```
ORCAMENTO
├── user_id ───→ USER
├── orgao_id ───→ ORGAO
├── hasMany(OrcamentoItem)
├── hasMany(Lote)
├── hasMany(SolicitacaoCDF)
├── hasMany(ContratacaoSimilar)
├── hasMany(ColetaEcommerce)
├── hasMany(Anexo)
├── orcamento_origem_id ───→ ORCAMENTO (self-join)
└── hasMany(orcamentosDerivados) ───→ ORCAMENTO

ORCAMENTO_ITEM
├── orcamento_id ───→ ORCAMENTO
└── (outros campos, sem FK adicional)

FORNECEDOR
├── hasMany(FornecedorItem)
└── numero_documento (único)

CONTRATO_PNCP
├── numero_controle_pncp (único - PNCP)
├── tipo: [contrato, ata, edital]
└── dados de órgão

MEDICAMENTO_CMED
├── ean1, ean2, ean3 (EAN)
├── laboratorio
└── preços: pmc_0, pmc_12, pmc_17, pmc_18, pmc_20

PRECO_COMPRASGOV
├── catmat_codigo ───→ CATMAT
├── fornecedor_cnpj
├── data_compra
└── valores reais

CATMAT
├── codigo (único)
├── titulo
├── tem_preco_comprasgov (boolean)
└── contador_ocorrencias
```

---

## 2. FLUXO DE DADOS - BUSCA E PREÇOS

### Fluxo Principal: Criar Orçamento

```
┌─────────────────────────────────────────────────────────────┐
│ USUÁRIO ACESSA: /orcamentos/criar                           │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ FORMULÁRIO COM 7 ABAS (create.blade.php)                    │
├─────────────────────────────────────────────────────────────┤
│ Aba 1: Identificação Geral                                  │
│ Aba 2: Metodologia de Análise                               │
│ Aba 3: Cadastramento de Itens                               │
│ Aba 4: Pesquisa de Preços (MODAL)                           │
│ Aba 5: Contratações Similares                               │
│ Aba 6: Dados do Orcamentista                                │
│ Aba 7: Análise Crítica                                      │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     PREENCH ABA 1-3 E CLICA "BUSCAR PREÇOS"
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ MODAL DE COTAÇÃO ABRE                                       │
│ (modal-cotacao.blade.php)                                   │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     USUÁRIO DIGITA TERMO NA BARRA DE BUSCA
                         │
                         ▼
        DEBOUNCE 300ms + VALIDAÇÃO
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ POST /api/pesquisa-rapida/buscar?termo=ARROZ               │
│ (PesquisaRapidaController@buscar)                           │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     BUSCA EM PARALELO EM 7 FONTES:
     
     ┌─────────────────────────────────────────┐
     │ 1. CMED                                 │
     │    SELECT * FROM cp_medicamentos_cmed  │
     │    WHERE substancia ILIKE '%ARROZ%'    │
     │    LIMIT 100                            │
     └─────────────────────────────────────────┘
                         │
     ┌─────────────────────────────────────────┐
     │ 2. CATMAT+API                           │
     │    a) SELECT * FROM cp_catmat           │
     │       WHERE to_tsvector...              │
     │    b) Para cada CATMAT:                 │
     │       GET /compras.gov/1_consultarMat  │
     │    c) SELECT * FROM cp_precos_comprasgov│
     │       WHERE catmat = 'XX' AND           │
     │       preco_unitario > 0                │
     └─────────────────────────────────────────┘
                         │
     ┌─────────────────────────────────────────┐
     │ 3. BANCO LOCAL PNCP                     │
     │    SELECT * FROM cp_contratos_pncp      │
     │    WHERE objeto_contrato ILIKE '%..%'   │
     │    AND data_publicacao >= -12 meses     │
     └─────────────────────────────────────────┘
                         │
     ┌─────────────────────────────────────────┐
     │ 4. API PNCP (/api/search)               │
     │    GET https://pncp.gov.br/api/search/  │
     │    ?q=ARROZ&tipos_documento=contrato    │
     │    &pagina=1&tamanhoPagina=10           │
     │    (5 páginas = 50 contratos)           │
     └─────────────────────────────────────────┘
                         │
     ┌─────────────────────────────────────────┐
     │ 5. TCE-RS                               │
     │    Via TceRsApiService                  │
     │    Busca: itensContratos + itensLicitac│
     └─────────────────────────────────────────┘
                         │
     ┌─────────────────────────────────────────┐
     │ 6. COMPRASNET                           │
     │    Via ComprasnetApiService             │
     │    buscarItensContratos($termo)         │
     └─────────────────────────────────────────┘
                         │
     ┌─────────────────────────────────────────┐
     │ 7. PORTAL TRANSPARÊNCIA (desabilitado)  │
     │    Status: Aguarda configuração         │
     └─────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ CONSOLIDAÇÃO DE RESULTADOS                                  │
├─────────────────────────────────────────────────────────────┤
│ 1. Array merge dos 7 resultados                             │
│ 2. Filtrar valores > 0                                      │
│ 3. Remover duplicatas (por MD5)                             │
│ 4. Ordenar por confiabilidade                               │
│ 5. Retornar JSON com:                                       │
│    - total: N                                               │
│    - resultados: [{...}, {...}]                             │
│    - fontes: {CMED: 5, COMPRAS_GOV: 10, ...}                │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ RENDERIZAR TABELA DE RESULTADOS (modal-cotacao.js)          │
├─────────────────────────────────────────────────────────────┤
│ Coluna 1: Checkbox (seleção)                                │
│ Coluna 2: Descrição (truncada)                              │
│ Coluna 3: Valor Unitário (formatado)                        │
│ Coluna 4: Unidade                                           │
│ Coluna 5: Fornecedor                                        │
│ Coluna 6: Órgão/Origem                                      │
│ Coluna 7: Data                                              │
│ Filtro: Fonte, Unidade, Valor Min/Max                       │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     USUÁRIO SELECIONA ITENS + CLICA "ADICIONAR"
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ VALIDAÇÃO FRONTEND                                          │
├─────────────────────────────────────────────────────────────┤
│ ✓ Descrição não vazia                                       │
│ ✓ Preço > 0                                                 │
│ ✓ Quantidade > 0                                            │
│ ✓ Unidade informada                                         │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     POST /orcamentos/{id}/itens
     Body: [
       {
         descricao: "Arroz tipo 1",
         quantidade: 100,
         preco_unitario: 125.50,
         unidade_medida: "UN",
         origem_dados: "COMPRAS.GOV"
       },
       ...
     ]
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ BACKEND: OrcamentoController@adicionarItens                 │
├─────────────────────────────────────────────────────────────┤
│ 1. Validar dados                                            │
│ 2. Para cada item:                                          │
│    - Criar OrcamentoItem                                    │
│    - numero_item = max + 1                                  │
│    - preco_total = quantidade * preco_unitario              │
│    - Salvar no banco                                        │
│ 3. Retornar JSON {"success": true}                          │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ FRONTEND: Atualizar Tabela de Itens                         │
├─────────────────────────────────────────────────────────────┤
│ Recarregar lista de itens da Aba 3                          │
│ Mostrar toast: "X itens adicionados com sucesso"            │
│ Fechar modal                                                │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     USUÁRIO CONTINUA NAS DEMAIS ABAS (4-7)
                         │
                         ▼
     CLICA "FINALIZAR ORÇAMENTO"
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ SALVAR TUDO NO BANCO                                        │
├─────────────────────────────────────────────────────────────┤
│ 1. Atualizar cp_orcamentos com status = "realizado"         │
│ 2. Todos os OrcamentoItems já salvos                        │
│ 3. Gerar PDF se solicitado                                  │
│ 4. Salvar em storage/pdfs/                                  │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
     REDIRECIONAR PARA /orcamentos/{id} (sucesso)
```

---

## 3. FLUXO DE IMPORTAÇÃO DE DADOS

### 3.1 CMED (26.046 medicamentos)

```
USUÁRIO OBTÉM ARQUIVO EXCEL
        ↓
php artisan cmed:import arquivo.xlsx --mes="Outubro 2025"
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. LEITURA DO ARQUIVO                                       │
│    PhpSpreadsheet\IOFactory::load()                         │
│    - Detecta 74 colunas (A até BV)                          │
│    - Mapeia para campos do banco:                           │
│      B → substancia                                         │
│      C → cnpj_laboratorio                                   │
│      D → laboratorio                                        │
│      ...                                                    │
│      O → pf_0, P → pf_12, etc (preços)                      │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. INSERÇÃO EM LOTE                                         │
│    INSERT INTO cp_medicamentos_cmed (...)                   │
│    VALUES (row1), (row2), ..., (row1000)                    │
│    - Batch inserts de 1000 registros                        │
│    - ~260 batches para 26.046 medicamentos                  │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. CRIAÇÃO DE ÍNDICES                                       │
│    CREATE INDEX ON substancia, laboratorio, etc             │
└─────────────────────────────────────────────────────────────┘
        ↓
✅ 26.046 medicamentos prontos para busca
```

### 3.2 Preços Compras.gov

```
php artisan comprasgov:baixar-precos --limite-gb=3
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. BUSCAR CÓDIGOS CATMAT (Top 10k)                          │
│    SELECT codigo, titulo FROM cp_catmat                     │
│    ORDER BY contador_ocorrencias DESC                       │
│    LIMIT 10000                                              │
└─────────────────────────────────────────────────────────────┘
        ↓
FOR EACH CATMAT:
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. CHAMAR API COMPRAS.GOV                                   │
│    GET /modulo-pesquisa-preco/1_consultarMaterial           │
│    ?codigoItemCatalogo={codigo}&pagina=1                    │
│    - Rate limit: ~500ms por requisição                      │
│    - Timeout: 10 segundos                                   │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. PROCESSAR RESPOSTA                                       │
│    - Extrair precos (últimos 12 meses)                      │
│    - Validar: preco_unitario > 0                            │
│    - Formatar: data, valores                                │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. INSERIR EM LOTE                                          │
│    INSERT INTO cp_precos_comprasgov (...)                   │
│    VALUES (price1), (price2), ...                           │
│    - 1000 preços/segundo (estimado)                         │
│    - Buffer: ~100MB para 100k preços                        │
└─────────────────────────────────────────────────────────────┘
        ↓
REPETIR PARA PRÓXIMO CATMAT
        ↓
VERIFICAR LIMITE DE TAMANHO (3 GB)
        ↓
✅ Preços Compras.gov sincronizados
```

---

## 4. FLUXO DE GERAÇÃO DE PDF

### Processo de Geração

```
USUÁRIO CLICA: GET /orcamentos/{id}/pdf
        ↓
OrcamentoController@gerarPDF($id)
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. CARREGAR DADOS                                           │
│    $orcamento = Orcamento::with([                           │
│        'itens', 'user', 'orgao', 'solicitacoesCDF'          │
│    ])->findOrFail($id)                                      │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. RENDERIZAR BLADE TEMPLATE                                │
│    resources/views/orcamentos/pdf.blade.php                 │
│    - Cabeçalho com brasão                                   │
│    - Etapas 1-7 formatadas                                  │
│    - Tabela de itens com preços                             │
│    - Totalizações                                           │
│    - Assinatura digital                                     │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. CONVERTER PARA PDF                                       │
│    mPDF ou DomPDF:                                          │
│    - HTML → PDF                                            │
│    - Insere imagem de brasão                                │
│    - Pagina landscape para tabelas grandes                  │
│    - Cria múltiplas páginas se necessário                   │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. SALVAR E RETORNAR                                        │
│    storage/pdfs/orcamento_{id}_{timestamp}.pdf              │
│    response()->download($caminho)                           │
└─────────────────────────────────────────────────────────────┘
        ↓
✅ PDF baixado no navegador
```

---

## 5. FLUXO DE SINCRONIZAÇÃO PNCP

```
php artisan pncp:sincronizar-completo
        ↓
┌─────────────────────────────────────────────────────────────┐
│ ETAPA 1: BUSCAR CONTRATOS (últimos 12 meses)               │
│                                                             │
│ Parámetros:                                                 │
│ - dataInicial: 12 meses atrás                               │
│ - dataFinal: hoje                                           │
│ - pagina: 1, 2, 3, ...                                      │
│ - tamanhoPagina: 500 (máximo)                               │
│                                                             │
│ API: GET https://pncp.gov.br/api/consulta/v1/contratos     │
│ Delay: 1-2 segundos entre requisições                       │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ ETAPA 2: PROCESSAR RESPOSTA                                 │
│                                                             │
│ Para cada contrato:                                         │
│ 1. Extrair campos:                                          │
│    - numero_controle_pncp                                   │
│    - objeto_contrato (descrição)                            │
│    - valor_global                                           │
│    - valor_unitario_estimado                                │
│    - orgao_razao_social                                     │
│    - data_publicacao_pncp                                   │
│    - tipo: [contrato, ata, edital]                          │
│                                                             │
│ 2. Validar:                                                 │
│    - valor_global > 0                                       │
│    - data_publicacao_pncp >= -12m                           │
│                                                             │
│ 3. Calcular:                                                │
│    - confiabilidade: [alta, media, baixa]                   │
│    - valor_estimado: boolean                                │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ ETAPA 3: INSERIR EM LOTE                                    │
│                                                             │
│ INSERT OR REPLACE INTO cp_contratos_pncp (...)              │
│ VALUES (contrato1), (contrato2), ...                        │
│                                                             │
│ - Evita duplicatas (PK: numero_controle_pncp)               │
│ - Batch inserts de 1000 registros                           │
│ - Timestamp: sincronizado_em = now()                        │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ ETAPA 4: CRIAR ÍNDICES                                      │
│                                                             │
│ CREATE INDEX ON:                                            │
│ - objeto_contrato (para ILIKE)                              │
│ - data_publicacao_pncp (para range queries)                 │
│ - valor_global (para filtros de valor)                      │
└─────────────────────────────────────────────────────────────┘
        ↓
MOSTRAR STATS:
- Total de contratos sincronizados
- Novos vs. atualizados
- Tempo decorrido
- Próxima sincronização em

✅ Banco PNCP local atualizado
```

---

## 6. ARQUITETURA MULTI-TENANT

### Isolamento de Dados

```
USUÁRIO ACESSA MÓDULO
        ↓
middleware: ProxyAuth
        ↓
┌─────────────────────────────────────────────────────────────┐
│ EXTRAIR INFORMAÇÕES DO HEADER                               │
│ - X-Tenant-ID (ID do órgão)                                 │
│ - X-User-ID (ID do usuário)                                 │
│ - X-User-Email (Email do usuário)                           │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ PASSAR PARA REQUEST                                         │
│ $request->attributes->set('tenant', ['id' => 123])          │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ TODOS OS QUERIES FILTRAM POR tenant_id                      │
│                                                             │
│ Orcamento::where('tenant_id', $tenantId)                    │
│ Fornecedor::where('tenant_id', $tenantId)                   │
│ OrcamentoItem::whereHas('orcamento', function($q) {         │
│     $q->where('tenant_id', $tenantId)                       │
│ })                                                          │
└─────────────────────────────────────────────────────────────┘
        ↓
✅ Dados isolados por tenant (órgão)
```

---

## 7. FLUXO DE NOTIFICAÇÕES

### Sistema de Polling

```
PÁGINA CARREGA: /orcamentos/criar
        ↓
JavaScript setInterval() a cada 5 segundos
        ↓
GET /api/notificacoes/nao-lidas
Header: X-User-Email
        ↓
NotificacaoController@naoLidas
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. MAPEAR USUÁRIO PELO EMAIL                                │
│    User::where('email', $request->header('X-User-Email'))   │
│    .first()                                                 │
│                                                             │
│    (Importante: usa email, não ID!                          │
│     Porque o ID vem do MinhaDattaTech, mas precisa do ID    │
│     local do módulo)                                        │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. BUSCAR NOTIFICAÇÕES NÃO LIDAS                            │
│    Notificacao::where('user_id', $user->id)                 │
│    .where('lida', false)                                    │
│    .orderBy('created_at', 'desc')                           │
│    .limit(10)                                               │
│    .get()                                                   │
└─────────────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. RETORNAR JSON                                            │
│    {                                                        │
│      "success": true,                                       │
│      "count": 3,                                            │
│      "notificacoes": [                                      │
│        {                                                    │
│          "id": 1,                                           │
│          "tipo": "orcamento_aprovado",                      │
│          "titulo": "Orçamento aprovado",                    │
│          "mensagem": "Seu orçamento #001/2025 foi...",      │
│          "dados": {...},                                    │
│          "created_at": "30/10/2025 14:30"                   │
│        }                                                    │
│      ]                                                      │
│    }                                                        │
└─────────────────────────────────────────────────────────────┘
        ↓
JAVASCRIPT ATUALIZA BADGE COM CONTA
        ↓
SE HOUVER NOVAS NOTIFICAÇÕES:
- Exibir toast/notification
- Atualizar ícone no header
- Soar som (opcional)

✅ Notificações em tempo real
```

---

## 8. MATRIZ DE RESPONSABILIDADES

```
┌─────────────────────────────┬─────────────┬──────────────┬────────────┐
│ Funcionalidade              │ Controller  │ Model        │ Service    │
├─────────────────────────────┼─────────────┼──────────────┼────────────┤
│ CRUD Orçamentos             │ Orcamento   │ Orcamento    │ -          │
│ 7 Etapas                    │ Orcamento   │ Orcamento    │ -          │
│ Gerar PDF                   │ Orcamento   │ Orcamento    │ PDF/*      │
│ Pesquisa Rápida             │ Pesquisa    │ Múltiplos    │ TceRs,etc  │
│ Catálogo de Produtos        │ Catalogo    │ Catalogo     │ -          │
│ Fornecedores                │ Fornecedor  │ Fornecedor   │ ViaCEP     │
│ Mapa de Atas                │ MapaAtas    │ Arp*         │ -          │
│ Notificações                │ Notificacao │ Notificacao  │ -          │
│ Sincronização PNCP          │ -           │ ContratoPNCP │ -          │
│ Importação CMED             │ -           │ Medicamento  │ -          │
│ Preços Compras.gov          │ -           │ PrecoComp*   │ -          │
├─────────────────────────────┼─────────────┼──────────────┼────────────┤
│ TOTAL                       │ 8 files     │ 34 models    │ 17 services│
└─────────────────────────────┴─────────────┴──────────────┴────────────┘
```

---

## 9. STACK TECNOLÓGICO

```
BACKEND:
├── Laravel 10+ (Framework)
├── PostgreSQL (Banco de Dados)
│   ├── Full-Text Search (português)
│   ├── JSON fields para amostras/críticas
│   └── Índices para performance
├── Eloquent ORM (Data Mapper)
├── Artisan Commands (CLI)
├── Queue Jobs (processamento assíncrono)
└── Cache (Redis/File)

FRONTEND:
├── Blade Templates (Server-side rendering)
├── Vanilla JavaScript (DOM manipulation)
├── Fetch API (AJAX)
├── HTML/CSS/Bootstrap (UI)
└── No framework frontend (vanillla JS)

INTEGRAÇÕES:
├── PNCP API (REST)
├── Compras.gov API (REST)
├── ViaCEP (CEP lookup)
├── mPDF/DomPDF (PDF generation)
├── PhpSpreadsheet (Excel parsing)
└── GuzzleHttp (HTTP client)

PADRÕES:
├── MVC (Model-View-Controller)
├── Repository Pattern (não implementado)
├── Service Layer (parcial)
└── Middleware (autenticação, tenant)
```

---

## 10. FLUXO DE ERROS E EXCEÇÕES

```
ERRO NA API PNCP
        ↓
┌─────────────────────────────────────────────────────────────┐
│ catch (Exception $e)                                        │
│ {                                                           │
│    Log::warning('API PNCP falhou', ['erro' => ...])         │
│    // Continua com próxima fonte                            │
│    return resultado_parcial                                 │
│ }                                                           │
└─────────────────────────────────────────────────────────────┘
        ↓
✅ Sistema não quebra - outras fontes continuam

ERRO DE VALIDAÇÃO NO FRONTEND
        ↓
JS valida antes de enviar:
├── Descrição não vazia
├── Preço > 0
├── Quantidade > 0
└── Unidade informada

Se FALHAR:
└── Mostra erro em toast + mantém modal aberta

ERRO DE BANCO DE DADOS
        ↓
Transaction rollback automático
        ↓
Retorna erro JSON ao frontend
        ↓
Usuario vê mensagem amigável
```

---

**Diagrama Gerado:** 30/10/2025  
**Versão:** 1.0  
**Status:** Completo
