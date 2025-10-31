<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class InternalOnly
{
    /**
     * Handle an incoming request.
     *
     * Este middleware garante que o módulo só pode ser acessado
     * internamente via proxy do MinhaDataTech
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se é acesso local
        $allowedIPs = ['127.0.0.1', '::1', 'localhost'];
        $clientIP = $request->ip();

        if (!in_array($clientIP, $allowedIPs)) {
            Log::warning('Tentativa de acesso externo bloqueada', [
                'ip' => $clientIP,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);

            abort(403, 'Acesso externo não permitido. Este módulo só pode ser acessado via sistema principal.');
        }

        // Verificar token do módulo
        $token = $request->header('X-Module-Token');

        if (!$token) {
            Log::warning('Requisição sem token de módulo', [
                'ip' => $clientIP,
                'url' => $request->fullUrl()
            ]);

            // Em desenvolvimento, permitir acesso sem token para testes
            if (config('app.env') === 'local') {
                Log::info('Acesso local permitido sem token para desenvolvimento');
            } else {
                abort(401, 'Token de autenticação do módulo não fornecido');
            }
        }

        // Validar e decodificar token
        if ($token) {
            try {
                $payload = decrypt($token);

                // Verificar expiração
                if (isset($payload['expires_at']) && $payload['expires_at'] < time()) {
                    Log::warning('Token de módulo expirado', [
                        'expired_at' => $payload['expires_at'],
                        'current_time' => time()
                    ]);

                    abort(401, 'Token de autenticação expirado');
                }

                // Adicionar dados do contexto ao request
                $request->attributes->set('tenant', [
                    'id' => $request->header('X-Tenant-Id'),
                    'subdomain' => $request->header('X-Tenant-Subdomain'),
                    'name' => $request->header('X-Tenant-Name')
                ]);

                $request->attributes->set('user', [
                    'id' => $request->header('X-User-Id'),
                    'name' => $request->header('X-User-Name'),
                    'email' => $request->header('X-User-Email'),
                    'role' => $request->header('X-User-Role')
                ]);

                // Configurar prefixo das tabelas
                $prefix = $request->header('X-DB-Prefix', 'cp_');
                config(['database.connections.pgsql.prefix' => $prefix]);

            } catch (\Exception $e) {
                Log::error('Erro ao processar token do módulo', [
                    'error' => $e->getMessage()
                ]);

                if (config('app.env') !== 'local') {
                    abort(401, 'Token de autenticação inválido');
                }
            }
        }

        // Para desenvolvimento local sem proxy
        if (config('app.env') === 'local' && !$request->header('X-Tenant-Id')) {
            $request->attributes->set('tenant', [
                'id' => 1,
                'subdomain' => 'desenvolvimento',
                'name' => 'Desenvolvimento Local'
            ]);

            $request->attributes->set('user', [
                'id' => 1,
                'name' => 'Desenvolvedor',
                'email' => 'dev@local.test',
                'role' => 'admin'
            ]);

            config(['database.connections.pgsql.prefix' => 'cp_']);
        }

        return $next($request);
    }
}