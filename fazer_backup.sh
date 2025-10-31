#!/bin/bash

# ========================================
# SCRIPT DE BACKUP - CESTA DE PREÇOS
# ========================================
# Uso: ./fazer_backup.sh "descrição opcional"
# ========================================

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DESCRICAO="${1:-backup_automatico}"
BACKUP_DIR="/home/dattapro/modulos/cestadeprecos/backups"

echo "╔════════════════════════════════════════════════════════════╗"
echo "║           BACKUP - MÓDULO CESTA DE PREÇOS                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "⏰ Data/Hora: $(date '+%Y-%m-%d %H:%M:%S')"
echo "📝 Descrição: $DESCRICAO"
echo "📁 Destino: $BACKUP_DIR/backup_$TIMESTAMP/"
echo ""

# Criar diretório de backups
mkdir -p "$BACKUP_DIR/backup_$TIMESTAMP"

# ========================================
# 1. BACKUP DO CÓDIGO
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 [1/4] Fazendo backup do código..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Copiar pastas importantes
cp -r app "$BACKUP_DIR/backup_$TIMESTAMP/"
cp -r resources "$BACKUP_DIR/backup_$TIMESTAMP/"
cp -r routes "$BACKUP_DIR/backup_$TIMESTAMP/"
cp -r config "$BACKUP_DIR/backup_$TIMESTAMP/"
cp -r database "$BACKUP_DIR/backup_$TIMESTAMP/"

# Copiar arquivos importantes
cp .env "$BACKUP_DIR/backup_$TIMESTAMP/" 2>/dev/null || echo "⚠️  .env não encontrado"
cp composer.json "$BACKUP_DIR/backup_$TIMESTAMP/"
cp composer.lock "$BACKUP_DIR/backup_$TIMESTAMP/"

echo "✅ Código copiado com sucesso!"
echo ""

# ========================================
# 2. BACKUP DO BANCO DE DADOS
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗄️  [2/4] Fazendo backup do banco de dados..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

PGPASSWORD='MinhaDataTech2024SecureDB' pg_dump \
  -h 127.0.0.1 \
  -U minhadattatech_user \
  -d minhadattatech_db \
  --table='cp_*' \
  --no-owner \
  --no-acl \
  -f "$BACKUP_DIR/backup_$TIMESTAMP/banco_de_dados.sql" 2>&1

if [ $? -eq 0 ]; then
  echo "✅ Banco de dados exportado com sucesso!"
  echo "   Tabelas com prefixo 'cp_' salvas"
else
  echo "⚠️  Erro ao exportar banco de dados"
fi
echo ""

# ========================================
# 3. INFORMAÇÕES DO GIT
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 [3/4] Salvando informações do Git..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cat > "$BACKUP_DIR/backup_$TIMESTAMP/git_info.txt" <<EOF
Commit Atual: $(git rev-parse HEAD)
Branch: $(git branch --show-current)
Data do Commit: $(git log -1 --format=%cd)
Mensagem: $(git log -1 --format=%s)

Status do Git:
$(git status --short)
EOF

echo "✅ Informações do Git salvas!"
echo ""

# ========================================
# 4. CRIAR ARQUIVO DE METADADOS
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📄 [4/4] Criando arquivo de metadados..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cat > "$BACKUP_DIR/backup_$TIMESTAMP/LEIA-ME.txt" <<EOF
╔════════════════════════════════════════════════════════════╗
║              BACKUP - CESTA DE PREÇOS                      ║
╚════════════════════════════════════════════════════════════╝

📅 Data/Hora: $(date '+%Y-%m-%d %H:%M:%S')
📝 Descrição: $DESCRICAO
📁 Backup ID: backup_$TIMESTAMP

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📦 CONTEÚDO DESTE BACKUP:

  ✓ app/          - Controllers, Models, Middlewares
  ✓ resources/    - Views, CSS, JS
  ✓ routes/       - Rotas web e API
  ✓ config/       - Configurações
  ✓ database/     - Migrations, Seeders
  ✓ .env          - Variáveis de ambiente
  ✓ composer.*    - Dependências PHP
  ✓ banco_de_dados.sql - Dump das tabelas cp_*
  ✓ git_info.txt  - Informações do commit

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔄 COMO RESTAURAR ESTE BACKUP:

  cd /home/dattapro/modulos/cestadeprecos
  ./restaurar_backup.sh backup_$TIMESTAMP

  OU manualmente:

  1. Copiar arquivos:
     cp -r backups/backup_$TIMESTAMP/app .
     cp -r backups/backup_$TIMESTAMP/resources .
     cp -r backups/backup_$TIMESTAMP/routes .
     cp -r backups/backup_$TIMESTAMP/config .
     cp -r backups/backup_$TIMESTAMP/database .
     cp backups/backup_$TIMESTAMP/.env .

  2. Restaurar banco:
     PGPASSWORD='MinhaDataTech2024SecureDB' psql \\
       -h 127.0.0.1 \\
       -U minhadattatech_user \\
       -d minhadattatech_db \\
       -f backups/backup_$TIMESTAMP/banco_de_dados.sql

  3. Limpar caches:
     php artisan cache:clear
     php artisan config:clear
     php artisan route:clear
     php artisan view:clear

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ℹ️  INFORMAÇÕES DO SISTEMA:

  Git Commit: $(git rev-parse HEAD)
  Git Branch: $(git branch --show-current)
  Laravel: $(php artisan --version | head -n 1)
  PHP: $(php -v | head -n 1)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EOF

echo "✅ Metadados criados!"
echo ""

# ========================================
# RESUMO FINAL
# ========================================
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                   ✅ BACKUP CONCLUÍDO!                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📁 Localização:"
echo "   $BACKUP_DIR/backup_$TIMESTAMP/"
echo ""
echo "📊 Tamanho:"
TAMANHO=$(du -sh "$BACKUP_DIR/backup_$TIMESTAMP" | cut -f1)
echo "   $TAMANHO"
echo ""
echo "📝 Arquivos salvos:"
echo "   $(find "$BACKUP_DIR/backup_$TIMESTAMP" -type f | wc -l) arquivos"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 Para restaurar este backup:"
echo "   ./restaurar_backup.sh backup_$TIMESTAMP"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
