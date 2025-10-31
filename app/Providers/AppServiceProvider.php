<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar CnpjService
        $this->app->singleton(\App\Services\CnpjService::class, function ($app) {
            return new \App\Services\CnpjService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forçar HTTPS em produção (exceto requisições do proxy interno)
        // NÃO forçar HTTPS quando a requisição vem do MinhaDataTech proxy (localhost)
        $isInternalProxy = request()->server('REMOTE_ADDR') === '127.0.0.1'
                        || request()->server('REMOTE_ADDR') === '::1'
                        || request()->ip() === '127.0.0.1';

        if (!$isInternalProxy && (config('app.env') === 'production' || request()->header('X-Forwarded-Proto') === 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Configurar TrustedProxies para proxy reverso (Nginx)
        // Isso permite que o Laravel detecte corretamente o protocolo HTTPS
        request()->setTrustedProxies(
            ['127.0.0.1', '::1'],
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Desabilitar regeneração automática de sessão
        // Isso é crítico para manter CSRF token válido em iframes
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event) {
                // NÃO regenerar sessão no login para manter CSRF token
                // session()->regenerate(); // REMOVIDO
            }
        );
    }
}
