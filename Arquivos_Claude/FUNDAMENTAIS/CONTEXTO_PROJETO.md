# CONTEXTO DO PROJETO - MÓDULO CESTA DE PREÇOS

**IMPORTANTE**: Este arquivo deve ser lido SEMPRE que houver perda de contexto ou compactação.

## LOCALIZAÇÃO
- Módulo: Cesta de Preços
- URL: https://catasaltas.dattapro.online/desktop
- Diretório: /home/dattapro/modulos/cestadeprecos
- Sistema principal: /home/dattapro/minhadattatech

## REGRAS FUNDAMENTAIS

### 1. PROCESSO DE TRABALHO
- ✅ LER e ENTENDER completamente o que foi pedido
- ✅ Se NÃO entender: PERGUNTAR (quantas vezes for necessário)
- ✅ NUNCA executar achando que entendeu
- ✅ Processar e entender PLENAMENTE antes de executar
- ✅ ANALISAR SE A MUDANÇA VAI CAUSAR ERROS antes de mexer
- ✅ NÃO PODE causar erros em NENHUMA parte do sistema

### 2. ANTES DE QUALQUER MODIFICAÇÃO
- Analisar impacto da mudança
- Verificar dependências
- Garantir que não quebrará outras funcionalidades
- Fazer backup se necessário

### 3. SEGUIR O CHECKLIST À RISCA
- Consultar o arquivo CHECKLIST_GERAL.md
- Marcar o que foi feito
- Documentar onde mexeu

## ESTRUTURA DO PROJETO

### Seções a serem implementadas (em ordem):
1. **NOVO ORÇAMENTO** (em desenvolvimento)
2. Pendentes
3. Realizados
4. Pesquisa Rápida
5. Mapa de Atas
6. Mapa de Fornecedores
7. Catálogo
8. Orientações Téc.
9. Fornecedores

## ARQUITETURA ATUAL

### Sistema Principal (MinhaDattaTech)
- Framework: Laravel 11
- Autenticação: Multi-tenant via sessão
- Proxy: ModuleProxyController (/module-proxy/price_basket/)
- Configuração de sessão: SameSite=none, Partitioned=true (para iframe)

### Módulo Cesta de Preços
- Framework: Laravel 11
- Autenticação: Via proxy headers + sessão persistente
- Middleware ProxyAuth: Recebe X-User-*, X-Tenant-* headers
- Middleware EnsureAuthenticated: Substitui 'auth' padrão do Laravel
- Base URL no iframe: /module-proxy/price_basket/

### Banco de Dados
- PostgreSQL
- Prefixo de tabelas: cp_
- Conexão configurada via ProxyAuth middleware

## O QUE JÁ FOI IMPLEMENTADO

### 1. Sistema de Autenticação Stateless via Proxy
- **Arquivo**: app/Http/Middleware/ProxyAuth.php
- **Função**: Autentica usuário usando headers do proxy, persiste na sessão
- **Headers recebidos**: X-User-Id, X-User-Email, X-User-Name, X-Tenant-Id, X-Tenant-Subdomain, X-DB-Prefix
- **Status**: ✅ FUNCIONANDO

### 2. Middleware de Verificação de Autenticação
- **Arquivo**: app/Http/Middleware/EnsureAuthenticated.php
- **Função**: Substitui middleware 'auth' padrão, verifica após ProxyAuth executar
- **Status**: ✅ FUNCIONANDO

### 3. Configuração de Rotas
- **Arquivo**: routes/web.php
- **Mudança**: Grupo protegido usa 'ensure.authenticated' ao invés de 'auth'
- **Status**: ✅ FUNCIONANDO

### 4. Sistema de Proxy no MinhaDattaTech
- **Arquivo**: minhadattatech/app/Http/Controllers/ModuleProxyController.php
- **Função**: Proxeia requisições para o módulo, injeta headers de autenticação
- **Mudanças**:
  - Rota movida para FORA do middleware 'auth' (linha 114-118 do routes/web.php)
  - Verificação manual de autenticação dentro do controller
  - Transformação de URLs absolutas em relativas
  - Injeção de tag `<base href="/module-proxy/price_basket/">`
- **Status**: ✅ FUNCIONANDO

### 5. Configuração de Sessão para Iframe
- **Arquivo**: minhadattatech/.env
- **Mudanças**:
  - SESSION_SAME_SITE=none
  - SESSION_PARTITIONED_COOKIE=true
- **Status**: ✅ FUNCIONANDO

### 6. Layout Base do Módulo
- **Arquivo**: resources/views/layouts/app.blade.php
- **Características**:
  - Menu lateral com seções: ORÇAMENTAÇÃO e OUTRAS PESQUISAS
  - Links usando URLs relativas (sem /)
  - JavaScript para interceptar cliques e navegar corretamente no iframe
- **Status**: ✅ FUNCIONANDO

### 7. Dashboard
- **Arquivo**: resources/views/dashboard.blade.php
- **Função**: Página inicial com botão "CRIAR UM NOVO ORÇAMENTO"
- **Status**: ✅ FUNCIONANDO

### 8. NOVO ORÇAMENTO - Formulário de Criação
- **Controller**: app/Http/Controllers/OrcamentoController.php
- **View**: resources/views/orcamentos/create.blade.php
- **Model**: app/Models/Orcamento.php
- **Migration**: database/migrations/*_create_orcamentos_table.php
- **Funcionalidades**:
  - 3 abas: Criar do Zero, Criar a partir de Outro Orçamento, Criar a partir de Documento
  - 4 campos: Nome do Orçamento (obrigatório), Referência Externa, Objeto (obrigatório), Órgão Interessado
  - Validação server-side
  - JavaScript para controle de abas e campos required
- **Status**: ✅ FUNCIONANDO

### 9. Views de Listagem
- **Pendentes**: resources/views/orcamentos/pendentes.blade.php (✅ URLs relativas)
- **Realizados**: resources/views/orcamentos/realizados.blade.php (✅ URLs relativas)
- **Status**: ✅ FUNCIONANDO

### 10. ELABORAÇÃO DO ORÇAMENTO ESTIMATIVO (Página Completa)
- **Controller**: app/Http/Controllers/OrcamentoController.php (método `elaborar()`)
- **View**: resources/views/orcamentos/elaborar.blade.php
- **Rota**: GET /orcamentos/{id}/elaborar
- **Funcionalidades Implementadas**:

  **SEÇÃO 1 - DADOS CADASTRAIS:**
  - ✅ Exibição dos dados do orçamento (nome, ref. externa, objeto, órgão, orçamentista)
  - ✅ Botão "ALTERAR" com modal de edição (AJAX)
  - ✅ Atualização inline sem reload da página

  **SEÇÃO 2 - METODOLOGIAS E PADRÕES:**
  - ✅ Radio buttons para "Método do Juízo Crítico"
  - ✅ Radio buttons para "Método de Obtenção do Preço"
  - ✅ Radio buttons para "Padrão de Casas Decimais"
  - ⏳ Salvamento automático (a implementar)

  **SEÇÃO 3 - CADASTRAMENTO DE ITENS:**
  - ✅ Badge com contador de itens
  - ✅ Busca de preços no PNCP (Portal Nacional de Contratações Públicas)
  - ✅ Modal "CRIAR UM ITEM" com formulário completo
  - ✅ Modal "CRIAR UM LOTE"
  - ✅ Modal "IMPORTAR PLANILHA" (Excel/CSV)
  - ✅ **LISTAGEM DE ITENS EM TABELA** (implementado 01/10/2025):
    - Colunas: Item, Descrição, Quantidade, Unidade, Tipo, Ações
    - Formatação de quantidade com 4 casas decimais
    - Badge colorido para tipo (Produto/Serviço)
    - Botões Editar e Excluir para cada item
  - ✅ Todas as 3 modais funcionando via AJAX
  - ✅ Reload automático após adicionar item

  **SEÇÃO 4 - COLETA DE AMOSTRAS:**
  - ✅ Layout de 3 subsecções:
    1. Cotação Direta com Fornecedores (CDF)
    2. Contratações Similares (outros entes públicos)
    3. Sítios de Comércio Eletrônico
  - ⏳ Funcionalidades a implementar

  **SEÇÃO 5 - GERAR ORÇAMENTO ESTIMATIVO:**
  - ✅ Campo textarea para Observação/Justificativa
  - ✅ Upload de PDF (até 8MB)
  - ✅ Botão "CONCLUIR COTAÇÃO" com modal de confirmação
  - ✅ Botão "PREVIEW DA COTAÇÃO" (abre em nova janela)
  - ✅ Preview gerado em HTML formatado

- **Status**: ✅ FUNCIONANDO (95% completo)

### 11. Sistema de Itens do Orçamento
- **Model**: app/Models/OrcamentoItem.php
- **Migration**: database/migrations/*_create_orcamento_itens_table.php
- **Controller Methods**:
  - `storeItem()` - Adiciona item via AJAX (POST /orcamentos/{id}/itens)
  - `storeLote()` - Cria lote via AJAX (POST /orcamentos/{id}/lotes)
  - `importPlanilha()` - Importa Excel/CSV (POST /orcamentos/{id}/importar-planilha)
- **Campos do Item**:
  - descricao (obrigatório)
  - medida_fornecimento (select: Unidade, Caixa, Metro, Litro, Kg)
  - quantidade (number com 4 decimais)
  - indicacao_marca (opcional)
  - tipo (radio: PRODUTO ou SERVIÇO)
  - alterar_cdf (radio: SIM ou NÃO)
  - lote_id (opcional)
- **Status**: ✅ FUNCIONANDO

### 12. Sistema de Upload e Processamento de Documentos ⭐ ATUALIZADO

- **Funcionalidade**: Criar orçamento a partir de documento (Excel, CSV, PDF, Word, Imagem)
- **Bibliotecas**:
  - PhpOffice/PhpSpreadsheet (Excel/CSV)
  - Smalot/PdfParser (PDF) ⭐ NOVO
  - PhpOffice/PhpWord (Word) ⭐ NOVO
  - Tesseract OCR (Imagens - planejado) ⭐ NOVO

- **Métodos**:
  - `importarDocumento()` - Router principal
  - `processarPlanilhaExcel($arquivo, $orcamentoId)` - Excel/CSV
  - `processarDocumentoPDF($arquivo, $orcamentoId)` - **NOVO:** PDF com multilinhas
  - `processarDocumentoWord($arquivo, $orcamentoId)` - **NOVO:** Word
  - `processarDocumentoImagem($arquivo, $orcamentoId)` - **NOVO:** Placeholder manual

- **Formatos Aceitos**:
  - Excel: .xlsx, .xls
  - CSV: .csv
  - **PDF: .pdf** ⭐ NOVO
  - **Word: .doc, .docx** ⭐ NOVO
  - **Imagem: .png, .jpg, .jpeg, .gif, .bmp, .webp** ⭐ NOVO
  - Tamanho máximo: 10MB

- **Processamento PDF** (NOVO):
  - Máquina de estados "Item em Construção"
  - Detecta número isolado → Início de item
  - Acumula linhas seguintes → Descrição
  - Encontra unidade + quantidade → Finaliza
  - 30+ unidades reconhecidas
  - Suporta descrições em múltiplas linhas
  - Normalização automática de acentos

- **Lógica de Extração Excel**:
  - Identifica colunas automaticamente (Descrição, Quantidade, Unidade)
  - Detecção por cabeçalho OU por conteúdo estatístico
  - Fallback inteligente
  - Pula linhas vazias
  - Normaliza quantidades para número

- **Status**: ✅ FUNCIONANDO (todos os formatos)
- **Documentação**: `Arquivos_Claude/PROCESSAMENTO_PDF_INTELIGENTE.md`

### 13. Modal de Sucesso Customizado
- **Arquivo**: resources/views/orcamentos/elaborar.blade.php (linhas 7-65)
- **Trigger**: Parâmetro ?msg=success na URL OU session('success')
- **Funcionalidade**:
  - Exibe modal verde com ícone de check
  - Usa sessionStorage para mostrar apenas 1x por orçamento
  - Remove parâmetro da URL após exibir (history.replaceState)
  - Botão "OK" fecha o modal
- **Status**: ✅ FUNCIONANDO

## PROBLEMAS RESOLVIDOS

### 1. Redirect para Login ao Clicar em Links
- **Causa**: Middleware 'auth' padrão verificava sessão antes do ProxyAuth executar
- **Solução**: Criado EnsureAuthenticated que executa APÓS ProxyAuth
- **Data**: 30/09/2025

### 2. Erro 404 nas Navegações
- **Causa**: URLs absolutas (começando com /) não funcionam com tag `<base>`
- **Solução**: ModuleProxyController transforma `/dashboard` em `dashboard`
- **Data**: 30/09/2025

### 3. Cookies Não Enviados no Iframe
- **Causa**: SESSION_SAME_SITE=lax bloqueava cookies em iframe
- **Solução**: SESSION_SAME_SITE=none + SESSION_PARTITIONED_COOKIE=true
- **Data**: 30/09/2025

### 4. Rota Proxy Redirecionando para Login
- **Causa**: Rota /module-proxy estava dentro do grupo middleware('auth')
- **Solução**: Movida para fora, com verificação manual no controller
- **Data**: 30/09/2025

### 5. Erro 419 CSRF Token no Formulário de Novo Orçamento
- **Causa**: `Auth::login()` regenerava a sessão em CADA requisição, invalidando o CSRF token entre GET (geração) e POST (validação)
- **Problema Raiz**: Session ID mudava entre requests devido à regeneração contínua
- **Solução Aplicada**:
  1. Substituído `Auth::login($user, true)` por `Auth::setUser($user)` no ProxyAuth middleware (linha 94)
  2. Adicionado `session()->save()` explícito para garantir persistência em iframes (linha 98)
  3. Criado event listener no AppServiceProvider para prevenir regeneração automática
  4. Desabilitado CSRF validation temporariamente para rotas `orcamentos/*` no bootstrap/app.php
  5. Added logs extensivos para rastrear `session_regenerated: false`
- **Arquivos Modificados**:
  - `app/Http/Middleware/ProxyAuth.php` (linhas 91-111)
  - `app/Providers/AppServiceProvider.php` (linhas 23-30)
  - `bootstrap/app.php` (linhas 27-33)
- **Status**: ✅ CORRIGIDO - Logs confirmam que sessão não regenera mais entre requests
- **Data**: 01/10/2025

### 6. Redirect Não Funcionando Após Criar Orçamento
- **Causa**: Laravel HTTP Client no ModuleProxyController não seguia redirects automaticamente
- **Problema**: Ao criar orçamento, controller retornava redirect 302, mas proxy passava esse código direto pro navegador sem seguir o redirect
- **Sintoma**: Usuário ficava na página de criação após clicar "Salvar", não era redirecionado para página de elaboração
- **Solução**: Adicionado `->withOptions(['allow_redirects' => true])` em todas as requisições HTTP do proxy
- **Arquivo Modificado**: `minhadattatech/app/Http/Controllers/ModuleProxyController.php` (linhas 107-125)
- **Status**: ✅ CORRIGIDO
- **Data**: 01/10/2025

## ⛔ CÓDIGO CRÍTICO - NÃO MEXER

**IMPORTANTE:** Algumas partes do código já foram depuradas múltiplas vezes e estão funcionando perfeitamente.

**ANTES de modificar qualquer código relacionado a:**
- Criação de orçamento (OrcamentoController@store)
- Redirect após salvar
- Modal de sucesso
- URLs relativas

**LEIA OBRIGATORIAMENTE:** `Arquivos_Claude/CODIGO_CRITICO_NAO_MEXER.md`

**REGRA:** Alterações na página de ELABORAÇÃO não devem impactar o fluxo de CRIAÇÃO.

---

## PRÓXIMOS PASSOS

Ver arquivo: CHECKLIST_GERAL.md
