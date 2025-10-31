# 📦 BACKUP DA ESTRUTURA - CESTA DE PREÇOS

**Data do Snapshot:** 2025-09-30 17:00
**Commit Git Atual:** b8945002
**Status:** ESTADO BASE LIMPO (sem funcionalidades implementadas)

---

## ⚠️ COMO USAR ESTE ARQUIVO

**Se algo der errado após uma mudança:**

```bash
# 1. Voltar para este estado no Git
cd /home/dattapro/modulos/cestadeprecos
git reset --hard b8945002

# 2. Reverter migrations do banco
php artisan migrate:fresh

# 3. Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Testar se voltou ao normal
php artisan serve --host=0.0.0.0 --port=8001
```

---

## 📂 ESTRUTURA DE ARQUIVOS ATUAL

### Controllers (2 arquivos)
```
app/Http/Controllers/
├── Controller.php                    [Base abstrata vazia]
└── AuthController.php                [Login, logout, dashboard]
```

### Models (1 arquivo)
```
app/Models/
└── User.php                          [Model padrão Laravel]
```

### Middlewares (2 arquivos)
```
app/Http/Middleware/
├── InternalOnly.php                  [Bloqueia acesso externo]
└── ProxyAuth.php                     [Auth automática via proxy]
```

### Views (6 arquivos)
```
resources/views/
├── layouts/
│   └── app.blade.php                 [Layout principal com sidebar]
├── auth/
│   └── login.blade.php               [Página de login]
├── dashboard.blade.php               [Dashboard com dados mockados]
└── orcamentos/
    ├── create.blade.php              [Formulário sem backend]
    ├── pendentes.blade.php           [Placeholder vazio]
    └── concluidos.blade.php          [Placeholder vazio]
```

### Rotas (1 arquivo)
```
routes/
└── web.php                           [13 rotas definidas]
```

### Migrations (3 arquivos executados)
```
database/migrations/
├── 0001_01_01_000000_create_users_table.php           ✅ Executada
├── 0001_01_01_000001_create_cache_table.php           ✅ Executada
└── 0001_01_01_000002_create_jobs_table.php            ✅ Executada
```

### Configurações
```
.env                                  [PostgreSQL, prefixo cp_]
config/database.php                   [Config PostgreSQL]
config/auth.php                       [Auth web]
bootstrap/app.php                     [Middlewares registrados]
```

---

## 🗄️ ESTADO DO BANCO DE DADOS

### Tabelas Criadas (prefixo cp_)
```sql
-- Tabelas de usuários
cp_users                              (2 registros)
cp_password_reset_tokens              (vazia)

-- Tabelas de sistema
cp_sessions                           (vazia)
cp_cache                              (vazia)
cp_cache_locks                        (vazia)
cp_jobs                               (vazia)
cp_job_batches                        (vazia)
cp_failed_jobs                        (vazia)
cp_migrations                         (3 registros)
```

### Usuários Existentes
```
1. Vinícius (vinicius@catasaltas.dattapro.online) - Senha: 10037175
2. Fernando (lassais@catasaltas.dattapro.online)
```

### Backup do Banco (SQL)
```bash
# Para criar backup do banco ANTES de alterações:
PGPASSWORD='MinhaDataTech2024SecureDB' pg_dump \
  -h 127.0.0.1 \
  -U minhadattatech_user \
  -d minhadattatech_db \
  --table='cp_*' \
  --no-owner \
  --no-acl \
  -f /home/dattapro/modulos/cestadeprecos/.backup_banco_$(date +%Y%m%d_%H%M%S).sql

# Para restaurar backup:
PGPASSWORD='MinhaDataTech2024SecureDB' psql \
  -h 127.0.0.1 \
  -U minhadattatech_user \
  -d minhadattatech_db \
  -f /home/dattapro/modulos/cestadeprecos/.backup_banco_XXXXXXXX_XXXXXX.sql
```

---

## 🔄 COMANDOS DE REVERSÃO RÁPIDA

### Reverter TUDO para este estado
```bash
cd /home/dattapro/modulos/cestadeprecos

# Reverter código
git reset --hard b8945002

# Reverter banco (CUIDADO: apaga dados)
php artisan migrate:fresh

# Limpar tudo
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Reverter apenas última alteração
```bash
# Ver últimos commits
git log --oneline -5

# Reverter último commit (mantém histórico)
git revert HEAD

# OU voltar 1 commit atrás (perde histórico)
git reset --hard HEAD~1
```

### Reverter migration específica
```bash
# Ver status
php artisan migrate:status

# Reverter última
php artisan migrate:rollback

# Reverter N migrations
php artisan migrate:rollback --step=2
```

---

## 📋 CHECKLIST ANTES DE FAZER MUDANÇAS

```bash
# 1. Criar backup do código
cd /home/dattapro/modulos/cestadeprecos
git add .
git commit -m "[Backup] Antes de [DESCRIÇÃO]"

# 2. Criar backup do banco
PGPASSWORD='MinhaDataTech2024SecureDB' pg_dump \
  -h 127.0.0.1 \
  -U minhadattatech_user \
  -d minhadattatech_db \
  --table='cp_*' \
  --no-owner \
  --no-acl \
  -f .backup_banco_$(date +%Y%m%d_%H%M%S).sql

# 3. Anotar commit atual
git log --oneline -1

# 4. Fazer a alteração
# ... suas mudanças ...

# 5. Testar
php artisan serve --host=0.0.0.0 --port=8001
# Acessar: http://localhost:8001

# 6. Se der errado, usar comandos de reversão acima
```

---

## 📊 ESTADO DOS ARQUIVOS IMPORTANTES

### AuthController.php (4 métodos)
```php
showLogin()    → Exibe formulário de login
login()        → Processa login (email OU username)
dashboard()    → Exibe dashboard com dados mockados
logout()       → Faz logout e invalida sessão
```

### routes/web.php (13 rotas)
```php
GET  /                      → Redireciona
GET  /login                 → AuthController@showLogin
POST /login                 → AuthController@login
POST /logout                → AuthController@logout
GET  /dashboard             → AuthController@dashboard
GET  /orcamentos/novo       → view (sem backend)
GET  /orcamentos/pendentes  → view vazia
GET  /orcamentos/concluidos → view vazia
GET  /health                → Health check JSON
GET  /up                    → Laravel health
GET  /info                  → Debug info (local only)
```

### InternalOnly Middleware
```php
Função: Bloquear acesso externo ao módulo
Valida: IP (127.0.0.1), Token (X-Module-Token), Headers contexto
Injeta: Tenant ID, User ID, DB Prefix
```

### ProxyAuth Middleware
```php
Função: Autenticação automática via proxy
Cria/Atualiza: Usuário local baseado em headers
Faz: Auth::login() automático
```

---

## 🚨 ARQUIVOS QUE NÃO DEVEM SER ALTERADOS

**NÃO mexer sem necessidade:**
```
.env                         → Configurações sensíveis
config/database.php          → Config do banco
bootstrap/app.php            → Bootstrap do Laravel
app/Http/Middleware/InternalOnly.php     → Segurança crítica
app/Http/Middleware/ProxyAuth.php        → Auth crítica
```

---

## ✅ O QUE PODE SER ALTERADO COM SEGURANÇA

**Pode criar/modificar livremente:**
```
app/Models/*                 → Criar novos models
app/Http/Controllers/*       → Criar novos controllers
resources/views/*            → Criar/modificar views
routes/web.php               → Adicionar rotas (cuidado)
database/migrations/*        → Criar novas migrations
```

---

## 📝 REGISTRO DE ALTERAÇÕES

### 2025-09-30 17:00 - ESTADO BASE
```
Status: Sistema base sem funcionalidades
Commit: b8945002
Tabelas: 9 (cp_*)
Controllers: 2
Models: 1
Views: 6
Rotas: 13
```

---

## 🆘 EM CASO DE EMERGÊNCIA

**Se o site parar de funcionar:**

```bash
# 1. PARAR servidor
# Pressionar Ctrl+C no terminal do artisan serve

# 2. VOLTAR para este estado
cd /home/dattapro/modulos/cestadeprecos
git reset --hard b8945002
php artisan migrate:fresh
php artisan cache:clear

# 3. REINICIAR servidor
php artisan serve --host=0.0.0.0 --port=8001

# 4. TESTAR
# Acessar: http://localhost:8001/login
# Usuário: vinicius@catasaltas.dattapro.online
# Senha: 10037175
```

**Se ainda não funcionar:**
```bash
# Verificar logs
tail -f storage/logs/laravel.log

# Verificar permissões
ls -la storage/
ls -la bootstrap/cache/

# Recriar permissões
chmod -R 775 storage bootstrap/cache
```

---

**FIM DO BACKUP DE ESTRUTURA**

_Mantenha este arquivo atualizado quando fizer mudanças importantes!_
_Anote aqui qual commit representa qual estado do sistema._
