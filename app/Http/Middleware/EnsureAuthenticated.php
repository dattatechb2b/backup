<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * Este middleware substitui o 'auth' padrão do Laravel porque precisamos
     * verificar autenticação DEPOIS que o ProxyAuth executar (stateless).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Neste ponto, o ProxyAuth já executou (está no append do grupo web)
        // Então só precisamos verificar se Auth::check() retorna true

        $isAuthenticated = Auth::check();
        $hasProxyTenant = session('proxy_tenant') !== null;
        $dbPrefix = config('database.connections.pgsql.prefix');

        Log::info('EnsureAuthenticated: Verificação', [
            'uri' => $request->getRequestUri(),
            'is_authenticated' => $isAuthenticated,
            'has_proxy_tenant_in_session' => $hasProxyTenant,
            'db_prefix_config' => $dbPrefix,
            'session_id' => session()->getId()
        ]);

        if (!$isAuthenticated) {
            Log::warning('EnsureAuthenticated: USUÁRIO NÃO AUTENTICADO', [
                'uri' => $request->getRequestUri(),
                'session_id' => session()->getId(),
                'has_proxy_tenant' => $hasProxyTenant,
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson()
            ]);

            // Se for request AJAX ou API, retornar JSON 401
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado.',
                    'redirect' => route('login')
                ], 401);
            }

            // Para requests normais, redirecionar para login
            return redirect()->route('login');
        }

        return $next($request);
    }
}
