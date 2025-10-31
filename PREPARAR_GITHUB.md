# 🚀 GUIA: Preparar Sistema para GitHub

**Data:** 16/10/2025
**Versão:** 2.0.0

---

## 📋 CHECKLIST COMPLETO

Use este guia para preparar o sistema Cesta de Preços para publicação no GitHub.

### ✅ Etapa 1: Limpar Arquivos Desnecessários

```bash
cd /home/dattapro/modulos/cestadeprecos

# Remover arquivos de teste do desenvolvedor
rm -f test*.php teste*.php analisar*.php cadastrar*.php atualizar*.php

# Remover screenshots e imagens de teste
rm -f *.png *.jpg *.jpeg *.gif *.PNG *.JPG

# Remover planilhas de teste
rm -f *.xlsx *.xls *.csv *.ods

# Remover documentos de teste
rm -f *.docx *.doc *.odt *.pdf

# Remover arquivos Claude
rm -rf Arquivos_Claude/

# Remover backups antigos
rm -rf backups/

# Remover scripts de fix temporários
rm -f fix_*.py fix_*.sh
```

### ✅ Etapa 2: Aplicar .gitignore Correto

```bash
# Backup do .gitignore atual
cp .gitignore .gitignore.old

# Aplicar novo .gitignore
cp .gitignore.github .gitignore

# Verificar o que será ignorado
git status --ignored
```

### ✅ Etapa 3: Limpar Cache e Temporários

```bash
# Limpar cache do Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Limpar logs antigos
rm -f storage/logs/*.log
touch storage/logs/.gitkeep

# Limpar uploads de teste
rm -rf storage/app/public/brasoes/*
touch storage/app/public/brasoes/.gitkeep

# Limpar cache do framework
rm -rf storage/framework/cache/*
touch storage/framework/cache/.gitkeep

# Limpar sessões
rm -rf storage/framework/sessions/*
touch storage/framework/sessions/.gitkeep

# Limpar views compiladas
rm -rf storage/framework/views/*
touch storage/framework/views/.gitkeep

# Limpar node_modules (será reinstalado)
rm -rf node_modules/
```

### ✅ Etapa 4: Proteger Informações Sensíveis

```bash
# Criar .env.example atualizado (SEM SENHAS REAIS)
cp .env .env.backup
cat > .env.example << 'EOF'
APP_NAME="Cesta de Preços"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://localhost:8001

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cestadeprecos_db
DB_USERNAME=cestadeprecos_user
DB_PASSWORD=sua_senha_aqui
DB_TABLE_PREFIX=cp_

SESSION_DRIVER=database
SESSION_CONNECTION=pgsql_sessions
SESSION_LIFETIME=120

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="Cesta de Preços"

# Portal da Transparência (CGU) - Obtenha em: https://portaldatransparencia.gov.br/api-de-dados
PORTALTRANSPARENCIA_API_KEY=sua_chave_aqui
EOF
```

### ✅ Etapa 5: Atualizar README.md

```bash
# Substituir README.md pelo novo
cp README_GITHUB.md README.md

# Editar e personalizar URLs
nano README.md
# Alterar: seu-usuario -> seu_usuario_github_real
# Alterar: cestadeprecos.dominio.com.br -> seu_dominio_real
```

### ✅ Etapa 6: Verificar Estrutura de Diretórios

```bash
# Criar .gitkeep em diretórios vazios necessários
touch storage/app/.gitkeep
touch storage/app/public/.gitkeep
touch storage/app/public/brasoes/.gitkeep
touch storage/app/public/pdfs/.gitkeep
touch storage/framework/.gitkeep
touch storage/framework/cache/.gitkeep
touch storage/framework/sessions/.gitkeep
touch storage/framework/testing/.gitkeep
touch storage/framework/views/.gitkeep
touch storage/logs/.gitkeep
```

### ✅ Etapa 7: Inicializar Git (se ainda não foi)

```bash
# Se ainda não tem repositório git
git init

# Adicionar remote do GitHub
git remote add origin https://github.com/SEU_USUARIO/cestadeprecos.git
```

### ✅ Etapa 8: Primeiro Commit

```bash
# Adicionar todos os arquivos
git add .

# Verificar o que será commitado
git status

# Criar commit inicial
git commit -m "feat: Versão inicial do sistema Cesta de Preços

- Sistema completo de orçamento estimativo
- Integração com PNCP, Portal da Transparência, Compras.gov
- Cotação Direta com Fornecedores (CDF)
- Geração de PDFs com layout oficial
- OCR para extração de dados
- Importação de planilhas Excel/Word
- Pesquisa automatizada de preços
- Laravel 11.31 + PostgreSQL + Redis

Versão: 2.0.0"
```

### ✅ Etapa 9: Criar Tags e Branches

```bash
# Criar tag da versão
git tag -a v2.0.0 -m "Versão 2.0.0 - Release inicial GitHub"

# Criar branch de desenvolvimento
git checkout -b develop

# Voltar para main
git checkout main
```

### ✅ Etapa 10: Push para GitHub

```bash
# Push do código
git push -u origin main

# Push da tag
git push origin v2.0.0

# Push da branch develop
git push -u origin develop
```

---

## 📝 VERIFICAÇÕES FINAIS

### Antes de fazer push, verificar:

- [ ] `.env` NÃO está sendo versionado (deve aparecer em .gitignore)
- [ ] `.env.example` ESTÁ sendo versionado (template sem senhas)
- [ ] `vendor/` e `node_modules/` NÃO estão sendo versionados
- [ ] `storage/logs/*.log` NÃO estão sendo versionados
- [ ] Uploads e brasões de teste NÃO estão sendo versionados
- [ ] README.md está atualizado e personalizado
- [ ] LICENSE existe (se aplicável)
- [ ] .gitignore está configurado corretamente
- [ ] Migrations estão todas versionadas
- [ ] Seeders estão versionados (se aplicável)

### Comando para verificar tamanho:

```bash
# Ver tamanho do repositório
du -sh .git

# Ver arquivos maiores que 10MB
find . -type f -size +10M -exec ls -lh {} \;

# GitHub tem limite de 100MB por arquivo
# Se houver arquivos grandes, adicione ao .gitignore
```

---

## 🔒 SEGURANÇA

### NUNCA versionar:

- ❌ Senhas reais no `.env`
- ❌ Chaves de API privadas
- ❌ Certificados SSL
- ❌ Backups de banco de dados
- ❌ Logs com informações sensíveis
- ❌ Uploads de usuários reais
- ❌ Dados pessoais (LGPD)

### SEMPRE versionar:

- ✅ Código fonte
- ✅ Migrations
- ✅ Seeders (se não contiverem dados sensíveis)
- ✅ Assets públicos essenciais
- ✅ Configurações de exemplo (`.env.example`)
- ✅ Documentação
- ✅ Testes

---

## 📦 ESTRUTURA FINAL NO GITHUB

```
cestadeprecos/
├── .github/
│   └── workflows/           # GitHub Actions (opcional)
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── storage/
│   └── app/
│       └── .gitkeep
├── tests/
├── .editorconfig
├── .env.example             # ✅ Template sem senhas
├── .gitattributes
├── .gitignore               # ✅ Configurado
├── composer.json
├── LICENSE                  # ✅ Se aplicável
├── package.json
├── phpunit.xml
├── README.md                # ✅ Completo e atualizado
└── vite.config.js
```

---

## 🎯 PRÓXIMOS PASSOS (Opcional)

### 1. Configurar GitHub Actions (CI/CD)

Criar `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: php artisan test
```

### 2. Criar Wiki do Projeto

- Documentação detalhada
- Guias de uso
- FAQ
- Troubleshooting

### 3. Configurar Issues Templates

Criar `.github/ISSUE_TEMPLATE/bug_report.md`
Criar `.github/ISSUE_TEMPLATE/feature_request.md`

### 4. Adicionar Badges no README

```markdown
![Laravel](https://img.shields.io/badge/Laravel-11.31-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue)
![License](https://img.shields.io/badge/License-MIT-green)
```

---

## ✅ CHECKLIST FINAL

Antes de tornar o repositório público:

- [ ] Código limpo e sem arquivos de teste
- [ ] .gitignore configurado corretamente
- [ ] README.md completo e personalizado
- [ ] .env.example sem senhas reais
- [ ] Documentação atualizada
- [ ] Migrations testadas
- [ ] Sistema funciona após clone + install
- [ ] Licença definida (se aplicável)
- [ ] Informações sensíveis removidas
- [ ] Tamanho do repositório aceitável (< 1GB)

---

## 🎉 PRONTO!

Seu sistema está preparado para o GitHub!

**Comando final:**

```bash
git push -u origin main --tags
```

Acesse: `https://github.com/SEU_USUARIO/cestadeprecos`

---

**Preparado por:** Claude Code
**Data:** 16/10/2025
**Versão:** 2.0.0
