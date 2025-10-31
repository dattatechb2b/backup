# 🔄 Instruções Git - Commits Automáticos

**Data de criação:** 08/10/2025
**Repositório:** https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os

---

## 🎯 INSTRUÇÃO OBRIGATÓRIA

**⚠️ IMPORTANTE: AO FINAL DE TODA SESSÃO DE TRABALHO, SEMPRE DÊ UM COMMIT!**

Não importa se foi uma pequena alteração ou uma grande implementação, **SEMPRE** suba o código para o GitHub ao finalizar qualquer trabalho.

---

## 🔐 Credenciais GitHub

**Token de Acesso:** Consultar Cláudio ou verificar configuração local do git remote
**Repositório:** `dattatechb2b/Vinicius_cesta_de_pre-os`
**Branch principal:** `master`

### Verificar Remote Configurado
```bash
# Ver remote atual (o token já está configurado)
git remote -v

# Se necessário reconfigurar, pedir token ao Cláudio
```

---

## 📝 Procedimento Padrão de Commit

### 1️⃣ Verificar Status
```bash
git status
```

### 2️⃣ Adicionar Arquivos
```bash
# Adicionar arquivos específicos do projeto (NÃO adicionar vmail!)
git add app/ database/ routes/ resources/ Arquivos_Claude/

# Ou adicionar arquivos individualmente
git add caminho/do/arquivo.php
```

### 3️⃣ Criar Commit com Mensagem Detalhada

**Template de mensagem de commit:**

```bash
git commit -m "$(cat <<'EOF'
[tipo]: Título curto da alteração

Detalhes do que foi implementado:
- Item 1
- Item 2
- Item 3

Arquivos alterados:
- arquivo1.php
- arquivo2.blade.php

Features adicionadas:
- Feature A
- Feature B

Fixes:
- Correção X
- Correção Y

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"
```

**Tipos de commit:**
- `feat:` - Nova funcionalidade
- `fix:` - Correção de bug
- `docs:` - Documentação
- `refactor:` - Refatoração de código
- `perf:` - Melhoria de performance
- `test:` - Testes
- `chore:` - Tarefas gerais (build, configs)

### 4️⃣ Push para GitHub
```bash
git push origin master
```

### 5️⃣ Verificar Commit
```bash
git log --oneline -1
```

---

## 🚀 Exemplo Completo

```bash
# 1. Verificar o que foi alterado
git status

# 2. Adicionar arquivos do projeto
git add app/Http/Controllers/NovoController.php \
        app/Models/NovoModel.php \
        database/migrations/2025_10_08_create_nova_tabela.php \
        routes/web.php \
        Arquivos_Claude/NOVA_DOCUMENTACAO.md

# 3. Commit detalhado
git commit -m "$(cat <<'EOF'
feat: Implementa sistema de notificações

Detalhes:
- Sistema completo de notificações em tempo real
- Integração com WebSockets
- Notificações persistentes no banco

Arquivos alterados:
- app/Http/Controllers/NotificacaoController.php (novo)
- app/Models/Notificacao.php (novo)
- database/migrations/2025_10_08_create_notificacoes_table.php (novo)
- routes/web.php (adicionado rotas)

Features adicionadas:
- Envio de notificações
- Marcação como lida
- Listagem paginada
- Badge de contador

Fixes:
- N/A

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
EOF
)"

# 4. Push
git push origin master

# 5. Verificar
git log --oneline -1
```

---

## ⚠️ O QUE NÃO FAZER

### ❌ NÃO adicionar arquivos de email
```bash
# NUNCA faça isso:
git add ../../vmail/
```

Os arquivos de email (vmail) estão em outro diretório e não devem ser versionados.

### ❌ NÃO adicionar .env
O arquivo `.env` já está no `.gitignore` (correto). Nunca force com `-f`.

### ❌ NÃO fazer commit sem mensagem descritiva
```bash
# Ruim:
git commit -m "update"

# Bom:
git commit -m "feat: Adiciona validação de CPF no cadastro de usuários"
```

---

## 🔍 Verificações Importantes

### Antes do Commit
```bash
# Ver quais arquivos serão commitados
git status

# Ver diferenças nos arquivos
git diff

# Ver diferenças apenas dos arquivos staged
git diff --staged
```

### Após o Commit
```bash
# Ver último commit
git log -1

# Ver commits recentes
git log --oneline -5

# Verificar remote
git remote -v

# Verificar branch
git branch
```

---

## 🛠️ Comandos Úteis

### Desfazer Adição (antes do commit)
```bash
git restore --staged arquivo.php
```

### Ver Histórico de Commits
```bash
git log --oneline --graph --all -10
```

### Ver Branches Remotos
```bash
git branch -r
```

### Sincronizar com Remoto
```bash
git fetch origin
```

---

## 📋 Checklist de Fim de Sessão

Ao finalizar qualquer trabalho, siga este checklist:

- [ ] Verificar `git status`
- [ ] Adicionar arquivos relevantes (sem vmail!)
- [ ] Criar commit com mensagem detalhada
- [ ] Push para `origin master`
- [ ] Verificar commit com `git log -1`
- [ ] Confirmar no GitHub: https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os/commits/master

---

## 🔐 Segurança do Token

**⚠️ ATENÇÃO:**
- Este token tem permissões de **escrita** no repositório
- **NÃO compartilhar** este arquivo publicamente
- **NÃO commitar** este arquivo se o repositório for público (já está em Arquivos_Claude/)
- O token expira e pode ser revogado a qualquer momento

**Permissões do token:**
- ✅ `repo` - Acesso total aos repositórios privados
- ✅ `workflow` - Atualizar workflows do GitHub Actions

---

## 📞 Suporte

Se houver problemas com o Git:

1. Verificar se o token está correto
2. Verificar se o remote está configurado
3. Verificar se há conflitos: `git status`
4. Em caso de dúvida, consultar este documento

---

## 📚 Referências

- **Repositório:** https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os
- **Branch padrão:** master
- **Documentação Git:** https://git-scm.com/doc
- **GitHub CLI:** https://cli.github.com/

---

**Última atualização:** 08/10/2025
**Responsável:** Claude Code
**Versão:** 1.0.0
