# REFERÊNCIA RÁPIDA - INTEGRAÇÕES DE APIs

**Guia de consulta rápida para desenvolvedores**

---

## TABELA RESUMO DE TODAS AS APIS

| API | Tipo | URL Base | Auth | Cache | Status |
|-----|------|----------|------|-------|--------|
| **PNCP** | REST | pncp.gov.br | Nenhuma | 15min | ✅ Ativo |
| **Compras.gov Nova** | REST | dadosabertos.compras.gov.br | Nenhuma | 24h | ⚠️ Instável |
| **Compras.gov Clássica** | REST | api.compras.dados.gov.br | Nenhuma | 15min | ✅ Ativo |
| **TCE-RS CKAN** | REST | dados.tce.rs.gov.br | Nenhuma | 15min | ✅ Ativo |
| **LicitaCon CSV** | Download | dados.tce.rs.gov.br | Nenhuma | 24h | ✅ Ativo |
| **ReceitaWS** | REST | receitaws.com.br | Nenhuma | 15min | ✅ Ativo |
| **BrasilAPI** | REST | brasilapi.com.br | Nenhuma | 15min | ✅ Ativo |
| **CNPJ.WS** | REST | publica.cnpj.ws | Nenhuma | 15min | ✅ Ativo |
| **ViaCEP** | REST | viacep.com.br | Nenhuma | Sem | ✅ Ativo |
| **CMED** | Excel | Manual | - | Permanente | ✅ Ativo |
| **CATMAT** | JSON | Manual | - | Permanente | ✅ Ativo |

---

## COMANDOS ARTISAN RÁPIDOS

### PNCP
```bash
# Sincronizar últimos 6 meses
php artisan pncp:sincronizar --meses=6 --paginas=50

# Sincronização completa
php artisan pncp:sincronizar-completo

# Popular fornecedores
php artisan pncp:popular-fornecedores
```

### Compras.gov
```bash
# Monitorar e baixar automaticamente
php artisan comprasgov:monitorar --auto-download --workers=20

# Testar status da API
php artisan comprasgov:monitorar --testar-agora

# Download manual paralelo (RÁPIDO)
php artisan comprasgov:baixar-paralelo --workers=20 --codigos=5000

# Download síncrono (LENTO)
php artisan comprasgov:baixar-precos --limite-gb=3
```

### TCE-RS / LicitaCon
```bash
# Sincronizar via API CKAN
php artisan tcers:importar

# Sincronizar via CSV
php artisan licitacon:sincronizar

# Importação completa
php artisan licitacon:importar-completo
```

### CMED (Medicamentos)
```bash
# Importar com limpeza
php artisan cmed:import --limpar

# Especificar arquivo
php artisan cmed:import "/path/to/CMED Outubro 25.xlsx" --mes="Outubro 2025"

# Modo teste (100 linhas)
php artisan cmed:import --teste=100
```

### CATMAT
```bash
# Baixar JSON
php artisan catmat:baixar

# Importar para banco
php artisan catmat:importar --limpar

# Modo teste
php artisan catmat:importar --teste=1000
```

---

## EXEMPLOS DE USO NOS CONTROLLERS

### 1. Buscar Preços (Compras.gov)

```php
use App\Services\ComprasnetApiNovaService;

public function buscarPrecos(Request $request)
{
    $service = app(ComprasnetApiNovaService::class);
    $catmat = $request->input('catmat');
    
    $resultado = $service->buscarPrecosPraticados($catmat);
    
    if ($resultado['sucesso']) {
        return response()->json([
            'success' => true,
            'dados' => $resultado['dados']
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => $resultado['erro']
    ], 500);
}
```

### 2. Consultar CNPJ (ReceitaWS com Fallback)

```php
use App\Services\CnpjService;

public function consultarCnpj(Request $request)
{
    $cnpjService = app(CnpjService::class);
    $cnpj = $request->input('cnpj');
    
    $resultado = $cnpjService->consultar($cnpj);
    
    // Fallback automático: ReceitaWS → BrasilAPI → CNPJ.WS
    
    return response()->json($resultado);
}
```

### 3. Buscar no TCE-RS (Híbrido: Local + API)

```php
use App\Services\TceRsApiService;

public function buscarItens(Request $request)
{
    $service = app(TceRsApiService::class);
    $termo = $request->input('termo');
    
    $resultado = $service->buscarItensContratos($termo, 100);
    
    // Busca PRIMEIRO no banco local (rápido)
    // Se não encontrar, busca na API (lento)
    
    return response()->json($resultado);
}
```

### 4. Pesquisa Multi-Fonte (Todas as APIs)

```php
// PesquisaRapidaController::buscar()
public function buscar(Request $request)
{
    $termo = $request->input('termo');
    $resultados = [];
    
    // 1. CMED (medicamentos)
    $resultados = array_merge($resultados, $this->buscarNoCMED($termo));
    
    // 2. CATMAT + Compras.gov
    $resultados = array_merge($resultados, $this->buscarNoCATMATComPrecos($termo));
    
    // 3. PNCP (contratos)
    $resultados = array_merge($resultados, $this->buscarContratosPNCP($termo));
    
    // 4. TCE-RS (licitações)
    $resultados = array_merge($resultados, $this->buscarNoLicitaCon($termo));
    
    // 5. Comprasnet (itens de contratos)
    $resultados = array_merge($resultados, $this->buscarNoComprasnet($termo));
    
    return response()->json([
        'success' => true,
        'resultados' => $resultados,
        'total' => count($resultados)
    ]);
}
```

---

## QUERIES ÚTEIS NO BANCO

### Ver Estatísticas de Preços Compras.gov
```sql
-- Total de preços
SELECT COUNT(*) FROM cp_precos_comprasgov;

-- Preços por CATMAT (top 10)
SELECT catmat_codigo, COUNT(*) as total
FROM cp_precos_comprasgov
GROUP BY catmat_codigo
ORDER BY total DESC
LIMIT 10;

-- Preços por UF
SELECT orgao_uf, COUNT(*) as total, AVG(preco_unitario) as preco_medio
FROM cp_precos_comprasgov
WHERE preco_unitario > 0
GROUP BY orgao_uf
ORDER BY total DESC;

-- Últimos sincronizados
SELECT DATE(sincronizado_em) as data, COUNT(*) as total
FROM cp_precos_comprasgov
GROUP BY DATE(sincronizado_em)
ORDER BY data DESC
LIMIT 30;
```

### Ver Contratos PNCP
```sql
-- Total de contratos
SELECT COUNT(*) FROM cp_contratos_pncp;

-- Contratos por órgão (top 10)
SELECT orgao_razao_social, COUNT(*) as total, SUM(valor_global) as valor_total
FROM cp_contratos_pncp
GROUP BY orgao_razao_social
ORDER BY total DESC
LIMIT 10;

-- Contratos recentes
SELECT numero_controle_pncp, objeto_contrato, valor_global, 
       orgao_razao_social, data_publicacao_pncp
FROM cp_contratos_pncp
ORDER BY data_publicacao_pncp DESC
LIMIT 20;

-- Contratos por fornecedor
SELECT fornecedor_razao_social, COUNT(*) as total_contratos, 
       SUM(valor_global) as valor_total
FROM cp_contratos_pncp
WHERE fornecedor_razao_social IS NOT NULL
GROUP BY fornecedor_razao_social
ORDER BY total_contratos DESC
LIMIT 10;
```

### Ver Medicamentos CMED
```sql
-- Total de medicamentos
SELECT COUNT(*) FROM cp_medicamentos_cmed;

-- Por tipo (genérico, similar, referência)
SELECT tipo_produto, COUNT(*) as total
FROM cp_medicamentos_cmed
GROUP BY tipo_produto
ORDER BY total DESC;

-- Laboratórios com mais produtos
SELECT laboratorio, COUNT(*) as total_produtos
FROM cp_medicamentos_cmed
GROUP BY laboratorio
ORDER BY total_produtos DESC
LIMIT 20;

-- Preço médio por classe terapêutica
SELECT classe_terapeutica, 
       COUNT(*) as total,
       AVG(pmc_0) as preco_medio,
       MIN(pmc_0) as preco_minimo,
       MAX(pmc_0) as preco_maximo
FROM cp_medicamentos_cmed
WHERE pmc_0 > 0
GROUP BY classe_terapeutica
ORDER BY total DESC
LIMIT 20;
```

### Ver Cache e Performance
```sql
-- Tamanho das tabelas
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE tablename LIKE 'cp_%'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- Consultas no cache PNCP
SELECT COUNT(*) FROM cp_consultas_pncp_cache;

-- Cache expirado
SELECT COUNT(*) FROM cp_consultas_pncp_cache
WHERE valido_ate < NOW();

-- Limpar cache expirado
DELETE FROM cp_consultas_pncp_cache WHERE valido_ate < NOW();
```

---

## TROUBLESHOOTING RÁPIDO

### API Compras.gov não responde

```bash
# 1. Testar se está online
php artisan comprasgov:monitorar --testar-agora

# 2. Se offline, iniciar monitoramento
php artisan comprasgov:monitorar --auto-download --workers=20

# 3. Verificar logs
tail -f storage/logs/laravel.log | grep -i comprasgov
```

### Timeout nas buscas TCE-RS

```sql
-- Verificar se dados locais existem
SELECT COUNT(*) FROM cp_itens_contrato_externo WHERE fonte LIKE 'TCE-RS%';

-- Se vazio, importar
php artisan licitacon:sincronizar
```

### Cache desatualizado

```bash
# Limpar cache Laravel
php artisan cache:clear

# Limpar cache específico
php artisan tinker
>>> Cache::forget('comprasnet_nova:material_detalhe:243756:1:100');

# Limpar cache de tabela específica
php artisan db:seed --class=LimparCachePNCPSeeder
```

### ReceitaWS retornando erro 429

```php
// Aumentar rate limit no controller
// app/Http/Controllers/CnpjController.php

if (RateLimiter::tooManyAttempts($key, 5)) { // Reduzir de 10 para 5
    // ...
}
```

### Importação CMED/CATMAT travando

```bash
# Verificar memória
php -i | grep memory_limit

# Aumentar se necessário
php -d memory_limit=2G artisan cmed:import

# Ou editar php.ini
memory_limit = 2048M
```

---

## ENDPOINTS DE API PARA TESTES EXTERNOS

### Testar PNCP
```bash
curl "https://pncp.gov.br/api/search/?q=arroz&tipos_documento=contrato&pagina=1"
```

### Testar Compras.gov Nova
```bash
curl "https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/2_consultarMaterialDetalhe?codigoItemCatalogo=243756&pagina=1&tamanhoPagina=10"
```

### Testar TCE-RS CKAN
```bash
curl "https://dados.tce.rs.gov.br/api/3/action/package_search?q=contratos&rows=5"
```

### Testar ReceitaWS
```bash
curl "https://www.receitaws.com.br/v1/cnpj/00000000000191"
```

### Testar ViaCEP
```bash
curl "https://viacep.com.br/ws/01310100/json/"
```

---

## MONITORAMENTO EM PRODUÇÃO

### Criar Cron Jobs

```bash
# Editar crontab
crontab -e

# Adicionar:
# Sincronizar PNCP diariamente às 2h
0 2 * * * cd /path/to/app && php artisan pncp:sincronizar --meses=1

# Monitorar Compras.gov a cada 15 minutos
*/15 * * * * cd /path/to/app && php artisan comprasgov:monitorar --testar-agora

# Limpar cache expirado a cada hora
0 * * * * cd /path/to/app && php artisan cache:prune-stale-tags

# Importar CMED no dia 5 de cada mês às 3h
0 3 5 * * cd /path/to/app && php artisan cmed:import --limpar
```

### Health Check Endpoint

```php
// routes/api.php
Route::get('/health/apis', function() {
    $status = [];
    
    // Testar PNCP
    try {
        $response = Http::timeout(5)->get('https://pncp.gov.br/api/search/?q=teste');
        $status['pncp'] = $response->successful() ? 'online' : 'offline';
    } catch (\Exception $e) {
        $status['pncp'] = 'offline';
    }
    
    // Testar Compras.gov
    try {
        $response = Http::timeout(5)->get('https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial', [
            'pagina' => 1,
            'tamanhoPagina' => 1
        ]);
        $status['comprasgov'] = $response->successful() ? 'online' : 'offline';
    } catch (\Exception $e) {
        $status['comprasgov'] = 'offline';
    }
    
    // etc...
    
    return response()->json([
        'status' => 'ok',
        'apis' => $status,
        'timestamp' => now()
    ]);
});
```

---

## VARIÁVEIS DE AMBIENTE

```env
# .env

# Cache
CACHE_DRIVER=redis  # Ou file para desenvolvimento
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Database (multi-tenant)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cestadeprecos
DB_USERNAME=postgres
DB_PASSWORD=secret

# Database Principal (dados compartilhados)
DB_MAIN_CONNECTION=pgsql_main
DB_MAIN_HOST=127.0.0.1
DB_MAIN_PORT=5432
DB_MAIN_DATABASE=cestadeprecos_main
DB_MAIN_USERNAME=postgres
DB_MAIN_PASSWORD=secret

# Timeouts
HTTP_TIMEOUT=30
HTTP_CONNECT_TIMEOUT=5

# Rate Limits
CNPJ_RATE_LIMIT=10  # Requests por minuto
API_RATE_LIMIT=100  # Requests por minuto

# Compras.gov
COMPRASGOV_WORKERS=20  # Workers paralelos
COMPRASGOV_BATCH_SIZE=5000  # Batch insert

# PNCP
PNCP_SYNC_MONTHS=6  # Meses para sincronizar
```

---

**Última atualização:** 31/10/2025  
**Próxima revisão:** Quando houver mudanças nas APIs

