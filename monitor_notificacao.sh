#!/bin/bash

###############################################################################
# 🔔 MONITOR DE NOTIFICAÇÃO - API COMPRAS.GOV
#
# FUNCIONALIDADE:
# - Monitora o processo de monitoramento automático
# - Quando terminar, verifica se foi sucesso ou erro
# - Cria notificação visual em arquivo
# - Registra logs detalhados
# - Envia e-mail (se configurado)
#
# USO:
# ./monitor_notificacao.sh
#
# CRIADO: 29/10/2025
# AUTOR: Claude + Cláudio
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
PID_FILE="/tmp/monitoramento_comprasgov.pid"
LOG_FILE="/tmp/monitoramento_comprasgov.log"
NOTIFICATION_FILE="/tmp/COMPRASGOV_NOTIFICACAO.txt"
CHECK_INTERVAL=60 # Verificar a cada 60 segundos

# Banner
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  🔔 MONITOR DE NOTIFICAÇÃO - API COMPRAS.GOV             ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar se arquivo PID existe
if [ ! -f "$PID_FILE" ]; then
    echo -e "${RED}❌ ERRO: Arquivo PID não encontrado${NC}"
    echo "   Arquivo esperado: $PID_FILE"
    echo ""
    echo "   O monitoramento está rodando?"
    echo "   Execute: ps aux | grep 'comprasgov:monitorar'"
    exit 1
fi

# Ler PID
MONITOR_PID=$(cat "$PID_FILE")
echo -e "${GREEN}✅ PID do monitoramento: $MONITOR_PID${NC}"
echo ""

# Verificar se processo está rodando
if ! ps -p $MONITOR_PID > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Processo já finalizou!${NC}"
    echo ""
    echo "   Verificando resultado agora..."
    echo ""
    # Forçar verificação imediata
    CHECK_INTERVAL=0
fi

echo -e "${BLUE}⚙️  CONFIGURAÇÕES:${NC}"
echo "   • Intervalo de verificação: ${CHECK_INTERVAL}s"
echo "   • Arquivo de notificação: $NOTIFICATION_FILE"
echo "   • Log de monitoramento: $LOG_FILE"
echo ""

echo -e "${GREEN}🔄 Monitorando processo...${NC}"
echo "   (Pressione Ctrl+C para parar)"
echo ""

# Contador de checks
CHECK_COUNT=0

# Loop de monitoramento
while true; do
    CHECK_COUNT=$((CHECK_COUNT + 1))

    # Verificar se processo ainda está rodando
    if ! ps -p $MONITOR_PID > /dev/null 2>&1; then
        echo ""
        echo -e "${YELLOW}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}║  🎉 PROCESSO FINALIZOU!                                   ║${NC}"
        echo -e "${YELLOW}╚════════════════════════════════════════════════════════════╝${NC}"
        echo ""

        # Aguardar 2 segundos para logs finalizarem
        sleep 2

        # Analisar resultado
        echo -e "${BLUE}📊 Analisando resultado...${NC}"
        echo ""

        # Verificar se teve sucesso nos logs
        if grep -q "✅ DOWNLOAD CONCLUÍDO COM SUCESSO" "$LOG_FILE" 2>/dev/null; then
            STATUS="SUCESSO"
            STATUS_EMOJI="✅"
            STATUS_COLOR="${GREEN}"
        elif grep -q "API COMPRAS.GOV VOLTOU ONLINE" "$LOG_FILE" 2>/dev/null; then
            STATUS="API_VOLTOU_SEM_DOWNLOAD"
            STATUS_EMOJI="✅"
            STATUS_COLOR="${GREEN}"
        elif grep -q "LIMITE DE TENTATIVAS ATINGIDO" "$LOG_FILE" 2>/dev/null; then
            STATUS="TIMEOUT"
            STATUS_EMOJI="⏰"
            STATUS_COLOR="${YELLOW}"
        elif grep -q "DOWNLOAD FALHOU" "$LOG_FILE" 2>/dev/null; then
            STATUS="ERRO_DOWNLOAD"
            STATUS_EMOJI="❌"
            STATUS_COLOR="${RED}"
        else
            STATUS="DESCONHECIDO"
            STATUS_EMOJI="❓"
            STATUS_COLOR="${YELLOW}"
        fi

        # Consultar banco de dados
        echo -e "${BLUE}🔍 Consultando banco de dados...${NC}"
        DB_COUNT=$(PGPASSWORD="MinhaDataTech2024SecureDB" psql -h localhost -U minhadattatech_user -d minhadattatech_db -t -c "SELECT COUNT(*) FROM cp_precos_comprasgov;" 2>/dev/null | xargs)

        if [ -z "$DB_COUNT" ]; then
            DB_COUNT="ERRO_CONSULTA"
        fi

        echo -e "   Registros no banco: ${GREEN}${DB_COUNT}${NC}"
        echo ""

        # Data/hora atual
        TIMESTAMP=$(date '+%d/%m/%Y %H:%M:%S')

        # Criar arquivo de notificação
        cat > "$NOTIFICATION_FILE" << EOF
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║  🔔 NOTIFICAÇÃO - MONITORAMENTO COMPRAS.GOV               ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝

📅 DATA/HORA: $TIMESTAMP

${STATUS_EMOJI} STATUS: $STATUS

📊 ESTATÍSTICAS:
   • PID do processo: $MONITOR_PID
   • Registros no banco: $DB_COUNT
   • Verificações realizadas: $CHECK_COUNT

📝 DETALHES:

EOF

        # Adicionar detalhes específicos por status
        case $STATUS in
            "SUCESSO")
                cat >> "$NOTIFICATION_FILE" << EOF
✅ DOWNLOAD CONCLUÍDO COM SUCESSO!

O sistema detectou que a API Compras.gov voltou online e executou
o download dos dados automaticamente.

📊 Dados baixados: $DB_COUNT preços

🎯 PRÓXIMOS PASSOS:
   1. Acesse a Pesquisa Rápida
   2. Busque por um produto (ex: "computador")
   3. Verifique se aparecem resultados do Compras.gov

🌐 URL: https://novaroma.dattapro.online/desktop/price_basket/pesquisa-rapida

EOF
                ;;

            "API_VOLTOU_SEM_DOWNLOAD")
                cat >> "$NOTIFICATION_FILE" << EOF
✅ API COMPRAS.GOV VOLTOU ONLINE!

O sistema detectou que a API voltou online, mas o download
automático não estava habilitado ou falhou.

📊 Dados no banco: $DB_COUNT preços

🎯 PRÓXIMOS PASSOS:
   1. Execute manualmente: php artisan comprasgov:baixar-paralelo
   2. Ou reinicie o monitoramento com --auto-download

EOF
                ;;

            "TIMEOUT")
                cat >> "$NOTIFICATION_FILE" << EOF
⏰ LIMITE DE TENTATIVAS ATINGIDO

O sistema testou a API durante o período configurado, mas ela
permaneceu offline.

📊 Dados no banco: $DB_COUNT preços

🎯 PRÓXIMOS PASSOS:
   1. Reiniciar monitoramento quando desejar
   2. Testar API manualmente: php artisan comprasgov:monitorar --testar-agora
   3. Verificar status oficial em: https://www.gov.br/compras/

EOF
                ;;

            "ERRO_DOWNLOAD")
                cat >> "$NOTIFICATION_FILE" << EOF
❌ ERRO NO DOWNLOAD

O sistema detectou que a API voltou, mas o download falhou.

📊 Dados no banco: $DB_COUNT preços

🎯 PRÓXIMOS PASSOS:
   1. Verificar logs: tail -100 $LOG_FILE
   2. Tentar download manual: php artisan comprasgov:baixar-paralelo
   3. Verificar espaço em disco: df -h

EOF
                ;;

            *)
                cat >> "$NOTIFICATION_FILE" << EOF
❓ STATUS DESCONHECIDO

O processo finalizou, mas não foi possível determinar o motivo.

📊 Dados no banco: $DB_COUNT preços

🎯 PRÓXIMOS PASSOS:
   1. Verificar logs: tail -100 $LOG_FILE
   2. Verificar processo: ps aux | grep comprasgov
   3. Testar API: php artisan comprasgov:monitorar --testar-agora

EOF
                ;;
        esac

        # Adicionar informações de logs
        cat >> "$NOTIFICATION_FILE" << EOF

📄 ÚLTIMAS LINHAS DO LOG:
────────────────────────────────────────────────────────────
$(tail -20 "$LOG_FILE" 2>/dev/null | sed 's/^/   /')
────────────────────────────────────────────────────────────

📁 ARQUIVOS:
   • Log completo: $LOG_FILE
   • PID do processo: $PID_FILE
   • Esta notificação: $NOTIFICATION_FILE

═══════════════════════════════════════════════════════════

🤖 Notificação gerada automaticamente pelo sistema de
   monitoramento automático da API Compras.gov

   Data: $TIMESTAMP

═══════════════════════════════════════════════════════════
EOF

        # Mostrar notificação no terminal
        echo -e "${STATUS_COLOR}"
        cat "$NOTIFICATION_FILE"
        echo -e "${NC}"

        # Adicionar ao log do Laravel também
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] 🔔 NOTIFICAÇÃO: Status=$STATUS, DB_COUNT=$DB_COUNT" >> /home/dattapro/modulos/cestadeprecos/storage/logs/laravel.log

        # Criar arquivo de sinal para fácil detecção
        touch "/tmp/COMPRASGOV_${STATUS}_${TIMESTAMP// /_}.flag"

        echo ""
        echo -e "${GREEN}✅ Notificação criada em: $NOTIFICATION_FILE${NC}"
        echo ""
        echo -e "${BLUE}💡 Para ver a notificação novamente:${NC}"
        echo "   cat $NOTIFICATION_FILE"
        echo ""

        # Enviar e-mail (se configurado)
        # TODO: Implementar envio de e-mail aqui se necessário
        # mail -s "Compras.gov: $STATUS" usuario@email.com < "$NOTIFICATION_FILE"

        exit 0
    fi

    # Mostrar progresso (atualizar na mesma linha)
    echo -ne "\r   ${BLUE}⏰${NC} Check #$CHECK_COUNT | Processo ATIVO (PID: $MONITOR_PID) | Aguardando... "

    # Aguardar intervalo
    sleep $CHECK_INTERVAL
done
