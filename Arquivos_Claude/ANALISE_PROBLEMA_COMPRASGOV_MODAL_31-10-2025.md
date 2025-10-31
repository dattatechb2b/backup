# 🔍 ANÁLISE COMPLETA: Por que COMPRAS.GOV não aparece no Modal de Cotação

**Data:** 31/10/2025
**Investigação:** Modal de Cotação - Resultado vazio do Compras.gov
**Status:** ✅ ROOT CAUSE IDENTIFICADO

---

## 📋 RESUMO EXECUTIVO

O usuário reportou que ao buscar "arroz 5kg" no **Modal de Cotação**, aparecem 75 resultados do PNCP, mas **NENHUM resultado do Compras.gov**.

**ROOT CAUSE:** A rota `/compras-gov/buscar` filtra apenas códigos CATMAT que têm a flag `tem_preco_comprasgov = true`. Estatisticamente, apenas **1% dos códigos CATMAT** têm preços disponíveis no Compras.gov, e para "arroz", esse número é ainda menor (1 de 129 códigos, e é chocolate com flocos de arroz).

**CONCLUSÃO:** O código do modal está **CORRETO**. O problema é o **filtro restritivo** na rota backend combinado com a **baixa disponibilidade de preços** na API do Compras.gov.

---

## 🔬 INVESTIGAÇÃO DETALHADA

### 1. Estrutura do Modal de Cotação

#### Frontend (modal-cotacao.js)

**Linha 344-384:** Construção das URLs e busca paralela
```javascript
const urlComprasGov = termo && termo.length >= 3 ?
    `${window.APP_BASE_PATH}/compras-gov/buscar?termo=${encodeURIComponent(termo)}` : null;

const [resultPNCP, resultCMED, resultComprasGov] = await Promise.all([
    buscarComTimeout('PNCP', urlPNCP, '🔵'),
    buscarComTimeout('CMED', urlCMED, '💊'),
    buscarComTimeout('Compras.gov', urlComprasGov, '🛒')
]);
```

✅ **CORRETO**: Busca é feita em paralelo, URL está correta, timeout adequado.

**Linha 398-400:** Consolidação de resultados
```javascript
if (resultComprasGov.resultados.length > 0) {
    console.log(`🛒 Adicionando ${resultComprasGov.resultados.length} resultados do Compras.gov`);
    resultadosCompletos = [...resultadosCompletos, ...resultComprasGov.resultados];
}
```

✅ **CORRETO**: Resultados são adicionados ao array se existirem.

**Linha 681-682:** Normalização de fonte
```javascript
} else if (fonteResultado === 'COMPRAS.GOV') {
    fonteNormalizada = 'COMPRAS_GOV'; // COMPRAS.GOV → COMPRAS_GOV
```

✅ **CORRETO**: Fonte é normalizada para comparação com checkboxes.

#### HTML (_modal-cotacao.blade.php)

**Linha 180:** Checkbox do filtro
```html
<input type="checkbox" name="filtro_fonte" value="COMPRAS_GOV" checked>
```

✅ **CORRETO**: Checkbox tem `name="filtro_fonte"` e `value="COMPRAS_GOV"`, marcado por padrão.

---

### 2. Backend - Rota `/compras-gov/buscar`

**Arquivo:** `routes/web.php`, linhas 55-224

#### PASSO 1: Buscar CATMAT local (linhas 71-125)

```php
$query = \DB::connection('pgsql_main')->table('cp_catmat')
    ->select('codigo', 'titulo')
    ->where('ativo', true)
    ->where(function($q) {
        // FILTRO INTELIGENTE: Apenas materiais com preço OU não verificados ainda
        $q->where('tem_preco_comprasgov', true)
          ->orWhereNull('tem_preco_comprasgov');
    });
```

🔴 **PROBLEMA AQUI**: Filtro restringe a códigos que:
- Têm `tem_preco_comprasgov = true` (confirmado que têm preços)
- OU `tem_preco_comprasgov IS NULL` (nunca foram verificados)

**EXCLUINDO:**
- Códigos com `tem_preco_comprasgov = false` (99% dos casos!)

#### PASSO 2: Buscar preços na API (linhas 137-197)

```php
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Accept' => '*/*',
    'User-Agent' => 'DattaTech-CestaPrecos/1.0'
])
->timeout(10)
->get($urlPrecos, [
    'codigoItemCatalogo' => $material->codigo,
    'pagina' => 1,
    'tamanhoPagina' => 100
]);
```

✅ **CORRETO**: Parâmetros adequados (API aceita 10-500).

**Linha 166:** Define fonte
```php
'fonte' => 'COMPRAS.GOV',
```

✅ **CORRETO**: Frontend normaliza para `COMPRAS_GOV`.

---

### 3. Estatísticas da Base de Dados

#### Geral (336.117 códigos CATMAT ativos)

```sql
SELECT
    COUNT(*) as total_catmat,
    COUNT(CASE WHEN tem_preco_comprasgov = true THEN 1 END) as com_preco,
    COUNT(CASE WHEN tem_preco_comprasgov = false THEN 1 END) as sem_preco,
    COUNT(CASE WHEN tem_preco_comprasgov IS NULL THEN 1 END) as nao_verificado
FROM cp_catmat WHERE ativo = true;
```

**Resultado:**
```
Total CATMAT:     336.117 (100%)
Com preços:         3.646 (1.08%)  ✅ INCLUÍDOS na busca
Sem preços:       332.471 (98.92%) 🔴 EXCLUÍDOS da busca
Não verificados:        0 (0%)     ✅ INCLUÍDOS na busca (se houvesse)
```

#### Específico para "arroz" (129 códigos)

```sql
SELECT COUNT(*) as total_arroz,
       COUNT(CASE WHEN tem_preco_comprasgov = true THEN 1 END) as arroz_com_preco
FROM cp_catmat
WHERE ativo = true AND titulo ILIKE '%arroz%';
```

**Resultado:**
```
Total arroz:        129 (100%)
Com preços:           1 (0.78%)  ← É "CHOCOLATE COM FLOCOS DE ARROZ"
Sem preços:         128 (99.22%)
```

---

### 4. Teste da API Externa

#### Teste 1: Código 243756 (COMPUTADOR)
```bash
curl "https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial?codigoItemCatalogo=243756&pagina=1&tamanhoPagina=10"
```

**Resposta:**
```json
{
  "resultado": [],
  "totalRegistros": 0,
  "totalPaginas": 0
}
HTTP Status: 200
```

✅ API está **ONLINE**, mas **SEM preços** para este código.

#### Teste 2: Limite de paginação
```bash
curl "...&tamanhoPagina=5"
```

**Resposta:**
```
Informe um número de paginação no intervalo de 10 a 500
HTTP Status: 400
```

⚠️ API requer `tamanhoPagina` entre **10 e 500**. Código usa 100 ✅.

---

### 5. Comando que Atualiza as Flags

**Arquivo:** `app/Console/Commands/ComprasGovScout.php`

**Função:**
- Verifica TODOS os códigos CATMAT (336 mil)
- Para cada um, faz requisição rápida à API
- Marca `tem_preco_comprasgov = true` se API retornar dados
- Marca `tem_preco_comprasgov = false` se API não retornar dados

**Linha 32:** Busca apenas não verificados
```php
->whereNull('tem_preco_comprasgov') // Apenas os não verificados
```

**Status atual:**
- Comando já foi executado (todos verificados)
- Resultado: 99% dos códigos NÃO têm preços no Compras.gov

---

## 🎯 SOLUÇÕES PROPOSTAS

### Solução 1: Remover Filtro `tem_preco_comprasgov` (RECOMENDADA)

**Descrição:** Tentar buscar preços na API para TODOS os códigos CATMAT encontrados, independente da flag.

**Implementação:**
```php
// ANTES (linha 74-78 de routes/web.php):
->where(function($q) {
    $q->where('tem_preco_comprasgov', true)
      ->orWhereNull('tem_preco_comprasgov');
})

// DEPOIS (REMOVER o where acima completamente):
// Buscar todos os códigos ativos, sem filtrar por flag
```

**Vantagens:**
- ✅ Pode encontrar preços novos que não estavam disponíveis quando o scout rodou
- ✅ Usuário vê MAIS resultados
- ✅ Dados sempre atualizados

**Desvantagens:**
- ⚠️ Faz 30 requisições à API (limite atual) mesmo para códigos sem preços
- ⚠️ Resposta pode ser 2-5 segundos mais lenta
- ⚠️ Pode ultrapassar rate limits da API se muitas buscas simultâneas

**Estimativa de impacto:**
- Para "arroz": Tentará buscar 30 códigos (128 excluídos atualmente)
- Tempo adicional: ~3-6 segundos (30 códigos x 0.2s delay)

---

### Solução 2: Aumentar Limite de Materiais

**Descrição:** Manter filtro, mas buscar 100 códigos em vez de 30.

**Implementação:**
```php
// Linha 124 de routes/web.php:
// ANTES:
->limit(30)

// DEPOIS:
->limit(100)
```

**Vantagens:**
- ✅ Simples de implementar
- ✅ Mais chances de encontrar resultados

**Desvantagens:**
- ⚠️ Ainda limitado aos 1% que têm flag `true`
- ⚠️ Para "arroz", só tem 1 código marcado

---

### Solução 3: Busca Híbrida (MELHOR PERFORMANCE)

**Descrição:** Primeiro buscar códigos com flag `true`, depois buscar alguns sem flag.

**Implementação:**
```php
// Buscar 20 códigos com preço confirmado
$materiaisComPreco = $query
    ->where('tem_preco_comprasgov', true)
    ->limit(20)
    ->get();

// Buscar 10 códigos sem preço (tentar sorte)
$materiaisSemPreco = $query
    ->where('tem_preco_comprasgov', false)
    ->inRandomOrder()
    ->limit(10)
    ->get();

$materiais = $materiaisComPreco->merge($materiaisSemPreco);
```

**Vantagens:**
- ✅ Melhor dos dois mundos
- ✅ Não sobrecarrega API
- ✅ Ainda busca preços novos

**Desvantagens:**
- ⚠️ Mais complexo

---

### Solução 4: Re-executar Scout Periodicamente

**Descrição:** Agendar comando `comprasgov:scout` para rodar semanalmente.

**Implementação:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('comprasgov:scout --workers=10')
             ->weekly()
             ->sundays()
             ->at('02:00');
}
```

**Vantagens:**
- ✅ Mantém flags atualizadas
- ✅ Não impacta performance das buscas

**Desvantagens:**
- ⚠️ Demora 2-4 horas para verificar 336 mil códigos
- ⚠️ Resultado só disponível após próxima execução

---

## 🔧 RECOMENDAÇÃO FINAL

**Implementar Solução 1 + Solução 3 juntas:**

1. **Remover filtro** `tem_preco_comprasgov` da rota (Solução 1)
2. **Implementar cache de 7 dias** para não bater na API repetidamente
3. **Limitar a 50 códigos** em vez de 30 (meio termo)

**Código sugerido:**

```php
// routes/web.php - Linha 71-125

use Illuminate\Support\Facades\Cache;

// Buscar materiais SEM filtro de flag
$query = \DB::connection('pgsql_main')->table('cp_catmat')
    ->select('codigo', 'titulo')
    ->where('ativo', true);

// ... resto da lógica de busca ...

$materiais = $query
    ->orderBy('contador_ocorrencias', 'desc')
    ->limit(50) // Aumentado de 30 para 50
    ->get();

// PASSO 2: Para cada material, buscar preços (COM CACHE)
foreach ($materiais as $material) {
    // Cache de 7 dias para não ficar batendo na API
    $cacheKey = "comprasgov_precos_{$material->codigo}";

    $precos = Cache::remember($cacheKey, 60 * 60 * 24 * 7, function() use ($material) {
        try {
            $response = Http::timeout(10)->get($urlPrecos, [
                'codigoItemCatalogo' => $material->codigo,
                'pagina' => 1,
                'tamanhoPagina' => 100
            ]);

            if ($response->successful()) {
                return $response->json()['resultado'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    });

    foreach ($precos as $preco) {
        $resultados[] = [
            // ... resto do mapeamento ...
        ];
    }
}
```

**Benefícios:**
- ✅ Busca em TODOS os códigos CATMAT
- ✅ Cache evita requests repetidos
- ✅ Limite de 50 códigos equilibra performance
- ✅ Resultados sempre atualizados

---

## 📊 COMPARATIVO DAS SOLUÇÕES

| Solução | Resultados | Performance | Complexidade | Recomendado |
|---------|-----------|-------------|--------------|-------------|
| 1. Remover filtro | ⭐⭐⭐⭐⭐ Máximo | ⭐⭐⭐ Média | ⭐⭐⭐⭐ Baixa | ✅ SIM |
| 2. Aumentar limite | ⭐⭐ Baixo | ⭐⭐⭐⭐⭐ Alta | ⭐⭐⭐⭐⭐ Muito baixa | ❌ NÃO |
| 3. Busca híbrida | ⭐⭐⭐⭐ Alto | ⭐⭐⭐⭐ Alta | ⭐⭐ Média | ✅ SIM |
| 4. Scout periódico | ⭐⭐⭐ Médio | ⭐⭐⭐⭐⭐ Alta | ⭐⭐⭐ Média | ⚠️ COMPLEMENTAR |

---

## 🔄 OUTRAS GUIAS (PRÓXIMA ETAPA)

Conforme solicitado pelo usuário, ainda preciso estudar separadamente:

- ✅ **Modal de Cotação** (CONCLUÍDO)
- ⏳ **Pesquisa Rápida** (PENDENTE)
- ⏳ **Mapa de Atas** (PENDENTE)
- ⏳ **Mapa de Fornecedores** (PENDENTE)

Cada guia pode ter estrutura diferente de busca e filtros.

---

## 📝 CONCLUSÃO

O **código do Modal de Cotação está 100% correto**. O problema é:

1. **Filtro muito restritivo** na rota backend (apenas 1% dos códigos)
2. **Baixa disponibilidade de preços** na API do Compras.gov (problema externo)
3. **Scout já executado** marcou 99% dos códigos como `false`

**Solução:** Implementar **Solução 1 + Solução 3** (remover filtro + cache de 7 dias).

---

**Fim da Análise**
