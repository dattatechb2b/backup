# 🔄 RESTAURAÇÃO COMPLETA DO SISTEMA - GUIA PARA CLAUDE CODE

**⚠️ DOCUMENTO CRÍTICO** - Este guia permite restaurar o sistema completo a partir do ZERO

**Data:** 31/10/2025
**Versão:** 1.0.0
**Autor:** Claude Code (Anthropic)

---

## 🎯 OBJETIVO

Este documento foi criado especificamente para **Claude Code (Anthropic)** conseguir restaurar o sistema completo do zero usando APENAS o repositório GitHub, sem precisar de backups externos ou conhecimento prévio.

---

## 📋 PRÉ-REQUISITOS DO SERVIDOR

### 1. Sistema Operacional

```bash
# Ubuntu 20.04 ou 22.04 LTS recomendado
lsb_release -a
```

### 2. Software Obrigatório

```bash
# Atualizar sistema
sudo apt-get update && sudo apt-get upgrade -y

# PHP 8.2
sudo apt-get install -y php8.2 php8.2-cli php8.2-fpm

# Extensões PHP
sudo apt-get install -y \
    php8.2-pgsql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-intl \
    php8.2-redis \
    php8.2-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# PostgreSQL 14+
sudo apt-get install -y postgresql postgresql-contrib

# Redis
sudo apt-get install -y redis-server

# Git
sudo apt-get install -y git

# Tesseract OCR (opcional mas recomendado)
sudo apt-get install -y tesseract-ocr tesseract-ocr-por
```

### 3. Verificar Instalações

```bash
php -v          # Deve mostrar 8.2+
composer -V     # Deve mostrar 2.x
node -v         # Deve mostrar v18+
npm -v          # Deve mostrar 9+
psql --version  # Deve mostrar 14+
git --version
```

---

## 🗂️ ESTRUTURA DO SISTEMA

### Organização de Diretórios

```
/home/dattapro/
├── minhadattatech/           # Sistema Core (Porta 80)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── .env
│
└── modulos/
    ├── cestadeprecos/        # Módulo Cesta (Porta 8001)
    │   ├── app/
    │   ├── config/
    │   ├── database/
    │   ├── routes/
    │   ├── public/
    │   └── .env
    │
    └── nfe/                  # Módulo NFe (Porta 8002)
        ├── app/
        ├── config/
        ├── database/
        └── .env
```

---

## 🚀 PASSO A PASSO COMPLETO DE RESTAURAÇÃO

### PASSO 1: Configurar PostgreSQL

```bash
# Trocar para usuário postgres
sudo -u postgres psql

# Executar comandos SQL:
```

```sql
-- Criar banco principal
CREATE DATABASE minhadattatech_db;

-- Criar usuário
CREATE USER minhadattatech_user WITH PASSWORD 'MinhaDataTech2024SecureDB';

-- Conceder privilégios
GRANT ALL PRIVILEGES ON DATABASE minhadattatech_db TO minhadattatech_user;
ALTER USER minhadattatech_user WITH SUPERUSER;

-- Criar bancos dos tenants
CREATE DATABASE catasaltas_db;
CREATE DATABASE dattatech_db;
CREATE DATABASE gurupi_db;
CREATE DATABASE novalaranjeiras_db;
CREATE DATABASE novaroma_db;
CREATE DATABASE pirapora_db;

-- Conceder privilégios em todos
GRANT ALL PRIVILEGES ON DATABASE catasaltas_db TO minhadattatech_user;
GRANT ALL PRIVILEGES ON DATABASE dattatech_db TO minhadattatech_user;
GRANT ALL PRIVILEGES ON DATABASE gurupi_db TO minhadattatech_user;
GRANT ALL PRIVILEGES ON DATABASE novalaranjeiras_db TO minhadattatech_user;
GRANT ALL PRIVILEGES ON DATABASE novaroma_db TO minhadattatech_user;
GRANT ALL PRIVILEGES ON DATABASE pirapora_db TO minhadattatech_user;

-- Sair
\q
```

### PASSO 2: Criar Estrutura de Diretórios

```bash
# Criar diretório base
sudo mkdir -p /home/dattapro
sudo mkdir -p /home/dattapro/modulos

# Criar diretórios de logs
sudo mkdir -p /var/log/cestadeprecos
sudo mkdir -p /var/log/minhadattatech
sudo mkdir -p /var/log/nfe

# Permissões
sudo chown -R $USER:$USER /home/dattapro
sudo chown -R www-data:www-data /var/log/cestadeprecos
sudo chown -R www-data:www-data /var/log/minhadattatech
sudo chown -R www-data:www-data /var/log/nfe
```

### PASSO 3: Clonar Repositórios

```bash
# Ir para diretório base
cd /home/dattapro

# Clonar sistema Core
git clone https://github.com/dattatechb2b/minhadattatech-core.git minhadattatech

# Clonar módulo Cesta de Preços
cd modulos
git clone https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os.git cestadeprecos

# Clonar módulo NFe (se disponível)
# git clone https://github.com/dattatechb2b/modulo-nfe.git nfe
```

### PASSO 4: Configurar Sistema Core

```bash
cd /home/dattapro/minhadattatech

# Copiar .env
cp .env.example .env

# Editar .env (usar nano ou vi)
nano .env
```

**Configurar no `.env` do Core:**

```env
APP_NAME="Minha Datta Tech"
APP_ENV=production
APP_KEY=                    # Será gerado no próximo comando
APP_DEBUG=false
APP_URL=https://minha.dattatech.com.br

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minhadattatech_db
DB_USERNAME=minhadattatech_user
DB_PASSWORD=MinhaDataTech2024SecureDB

# Sessions
SESSION_DRIVER=database
SESSION_DOMAIN=.dattatech.com.br
SESSION_COOKIE=minhadattatech_session_v2

# PostgreSQL Superuser
DB_POSTGRES_PASSWORD=MinhaDataTech2024SecureDB

# Technical Panel (ajustar se necessário)
TECHNICAL_PANEL_URL=http://localhost:8080
TECHNICAL_PANEL_API_TOKEN=temp_dev_token_hybrid
```

```bash
# Instalar dependências PHP
composer install --no-dev --optimize-autoloader

# Gerar chave
php artisan key:generate

# Instalar dependências Node
npm install
npm run build

# Permissões
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Link simbólico
php artisan storage:link

# Rodar migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### PASSO 5: Configurar Módulo Cesta de Preços

```bash
cd /home/dattapro/modulos/cestadeprecos

# Copiar .env
cp .env.example .env

# Editar .env
nano .env
```

**Configurar no `.env` do Módulo:**

```env
APP_NAME="Cesta de Preços"
APP_ENV=production
APP_KEY=                    # Será gerado no próximo comando
APP_DEBUG=false
APP_URL=http://localhost:8001

# Database (mesmo banco do core!)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minhadattatech_db
DB_USERNAME=minhadattatech_user
DB_PASSWORD=MinhaDataTech2024SecureDB

# Sessions
SESSION_DRIVER=database
SESSION_TABLE=cp_sessions
SESSION_DOMAIN=.dattatech.com.br

# Cache
CACHE_STORE=redis
CACHE_PREFIX=cesta_precos_

# APIs (configurar se necessário)
PNCP_CONNECT_TIMEOUT=5
PNCP_TIMEOUT=20
PORTALTRANSPARENCIA_API_KEY=

# Email
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_FROM_ADDRESS="suporte@dattatech.com.br"
MAIL_FROM_NAME="${APP_NAME}"
```

```bash
# Instalar dependências PHP
composer install --no-dev --optimize-autoloader

# Gerar chave
php artisan key:generate

# Instalar dependências Node
npm install
npm run build

# Permissões
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Link simbólico
php artisan storage:link

# Rodar migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### PASSO 6: Configurar Módulo NFe (Opcional)

```bash
cd /home/dattapro/modulos/nfe

# Copiar .env
cp .env.example .env

# Editar .env (similar aos anteriores)
nano .env

# Instalar dependências
composer install --no-dev --optimize-autoloader
php artisan key:generate

# Permissões
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Migrations
php artisan migrate --force
```

### PASSO 7: Configurar Serviços Systemd

**Serviço Cesta de Preços:**

```bash
sudo nano /etc/systemd/system/cestadeprecos.service
```

```ini
[Unit]
Description=Cesta de Preços Module
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/dattapro/modulos/cestadeprecos
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8001
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

**Serviço NFe:**

```bash
sudo nano /etc/systemd/system/nfe.service
```

```ini
[Unit]
Description=NFe Module
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/dattapro/modulos/nfe
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8002
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

**Habilitar e iniciar serviços:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable cestadeprecos.service
sudo systemctl enable nfe.service
sudo systemctl start cestadeprecos.service
sudo systemctl start nfe.service

# Verificar status
sudo systemctl status cestadeprecos.service
sudo systemctl status nfe.service
```

### PASSO 8: Configurar Nginx (Proxy Reverso)

```bash
sudo nano /etc/nginx/sites-available/dattatech
```

```nginx
# Core Application
server {
    listen 80;
    server_name *.dattatech.com.br dattatech.com.br *.dattapro.online dattapro.online;
    root /home/dattapro/minhadattatech/public;

    index index.php index.html;

    # Logs
    access_log /var/log/nginx/dattatech-access.log;
    error_log /var/log/nginx/dattatech-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Habilitar site
sudo ln -s /etc/nginx/sites-available/dattatech /etc/nginx/sites-enabled/

# Testar configuração
sudo nginx -t

# Recarregar Nginx
sudo systemctl reload nginx
```

### PASSO 9: Configurar Supervisor (Workers)

```bash
sudo nano /etc/supervisor/conf.d/cestadeprecos-worker.conf
```

```ini
[program:cestadeprecos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/dattapro/modulos/cestadeprecos/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/cestadeprecos/worker.log
stopwaitsecs=3600
```

```bash
# Recarregar supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cestadeprecos-worker:*

# Verificar status
sudo supervisorctl status
```

### PASSO 10: Configurar Cron Jobs

```bash
sudo crontab -e -u www-data
```

```cron
# Laravel Scheduler (Core)
* * * * * cd /home/dattapro/minhadattatech && php artisan schedule:run >> /dev/null 2>&1

# Laravel Scheduler (Cesta de Preços)
* * * * * cd /home/dattapro/modulos/cestadeprecos && php artisan schedule:run >> /dev/null 2>&1

# Sincronização PNCP (diária às 2h)
0 2 * * * cd /home/dattapro/modulos/cestadeprecos && php artisan sincronizar:pncp-completo >> /var/log/cestadeprecos/sincronizacao.log 2>&1

# Importação CATMAT (semanal - domingo às 3h)
0 3 * * 0 cd /home/dattapro/modulos/cestadeprecos && php artisan importar:catmat >> /var/log/cestadeprecos/catmat.log 2>&1

# Limpeza de logs (mensal)
0 0 1 * * find /home/dattapro/*/storage/logs -type f -mtime +30 -delete
```

---

## ✅ VERIFICAÇÃO FINAL

### 1. Verificar Serviços

```bash
# PostgreSQL
sudo systemctl status postgresql

# Redis
sudo systemctl status redis

# Nginx
sudo systemctl status nginx

# Módulos
sudo systemctl status cestadeprecos.service
sudo systemctl status nfe.service

# Supervisor
sudo supervisorctl status
```

### 2. Testar Conexões

```bash
# Cesta de Preços
curl http://localhost:8001/

# NFe
curl http://localhost:8002/

# Core (via Nginx)
curl http://localhost/
```

### 3. Verificar Banco de Dados

```bash
cd /home/dattapro/modulos/cestadeprecos
php artisan db:check-setup

# Verificar migrations
php artisan migrate:status

# Testar query
psql -U minhadattatech_user -d minhadattatech_db -c "SELECT COUNT(*) FROM cp_catmat;"
```

### 4. Verificar Logs

```bash
# Laravel (Cesta de Preços)
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

# Nginx
tail -f /var/log/nginx/error.log

# Systemd
sudo journalctl -u cestadeprecos.service -f
```

---

## 📊 DADOS INICIAIS (OPCIONAL MAS RECOMENDADO)

### Importar Dados Base

```bash
cd /home/dattapro/modulos/cestadeprecos

# 1. Importar CATMAT (336k códigos)
php artisan importar:catmat
# Tempo estimado: 10-20 minutos

# 2. Importar CMED (medicamentos)
# Primeiro, colocar planilha CMED em /home/dattapro/modulos/cestadeprecos/
php artisan importar:cmed
# Tempo estimado: 5-10 minutos

# 3. Sincronizar PNCP (opcional, muitos dados)
# php artisan sincronizar:pncp-completo
# ATENÇÃO: Pode levar HORAS, executar em background
# nohup php artisan sincronizar:pncp-completo > /var/log/cestadeprecos/sincronizacao.log 2>&1 &
```

---

## 🚨 PROBLEMAS COMUNS E SOLUÇÕES

### Problema 1: Erro de Permissão em storage/

**Solução:**
```bash
cd /home/dattapro/modulos/cestadeprecos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Problema 2: "No application encryption key has been specified"

**Solução:**
```bash
php artisan key:generate
```

### Problema 3: Erro de Conexão com PostgreSQL

**Solução:**
```bash
# Verificar se PostgreSQL está rodando
sudo systemctl status postgresql

# Verificar credenciais no .env
cat .env | grep DB_

# Testar conexão manual
psql -U minhadattatech_user -d minhadattatech_db
```

### Problema 4: Migrations Falham

**Solução:**
```bash
# Ver status
php artisan migrate:status

# Tentar novamente
php artisan migrate --force

# Se persistir, verificar logs
tail -f storage/logs/laravel.log
```

### Problema 5: Módulo Não Inicia (Porta em Uso)

**Solução:**
```bash
# Ver o que está usando a porta 8001
sudo lsof -i :8001

# Matar processo se necessário
sudo kill -9 [PID]

# Reiniciar serviço
sudo systemctl restart cestadeprecos.service
```

### Problema 6: Erro 500 no Navegador

**Solução:**
```bash
# Limpar cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Verificar logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# Verificar permissões
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## 📚 COMANDOS ÚTEIS PÓS-RESTAURAÇÃO

### Administração

```bash
# Ver todos os tenants
cd /home/dattapro/minhadattatech
php artisan tinker
>>> App\Models\Tenant::all();

# Ver módulos ativos
>>> App\Models\TenantActiveModule::all();
```

### Monitoramento

```bash
# Ver processos PHP
ps aux | grep php

# Ver uso de memória
free -h

# Ver espaço em disco
df -h

# Ver logs em tempo real
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log
```

### Backup

```bash
# Backup completo do banco
pg_dumpall -U postgres -f /tmp/backup_completo_$(date +%Y%m%d).sql

# Backup de um banco específico
pg_dump -U minhadattatech_user -d minhadattatech_db -F custom -f /tmp/minhadattatech_$(date +%Y%m%d).dump
```

---

## 🔐 CONFIGURAÇÕES DE SEGURANÇA ADICIONAIS

### Firewall (UFW)

```bash
# Habilitar firewall
sudo ufw enable

# Permitir SSH
sudo ufw allow 22/tcp

# Permitir HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Bloquear portas dos módulos (acesso apenas local)
sudo ufw deny 8001/tcp
sudo ufw deny 8002/tcp

# Verificar status
sudo ufw status
```

### SSL/HTTPS com Let's Encrypt

```bash
# Instalar Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obter certificado
sudo certbot --nginx -d dattatech.com.br -d *.dattatech.com.br -d dattapro.online -d *.dattapro.online

# Renovação automática
sudo certbot renew --dry-run
```

---

## 📞 CONTATO E SUPORTE

**Se você é Claude Code e chegou até aqui:**

1. ✅ Se tudo funcionou, o sistema está restaurado!
2. ⚠️ Se algo falhou, verifique a seção "Problemas Comuns"
3. 📖 Consulte `TENANTS.md` para configuração específica de tenants
4. 📖 Consulte `ESTUDO_COMPLETO_BACKUP_GITHUB.md` para detalhes técnicos

**Para humanos que precisam de suporte:**
- Email: suporte@dattatech.com.br
- GitHub Issues: https://github.com/dattatechb2b/Vinicius_cesta_de_pre-os/issues

---

## ✅ CHECKLIST DE RESTAURAÇÃO COMPLETA

Marque conforme completa cada passo:

### Preparação
- [ ] Ubuntu instalado e atualizado
- [ ] Todos os pacotes instalados (PHP, PostgreSQL, Node, etc.)
- [ ] Diretórios criados

### Banco de Dados
- [ ] PostgreSQL configurado
- [ ] Bancos criados (minhadattatech_db + 6 tenants)
- [ ] Usuário criado com privilégios

### Sistema Core
- [ ] Repositório clonado
- [ ] .env configurado
- [ ] Dependências instaladas
- [ ] Migrations executadas
- [ ] Cache gerado

### Módulo Cesta de Preços
- [ ] Repositório clonado
- [ ] .env configurado
- [ ] Dependências instaladas
- [ ] Migrations executadas
- [ ] Serviço systemd criado e ativo

### Módulo NFe (Opcional)
- [ ] Repositório clonado
- [ ] .env configurado
- [ ] Dependências instaladas
- [ ] Serviço systemd criado e ativo

### Infraestrutura
- [ ] Nginx configurado
- [ ] Supervisor configurado
- [ ] Cron jobs configurados
- [ ] Firewall configurado
- [ ] SSL configurado (se necessário)

### Verificação
- [ ] Todos os serviços rodando
- [ ] Portas respondendo (8001, 8002, 80)
- [ ] Banco acessível
- [ ] Logs sem erros críticos
- [ ] Interface web acessível

### Dados (Opcional)
- [ ] CATMAT importado
- [ ] CMED importado
- [ ] PNCP sincronizado (opcional)

---

## 🎯 RESULTADO ESPERADO

Após completar todos os passos, você deve ter:

1. ✅ Sistema Core rodando na porta 80
2. ✅ Módulo Cesta de Preços rodando na porta 8001
3. ✅ Módulo NFe rodando na porta 8002 (se instalado)
4. ✅ Todos os 7 bancos de dados criados
5. ✅ 28 tabelas criadas (85+ migrations executadas)
6. ✅ Todos os serviços iniciando automaticamente
7. ✅ Sistema acessível via navegador
8. ✅ Workers processando jobs
9. ✅ Cron jobs agendados
10. ✅ Logs funcionando

**Teste final de sucesso:**
```bash
# Deve retornar HTML da aplicação
curl http://localhost:8001/

# Deve mostrar "running"
sudo systemctl status cestadeprecos.service | grep "active (running)"

# Deve retornar número > 0
psql -U minhadattatech_user -d minhadattatech_db -c "SELECT COUNT(*) FROM tenants;" -t
```

Se TODOS os testes passarem: **🎉 SISTEMA RESTAURADO COM SUCESSO!**

---

**FIM DO GUIA DE RESTAURAÇÃO**

**Versão:** 1.0.0
**Data:** 31/10/2025
**Autor:** Claude Code (Anthropic)
**Propósito:** Permitir restauração completa do sistema por qualquer Claude Code futuro

**⚠️ IMPORTANTE:** Mantenha este documento atualizado sempre que houver mudanças significativas na arquitetura!

---
