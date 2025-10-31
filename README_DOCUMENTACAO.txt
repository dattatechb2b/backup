DOCUMENTAÇÃO DE INTEGRAÇÕES COM APIs EXTERNAS
Projeto: Cesta de Preços
Data: 16/10/2025

ARQUIVOS DE DOCUMENTAÇÃO GERADOS:
==================================

1. RELATORIO_APIS_EXTERNAS.md (19 KB)
   Localização: /home/dattapro/modulos/cestadeprecos/RELATORIO_APIS_EXTERNAS.md
   Tipo: Markdown técnico
   Conteúdo:
   - Sumário executivo
   - Serviços (CnpjService, LicitaconService)
   - Controllers (5 controllers com integrações)
   - Comandos de sincronização (6 comandos)
   - Modelos de dados
   - Cache implementado
   - Tratamento de erros
   - Autenticação e segurança
   - Headers HTTP customizados
   - Limitações
   - Configurações .env
   - Endpoints públicos e protegidos
   - Volume e performance
   - Monitoramento e logs
   - Manutenção e troubleshooting

2. SUMARIO_APIS_VISUAL.txt (19 KB)
   Localização: /home/dattapro/modulos/cestadeprecos/SUMARIO_APIS_VISUAL.txt
   Tipo: Text com diagramas ASCII
   Conteúdo:
   - Mapa visual de serviços
   - Hierarquia de controllers
   - Comandos de sincronização
   - Lista de APIs externas com URLs
   - Cache implementado
   - Tratamento de erros e segurança
   - Performance esperada
   - Endpoints resumidos
   - Resumo estatístico

3. INDICE_COMPLETO.txt (33 KB)
   Localização: /home/dattapro/modulos/cestadeprecos/INDICE_COMPLETO.txt
   Tipo: Text com índice detalhado
   Conteúdo:
   - Arquivos de documentação gerados
   - Arquivos analisados no projeto
   - Mapa detalhado de APIs externas
   - Cache implementado
   - Fluxos de fallback e retry
   - Logs e monitoramento
   - Performance esperada
   - Arquivo .env recomendado
   - Resumo estatístico
   - Conclusão

4. README_DOCUMENTACAO.txt (este arquivo)
   Localização: /home/dattapro/modulos/cestadeprecos/README_DOCUMENTACAO.txt
   Tipo: Guia de referência rápida

PRINCIPAIS DESCOBERTAS:
=======================

SERVIÇOS (2):
- CnpjService: Consulta CNPJ com fallback (ReceitaWS → BrasilAPI → CNPJ.ROCKS)
- LicitaconService: Download e cache de ZIP com parsing CSV

CONTROLLERS (5):
- CnpjController: Consulta CNPJ com rate limiting
- PesquisaRapidaController: Busca multi-fonte (5 APIs)
- MapaAtasController: Buscar ARPs do PNCP
- CatalogoController: Gerenciar catálogo com referências
- FornecedorController: Gerenciar fornecedores

COMANDOS (6):
- pncp:sincronizar: Sincronizar ~100K+ contratos
- cmed:import: Importar ~40K+ medicamentos
- BaixarCatmat, LicitaconSincronizar, PopularFornecedoresPNCP, AtualizarFornecedoresContratos

APIS EXTERNAS (7):
1. PNCP - https://pncp.gov.br/api/
2. CNPJ - ReceitaWS, BrasilAPI, CNPJ.ROCKS
3. LicitaCon - https://dados.tce.rs.gov.br/
4. Compras.gov - https://dadosabertos.compras.gov.br/
5. Portal Transparência - https://api.portaldatransparencia.gov.br/
6. CMED - Arquivo Excel local
7. CATMAT - https://dadosabertos.compras.gov.br/

CACHE:
- CNPJ: 15 minutos
- LicitaCon: 24 horas
- Banco de dados: consultas_pncp_cache

TRATAMENTO DE ERROS:
- Validação de entrada
- Retry automático (2x com 1000ms delay)
- Fallback automático entre fontes
- Logging completo
- Rate limiting (10/min por IP)

SEGURANÇA:
- HTTPS/TLS para todas as APIs
- Validação de CNPJ
- Autenticação por API Key (Portal Transparência)
- Logging detalhado

PERFORMANCE:
- Consulta CNPJ: 2-5 segundos
- Busca multi-fonte: 10-30 segundos
- Busca local: <1 segundo
- ~340K+ registros sincronizados

COMO USAR A DOCUMENTAÇÃO:
=========================

Para entender o TÉCNICO completo:
  Leia: RELATORIO_APIS_EXTERNAS.md

Para ter uma VISÃO RÁPIDA:
  Leia: SUMARIO_APIS_VISUAL.txt

Para buscar INFORMAÇÕES ESPECÍFICAS:
  Use: INDICE_COMPLETO.txt

Para monitorar em PRODUÇÃO:
  Acompanhe: storage/logs/laravel.log

ARQUIVOS ANALISADOS NO CÓDIGO:
==============================

Serviços:
- app/Services/CnpjService.php (273 linhas)
- app/Services/LicitaconService.php (397 linhas)

Controllers:
- app/Http/Controllers/CnpjController.php
- app/Http/Controllers/PesquisaRapidaController.php (1068 linhas)
- app/Http/Controllers/MapaAtasController.php
- app/Http/Controllers/CatalogoController.php
- app/Http/Controllers/FornecedorController.php

Comandos:
- app/Console/Commands/SincronizarPNCP.php
- app/Console/Commands/ImportarCmed.php
- app/Console/Commands/BaixarCatmat.php
- app/Console/Commands/LicitaconSincronizar.php
- app/Console/Commands/PopularFornecedoresPNCP.php
- app/Console/Commands/AtualizarFornecedoresContratos.php

Modelos:
- app/Models/ContratoPNCP.php (~100K+ registros)
- app/Models/MedicamentoCmed.php (~40K+ registros)
- app/Models/ConsultaPncpCache.php

Rotas:
- routes/web.php (429 linhas)

COMANDOS ÚTEIS:
===============

Sincronizar PNCP:
  php artisan pncp:sincronizar {--meses=6} {--paginas=50}

Importar CMED:
  php artisan cmed:import {arquivo?} {--mes=} {--limpar} {--teste=0}

Ver logs:
  tail -f storage/logs/laravel.log

Consultar CNPJ (curl):
  curl -X POST http://localhost/api/cnpj/consultar \
    -H "Content-Type: application/json" \
    -d '{"cnpj":"12345678000190"}'

Buscar (curl):
  curl "http://localhost/pesquisa/buscar?termo=papel"

CONFIGURAÇÃO RECOMENDADA (.env):
================================

CACHE_DRIVER=redis
CACHE_TTL=900

PNCP_PAGE_SIZE_RAPIDA=100
PNCP_PAGINAS_RAPIDA=3

PORTALTRANSPARENCIA_API_KEY=319215bff3b6753f5e1e4105c58a55e9

LOG_CHANNEL=single
LOG_LEVEL=info

RECOMENDAÇÕES:
==============

1. Manter sincronização programada (cronjob diário para PNCP)
2. Monitorar logs em storage/logs/laravel.log
3. Manter Redis rodando para cache
4. Testar fallbacks regularmente
5. Documentar mudanças em APIs externas

SUPORTE:
========

Para dúvidas sobre a documentação:
- Verifique RELATORIO_APIS_EXTERNAS.md seção 15 (Troubleshooting)
- Consulte INDICE_COMPLETO.txt para fluxos de fallback
- Acompanhe storage/logs/laravel.log para diagnóstico

Data de última atualização: 16/10/2025
Versão: 1.0
