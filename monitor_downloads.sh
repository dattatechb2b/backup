#!/bin/bash

# Script para monitorar progresso dos downloads PNCP e Compras.gov

PNCP_LOG="storage/logs/download_pncp.log"
COMPRAS_LOG="storage/logs/download_comprasgov.log"

# Função para criar barra de progresso
barra_progresso() {
    local porcentagem=$1
    local largura=50
    local completo=$((porcentagem * largura / 100))
    local vazio=$((largura - completo))

    printf "["
    printf "%${completo}s" | tr ' ' '█'
    printf "%${vazio}s" | tr ' ' '░'
    printf "] %3d%%\n" "$porcentagem"
}

# Função para extrair número de contratos/preços dos logs
extrair_progresso() {
    local log=$1
    local padrao=$2

    if [ -f "$log" ]; then
        tail -5 "$log" | grep -oP "$padrao" | tail -1
    else
        echo "0"
    fi
}

echo "════════════════════════════════════════════════════════════"
echo "  📊 MONITOR DE DOWNLOADS - CESTA DE PREÇOS"
echo "════════════════════════════════════════════════════════════"
echo ""

while true; do
    clear
    echo "════════════════════════════════════════════════════════════"
    echo "  📊 MONITOR DE DOWNLOADS - CESTA DE PREÇOS"
    echo "════════════════════════════════════════════════════════════"
    echo ""

    # Verificar se processos estão rodando
    PNCP_PID=$(ps aux | grep "pncp:baixar-contratos" | grep -v grep | awk '{print $2}')
    COMPRAS_PID=$(ps aux | grep "comprasgov:baixar-precos" | grep -v grep | awk '{print $2}')

    # ===== PNCP =====
    echo "🏛️  PNCP - CONTRATOS PÚBLICOS"
    echo "────────────────────────────────────────────────────────────"

    if [ -n "$PNCP_PID" ]; then
        # Contar contratos baixados
        CONTRATOS=$(extrair_progresso "$PNCP_LOG" "(?<=baixados... ).*(?=✅)" | tail -1)
        if [ -z "$CONTRATOS" ]; then
            CONTRATOS=$(grep -oP '\d+(?= contratos baixados)' "$PNCP_LOG" | tail -1)
        fi
        [ -z "$CONTRATOS" ] && CONTRATOS=0

        # Detectar qual mês está processando
        MES_ATUAL=$(tail -20 "$PNCP_LOG" | grep -oP "Processando: \K\d+/\d+" | tail -1)
        [ -z "$MES_ATUAL" ] && MES_ATUAL="Iniciando..."

        # Calcular progresso (12 meses = 100%)
        # Estimar baseado no mês atual
        if [[ "$MES_ATUAL" =~ ^[0-9]{2}/[0-9]{4}$ ]]; then
            MES_NUM=$(echo "$MES_ATUAL" | cut -d'/' -f1)
            ANO=$(echo "$MES_ATUAL" | cut -d'/' -f2)

            # Calcular meses desde outubro/2024
            if [[ "$ANO" == "2024" ]]; then
                MESES_PROG=$((MES_NUM - 9))  # Outubro = mês 10
            else
                MESES_PROG=$((MES_NUM + 3))  # Janeiro 2025 = 4° mês
            fi

            [ "$MESES_PROG" -lt 1 ] && MESES_PROG=1
            [ "$MESES_PROG" -gt 12 ] && MESES_PROG=12

            PNCP_PERCENT=$((MESES_PROG * 100 / 12))
        else
            MESES_PROG=1
            PNCP_PERCENT=5
        fi

        echo "Status: ✅ ATIVO (PID: $PNCP_PID)"
        echo "Mês atual: $MES_ATUAL"
        echo "Contratos baixados: $CONTRATOS"
        echo "Progresso: $MESES_PROG de 12 meses"
        echo ""
        barra_progresso $PNCP_PERCENT

        # Tamanho no banco
        PNCP_COUNT=$(PGPASSWORD="MinhaDataTech2024SecureDB" psql -h 127.0.0.1 -U minhadattatech_user -d minhadattatech_db -t -c "SELECT COUNT(*) FROM cp_contratos_pncp;" 2>/dev/null | xargs)
        PNCP_SIZE=$(PGPASSWORD="MinhaDataTech2024SecureDB" psql -h 127.0.0.1 -U minhadattatech_user -d minhadattatech_db -t -c "SELECT pg_size_pretty(pg_total_relation_size('cp_contratos_pncp'));" 2>/dev/null | xargs)
        echo "📦 No banco: $PNCP_COUNT contratos | Tamanho: $PNCP_SIZE"
    else
        echo "Status: ⏸️  PARADO ou CONCLUÍDO"

        # Verificar se terminou no log
        if grep -q "DOWNLOAD PNCP CONCLUÍDO" "$PNCP_LOG" 2>/dev/null; then
            echo "✅ Download finalizado!"
            TOTAL=$(grep -oP "Total baixados: \K\d+" "$PNCP_LOG" | tail -1)
            echo "Total: $TOTAL contratos"
            barra_progresso 100
        else
            echo "❌ Processo não está rodando"
        fi
    fi

    echo ""
    echo ""

    # ===== COMPRAS.GOV =====
    echo "🛒 COMPRAS.GOV - PREÇOS PRATICADOS"
    echo "────────────────────────────────────────────────────────────"

    if [ -n "$COMPRAS_PID" ]; then
        # Extrair códigos processados
        CODIGOS=$(grep -oP "Processados: \K\d+" "$COMPRAS_LOG" | tail -1)
        [ -z "$CODIGOS" ] && CODIGOS=0

        # Extrair preços coletados
        PRECOS=$(grep -oP "Preços: \K\d+" "$COMPRAS_LOG" | tail -1)
        [ -z "$PRECOS" ] && PRECOS=0

        # Extrair tamanho atual
        TAMANHO=$(grep -oP "Tamanho: \K[\d.]+ MB" "$COMPRAS_LOG" | tail -1)
        [ -z "$TAMANHO" ] && TAMANHO="0 MB"

        # Calcular porcentagem (10.000 códigos = 100%)
        COMPRAS_PERCENT=$((CODIGOS * 100 / 10000))

        echo "Status: ✅ ATIVO (PID: $COMPRAS_PID)"
        echo "Códigos processados: $CODIGOS de 10.000"
        echo "Preços coletados: $PRECOS"
        echo "Tamanho: $TAMANHO de 3 GB (limite)"
        echo ""
        barra_progresso $COMPRAS_PERCENT

        # Tamanho no banco
        COMPRAS_COUNT=$(PGPASSWORD="MinhaDataTech2024SecureDB" psql -h 127.0.0.1 -U minhadattatech_user -d minhadattatech_db -t -c "SELECT COUNT(*) FROM cp_precos_comprasgov;" 2>/dev/null | xargs)
        COMPRAS_SIZE=$(PGPASSWORD="MinhaDataTech2024SecureDB" psql -h 127.0.0.1 -U minhadattatech_user -d minhadattatech_db -t -c "SELECT pg_size_pretty(pg_total_relation_size('cp_precos_comprasgov'));" 2>/dev/null | xargs)
        echo "📦 No banco: $COMPRAS_COUNT preços | Tamanho: $COMPRAS_SIZE"
    else
        echo "Status: ⏸️  PARADO ou CONCLUÍDO"

        # Verificar se terminou no log
        if grep -q "DOWNLOAD COMPRAS.GOV CONCLUÍDO" "$COMPRAS_LOG" 2>/dev/null; then
            echo "✅ Download finalizado!"
            TOTAL=$(grep -oP "Total preços baixados: \K\d+" "$COMPRAS_LOG" | tail -1)
            echo "Total: $TOTAL preços"
            barra_progresso 100
        else
            echo "❌ Processo não está rodando"
        fi
    fi

    echo ""
    echo "════════════════════════════════════════════════════════════"
    echo "Última atualização: $(date '+%d/%m/%Y %H:%M:%S')"
    echo "Pressione Ctrl+C para sair"
    echo "════════════════════════════════════════════════════════════"

    # Verificar se ambos terminaram
    if [ -z "$PNCP_PID" ] && [ -z "$COMPRAS_PID" ]; then
        echo ""
        echo "✅ Ambos os downloads foram concluídos!"
        echo ""
        break
    fi

    sleep 10
done
