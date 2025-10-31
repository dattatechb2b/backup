<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CleanDuplicateCookies
{
    /**
     * Handle an incoming request.
     *
     * Este middleware DEVE rodar ANTES do StartSession
     * para garantir que apenas um cookie de sessão seja processado
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieHeader = $request->header('Cookie');

        if ($cookieHeader) {
            // Procurar por múltiplas ocorrências do cookie de sessão
            $sessionCookieName = 'cesta_de_precos_session';
            preg_match_all('/' . preg_quote($sessionCookieName) . '=([^;]+)/', $cookieHeader, $matches);

            if (isset($matches[1]) && count($matches[1]) > 1) {
                Log::warning('CleanDuplicateCookies: COOKIES DUPLICADOS DETECTADOS!', [
                    'uri' => $request->getRequestUri(),
                    'cookie_count' => count($matches[1]),
                    'cookie_values' => array_map(function($v) {
                        return substr($v, 0, 20) . '... (length: ' . strlen($v) . ')';
                    }, $matches[1])
                ]);

                // PROCURAR qual cookie tem sessão AUTENTICADA no banco
                $authenticatedCookie = null;
                foreach ($matches[1] as $cookieValue) {
                    try {
                        // Tentar descriptografar o cookie
                        $sessionId = decrypt($cookieValue);

                        // Verificar se essa sessão existe no banco e tem user_id
                        $session = \DB::connection('pgsql_sessions')
                            ->table('sessions')  // Conexão pgsql_sessions já adiciona prefixo cp_
                            ->where('id', $sessionId)
                            ->first();

                        if ($session && $session->user_id !== null) {
                            $authenticatedCookie = $cookieValue;
                            Log::info('CleanDuplicateCookies: ENCONTRADO cookie com sessão autenticada!', [
                                'session_id' => $sessionId,
                                'user_id' => $session->user_id
                            ]);
                            break;
                        }
                    } catch (\Exception $e) {
                        // Cookie inválido ou sessão não existe, tentar próximo
                        continue;
                    }
                }

                // Se não encontrou cookie autenticado, usar o primeiro
                $firstCookie = $authenticatedCookie ?? $matches[1][0];

                if ($authenticatedCookie === null) {
                    Log::warning('CleanDuplicateCookies: Nenhum cookie autenticado encontrado, usando primeiro cookie');
                }

                // Reconstruir header Cookie sem duplicatas
                $cleanedCookieHeader = preg_replace(
                    '/' . preg_quote($sessionCookieName) . '=[^;]+(; ?)?/',
                    '',
                    $cookieHeader
                );

                // Adicionar apenas o primeiro cookie de volta
                $cleanedCookieHeader = $sessionCookieName . '=' . $firstCookie .
                    (empty(trim($cleanedCookieHeader)) ? '' : '; ' . trim($cleanedCookieHeader));

                // Substituir o header Cookie na requisição
                $request->headers->set('Cookie', $cleanedCookieHeader);

                // Forçar reconstrução do cookie bag do request
                $request->cookies = new \Symfony\Component\HttpFoundation\InputBag(
                    array_merge(
                        $request->cookies->all(),
                        [$sessionCookieName => $firstCookie]
                    )
                );

                Log::info('CleanDuplicateCookies: Cookies limpos ANTES da sessão iniciar', [
                    'first_cookie_preview' => substr($firstCookie, 0, 20) . '...',
                    'cleaned_cookie_count' => 1
                ]);
            }
        }

        return $next($request);
    }
}
