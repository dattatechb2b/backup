<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSaveSession
{
    /**
     * Handle an incoming request.
     *
     * Este middleware força o salvamento da sessão APÓS a resposta ser gerada
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Forçar salvamento da sessão
        if (session()->isStarted()) {
            session()->save();
        }

        return $response;
    }
}
