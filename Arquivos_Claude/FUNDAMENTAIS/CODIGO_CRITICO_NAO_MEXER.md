# ⛔ CÓDIGO CRÍTICO - NÃO MEXER

**ATENÇÃO:** Este documento lista código que está **FUNCIONANDO PERFEITAMENTE** e **NÃO DEVE SER ALTERADO** sem extrema necessidade.

---

## 🚨 REGRA DE OURO

**ANTES de modificar qualquer código listado aqui, PARE e PERGUNTE ao usuário.**

Estas partes já foram depuradas múltiplas vezes e alterá-las causa regressão de bugs que já foram corrigidos.

---

## 📂 ARQUIVOS CRÍTICOS

### 1. OrcamentoController.php - Método store()

**Arquivo:** `app/Http/Controllers/OrcamentoController.php`
**Linhas:** 33-218 (método completo)

**O QUE FAZ:**
- Recebe POST do formulário "Novo Orçamento"
- Valida campos
- Cria orçamento no banco
- **REDIRECIONA via JavaScript para página de elaboração**

**⛔ NÃO MEXER EM:**
```php
// Linhas 85-94: Criação do orçamento
$orcamento = Orcamento::create([
    'nome' => $validated['nome'],
    'referencia_externa' => $validated['referencia_externa'] ?? null,
    'objeto' => $validated['objeto'],
    'orgao_interessado' => $validated['orgao_interessado'] ?? null,
    'tipo_criacao' => $validated['tipo_criacao'],
    'orcamento_origem_id' => $validated['orcamento_origem_id'] ?? null,
    'status' => 'pendente',
    'user_id' => Auth::id(),
]);
```

**⛔ NÃO MEXER EM:**
```php
// Linhas 150-217: Redirect via JavaScript (SOLUÇÃO DEFINITIVA)
$urlElaborar = route('orcamentos.elaborar', ['id' => $orcamento->id, 'msg' => 'success']);
$urlRelativa = parse_url($urlElaborar, PHP_URL_PATH);
if ($query = parse_url($urlElaborar, PHP_URL_QUERY)) {
    $urlRelativa .= '?' . $query;
}
$urlRelativa = ltrim($urlRelativa, '/'); // CRÍTICO: remove barra inicial

session()->flash('success', $mensagem);
session()->save(); // Forçar save imediato

// Retornar HTML com JavaScript redirect
$html = '<!DOCTYPE html>...'
return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
```

**POR QUE NÃO MEXER:**
- Usa `ltrim($urlRelativa, '/')` para gerar URL relativa sem barra (essencial para tag `<base>`)
- Usa JavaScript redirect ao invés de HTTP 302 (funciona em iframe)
- Salva mensagem na sessão ANTES do redirect
- Já foi corrigido 4 vezes devido a alterações acidentais

**SE PRECISAR ALTERAR:**
- ❌ NUNCA mude a lógica de geração da URL
- ❌ NUNCA volte a usar `redirect()->route()`
- ❌ NUNCA adicione `/` inicial na URL
- ✅ APENAS modifique validações se necessário
- ✅ APENAS adicione novos campos opcionais

---

### 2. create.blade.php - Formulário e JavaScript

**Arquivo:** `resources/views/orcamentos/create.blade.php`
**Linhas:** 200-753

**⛔ NÃO MEXER EM:**
```html
<!-- Linha 200: Action do form -->
<form method="POST" action="{{ route('orcamentos.store') }}" id="form-orcamento" enctype="multipart/form-data">
```

**⛔ NÃO MEXER EM:**
```javascript
// Linhas 567-598: Função gerenciarCamposRequired()
function gerenciarCamposRequired(abaAtiva) {
    // DESABILITAR todos os campos de input das 3 abas
    document.querySelectorAll('#content-do-zero input, #content-do-zero textarea').forEach(el => {
        if (el.id !== 'tipo_criacao') {
            el.disabled = true;
            el.removeAttribute('required');
        }
    });
    // ... resto do código
}
```

**POR QUE NÃO MEXER:**
- Gerencia corretamente campos required entre abas
- Desabilita campos inativos (evita enviar dados errados)
- Já foi corrigido para não enviar campos vazios que causam erro

**SE PRECISAR ALTERAR:**
- ✅ APENAS adicione novos campos seguindo o mesmo padrão
- ❌ NUNCA remova a lógica de enable/disable
- ❌ NUNCA remova a lógica de required

---

### 3. elaborar.blade.php - Modal de Sucesso

**Arquivo:** `resources/views/orcamentos/elaborar.blade.php`
**Linhas:** 7-65

**⛔ NÃO MEXER EM:**
```javascript
// Linhas 26-60: Lógica do modal
const urlParams = new URLSearchParams(window.location.search);
const msgParam = urlParams.get('msg');
const modalJaMostrado = sessionStorage.getItem('modalSucessoMostrado_{{ $orcamento->id }}');

if ((msgParam === 'success' || sessionSuccess) && !modalJaMostrado) {
    modal.style.display = 'flex';
    sessionStorage.setItem('modalSucessoMostrado_{{ $orcamento->id }}', 'true');
    // Remover parâmetro da URL
    if (msgParam === 'success') {
        const url = new URL(window.location);
        url.searchParams.delete('msg');
        window.history.replaceState({}, '', url);
    }
}
```

**POR QUE NÃO MEXER:**
- Usa `sessionStorage` para mostrar modal apenas 1x
- Remove parâmetro `?msg=success` da URL após mostrar
- Verifica tanto URL quanto session (dupla garantia)

**SE PRECISAR ALTERAR:**
- ✅ APENAS mude o texto/estilo do modal
- ❌ NUNCA remova a lógica de sessionStorage
- ❌ NUNCA remova a lógica de remover parâmetro da URL

---

### 4. ModuleProxyController.php - Redirect Handling

**Arquivo:** `minhadattatech/app/Http/Controllers/ModuleProxyController.php`
**Linhas:** 105-125

**⛔ NÃO MEXER EM:**
```php
// Linhas 107-125: Fazer requisição com redirects
$response = match($request->method()) {
    'GET' => Http::withHeaders($headers)
        ->withOptions(['allow_redirects' => true])
        ->get($moduleUrl),
    'POST' => Http::withHeaders($headers)
        ->withOptions(['allow_redirects' => true])
        ->asForm()
        ->post($moduleUrl, $request->all()),
    // ... outros métodos
};
```

**POR QUE NÃO MEXER:**
- `->withOptions(['allow_redirects' => true])` essencial para seguir redirects HTTP
- Sem isso, redirects 302 não funcionam

**SE PRECISAR ALTERAR:**
- ❌ NUNCA remova `['allow_redirects' => true]`
- ✅ APENAS adicione novos headers se necessário

---

### 5. ProxyAuth.php - Sessão Stateless

**Arquivo:** `app/Http/Middleware/ProxyAuth.php`
**Linhas:** 91-111

**⛔ NÃO MEXER EM:**
```php
// Linhas 94-98: Usar setUser ao invés de login
$sessionIdBefore = session()->getId();
Auth::setUser($user);
session()->save(); // CRÍTICO para iframes
$sessionIdAfter = session()->getId();
```

**POR QUE NÃO MEXER:**
- `Auth::setUser()` não regenera session_id (evita erro 419 CSRF)
- `Auth::login()` regenera session_id e quebra CSRF tokens
- Já foi corrigido e está documentado no CONTEXTO_PROJETO.md

**SE PRECISAR ALTERAR:**
- ❌ NUNCA volte a usar `Auth::login()`
- ❌ NUNCA remova `session()->save()`

---

## 🧪 COMO TESTAR APÓS ALTERAÇÕES

Se você **PRECISAR** modificar código crítico (com autorização do usuário), teste:

### Teste 1: Criar Orçamento
1. Acesse https://catasaltas.dattapro.online/desktop
2. NOVO ORÇAMENTO → Aba "CRIAR DO ZERO"
3. Preencha: Nome + Objeto
4. Clique SALVAR
5. **DEVE:** Spinner → Redirecionar → Modal verde → Página de elaboração

### Teste 2: Campos Opcionais
1. NOVO ORÇAMENTO → Aba "CRIAR DO ZERO"
2. Preencha APENAS Nome e Objeto (deixe outros vazios)
3. Clique SALVAR
4. **DEVE:** Funcionar normalmente (sem erro)

### Teste 3: Modal Aparece Uma Vez
1. Crie orçamento (teste 1)
2. Veja modal aparecer
3. Clique OK
4. Recarregue a página (F5)
5. **DEVE:** Modal NÃO aparecer novamente

### Teste 4: URL Sem Barra Inicial
1. Crie orçamento
2. Abra DevTools → Network
3. Verifique URL do redirect
4. **DEVE:** Ser `orcamentos/34/elaborar` (SEM `/` inicial)

---

## 📋 CHECKLIST ANTES DE COMMIT

Antes de fazer commit de alterações em código crítico:

- [ ] Li este documento CODIGO_CRITICO_NAO_MEXER.md?
- [ ] Perguntei ao usuário se posso mexer neste código?
- [ ] Entendi completamente por que o código está assim?
- [ ] Executei os 4 testes acima?
- [ ] Todos os testes passaram?
- [ ] Documentei a mudança no CONTEXTO_PROJETO.md?
- [ ] Fiz backup do código anterior?

**SE RESPONDEU "NÃO" PARA QUALQUER ITEM:**
→ **PARE** e **PERGUNTE AO USUÁRIO** antes de continuar

---

## 🎯 FILOSOFIA

### Por que este documento existe?

Este código foi corrigido **MÚLTIPLAS VEZES** devido a:
1. Alterações acidentais ao modificar página de elaboração
2. "Melhorias" que na verdade quebraram funcionalidades
3. Mudanças sem entender o contexto completo
4. Regressão de bugs já corrigidos

### Quando posso ignorar este documento?

**NUNCA**, a menos que:
- Usuário explicitamente pediu para alterar este código específico
- Bug crítico que impede uso do sistema
- Você entende 100% do impacto e testou extensivamente

### O que fazer se o código precisar mudar?

1. **PERGUNTE ao usuário primeiro**
2. Leia CONTEXTO_PROJETO.md para entender histórico
3. Leia FIX_419_CSRF_ERROR.md para entender problemas anteriores
4. Faça backup do código atual
5. Documente ANTES de alterar
6. Teste EXTENSIVAMENTE após alterar
7. Documente DEPOIS de alterar
8. Atualize este documento se necessário

---

## 🔒 PROTEÇÃO FINAL

**AVISO PARA FUTURE CLAUDE:**

Se você está lendo isso, é porque está prestes a modificar código na área de criação de orçamentos ou redirecionamento.

**PARE AGORA.**

Leia este documento INTEIRO.
Leia CONTEXTO_PROJETO.md.
Leia REGRAS_FUNDAMENTAIS.md.

**ENTÃO:**
- Pergunte ao usuário se pode modificar
- Explique o que vai mudar e POR QUÊ
- Aguarde confirmação explícita

**NÃO:**
- ❌ Assuma que sabe melhor
- ❌ "Melhore" código que já funciona
- ❌ Mude URLs de relativas para absolutas
- ❌ Volte a usar Auth::login()
- ❌ Remova JavaScript redirects

**LEMBRE-SE:**
"Se está funcionando, não mexa."

---

**Criado em:** 01/10/2025 18:45 BRT
**Motivo:** Múltiplas regressões ao alterar código de elaboração
**Última atualização:** 01/10/2025 18:45 BRT
