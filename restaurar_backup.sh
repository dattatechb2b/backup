#!/bin/bash

# ========================================
# SCRIPT DE RESTAURAÇÃO - CESTA DE PREÇOS
# ========================================
# Uso: ./restaurar_backup.sh [nome_do_backup]
# ========================================

if [ -z "$1" ]; then
  echo "╔════════════════════════════════════════════════════════════╗"
  echo "║         RESTAURAR BACKUP - CESTA DE PREÇOS                 ║"
  echo "╚════════════════════════════════════════════════════════════╝"
  echo ""
  echo "❌ Erro: Nome do backup não especificado"
  echo ""
  echo "Uso: ./restaurar_backup.sh [nome_do_backup]"
  echo ""
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "📁 Backups disponíveis:"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  ls -1 /home/dattapro/modulos/cestadeprecos/backups/ 2>/dev/null || echo "   (nenhum backup encontrado)"
  echo ""
  exit 1
fi

BACKUP_DIR="/home/dattapro/modulos/cestadeprecos/backups"
BACKUP_NAME="$1"
BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"

# Verificar se backup existe
if [ ! -d "$BACKUP_PATH" ]; then
  echo "❌ Erro: Backup não encontrado: $BACKUP_PATH"
  echo ""
  echo "Backups disponíveis:"
  ls -1 "$BACKUP_DIR/" 2>/dev/null
  exit 1
fi

# ========================================
# EXIBIR INFORMAÇÕES DO BACKUP
# ========================================
echo "╔════════════════════════════════════════════════════════════╗"
echo "║         RESTAURAR BACKUP - CESTA DE PREÇOS                 ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📦 Backup: $BACKUP_NAME"
echo "📁 Localização: $BACKUP_PATH"
echo ""

if [ -f "$BACKUP_PATH/LEIA-ME.txt" ]; then
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "ℹ️  Informações do Backup:"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  head -n 20 "$BACKUP_PATH/LEIA-ME.txt"
  echo ""
fi

# ========================================
# CONFIRMAÇÃO
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⚠️  ATENÇÃO: Esta operação irá SOBRESCREVER os arquivos atuais!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
read -p "Deseja continuar? (digite SIM para confirmar): " CONFIRMA

if [ "$CONFIRMA" != "SIM" ]; then
  echo ""
  echo "❌ Operação cancelada pelo usuário."
  echo ""
  exit 0
fi

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              INICIANDO RESTAURAÇÃO...                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# ========================================
# 0. CRIAR BACKUP DE SEGURANÇA
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔒 [0/4] Criando backup de segurança do estado atual..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./fazer_backup.sh "antes_de_restaurar_$BACKUP_NAME" > /dev/null 2>&1

if [ $? -eq 0 ]; then
  echo "✅ Backup de segurança criado!"
  echo "   (Caso algo dê errado, você pode restaurar este backup)"
else
  echo "⚠️  Não foi possível criar backup de segurança"
  echo ""
  read -p "Deseja continuar mesmo assim? (digite SIM): " CONFIRMA2
  if [ "$CONFIRMA2" != "SIM" ]; then
    echo "Operação cancelada."
    exit 0
  fi
fi
echo ""

# ========================================
# 1. RESTAURAR ARQUIVOS
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 [1/4] Restaurando arquivos do código..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cd /home/dattapro/modulos/cestadeprecos

# Restaurar pastas
cp -r "$BACKUP_PATH/app" . && echo "   ✓ app/"
cp -r "$BACKUP_PATH/resources" . && echo "   ✓ resources/"
cp -r "$BACKUP_PATH/routes" . && echo "   ✓ routes/"
cp -r "$BACKUP_PATH/config" . && echo "   ✓ config/"
cp -r "$BACKUP_PATH/database" . && echo "   ✓ database/"

# Restaurar arquivos
cp "$BACKUP_PATH/.env" . 2>/dev/null && echo "   ✓ .env" || echo "   ⚠️  .env não encontrado no backup"
cp "$BACKUP_PATH/composer.json" . 2>/dev/null && echo "   ✓ composer.json"
cp "$BACKUP_PATH/composer.lock" . 2>/dev/null && echo "   ✓ composer.lock"

echo ""
echo "✅ Arquivos restaurados!"
echo ""

# ========================================
# 2. RESTAURAR BANCO DE DADOS
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗄️  [2/4] Deseja restaurar o banco de dados?"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -f "$BACKUP_PATH/banco_de_dados.sql" ]; then
  echo "⚠️  ATENÇÃO: Isso irá SOBRESCREVER todas as tabelas cp_*"
  echo ""
  read -p "Confirma restauração do banco? (digite SIM): " CONFIRMA_DB

  if [ "$CONFIRMA_DB" = "SIM" ]; then
    echo ""
    echo "Restaurando banco de dados..."

    PGPASSWORD='MinhaDataTech2024SecureDB' psql \
      -h 127.0.0.1 \
      -U minhadattatech_user \
      -d minhadattatech_db \
      -f "$BACKUP_PATH/banco_de_dados.sql" 2>&1 | grep -v "^SET$" | grep -v "^--"

    if [ $? -eq 0 ]; then
      echo ""
      echo "✅ Banco de dados restaurado!"
    else
      echo ""
      echo "⚠️  Erro ao restaurar banco de dados"
    fi
  else
    echo ""
    echo "⏭️  Banco de dados NÃO foi restaurado (pulado)"
  fi
else
  echo "⚠️  Arquivo de banco não encontrado no backup"
fi
echo ""

# ========================================
# 3. LIMPAR CACHES
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🧹 [3/4] Limpando caches..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php artisan cache:clear 2>/dev/null && echo "   ✓ Cache limpo"
php artisan config:clear 2>/dev/null && echo "   ✓ Config limpa"
php artisan route:clear 2>/dev/null && echo "   ✓ Rotas limpas"
php artisan view:clear 2>/dev/null && echo "   ✓ Views limpas"

echo ""
echo "✅ Caches limpos!"
echo ""

# ========================================
# 4. VERIFICAR INTEGRIDADE
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 [4/4] Verificando integridade..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Verificar arquivos críticos
CRITICOS=("app/Http/Controllers/AuthController.php" "app/Models/User.php" "routes/web.php" ".env")
TUDO_OK=true

for arquivo in "${CRITICOS[@]}"; do
  if [ -f "$arquivo" ]; then
    echo "   ✓ $arquivo"
  else
    echo "   ❌ $arquivo (FALTANDO!)"
    TUDO_OK=false
  fi
done

echo ""

# ========================================
# RESUMO FINAL
# ========================================
if [ "$TUDO_OK" = true ]; then
  echo "╔════════════════════════════════════════════════════════════╗"
  echo "║              ✅ RESTAURAÇÃO CONCLUÍDA!                     ║"
  echo "╚════════════════════════════════════════════════════════════╝"
  echo ""
  echo "📋 Backup restaurado: $BACKUP_NAME"
  echo ""
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "🚀 Próximos passos:"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo ""
  echo "1. Testar a aplicação:"
  echo "   php artisan serve --host=0.0.0.0 --port=8001"
  echo ""
  echo "2. Acessar no navegador:"
  echo "   http://localhost:8001"
  echo ""
  echo "3. Fazer login:"
  echo "   Usuário: vinicius@catasaltas.dattapro.online"
  echo "   Senha: 10037175"
  echo ""
  echo "4. Se tudo estiver OK, commit no Git:"
  echo "   git add ."
  echo "   git commit -m \"[Restore] Restaurado backup $BACKUP_NAME\""
  echo ""
else
  echo "╔════════════════════════════════════════════════════════════╗"
  echo "║            ⚠️  RESTAURAÇÃO COM PROBLEMAS                   ║"
  echo "╚════════════════════════════════════════════════════════════╝"
  echo ""
  echo "Alguns arquivos críticos estão faltando."
  echo "Verifique os erros acima."
  echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
