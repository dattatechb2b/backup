-- =========================================================================
-- SCRIPT DE ROLLBACK - REVERTER CRIAÇÃO DO BANCO CATASALTAS
-- Data: 2025-10-20
-- =========================================================================
-- ESTE SCRIPT REMOVE COMPLETAMENTE TUDO QUE FOI CRIADO
-- USE APENAS SE PRECISAR REVERTER A CRIAÇÃO DO BANCO
-- =========================================================================

-- AVISO: Todas as conexões serão encerradas e o banco será DELETADO
-- =========================================================================

-- PASSO 1: Encerrar todas as conexões ao banco (se houver)
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = 'catasaltas_db'
AND pid <> pg_backend_pid();

-- PASSO 2: Dropar o banco de dados
DROP DATABASE IF EXISTS catasaltas_db;

-- PASSO 3: Dropar o usuário
DROP USER IF EXISTS catasaltas_user;

-- =========================================================================
-- FIM DO ROLLBACK
-- =========================================================================
-- O banco e usuário foram COMPLETAMENTE removidos do sistema
-- =========================================================================
