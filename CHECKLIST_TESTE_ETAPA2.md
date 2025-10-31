# ✅ CHECKLIST DE TESTE - ETAPA 2 FUNCIONAL

**Data:** 2025-10-20
**Responsável:** Cláudio
**Sistema:** Cesta de Preços - Materlândia

---

## 📋 CHECKLIST RÁPIDO

### FASE 1: PREPARAÇÃO
- [ ] Navegador aberto (Chrome/Firefox recomendado)
- [ ] Console do navegador aberto (F12 → aba Console)
- [ ] Acesso a https://materlandia.dattapro.online/cestadeprecos
- [ ] Login realizado
- [ ] Orçamento existente selecionado (ou criar novo)

### FASE 2: VERIFICAÇÃO INICIAL
- [ ] Console não mostra erros JavaScript em vermelho
- [ ] Mensagem `[CONFIG] Casas decimais: duas` aparece no console
- [ ] Mensagem `[CONFIG] Método Juízo Crítico: saneamento_desvio_padrao` aparece

### FASE 3: TESTE ETAPA 2 - CONFIGURAÇÕES
- [ ] Aba "2. Metodologias e Padrões" está visível
- [ ] Radio button "Saneamento pelo desvio-padrão" está marcado
- [ ] Radio button "Média (CV ≤ 25%) ou Mediana (CV > 25%)" está marcado
- [ ] Radio button "2 casas decimais" está marcado

#### TESTE 3A: Trocar Método de Saneamento
- [ ] Cliquei em "Saneamento com base em percentual"
- [ ] Console mostra `[AUTO-SAVE] Salvando metodologias...`
- [ ] Console mostra `[AUTO-SAVE] ✓ Metodologias salvas!`
- [ ] NENHUM erro aparece

#### TESTE 3B: Trocar Método de Obtenção
- [ ] Cliquei em "Mediana de todas as amostras"
- [ ] Console mostra `[AUTO-SAVE] ✓ Metodologias salvas!`
- [ ] NENHUM erro aparece

#### TESTE 3C: Trocar Casas Decimais
- [ ] Cliquei em "4 casas decimais"
- [ ] Console mostra `[AUTO-SAVE] ✓ Metodologias salvas!`
- [ ] NENHUM erro aparece

### FASE 4: TESTE MODAL - PRÉ-SELEÇÃO
- [ ] Voltei para Etapa 2
- [ ] Selecionei "Saneamento com base em percentual"
- [ ] Aguardei 2 segundos (auto-save)
- [ ] Fui para aba "3. Itens do Orçamento"
- [ ] Cliquei em botão "Análise" de algum item que tenha amostras

#### Verificar Modal:
- [ ] Modal "Análise Crítica" abriu
- [ ] Console mostra `[ANALISE-CRITICA] Configuração Etapa 2 - Método: saneamento_percentual`
- [ ] Console mostra `[ANALISE-CRITICA] ✓ Método Percentual pré-selecionado (Etapa 2)`
- [ ] Radio "Método Percentual da Mediana" está MARCADO no modal
- [ ] Campos de percentuais (70% e 30%) estão VISÍVEIS

#### TESTE 4B: Pré-seleção Desvio-Padrão
- [ ] Fechei o modal
- [ ] Voltei para Etapa 2
- [ ] Selecionei "Saneamento pelo desvio-padrão"
- [ ] Aguardei 2 segundos
- [ ] Voltei para Itens
- [ ] Abri modal "Análise" novamente
- [ ] Console mostra `[ANALISE-CRITICA] ✓ Método Desvio-Padrão pré-selecionado (Etapa 2)`
- [ ] Radio "Método Desvio-Padrão" está MARCADO

### FASE 5: TESTE APLICAÇÃO DE SANEAMENTO
**Pré-requisito:** Item precisa ter no mínimo 3 amostras de preço

#### TESTE 5A: Saneamento com Desvio-Padrão
- [ ] Etapa 2: Selecionei "Saneamento pelo desvio-padrão"
- [ ] Etapa 2: Selecionei "Média de todas as amostras"
- [ ] Etapa 2: Selecionei "2 casas decimais"
- [ ] Abri modal Análise de um item
- [ ] Radio "Desvio-Padrão" está pré-marcado
- [ ] Cliquei em "Aplicar Saneamento"
- [ ] Aguardei processamento
- [ ] Mensagem de sucesso apareceu
- [ ] Console mostra `calc_metodo: "MEDIA"`
- [ ] Console mostra valores com 2 casas decimais (ex: 10.50, não 10.5000)

#### TESTE 5B: Saneamento com Percentual
- [ ] Etapa 2: Selecionei "Saneamento com base em percentual"
- [ ] Etapa 2: Selecionei "Mediana de todas as amostras"
- [ ] Etapa 2: Selecionei "4 casas decimais"
- [ ] Abri modal Análise de um item
- [ ] Radio "Percentual" está pré-marcado
- [ ] Campos 70% e 30% estão visíveis
- [ ] Cliquei em "Aplicar Saneamento"
- [ ] Mensagem de sucesso apareceu
- [ ] Console mostra `calc_metodo: "MEDIANA"`
- [ ] Console mostra valores com 4 casas decimais (ex: 10.5000)

#### TESTE 5C: Método "Menor Preço"
- [ ] Etapa 2: Selecionei "Menor preço das amostras"
- [ ] Apliquei saneamento em um item
- [ ] Console mostra `calc_metodo: "MENOR"`

### FASE 6: VERIFICAÇÃO DE ERROS
- [ ] Console NÃO mostra erros em vermelho
- [ ] Console NÃO mostra `Uncaught TypeError`
- [ ] Console NÃO mostra `Undefined variable`
- [ ] Console NÃO mostra `500 Internal Server Error`
- [ ] Nenhuma funcionalidade antiga parou de funcionar

---

## 🎯 RESULTADO FINAL

**Total de checks:** _____ / 60

**Status:**
- [ ] ✅ TUDO FUNCIONANDO (58-60 checks)
- [ ] ⚠️ FUNCIONANDO COM PEQUENOS PROBLEMAS (50-57 checks)
- [ ] ❌ NÃO FUNCIONANDO (< 50 checks)

**Observações:**
```
(escrever aqui qualquer problema encontrado)
```

---

## 🆘 SE ALGO DER ERRADO

1. **Tire print do console** (F12 → Console)
2. **Copie a mensagem de erro completa**
3. **Me envie para análise**
4. **NÃO entre em pânico** - temos backups de tudo!

---

**Assinatura:** ___________________
**Data/Hora:** ___________________
