# 🔧 GUIA DE DEBUG - ETAPA 2

**Se algo der errado durante os testes, use este guia!**

---

## 🚨 CENÁRIOS DE PROBLEMA

### PROBLEMA 1: Console mostra erro "ORCAMENTO_CONFIG is not defined"

**Causa:** Variável JavaScript não foi inicializada

**Como investigar:**
1. No console, digite: `console.log(ORCAMENTO_CONFIG)`
2. Pressione Enter

**Resultado esperado:**
```javascript
{casasDecimais: "duas", metodoJuizoCritico: "saneamento_desvio_padrao"}
```

**Se aparecer "undefined":**
```javascript
// Digite no console para verificar se $orcamento existe:
console.log('{{ $orcamento->id ?? "ORCAMENTO NAO EXISTE" }}')
```

**Solução:**
- Recarregue a página (Ctrl + F5)
- Se persistir, o orçamento pode não estar carregado corretamente

---

### PROBLEMA 2: Radio button não está pré-selecionado no modal

**Causa possível 1:** Evento 'change' não disparou

**Como investigar no console:**
```javascript
// Verificar se elemento existe:
document.getElementById('metodo-dp')  // Deve retornar <input...>
document.getElementById('metodo-percentual')  // Deve retornar <input...>

// Verificar qual está marcado:
document.getElementById('metodo-dp').checked  // true ou false
document.getElementById('metodo-percentual').checked  // true ou false
```

**Causa possível 2:** Modal abre antes do JavaScript executar

**Solução:**
- Feche e abra o modal novamente
- Verifique console se mostra `[ANALISE-CRITICA] ✓ Método ... pré-selecionado`

---

### PROBLEMA 3: Campos de percentual não aparecem

**Causa:** Evento 'change' não disparou a função `togglePercentualInputs()`

**Como investigar no console:**
```javascript
// Forçar mostrar campos:
document.getElementById('percentual-inputs').style.display = 'block'

// Se aparecer, significa que evento não disparou
```

**Verificar se função existe:**
```javascript
typeof togglePercentualInputs  // Deve retornar "function"
```

**Forçar disparo do evento:**
```javascript
document.getElementById('metodo-percentual').dispatchEvent(new Event('change'))
```

---

### PROBLEMA 4: Saneamento falha com erro 500

**Causa:** Erro PHP no backend

**Como investigar:**

#### 4.1 Ver detalhes do erro no console (aba Network)
1. Abra aba **Network** (Rede) no F12
2. Clique em "Aplicar Saneamento"
3. Procure requisição que ficou vermelha (status 500)
4. Clique nela
5. Vá para aba **"Response"**
6. Copie o erro completo

#### 4.2 Ver logs do Laravel
```bash
cd /home/dattapro/modulos/cestadeprecos
tail -50 storage/logs/laravel.log
```

**Erros comuns:**

❌ **Erro: "Call to undefined method"**
```
Call to undefined method App\Services\EstatisticaService::aplicarSaneamentoDP()
```
**Causa:** Service não foi salvo corretamente
**Solução:** Reverter para backup

❌ **Erro: "Undefined variable: metodoObtencao"**
```
Undefined variable: metodoObtencao in OrcamentoController.php
```
**Causa:** Variável não foi declarada
**Solução:** Controller não foi salvo corretamente

---

### PROBLEMA 5: Configuração não está salvando

**Causa:** Auto-save não está funcionando

**Como investigar no console:**
```javascript
// Verificar se evento está registrado:
document.querySelectorAll('input[name="metodo_juizo_critico"]').length  // Deve retornar 2

// Testar manualmente o fetch:
fetch(window.APP_BASE_PATH + '/orcamentos/{{ $orcamento->id ?? 1 }}', {
    method: 'PATCH',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        metodo_juizo_critico: 'saneamento_percentual'
    })
})
.then(r => r.json())
.then(data => console.log('RESPOSTA:', data))
```

**Resposta esperada:**
```javascript
{success: true, message: "Orçamento atualizado..."}
```

---

### PROBLEMA 6: calc_metodo não é o esperado

**Exemplo:** Configurei "Média de todas" mas retornou "MEDIANA"

**Como investigar:**

#### 6.1 Verificar o que foi salvo no banco
```bash
cd /home/dattapro/modulos/cestadeprecos
php artisan tinker
```

```php
config(['database.connections.tenant_materlandia' => [
    'driver' => 'pgsql',
    'host' => '127.0.0.1',
    'port' => 5432,
    'database' => 'materlandia_db',
    'username' => 'materlandia_user',
    'password' => '53zRUwrSIhY0bSCXVAzwz8MOlyAxLaye',
    'charset' => 'utf8',
    'prefix' => 'cp_',
    'schema' => 'public',
]]);

DB::purge('tenant_materlandia');

$orcamento = DB::connection('tenant_materlandia')
    ->table('orcamentos')
    ->where('id', 1)  // ← Troque pelo ID do seu orçamento
    ->first();

echo "Método Juízo Crítico: " . $orcamento->metodo_juizo_critico . "\n";
echo "Método Obtenção: " . $orcamento->metodo_obtencao_preco . "\n";
echo "Casas Decimais: " . $orcamento->casas_decimais . "\n";
```

**Valores esperados:**
```
Método Juízo Crítico: saneamento_desvio_padrao (ou saneamento_percentual)
Método Obtenção: media_mediana, media_todas, mediana_todas, ou menor_preco
Casas Decimais: duas (ou quatro)
```

#### 6.2 Verificar mapeamento no Controller

Verifique se o mapeamento está correto:
```bash
grep -A 5 "metodoMap = " app/Http/Controllers/OrcamentoController.php
```

**Esperado:**
```php
$metodoMap = [
    'media_mediana' => 'auto',
    'media_todas' => 'media',
    'mediana_todas' => 'mediana',
    'menor_preco' => 'menor'
];
```

---

### PROBLEMA 7: Casas decimais incorretas

**Exemplo:** Configurei 4 casas mas retornou 2

**Como investigar:**

#### Verificar configuração:
```javascript
// No console:
ORCAMENTO_CONFIG.casasDecimais  // Deve ser "duas" ou "quatro"
```

#### Verificar no Service:
```bash
grep -A 3 "casasDecimais ===" app/Http/Controllers/OrcamentoController.php
```

**Esperado:**
```php
$casasDecimais = $orcamento->casas_decimais === 'quatro' ? 4 : 2;
```

---

## 🔍 COMANDOS ÚTEIS DE DEBUG

### Ver sintaxe PHP
```bash
php -l app/Services/EstatisticaService.php
php -l app/Http/Controllers/OrcamentoController.php
```

### Ver últimos logs
```bash
tail -100 storage/logs/laravel.log
```

### Limpar cache Laravel
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Recarregar página sem cache
No navegador:
- **Ctrl + F5** (Windows/Linux)
- **Cmd + Shift + R** (Mac)

---

## 🆘 TESTES MANUAIS NO CONSOLE

### Testar se Service recebe parâmetros corretos

Adicione temporariamente no Controller (linha 7793):
```php
\Log::info('ETAPA 2 DEBUG', [
    'metodoObtencao' => $metodoObtencao,
    'casasDecimais' => $casasDecimais,
    'config_banco' => [
        'metodo_obtencao_preco' => $orcamento->metodo_obtencao_preco,
        'casas_decimais' => $orcamento->casas_decimais,
    ]
]);
```

Depois aplique saneamento e veja log:
```bash
tail -20 storage/logs/laravel.log
```

---

## 📊 VERIFICAR DADOS NO BANCO

### Conectar ao banco Materlândia:
```bash
PGPASSWORD='53zRUwrSIhY0bSCXVAzwz8MOlyAxLaye' psql -h 127.0.0.1 -U materlandia_user -d materlandia_db
```

### Ver configurações de todos orçamentos:
```sql
SELECT
    id,
    nome,
    metodo_juizo_critico,
    metodo_obtencao_preco,
    casas_decimais
FROM cp_orcamentos
ORDER BY id DESC
LIMIT 10;
```

### Ver snapshot de um item:
```sql
SELECT
    id,
    descricao,
    calc_metodo,
    calc_media,
    calc_mediana,
    calc_dp,
    calc_cv
FROM cp_itens_orcamento
WHERE orcamento_id = 1  -- ← Troque pelo ID do orçamento
LIMIT 5;
```

---

## 🔄 ROLLBACK RÁPIDO

**Se algo quebrou:**
```bash
cd /home/dattapro/modulos/cestadeprecos

# Reverter Service:
cp app/Services/EstatisticaService.php.backup_antes_etapa2_20251020_173314 \
   app/Services/EstatisticaService.php

# Reverter Controller:
cp app/Http/Controllers/OrcamentoController.php.backup_antes_etapa2_20251020_173327 \
   app/Http/Controllers/OrcamentoController.php

# Reverter View:
cp resources/views/orcamentos/elaborar.blade.php.backup_antes_etapa2_20251020_173425 \
   resources/views/orcamentos/elaborar.blade.php

# Limpar cache:
php artisan view:clear

# Recarregar página no navegador (Ctrl + F5)
```

---

## 📞 QUANDO ME CHAMAR

**Me chame SE:**
- ❌ Erro 500 persistente
- ❌ JavaScript não funciona mesmo após reload
- ❌ Cálculos retornam valores absurdos
- ❌ Rollback não funciona

**ME ENVIE:**
1. 📸 Print do console (F12 → Console)
2. 📸 Print da aba Network (se erro 500)
3. 📋 Últimas 50 linhas do laravel.log
4. 📝 Descrição do que estava fazendo quando deu erro

---

**Este guia cobre 99% dos problemas possíveis! 🔧**
