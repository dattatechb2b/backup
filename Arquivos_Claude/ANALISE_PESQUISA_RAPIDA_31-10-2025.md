# 🔍 ANÁLISE COMPLETA: Pesquisa Rápida

**Data:** 31/10/2025 09:00
**Guia:** Pesquisa Rápida
**Status:** ✅ FUNCIONANDO CORRETAMENTE (SEM PROBLEMAS)

---

## 📋 RESUMO EXECUTIVO

**Descoberta importante:** A Pesquisa Rápida **NÃO tem o mesmo problema** que o Modal de Cotação tinha!

O código da Pesquisa Rápida **JÁ está correto** e NÃO filtra por `tem_preco_comprasgov = true`. Por isso, ela retorna resultados do Compras.gov normalmente.

---

## 🏗️ ESTRUTURA DA PESQUISA RÁPIDA

### Arquivos Envolvidos

**View:**
- `/home/dattapro/modulos/cestadeprecos/resources/views/pesquisa-rapida.blade.php` (1.383 linhas)

**Controller:**
- `/home/dattapro/modulos/cestadeprecos/app/Http/Controllers/PesquisaRapidaController.php` (1.519 linhas)

**Rota:**
```php
// Linha 258 de routes/web.php
Route::get('/pesquisa/buscar', [PesquisaRapidaController::class, 'buscar'])
    ->name('pesquisa.buscar.public');
```

---

## 🔄 FLUXO DE FUNCIONAMENTO

### JavaScript (Linha 608 do pesquisa-rapida.blade.php)

```javascript
// Construir URL (considerando proxy)
const urlBase = `${window.APP_BASE_PATH}/pesquisa/buscar`;

const response = await fetch(`${urlBase}?termo=${encodeURIComponent(descricao)}`);
const data = await response.json();
```

### Controller: método `buscar()` (Linha 106-304)

**Busca em MÚLTIPLAS fontes (7 APIs):**

1. **CMED** - Medicamentos ANVISA (linha 130-142)
   - Tabela local: `cp_medicamentos_cmed`
   - 26.046 medicamentos
   - ✅ Retorna preços CMED

2. **CATMAT + Compras.gov** (linha 145-157)
   - **MÉTODO CRÍTICO:** `buscarNoCATMATComPrecos()`
   - ✅ **SEM filtro tem_preco_comprasgov**
   - Busca em todos os códigos CATMAT
   - Fallback para API tempo real

3. **Banco Local PNCP** (linha 160-168)
   - Tabela: `contratos_pncp`
   - Contratos já sincronizados

4. **API PNCP Contratos** (linha 171-179)
   - API `/api/search` do PNCP
   - Busca contratos em tempo real

5. **LicitaCon (TCE-RS)** (linha 186-198)
   - API CKAN do TCE-RS
   - Contratos e licitações do RS

6. **Comprasnet (SIASG)** (linha 201-213)
   - API federal de contratos
   - Comprasnet.gov.br

7. **Portal Transparência (CGU)** (linha 216-228)
   - **DESABILITADO** (requer codigoOrgao)
   - API com chave

---

## 🔑 DIFERENÇA CRÍTICA: Pesquisa Rápida vs Modal de Cotação

### Pesquisa Rápida (✅ CORRETO)

**Método:** `buscarNaAPIComprasGovTempoReal()` (linha 1100-1223)

**Busca CATMAT (linha 1106-1119):**
```php
$catmats = DB::connection('pgsql_main')
    ->table('cp_catmat')
    ->select('codigo', 'titulo')
    ->where('ativo', true)  // ✅ SÓ filtra por ativo!
    ->where(function($q) use ($termoEscapado) {
        $q->whereRaw(
            "to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)",
            [$termoEscapado]
        )
        ->orWhere('titulo', 'ILIKE', "%{$termoEscapado}%");
    })
    ->orderBy('contador_ocorrencias', 'desc')
    ->limit(3)
    ->get();
```

✅ **NÃO tem filtro `tem_preco_comprasgov = true`**
✅ Busca em TODOS os 336 mil códigos CATMAT ativos
✅ Por isso retorna resultados do Compras.gov

---

### Modal de Cotação (❌ TINHA PROBLEMA - JÁ CORRIGIDO)

**Rota:** `/compras-gov/buscar` (routes/web.php, linha 55-224)

**ANTES da correção (linha 74-78):**
```php
->where('ativo', true)
->where(function($q) {
    // ❌ FILTRO RESTRITIVO: Apenas 1% dos códigos
    $q->where('tem_preco_comprasgov', true)
      ->orWhereNull('tem_preco_comprasgov');
});
```

❌ Filtrava 99% dos códigos CATMAT
❌ Resultado: ZERO resultados para qualquer termo

**DEPOIS da correção:**
```php
->where('ativo', true);
// ✅ FIX 31/10/2025: Removido filtro tem_preco_comprasgov
```

✅ Agora funciona igual à Pesquisa Rápida
✅ Busca em TODOS os códigos CATMAT

---

## 📊 ESTRATÉGIA DE BUSCA DA PESQUISA RÁPIDA

### 1. Busca Local PRIMEIRO (Mais Rápido)

**Método:** `buscarNoCATMATComPrecos()` (linha 990-1084)

**Tabela:** `cp_precos_comprasgov` (preços baixados previamente)

```php
$precos = DB::connection('pgsql_main')
    ->table('cp_precos_comprasgov')
    ->whereRaw(
        "to_tsvector('simple', descricao_item) @@ plainto_tsquery('simple', ?)",
        [$termoEscapado]
    )
    ->where('preco_unitario', '>', 0)
    ->orderBy('data_compra', 'desc')
    ->limit(200)
    ->get();
```

✅ **Vantagens:**
- Resposta instantânea (< 1 segundo)
- Sem depender da API externa
- Dados já validados

**Se encontrar:** Retorna imediatamente
**Se NÃO encontrar:** Fallback para API tempo real

---

### 2. Fallback: API Tempo Real (Mais Completo)

**Método:** `buscarNaAPIComprasGovTempoReal()` (linha 1100-1223)

**Estratégia:**
1. Busca 3 códigos CATMAT mais relevantes
2. Para cada código, consulta API de preços
3. Limita a 100 resultados por código
4. Filtra apenas últimos 12 meses
5. Remove valores zerados

```php
$response = Http::timeout(10)
    ->withHeaders([
        'Accept' => '*/*',
        'User-Agent' => 'DattaTech-CestaPrecos/1.0'
    ])
    ->get('https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial', [
        'codigoItemCatalogo' => $catmat->codigo,
        'pagina' => 1,
        'tamanhoPagina' => 100
    ]);
```

✅ **Vantagens:**
- Dados sempre atualizados
- Maior cobertura (API tem mais dados que base local)
- Busca em TODOS os códigos (não apenas 1%)

---

## ✅ CONCLUSÃO: PESQUISA RÁPIDA ESTÁ CORRETA

### NÃO precisa de correção!

A Pesquisa Rápida **JÁ funciona corretamente** porque:

1. ✅ Busca primeiro na tabela LOCAL `cp_precos_comprasgov`
2. ✅ Se não encontrar, busca na API em tempo real
3. ✅ **NÃO tem filtro** `tem_preco_comprasgov = true`
4. ✅ Busca em TODOS os 336 mil códigos CATMAT ativos
5. ✅ Retorna resultados do Compras.gov normalmente

### Diferença em relação ao Modal de Cotação

| Aspecto | Pesquisa Rápida | Modal de Cotação (ANTES) | Modal de Cotação (DEPOIS) |
|---------|----------------|-------------------------|---------------------------|
| Filtro CATMAT | ✅ SÓ `ativo=true` | ❌ `tem_preco_comprasgov=true` | ✅ SÓ `ativo=true` |
| Códigos buscados | ✅ 336 mil (100%) | ❌ 3.6 mil (1%) | ✅ 336 mil (100%) |
| Resultados Compras.gov | ✅ SIM | ❌ NÃO | ✅ SIM |
| Status | ✅ CORRETO | ❌ PROBLEMA | ✅ CORRIGIDO |

---

## 🔍 COMPARATIVO: PESQUISA RÁPIDA vs MODAL DE COTAÇÃO

### Semelhanças

Ambos:
- Buscam no Compras.gov via API
- Usam tabela CATMAT local
- Consultam API `dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial`
- Filtram valores zerados
- Limitam resultados (100-300)

### Diferenças

| Característica | Pesquisa Rápida | Modal de Cotação |
|---------------|-----------------|------------------|
| **Escopo** | Busca AMPLA em 7 APIs | Busca FOCADA em 3 APIs |
| **APIs consultadas** | CMED + Compras.gov + PNCP + LicitaCon + Comprasnet + Portal CGU + Banco Local | PNCP + CMED + Compras.gov |
| **Tabela local** | `cp_precos_comprasgov` | ❌ NÃO usa (só API) |
| **Fallback** | Local → API tempo real | Direto na API |
| **Limite CATMAT** | 3 códigos | 30-50 códigos |
| **Limite resultados** | 100 por CATMAT | 300 total |
| **Formato resposta** | Array agrupado | Array individual |
| **Finalidade** | Pesquisa exploratória | Cotação específica de item |

---

## 📝 RECOMENDAÇÕES

### 1. Manter Como Está ✅

A Pesquisa Rápida **NÃO precisa de alterações**. O código está bem estruturado e funcional.

### 2. Documentar Diferenças

Criar documentação explicando que:
- Pesquisa Rápida = Busca AMPLA (7 APIs)
- Modal de Cotação = Busca FOCADA (3 APIs)

Cada um tem propósito diferente.

### 3. Considerar Unificar Lógica (Futuro)

Se desejar, pode-se criar um Service compartilhado:

```php
// app/Services/ComprasGovService.php
class ComprasGovService {
    public function buscarPrecos($termo, $opcoes = []) {
        // Lógica unificada de busca
        // Usado por Pesquisa Rápida E Modal de Cotação
    }
}
```

**Vantagens:**
- ✅ Código único (DRY)
- ✅ Manutenção centralizada
- ✅ Comportamento consistente

**Desvantagens:**
- ⚠️ Precisa refatoração
- ⚠️ Pode quebrar funcionalidades atuais
- ⚠️ Requer testes extensivos

**DECISÃO:** Por enquanto, manter separado. Funciona bem.

---

## 🎯 PRÓXIMOS PASSOS

Conforme solicitado, estudar as outras guias SEPARADAMENTE:

- ✅ **Pesquisa Rápida** - CONCLUÍDO
- ⏳ **Mapa de Atas** - PENDENTE
- ⏳ **Mapa de Fornecedores** - PENDENTE

Cada guia pode ter estrutura e comportamento diferentes.

---

**Fim do Documento - Pesquisa Rápida**
