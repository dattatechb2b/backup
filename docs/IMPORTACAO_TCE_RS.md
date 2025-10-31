# Importação TCE-RS - Documentação

## 📋 Visão Geral

O comando `cesta:importar-tce-rs` permite importar contratos públicos do Tribunal de Contas do Estado do Rio Grande do Sul (TCE-RS) com **preços reais praticados**.

## ✅ O que foi implementado

### FASE 3 - Importação TCE-RS (COMPLETA)

**Arquivos criados/modificados:**

1. **Migrations:**
   - `2025_10_23_155204_create_cp_contratos_externos_table.php` - Tabela de contratos
   - `2025_10_23_155224_create_cp_itens_contrato_externo_table.php` - Tabela de itens (com preços!)
   - `2025_10_23_155251_create_cp_checkpoint_importacao_table.php` - Controle de importação

2. **Models:**
   - `ContratoExterno.php` - Model de contratos
   - `ItemContratoExterno.php` - Model de itens com preços
   - `CheckpointImportacao.php` - Controle de processamento

3. **Command:**
   - `ImportarTceRs.php` - Comando completo de importação (862 linhas)

## 🚀 Como usar

### 1. Executar migrations

```bash
php artisan migrate
```

### 2. Download do arquivo ZIP

Acesse: https://dados.tce.rs.gov.br/dataset/contratos-consolidado-2025

Ou baixe direto:
```bash
wget https://dados.tce.rs.gov.br/dados/licitacon/contrato/ano/2025.csv.zip -O /tmp/contratos-2025.zip
```

### 3. Executar importação

**Opção A: Com arquivo local (ZIP ou CSV)**
```bash
php artisan cesta:importar-tce-rs contratos --arquivo=/tmp/contratos-2025.zip
```

**Opção B: Com URL para download**
```bash
php artisan cesta:importar-tce-rs contratos --url=https://dados.tce.rs.gov.br/dados/licitacon/contrato/ano/2025.csv.zip
```

**Opção C: Limpar dados antigos antes de importar**
```bash
php artisan cesta:importar-tce-rs contratos --arquivo=/tmp/contratos-2025.zip --limpar
```

### 4. Verificar importação

```sql
-- Ver total de contratos importados
SELECT COUNT(*) FROM cp_contratos_externos WHERE fonte LIKE 'TCE-RS%';

-- Ver total de itens com preços
SELECT COUNT(*) FROM cp_itens_contrato_externo WHERE valor_unitario > 0;

-- Ver qualidade dos dados
SELECT
    CASE
        WHEN qualidade_score >= 80 THEN 'Excelente'
        WHEN qualidade_score >= 60 THEN 'Bom'
        WHEN qualidade_score >= 40 THEN 'Regular'
        ELSE 'Ruim'
    END as qualidade,
    COUNT(*) as total
FROM cp_itens_contrato_externo
GROUP BY
    CASE
        WHEN qualidade_score >= 80 THEN 'Excelente'
        WHEN qualidade_score >= 60 THEN 'Bom'
        WHEN qualidade_score >= 40 THEN 'Regular'
        ELSE 'Ruim'
    END
ORDER BY qualidade;
```

## 🎯 Funcionalidades Implementadas

### 1. Processamento de ZIP
- Detecta automaticamente arquivos ZIP
- Extrai e processa múltiplos CSVs
- Prioriza CONTRATO antes de ITEM_CON (dependência)

### 2. Streaming de CSV
- Processa linha por linha (sem carregar tudo na memória)
- Detecta encoding automaticamente (UTF-8, ISO-8859-1, Windows-1252)
- Progress bar com memória e tempo decorrido

### 3. Checkpointing
- Calcula SHA256 do arquivo
- Evita reprocessar arquivos iguais
- Permite resumir importação interrompida
- Rastreia última linha processada

### 4. Deduplicação Inteligente
- **Contratos:** Hash baseado em fonte + id_externo + número + CNPJ + data
- **Itens:** Hash baseado em contrato_id + número_item + descrição + valor
- UPSERT automático (insert ou update)

### 5. Mapeamento Flexível de Colunas
Aceita múltiplas variações de nomes de colunas:

**Contratos:**
- ID: `CD_CONTRATO`, `id_contrato`, `NR_CONTRATO`, etc.
- Número: `NR_CONTRATO`, `numero_contrato`, etc.
- Objeto: `DS_OBJETO`, `objeto`, `descricao`, etc.
- Valor: `VL_CONTRATO`, `valor_total`, `VL_INICIAL`, etc.
- Datas: `DT_ASSINATURA`, `DT_INICIO_VIGENCIA`, etc.
- Órgão: `DS_ORGAO`, `NM_ORGAO`, `NR_CNPJ_ORGAO`, etc.
- Fornecedor: `NM_CONTRATADO`, `NR_CNPJ_CONTRATADO`, etc.

**Itens:**
- Número: `NR_ITEM`, `numero_item`, `SEQ_ITEM`, etc.
- Descrição: `DS_ITEM`, `descricao`, `DS_DESCRICAO`, etc.
- Quantidade: `QT_ITEM`, `QT_CONTRATADA`, etc.
- Unidade: `DS_UNIDADE`, `UN`, `SG_UNIDADE`, etc.
- **Preço Unitário:** `VL_UNITARIO`, `valor_unitario`, `VL_PRECO_UNITARIO` ⭐
- Valor Total: `VL_TOTAL_ITEM`, `VL_ITEM`, etc.
- CATMAT: `CD_CATMAT`, `catmat`, etc.
- CATSER: `CD_CATSER`, `catser`, etc.

### 6. Quality Score (0-100)

**Contratos:**
- 100 pontos base
- -10 por falta de número
- -10 por falta de CNPJ órgão
- -10 por falta de CNPJ fornecedor
- -15 por falta de data assinatura
- -15 por valor zerado/inválido

**Itens (PREÇOS):**
- 100 pontos base
- -20 por falta de descrição
- **-30 por falta de valor unitário** (CRÍTICO!)
- -15 por falta de quantidade
- -10 por falta de unidade

### 7. Flags de Qualidade

**Contratos:**
- `sem_numero`, `sem_cnpj_orgao`, `sem_cnpj_fornecedor`, `sem_data`, `valor_zerado`

**Itens:**
- `sem_descricao`
- **`sem_preco`** (FLAG CRÍTICA!)
- `sem_quantidade`, `sem_unidade`
- `com_catmat`, `com_catser` (flags positivas)

### 8. Normalização de Dados

- **CNPJ:** 14 dígitos com zeros à esquerda
- **Valores:** Float com conversão de vírgula para ponto
- **Datas:** Formato Y-m-d (ISO 8601)
- **Encoding:** Conversão automática para UTF-8

## 📊 Estrutura dos Dados

### Tabela: cp_contratos_externos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | PK auto-incremento |
| fonte | varchar(50) | TCE-RS-CONTRATOS |
| id_externo | varchar(255) | ID do TCE-RS |
| hash_normalizado | varchar(64) | SHA256 para dedup |
| numero_contrato | varchar(100) | Número oficial |
| objeto | text | Descrição do objeto |
| valor_total | decimal(15,2) | Valor total |
| data_assinatura | date | Data de assinatura |
| orgao_nome | varchar(255) | Nome do órgão |
| orgao_cnpj | varchar(18) | CNPJ do órgão |
| orgao_uf | varchar(2) | RS |
| fornecedor_nome | varchar(255) | Fornecedor/Contratado |
| fornecedor_cnpj | varchar(18) | CNPJ fornecedor |
| qualidade_score | int | 0-100 |
| flags_qualidade | jsonb | Array de flags |
| dados_originais | jsonb | CSV original |

### Tabela: cp_itens_contrato_externo (PREÇOS!)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | PK auto-incremento |
| contrato_id | bigint | FK → cp_contratos_externos |
| numero_item | int | Sequencial do item |
| hash_normalizado | varchar(64) | SHA256 para dedup |
| descricao | text | Descrição do item |
| quantidade | decimal(15,4) | Quantidade contratada |
| unidade | varchar(20) | UN, KG, CX, etc. |
| **valor_unitario** | **decimal(15,2)** | **PREÇO UNITÁRIO!** ⭐ |
| valor_total | decimal(15,2) | Valor total do item |
| catmat | varchar(20) | Código CATMAT (se houver) |
| catser | varchar(20) | Código CATSER (se houver) |
| qualidade_score | int | 0-100 |
| flags_qualidade | jsonb | Array de flags |

### Tabela: cp_checkpoint_importacao

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | PK |
| fonte | varchar(50) | TCE-RS-CONTRATOS ou TCE-RS-ITENS |
| arquivo | varchar(255) | Nome do arquivo |
| checksum | varchar(64) | SHA256 do arquivo |
| status | varchar(20) | em_processamento, concluido, erro |
| registros_processados | int | Total processado |
| registros_novos | int | Inseridos |
| registros_atualizados | int | Atualizados |
| registros_erro | int | Com erro |
| ultima_linha_processada | int | Para resume |
| erro_mensagem | text | Se houver erro |

## 🔍 Queries Úteis

### Buscar itens por descrição (full-text search)
```sql
SELECT i.*, c.numero_contrato, c.orgao_nome
FROM cp_itens_contrato_externo i
JOIN cp_contratos_externos c ON i.contrato_id = c.id
WHERE to_tsvector('portuguese', i.descricao) @@ plainto_tsquery('portuguese', 'caneta')
  AND i.valor_unitario > 0
  AND i.qualidade_score >= 70
ORDER BY i.valor_unitario
LIMIT 10;
```

### Estatísticas de preços por descrição
```sql
SELECT
    descricao,
    COUNT(*) as amostras,
    MIN(valor_unitario) as preco_min,
    AVG(valor_unitario) as preco_medio,
    MAX(valor_unitario) as preco_max,
    unidade
FROM cp_itens_contrato_externo
WHERE to_tsvector('portuguese', descricao) @@ plainto_tsquery('portuguese', 'notebook')
  AND valor_unitario > 0
  AND qualidade_score >= 70
GROUP BY descricao, unidade
ORDER BY amostras DESC;
```

### Contratos recentes com itens
```sql
SELECT
    c.numero_contrato,
    c.orgao_nome,
    c.fornecedor_nome,
    c.data_assinatura,
    COUNT(i.id) as total_itens,
    SUM(i.valor_total) as valor_total_itens
FROM cp_contratos_externos c
LEFT JOIN cp_itens_contrato_externo i ON c.id = i.contrato_id
WHERE c.fonte LIKE 'TCE-RS%'
  AND c.data_assinatura >= CURRENT_DATE - INTERVAL '12 months'
GROUP BY c.id, c.numero_contrato, c.orgao_nome, c.fornecedor_nome, c.data_assinatura
ORDER BY c.data_assinatura DESC
LIMIT 20;
```

## ⚙️ Próximos Passos

### FASE 4: Qualidade e Deduplicação
- Detecção de outliers (preços muito acima/abaixo da média)
- Normalização de descrições (machine learning)
- Matching com CATMAT automatizado
- Clustering de produtos similares

### FASE 5: Integração
- Controller para consulta de preços TCE-RS
- Endpoint API REST
- Integração com tela de "Pesquisa Rápida"
- Exibição de amostras do TCE-RS junto com PNCP/CMED

### FASE 6: Automação
- Scheduler Laravel para atualização mensal
- Comando de atualização incremental
- Notificações de novas importações
- Dashboard de monitoramento

### FASE 7: Testes
- Testes com itens reais: caneta, notebook, toner
- Validação de preços vs mercado
- Análise de qualidade dos dados
- Benchmarks de performance

## 🐛 Troubleshooting

### Erro: "Contrato pai não encontrado"
Significa que o arquivo ITEM_CON foi processado antes do CONTRATO. Solução:
- Se for ZIP, o comando já ordena automaticamente (CONTRATO → ITEM_CON)
- Se for CSV individual, importe CONTRATO.csv primeiro, depois ITEM_CON.csv

### Encoding incorreto
O comando detecta automaticamente UTF-8, ISO-8859-1 e Windows-1252. Se houver problemas:
- Verifique a primeira linha do CSV
- Converta manualmente: `iconv -f WINDOWS-1252 -t UTF-8 input.csv > output.csv`

### Memória insuficiente
O comando usa streaming (processa linha por linha), mas se houver problemas:
```bash
php -d memory_limit=512M artisan cesta:importar-tce-rs contratos --arquivo=...
```

### Checkpoint travado
Se uma importação falhou e ficou "em_processamento":
```sql
UPDATE cp_checkpoint_importacao
SET status = 'erro', erro_mensagem = 'Resetado manualmente'
WHERE status = 'em_processamento' AND fonte = 'TCE-RS-CONTRATOS';
```

## 📈 Métricas Esperadas

Com dados de 2025 (estimativa):
- **Contratos:** ~50.000-100.000 registros
- **Itens:** ~500.000-1.000.000 registros
- **Tempo de processamento:** 5-15 minutos (depende do hardware)
- **Tamanho no disco:** ~500MB-1GB (com índices)
- **Qualidade média:** 70-85 pontos

## ✅ Pronto para uso!

O comando está completo e testável. Execute com um arquivo pequeno primeiro para validar:

```bash
php artisan cesta:importar-tce-rs contratos --arquivo=/caminho/para/teste.csv
```

Depois processe o ZIP completo:

```bash
php artisan cesta:importar-tce-rs contratos --arquivo=/tmp/contratos-2025.zip
```
