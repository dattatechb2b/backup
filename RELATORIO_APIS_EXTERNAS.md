# RELATÓRIO COMPLETO DE INTEGRAÇÕES COM APIs EXTERNAS
## Projeto: Cesta de Preços
### Data: 16/10/2025

---

## SUMÁRIO EXECUTIVO

O projeto **Cesta de Preços** integra múltiplas APIs externas para consolidar dados de preços, contratos e medicamentos. As principais fontes de dados externas são:

1. **PNCP** - Portal Nacional de Contratações Públicas
2. **ReceitaWS / BrasilAPI** - Consulta de CNPJ
3. **LicitaCon** - Tribunal de Contas do RS
4. **Compras.gov** - Portal de Compras do Governo Federal
5. **Portal da Transparência** - CGU (Controladoria-Geral da União)
6. **CMED** - Câmara de Regulação do Mercado de Medicamentos
7. **CATMAT** - Catálogo de Materiais do Compras.gov

---

## 1. SERVIÇOS IMPLEMENTADOS (app/Services/)

### 1.1 CnpjService
**Arquivo:** `/app/Services/CnpjService.php`
**Propósito:** Consulta de dados de CNPJ com fallback entre múltiplas fontes

**APIs Utilizadas (em ordem de prioridade):**
1. ReceitaWS: https://www.receitaws.com.br/v1/cnpj/{cnpj}
2. BrasilAPI: https://brasilapi.com.br/api/cnpj/v1/{cnpj}
3. CNPJ.ROCKS (Receita Federal): https://publica.cnpj.ws/cnpj/{cnpj}

**Características:**
- Validação de CNPJ (formato e dígitos verificadores)
- Cache de 15 minutos (CACHE_TTL = 900 segundos)
- Retry automático (2 tentativas com 1000ms de intervalo)
- Timeout de 10-15 segundos por requisição
- Tratamento de erros com logging

**Dados Retornados:**
```
{
  "success": true|false,
  "cnpj": "XX.XXX.XXX/XXXX-XX",
  "razao_social": "...",
  "nome_fantasia": "...",
  "email": "...",
  "telefone": "(XX) XXXXX-XXXX",
  "situacao": "Ativa",
  "uf": "SP",
  "municipio": "...",
  "fonte": "receitaws|brasilapi|receita_federal_oficial"
}
```

**Controle de Erros:**
- Validação antes da consulta
- Tratamento de exceções com logging
- Fallback automático entre fontes
- Mensagens de erro claras ao usuário

---

### 1.2 LicitaconService
**Arquivo:** `/app/Services/LicitaconService.php`
**Propósito:** Busca de itens em licitações do Tribunal de Contas do RS

**API Base URL:**
- https://dados.tce.rs.gov.br/dados/licitacon/licitacao/ano/{ano}.csv.zip

**Funcionamento:**
1. Baixa arquivo ZIP consolidado por ano
2. Extrai arquivos CSV (ITEM.csv e LICITACAO.csv)
3. Realiza busca local por termo de busca
4. Enriquece itens com dados das licitações
5. Retorna resultados padronizados

**Cache:**
- TTL: 24 horas (CACHE_TTL = 86400 segundos)
- Armazenamento: Redis/File (conforme config)
- Cache por tipo (ITEM.csv e LICITACAO.csv separados)

**Características Especiais:**
- Download inteligente com extração de ZIP
- Busca case-insensitive
- Parsing CSV com separador ";"
- Formatação de data flexível (YYYYMMDD ou DD/MM/YYYY)
- Linkagem com portal Licitacon Cidadão

**Dados Retornados (formatados):**
```
{
  "descricao": "DS_ITEM",
  "valor": "VL_UNITARIO_ITEM",
  "quantidade": "QT_ITEM",
  "unidade": "DS_UNIDADE",
  "orgao": "NM_ORGAO",
  "tipo_origem": "licitacon",
  "confiabilidade": "alta",
  "licitacon_numero_pregao": "NR_LICITACAO",
  "licitacon_vencedor": "NM_LICITANTE_VENCEDOR",
  "licitacon_fonte_url": "https://portal.tce.rs.gov.br/..."
}
```

---

## 2. CONTROLADORES E ENDPOINTS PRINCIPAIS

### 2.1 CnpjController
**Arquivo:** `/app/Http/Controllers/CnpjController.php`
**Endpoints:**
- POST `/api/cnpj/consultar` - Consultar CNPJ

**Rate Limiting:**
- 10 consultas por IP a cada 60 segundos
- Retorna erro 429 (Too Many Requests) quando excedido

**Autenticação:**
- Público (sem autenticação obrigatória)
- Validação por IP com RateLimiter

**Validação:**
- CNPJ: required, string, min:14, max:18

---

### 2.2 PesquisaRapidaController
**Arquivo:** `/app/Http/Controllers/PesquisaRapidaController.php`
**Endpoints:**
- GET `/pesquisa/buscar?termo={termo}` - Busca multi-fonte
- POST `/pesquisa-rapida/criar-orcamento` - Criar orçamento com itens

**Fontes de Dados (ordem de prioridade):**

#### 2.2.1 Banco Local
- Tabela: `contratos_pncp`
- Query: ILIKE com termo normalizado
- Período: últimos 12 meses

#### 2.2.2 API PNCP - Busca Textual
**URL:** https://pncp.gov.br/api/search/
**Parâmetros:**
```
GET /api/search/?q=termo&tipos_documento=contrato&pagina=1&tamanhoPagina=10
```
**Tipos de Documento:**
- `contrato` - Contratos assinados
- `edital` - Licitações publicadas
- `ata_registro_preco` - Atas de Registro de Preço

**Paginação:** 5 páginas x 10 itens = 50 máximo
**Características:**
- Busca por palavra-chave em índice elasticsearch
- Delay de 1 segundo entre páginas
- Confiabilidade: ALTA

#### 2.2.3 LicitaCon (TCE-RS)
**URL:** https://dados.tce.rs.gov.br/api/3/action/package_search
**Parâmetros:**
```
GET /api/3/action/package_search?q=termo&rows=1000
```
**Formato:** CKAN (Catalog of Data)
**Características:**
- Busca em tempo real (SEM cache local)
- Retorna datasets/pacotes
- Timeout: 10 segundos
- Confiabilidade: MÉDIA

#### 2.2.4 Compras.gov
**URL:** https://dadosabertos.compras.gov.br/modulo-contratos/2_consultarContratosItem
**Parâmetros:**
```
GET /modulo-contratos/2_consultarContratosItem
  ?dataVigenciaInicialMin=YYYY-MM-DD
  &dataVigenciaInicialMax=YYYY-MM-DD
  &pagina=1
  &tamanhoPagina=500
```
**Período:** últimos 12 meses
**Busca:** Filtro local por termo em descricaoItem
**Paginação:** até 5 páginas
**Características:**
- Timeout: 30 segundos
- Sem autenticação necessária
- Filtro local (TODAS as palavras devem estar presentes)
- Confiabilidade: MÉDIA

#### 2.2.5 Portal da Transparência (CGU)
**URL Base:** https://api.portaldatransparencia.gov.br/api-de-dados
**Endpoints:**
- `/contratos` - Listar contratos
- `/contratos/itens-contratados` - Itens de um contrato

**Autenticação:**
- Header: `chave-api-dados: 319215bff3b6753f5e1e4105c58a55e9`

**Status:** TEMPORARIAMENTE DESABILITADO
- Motivo: Endpoint /contratos exige `codigoOrgao` (obrigatório)
- Plano futuro: Implementar busca por notas fiscais

**Timeout:** 10 segundos
**Confiabilidade:** ALTA (quando funcional)

---

### 2.3 MapaAtasController
**Arquivo:** `/app/Http/Controllers/MapaAtasController.php`
**Endpoints:**
- GET `/mapa-de-atas/buscar` - Buscar ARPs (Atas de Registro de Preço)
- GET `/api/mapa-atas/buscar-arps` - API de ARPs
- GET `/api/mapa-atas/itens/{ataId}` - Itens de uma ARP

**API PNCP Utilizada:**
- Base: https://pncp.gov.br/api/consulta
- Endpoints:
  - `/v1/contratos` - Contratos normais
  - `/v1/atas-registro-precos` - ARPs

**Filtros Avançados:**
- Descrição do objeto
- UASG (Unidade Administrativa)
- CNPJ do órgão
- Data inicial/final (formato YYYYMMDD)
- UF
- Município
- Valor mínimo/máximo
- Período (30, 90, 365 dias)

**Timeout:** 30 segundos
**Tamanho da Página:** até 500 itens

---

### 2.4 CatalogoController
**Arquivo:** `/app/Http/Controllers/CatalogoController.php`
**Endpoints (API):**
- GET `/api/catalogo` - Listar produtos
- GET `/api/catalogo/{id}` - Detalhes do produto
- POST `/api/catalogo` - Criar produto
- PUT `/api/catalogo/{id}` - Atualizar produto
- DELETE `/api/catalogo/{id}` - Desativar produto
- GET `/api/catalogo/{id}/referencias-preco` - Referências de preço
- POST `/api/catalogo/{id}/adicionar-preco` - Adicionar preço

**Fontes de Dados para Referências:**
- ARPs (Atas de Registro de Preço)
- Contratos PNCP
- Histórico local de preços

---

### 2.5 FornecedorController
**Arquivo:** `/app/Http/Controllers/FornecedorController.php`
**Endpoints:**
- GET `/fornecedores/{id}` - Detalhes
- GET `/fornecedores/consultar-cnpj/{cnpj}` - Consulta CNPJ
- GET `/api/fornecedores/buscar-pncp` - Buscar no PNCP
- GET `/api/fornecedores/buscar-por-produto` - Buscar por produto
- POST `/api/fornecedores/atualizar-pncp` - Atualizar dados PNCP

**APIs Utilizadas:**
1. ReceitaWS: https://www.receitaws.com.br/v1/cnpj/{cnpj}
2. BrasilAPI: https://brasilapi.com.br/api/cnpj/v1/{cnpj}
3. PNCP: https://pncp.gov.br/api/consulta/v1/contratos
4. Compras.gov: https://dadosabertos.compras.gov.br/modulo-material/

---

## 3. COMANDOS DE SINCRONIZAÇÃO (app/Console/Commands/)

### 3.1 SincronizarPNCP
**Arquivo:** `/app/Console/Commands/SincronizarPNCP.php`
**Comando:** `php artisan pncp:sincronizar {--meses=6} {--paginas=50}`

**Funcionalidade:**
- Sincroniza contratos do PNCP para banco local
- Permite busca por qualquer palavra no banco

**API Utilizada:**
- https://pncp.gov.br/api/consulta/v1/contratos

**Parâmetros:**
- `--meses`: Meses atrás para sincronizar (padrão: 6)
- `--paginas`: Número máximo de páginas (padrão: 50)

**Dados Sincronizados:**
- numero_controle_pncp
- tipo (contrato)
- objeto_contrato
- valor_global
- número_parcelas
- valor_unitario_estimado
- orgao (CNPJ, razão social, UF)
- fornecedor (CNPJ, razão social)
- datas (publicação, vigência)
- confiabilidade

**Delay:** 100ms entre requisições (não sobrecarregar API)

---

### 3.2 ImportarCmed
**Arquivo:** `/app/Console/Commands/ImportarCmed.php`
**Comando:** `php artisan cmed:import {arquivo?} {--mes=} {--limpar} {--teste=0}`

**Funcionalidade:**
- Importa medicamentos da Tabela CMED em Excel
- Câmara de Regulação do Mercado de Medicamentos

**Fonte:**
- Arquivo Excel local (CMED Outubro 25 - Modificada.xlsx)
- Tabela com 74 colunas (preços, ICMS, laboratorio, etc)

**Mapeamento de Dados:**
- Substância, Laboratório, CNPJ
- Códigos (GGREM, EAN, Registro)
- Preços PF (Preço Fábrica) - 23 variações com ICMS
- Preços PMC (Preço Máximo ao Consumidor) - 23 variações
- Dados tributários e regulatórios

**Características:**
- Inserção em chunks de 1000 registros
- Validação de linhas
- Parsing de preços (decimal com vírgula/ponto)
- Parsing de booleanos (SIM/NÃO)
- Estatísticas finais

**Mês de Referência:** Extraído do nome do arquivo

---

### 3.3 BaixarCatmat
**Arquivo:** `/app/Console/Commands/BaixarCatmat.php`
**Propósito:** Sincronizar catálogo de materiais

**API:** https://dadosabertos.compras.gov.br/modulo-material/4_consultarItemMaterial

---

### 3.4 LicitaconSincronizar
**Arquivo:** `/app/Console/Commands/LicitaconSincronizar.php`
**Propósito:** Sincronizar dados do LicitaCon (TCE-RS)

**API:** https://dados.tce.rs.gov.br/api/3/action/package_show

---

### 3.5 PopularFornecedoresPNCP
**Propósito:** Popular fornecedores a partir de contratos PNCP

**APIs Utilizadas:**
- PNCP (contratos)
- ReceitaWS (complementar dados CNPJ)

---

### 3.6 AtualizarFornecedoresContratos
**Propósito:** Atualizar relação de fornecedores com contratos

**API Utilizada:**
- https://pncp.gov.br/api/pncp/v1/orgaos/{cnpj}/contratos/{ano}/{sequencial}/itens

---

## 4. MODELOS DE DADOS

### 4.1 ContratoPNCP
**Tabela:** `contratos_pncp`
**Arquivo:** `/app/Models/ContratoPNCP.php`

**Campos Principais:**
- numero_controle_pncp (PK)
- tipo (contrato)
- objeto_contrato (texto até 5000 chars)
- valor_global, numero_parcelas
- unidade_medida
- orgao_cnpj, orgao_razao_social, orgao_uf
- fornecedor_cnpj, fornecedor_razao_social, fornecedor_id
- data_publicacao_pncp, data_vigencia_inicio, data_vigencia_fim
- confiabilidade (baixa|media|alta)
- valor_estimado (boolean)
- sincronizado_em (timestamp)

**Métodos de Busca:**
- `buscarPorTermo($termo, $mesesAtras, $limite)` - Full-text com ILIKE
- `buscarSimples($termo, $mesesAtras, $limite)` - ILIKE simples

**Casting:**
- Datas como `date`
- Valores monetários como `decimal:2`
- Booleanos como `boolean`

---

### 4.2 MedicamentoCmed
**Tabela:** `medicamentos_cmed`
**Arquivo:** `/app/Models/MedicamentoCmed.php`

**Campos Principais:**
- substancia, cnpj_laboratorio, laboratorio
- codigo_ggrem, registro, EANs
- produto, apresentacao, classe_terapeutica
- tipo_produto, regime_preco
- pf_* (23 variações de Preço Fábrica)
- pmc_* (23 variações de Preço Máximo ao Consumidor)
- restricao_hospitalar, cap, confaz, icms_0
- mes_referencia, data_importacao

**Casting:**
- Booleanos
- Decimais (preços como `decimal:2`)

**Métodos:**
- `buscar($termo)` - Busca por substância, produto ou EAN
- `getPrecoAttribute()` - Retorna preço padrão (PMC com ICMS 0%)

---

### 4.3 ConsultaPncpCache
**Tabela:** `consultas_pncp_cache`
**Propósito:** Cache de consultas ao PNCP

---

## 5. CACHE IMPLEMENTADO

### 5.1 Cache em Serviços

**CnpjService:**
- **Chave:** `cnpj:{cnpj_limpo}`
- **TTL:** 15 minutos (900 segundos)
- **Driver:** Redis/File (conforme config)

**LicitaconService:**
- **Chave:** `licitacon_csv_{ano}_{tipo}`
- **TTL:** 24 horas (86400 segundos)
- **Tipo:** Cache de CSV baixado

### 5.2 Database Cache
- **Tabela:** `consultas_pncp_cache`
- **Propósito:** Cache de resultados de consultas ao PNCP

---

## 6. TRATAMENTO DE ERROS

### Estratégia Geral:
1. **Validação de entrada** - Antes de chamar APIs
2. **Retry automático** - Em caso de timeout/conexão
3. **Fallback** - Tentar fonte alternativa
4. **Logging** - Todos os erros são registrados
5. **Mensagens ao usuário** - Claras e apropriadas

### Por Serviço:

**CnpjService:**
```
try {
  API1 (ReceitaWS)
  if fail → API2 (BrasilAPI)
  if fail → API3 (CNPJ.ROCKS)
  if fail → erro com mensagem clara
} catch (Exception) → log + retornar success: false
```

**PesquisaRapidaController:**
```
try-catch em cada fonte
Continua mesmo se uma fonte falhar
Retorna erros parciais com dados obtidos
```

**Timeouts:**
- CNPJ: 10-15 segundos
- PNCP: 20-30 segundos
- Compras.gov: 30 segundos
- LicitaCon: 10 segundos

---

## 7. AUTENTICAÇÃO E SEGURANÇA

### APIs Públicas (SEM autenticação):
- PNCP (https://pncp.gov.br/api/)
- BrasilAPI
- ReceitaWS
- Compras.gov
- LicitaCon (TCE-RS)

### APIs COM autenticação:
- **Portal da Transparência (CGU)**
  - Método: Header de API Key
  - Header: `chave-api-dados`
  - Chave padrão: `319215bff3b6753f5e1e4105c58a55e9`
  - Status: Temporariamente desabilitado

### Rate Limiting Local:
- **CnpjController**: 10 consultas/60 segundos por IP
- **PesquisaRapidaController**: Sem limite específico

### HTTPS/TLS:
- Todas as APIs utilizam HTTPS
- Certificados SSL validados

---

## 8. HEADERS HTTP CUSTOMIZADOS

**Envios padrão:**
```
User-Agent: DattaTech-PNCP/1.0 ou Mozilla/5.0 (...)
Accept: application/json
Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8
Accept-Encoding: gzip, deflate, br
Connection: keep-alive
```

---

## 9. LIMITAÇÕES E PONTOS DE ATENÇÃO

### LicitaCon (TCE-RS):
- ⚠ Dados consolidados em arquivo ZIP anual
- ⚠ Atualização não é em tempo real (dia seguinte)
- ⚠ Tamanho variável (dezenas de MB)

### Compras.gov:
- ⚠ Filtro de descrição deve contêm TODAS as palavras
- ⚠ Página máx 500 itens
- ⚠ Busca localizada após download

### Portal da Transparência:
- ⚠ Endpoint /contratos exige CNPJ do órgão (obrigatório)
- ⚠ Implementação incompleta no código atual
- ⚠ Plano futuro: usar endpoint /notas-fiscais

### PNCP:
- ⚠ API /search é mais rápida mas menos documentada
- ⚠ Paginação máx ~50 resultados por busca
- ⚠ Delay de 1-2 segundos recomendado entre requisições

### Consulta CNPJ:
- ⚠ ReceitaWS frequentemente lento ou indisponível
- ⚠ BrasilAPI é fallback mais confiável
- ⚠ CNPJ.ROCKS é o last resort

---

## 10. CONFIGURAÇÕES IMPORTANTES (.env)

```env
# PNCP
PNCP_PAGE_SIZE_RAPIDA=100
PNCP_PAGINAS_RAPIDA=3

# Portal da Transparência
PORTALTRANSPARENCIA_API_KEY=319215bff3b6753f5e1e4105c58a55e9

# Cache
CACHE_DRIVER=redis (ou file)
CACHE_TTL=900 (padrão 15 minutos)
```

---

## 11. ENDPOINTS PÚBLICOS (SEM AUTENTICAÇÃO)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/health` | Health check do módulo |
| GET | `/pesquisa/buscar?termo=X` | Busca multi-fonte (pública) |
| POST | `/api/cnpj/consultar` | Consulta CNPJ |
| GET | `/responder-cdf/{token}` | Resposta CDF por fornecedor |
| POST | `/api/cdf/responder` | Salvar resposta CDF |
| GET | `/api/cdf/consultar-cnpj/{cnpj}` | Consultar CNPJ (CDF) |

---

## 12. ENDPOINTS PROTEGIDOS (COM AUTENTICAÇÃO)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/pesquisa-rapida` | Página de Pesquisa Rápida |
| POST | `/pesquisa-rapida/criar-orcamento` | Criar orçamento |
| GET | `/mapa-de-atas` | Página de ARPs |
| GET | `/mapa-de-atas/buscar` | Buscar ARPs |
| GET | `/catalogo` | Catálogo de produtos |
| GET | `/fornecedores` | Lista de fornecedores |

---

## 13. VOLUME E PERFORMANCE

### Dados Sincronizados:
- **Contratos PNCP**: ~100.000+ registros (últimos 6-12 meses)
- **Medicamentos CMED**: ~40.000+ registros
- **Catmat**: ~200.000+ registros
- **LicitaCon**: Variável (dezenas de mil)

### Performance típica:
- Consulta CNPJ: 2-5 segundos
- Busca Pesquisa Rápida: 10-30 segundos (multi-fonte)
- Busca no PNCP local: <1 segundo
- Consulta LicitaCon: 3-5 segundos

---

## 14. MONITORAMENTO E LOGS

**Arquivo de Log:** `storage/logs/laravel.log`

**Eventos Registrados:**
- Todas as requisições HTTP a APIs externas
- Cache hits/misses
- Erros de conexão
- Timeouts
- Validações falhadas
- Sincronizações iniciadas/concluídas

**Exemplo de Log:**
```
[2025-10-16 10:30:15] laravel.INFO: [CnpjService] Consultando ReceitaWS para 12345678000190
[2025-10-16 10:30:18] laravel.INFO: CNPJ consultado com sucesso: 12345678000190 (fonte: receitaws)
[2025-10-16 10:30:20] laravel.INFO: 📋 Pesquisa Rápida: Termo 'papel A4' retornou 250 resultados de 4 fontes
```

---

## 15. MANUTENÇÃO E TROUBLESHOOTING

### Problema: Pesquisa Rápida muito lenta
**Solução:**
1. Verificar cache Redis está rodando
2. Rodar `php artisan pncp:sincronizar` para popular cache local
3. Aumentar timeout em `.env`

### Problema: CNPJ não encontrado
**Solução:**
1. Verificar formato (com ou sem máscara)
2. Tentar em https://brasilapi.com.br (fallback)
3. Consultar em https://www.receitaws.com.br/v1/cnpj/{cnpj}

### Problema: API PNCP retorna erro 500
**Solução:**
1. Aguardar (serviço pode estar em manutenção)
2. Usar dados do cache local (banco `contratos_pncp`)
3. Reporte para suporte PNCP

---

## CONCLUSÃO

O sistema de Cesta de Preços possui **integração robusta** com 7 fontes de dados externas, com:
- ✅ Cache inteligente (Redis/File)
- ✅ Retry automático e fallback
- ✅ Tratamento de erros completo
- ✅ Rate limiting local
- ✅ Logging detalhado
- ✅ Validação de entrada
- ✅ HTTPS/TLS para todas as conexões

**Recomendações:**
1. Manter cache sincronizado (rodar cronjobs diários)
2. Monitorar logs em `storage/logs/laravel.log`
3. Testar fallbacks regularmente
4. Documentar mudanças em APIs externas

