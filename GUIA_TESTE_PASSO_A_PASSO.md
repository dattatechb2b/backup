# 📚 GUIA PASSO A PASSO - TESTE COMPLETO DA ETAPA 2

**Data:** 2025-10-20
**Sistema:** Cesta de Preços - Materlândia
**Objetivo:** Validar que as configurações da Etapa 2 estão sendo respeitadas

---

## 🚀 PASSO 1: PREPARAÇÃO DO AMBIENTE

### 1.1 Abrir o Navegador
1. Abra **Google Chrome** ou **Firefox** (recomendado)
2. Acesse: `https://materlandia.dattapro.online/cestadeprecos`
3. Faça login com suas credenciais

### 1.2 Abrir o Console do Desenvolvedor
1. Pressione **F12** no teclado
   - OU clique com botão direito → "Inspecionar"
2. Clique na aba **"Console"**
3. **IMPORTANTE:** Deixe este console aberto durante TODO o teste

**O que você verá:**
```
> Console aberto
> Possíveis mensagens de log do sistema
```

### 1.3 Limpar o Console
1. Clique no ícone 🚫 (Limpar console)
2. Ou digite `clear()` e pressione Enter

---

## 🔍 PASSO 2: VERIFICAÇÃO INICIAL

### 2.1 Carregar a Página do Orçamento
1. No menu lateral, clique em **"Orçamentos"**
2. Selecione um orçamento existente (ex: "Orçamento de Teste")
3. OU clique em "Novo Orçamento" e crie um

### 2.2 Verificar Console - Primeira Checagem
**Procure por estas mensagens no console:**

✅ **ESPERADO (BOM):**
```javascript
[CONFIG] Casas decimais: duas
[CONFIG] Método Juízo Crítico: saneamento_desvio_padrao
```

❌ **NÃO ESPERADO (PROBLEMA):**
```javascript
Uncaught ReferenceError: ORCAMENTO_CONFIG is not defined
Uncaught TypeError: Cannot read property 'casasDecimais' of undefined
```

**Se aparecer erro vermelho:**
- ❌ Pare aqui
- 📸 Tire print do console
- 📧 Me envie o print

---

## ⚙️ PASSO 3: TESTAR ETAPA 2 - AUTO-SAVE

### 3.1 Ir para Etapa 2
1. Na tela do orçamento, procure as abas no topo
2. Clique na aba **"2. Metodologias e Padrões"**

**O que você verá:**
- 📋 3 seções com radio buttons
- 📌 Seção 1: "Método do Juízo Crítico" (2 opções)
- 📌 Seção 2: "Método de Obtenção do Preço" (4 opções)
- 📌 Seção 3: "Casas Decimais" (2 opções)

### 3.2 Teste 1: Trocar Método de Saneamento

**AÇÃO:**
1. Clique no radio button: **"Saneamento com base em percentual"**

**AGUARDE:** 2 segundos (sistema salva automaticamente)

**VERIFICAR CONSOLE:**
```javascript
[AUTO-SAVE] Salvando metodologias...
[AUTO-SAVE] Dados enviados: {metodo_juizo_critico: "saneamento_percentual", ...}
[AUTO-SAVE] ✓ Metodologias salvas!
```

✅ **SE APARECEU:** Funcionou!
❌ **SE NÃO APARECEU:** Anote "Teste 3.2 FALHOU"

### 3.3 Teste 2: Trocar Método de Obtenção

**AÇÃO:**
1. Clique em: **"Mediana de todas as amostras"**

**AGUARDE:** 2 segundos

**VERIFICAR CONSOLE:**
```javascript
[AUTO-SAVE] ✓ Metodologias salvas!
```

### 3.4 Teste 3: Trocar Casas Decimais

**AÇÃO:**
1. Clique em: **"4 casas decimais"**

**AGUARDE:** 2 segundos

**VERIFICAR CONSOLE:**
```javascript
[AUTO-SAVE] ✓ Metodologias salvas!
```

---

## 🎭 PASSO 4: TESTAR PRÉ-SELEÇÃO DO MODAL

Este é o teste MAIS IMPORTANTE! Vamos verificar se o modal respeita a Etapa 2.

### 4.1 Configurar Etapa 2 para "Percentual"

**AÇÃO:**
1. Vá para Etapa 2
2. Selecione: **"Saneamento com base em percentual"**
3. **AGUARDE 2 segundos** (deixe salvar)
4. Vá para aba **"3. Itens do Orçamento"**

### 4.2 Abrir Modal de Análise Crítica

**AÇÃO:**
1. Encontre um item na lista (qualquer um)
2. Na coluna "Ações", clique no botão **"Análise"** (ícone de gráfico 📊)

**O MODAL VAI ABRIR**

### 4.3 VERIFICAÇÃO CRÍTICA DO MODAL

**VERIFICAR VISUALMENTE NO MODAL:**

✅ **O QUE DEVE ESTAR MARCADO:**
- [ ] Radio button "Método Percentual da Mediana" está SELECIONADO
- [ ] Campos "Percentual Inferior: 70%" estão VISÍVEIS
- [ ] Campos "Percentual Superior: 30%" estão VISÍVEIS

**VERIFICAR NO CONSOLE:**
```javascript
[ANALISE-CRITICA] ===== ABRIR MODAL =====
[ANALISE-CRITICA] Item ID: 123
[ANALISE-CRITICA] Configuração Etapa 2 - Método: saneamento_percentual
[ANALISE-CRITICA] ✓ Método Percentual pré-selecionado (Etapa 2)
```

✅ **SE TUDO APARECEU:** PERFEITO! A Etapa 2 está funcionando!
❌ **SE O RADIO ERRADO ESTÁ MARCADO:** FALHOU - anote

### 4.4 Fechar Modal e Testar Método Desvio-Padrão

**AÇÃO:**
1. Feche o modal (clique no X)
2. Volte para Etapa 2
3. Selecione: **"Saneamento pelo desvio-padrão"**
4. **AGUARDE 2 segundos**
5. Volte para Itens
6. Abra o modal "Análise" de novo

**VERIFICAR:**
- [ ] Radio "Método Desvio-Padrão (μ ± σ)" está SELECIONADO
- [ ] Campos de percentual estão ESCONDIDOS

**CONSOLE:**
```javascript
[ANALISE-CRITICA] ✓ Método Desvio-Padrão pré-selecionado (Etapa 2)
```

---

## 🧮 PASSO 5: TESTAR CÁLCULOS REAIS

**IMPORTANTE:** Este teste só funciona se o item tiver no mínimo 3 amostras de preço.

### 5.1 Verificar se Item Tem Amostras

**AÇÃO:**
1. Na aba "3. Itens do Orçamento"
2. Procure um item que tenha números na coluna "Amostras"
3. Ex: se aparecer "(5)" significa que tem 5 amostras

**SE NENHUM ITEM TEM AMOSTRAS:**
- 📋 Primeiro adicione amostras a um item
- 🔍 Busque preços no PNCP ou adicione manualmente

### 5.2 Teste Completo: Média com 2 Casas Decimais

**CONFIGURAR ETAPA 2:**
1. Vá para Etapa 2
2. Selecione:
   - ✅ "Saneamento pelo desvio-padrão"
   - ✅ "Média de todas as amostras"
   - ✅ "2 casas decimais"
3. **AGUARDE 2 segundos**

**APLICAR SANEAMENTO:**
1. Vá para aba Itens
2. Clique em "Análise" de um item que tenha amostras
3. Verifique que "Desvio-Padrão" está pré-selecionado
4. Clique em **"Aplicar Saneamento"**
5. **AGUARDE** a mensagem de sucesso

**VERIFICAR NO CONSOLE:**

Procure por um objeto JSON parecido com:
```javascript
{
  success: true,
  message: "Saneamento aplicado com sucesso!",
  snapshot: {
    calc_metodo: "MEDIA",        // ← DEVE SER "MEDIA"
    calc_media: 10.50,            // ← 2 CASAS DECIMAIS (não 10.5000)
    calc_mediana: 10.00,          // ← 2 CASAS DECIMAIS
    calc_dp: 1.23,                // ← 2 CASAS DECIMAIS
    calc_cv: 12.3000,             // ← CV sempre 4 casas
    calc_menor: 9.50,
    calc_maior: 12.00,
    ...
  }
}
```

✅ **VERIFICAR:**
- [ ] `calc_metodo` é **"MEDIA"** (porque selecionamos "Média de todas")
- [ ] Valores têm **2 casas decimais** (10.50 e não 10.5000)

### 5.3 Teste Completo: Mediana com 4 Casas Decimais

**CONFIGURAR ETAPA 2:**
1. Vá para Etapa 2
2. Selecione:
   - ✅ "Saneamento com base em percentual"
   - ✅ "Mediana de todas as amostras"
   - ✅ "4 casas decimais"
3. **AGUARDE 2 segundos**

**APLICAR SANEAMENTO:**
1. Vá para Itens
2. Abra modal "Análise" de outro item (ou do mesmo)
3. Verifique que "Percentual" está pré-selecionado
4. Clique em "Aplicar Saneamento"

**VERIFICAR NO CONSOLE:**
```javascript
{
  snapshot: {
    calc_metodo: "MEDIANA",       // ← DEVE SER "MEDIANA"
    calc_media: 10.5000,          // ← 4 CASAS DECIMAIS
    calc_mediana: 10.0000,        // ← 4 CASAS DECIMAIS
    calc_dp: 1.2345,              // ← 4 CASAS DECIMAIS
    ...
  }
}
```

✅ **VERIFICAR:**
- [ ] `calc_metodo` é **"MEDIANA"**
- [ ] Valores têm **4 casas decimais** (10.5000)

### 5.4 Teste Completo: Menor Preço

**CONFIGURAR ETAPA 2:**
1. Selecione:
   - ✅ Qualquer método de saneamento
   - ✅ **"Menor preço das amostras"**
   - ✅ Qualquer casas decimais

**APLICAR SANEAMENTO:**
1. Aplique em um item

**VERIFICAR NO CONSOLE:**
```javascript
{
  snapshot: {
    calc_metodo: "MENOR",         // ← DEVE SER "MENOR"
    ...
  }
}
```

### 5.5 Teste Completo: Automático (CV)

**CONFIGURAR ETAPA 2:**
1. Selecione:
   - ✅ **"Média (CV ≤ 25%) ou Mediana (CV > 25%)"**

**APLICAR SANEAMENTO:**

**VERIFICAR NO CONSOLE:**
```javascript
{
  snapshot: {
    calc_cv: 15.2345,             // ← Se CV ≤ 25%
    calc_metodo: "MEDIA",         // ← Deve usar MEDIA
  }
}

// OU

{
  snapshot: {
    calc_cv: 32.1234,             // ← Se CV > 25%
    calc_metodo: "MEDIANA",       // ← Deve usar MEDIANA
  }
}
```

---

## 🔍 PASSO 6: VERIFICAR ERROS COMUNS

### 6.1 Erros JavaScript

**VERIFICAR CONSOLE:**

❌ **SE APARECER:**
```javascript
Uncaught TypeError: Cannot read property 'checked' of null
```
**PROBLEMA:** Elemento HTML não foi encontrado
**AÇÃO:** Me avise

❌ **SE APARECER:**
```javascript
Uncaught ReferenceError: ORCAMENTO_CONFIG is not defined
```
**PROBLEMA:** Variável global não existe
**AÇÃO:** Me avise

### 6.2 Erros PHP

**SE O SANEAMENTO FALHAR, VERIFICAR RESPOSTA:**

❌ **SE APARECER:**
```javascript
{
  success: false,
  message: "Erro ao aplicar saneamento: Call to undefined method..."
}
```
**PROBLEMA:** Método não existe no Service
**AÇÃO:** Me avise imediatamente

### 6.3 Erro 500 (Server Error)

❌ **SE APARECER:**
```
500 Internal Server Error
```
**PROBLEMA:** Erro PHP no servidor
**AÇÃO:** Verificar logs do Laravel

---

## 📊 PASSO 7: RESULTADOS

### 7.1 Preencher Checklist

Volte para o arquivo `CHECKLIST_TESTE_ETAPA2.md` e marque todos os itens que funcionaram.

### 7.2 Calcular Taxa de Sucesso

**Conte quantos checks você marcou:**
- 58-60 checks: ✅ **PERFEITO!**
- 50-57 checks: ⚠️ **Funcionando mas tem problemas**
- <50 checks: ❌ **Não funcionando**

### 7.3 Documentar Problemas

**SE ALGO FALHOU:**

Anote aqui:
```
TESTE FALHOU: (nome do teste)
ERRO: (mensagem de erro do console)
PRINT: (anexar screenshot)
```

---

## 🆘 AJUDA RÁPIDA

### Console Limpo (Sem Erros)
✅ **Isso é BOM:**
```
[CONFIG] Casas decimais: duas
[AUTO-SAVE] ✓ Metodologias salvas!
[ANALISE-CRITICA] ✓ Método Desvio-Padrão pré-selecionado
```

### Console com Erros
❌ **Isso é RUIM:**
```
❌ Uncaught TypeError: ...
❌ 500 Internal Server Error
❌ Undefined variable: ...
```

---

## 🎯 PRÓXIMOS PASSOS

**SE TUDO FUNCIONOU:**
1. ✅ Marque o checklist completo
2. 🎉 Comemore!
3. 📧 Me confirme que está 100%

**SE ALGO FALHOU:**
1. 📸 Tire prints do console
2. 📋 Copie mensagens de erro
3. 📧 Me envie para análise
4. 🔧 Eu corrijo imediatamente

---

## 🔐 ROLLBACK DE EMERGÊNCIA

**SE TUDO DEU MUITO ERRADO:**

```bash
cd /home/dattapro/modulos/cestadeprecos

# Reverter TUDO de uma vez:
cp app/Services/EstatisticaService.php.backup_antes_etapa2_20251020_173314 app/Services/EstatisticaService.php
cp app/Http/Controllers/OrcamentoController.php.backup_antes_etapa2_20251020_173327 app/Http/Controllers/OrcamentoController.php
cp resources/views/orcamentos/elaborar.blade.php.backup_antes_etapa2_20251020_173425 resources/views/orcamentos/elaborar.blade.php

# Recarregar página (Ctrl + F5)
```

---

**Boa sorte nos testes! Estou aqui para ajudar! 🚀**
