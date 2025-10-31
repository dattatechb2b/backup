# 🛒 Cesta de Preços - Sistema de Orçamento Estimativo

**Versão:** 2.0.0
**Framework:** Laravel 11.31
**PHP:** 8.2+
**Banco de Dados:** PostgreSQL 15+

---

## 📋 Índice

- [Sobre o Sistema](#-sobre-o-sistema)
- [Funcionalidades](#-funcionalidades)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Uso](#-uso)
- [APIs Integradas](#-apis-integradas)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Tecnologias](#-tecnologias)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)
- [Suporte](#-suporte)

---

## 📖 Sobre o Sistema

O **Cesta de Preços** é um sistema completo de elaboração de orçamentos estimativos para órgãos públicos, desenvolvido para facilitar a pesquisa de preços de mercado, gestão de cotações e geração de documentos oficiais.

### Principais Diferenciais:

- ✅ **Pesquisa Automatizada** de preços em múltiplas fontes
- ✅ **Integração com PNCP, Compras.gov e Portal da Transparência**
- ✅ **Geração Automática de PDFs** com layout oficial
- ✅ **Cotação Direta com Fornecedores (CDF)** via e-mail
- ✅ **OCR de Documentos** para extração de dados
- ✅ **Análise Crítica de Amostras** com justificativas
- ✅ **Importação de Planilhas** Excel/CSV com detecção automática

---

## 🚀 Funcionalidades

### 1. Pesquisa de Preços

- **Pesquisa Rápida:** Busca em PNCP, Compras.gov, LicitaCon
- **Catálogo de Produtos:** Integração com CATMAT
- **Mapa de Fornecedores:** Histórico de fornecedores por município
- **Mapa de Atas:** Contratações ativas no PNCP
- **Sites de E-commerce:** Coleta automatizada de preços online

### 2. Elaboração de Orçamentos

- **Criação do Zero:** Interface intuitiva para cadastro manual
- **Importação de Documentos:** Excel, Word, PDF com OCR
- **Gestão de Itens:** Adicionar, editar, remover, ordenar
- **Lotes:** Agrupamento de itens para licitação
- **Análise Crítica:** Justificativas técnicas para cada item
- **Preview em Tempo Real:** Visualização do PDF antes de concluir

### 3. Cotação com Fornecedores (CDF)

- **Solicitação por E-mail:** Envio automático de ofício
- **Gerenciamento Completo:** Primeiro e segundo passo
- **Formulário Online:** Fornecedores respondem via web
- **Importação de Documentos:** Upload de comprovantes (PDF, imagens)
- **Análise de Respostas:** Comparação automática de preços

### 4. Geração de Documentos

- **PDF de Orçamento:** Layout oficial com brasão personalizado
- **Ofício de Solicitação CDF:** Documento formal automático
- **Formulário de Cotação:** Para preenchimento pelo fornecedor
- **Relatórios Personalizados:** Exports em PDF/Excel

### 5. Gestão de Fornecedores

- **Cadastro Completo:** Dados, contatos, documentos
- **Integração PNCP:** Importação automática de fornecedores
- **Histórico de Cotações:** Rastreamento de participações
- **Notificações:** Sistema de avisos e lembretes

---

## 💻 Requisitos

### Servidor

- **Sistema Operacional:** Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- **PHP:** 8.2 ou superior
- **Banco de Dados:** PostgreSQL 15+
- **Servidor Web:** Nginx ou Apache
- **Redis:** (recomendado para cache)
- **Node.js:** 18+ (para compilação de assets)

### Extensões PHP Requeridas

```bash
php8.2-cli
php8.2-fpm
php8.2-pgsql
php8.2-mbstring
php8.2-xml
php8.2-curl
php8.2-zip
php8.2-gd
php8.2-redis
php8.2-intl
```

### Dependências Externas

- **Tesseract OCR:** Para reconhecimento de texto em imagens
- **Composer:** Gerenciador de dependências PHP
- **NPM:** Gerenciador de pacotes JavaScript

---

## 📦 Instalação

### 1. Clonar Repositório

```bash
git clone https://github.com/seu-usuario/cestadeprecos.git
cd cestadeprecos
```

### 2. Instalar Dependências do Sistema

#### Ubuntu/Debian:

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
    php8.2-redis php8.2-intl postgresql-15 redis-server nginx \
    tesseract-ocr tesseract-ocr-por composer nodejs npm
```

#### CentOS/RHEL:

```bash
sudo dnf install -y php82 php82-cli php82-fpm php82-pgsql \
    php82-mbstring php82-xml php82-curl php82-zip php82-gd \
    php82-redis php82-intl postgresql15-server redis nginx \
    tesseract tesseract-langpack-por composer nodejs npm
```

### 3. Configurar Banco de Dados

```bash
# Entrar no PostgreSQL
sudo -u postgres psql

# Criar banco e usuário
CREATE DATABASE cestadeprecos_db;
CREATE USER cestadeprecos_user WITH PASSWORD 'SuaSenhaForte123';
GRANT ALL PRIVILEGES ON DATABASE cestadeprecos_db TO cestadeprecos_user;
ALTER DATABASE cestadeprecos_db OWNER TO cestadeprecos_user;
\q
```

### 4. Configurar Projeto

```bash
# Instalar dependências PHP
composer install --no-dev --optimize-autoloader

# Instalar dependências JavaScript
npm install

# Copiar arquivo de ambiente
cp .env.example .env

# Editar .env e configurar:
# - DB_DATABASE
# - DB_USERNAME
# - DB_PASSWORD
# - APP_URL
nano .env

# Gerar chave da aplicação
php artisan key:generate

# Executar migrations
php artisan migrate --seed

# Criar link simbólico do storage
php artisan storage:link

# Compilar assets
npm run build

# Ajustar permissões
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 5. Configurar Servidor Web

#### Nginx:

```nginx
server {
    listen 80;
    server_name cestadeprecos.dominio.com.br;
    root /var/www/cestadeprecos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Salvar em `/etc/nginx/sites-available/cestadeprecos` e criar link:

```bash
sudo ln -s /etc/nginx/sites-available/cestadeprecos /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## ⚙️ Configuração

### Variáveis de Ambiente (.env)

#### Aplicação

```env
APP_NAME="Cesta de Preços"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cestadeprecos.dominio.com.br
APP_TIMEZONE=America/Sao_Paulo
```

#### Banco de Dados

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cestadeprecos_db
DB_USERNAME=cestadeprecos_user
DB_PASSWORD=SuaSenhaForte123
DB_TABLE_PREFIX=cp_
```

#### Cache e Sessão

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
SESSION_DRIVER=database
SESSION_CONNECTION=pgsql_sessions
```

#### E-mail

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.seuservidor.com.br
MAIL_PORT=587
MAIL_USERNAME=noreply@dominio.com.br
MAIL_PASSWORD=SenhaDoEmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@dominio.com.br"
MAIL_FROM_NAME="Cesta de Preços"
```

#### APIs (Opcional)

```env
# Portal da Transparência
PORTALTRANSPARENCIA_API_KEY=sua-chave-aqui

# PNCP (público, não precisa chave)
PNCP_CONNECT_TIMEOUT=5
PNCP_TIMEOUT=20
```

---

## 🎯 Uso

### Acessar o Sistema

```
http://cestadeprecos.dominio.com.br
```

### Usuário Padrão (após seed)

```
Email: admin@example.com
Senha: password
```

**⚠️ IMPORTANTE:** Altere as credenciais padrão imediatamente!

### Criar Primeiro Orçamento

1. Acesse "Novo Orçamento Estimativo"
2. Escolha "Criar do Zero" ou "Importar Documento"
3. Preencha dados básicos (nome, objeto, órgão)
4. Adicione itens via:
   - Pesquisa Rápida
   - Catálogo CATMAT
   - Cadastro Manual
   - Importação de Planilha
5. Configure lotes (se necessário)
6. Preencha "Dados do Orçamentista"
7. Clique em "Preview" para visualizar
8. Clique em "Concluir Orçamento"

---

## 🔌 APIs Integradas

### 1. PNCP (Portal Nacional de Contratações Públicas)

- **Endpoint:** `https://pncp.gov.br/api/pncp/v1/`
- **Uso:** Busca de contratos, fornecedores, atas
- **Autenticação:** Não requerida (API pública)

### 2. Portal da Transparência (CGU)

- **Endpoint:** `https://api.portaldatransparencia.gov.br/api-de-dados/`
- **Uso:** Consulta de contratos federais
- **Autenticação:** Chave de API (gratuita)
- **Como obter:** https://portaldatransparencia.gov.br/api-de-dados

### 3. Compras.gov

- **Endpoint:** `https://compras.dados.gov.br/docs/`
- **Uso:** Catálogos, itens, contratos
- **Autenticação:** Não requerida

### 4. ReceitaWS

- **Endpoint:** `https://www.receitaws.com.br/v1/cnpj/`
- **Uso:** Consulta de CNPJ
- **Autenticação:** Não requerida
- **Limite:** 3 consultas/minuto

### 5. ViaCEP

- **Endpoint:** `https://viacep.com.br/ws/`
- **Uso:** Consulta de CEP
- **Autenticação:** Não requerida

---

## 📁 Estrutura do Projeto

```
cestadeprecos/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── OrcamentoController.php
│   │   │   ├── PesquisaRapidaController.php
│   │   │   ├── CDFController.php
│   │   │   └── ...
│   │   └── Middleware/
│   ├── Models/
│   │   ├── Orcamento.php
│   │   ├── ItemOrcamento.php
│   │   ├── Fornecedor.php
│   │   └── ...
│   └── Services/
│       ├── PNCPService.php
│       ├── PortalTransparenciaService.php
│       └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── css/
│   ├── js/
│   └── images/
├── resources/
│   ├── views/
│   │   ├── orcamentos/
│   │   │   ├── elaborar.blade.php
│   │   │   ├── preview.blade.php
│   │   │   └── ...
│   │   ├── pesquisa-rapida.blade.php
│   │   └── emails/
│   │       └── cdf-solicitacao.blade.php
│   ├── css/
│   └── js/
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── app/
│   │   └── public/
│   │       ├── brasoes/
│   │       ├── pdfs/
│   │       └── uploads/
│   └── logs/
├── tests/
├── .env.example
├── composer.json
├── package.json
└── README.md
```

---

## 🛠️ Tecnologias

### Backend

- **Laravel 11.31** - Framework PHP
- **PostgreSQL 15+** - Banco de dados relacional
- **Redis** - Cache e filas
- **DomPDF / mPDF** - Geração de PDFs
- **PhpSpreadsheet** - Leitura de planilhas Excel
- **PhpWord** - Leitura de documentos Word
- **Tesseract OCR** - Reconhecimento de texto em imagens

### Frontend

- **Bootstrap 5.3** - Framework CSS
- **Font Awesome 6** - Ícones
- **Chart.js** - Gráficos
- **Vanilla JavaScript** - Interatividade
- **Vite** - Build tool

### Dependências Principais

```json
{
  "php": "^8.2",
  "laravel/framework": "^11.31",
  "barryvdh/laravel-dompdf": "^3.1",
  "mpdf/mpdf": "^8.2",
  "phpoffice/phpspreadsheet": "^5.1",
  "phpoffice/phpword": "^1.4",
  "simplesoftwareio/simple-qrcode": "^4.2",
  "thiagoalessio/tesseract_ocr": "^2.13"
}
```

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, siga estas etapas:

1. **Fork** o projeto
2. Crie uma **branch** para sua feature (`git checkout -b feature/MinhaFeature`)
3. **Commit** suas mudanças (`git commit -m 'Add: MinhaFeature'`)
4. **Push** para a branch (`git push origin feature/MinhaFeature`)
5. Abra um **Pull Request**

### Padrões de Código

- **PSR-12** para PHP
- **ESLint** para JavaScript
- **Comentários** em português
- **Commits** seguindo Conventional Commits

---

## 📄 Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

---

## 📞 Suporte

### Reportar Bugs

Abra uma [issue no GitHub](https://github.com/seu-usuario/cestadeprecos/issues) com:
- Descrição detalhada do problema
- Passos para reproduzir
- Screenshots (se aplicável)
- Logs relevantes

### Dúvidas

- **Documentação:** [Wiki do Projeto](https://github.com/seu-usuario/cestadeprecos/wiki)
- **E-mail:** suporte@dattatech.com.br

---

## 🎉 Créditos

Desenvolvido por **DattaTech** com ❤️

---

## 📝 Changelog

### v2.0.0 (16/10/2025)

- ✅ Refatoração completa do sistema
- ✅ Correção de erros de sintaxe JavaScript
- ✅ Integração com múltiplas APIs
- ✅ Novo design corporativo para e-mails CDF
- ✅ Backup automatizado completo
- ✅ Documentação completa para GitHub

### v1.0.0 (30/09/2025)

- 🎉 Lançamento inicial

---

**⭐ Se este projeto foi útil para você, considere dar uma estrela no GitHub!**
