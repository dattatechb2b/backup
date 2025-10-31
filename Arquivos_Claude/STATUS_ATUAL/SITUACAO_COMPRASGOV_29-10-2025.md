# Situação Compras.gov - 29/10/2025

## Problema Identificado

Pesquisa Rápida retorna **0 resultados** do Compras.gov, mesmo com integração implementada.

**Evidência do Usuário:**
- Busca por "computador" retorna 49 resultados TODOS do PNCP
- NENHUM resultado do Compras.gov aparece

## Análise Técnica Completa

### 1. Estado Atual do Banco de Dados

```sql
-- Tabela cp_precos_comprasgov
Total de registros: 0 (VAZIA)
Tamanho: 96 kB (apenas overhead)

-- Tabela cp_catmat
Total de códigos: 336.117
Com flag tem_preco_comprasgov = TRUE: 0
Com flag tem_preco_comprasgov = FALSE: 0
Com flag tem_preco_comprasgov = NULL: 336.117 (100%)
```

### 2. Histórico Descoberto

**Download Bem-Sucedido em 23/10/2025:**
- **Horário:** 00:14h
- **Total baixado:** 29.179 preços
- **Tamanho:** 15 MB
- **Códigos processados:** 10.000
- **Script usado:** `coleta_precos_comprasgov_hibrida.php`
- **Log:** `/home/dattapro/modulos/cestadeprecos/storage/logs/download_comprasgov.log`

**Perda dos Dados em 29/10/2025:**
- **Horário:** 14:38h
- **Causa:** Execução de migration que recria a tabela
- **Migration:** `2025_10_29_113814_create_cp_precos_comprasgov_table.php`
- **Problema:** Usa `Schema::create()` que DROP a tabela existente
- **Resultado:** 29.179 registros PERDIDOS PERMANENTEMENTE

### 3. Tentativa de Recuperação (29/10/2025)

**Investigação Realizada:**
- ❌ Backups PostgreSQL: Não encontrados
- ❌ Dumps SQL: Não encontrados
- ❌ Arquivos temporários: Não encontrados
- ❌ WAL logs: Não acessíveis
- ✅ Confirmação via pg_stat_user_tables: n_tup_ins = 0, n_tup_del = 0

**Conclusão:** Dados IRRECUPERÁVEIS

### 4. Teste de API (29/10/2025 - 17:05h)

```bash
Endpoint: https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial
Status: OFFLINE / INACESSÍVEL
HTTP Code: 0 (falha de conexão)
Tempo de resposta: 5.14s (timeout)
```

**Resultado:** API Compras.gov está FORA DO AR ou BLOQUEADA

## Por Que Não Aparece na Pesquisa?

### Código Atual (PesquisaRapidaController.php)

```php
// LINHA 1020-1031
$precos = DB::connection('pgsql_main')
    ->table('cp_precos_comprasgov')
    ->whereRaw("to_tsvector('portuguese', descricao_item) @@ plainto_tsquery('portuguese', ?)", [$termo])
    ->get();

if ($precos->isEmpty()) {
    Log::info('🟢 COMPRAS.GOV LOCAL: Nenhum preço encontrado na base local');
    Log::info('🔵 COMPRAS.GOV API: Tentando busca em tempo real...');
    return $this->buscarNaAPIComprasGovTempoReal($termo);
}
```

**Fluxo Atual:**
1. Busca na tabela `cp_precos_comprasgov` → **VAZIA (0 resultados)**
2. Detecta vazio → Tenta fallback API em tempo real
3. API está FORA DO AR → **FALHA**
4. Retorna 0 resultados

## Soluções Disponíveis

### Solução 1: Aguardar API Voltar e Re-Baixar (RECOMENDADO)

**Quando:** Assim que a API voltar online
**Comandos disponíveis:**

```bash
# Opção A: Download Paralelo (RÁPIDO - 30-60 min)
php artisan comprasgov:baixar-paralelo --workers=10 --codigos=10000 --limite-gb=3

# Opção B: Download Sequencial (LENTO - 2-3 horas)
php artisan comprasgov:baixar-precos --limite-gb=3
```

**Estimativa:**
- 10.000 códigos = ~30.000 preços
- Tamanho: ~15-20 MB
- Tempo (paralelo): 30-60 minutos
- Tempo (sequencial): 2-3 horas

### Solução 2: Melhorar Feedback ao Usuário (IMEDIATO)

Modificar `PesquisaRapidaController.php` para mostrar mensagem clara:

```php
if ($precos->isEmpty()) {
    return [
        'fonte' => 'COMPRAS.GOV',
        'status' => 'indisponivel',
        'mensagem' => 'Compras.gov temporariamente indisponível. Dados sendo atualizados.',
        'resultados' => []
    ];
}
```

### Solução 3: Usar Script PHP Híbrido (ALTERNATIVA)

**Script:** `coleta_precos_comprasgov_hibrida.php`
**Características:**
- Marca flags em cp_catmat (tem_preco_comprasgov)
- Salva em tabela historico_precos (que NÃO EXISTE atualmente)
- User-Agent customizado: DattaTech-CestaPrecos/1.0
- Delay: 0.2s entre requests

**Problema:** Tabela `historico_precos` não existe no schema atual

## Comandos Artisan Disponíveis

```bash
catmat:baixar                     # Baixar catálogo CATMAT (336.192 itens)
comprasgov:baixar-paralelo        # Download paralelo RÁPIDO
comprasgov:baixar-precos          # Download sequencial LENTO
comprasgov:worker                 # Worker interno (não usar diretamente)
```

## Arquivos Relevantes

```
/home/dattapro/modulos/cestadeprecos/
├── app/Console/Commands/
│   ├── BaixarPrecosComprasGovParalelo.php    # Download paralelo
│   ├── BaixarPrecosComprasGov.php            # Download sequencial
│   └── ComprasGovWorker.php                  # Worker
├── app/Http/Controllers/
│   └── PesquisaRapidaController.php          # Integração na busca
├── coleta_precos_comprasgov_hibrida.php      # Script PHP alternativo
├── storage/logs/
│   └── download_comprasgov.log               # Log do download de 23/10
└── database/migrations/
    └── 2025_10_29_113814_create_cp_precos_comprasgov_table.php  # Migration problemática
```

## Recomendações

### IMEDIATO
1. ✅ Monitorar se API Compras.gov volta online
2. ✅ Implementar mensagem de feedback no frontend
3. ✅ Documentar situação (este arquivo)

### CURTO PRAZO (quando API voltar)
1. ⏳ Executar `comprasgov:baixar-paralelo` para baixar dados
2. ⏳ Validar que dados foram salvos corretamente
3. ⏳ Testar busca retornando resultados Compras.gov

### MÉDIO PRAZO (prevenção)
1. 📋 Implementar backup automático da tabela cp_precos_comprasgov
2. 📋 Modificar migration para usar `createIfNotExists()` ou verificar antes
3. 📋 Criar cron job mensal para atualizar dados Compras.gov
4. 📋 Implementar cache Redis para API fallback

## Monitoramento da API

**Testar se API voltou:**
```bash
timeout 15 php -r "
\$url = 'https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial';
\$codigo = '243756';  // COMPUTADOR COMPLETO
\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, \$url . '?codigoItemCatalogo=' . \$codigo);
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_TIMEOUT, 10);
\$response = curl_exec(\$ch);
\$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
curl_close(\$ch);
echo \$httpCode == 200 ? \"✅ API ONLINE\n\" : \"❌ API OFFLINE (HTTP \$httpCode)\n\";
"
```

## Conclusão

**Problema:** Tabela vazia + API offline = 0 resultados Compras.gov

**Causa Raiz:** Dados perdidos em migration + API indisponível no momento

**Solução:** Aguardar API voltar e executar download paralelo

**Status Atual:** BLOQUEADO - Aguardando API Compras.gov ficar disponível

---

**Última atualização:** 29/10/2025 17:10h
**Próxima verificação:** Testar API periodicamente (a cada 2-4 horas)
