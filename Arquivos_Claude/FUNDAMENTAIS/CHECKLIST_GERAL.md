# CHECKLIST GERAL - MÓDULO CESTA DE PREÇOS

**Última atualização**: 30/09/2025 16:47

## 📋 LEGENDA
- ✅ Concluído
- 🚧 Em desenvolvimento
- ⏳ Aguardando
- ❌ Não iniciado

---

## 1. NOVO ORÇAMENTO 🚧

### 1.1 Banco de Dados
- ✅ Migration da tabela `cp_orcamentos`
- ✅ Campos obrigatórios implementados
- ✅ Campos opcionais implementados
- ✅ Soft deletes configurado
- ✅ Indexes criados

### 1.2 Model
- ✅ Model `Orcamento` criado
- ✅ Fillable configurado
- ✅ Casts configurado
- ✅ Scopes: pendentes(), realizados()
- ✅ Métodos: marcarComoRealizado(), marcarComoPendente()
- ✅ Relacionamentos: user, orcamentoOrigem, orcamentosDerivados

### 1.3 Controller
- ✅ OrcamentoController criado
- ✅ Método create() - exibir formulário
- ✅ Método store() - salvar orçamento
- ✅ Validação de campos
- ✅ Mensagens de erro personalizadas
- ✅ Tratamento de exceções

### 1.4 Views
- ✅ View create.blade.php criada
- ✅ Formulário com 4 campos principais
- ✅ 3 abas implementadas:
  - ✅ Aba 1: Criar do Zero (funcional)
  - ✅ Aba 2: Criar a partir de Outro Orçamento (estrutura criada)
  - ✅ Aba 3: Criar a partir de Documento (placeholder)
- ✅ Validação client-side (required nos campos)
- ✅ Helper texts nos campos
- ✅ Estilos CSS customizados
- ✅ JavaScript para controle de abas

### 1.5 Rotas
- ✅ GET /orcamentos/novo → create()
- ✅ POST /orcamentos/novo → store()
- ✅ Rotas protegidas com 'ensure.authenticated'

### 1.6 Funcionalidades Pendentes
- ⏳ Implementar funcionalidade "Criar a partir de Outro Orçamento"
  - Carregar dados do orçamento selecionado via AJAX
  - Preencher campos automaticamente
  - Permitir edição antes de salvar
- ⏳ Implementar funcionalidade "Criar a partir de Documento"
  - Upload de arquivo
  - Parsing do documento (PDF, Word, Excel)
  - Extração automática de dados
- ❌ Testes unitários do OrcamentoController
- ❌ Testes de integração do formulário

### 1.7 Melhorias Futuras
- ❌ Preview do orçamento antes de salvar
- ❌ Salvar como rascunho
- ❌ Anexar arquivos ao orçamento
- ❌ Histórico de alterações

---

## 2. PENDENTES ❌

### 2.1 Listagem
- ✅ View pendentes.blade.php criada
- ✅ Controller: método pendentes()
- ✅ Rota: GET /orcamentos/pendentes
- ❌ Filtros de busca (por nome, referência, data)
- ❌ Ordenação de colunas
- ❌ Exportar para Excel/PDF
- ❌ Ações em massa (marcar vários como realizado)

### 2.2 Detalhes
- ❌ View show.blade.php
- ❌ Exibir todos os campos do orçamento
- ❌ Mostrar histórico de alterações
- ❌ Botões de ação (editar, marcar realizado, excluir)

### 2.3 Edição
- ❌ View edit.blade.php
- ❌ Controller: método edit()
- ❌ Controller: método update()
- ❌ Validação de campos
- ❌ Histórico de alterações

### 2.4 Ações
- ✅ Marcar como realizado (estrutura criada)
- ❌ Testar marcar como realizado
- ❌ Excluir orçamento (soft delete)
- ❌ Duplicar orçamento
- ❌ Enviar por e-mail

---

## 3. REALIZADOS ❌

### 3.1 Listagem
- ✅ View realizados.blade.php criada
- ✅ Controller: método realizados()
- ✅ Rota: GET /orcamentos/realizados
- ❌ Filtros de busca (por nome, referência, data conclusão)
- ❌ Ordenação de colunas
- ❌ Exportar para Excel/PDF
- ❌ Estatísticas (total, média de tempo, etc)

### 3.2 Detalhes
- ❌ View show.blade.php (compartilhada com Pendentes)
- ❌ Exibir data de conclusão
- ❌ Botão para marcar como pendente novamente
- ❌ Comparar com outros orçamentos

### 3.3 Relatórios
- ❌ Relatório de orçamentos por período
- ❌ Relatório de orçamentos por órgão
- ❌ Gráficos e estatísticas
- ❌ Exportar relatórios

---

## 4. PESQUISA RÁPIDA ❌

### 4.1 Funcionalidade
- ❌ Definir o que será pesquisado (orçamentos, fornecedores, produtos?)
- ❌ Interface de busca
- ❌ Resultados com paginação
- ❌ Filtros avançados
- ❌ Busca por palavra-chave
- ❌ Busca por período

### 4.2 Backend
- ❌ Controller para pesquisa
- ❌ Método de busca otimizado
- ❌ Índices no banco para performance
- ❌ API de busca (se necessário)

---

## 5. MAPA DE ATAS ❌

### 5.1 Definição
- ❌ Entender o que é "Mapa de Atas" no contexto
- ❌ Definir estrutura de dados
- ❌ Definir funcionalidades necessárias

### 5.2 Banco de Dados
- ❌ Migration para tabela de atas
- ❌ Relacionamentos com orçamentos
- ❌ Campos necessários

### 5.3 CRUD
- ❌ Controller
- ❌ Views (listagem, criação, edição)
- ❌ Rotas
- ❌ Validações

---

## 6. MAPA DE FORNECEDORES ❌

### 6.1 Definição
- ❌ Entender o que é "Mapa de Fornecedores"
- ❌ Definir estrutura de dados
- ❌ Definir funcionalidades necessárias

### 6.2 Banco de Dados
- ❌ Migration para tabela de fornecedores
- ❌ Campos necessários (CNPJ, razão social, etc)
- ❌ Relacionamentos

### 6.3 CRUD
- ❌ Controller
- ❌ Views
- ❌ Rotas
- ❌ Validações

---

## 7. CATÁLOGO ❌

### 7.1 Definição
- ❌ Entender o que será catalogado (produtos, serviços?)
- ❌ Definir estrutura de dados
- ❌ Definir categorias

### 7.2 Banco de Dados
- ❌ Migration para tabela de itens do catálogo
- ❌ Migration para categorias
- ❌ Relacionamentos

### 7.3 CRUD
- ❌ Controller
- ❌ Views
- ❌ Rotas
- ❌ Busca e filtros

---

## 8. ORIENTAÇÕES TÉC. ❌

### 8.1 Definição
- ❌ Entender o conteúdo das orientações técnicas
- ❌ Definir estrutura (texto, PDF, vídeo?)
- ❌ Definir categorias

### 8.2 Banco de Dados
- ❌ Migration para tabela de orientações
- ❌ Campos necessários
- ❌ Sistema de categorização

### 8.3 Interface
- ❌ Visualização de orientações
- ❌ Busca por categoria/palavra-chave
- ❌ Download de arquivos
- ❌ Versionamento de orientações

---

## 9. FORNECEDORES ❌

### 9.1 CRUD Básico
- ❌ Migration para tabela de fornecedores
- ❌ Controller
- ❌ Views (listagem, cadastro, edição)
- ❌ Validações (CNPJ, email, etc)

### 9.2 Funcionalidades
- ❌ Cadastro completo de fornecedor
- ❌ Histórico de orçamentos com fornecedor
- ❌ Avaliação de fornecedores
- ❌ Documentos anexados
- ❌ Contatos do fornecedor

### 9.3 Integrações
- ❌ Consulta CNPJ (Receita Federal)
- ❌ Integração com sistema de compras
- ❌ Importação em lote

---

## OBSERVAÇÕES IMPORTANTES

### Arquivos Modificados Nesta Sessão
- ✅ app/Http/Middleware/ProxyAuth.php
- ✅ app/Http/Middleware/EnsureAuthenticated.php
- ✅ bootstrap/app.php
- ✅ routes/web.php
- ✅ resources/views/layouts/app.blade.php
- ✅ resources/views/orcamentos/create.blade.php
- ✅ resources/views/orcamentos/pendentes.blade.php
- ✅ resources/views/orcamentos/realizados.blade.php
- ✅ resources/views/dashboard.blade.php
- ✅ minhadattatech/routes/web.php (moveu rota module-proxy)
- ✅ minhadattatech/app/Http/Controllers/ModuleProxyController.php
- ✅ minhadattatech/.env (SESSION_SAME_SITE=none, PARTITIONED=true)

### Backup de Segurança
- ✅ routes/web.php.backup

### Testes Necessários
- ⏳ Testar navegação completa: Dashboard → Novo Orçamento → Salvar → Pendentes → Realizados
- ⏳ Testar validação de campos obrigatórios
- ⏳ Testar mensagens de erro
- ⏳ Testar em diferentes navegadores (Chrome, Firefox, Safari, Edge)
- ⏳ Testar em dispositivos móveis
