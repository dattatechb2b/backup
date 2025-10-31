<?php

namespace App\Http\Controllers;

use App\Services\CnpjService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CnpjController extends Controller
{
    private $cnpjService;

    public function __construct(CnpjService $cnpjService)
    {
        $this->cnpjService = $cnpjService;
    }

    /**
     * Consultar CNPJ via API
     *
     * POST /api/cnpj/consultar
     * Body: { "cnpj": "00.000.000/0000-00" }
     */
    public function consultar(Request $request)
    {
        try {
            \Log::info('CnpjController::consultar - Início', [
                'input' => $request->all(),
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Validação
            try {
                $request->validate([
                    'cnpj' => 'required|string|min:14|max:18'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Erro na validação do CNPJ', [
                    'error' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'input' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos: ' . $e->getMessage(),
                    'errors' => $e->errors()
                ], 422);
            }

            $cnpj = $request->input('cnpj');
            \Log::info('CNPJ recebido', ['cnpj' => $cnpj]);

            // Rate limiting por IP
            $key = 'cnpj-consulta:' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);

                \Log::warning('Rate limit atingido', [
                    'ip' => $request->ip(),
                    'seconds' => $seconds
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Muitas consultas. Tente novamente em {$seconds} segundos."
                ], 429);
            }

            RateLimiter::hit($key, 60); // 1 minuto

            // Consultar
            \Log::info('Chamando CnpjService::consultar', ['cnpj' => $cnpj]);
            $resultado = $this->cnpjService->consultar($cnpj);
            \Log::info('CnpjService retornou resultado', ['success' => $resultado['success'] ?? false]);

            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('ERRO CRÍTICO em CnpjController::consultar', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao consultar CNPJ. Por favor, tente novamente.',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
