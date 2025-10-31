#!/usr/bin/env python3
import re

# Ler arquivo
with open('resources/views/orcamentos/elaborar.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Adicionar variável global no início se não existir
if 'window.ORCAMENTO_ID' not in content:
    content = content.replace(
        '<script>\n    document.addEventListener(\'DOMContentLoaded\', function() {',
        '<script>\n    // Variavel global com ID do orcamento (evita erros de sintaxe com Blade tags)\n    window.ORCAMENTO_ID = {{ $orcamento->id }};\n\n    document.addEventListener(\'DOMContentLoaded\', function() {'
    )

# Substituir template literals com aspas simples por concatenação
# Padrão: '/orcamentos/{{ $orcamento->id }}
content = re.sub(
    r"'/orcamentos/\{\{ \$orcamento->id \}\}(/[^']*)'",
    r"'/orcamentos/' + window.ORCAMENTO_ID + '\1'",
    content
)

# Substituir template literals com crases
# Padrão: `/orcamentos/{{ $orcamento->id }}
content = re.sub(
    r"`/orcamentos/\{\{ \$orcamento->id \}\}([^`]*)`",
    r"`/orcamentos/${window.ORCAMENTO_ID}\1`",
    content
)

# Substituir sessionStorage
content = re.sub(
    r"'modalSucessoMostrado_\{\{ \$orcamento->id \}\}'",
    r"'modalSucessoMostrado_' + window.ORCAMENTO_ID",
    content
)

# Substituir window.open
content = re.sub(
    r"'orcamentos/\{\{ \$orcamento->id \}\}(/[^']*)'",
    r"'orcamentos/' + window.ORCAMENTO_ID + '\1'",
    content
)

# Substituir console.log específico
content = re.sub(
    r"console\.log\('\[.*?\] Pgina carregada - Orcamento ID: \{\{ \$orcamento->id \}\}'\);",
    r"console.log('[LOG] [ELABORAR.BLADE.PHP] Pgina carregada - Orcamento ID:', window.ORCAMENTO_ID);",
    content
)

# Salvar
with open('resources/views/orcamentos/elaborar.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Substituições concluídas!")
