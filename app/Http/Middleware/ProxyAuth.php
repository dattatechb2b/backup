<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ProxyAuth
{
    /**
     * Handle an incoming request.
     *
     * Este middleware autentica automaticamente o usuário quando
     * a requisição vem via proxy do MinhaDataTech
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rotas públicas - não precisa autenticação via proxy
        $publicRoutes = [
            '/responder-cdf',
            '/api/cdf/responder',
            '/api/cdf/consultar-cnpj',
            '/storage/',  // Arquivos estáticos (brasões, PDFs, uploads)
            '/brasao/'    // Rota específica para brasões
        ];

        foreach ($publicRoutes as $route) {
            if (str_starts_with($request->getPathInfo(), $route)) {
                // CORREÇÃO: Configurar banco dinamicamente APENAS se os headers existirem
                // Para formulários públicos CDF, o ModuleProxyController deve enviar os headers
                // do banco baseado no tenant (subdomínio ou contexto da requisição)
                if ($request->hasHeader('X-DB-Name') && $request->hasHeader('X-DB-User')) {
                    $this->configureDynamicDatabaseConnection($request);
                    Log::info('ProxyAuth: Rota pública CDF - banco configurado via headers', [
                        'uri' => $request->getRequestUri(),
                        'database' => $request->header('X-DB-Name')
                    ]);
                } else {
                    // Usar configuração padrão do .env se não houver headers
                    Log::warning('ProxyAuth: Rota pública CDF SEM headers de banco - usando padrão', [
                        'uri' => $request->getRequestUri(),
                        'default_db' => config('database.connections.pgsql.database'),
                        'all_headers' => $request->headers->all()
                    ]);
                }
                return $next($request);
            }
        }

        // LOG REMOVIDO: Estava causando spam excessivo nos logs
        // especialmente com polling de notificações (/api/notificacoes/nao-lidas)
        // $cookieValue = $request->cookie('cesta_de_precos_session');
        //
        // Log::info('ProxyAuth: INÍCIO - Requisição recebida', [
        //     'uri' => $request->getRequestUri(),
        //     'method' => $request->method(),
        //     ...
        // ]);

        // PRIORIDADE: Verificar se já tem dados de autenticação na sessão
        $tenantData = session('proxy_tenant');
        $userData = session('proxy_user_data');
        $dbConfig = session('proxy_db_config'); // NOVA ARQUITETURA: Configuração completa do banco

        // Verificar se tem chave de login do Laravel na sessão
        $guard = Auth::guard('web');
        $providerClass = get_class($guard->getProvider());
        $sessionKey = 'login_web_' . sha1($providerClass);
        $hasLoginKey = session()->has($sessionKey);

        // LOG REMOVIDO: Causava spam excessivo nos logs
        // Log::info('ProxyAuth: Verificando sessão existente', [...])

        if ($tenantData && $userData && $dbConfig && $hasLoginKey) {
            // ✅ VALIDAÇÃO DE SEGURANÇA: Verificar se tenant da sessão == tenant da requisição
            $currentTenantId = $request->header('X-Tenant-Id');
            $sessionTenantId = $tenantData['id'] ?? null;

            if ($currentTenantId && $sessionTenantId && $currentTenantId != $sessionTenantId) {
                // 🚨 BLOQUEIO: Cross-tenant access attempt BLOCKED!
                Log::critical('ProxyAuth: Cross-tenant access attempt BLOCKED!', [
                    'session_tenant_id' => $sessionTenantId,
                    'session_tenant_subdomain' => $tenantData['subdomain'] ?? 'N/A',
                    'session_tenant_db' => $dbConfig['database'] ?? 'N/A',
                    'current_tenant_id' => $currentTenantId,
                    'current_tenant_subdomain' => $request->header('X-Tenant-Subdomain'),
                    'current_tenant_db' => $request->header('X-DB-Name'),
                    'user_email' => $userData['email'] ?? 'N/A',
                    'uri' => $request->getRequestUri(),
                    'method' => $request->method()
                ]);

                // Limpar sessão do módulo (forçar reautenticação via headers)
                session()->forget(['proxy_tenant', 'proxy_user_data', 'proxy_db_config']);

                // NÃO fazer early return - deixar código continuar para autenticar via headers
                Log::info('ProxyAuth: Sessão limpa, reautenticando via headers do proxy');
            } else {
                // ✅ Validação passou: tenant correto, restaurar contexto
                // NOVA ARQUITETURA: Restaurar configuração dinâmica do banco
                $this->configureDatabaseFromConfig($dbConfig);

                $request->attributes->set('tenant', $tenantData);
                $request->attributes->set('user', $userData);

                // Garantir que o Auth facade reconheça o usuário
                $userId = session($sessionKey);
                $user = User::find($userId);
                if ($user) {
                    $guard->setUser($user);
                }

                // LOG REMOVIDO: Causava spam excessivo nos logs
                // Log::info('ProxyAuth: Contexto restaurado da sessão', [...])

                return $next($request);
            }
        }

        // Verificar se vem do proxy (tem headers X-User-*)
        $userId = $request->header('X-User-Id');
        $userEmail = $request->header('X-User-Email');
        $userName = $request->header('X-User-Name');
        $tenantId = $request->header('X-Tenant-Id');
        $tenantSubdomain = $request->header('X-Tenant-Subdomain');
        $tenantName = $request->header('X-Tenant-Name');

        // Se tem headers do proxy, autenticar e PERSISTIR na sessão
        if ($userId && $userEmail && $tenantId) {
            // NOVA ARQUITETURA: Salvar configuração completa do banco na sessão
            $dbConfig = [
                'database' => $request->header('X-DB-Name'),
                'host' => $request->header('X-DB-Host', '127.0.0.1'),
                'username' => $request->header('X-DB-User'),
                'password' => $request->header('X-DB-Password'),
            ];

            // Salvar dados do tenant na sessão para requisições subsequentes
            session([
                'proxy_tenant' => [
                    'id' => $tenantId,
                    'subdomain' => $tenantSubdomain,
                    'name' => $tenantName
                ],
                'proxy_user_data' => [
                    'id' => $userId,
                    'name' => $userName,
                    'email' => $userEmail,
                    'role' => $request->header('X-User-Role', 'user')
                ],
                'proxy_db_config' => $dbConfig
            ]);

            Log::info('ProxyAuth: SESSÃO SALVA com configuração dinâmica de banco', [
                'tenant_id' => $tenantId,
                'subdomain' => $tenantSubdomain,
                'database' => $dbConfig['database'],
                'host' => $dbConfig['host'],
                'session_has_proxy_tenant_id' => session()->has('proxy_tenant.id'),
                'session_proxy_tenant_id_value' => session('proxy_tenant.id'),
            ]);

            // NOVA ARQUITETURA: Configurar conexão dinâmica do banco
            $this->configureDynamicDatabaseConnection($request);

            // Adicionar ao request attributes (para views que usam)
            $request->attributes->set('tenant', [
                'id' => $tenantId,
                'subdomain' => $tenantSubdomain,
                'name' => $tenantName
            ]);

            $request->attributes->set('user', [
                'id' => $userId,
                'name' => $userName,
                'email' => $userEmail,
                'role' => $request->header('X-User-Role', 'user')
            ]);

            // Buscar ou criar usuário no módulo baseado no email
            $user = User::firstOrCreate(
                ['email' => $userEmail],
                [
                    'name' => $userName ?? 'Usuário',
                    'username' => $this->generateUniqueUsername($userEmail), // Gerar username único
                    'password' => bcrypt(str()->random(32)), // Senha aleatória (não será usada)
                    'email_verified_at' => now()
                ]
            );

            // Autenticar usuário MANUALMENTE na sessão (sem regenerar)
            $sessionIdBefore = session()->getId();

            // Adicionar manualmente o ID do usuário na sessão
            // Laravel usa 'login_web_' + hash do nome da classe do provider
            $guard = Auth::guard('web');
            $providerClass = get_class($guard->getProvider());
            $sessionKey = 'login_web_' . sha1($providerClass);

            session()->put($sessionKey, $user->getAuthIdentifier());
            session()->put('password_hash_web', $user->getAuthPassword());

            // Definir o usuário no guard atual
            $guard->setUser($user);

            // CRÍTICO: Salvar explicitamente a sessão para garantir persistência
            session()->save();

            $sessionIdAfter = session()->getId();

            Log::info('ProxyAuth: Autenticação via proxy com persistência', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $tenantId,
                'tenant_subdomain' => $tenantSubdomain,
                'database' => $dbConfig['database'] ?? 'N/A',
                'session_regenerated' => $sessionIdBefore !== $sessionIdAfter,
                'session_id_after' => $sessionIdAfter,
                'auth_persisted' => Auth::check(),
                'session_key' => $sessionKey ?? 'N/A',
                'session_has_auth_key' => session()->has($sessionKey ?? 'N/A')
            ]);
        }

        return $next($request);
    }

    /**
     * NOVA ARQUITETURA: Configurar conexão dinâmica do banco a partir dos headers
     *
     * @param Request $request
     * @return void
     */
    private function configureDynamicDatabaseConnection(Request $request): void
    {
        $dbConfig = [
            'driver' => 'pgsql',
            'host' => $request->header('X-DB-Host', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => $request->header('X-DB-Name'),
            'username' => $request->header('X-DB-User'),
            'password' => $request->header('X-DB-Password'),
            'charset' => 'utf8',
            'prefix' => '', // PREFIXO REMOVIDO - Todas as tabelas já têm cp_ explícito
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ];

        // Configurar conexão 'pgsql' com os dados do tenant
        config(['database.connections.pgsql' => $dbConfig]);

        // Reconectar para aplicar as novas configurações
        \Illuminate\Support\Facades\DB::purge('pgsql');
        \Illuminate\Support\Facades\DB::reconnect('pgsql');

        Log::info('ProxyAuth: Conexão dinâmica configurada', [
            'database' => $dbConfig['database'],
            'host' => $dbConfig['host'],
            'username' => $dbConfig['username']
        ]);
    }

    /**
     * NOVA ARQUITETURA: Configurar banco a partir de config salva na sessão
     *
     * @param array $dbConfig
     * @return void
     */
    private function configureDatabaseFromConfig(array $dbConfig): void
    {
        $fullConfig = [
            'driver' => 'pgsql',
            'host' => $dbConfig['host'] ?? '127.0.0.1',
            'port' => env('DB_PORT', '5432'),
            'database' => $dbConfig['database'],
            'username' => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'charset' => 'utf8',
            'prefix' => '', // PREFIXO REMOVIDO - Todas as tabelas já têm cp_ explícito
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ];

        // Configurar conexão 'pgsql' com os dados do tenant
        config(['database.connections.pgsql' => $fullConfig]);

        // Reconectar para aplicar as novas configurações
        \Illuminate\Support\Facades\DB::purge('pgsql');
        \Illuminate\Support\Facades\DB::reconnect('pgsql');

        // LOG REMOVIDO: Causava spam excessivo nos logs
        // Log::info('ProxyAuth: Conexão restaurada da sessão', [...])
    }

    /**
     * Gerar username único baseado no email
     * Se o username já existir, adiciona sufixo incremental
     *
     * @param string $email
     * @return string
     */
    private function generateUniqueUsername(string $email): string
    {
        // Extrair parte antes do @ do email
        $baseUsername = explode('@', $email)[0];
        $username = $baseUsername;
        $counter = 1;

        // Verificar se username já existe, se sim adicionar sufixo
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter;
            $counter++;

            // Proteção contra loop infinito (máximo 1000 tentativas)
            if ($counter > 1000) {
                // Fallback: usar email completo como username (garantido ser único)
                $username = $email;
                break;
            }
        }

        Log::info('ProxyAuth: Username único gerado', [
            'email' => $email,
            'baseUsername' => $baseUsername,
            'finalUsername' => $username,
            'attempts' => $counter - 1
        ]);

        return $username;
    }
}