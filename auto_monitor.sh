#!/bin/bash

# Script para monitorar Compras.gov e cancelar PNCP quando terminar

COMPRAS_LOG="storage/logs/download_comprasgov.log"
PNCP_PID=$(ps aux | grep "pncp:baixar-contratos" | grep -v grep | awk '{print $2}')

echo "════════════════════════════════════════════════════════════"
echo "  🔍 MONITORANDO CONCLUSÃO DO COMPRAS.GOV"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "PID do PNCP para cancelar: $PNCP_PID"
echo ""

while true; do
    # Verificar se Compras.gov terminou
    COMPRAS_PID=$(ps aux | grep "comprasgov:baixar-precos" | grep -v grep | awk '{print $2}')

    if [ -z "$COMPRAS_PID" ]; then
        echo "✅ Compras.gov CONCLUÍDO!"
        echo ""

        # Verificar se foi concluído com sucesso
        if grep -q "DOWNLOAD COMPRAS.GOV CONCLUÍDO" "$COMPRAS_LOG" 2>/dev/null; then
            echo "📊 Estatísticas do Compras.gov:"
            grep "Total preços baixados:" "$COMPRAS_LOG" | tail -1
            grep "Total no banco:" "$COMPRAS_LOG" | tail -1
            grep "Tamanho:" "$COMPRAS_LOG" | tail -1
            echo ""
        fi

        # Cancelar PNCP se ainda estiver rodando
        PNCP_PID=$(ps aux | grep "pncp:baixar-contratos" | grep -v grep | awk '{print $2}')
        if [ -n "$PNCP_PID" ]; then
            echo "⏸️  Cancelando download do PNCP (PID: $PNCP_PID)..."
            kill $PNCP_PID
            sleep 2
            echo "✅ Download do PNCP cancelado!"
            echo "📡 PNCP continuará funcionando via API"
        fi

        echo ""
        echo "════════════════════════════════════════════════════════════"
        echo "  ✅ PROCESSO CONCLUÍDO"
        echo "════════════════════════════════════════════════════════════"
        echo ""
        echo "Resumo:"
        echo "- ✅ Compras.gov: Download local concluído"
        echo "- ⏸️  PNCP: Cancelado (continua via API)"
        echo ""

        break
    fi

    # Mostrar progresso
    CODIGOS=$(grep -oP "Processados: \K\d+" "$COMPRAS_LOG" | tail -1)
    PRECOS=$(grep -oP "Preços: \K\d+" "$COMPRAS_LOG" | tail -1)
    PERCENT=$((CODIGOS * 100 / 10000))

    echo -ne "\r🛒 Compras.gov: $CODIGOS/10.000 códigos ($PERCENT%) | Preços: $PRECOS   "

    sleep 10
done
