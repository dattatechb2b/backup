# 🔌 APIs IMPLEMENTADAS - CESTA DE PREÇOS

Documentação completa de todas as APIs REST implementadas no módulo.

---

## 📋 ÍNDICE DE ENDPOINTS

### Autenticação (Públicas)
- `POST /login` - Login de usuário
- `POST /logout` - Logout de usuário

### Health Check (Públicas)
- `GET /health` - Verificar se módulo está online

### Orçamentos (Protegidas - Requer Autenticação)
- `GET /orcamentos/novo` - Formulário de criação
- `POST /orcamentos/novo` - Criar orçamento
- `GET /orcamentos/pendentes` - Listar pendentes
- `GET /orcamentos/realizados` - Listar realizados
- `GET /orcamentos/{id}` - Ver detalhes
- `GET /orcamentos/{id}/elaborar` - Página de elaboração
- `GET /orcamentos/{id}/editar` - Formulário de edição
- `PUT /orcamentos/{id}` - Atualizar orçamento
- `POST /orcamentos/{id}/marcar-realizado` - Marcar como realizado
- `POST /orcamentos/{id}/marcar-pendente` - Marcar como pendente
- `DELETE /orcamentos/{id}` - Excluir (soft delete)

### Itens do Orçamento (Protegidas - AJAX)
- `POST /orcamentos/{id}/itens` - Adicionar item
- `POST /orcamentos/{id}/lotes` - Criar lote
- `POST /orcamentos/{id}/importar-planilha` - Importar Excel/CSV

### Busca e Preview (Públicas - Iframe)
- `GET /orcamentos/buscar` - Buscar orçamentos (AJAX)
- `GET /orcamentos/{id}/preview` - Preview do orçamento (PDF/HTML)
- `GET /pncp/buscar` - Buscar preços no PNCP

### Concluir Orçamento (Protegida)
- `POST /orcamentos/{id}/concluir` - Finalizar e gerar documento

---

## 🔐 AUTENTICAÇÃO

### POST /login
**Descrição:** Login de usuário (não usado no proxy, mantido por compatibilidade)

**Método:** `POST`
**Arquivo:** `AuthController@login`
**Proteção:** Nenhuma (pública)

**Request:**
```json
{
  "email": "usuario@exemplo.com",
  "password": "senha123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "redirect": "/dashboard"
}
```

**Response (401):**
```json
{
  "success": false,
  "message": "Credenciais inválidas"
}
```

---

### POST /logout
**Descrição:** Logout do usuário

**Método:** `POST`
**Arquivo:** `AuthController@logout`
**Proteção:** Nenhuma (pública)

**Response (302):**
- Redirect para `/login`

---

## 🏥 HEALTH CHECK

### GET /health
**Descrição:** Verifica se o módulo está online (usado pelo proxy)

**Método:** `GET`
**Proteção:** Nenhuma (pública)

**Response (200):**
```json
{
  "status": "online",
  "module": "cestadeprecos",
  "version": "1.0.0",
  "timestamp": "2025-10-01T16:00:00.000000Z"
}
```

**Uso:**
- ModuleProxyController chama este endpoint para verificar disponibilidade
- Monitoramento de infraestrutura

---

## 📝 ORÇAMENTOS - CRUD

### GET /orcamentos/novo
**Descrição:** Exibir formulário de criação de orçamento

**Método:** `GET`
**Arquivo:** `OrcamentoController@create`
**Proteção:** `ensure.authenticated`
**View:** `orcamentos/create.blade.php`

**Response:** HTML da página

---

### POST /orcamentos/novo
**Descrição:** Criar novo orçamento

**Método:** `POST`
**Arquivo:** `OrcamentoController@store`
**Proteção:** `ensure.authenticated`
**CSRF:** Desabilitado temporariamente

**Request (Form Data - Tipo "do_zero"):**
```json
{
  "nome": "Orçamento de Materiais de Escritório 2025",
  "referencia_externa": "PROC-2025-001",
  "objeto": "Aquisição de materiais de escritório para o exercício de 2025",
  "orgao_interessado": "Secretaria de Administração",
  "tipo_criacao": "do_zero"
}
```

**Request (Form Data - Tipo "documento"):**
```json
{
  "nome": "Orçamento Importado",
  "referencia_externa": "IMP-2025-001",
  "objeto": "Importação de planilha Excel",
  "orgao_interessado": "Secretaria de Compras",
  "tipo_criacao": "documento",
  "documento": "[arquivo.xlsx]"
}
```

**Validações:**
- `nome`: obrigatório, máx 255 caracteres
- `objeto`: obrigatório
- `tipo_criacao`: obrigatório, valores: `do_zero`, `outro_orcamento`, `documento`
- `documento`: obrigatório se tipo = "documento", formatos: pdf|xlsx|xls, máx 10MB

**Response (302):**
- Redirect para `/orcamentos/{id}/elaborar?msg=success`
- Flash message: "Orçamento criado com sucesso! X itens foram extraídos." (se documento)

**Processamento Especial:**
- Se `tipo_criacao = "documento"`:
  - Processa Excel/PDF com `processarDocumento()`
  - Extrai itens automaticamente
  - Cria registros em `orcamento_itens`

---

### GET /orcamentos/pendentes
**Descrição:** Listar orçamentos pendentes

**Método:** `GET`
**Arquivo:** `OrcamentoController@pendentes`
**Proteção:** `ensure.authenticated`
**View:** `orcamentos/pendentes.blade.php`

**Response:** HTML com tabela paginada

**Dados Retornados:**
```php
$orcamentos = [
  {
    "id": 1,
    "nome": "Orçamento Exemplo",
    "status": "pendente",
    "created_at": "2025-10-01 10:00:00",
    "user": {
      "name": "João Silva"
    }
  }
]
```

---

### GET /orcamentos/realizados
**Descrição:** Listar orçamentos realizados/concluídos

**Método:** `GET`
**Arquivo:** `OrcamentoController@realizados`
**Proteção:** `ensure.authenticated`
**View:** `orcamentos/realizados.blade.php`

**Response:** HTML com tabela paginada

---

### GET /orcamentos/{id}
**Descrição:** Ver detalhes de um orçamento

**Método:** `GET`
**Arquivo:** `OrcamentoController@show`
**Proteção:** `ensure.authenticated`
**View:** `orcamentos/show.blade.php`

**Parâmetros:**
- `{id}`: ID do orçamento

**Response:** HTML com dados completos do orçamento

---

### GET /orcamentos/{id}/elaborar
**Descrição:** Página de elaboração do orçamento (5 seções)

**Método:** `GET`
**Arquivo:** `OrcamentoController@elaborar`
**Proteção:** `ensure.authenticated`
**View:** `orcamentos/elaborar.blade.php`

**Parâmetros:**
- `{id}`: ID do orçamento
- `?msg=success` (opcional): Exibe modal de sucesso

**Response:** HTML com 5 seções:
1. Dados Cadastrais
2. Metodologias e Padrões
3. Cadastramento de Itens
4. Coleta de Amostras
5. Gerar Orçamento Estimativo

---

### GET /orcamentos/{id}/editar
**Descrição:** Formulário de edição

**Método:** `GET`
**Arquivo:** `OrcamentoController@edit`
**Proteção:** `ensure.authenticated`
**View:** `orcamentos/edit.blade.php`

---

### PUT /orcamentos/{id}
**Descrição:** Atualizar orçamento (via AJAX ou form)

**Método:** `PUT` (via POST com _method=PUT)
**Arquivo:** `OrcamentoController@update`
**Proteção:** `ensure.authenticated`

**Request (JSON - AJAX):**
```json
{
  "nome": "Nome Atualizado",
  "referencia_externa": "REF-2025-002",
  "objeto": "Objeto atualizado",
  "orgao_interessado": "Órgão atualizado"
}
```

**Response (200 - AJAX):**
```json
{
  "success": true,
  "message": "Orçamento atualizado com sucesso!",
  "data": {
    "id": 1,
    "nome": "Nome Atualizado",
    ...
  }
}
```

**Response (302 - Form):**
- Redirect para `/orcamentos/{id}`
- Flash message: "Orçamento atualizado com sucesso!"

---

### POST /orcamentos/{id}/marcar-realizado
**Descrição:** Marcar orçamento como realizado

**Método:** `POST`
**Arquivo:** `OrcamentoController@marcarRealizado`
**Proteção:** `ensure.authenticated`

**Response (302):**
- Redirect para `/orcamentos/realizados`
- Flash message: "Orçamento marcado como realizado!"

**Efeito:**
- `status` = "realizado"
- `data_conclusao` = now()

---

### POST /orcamentos/{id}/marcar-pendente
**Descrição:** Marcar orçamento como pendente

**Método:** `POST`
**Arquivo:** `OrcamentoController@marcarPendente`
**Proteção:** `ensure.authenticated`

**Response (302):**
- Redirect para `/orcamentos/pendentes`
- Flash message: "Orçamento marcado como pendente!"

**Efeito:**
- `status` = "pendente"
- `data_conclusao` = null

---

### DELETE /orcamentos/{id}
**Descrição:** Excluir orçamento (soft delete)

**Método:** `DELETE` (via POST com _method=DELETE)
**Arquivo:** `OrcamentoController@destroy`
**Proteção:** `ensure.authenticated`

**Response (302):**
- Redirect back
- Flash message: "Orçamento excluído com sucesso!"

**Efeito:**
- Soft delete (campo `deleted_at` preenchido)
- Registro não removido fisicamente do banco

---

## 📦 ITENS DO ORÇAMENTO

### POST /orcamentos/{id}/itens
**Descrição:** Adicionar item ao orçamento (via modal AJAX)

**Método:** `POST`
**Arquivo:** `OrcamentoController@storeItem`
**Proteção:** `ensure.authenticated`
**CSRF:** Desabilitado temporariamente

**Request (Form Data):**
```json
{
  "descricao": "Caneta esferográfica azul",
  "medida_fornecimento": "Unidade",
  "quantidade": "100.0000",
  "indicacao_marca": "BIC (referência)",
  "tipo": "produto",
  "alterar_cdf": "0",
  "lote_id": null
}
```

**Validações:**
- `descricao`: obrigatório
- `medida_fornecimento`: obrigatório, máx 50 caracteres
- `quantidade`: obrigatório, numérico, min 0.0001
- `tipo`: obrigatório, valores: `produto` ou `servico`
- `alterar_cdf`: obrigatório, boolean

**Response (200):**
```json
{
  "success": true,
  "message": "Item adicionado com sucesso!",
  "item": {
    "id": 1,
    "orcamento_id": 1,
    "descricao": "Caneta esferográfica azul",
    "medida_fornecimento": "Unidade",
    "quantidade": 100.0000,
    "tipo": "produto",
    ...
  }
}
```

**Response (500):**
```json
{
  "success": false,
  "message": "Erro ao salvar item: [mensagem]"
}
```

---

### POST /orcamentos/{id}/lotes
**Descrição:** Criar lote para agrupar itens

**Método:** `POST`
**Arquivo:** `OrcamentoController@storeLote`
**Proteção:** `ensure.authenticated`

**Request (Form Data):**
```json
{
  "numero": "1",
  "nome": "Lote 01 - Materiais de Escritório"
}
```

**Validações:**
- `numero`: obrigatório, inteiro, min 1
- `nome`: obrigatório, máx 255 caracteres
- Não pode existir lote com mesmo número neste orçamento

**Response (200):**
```json
{
  "success": true,
  "message": "Lote criado com sucesso!",
  "lote": {
    "id": 1,
    "orcamento_id": 1,
    "numero": 1,
    "nome": "Lote 01 - Materiais de Escritório"
  }
}
```

**Response (422 - Duplicado):**
```json
{
  "success": false,
  "message": "Já existe um lote com este número neste orçamento."
}
```

---

### POST /orcamentos/{id}/importar-planilha
**Descrição:** Importar múltiplos itens de planilha Excel/CSV com detecção inteligente de colunas

**Método:** `POST`
**Arquivo:** `OrcamentoController@importPlanilha`
**Proteção:** `ensure.authenticated`
**Status:** ✅ **IMPLEMENTADO EM 01/10/2025**

**Request (Multipart Form Data):**
```
planilha: [arquivo.xlsx]
```

**Validações:**
- `planilha`: obrigatório, file, formatos: xlsx|xls|csv, máx 10MB

**Funcionalidades:**
- ✅ **Detecção automática de colunas por CABEÇALHO** (Descrição, Quantidade, Unidade, Marca, Tipo)
- ✅ **Detecção inteligente por CONTEÚDO** (sem necessidade de cabeçalho!)
- ✅ **Aceita qualquer formato de planilha** (com ou sem cabeçalho)
- ✅ **Importação parcial** (continua mesmo se algumas linhas tiverem erro)
- ✅ **Upload via proxy** funcionando com multipart/form-data

**Colunas Detectadas Automaticamente:**
- **Descrição:** textos longos (>20 chars) ou colunas: "descricao", "item", "nome", "produto"
- **Quantidade:** valores numéricos ou colunas: "quantidade", "qtd", "qtde"
- **Unidade:** valores como "UN", "KG", "RESMA" ou colunas: "unidade", "medida"
- **Marca:** textos curtos (<10 chars) ou colunas: "marca", "fabricante"
- **Tipo:** valores "produto"/"servico" ou colunas: "tipo", "categoria"

**Response (200 - Sucesso):**
```json
{
  "success": true,
  "message": "5 itens importados com sucesso! 1 linhas com erro.",
  "itens_importados": 5,
  "itens_com_erro": 1
}
```

**Response (500 - Erro):**
```json
{
  "success": false,
  "message": "Não foi possível identificar as colunas da planilha automaticamente..."
}
```

**Exemplo de Log (Sucesso):**
```
[16:32:59] ImportPlanilha: Header detectado {"header":["","","","","","",""]}
[16:32:59] ImportPlanilha: Cabeçalho não identificado, tentando detectar por conteúdo...
[16:32:59] ImportPlanilha: Detecção por conteúdo {
    "columnMap": {"quantidade": 1, "indicacao_marca": 2, "descricao": 5},
    "startRow": 0
}
[16:32:59] 5 itens importados, 1 linha com erro (Linha 8: Descrição vazia)
```

**Documentação Completa:** Ver `Arquivos_Claude/IMPLEMENTACAO_IMPORTACAO_PLANILHA.md`

---

## 🔍 BUSCA E PREVIEW

### GET /orcamentos/buscar
**Descrição:** Buscar orçamentos via AJAX (para aba "Criar a partir de outro")

**Método:** `GET`
**Arquivo:** `OrcamentoController@buscar`
**Proteção:** Nenhuma (pública - funciona em iframe)

**Query Params:**
- `nome` (opcional): Filtrar por nome (ILIKE)
- `referencia_externa` (opcional): Filtrar por referência (ILIKE)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nome": "Orçamento Exemplo",
      "referencia_externa": "REF-001",
      "total_itens": 15,
      "user": {
        "name": "João Silva"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25,
    "from": 1,
    "to": 10
  }
}
```

---

### GET /orcamentos/{id}/preview
**Descrição:** Visualizar preview do orçamento em HTML formatado

**Método:** `GET`
**Arquivo:** `OrcamentoController@preview`
**Proteção:** Nenhuma (pública - abre em nova janela)

**Response (200):**
- Content-Type: `text/html`
- HTML formatado para impressão/PDF
- Inclui:
  - Cabeçalho com dados cadastrais
  - Metodologias aplicadas
  - Tabela de itens
  - Totalizações
  - Observações/justificativas

---

### GET /pncp/buscar
**Descrição:** Buscar preços de referência no PNCP (Portal Nacional de Contratações Públicas)

**Método:** `GET`
**Arquivo:** `OrcamentoController@buscarPNCP`
**Proteção:** Nenhuma (pública - usado em AJAX)

**Query Params:**
- `termo` (obrigatório): Termo de busca (min 3 caracteres)

**Integração Externa:**
- API PNCP: `https://pncp.gov.br/api/consulta/v1/pca/atualizacao`
- Busca últimos 6 meses
- Agrega por descrição similar
- Calcula min/max/avg

**Response (200):**
```json
{
  "success": true,
  "resultados": [
    {
      "descricao": "CANETA ESFEROGRÁFICA AZUL",
      "unidade": "UNIDADE",
      "preco_minimo": 0.50,
      "preco_medio": 1.25,
      "preco_maximo": 2.00,
      "quantidade_amostras": 45,
      "exemplo_orgao": "Prefeitura de São Paulo"
    }
  ],
  "total_encontrado": 12
}
```

**Response (400 - Termo Curto):**
```json
{
  "success": false,
  "message": "Digite pelo menos 3 caracteres para buscar"
}
```

---

## 📄 CONCLUIR ORÇAMENTO

### POST /orcamentos/{id}/concluir
**Descrição:** Finalizar orçamento e marcar como realizado

**Método:** `POST`
**Arquivo:** `OrcamentoController@concluir`
**Proteção:** `ensure.authenticated`

**Request (Form Data):**
```
observacao_justificativa: "Justificativa geral..."
anexo_pdf: [arquivo.pdf] (opcional)
```

**Validações:**
- `observacao_justificativa`: opcional, string
- `anexo_pdf`: opcional, file, formato: pdf, máx 8MB

**Response (302):**
- Redirect para `/orcamentos/realizados`
- Flash message: "Orçamento concluído com sucesso!"

**Efeito:**
- Salva observação/justificativa
- Upload de PDF (se fornecido)
- Marca como realizado (status + data_conclusao)

---

## 📊 ESTATÍSTICAS DE USO

### APIs Mais Usadas:
1. ✅ `POST /orcamentos/novo` - Criar orçamento
2. ✅ `GET /orcamentos/{id}/elaborar` - Elaborar orçamento
3. ✅ `POST /orcamentos/{id}/itens` - Adicionar itens
4. ✅ `POST /orcamentos/{id}/importar-planilha` - Importar Excel (NOVO!)
5. ✅ `GET /pncp/buscar` - Buscar preços PNCP
6. ✅ `POST /orcamentos/{id}/concluir` - Finalizar

### APIs Implementadas mas Não Usadas:
- ⚠️ `POST /login` - Sistema usa autenticação via proxy

---

## 🔒 SEGURANÇA

### Autenticação:
- **Middleware:** `ensure.authenticated`
- **Método:** Stateless via headers (`X-User-*`, `X-Tenant-*`)
- **Fonte:** ModuleProxyController do sistema principal

### CSRF:
- **Status:** Desabilitado temporariamente para `orcamentos/*`
- **Motivo:** Regeneração de sessão causava erro 419
- **Futuro:** Re-habilitar quando sessão estabilizar

### Validações:
- ✅ Todos os endpoints validam dados de entrada
- ✅ Mensagens de erro personalizadas em PT-BR
- ✅ Sanitização automática do Laravel

### Autorização:
- ✅ Usuário só acessa orçamentos do seu tenant
- ✅ Database prefix dinâmico (`cp_`)
- ✅ Isolamento via tenant_id

---

## 📝 PRÓXIMAS APIs A IMPLEMENTAR

### Em Desenvolvimento:
- [ ] `PATCH /orcamentos/{id}/itens/{item_id}` - Editar item
- [ ] `DELETE /orcamentos/{id}/itens/{item_id}` - Excluir item
- [ ] `POST /orcamentos/{id}/cdf` - Solicitar cotação com fornecedores
- [ ] `POST /orcamentos/{id}/amostras` - Adicionar amostra de preço

### Planejadas:
- [ ] `GET /api/licitacoes` - Listar licitações
- [ ] `GET /api/fornecedores` - Listar fornecedores
- [ ] `POST /api/analise-precos` - Análise de preços

---

**Documentado em:** 01/10/2025 17:00 BRT
**Autor:** Claude Code
**Status:** ✅ Atualizado com todas as APIs ativas
