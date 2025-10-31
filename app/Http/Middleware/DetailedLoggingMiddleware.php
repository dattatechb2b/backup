<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class DetailedLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Capturar informações da requisição
        $requestData = $this->captureRequestData($request);

        // Processar requisição
        $response = $next($request);

        // Calcular tempo de execução
        $executionTime = round((microtime(true) - $startTime) * 1000, 2); // em ms

        // Capturar informações da resposta
        $responseData = $this->captureResponseData($response, $executionTime);

        // Combinar dados
        $logData = array_merge($requestData, $responseData);

        // Salvar em arquivo de log do servidor
        $this->saveServerLog($logData, $request);

        // Se houve erro, salvar também no log combinado
        if ($response->status() >= 400) {
            $this->saveCombinedLog($logData, 'SERVER_ERROR');
        }

        return $response;
    }

    /**
     * Capturar dados da requisição
     */
    private function captureRequestData(Request $request): array
    {
        return [
            'timestamp' => Carbon::now()->toIso8601String(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->header('X-User-Id'),
            'user_email' => $request->header('X-User-Email'),
            'tenant_id' => $request->header('X-Tenant-Id'),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query(),
            'body_size' => strlen($request->getContent()),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
        ];
    }

    /**
     * Capturar dados da resposta
     */
    private function captureResponseData($response, float $executionTime): array
    {
        return [
            'status_code' => $response->status(),
            'execution_time_ms' => $executionTime,
            'response_size' => strlen($response->getContent()),
            'content_type' => $response->headers->get('Content-Type'),
        ];
    }

    /**
     * Sanitizar headers sensíveis
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-csrf-token', 'x-xsrf-token'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Salvar log do servidor
     */
    private function saveServerLog(array $logData, Request $request): void
    {
        try {
            $logFile = storage_path('logs/sistema_detalhado/server/server-' . date('Y-m-d') . '.log');

            $logEntry = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $separator = str_repeat('=', 80);

            $statusEmoji = $logData['status_code'] >= 400 ? '❌' : '✅';

            file_put_contents(
                $logFile,
                "\n{$separator}\n{$statusEmoji} [{$logData['timestamp']}] {$logData['method']} {$logData['path']} - {$logData['status_code']} ({$logData['execution_time_ms']}ms)\n{$separator}\n{$logEntry}\n",
                FILE_APPEND
            );
        } catch (Throwable $e) {
            Log::error('Erro ao salvar log do servidor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Salvar no log combinado (erros de servidor + browser)
     */
    private function saveCombinedLog(array $logData, string $type): void
    {
        try {
            $logFile = storage_path('logs/sistema_detalhado/combined/combined-' . date('Y-m-d') . '.log');

            $logEntry = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $separator = str_repeat('=', 80);

            file_put_contents(
                $logFile,
                "\n{$separator}\n[{$type}] {$logData['timestamp']}\n{$separator}\n{$logEntry}\n",
                FILE_APPEND
            );
        } catch (Throwable $e) {
            Log::error('Erro ao salvar log combinado', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle exceptions
     */
    public function terminate($request, $response)
    {
        // Capturar erros PHP que aconteceram durante a execução
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logData = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'type' => 'PHP_ERROR',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ];

            $this->saveCombinedLog($logData, 'PHP_FATAL_ERROR');
        }
    }
}
