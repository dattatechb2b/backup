# Guia - Monitoramento Automático API Compras.gov

## Comando Criado

### Testar Manualmente
```bash
php artisan comprasgov:monitorar-api
```

### Ver Ajuda
```bash
php artisan comprasgov:monitorar-api --help
```

## Saída Esperada

### Quando API está OFFLINE (atual)
```
❌ ERRO AO CONECTAR NA API
🌐 URL: https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial
⏱️  Timeout após: 10079ms
⚠️  Erro: cURL error 28: Resolving timed out...
```

### Quando API estiver ONLINE (desejado)
```
✅ API COMPRAS.GOV ESTÁ ONLINE!
⏱️  Tempo de resposta: 245ms
📊 Resultados teste (CATMAT 243756): 872
🟢 Status HTTP: 200

💡 Próximo passo:
   1. Execute: php artisan comprasgov:baixar-paralelo
   2. Aguarde ~30-60 minutos
   3. Verifique: SELECT COUNT(*) FROM cp_precos_comprasgov;
```

## Configurar Monitoramento Automático via Cron

### Opção 1: Cron (Recomendado)

**Testar a cada 2 horas:**
```bash
# Abrir crontab
crontab -e

# Adicionar linha:
0 */2 * * * cd /home/dattapro/modulos/cestadeprecos && php artisan comprasgov:monitorar-api >> storage/logs/monitor_api.log 2>&1

# Salvar e fechar (Ctrl+X, Y, Enter no nano)
```

**Testar a cada 4 horas:**
```bash
0 */4 * * * cd /home/dattapro/modulos/cestadeprecos && php artisan comprasgov:monitorar-api >> storage/logs/monitor_api.log 2>&1
```

**Testar a cada 6 horas (8h, 14h, 20h, 2h):**
```bash
0 8,14,20,2 * * * cd /home/dattapro/modulos/cestadeprecos && php artisan comprasgov:monitorar-api >> storage/logs/monitor_api.log 2>&1
```

**Verificar se cron foi adicionado:**
```bash
crontab -l | grep monitor-api
```

**Ver log de monitoramento:**
```bash
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/monitor_api.log
```

### Opção 2: Laravel Schedule (Alternativa)

**Editar:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Monitorar API Compras.gov a cada 2 horas
    $schedule->command('comprasgov:monitorar-api')
             ->everyTwoHours()
             ->appendOutputTo(storage_path('logs/monitor_api.log'));
}
```

**Ativar scheduler no cron:**
```bash
crontab -e

# Adicionar:
* * * * * cd /home/dattapro/modulos/cestadeprecos && php artisan schedule:run >> /dev/null 2>&1
```

## Ver Logs de Monitoramento

### Log Específico (se usar cron direto)
```bash
# Ver últimas 50 linhas
tail -n 50 storage/logs/monitor_api.log

# Acompanhar em tempo real
tail -f storage/logs/monitor_api.log

# Ver apenas sucessos
grep "API ONLINE" storage/logs/monitor_api.log

# Ver apenas erros
grep "ERRO" storage/logs/monitor_api.log
```

### Log Laravel (todas as verificações)
```bash
# Ver últimas verificações
tail -n 100 storage/logs/laravel.log | grep "Compras.gov"

# Ver apenas quando API ficou online
grep "API Compras.gov ONLINE" storage/logs/laravel.log
```

## Quando API Voltar Online

### 1. Você Verá no Log
```
✅ API COMPRAS.GOV ESTÁ ONLINE!
📊 Resultados teste (CATMAT 243756): 872
```

### 2. Executar Download Imediatamente
```bash
# Entrar no diretório
cd /home/dattapro/modulos/cestadeprecos

# Executar download paralelo em background
nohup php artisan comprasgov:baixar-paralelo --workers=10 --codigos=10000 --limite-gb=3 > storage/logs/download_comprasgov.log 2>&1 &

# Monitorar progresso
tail -f storage/logs/download_comprasgov.log
```

### 3. Verificar Sucesso
```bash
# Verificar quantidade de registros
PGPASSWORD="MinhaDataTech2024SecureDB" psql -h localhost -U minhadattatech_user -d minhadattatech_db -c "
SELECT
    COUNT(*) as total_precos,
    pg_size_pretty(pg_total_relation_size('cp_precos_comprasgov')) as tamanho,
    COUNT(DISTINCT catmat_codigo) as codigos_unicos
FROM cp_precos_comprasgov;
"

# Esperado:
# total_precos | tamanho | codigos_unicos
# -------------+---------+----------------
#      ~30.000 |  ~15 MB |         ~10.000
```

### 4. Testar na Interface
```
1. Acessar Pesquisa Rápida
2. Buscar "computador"
3. Deve aparecer resultados de PNCP + COMPRAS.GOV
```

## Parar Monitoramento

### Remover do Cron
```bash
crontab -e

# Comentar ou deletar a linha do monitor-api
# Salvar e fechar
```

### Desativar Schedule
```bash
# Editar app/Console/Kernel.php
# Comentar ou remover a linha do schedule
```

## Comandos Úteis

### Testar API Agora
```bash
php artisan comprasgov:monitorar-api
```

### Testar com Notificação (exibe alerta se online)
```bash
php artisan comprasgov:monitorar-api --notificar
```

### Ver Status Atual da Tabela
```bash
PGPASSWORD="MinhaDataTech2024SecureDB" psql -h localhost -U minhadattatech_user -d minhadattatech_db -c "SELECT COUNT(*) FROM cp_precos_comprasgov;"
```

### Ver Processos em Background
```bash
# Ver se há download rodando
ps aux | grep comprasgov

# Ver todos comandos artisan rodando
ps aux | grep artisan
```

## Troubleshooting

### "Command not found"
```bash
# Verificar se comando existe
php artisan list | grep comprasgov

# Deve aparecer:
# comprasgov:monitorar-api
```

### Cron não está executando
```bash
# Ver logs do cron
grep CRON /var/log/syslog | tail -n 20

# Verificar se cron está rodando
systemctl status cron

# Verificar permissões
ls -la /home/dattapro/modulos/cestadeprecos/storage/logs/
```

### Log não está sendo criado
```bash
# Criar diretório se não existir
mkdir -p /home/dattapro/modulos/cestadeprecos/storage/logs/

# Dar permissões
chmod -R 775 /home/dattapro/modulos/cestadeprecos/storage/logs/
```

## Resumo Rápido

```bash
# 1. Adicionar ao cron (testar a cada 2 horas)
crontab -e
0 */2 * * * cd /home/dattapro/modulos/cestadeprecos && php artisan comprasgov:monitorar-api >> storage/logs/monitor_api.log 2>&1

# 2. Ver log
tail -f /home/dattapro/modulos/cestadeprecos/storage/logs/monitor_api.log

# 3. Quando API voltar: executar download
php artisan comprasgov:baixar-paralelo --workers=10 --codigos=10000
```

---

**Última atualização:** 29/10/2025 17:20h
