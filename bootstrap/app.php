<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registrar middleware como alias
        $middleware->alias([
            'internal' => \App\Http\Middleware\InternalOnly::class,
            'proxy.auth' => \App\Http\Middleware\ProxyAuth::class,
            'ensure.authenticated' => \App\Http\Middleware\EnsureAuthenticated::class,
        ]);

        // CRÍTICO: Limpar cookies duplicados ANTES da sessão iniciar
        // Usa prepend para executar ANTES do StartSession
        $middleware->web(prepend: [
            \App\Http\Middleware\CleanDuplicateCookies::class,
        ]);

        // Adicionar ProxyAuth ao grupo web APÓS a sessão ser iniciada
        // Usa append para executar depois do StartSession
        $middleware->web(append: [
            \App\Http\Middleware\ProxyAuth::class,
            \App\Http\Middleware\ForceSaveSession::class,
        ]);

        // CSRF desabilitado para rotas de orçamento e fornecedores
        // Sistema usa ProxyAuth como autenticação principal
        $middleware->validateCsrfTokens(except: [
            'orcamentos/novo',
            '/orcamentos/novo',
            'orcamentos/*',
            '/orcamentos/*',
            'orcamentos/processar-documento',
            '/orcamentos/processar-documento',
            'fornecedores',
            '/fornecedores',
            'fornecedores/*',
            '/fornecedores/*',
            'cotacao-externa',
            '/cotacao-externa',
            'cotacao-externa/*',
            '/cotacao-externa/*',
            'api/cnpj/consultar',
            '/api/cnpj/consultar',
            'responder-cdf/*',
            '/responder-cdf/*',
            'api/cdf/*',
            '/api/cdf/*',
            'api/notificacoes/*',
            '/api/notificacoes/*',
            'baixar-espelho-cnpj',
            '/baixar-espelho-cnpj',
            // Sistema de Logs - permitir sem CSRF para garantir funcionamento
            'api/logs/browser',
            '/api/logs/browser',
            // Configurações do Órgão
            'configuracoes',
            '/configuracoes',
            'configuracoes/*',
            '/configuracoes/*',
            '/configuracoes/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
