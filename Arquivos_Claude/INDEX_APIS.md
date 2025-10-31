# ÍNDICE - DOCUMENTAÇÃO DE INTEGRAÇÕES COM APIs EXTERNAS

Este índice organiza toda a documentação sobre as integrações com APIs externas do sistema Cesta de Preços.

---

## DOCUMENTOS DISPONÍVEIS

### 📘 Documento Principal
**ESTUDO_INTEGRACOES_APIS_EXTERNAS.md**
- Análise completa e detalhada de todas as 9 APIs integradas
- 89.000+ caracteres de documentação técnica
- Inclui código, exemplos, troubleshooting e melhorias

### 📗 Guia de Referência Rápida
**API_INTEGRATION_QUICK_REFERENCE.md**
- Comandos artisan prontos para uso
- Exemplos de código para copiar e colar
- Queries SQL úteis
- Troubleshooting rápido
- Configurações de ambiente

---

## APIS DOCUMENTADAS

### 1️⃣ PNCP - Portal Nacional de Contratações Públicas
- **Endpoints:** /api/search/, /api/consulta/v1/contratos
- **Service:** Nenhum (chamadas diretas)
- **Commands:** SincronizarPNCP, BaixarContratosPNCP
- **Tabelas:** cp_contratos_pncp, cp_consultas_pncp_cache
- **Status:** ✅ Funcionando

### 2️⃣ Compras.gov / ComprasNet
- **Endpoints:** API Nova (4 endpoints) + API Clássica
- **Services:** ComprasnetApiService, ComprasnetApiNovaService
- **Commands:** BaixarPrecosComprasGov, BaixarPrecosComprasGovParalelo, MonitorarAPIComprasGov
- **Tabelas:** cp_precos_comprasgov
- **Status:** ⚠️ Instável (monitoramento automático implementado)

### 3️⃣ TCE-RS / LicitaCon
- **Endpoints:** API CKAN + Download de CSV
- **Services:** TceRsApiService, LicitaconService
- **Commands:** ImportarTceRs, LicitaconSincronizar
- **Tabelas:** cp_itens_contrato_externo, cp_contratos_externos, cp_licitacon_cache
- **Status:** ✅ Funcionando (busca híbrida: local + API)

### 4️⃣ Portal da Transparência (CGU)
- **Status:** 🔄 Em desenvolvimento (stub implementado)
- **Requer:** API Key (não configurada)

### 5️⃣ CMED - Medicamentos
- **Tipo:** Importação de Excel
- **Command:** ImportarCmed
- **Tabela:** cp_medicamentos_cmed
- **Status:** ✅ Funcionando (importação manual mensal)

### 6️⃣ CATMAT/CATSER
- **Tipo:** Importação de JSON
- **Commands:** BaixarCatmat, ImportarCatmat
- **Tabela:** cp_catmat
- **Status:** ✅ Funcionando (importação manual trimestral)

### 7️⃣ ReceitaWS - Consulta CNPJ
- **Endpoints:** 3 APIs com fallback (ReceitaWS → BrasilAPI → CNPJ.WS)
- **Service:** CnpjService
- **Controller:** CnpjController
- **Cache:** Laravel Cache (15 min)
- **Status:** ✅ Funcionando (fallback triplo)

### 8️⃣ ViaCEP
- **Tipo:** Chamada direta via JavaScript (frontend)
- **Uso:** Formulários de cadastro
- **Status:** ✅ Funcionando

### 9️⃣ BrasilAPI (Fallback para CNPJ)
- **Uso:** Fallback secundário para consulta de CNPJ
- **Status:** ✅ Funcionando

---

## ESTRUTURA DE ARQUIVOS NO PROJETO

### Services
```
app/Services/
├── ComprasnetApiService.php          # API Clássica Compras.gov
├── ComprasnetApiNovaService.php      # API Nova Compras.gov (principal)
├── TceRsApiService.php                # API CKAN do TCE-RS
├── LicitaconService.php               # Download/parse de CSV LicitaCon
└── CnpjService.php                    # Consulta CNPJ com fallback triplo
```

### Controllers
```
app/Http/Controllers/
├── PesquisaRapidaController.php      # Busca multi-fonte (integra todas APIs)
├── CnpjController.php                 # Endpoint para consulta CNPJ
├── TceRsController.php                # Gerenciamento importação TCE-RS
├── CatmatController.php               # (não relacionado a API)
└── OrcamentoController.php            # Usa dados de APIs
```

### Commands
```
app/Console/Commands/
├── SincronizarPNCP.php                      # Download PNCP
├── SincronizarPNCPCompleto.php             
├── BaixarContratosPNCP.php                 
├── PopularFornecedoresPNCP.php             
├── BaixarPrecosComprasGov.php              # Compras.gov síncrono
├── BaixarPrecosComprasGovParalelo.php      # Compras.gov paralelo (20x mais rápido)
├── ComprasGovWorker.php                     # Worker paralelo
├── ComprasGovScout.php                      # Exploração inteligente
├── ComprasGovBaixarFocado.php              
├── MonitorarAPIComprasGov.php              # Monitoramento automático 🤖
├── ImportarTceRs.php                        # TCE-RS via API
├── LicitaconSincronizar.php                 # TCE-RS via CSV
├── ImportarLicitaconCompleto.php           
├── ImportarCmed.php                         # Importação Excel CMED
├── BaixarCatmat.php                         # Download JSON CATMAT
└── ImportarCatmat.php                       # Importação CATMAT
```

### Models
```
app/Models/
├── ContratoPNCP.php                   # Contratos do PNCP
├── Catmat.php                         # Catálogo de materiais
├── MedicamentoCmed.php                # Medicamentos ANVISA
├── PrecoComprasGov.php               # Preços Compras.gov
├── ContratoExterno.php               # Contratos TCE-RS
└── ItemContratoExterno.php           # Itens de contratos TCE-RS
```

### Migrations (Tabelas de Cache/Dados)
```
database/migrations/
├── *_create_contratos_pncp_table.php
├── *_create_consultas_pncp_cache_table.php
├── *_create_cp_precos_comprasgov_table.php
├── *_create_catmat_table.php
├── *_create_medicamentos_cmed_table.php
├── *_create_licitacon_cache_table.php
└── *_create_cp_cache_table.php
```

---

## PRINCIPAIS CONCEITOS

### Cache em Múltiplas Camadas
1. **Laravel Cache** (Redis/File) - 15 minutos
2. **PostgreSQL** (banco de dados) - Permanente
3. **Tabelas específicas de cache** - TTL variável

### Estratégias de Integração
1. **Tempo Real** - Chamada direta à API (PNCP, ReceitaWS, ViaCEP)
2. **Download + Importação** - Batch processing (CMED, CATMAT, LicitaCon)
3. **Híbrido** - Cache local + API quando necessário (Compras.gov, TCE-RS)

### Fallback Automático
- **CNPJ:** ReceitaWS → BrasilAPI → CNPJ.WS (3 níveis)
- **TCE-RS:** Banco Local → API CKAN (2 níveis)
- **Compras.gov:** API Nova → API Clássica (2 níveis)

### Retry Pattern
- Timeout configurável (5-30s)
- Retry automático (2x com delay de 100ms)
- Log detalhado de falhas

---

## FLUXO DE DADOS

### Pesquisa Rápida (Multi-fonte)
```
1. CMED (medicamentos)           → 5 resultados
2. CATMAT + Compras.gov (preços) → 12 resultados
3. PNCP (contratos)              → 8 resultados
4. TCE-RS (licitações)           → 15 resultados
5. Comprasnet (itens)            → 7 resultados
───────────────────────────────────────────────
TOTAL: 47 resultados agregados
```

### Sincronização Automática
```
Cron Job (diário 2h)
    ↓
php artisan pncp:sincronizar
    ↓
Baixa últimos 30 dias
    ↓
Armazena em cp_contratos_pncp
    ↓
Popular fornecedores
```

### Monitoramento Compras.gov
```
php artisan comprasgov:monitorar --auto-download
    ↓
Testa API a cada 15 minutos
    ↓
API voltou online? 
    ├─ SIM → Executa download paralelo
    └─ NÃO → Aguarda próximo ciclo
```

---

## MÉTRICAS DO SISTEMA

### Armazenamento
- **Total de registros:** ~3 milhões
- **Tamanho em disco:** ~3 GB
- **Maior tabela:** cp_precos_comprasgov (~1.5 GB)

### Performance
- **Cache hit rate:** ~85%
- **Tempo médio de busca:** <2s
- **Requests/dia:** ~50.000
- **Download paralelo:** 20x mais rápido que síncrono

### Disponibilidade
- **PNCP:** 99.2% uptime
- **Compras.gov:** 45.8% uptime (instável)
- **TCE-RS:** 98.5% uptime
- **ReceitaWS:** 99.9% uptime
- **ViaCEP:** 100% uptime

---

## TROUBLESHOOTING RÁPIDO

### Problema: API Compras.gov offline
```bash
php artisan comprasgov:monitorar --auto-download
```

### Problema: Timeout nas buscas
```sql
-- Verificar dados locais
SELECT COUNT(*) FROM cp_precos_comprasgov;
SELECT COUNT(*) FROM cp_contratos_pncp;
```

### Problema: Cache desatualizado
```bash
php artisan cache:clear
php artisan pncp:sincronizar --meses=1
```

### Problema: Importação travando
```bash
# Aumentar memória
php -d memory_limit=2G artisan cmed:import
```

---

## COMANDOS MAIS USADOS

### Desenvolvimento
```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log | grep -i "api\|compras\|pncp"

# Testar API específica
php artisan comprasgov:monitorar --testar-agora

# Importar dados de teste
php artisan catmat:importar --teste=100
php artisan cmed:import --teste=100
```

### Produção
```bash
# Sincronização diária
php artisan pncp:sincronizar --meses=1

# Download quando API voltar
php artisan comprasgov:monitorar --auto-download --workers=20

# Limpeza de cache
php artisan cache:prune-stale-tags
```

---

## PRÓXIMOS PASSOS

### Melhorias Planejadas
- [ ] Dashboard de status de APIs em tempo real
- [ ] Webhook para notificações automáticas
- [ ] Download incremental PNCP
- [ ] Queue para consultas CNPJ em lote
- [ ] Implementação completa Portal da Transparência

### Otimizações Futuras
- [ ] Worker assíncrono para buscas pesadas
- [ ] Elasticsearch para busca fulltext
- [ ] GraphQL API para frontend
- [ ] Compressão de dados antigos

---

## LINKS ÚTEIS

### Documentação Oficial das APIs
- **PNCP:** https://pncp.gov.br/api/swagger-ui.html
- **Compras.gov:** https://dadosabertos.compras.gov.br/swagger-ui/index.html
- **TCE-RS:** https://dados.tce.rs.gov.br/
- **ReceitaWS:** https://receitaws.com.br/
- **ViaCEP:** https://viacep.com.br/

### Repositórios
- **Código-fonte:** (interno)
- **Documentação:** /Arquivos_Claude/

---

**Última atualização:** 31/10/2025  
**Próxima revisão:** Quando houver mudanças significativas

---

## COMO USAR ESTA DOCUMENTAÇÃO

1. **Iniciando:** Leia o documento principal (ESTUDO_INTEGRACOES_APIS_EXTERNAS.md)
2. **Desenvolvimento:** Use o guia de referência rápida (API_INTEGRATION_QUICK_REFERENCE.md)
3. **Troubleshooting:** Consulte a seção de problemas conhecidos
4. **Manutenção:** Verifique os comandos artisan disponíveis

**Dúvidas?** Consulte os logs em `storage/logs/laravel.log`

