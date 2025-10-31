--
-- PostgreSQL database dump
--

\restrict lJXqn7qN6FCYg1HKaYPqxc3RwhB5CzYloef6yvHBwORTHXuFhnypthRl8r0N9O7

-- Dumped from database version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'SQL_ASCII';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cp_arp_cabecalhos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_arp_cabecalhos (
    id bigint NOT NULL,
    numero_ata character varying(50) NOT NULL,
    ano_ata integer,
    orgao_gerenciador character varying(255) NOT NULL,
    cnpj_orgao character varying(18) NOT NULL,
    uasg character varying(20),
    ano_compra integer NOT NULL,
    sequencial_compra integer NOT NULL,
    vigencia_inicio date,
    vigencia_fim date,
    situacao character varying(20) NOT NULL,
    fornecedor_razao text,
    fornecedor_cnpj character varying(18),
    fonte_url text,
    payload_json jsonb,
    coletado_em timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    coletado_por bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN cp_arp_cabecalhos.numero_ata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.numero_ata IS 'Número da ata (ex: 001/2025)';


--
-- Name: COLUMN cp_arp_cabecalhos.ano_ata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.ano_ata IS 'Ano da ata';


--
-- Name: COLUMN cp_arp_cabecalhos.orgao_gerenciador; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.orgao_gerenciador IS 'Nome do órgão gerenciador';


--
-- Name: COLUMN cp_arp_cabecalhos.cnpj_orgao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.cnpj_orgao IS 'CNPJ do órgão (14 dígitos)';


--
-- Name: COLUMN cp_arp_cabecalhos.uasg; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.uasg IS 'Código UASG';


--
-- Name: COLUMN cp_arp_cabecalhos.ano_compra; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.ano_compra IS 'Ano da compra no PNCP';


--
-- Name: COLUMN cp_arp_cabecalhos.sequencial_compra; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.sequencial_compra IS 'Sequencial da compra no PNCP';


--
-- Name: COLUMN cp_arp_cabecalhos.vigencia_inicio; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.vigencia_inicio IS 'Data início vigência';


--
-- Name: COLUMN cp_arp_cabecalhos.vigencia_fim; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.vigencia_fim IS 'Data fim vigência';


--
-- Name: COLUMN cp_arp_cabecalhos.situacao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.situacao IS 'Vigente ou Expirada';


--
-- Name: COLUMN cp_arp_cabecalhos.fornecedor_razao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.fornecedor_razao IS 'Razão social do fornecedor';


--
-- Name: COLUMN cp_arp_cabecalhos.fornecedor_cnpj; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.fornecedor_cnpj IS 'CNPJ do fornecedor';


--
-- Name: COLUMN cp_arp_cabecalhos.fonte_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.fonte_url IS 'Link PNCP da ata';


--
-- Name: COLUMN cp_arp_cabecalhos.payload_json; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.payload_json IS 'JSON bruto da API PNCP (auditoria)';


--
-- Name: COLUMN cp_arp_cabecalhos.coletado_em; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.coletado_em IS 'Data/hora da coleta';


--
-- Name: COLUMN cp_arp_cabecalhos.coletado_por; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_cabecalhos.coletado_por IS 'ID do usuário que coletou';


--
-- Name: cp_arp_cabecalhos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_arp_cabecalhos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_arp_cabecalhos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_arp_cabecalhos_id_seq OWNED BY public.cp_arp_cabecalhos.id;


--
-- Name: cp_arp_itens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_arp_itens (
    id bigint NOT NULL,
    ata_id bigint NOT NULL,
    catmat character varying(20),
    descricao text NOT NULL,
    unidade character varying(50) NOT NULL,
    preco_unitario numeric(15,4) NOT NULL,
    quantidade_registrada numeric(15,4),
    lote character varying(50),
    badge_confianca character varying(10) DEFAULT 'ALTA'::character varying NOT NULL,
    coletado_em timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN cp_arp_itens.catmat; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.catmat IS 'Código CATMAT';


--
-- Name: COLUMN cp_arp_itens.descricao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.descricao IS 'Descrição do item';


--
-- Name: COLUMN cp_arp_itens.unidade; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.unidade IS 'Unidade de medida normalizada';


--
-- Name: COLUMN cp_arp_itens.preco_unitario; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.preco_unitario IS 'Preço unitário oficial';


--
-- Name: COLUMN cp_arp_itens.quantidade_registrada; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.quantidade_registrada IS 'Quantidade registrada na ata';


--
-- Name: COLUMN cp_arp_itens.lote; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.lote IS 'Número do lote (se houver)';


--
-- Name: COLUMN cp_arp_itens.badge_confianca; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.badge_confianca IS 'Sempre ALTA para ARP';


--
-- Name: COLUMN cp_arp_itens.coletado_em; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_arp_itens.coletado_em IS 'Data/hora da coleta';


--
-- Name: cp_arp_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_arp_itens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_arp_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_arp_itens_id_seq OWNED BY public.cp_arp_itens.id;


--
-- Name: cp_cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cp_cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cp_catalogo_produtos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_catalogo_produtos (
    id bigint NOT NULL,
    descricao_padrao text NOT NULL,
    catmat character varying(20),
    catser character varying(20),
    unidade character varying(50) NOT NULL,
    especificacao text,
    tags text,
    ativo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN cp_catalogo_produtos.descricao_padrao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catalogo_produtos.descricao_padrao IS 'Descrição padronizada do órgão';


--
-- Name: COLUMN cp_catalogo_produtos.catmat; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catalogo_produtos.catmat IS 'Código CATMAT';


--
-- Name: COLUMN cp_catalogo_produtos.catser; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catalogo_produtos.catser IS 'Código CATSER';


--
-- Name: COLUMN cp_catalogo_produtos.unidade; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catalogo_produtos.unidade IS 'Unidade de medida normalizada';


--
-- Name: COLUMN cp_catalogo_produtos.especificacao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catalogo_produtos.especificacao IS 'Especificação técnica detalhada';


--
-- Name: COLUMN cp_catalogo_produtos.tags; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catalogo_produtos.tags IS 'Tags separadas por vírgula (material_escritorio, informatica)';


--
-- Name: cp_catalogo_produtos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_catalogo_produtos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_catalogo_produtos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_catalogo_produtos_id_seq OWNED BY public.cp_catalogo_produtos.id;


--
-- Name: cp_catmat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_catmat (
    id bigint NOT NULL,
    codigo character varying(20) NOT NULL,
    titulo text NOT NULL,
    tipo character varying(10) DEFAULT 'CATMAT'::character varying NOT NULL,
    caminho_hierarquia text,
    unidade_padrao character varying(50),
    fonte character varying(50) DEFAULT 'CSV_OFICIAL'::character varying NOT NULL,
    primeira_ocorrencia_em timestamp(0) without time zone,
    ultima_ocorrencia_em timestamp(0) without time zone,
    contador_ocorrencias integer DEFAULT 0 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN cp_catmat.codigo; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.codigo IS 'Código CATMAT ou CATSER';


--
-- Name: COLUMN cp_catmat.titulo; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.titulo IS 'Descrição do item';


--
-- Name: COLUMN cp_catmat.tipo; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.tipo IS 'CATMAT ou CATSER';


--
-- Name: COLUMN cp_catmat.caminho_hierarquia; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.caminho_hierarquia IS 'Hierarquia do catálogo';


--
-- Name: COLUMN cp_catmat.unidade_padrao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.unidade_padrao IS 'Unidade padrão (UN, KG, etc)';


--
-- Name: COLUMN cp_catmat.fonte; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.fonte IS 'CSV_OFICIAL ou PNCP_AUTO';


--
-- Name: COLUMN cp_catmat.primeira_ocorrencia_em; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.primeira_ocorrencia_em IS 'Primeira vez que apareceu';


--
-- Name: COLUMN cp_catmat.ultima_ocorrencia_em; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.ultima_ocorrencia_em IS 'Última vez que apareceu';


--
-- Name: COLUMN cp_catmat.contador_ocorrencias; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_catmat.contador_ocorrencias IS 'Quantas vezes apareceu';


--
-- Name: cp_catmat_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_catmat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_catmat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_catmat_id_seq OWNED BY public.cp_catmat.id;


--
-- Name: cp_coleta_ecommerce_itens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_coleta_ecommerce_itens (
    id bigint NOT NULL,
    coleta_ecommerce_id bigint NOT NULL,
    orcamento_item_id bigint NOT NULL,
    preco_unitario numeric(15,2) NOT NULL,
    preco_total numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_coleta_ecommerce_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_coleta_ecommerce_itens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_coleta_ecommerce_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_coleta_ecommerce_itens_id_seq OWNED BY public.cp_coleta_ecommerce_itens.id;


--
-- Name: cp_coletas_ecommerce; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_coletas_ecommerce (
    id bigint NOT NULL,
    orcamento_id bigint NOT NULL,
    nome_site character varying(255) NOT NULL,
    url_site text NOT NULL,
    eh_intermediacao boolean DEFAULT false NOT NULL,
    data_consulta date NOT NULL,
    hora_consulta time(0) without time zone NOT NULL,
    inclui_frete boolean DEFAULT false NOT NULL,
    arquivo_print character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_coletas_ecommerce_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_coletas_ecommerce_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_coletas_ecommerce_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_coletas_ecommerce_id_seq OWNED BY public.cp_coletas_ecommerce.id;


--
-- Name: cp_consultas_pncp_cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_consultas_pncp_cache (
    id bigint NOT NULL,
    hash_consulta character varying(64) NOT NULL,
    tipo character varying(20) NOT NULL,
    parametros jsonb NOT NULL,
    resposta_json jsonb,
    coletado_em timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ttl_expira_em timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN cp_consultas_pncp_cache.hash_consulta; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_consultas_pncp_cache.hash_consulta IS 'MD5 dos parâmetros da consulta';


--
-- Name: COLUMN cp_consultas_pncp_cache.tipo; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_consultas_pncp_cache.tipo IS 'ARP, CONTRATO, CATMAT';


--
-- Name: COLUMN cp_consultas_pncp_cache.parametros; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_consultas_pncp_cache.parametros IS 'Parâmetros da consulta (termo, período, etc)';


--
-- Name: COLUMN cp_consultas_pncp_cache.resposta_json; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_consultas_pncp_cache.resposta_json IS 'JSON completo da resposta PNCP';


--
-- Name: COLUMN cp_consultas_pncp_cache.coletado_em; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_consultas_pncp_cache.coletado_em IS 'Data/hora da consulta';


--
-- Name: COLUMN cp_consultas_pncp_cache.ttl_expira_em; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_consultas_pncp_cache.ttl_expira_em IS 'Data/hora de expiração do cache';


--
-- Name: cp_consultas_pncp_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_consultas_pncp_cache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_consultas_pncp_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_consultas_pncp_cache_id_seq OWNED BY public.cp_consultas_pncp_cache.id;


--
-- Name: cp_contratacao_similar_itens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_contratacao_similar_itens (
    id bigint NOT NULL,
    contratacao_similar_id bigint NOT NULL,
    orcamento_item_id bigint NOT NULL,
    descricao text NOT NULL,
    catmat character varying(20),
    unidade character varying(20) NOT NULL,
    quantidade_referencia numeric(15,2) DEFAULT '1'::numeric NOT NULL,
    preco_unitario numeric(15,2) NOT NULL,
    preco_total numeric(15,2) NOT NULL,
    nivel_confianca character varying(255) DEFAULT 'Unitário'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cp_contratacao_similar_itens_nivel_confianca_check CHECK (((nivel_confianca)::text = ANY ((ARRAY['Unitário'::character varying, 'Estimado'::character varying, 'Global'::character varying])::text[])))
);


--
-- Name: cp_contratacao_similar_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_contratacao_similar_itens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_contratacao_similar_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_contratacao_similar_itens_id_seq OWNED BY public.cp_contratacao_similar_itens.id;


--
-- Name: cp_contratacoes_similares; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_contratacoes_similares (
    id bigint NOT NULL,
    orcamento_id bigint NOT NULL,
    ente_publico character varying(255) NOT NULL,
    tipo character varying(100) NOT NULL,
    numero_processo character varying(100) NOT NULL,
    eh_registro_precos boolean DEFAULT false NOT NULL,
    data_publicacao date NOT NULL,
    local_publicacao character varying(50),
    link_oficial text NOT NULL,
    arquivo_pdf character varying(255),
    arquivo_hash character varying(255),
    arquivo_tamanho integer,
    data_coleta timestamp(0) without time zone,
    usuario_coleta character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_contratacoes_similares_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_contratacoes_similares_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_contratacoes_similares_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_contratacoes_similares_id_seq OWNED BY public.cp_contratacoes_similares.id;


--
-- Name: cp_contratos_pncp; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_contratos_pncp (
    id bigint NOT NULL,
    numero_controle_pncp character varying(255) NOT NULL,
    tipo character varying(255) NOT NULL,
    objeto_contrato text NOT NULL,
    valor_global numeric(15,2),
    numero_parcelas integer,
    valor_unitario_estimado numeric(15,2),
    unidade_medida character varying(255),
    orgao_cnpj character varying(255),
    orgao_razao_social character varying(255),
    orgao_uf character varying(2),
    orgao_municipio character varying(255),
    data_publicacao_pncp date,
    data_vigencia_inicio date,
    data_vigencia_fim date,
    confiabilidade character varying(255) DEFAULT 'baixa'::character varying,
    valor_estimado boolean DEFAULT false NOT NULL,
    sincronizado_em timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    fornecedor_cnpj character varying(14),
    fornecedor_razao_social character varying(255),
    fornecedor_id bigint,
    CONSTRAINT cp_contratos_pncp_confiabilidade_check CHECK (((confiabilidade)::text = ANY ((ARRAY['alta'::character varying, 'media'::character varying, 'baixa'::character varying])::text[])))
);


--
-- Name: cp_contratos_pncp_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_contratos_pncp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_contratos_pncp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_contratos_pncp_id_seq OWNED BY public.cp_contratos_pncp.id;


--
-- Name: cp_fornecedor_itens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_fornecedor_itens (
    id bigint NOT NULL,
    fornecedor_id bigint NOT NULL,
    descricao character varying(255) NOT NULL,
    codigo_catmat character varying(50),
    unidade character varying(20),
    preco_referencia numeric(15,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_cp_fornecedor_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_cp_fornecedor_itens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_cp_fornecedor_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_cp_fornecedor_itens_id_seq OWNED BY public.cp_fornecedor_itens.id;


--
-- Name: cp_fornecedores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_fornecedores (
    id bigint NOT NULL,
    tipo_documento character varying(255) DEFAULT 'CNPJ'::character varying NOT NULL,
    numero_documento character varying(20) NOT NULL,
    razao_social character varying(255) NOT NULL,
    nome_fantasia character varying(255),
    inscricao_estadual character varying(50),
    inscricao_municipal character varying(50),
    telefone character varying(20),
    celular character varying(20),
    email character varying(255),
    site character varying(255),
    cep character varying(10),
    logradouro character varying(255) NOT NULL,
    numero character varying(20),
    complemento character varying(100),
    bairro character varying(100) NOT NULL,
    cidade character varying(100) NOT NULL,
    uf character varying(2) NOT NULL,
    observacoes text,
    user_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cp_cp_fornecedores_tipo_documento_check CHECK (((tipo_documento)::text = ANY ((ARRAY['CNPJ'::character varying, 'CPF'::character varying])::text[])))
);


--
-- Name: cp_cp_fornecedores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_cp_fornecedores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_cp_fornecedores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_cp_fornecedores_id_seq OWNED BY public.cp_fornecedores.id;


--
-- Name: cp_failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: cp_failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_failed_jobs_id_seq OWNED BY public.cp_failed_jobs.id;


--
-- Name: cp_historico_precos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_historico_precos (
    id bigint NOT NULL,
    catalogo_produto_id bigint,
    catmat character varying(20),
    fonte character varying(20) NOT NULL,
    fonte_url text,
    preco_unitario numeric(15,4) NOT NULL,
    badge character varying(10),
    data_coleta timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN cp_historico_precos.catmat; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_historico_precos.catmat IS 'Código CATMAT (se não vinculado a catálogo)';


--
-- Name: COLUMN cp_historico_precos.fonte; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_historico_precos.fonte IS 'ARP, CONTRATO, MANUAL';


--
-- Name: COLUMN cp_historico_precos.fonte_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_historico_precos.fonte_url IS 'Link PNCP da fonte';


--
-- Name: COLUMN cp_historico_precos.preco_unitario; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_historico_precos.preco_unitario IS 'Preço unitário coletado';


--
-- Name: COLUMN cp_historico_precos.badge; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_historico_precos.badge IS '🟢, 🟡, 🔴';


--
-- Name: COLUMN cp_historico_precos.data_coleta; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_historico_precos.data_coleta IS 'Data/hora da coleta';


--
-- Name: cp_historico_precos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_historico_precos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_historico_precos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_historico_precos_id_seq OWNED BY public.cp_historico_precos.id;


--
-- Name: cp_itens_orcamento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_itens_orcamento (
    id bigint NOT NULL,
    orcamento_id bigint NOT NULL,
    lote_id bigint,
    descricao text NOT NULL,
    medida_fornecimento character varying(50) NOT NULL,
    quantidade numeric(15,4) NOT NULL,
    indicacao_marca character varying(255),
    tipo character varying(255) DEFAULT 'servico'::character varying NOT NULL,
    alterar_cdf boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    preco_unitario numeric(15,2),
    fonte_preco character varying(50),
    fonte_url text,
    fonte_detalhes jsonb,
    CONSTRAINT cp_itens_orcamento_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['produto'::character varying, 'servico'::character varying])::text[])))
);


--
-- Name: COLUMN cp_itens_orcamento.fonte_preco; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_itens_orcamento.fonte_preco IS 'ARP, CATALOGO, CONTRATO, MANUAL';


--
-- Name: COLUMN cp_itens_orcamento.fonte_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_itens_orcamento.fonte_url IS 'Link PNCP/ComprasGov da fonte';


--
-- Name: COLUMN cp_itens_orcamento.fonte_detalhes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_itens_orcamento.fonte_detalhes IS 'JSON com detalhes: ata, uasg, badge, preco_coletado, catmat, coletado_em';


--
-- Name: cp_itens_orcamento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_itens_orcamento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_itens_orcamento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_itens_orcamento_id_seq OWNED BY public.cp_itens_orcamento.id;


--
-- Name: cp_job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: cp_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: cp_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_jobs_id_seq OWNED BY public.cp_jobs.id;


--
-- Name: cp_lotes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_lotes (
    id bigint NOT NULL,
    orcamento_id bigint NOT NULL,
    numero integer NOT NULL,
    nome character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: cp_lotes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_lotes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_lotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_lotes_id_seq OWNED BY public.cp_lotes.id;


--
-- Name: cp_migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: cp_migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_migrations_id_seq OWNED BY public.cp_migrations.id;


--
-- Name: cp_orcamentos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_orcamentos (
    id bigint NOT NULL,
    nome character varying(255) NOT NULL,
    referencia_externa character varying(255),
    objeto text NOT NULL,
    orgao_interessado character varying(255),
    tipo_criacao character varying(255) DEFAULT 'do_zero'::character varying NOT NULL,
    orcamento_origem_id bigint,
    status character varying(255) DEFAULT 'pendente'::character varying NOT NULL,
    data_conclusao timestamp(0) without time zone,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    numero character varying(50),
    metodo_juizo_critico character varying(255) DEFAULT 'saneamento_desvio_padrao'::character varying NOT NULL,
    metodo_obtencao_preco character varying(255) DEFAULT 'media_mediana'::character varying NOT NULL,
    casas_decimais character varying(255) DEFAULT 'duas'::character varying NOT NULL,
    observacao_justificativa text,
    anexo_pdf character varying(255),
    orcamentista_nome character varying(255),
    orcamentista_cpf_cnpj character varying(18),
    orcamentista_matricula character varying(255),
    orcamentista_portaria character varying(255),
    orcamentista_razao_social character varying(255),
    orcamentista_endereco text,
    orcamentista_cep character varying(9),
    orcamentista_cidade character varying(255),
    orcamentista_uf character varying(2),
    orcamentista_setor character varying(255),
    brasao_path character varying(255),
    CONSTRAINT cp_orcamentos_casas_decimais_check CHECK (((casas_decimais)::text = ANY ((ARRAY['duas'::character varying, 'quatro'::character varying])::text[]))),
    CONSTRAINT cp_orcamentos_metodo_juizo_critico_check CHECK (((metodo_juizo_critico)::text = ANY ((ARRAY['saneamento_desvio_padrao'::character varying, 'saneamento_percentual'::character varying])::text[]))),
    CONSTRAINT cp_orcamentos_metodo_obtencao_preco_check CHECK (((metodo_obtencao_preco)::text = ANY ((ARRAY['media_mediana'::character varying, 'mediana_todas'::character varying, 'media_todas'::character varying, 'menor_preco'::character varying])::text[]))),
    CONSTRAINT cp_orcamentos_status_check CHECK (((status)::text = ANY ((ARRAY['pendente'::character varying, 'realizado'::character varying])::text[]))),
    CONSTRAINT cp_orcamentos_tipo_criacao_check CHECK (((tipo_criacao)::text = ANY ((ARRAY['do_zero'::character varying, 'outro_orcamento'::character varying, 'documento'::character varying])::text[])))
);


--
-- Name: COLUMN cp_orcamentos.nome; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.nome IS 'Nome do Orçamento (obrigatório)';


--
-- Name: COLUMN cp_orcamentos.referencia_externa; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.referencia_externa IS 'Referência Externa (opcional)';


--
-- Name: COLUMN cp_orcamentos.objeto; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.objeto IS 'Objeto do orçamento (obrigatório)';


--
-- Name: COLUMN cp_orcamentos.orgao_interessado; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.orgao_interessado IS 'Órgão Interessado (opcional)';


--
-- Name: COLUMN cp_orcamentos.tipo_criacao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.tipo_criacao IS 'Como foi criado: do zero, de outro orçamento ou de documento';


--
-- Name: COLUMN cp_orcamentos.orcamento_origem_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.orcamento_origem_id IS 'ID do orçamento de origem (quando criado a partir de outro)';


--
-- Name: COLUMN cp_orcamentos.data_conclusao; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.data_conclusao IS 'Data em que foi marcado como realizado';


--
-- Name: COLUMN cp_orcamentos.user_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.user_id IS 'Usuário que criou o orçamento';


--
-- Name: COLUMN cp_orcamentos.deleted_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.deleted_at IS 'Exclusão suave';


--
-- Name: COLUMN cp_orcamentos.numero; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.numero IS 'Número do orçamento no formato ID/ANO (ex: 00001/2025)';


--
-- Name: COLUMN cp_orcamentos.metodo_juizo_critico; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.metodo_juizo_critico IS 'Método para saneamento das amostras';


--
-- Name: COLUMN cp_orcamentos.metodo_obtencao_preco; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.metodo_obtencao_preco IS 'Método para calcular preço estimado';


--
-- Name: COLUMN cp_orcamentos.casas_decimais; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.casas_decimais IS 'Número de casas decimais para valores';


--
-- Name: COLUMN cp_orcamentos.observacao_justificativa; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.observacao_justificativa IS 'Observação ou justificativa geral do orçamento';


--
-- Name: COLUMN cp_orcamentos.anexo_pdf; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.cp_orcamentos.anexo_pdf IS 'Caminho do arquivo PDF anexado';


--
-- Name: cp_orcamentos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_orcamentos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_orcamentos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_orcamentos_id_seq OWNED BY public.cp_orcamentos.id;


--
-- Name: cp_orientacoes_tecnicas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_orientacoes_tecnicas (
    id bigint NOT NULL,
    numero character varying(10) NOT NULL,
    titulo text NOT NULL,
    conteudo text NOT NULL,
    ordem integer DEFAULT 0 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_orientacoes_tecnicas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_orientacoes_tecnicas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_orientacoes_tecnicas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_orientacoes_tecnicas_id_seq OWNED BY public.cp_orientacoes_tecnicas.id;


--
-- Name: cp_password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: cp_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: cp_solicitacao_cdf_itens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_solicitacao_cdf_itens (
    id bigint NOT NULL,
    solicitacao_cdf_id bigint NOT NULL,
    orcamento_item_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_solicitacao_cdf_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_solicitacao_cdf_itens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_solicitacao_cdf_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_solicitacao_cdf_itens_id_seq OWNED BY public.cp_solicitacao_cdf_itens.id;


--
-- Name: cp_solicitacoes_cdf; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_solicitacoes_cdf (
    id bigint NOT NULL,
    orcamento_id bigint NOT NULL,
    cnpj character varying(18) NOT NULL,
    razao_social character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    telefone character varying(20),
    justificativa_fornecedor_unico boolean DEFAULT false NOT NULL,
    justificativa_produto_exclusivo boolean DEFAULT false NOT NULL,
    justificativa_urgencia boolean DEFAULT false NOT NULL,
    justificativa_melhor_preco boolean DEFAULT false NOT NULL,
    justificativa_outro text,
    prazo_resposta_dias integer NOT NULL,
    prazo_entrega_dias integer NOT NULL,
    frete character varying(255) NOT NULL,
    observacao text,
    fornecedor_valido boolean DEFAULT true NOT NULL,
    arquivo_cnpj character varying(255),
    status character varying(50) DEFAULT 'Pendente'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    validacao_respostas json,
    descarte_motivo text,
    cancelamento_motivos json,
    cancelamento_obs text,
    descarte_motivos json,
    descarte_obs text,
    comprovante_path character varying(255),
    cotacao_path character varying(255),
    data_resposta timestamp(0) without time zone,
    metodo_coleta character varying(20),
    CONSTRAINT cp_solicitacoes_cdf_frete_check CHECK (((frete)::text = ANY ((ARRAY['CIF'::character varying, 'FOB'::character varying])::text[]))),
    CONSTRAINT solicitacoes_cdf_metodo_coleta_check CHECK (((metodo_coleta)::text = ANY ((ARRAY['email'::character varying, 'presencial'::character varying])::text[]))),
    CONSTRAINT solicitacoes_cdf_status_check CHECK (((status)::text = ANY ((ARRAY['Pendente'::character varying, 'Enviado'::character varying, 'Aguardando resposta'::character varying, 'Respondido'::character varying, 'Validada'::character varying, 'Descartada'::character varying, 'Vencido'::character varying, 'Cancelado'::character varying])::text[])))
);


--
-- Name: cp_solicitacoes_cdf_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_solicitacoes_cdf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_solicitacoes_cdf_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_solicitacoes_cdf_id_seq OWNED BY public.cp_solicitacoes_cdf.id;


--
-- Name: cp_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cp_users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cp_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cp_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cp_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cp_users_id_seq OWNED BY public.cp_users.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: orcamentos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orcamentos (
    id bigint NOT NULL,
    nome character varying(255) NOT NULL,
    referencia_externa character varying(255),
    objeto text NOT NULL,
    orgao_interessado character varying(255),
    status character varying(255) DEFAULT 'realizado'::character varying NOT NULL,
    data_conclusao timestamp(0) without time zone,
    user_id bigint NOT NULL,
    tenant_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT orcamentos_status_check CHECK (((status)::text = ANY ((ARRAY['pendente'::character varying, 'realizado'::character varying])::text[])))
);


--
-- Name: orcamentos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orcamentos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: orcamentos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orcamentos_id_seq OWNED BY public.orcamentos.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permissions (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: role_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.role_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.role_permissions_id_seq OWNED BY public.role_permissions.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: tenant_active_modules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tenant_active_modules (
    id bigint NOT NULL,
    tenant_id bigint NOT NULL,
    module_key character varying(50) NOT NULL,
    parent_module_key character varying(50),
    enabled boolean DEFAULT true NOT NULL,
    settings json,
    activation_date timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tenant_active_modules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tenant_active_modules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tenant_active_modules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tenant_active_modules_id_seq OWNED BY public.tenant_active_modules.id;


--
-- Name: tenant_auth_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tenant_auth_tokens (
    id bigint NOT NULL,
    tenant_id bigint NOT NULL,
    token character varying(255) NOT NULL,
    service character varying(50) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tenant_auth_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tenant_auth_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tenant_auth_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tenant_auth_tokens_id_seq OWNED BY public.tenant_auth_tokens.id;


--
-- Name: tenants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tenants (
    id bigint NOT NULL,
    crm_customer_id integer,
    technical_client_id integer,
    subdomain character varying(100),
    custom_domain character varying(255),
    database_name character varying(100) NOT NULL,
    company_name character varying(255) NOT NULL,
    primary_domain character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    max_users integer DEFAULT 5 NOT NULL,
    current_users integer DEFAULT 0 NOT NULL,
    allow_user_registration boolean DEFAULT false NOT NULL,
    require_email_verification boolean DEFAULT true NOT NULL,
    allow_password_reset boolean DEFAULT true NOT NULL,
    last_user_activity timestamp(0) without time zone,
    CONSTRAINT tenants_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'suspended'::character varying])::text[])))
);


--
-- Name: tenants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tenants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tenants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tenants_id_seq OWNED BY public.tenants.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company character varying(255),
    role_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    avatar character varying(255),
    phone character varying(255),
    last_login_at timestamp(0) without time zone,
    tenant_id bigint,
    role character varying(255) DEFAULT 'user'::character varying NOT NULL,
    created_by_technical boolean DEFAULT false NOT NULL,
    username character varying(255) NOT NULL,
    recovery_email character varying(255)
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: cp_arp_cabecalhos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_cabecalhos ALTER COLUMN id SET DEFAULT nextval('public.cp_arp_cabecalhos_id_seq'::regclass);


--
-- Name: cp_arp_itens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_itens ALTER COLUMN id SET DEFAULT nextval('public.cp_arp_itens_id_seq'::regclass);


--
-- Name: cp_catalogo_produtos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_catalogo_produtos ALTER COLUMN id SET DEFAULT nextval('public.cp_catalogo_produtos_id_seq'::regclass);


--
-- Name: cp_catmat id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_catmat ALTER COLUMN id SET DEFAULT nextval('public.cp_catmat_id_seq'::regclass);


--
-- Name: cp_coleta_ecommerce_itens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coleta_ecommerce_itens ALTER COLUMN id SET DEFAULT nextval('public.cp_coleta_ecommerce_itens_id_seq'::regclass);


--
-- Name: cp_coletas_ecommerce id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coletas_ecommerce ALTER COLUMN id SET DEFAULT nextval('public.cp_coletas_ecommerce_id_seq'::regclass);


--
-- Name: cp_consultas_pncp_cache id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_consultas_pncp_cache ALTER COLUMN id SET DEFAULT nextval('public.cp_consultas_pncp_cache_id_seq'::regclass);


--
-- Name: cp_contratacao_similar_itens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacao_similar_itens ALTER COLUMN id SET DEFAULT nextval('public.cp_contratacao_similar_itens_id_seq'::regclass);


--
-- Name: cp_contratacoes_similares id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacoes_similares ALTER COLUMN id SET DEFAULT nextval('public.cp_contratacoes_similares_id_seq'::regclass);


--
-- Name: cp_contratos_pncp id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratos_pncp ALTER COLUMN id SET DEFAULT nextval('public.cp_contratos_pncp_id_seq'::regclass);


--
-- Name: cp_failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.cp_failed_jobs_id_seq'::regclass);


--
-- Name: cp_fornecedor_itens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_fornecedor_itens ALTER COLUMN id SET DEFAULT nextval('public.cp_cp_fornecedor_itens_id_seq'::regclass);


--
-- Name: cp_fornecedores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_fornecedores ALTER COLUMN id SET DEFAULT nextval('public.cp_cp_fornecedores_id_seq'::regclass);


--
-- Name: cp_historico_precos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_historico_precos ALTER COLUMN id SET DEFAULT nextval('public.cp_historico_precos_id_seq'::regclass);


--
-- Name: cp_itens_orcamento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_itens_orcamento ALTER COLUMN id SET DEFAULT nextval('public.cp_itens_orcamento_id_seq'::regclass);


--
-- Name: cp_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_jobs ALTER COLUMN id SET DEFAULT nextval('public.cp_jobs_id_seq'::regclass);


--
-- Name: cp_lotes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_lotes ALTER COLUMN id SET DEFAULT nextval('public.cp_lotes_id_seq'::regclass);


--
-- Name: cp_migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_migrations ALTER COLUMN id SET DEFAULT nextval('public.cp_migrations_id_seq'::regclass);


--
-- Name: cp_orcamentos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orcamentos ALTER COLUMN id SET DEFAULT nextval('public.cp_orcamentos_id_seq'::regclass);


--
-- Name: cp_orientacoes_tecnicas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orientacoes_tecnicas ALTER COLUMN id SET DEFAULT nextval('public.cp_orientacoes_tecnicas_id_seq'::regclass);


--
-- Name: cp_solicitacao_cdf_itens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacao_cdf_itens ALTER COLUMN id SET DEFAULT nextval('public.cp_solicitacao_cdf_itens_id_seq'::regclass);


--
-- Name: cp_solicitacoes_cdf id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacoes_cdf ALTER COLUMN id SET DEFAULT nextval('public.cp_solicitacoes_cdf_id_seq'::regclass);


--
-- Name: cp_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_users ALTER COLUMN id SET DEFAULT nextval('public.cp_users_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: orcamentos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orcamentos ALTER COLUMN id SET DEFAULT nextval('public.orcamentos_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: role_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions ALTER COLUMN id SET DEFAULT nextval('public.role_permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: tenant_active_modules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_active_modules ALTER COLUMN id SET DEFAULT nextval('public.tenant_active_modules_id_seq'::regclass);


--
-- Name: tenant_auth_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_auth_tokens ALTER COLUMN id SET DEFAULT nextval('public.tenant_auth_tokens_id_seq'::regclass);


--
-- Name: tenants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants ALTER COLUMN id SET DEFAULT nextval('public.tenants_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: cp_arp_cabecalhos cp_arp_cabecalhos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_cabecalhos
    ADD CONSTRAINT cp_arp_cabecalhos_pkey PRIMARY KEY (id);


--
-- Name: cp_arp_itens cp_arp_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_itens
    ADD CONSTRAINT cp_arp_itens_pkey PRIMARY KEY (id);


--
-- Name: cp_cache_locks cp_cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_cache_locks
    ADD CONSTRAINT cp_cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cp_cache cp_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_cache
    ADD CONSTRAINT cp_cache_pkey PRIMARY KEY (key);


--
-- Name: cp_catalogo_produtos cp_catalogo_produtos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_catalogo_produtos
    ADD CONSTRAINT cp_catalogo_produtos_pkey PRIMARY KEY (id);


--
-- Name: cp_catmat cp_catmat_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_catmat
    ADD CONSTRAINT cp_catmat_codigo_unique UNIQUE (codigo);


--
-- Name: cp_catmat cp_catmat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_catmat
    ADD CONSTRAINT cp_catmat_pkey PRIMARY KEY (id);


--
-- Name: cp_coleta_ecommerce_itens cp_coleta_ecommerce_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coleta_ecommerce_itens
    ADD CONSTRAINT cp_coleta_ecommerce_itens_pkey PRIMARY KEY (id);


--
-- Name: cp_coletas_ecommerce cp_coletas_ecommerce_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coletas_ecommerce
    ADD CONSTRAINT cp_coletas_ecommerce_pkey PRIMARY KEY (id);


--
-- Name: cp_consultas_pncp_cache cp_consultas_pncp_cache_hash_consulta_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_consultas_pncp_cache
    ADD CONSTRAINT cp_consultas_pncp_cache_hash_consulta_unique UNIQUE (hash_consulta);


--
-- Name: cp_consultas_pncp_cache cp_consultas_pncp_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_consultas_pncp_cache
    ADD CONSTRAINT cp_consultas_pncp_cache_pkey PRIMARY KEY (id);


--
-- Name: cp_contratacao_similar_itens cp_contratacao_similar_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacao_similar_itens
    ADD CONSTRAINT cp_contratacao_similar_itens_pkey PRIMARY KEY (id);


--
-- Name: cp_contratacoes_similares cp_contratacoes_similares_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacoes_similares
    ADD CONSTRAINT cp_contratacoes_similares_pkey PRIMARY KEY (id);


--
-- Name: cp_contratos_pncp cp_contratos_pncp_numero_controle_pncp_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratos_pncp
    ADD CONSTRAINT cp_contratos_pncp_numero_controle_pncp_unique UNIQUE (numero_controle_pncp);


--
-- Name: cp_contratos_pncp cp_contratos_pncp_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratos_pncp
    ADD CONSTRAINT cp_contratos_pncp_pkey PRIMARY KEY (id);


--
-- Name: cp_fornecedor_itens cp_cp_fornecedor_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_fornecedor_itens
    ADD CONSTRAINT cp_cp_fornecedor_itens_pkey PRIMARY KEY (id);


--
-- Name: cp_fornecedores cp_cp_fornecedores_numero_documento_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_fornecedores
    ADD CONSTRAINT cp_cp_fornecedores_numero_documento_unique UNIQUE (numero_documento);


--
-- Name: cp_fornecedores cp_cp_fornecedores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_fornecedores
    ADD CONSTRAINT cp_cp_fornecedores_pkey PRIMARY KEY (id);


--
-- Name: cp_failed_jobs cp_failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_failed_jobs
    ADD CONSTRAINT cp_failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: cp_failed_jobs cp_failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_failed_jobs
    ADD CONSTRAINT cp_failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: cp_historico_precos cp_historico_precos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_historico_precos
    ADD CONSTRAINT cp_historico_precos_pkey PRIMARY KEY (id);


--
-- Name: cp_itens_orcamento cp_itens_orcamento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_itens_orcamento
    ADD CONSTRAINT cp_itens_orcamento_pkey PRIMARY KEY (id);


--
-- Name: cp_job_batches cp_job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_job_batches
    ADD CONSTRAINT cp_job_batches_pkey PRIMARY KEY (id);


--
-- Name: cp_jobs cp_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_jobs
    ADD CONSTRAINT cp_jobs_pkey PRIMARY KEY (id);


--
-- Name: cp_lotes cp_lotes_orcamento_id_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_lotes
    ADD CONSTRAINT cp_lotes_orcamento_id_numero_unique UNIQUE (orcamento_id, numero);


--
-- Name: cp_lotes cp_lotes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_lotes
    ADD CONSTRAINT cp_lotes_pkey PRIMARY KEY (id);


--
-- Name: cp_migrations cp_migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_migrations
    ADD CONSTRAINT cp_migrations_pkey PRIMARY KEY (id);


--
-- Name: cp_orcamentos cp_orcamentos_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orcamentos
    ADD CONSTRAINT cp_orcamentos_numero_unique UNIQUE (numero);


--
-- Name: cp_orcamentos cp_orcamentos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orcamentos
    ADD CONSTRAINT cp_orcamentos_pkey PRIMARY KEY (id);


--
-- Name: cp_orientacoes_tecnicas cp_orientacoes_tecnicas_numero_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orientacoes_tecnicas
    ADD CONSTRAINT cp_orientacoes_tecnicas_numero_unique UNIQUE (numero);


--
-- Name: cp_orientacoes_tecnicas cp_orientacoes_tecnicas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orientacoes_tecnicas
    ADD CONSTRAINT cp_orientacoes_tecnicas_pkey PRIMARY KEY (id);


--
-- Name: cp_password_reset_tokens cp_password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_password_reset_tokens
    ADD CONSTRAINT cp_password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: cp_sessions cp_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_sessions
    ADD CONSTRAINT cp_sessions_pkey PRIMARY KEY (id);


--
-- Name: cp_solicitacao_cdf_itens cp_solicitacao_cdf_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacao_cdf_itens
    ADD CONSTRAINT cp_solicitacao_cdf_itens_pkey PRIMARY KEY (id);


--
-- Name: cp_solicitacoes_cdf cp_solicitacoes_cdf_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacoes_cdf
    ADD CONSTRAINT cp_solicitacoes_cdf_pkey PRIMARY KEY (id);


--
-- Name: cp_users cp_users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_users
    ADD CONSTRAINT cp_users_email_unique UNIQUE (email);


--
-- Name: cp_users cp_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_users
    ADD CONSTRAINT cp_users_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: orcamentos orcamentos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orcamentos
    ADD CONSTRAINT orcamentos_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (id);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tenant_active_modules tenant_active_modules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_active_modules
    ADD CONSTRAINT tenant_active_modules_pkey PRIMARY KEY (id);


--
-- Name: tenant_active_modules tenant_active_modules_tenant_id_module_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_active_modules
    ADD CONSTRAINT tenant_active_modules_tenant_id_module_key_unique UNIQUE (tenant_id, module_key);


--
-- Name: tenant_auth_tokens tenant_auth_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_auth_tokens
    ADD CONSTRAINT tenant_auth_tokens_pkey PRIMARY KEY (id);


--
-- Name: tenant_auth_tokens tenant_auth_tokens_tenant_id_service_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_auth_tokens
    ADD CONSTRAINT tenant_auth_tokens_tenant_id_service_unique UNIQUE (tenant_id, service);


--
-- Name: tenants tenants_custom_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_custom_domain_unique UNIQUE (custom_domain);


--
-- Name: tenants tenants_database_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_database_name_unique UNIQUE (database_name);


--
-- Name: tenants tenants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_subdomain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_subdomain_unique UNIQUE (subdomain);


--
-- Name: cp_arp_cabecalhos unique_ata; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_cabecalhos
    ADD CONSTRAINT unique_ata UNIQUE (cnpj_orgao, ano_compra, sequencial_compra, numero_ata);


--
-- Name: users users_email_tenant_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_tenant_id_unique UNIQUE (email, tenant_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: contratos_pncp_objeto_gin; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contratos_pncp_objeto_gin ON public.cp_contratos_pncp USING gin (to_tsvector('portuguese'::regconfig, objeto_contrato));


--
-- Name: cp_arp_cabecalhos_cnpj_orgao_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_cabecalhos_cnpj_orgao_index ON public.cp_arp_cabecalhos USING btree (cnpj_orgao);


--
-- Name: cp_arp_cabecalhos_coletado_em_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_cabecalhos_coletado_em_index ON public.cp_arp_cabecalhos USING btree (coletado_em);


--
-- Name: cp_arp_cabecalhos_situacao_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_cabecalhos_situacao_index ON public.cp_arp_cabecalhos USING btree (situacao);


--
-- Name: cp_arp_cabecalhos_uasg_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_cabecalhos_uasg_index ON public.cp_arp_cabecalhos USING btree (uasg);


--
-- Name: cp_arp_cabecalhos_vigencia_fim_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_cabecalhos_vigencia_fim_index ON public.cp_arp_cabecalhos USING btree (vigencia_fim);


--
-- Name: cp_arp_itens_ata_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_itens_ata_id_index ON public.cp_arp_itens USING btree (ata_id);


--
-- Name: cp_arp_itens_catmat_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_itens_catmat_index ON public.cp_arp_itens USING btree (catmat);


--
-- Name: cp_arp_itens_coletado_em_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_arp_itens_coletado_em_index ON public.cp_arp_itens USING btree (coletado_em);


--
-- Name: cp_catalogo_produtos_ativo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_catalogo_produtos_ativo_index ON public.cp_catalogo_produtos USING btree (ativo);


--
-- Name: cp_catalogo_produtos_catmat_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_catalogo_produtos_catmat_index ON public.cp_catalogo_produtos USING btree (catmat);


--
-- Name: cp_catalogo_produtos_catser_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_catalogo_produtos_catser_index ON public.cp_catalogo_produtos USING btree (catser);


--
-- Name: cp_catmat_ativo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_catmat_ativo_index ON public.cp_catmat USING btree (ativo);


--
-- Name: cp_catmat_codigo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_catmat_codigo_index ON public.cp_catmat USING btree (codigo);


--
-- Name: cp_catmat_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_catmat_tipo_index ON public.cp_catmat USING btree (tipo);


--
-- Name: cp_coleta_ecommerce_itens_coleta_ecommerce_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_coleta_ecommerce_itens_coleta_ecommerce_id_index ON public.cp_coleta_ecommerce_itens USING btree (coleta_ecommerce_id);


--
-- Name: cp_coleta_ecommerce_itens_orcamento_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_coleta_ecommerce_itens_orcamento_item_id_index ON public.cp_coleta_ecommerce_itens USING btree (orcamento_item_id);


--
-- Name: cp_coletas_ecommerce_data_consulta_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_coletas_ecommerce_data_consulta_index ON public.cp_coletas_ecommerce USING btree (data_consulta);


--
-- Name: cp_coletas_ecommerce_orcamento_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_coletas_ecommerce_orcamento_id_index ON public.cp_coletas_ecommerce USING btree (orcamento_id);


--
-- Name: cp_consultas_pncp_cache_coletado_em_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_consultas_pncp_cache_coletado_em_index ON public.cp_consultas_pncp_cache USING btree (coletado_em);


--
-- Name: cp_consultas_pncp_cache_hash_consulta_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_consultas_pncp_cache_hash_consulta_index ON public.cp_consultas_pncp_cache USING btree (hash_consulta);


--
-- Name: cp_consultas_pncp_cache_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_consultas_pncp_cache_tipo_index ON public.cp_consultas_pncp_cache USING btree (tipo);


--
-- Name: cp_consultas_pncp_cache_ttl_expira_em_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_consultas_pncp_cache_ttl_expira_em_index ON public.cp_consultas_pncp_cache USING btree (ttl_expira_em);


--
-- Name: cp_contratacao_similar_itens_contratacao_similar_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratacao_similar_itens_contratacao_similar_id_index ON public.cp_contratacao_similar_itens USING btree (contratacao_similar_id);


--
-- Name: cp_contratacao_similar_itens_orcamento_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratacao_similar_itens_orcamento_item_id_index ON public.cp_contratacao_similar_itens USING btree (orcamento_item_id);


--
-- Name: cp_contratacoes_similares_data_publicacao_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratacoes_similares_data_publicacao_index ON public.cp_contratacoes_similares USING btree (data_publicacao);


--
-- Name: cp_contratacoes_similares_ente_publico_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratacoes_similares_ente_publico_index ON public.cp_contratacoes_similares USING btree (ente_publico);


--
-- Name: cp_contratacoes_similares_orcamento_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratacoes_similares_orcamento_id_index ON public.cp_contratacoes_similares USING btree (orcamento_id);


--
-- Name: cp_contratos_pncp_confiabilidade_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_confiabilidade_index ON public.cp_contratos_pncp USING btree (confiabilidade);


--
-- Name: cp_contratos_pncp_data_publicacao_pncp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_data_publicacao_pncp_index ON public.cp_contratos_pncp USING btree (data_publicacao_pncp);


--
-- Name: cp_contratos_pncp_data_publicacao_pncp_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_data_publicacao_pncp_tipo_index ON public.cp_contratos_pncp USING btree (data_publicacao_pncp, tipo);


--
-- Name: cp_contratos_pncp_fornecedor_cnpj_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_fornecedor_cnpj_index ON public.cp_contratos_pncp USING btree (fornecedor_cnpj);


--
-- Name: cp_contratos_pncp_fornecedor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_fornecedor_id_index ON public.cp_contratos_pncp USING btree (fornecedor_id);


--
-- Name: cp_contratos_pncp_orgao_cnpj_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_orgao_cnpj_index ON public.cp_contratos_pncp USING btree (orgao_cnpj);


--
-- Name: cp_contratos_pncp_orgao_uf_data_publicacao_pncp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_orgao_uf_data_publicacao_pncp_index ON public.cp_contratos_pncp USING btree (orgao_uf, data_publicacao_pncp);


--
-- Name: cp_contratos_pncp_orgao_uf_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_orgao_uf_index ON public.cp_contratos_pncp USING btree (orgao_uf);


--
-- Name: cp_contratos_pncp_tipo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_contratos_pncp_tipo_index ON public.cp_contratos_pncp USING btree (tipo);


--
-- Name: cp_cp_fornecedor_itens_descricao_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_cp_fornecedor_itens_descricao_index ON public.cp_fornecedor_itens USING btree (descricao);


--
-- Name: cp_cp_fornecedor_itens_fornecedor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_cp_fornecedor_itens_fornecedor_id_index ON public.cp_fornecedor_itens USING btree (fornecedor_id);


--
-- Name: cp_cp_fornecedores_cidade_uf_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_cp_fornecedores_cidade_uf_index ON public.cp_fornecedores USING btree (cidade, uf);


--
-- Name: cp_cp_fornecedores_numero_documento_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_cp_fornecedores_numero_documento_index ON public.cp_fornecedores USING btree (numero_documento);


--
-- Name: cp_cp_fornecedores_razao_social_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_cp_fornecedores_razao_social_index ON public.cp_fornecedores USING btree (razao_social);


--
-- Name: cp_historico_precos_catalogo_produto_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_historico_precos_catalogo_produto_id_index ON public.cp_historico_precos USING btree (catalogo_produto_id);


--
-- Name: cp_historico_precos_catmat_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_historico_precos_catmat_index ON public.cp_historico_precos USING btree (catmat);


--
-- Name: cp_historico_precos_data_coleta_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_historico_precos_data_coleta_index ON public.cp_historico_precos USING btree (data_coleta);


--
-- Name: cp_historico_precos_fonte_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_historico_precos_fonte_index ON public.cp_historico_precos USING btree (fonte);


--
-- Name: cp_itens_orcamento_lote_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_itens_orcamento_lote_id_index ON public.cp_itens_orcamento USING btree (lote_id);


--
-- Name: cp_itens_orcamento_orcamento_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_itens_orcamento_orcamento_id_index ON public.cp_itens_orcamento USING btree (orcamento_id);


--
-- Name: cp_jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_jobs_queue_index ON public.cp_jobs USING btree (queue);


--
-- Name: cp_lotes_orcamento_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_lotes_orcamento_id_index ON public.cp_lotes USING btree (orcamento_id);


--
-- Name: cp_orcamentos_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_orcamentos_created_at_index ON public.cp_orcamentos USING btree (created_at);


--
-- Name: cp_orcamentos_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_orcamentos_status_index ON public.cp_orcamentos USING btree (status);


--
-- Name: cp_orcamentos_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_orcamentos_user_id_index ON public.cp_orcamentos USING btree (user_id);


--
-- Name: cp_orientacoes_tecnicas_ativo_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_orientacoes_tecnicas_ativo_index ON public.cp_orientacoes_tecnicas USING btree (ativo);


--
-- Name: cp_orientacoes_tecnicas_numero_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_orientacoes_tecnicas_numero_index ON public.cp_orientacoes_tecnicas USING btree (numero);


--
-- Name: cp_orientacoes_tecnicas_ordem_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_orientacoes_tecnicas_ordem_index ON public.cp_orientacoes_tecnicas USING btree (ordem);


--
-- Name: cp_sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_sessions_last_activity_index ON public.cp_sessions USING btree (last_activity);


--
-- Name: cp_sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_sessions_user_id_index ON public.cp_sessions USING btree (user_id);


--
-- Name: cp_solicitacao_cdf_itens_orcamento_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_solicitacao_cdf_itens_orcamento_item_id_index ON public.cp_solicitacao_cdf_itens USING btree (orcamento_item_id);


--
-- Name: cp_solicitacao_cdf_itens_solicitacao_cdf_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_solicitacao_cdf_itens_solicitacao_cdf_id_index ON public.cp_solicitacao_cdf_itens USING btree (solicitacao_cdf_id);


--
-- Name: cp_solicitacoes_cdf_cnpj_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_solicitacoes_cdf_cnpj_index ON public.cp_solicitacoes_cdf USING btree (cnpj);


--
-- Name: cp_solicitacoes_cdf_orcamento_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_solicitacoes_cdf_orcamento_id_index ON public.cp_solicitacoes_cdf USING btree (orcamento_id);


--
-- Name: cp_solicitacoes_cdf_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cp_solicitacoes_cdf_status_index ON public.cp_solicitacoes_cdf USING btree (status);


--
-- Name: idx_arp_itens_descricao_fulltext; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_arp_itens_descricao_fulltext ON public.cp_arp_itens USING gin (to_tsvector('portuguese'::regconfig, descricao));


--
-- Name: idx_catalogo_descricao_fulltext; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_catalogo_descricao_fulltext ON public.cp_catalogo_produtos USING gin (to_tsvector('portuguese'::regconfig, descricao_padrao));


--
-- Name: idx_catalogo_tags_fulltext; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_catalogo_tags_fulltext ON public.cp_catalogo_produtos USING gin (to_tsvector('portuguese'::regconfig, tags));


--
-- Name: idx_catmat_titulo_fulltext; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_catmat_titulo_fulltext ON public.cp_catmat USING gin (to_tsvector('portuguese'::regconfig, titulo));


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: tenant_active_modules_module_key_enabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenant_active_modules_module_key_enabled_index ON public.tenant_active_modules USING btree (module_key, enabled);


--
-- Name: tenant_active_modules_module_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenant_active_modules_module_key_index ON public.tenant_active_modules USING btree (module_key);


--
-- Name: tenant_active_modules_parent_module_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenant_active_modules_parent_module_key_index ON public.tenant_active_modules USING btree (parent_module_key);


--
-- Name: tenant_active_modules_tenant_id_enabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenant_active_modules_tenant_id_enabled_index ON public.tenant_active_modules USING btree (tenant_id, enabled);


--
-- Name: tenant_auth_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenant_auth_tokens_expires_at_index ON public.tenant_auth_tokens USING btree (expires_at);


--
-- Name: tenants_company_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenants_company_name_index ON public.tenants USING btree (company_name);


--
-- Name: tenants_crm_customer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenants_crm_customer_id_index ON public.tenants USING btree (crm_customer_id);


--
-- Name: tenants_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenants_status_created_at_index ON public.tenants USING btree (status, created_at);


--
-- Name: tenants_technical_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenants_technical_client_id_index ON public.tenants USING btree (technical_client_id);


--
-- Name: unique_arp_item; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX unique_arp_item ON public.cp_arp_itens USING btree (ata_id, COALESCE(catmat, ''::character varying), COALESCE(lote, ''::character varying), md5(descricao));


--
-- Name: users_tenant_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_tenant_id_is_active_index ON public.users USING btree (tenant_id, is_active);


--
-- Name: cp_arp_itens cp_arp_itens_ata_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_itens
    ADD CONSTRAINT cp_arp_itens_ata_id_foreign FOREIGN KEY (ata_id) REFERENCES public.cp_arp_cabecalhos(id) ON DELETE CASCADE;


--
-- Name: cp_arp_itens cp_arp_itens_catmat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_arp_itens
    ADD CONSTRAINT cp_arp_itens_catmat_foreign FOREIGN KEY (catmat) REFERENCES public.cp_catmat(codigo) ON DELETE SET NULL;


--
-- Name: cp_catalogo_produtos cp_catalogo_produtos_catmat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_catalogo_produtos
    ADD CONSTRAINT cp_catalogo_produtos_catmat_foreign FOREIGN KEY (catmat) REFERENCES public.cp_catmat(codigo) ON DELETE SET NULL;


--
-- Name: cp_coleta_ecommerce_itens cp_coleta_ecommerce_itens_coleta_ecommerce_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coleta_ecommerce_itens
    ADD CONSTRAINT cp_coleta_ecommerce_itens_coleta_ecommerce_id_foreign FOREIGN KEY (coleta_ecommerce_id) REFERENCES public.cp_coletas_ecommerce(id) ON DELETE CASCADE;


--
-- Name: cp_coleta_ecommerce_itens cp_coleta_ecommerce_itens_orcamento_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coleta_ecommerce_itens
    ADD CONSTRAINT cp_coleta_ecommerce_itens_orcamento_item_id_foreign FOREIGN KEY (orcamento_item_id) REFERENCES public.cp_itens_orcamento(id) ON DELETE CASCADE;


--
-- Name: cp_coletas_ecommerce cp_coletas_ecommerce_orcamento_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_coletas_ecommerce
    ADD CONSTRAINT cp_coletas_ecommerce_orcamento_id_foreign FOREIGN KEY (orcamento_id) REFERENCES public.cp_orcamentos(id) ON DELETE CASCADE;


--
-- Name: cp_contratacao_similar_itens cp_contratacao_similar_itens_contratacao_similar_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacao_similar_itens
    ADD CONSTRAINT cp_contratacao_similar_itens_contratacao_similar_id_foreign FOREIGN KEY (contratacao_similar_id) REFERENCES public.cp_contratacoes_similares(id) ON DELETE CASCADE;


--
-- Name: cp_contratacao_similar_itens cp_contratacao_similar_itens_orcamento_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacao_similar_itens
    ADD CONSTRAINT cp_contratacao_similar_itens_orcamento_item_id_foreign FOREIGN KEY (orcamento_item_id) REFERENCES public.cp_itens_orcamento(id) ON DELETE CASCADE;


--
-- Name: cp_contratacoes_similares cp_contratacoes_similares_orcamento_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratacoes_similares
    ADD CONSTRAINT cp_contratacoes_similares_orcamento_id_foreign FOREIGN KEY (orcamento_id) REFERENCES public.cp_orcamentos(id) ON DELETE CASCADE;


--
-- Name: cp_contratos_pncp cp_contratos_pncp_fornecedor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_contratos_pncp
    ADD CONSTRAINT cp_contratos_pncp_fornecedor_id_foreign FOREIGN KEY (fornecedor_id) REFERENCES public.cp_fornecedores(id) ON DELETE SET NULL;


--
-- Name: cp_fornecedor_itens cp_fornecedor_itens_fornecedor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_fornecedor_itens
    ADD CONSTRAINT cp_fornecedor_itens_fornecedor_id_foreign FOREIGN KEY (fornecedor_id) REFERENCES public.cp_fornecedores(id) ON DELETE CASCADE;


--
-- Name: cp_historico_precos cp_historico_precos_catalogo_produto_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_historico_precos
    ADD CONSTRAINT cp_historico_precos_catalogo_produto_id_foreign FOREIGN KEY (catalogo_produto_id) REFERENCES public.cp_catalogo_produtos(id) ON DELETE CASCADE;


--
-- Name: cp_historico_precos cp_historico_precos_catmat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_historico_precos
    ADD CONSTRAINT cp_historico_precos_catmat_foreign FOREIGN KEY (catmat) REFERENCES public.cp_catmat(codigo) ON DELETE SET NULL;


--
-- Name: cp_itens_orcamento cp_itens_orcamento_lote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_itens_orcamento
    ADD CONSTRAINT cp_itens_orcamento_lote_id_foreign FOREIGN KEY (lote_id) REFERENCES public.cp_lotes(id) ON DELETE SET NULL;


--
-- Name: cp_itens_orcamento cp_itens_orcamento_orcamento_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_itens_orcamento
    ADD CONSTRAINT cp_itens_orcamento_orcamento_id_foreign FOREIGN KEY (orcamento_id) REFERENCES public.cp_orcamentos(id) ON DELETE CASCADE;


--
-- Name: cp_lotes cp_lotes_orcamento_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_lotes
    ADD CONSTRAINT cp_lotes_orcamento_id_foreign FOREIGN KEY (orcamento_id) REFERENCES public.cp_orcamentos(id) ON DELETE CASCADE;


--
-- Name: cp_orcamentos cp_orcamentos_orcamento_origem_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orcamentos
    ADD CONSTRAINT cp_orcamentos_orcamento_origem_id_foreign FOREIGN KEY (orcamento_origem_id) REFERENCES public.cp_orcamentos(id) ON DELETE SET NULL;


--
-- Name: cp_orcamentos cp_orcamentos_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_orcamentos
    ADD CONSTRAINT cp_orcamentos_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.cp_users(id) ON DELETE CASCADE;


--
-- Name: cp_solicitacao_cdf_itens cp_solicitacao_cdf_itens_orcamento_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacao_cdf_itens
    ADD CONSTRAINT cp_solicitacao_cdf_itens_orcamento_item_id_foreign FOREIGN KEY (orcamento_item_id) REFERENCES public.cp_itens_orcamento(id) ON DELETE CASCADE;


--
-- Name: cp_solicitacao_cdf_itens cp_solicitacao_cdf_itens_solicitacao_cdf_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacao_cdf_itens
    ADD CONSTRAINT cp_solicitacao_cdf_itens_solicitacao_cdf_id_foreign FOREIGN KEY (solicitacao_cdf_id) REFERENCES public.cp_solicitacoes_cdf(id) ON DELETE CASCADE;


--
-- Name: cp_solicitacoes_cdf cp_solicitacoes_cdf_orcamento_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cp_solicitacoes_cdf
    ADD CONSTRAINT cp_solicitacoes_cdf_orcamento_id_foreign FOREIGN KEY (orcamento_id) REFERENCES public.cp_orcamentos(id) ON DELETE CASCADE;


--
-- Name: orcamentos orcamentos_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orcamentos
    ADD CONSTRAINT orcamentos_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: orcamentos orcamentos_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orcamentos
    ADD CONSTRAINT orcamentos_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tenant_active_modules tenant_active_modules_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_active_modules
    ADD CONSTRAINT tenant_active_modules_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: tenant_auth_tokens tenant_auth_tokens_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenant_auth_tokens
    ADD CONSTRAINT tenant_auth_tokens_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: users users_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE SET NULL;


--
-- Name: users users_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict lJXqn7qN6FCYg1HKaYPqxc3RwhB5CzYloef6yvHBwORTHXuFhnypthRl8r0N9O7

--
-- PostgreSQL database dump
--

\restrict HTJDvYdiMxhcQw3zvO5qFSrgDGsdR8a2CwPSxNmFLnMFDiTYfhc3tElWHF7Iydh

-- Dumped from database version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'SQL_ASCII';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: cp_migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cp_migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
5	2025_09_30_143011_create_orcamentos_table	2
6	2025_10_01_082958_add_numero_to_orcamentos_table	3
7	2025_10_01_083056_create_orcamento_itens_table	4
8	2025_10_01_085759_add_configuracoes_to_orcamentos_table	5
9	2025_10_01_122006_create_cp_lotes_table	6
10	2025_10_01_122007_create_cp_itens_orcamento_table	6
11	2025_10_02_120518_create_contratos_pncp_table	7
12	2025_10_02_130020_create_orientacoes_tecnicas_table	8
13	2025_10_02_144047_create_coletas_ecommerce_table	9
14	2025_10_02_151228_create_solicitacoes_cdf_table	10
15	2025_10_02_153418_create_contratacoes_similares_table	11
16	2025_10_03_093113_create_fornecedores_table	12
17	2025_10_03_093141_create_fornecedor_itens_table	12
18	2025_10_06_150615_add_orcamentista_fields_to_orcamentos_table	13
19	2025_10_07_103420_add_preco_unitario_to_itens_orcamento_table	14
20	2025_10_07_133852_add_fornecedor_columns_to_contratos_pncp	15
26	2025_10_07_164801_add_validacao_fields_to_solicitacoes_cdf_table	16
27	2025_10_07_165021_add_primeiro_passo_fields_to_solicitacoes_cdf_table	16
30	2025_10_08_090626_add_fonte_preco_to_orcamento_itens_table	17
31	2025_10_08_090644_create_arp_cabecalhos_table	17
32	2025_10_08_090644_create_catmat_table	17
33	2025_10_08_090645_create_arp_itens_table	18
34	2025_10_08_090645_create_catalogo_produtos_table	18
35	2025_10_08_090645_create_historico_precos_table	18
36	2025_10_08_090646_create_consultas_pncp_cache_table	18
\.


--
-- Name: cp_migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cp_migrations_id_seq', 36, true);


--
-- PostgreSQL database dump complete
--

\unrestrict HTJDvYdiMxhcQw3zvO5qFSrgDGsdR8a2CwPSxNmFLnMFDiTYfhc3tElWHF7Iydh

