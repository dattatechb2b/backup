# Módulo Cesta de Preços

## Informações do Módulo

- **Nome**: Cesta de Preços
- **Versão**: 1.0.0
- **Framework**: Laravel 11
- **Porta**: 8001
- **Prefixo das Tabelas**: `cp_`
- **Banco de Dados**: minhadatattech_db (compartilhado com sistema principal)

## Arquitetura

Este módulo funciona de forma completamente isolada, sendo acessível apenas através do proxy interno do MinhaDataTech.

### Características de Segurança:
- ✅ **Isolamento Total**: Erros neste módulo não afetam o sistema principal
- ✅ **Sem Acesso Externo**: Bloqueado por middleware InternalOnly
- ✅ **Autenticação via Token**: Comunicação segura com sistema principal
- ✅ **Prefixo de Tabelas**: Todas as tabelas usam prefixo `cp_` para separação lógica

## Comandos Úteis

### Iniciar o Módulo
```bash
cd /home/dattapro/modulos/cestadeprecos
php artisan serve --host=0.0.0.0 --port=8001
```

### Verificar Configuração do Banco
```bash
php artisan db:check-setup
```

### Executar Migrations
```bash
php artisan migrate
```

## Estrutura de Diretórios

```
cestadeprecos/
├── app/
│   ├── Http/
│   │   └── Middleware/
│   │       └── InternalOnly.php    # Middleware de segurança
│   └── Console/
│       └── Commands/
│           └── CheckDatabaseSetup.php  # Comando para verificar DB
├── routes/
│   └── web.php                     # Rotas do módulo
├── resources/
│   └── views/
│       └── dashboard.blade.php     # Dashboard placeholder
└── .env                            # Configurações do ambiente
```

## Rotas Disponíveis

- `GET /health` - Health check do módulo
- `GET /` - Dashboard principal (placeholder)
- `GET /info` - Informações de debug (apenas em desenvolvimento)
- `GET /api/status` - Status da API

## Integração com MinhaDataTech

O módulo é acessado através do proxy interno:
- URL no Desktop: `/module-proxy/cestadeprecos/`
- O proxy adiciona headers com informações do tenant e usuário
- Token de autenticação é gerado e validado automaticamente

## Status Atual

✅ **Infraestrutura Configurada**:
- Banco de dados PostgreSQL configurado
- Tabelas com prefixo `cp_` criadas
- Middleware de segurança implementado
- Sistema de proxy configurado
- Health check disponível

⏳ **Aguardando Especificações**:
- Funcionalidades específicas do módulo
- Modelos de dados
- Regras de negócio
- Interface do usuário

## Notas de Desenvolvimento

Este módulo foi configurado sem funcionalidades concretas, aguardando especificações detalhadas do cliente. A estrutura base está pronta para receber a implementação das funcionalidades quando as especificações forem fornecidas.