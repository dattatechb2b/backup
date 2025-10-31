-- =========================================================================
-- SCRIPT DE CRIAÇÃO DO BANCO CATASALTAS
-- Data: 2025-10-20
-- Tenant: Catas Altas (ID: 1)
-- =========================================================================
-- ESTE SCRIPT PODE SER FACILMENTE REVERTIDO USANDO O SCRIPT DE ROLLBACK
-- =========================================================================

-- PASSO 1: Criar usuário PostgreSQL
CREATE USER catasaltas_user WITH PASSWORD 'trNKvgQpzp1Of5pNFLzmNzNe4Nd5Z5Pq';

-- PASSO 2: Criar banco de dados
CREATE DATABASE catasaltas_db;

-- PASSO 3: Conectar ao banco e dar permissões
\c catasaltas_db

GRANT ALL ON SCHEMA public TO catasaltas_user;
GRANT ALL ON ALL TABLES IN SCHEMA public TO catasaltas_user;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO catasaltas_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO catasaltas_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO catasaltas_user;

-- =========================================================================
-- FIM DO SCRIPT
-- =========================================================================
-- PRÓXIMO PASSO: Rodar migrations do Laravel
-- Comando: cd /home/dattapro/modulos/cestadeprecos && php artisan migrate --database=pgsql
-- =========================================================================
