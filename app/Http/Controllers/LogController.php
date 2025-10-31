<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LogController extends Controller
{
    /**
     * Recebe logs do navegador (console.log, console.error, etc)
     */
    public function storeBrowserLog(Request $request)
    {
        try {
            $logData = $request->validate([
                'type' => 'required|string|in:log,info,warn,error,debug',
                'message' => 'required|string',
                'url' => 'nullable|string',
                'line' => 'nullable|integer',
                'column' => 'nullable|integer',
                'stack' => 'nullable|string',
                'timestamp' => 'nullable|integer',
                'userAgent' => 'nullable|string',
            ]);

            // Adicionar informações de contexto
            $logData['ip'] = $request->ip();
            $logData['user_id'] = $request->header('X-User-Id');
            $logData['user_email'] = $request->header('X-User-Email');
            $logData['tenant_id'] = $request->header('X-Tenant-Id');
            $logData['server_time'] = Carbon::now()->toIso8601String();

            // Salvar em arquivo específico para logs do browser
            $logFile = storage_path('logs/sistema_detalhado/browser/browser-' . date('Y-m-d') . '.log');

            $logEntry = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $separator = str_repeat('=', 80);

            file_put_contents(
                $logFile,
                "\n{$separator}\n[" . Carbon::now()->toDateTimeString() . "] BROWSER {$logData['type']}\n{$separator}\n{$logEntry}\n",
                FILE_APPEND
            );

            // Também logar no Laravel log se for erro
            if ($logData['type'] === 'error') {
                Log::channel('stack')->error('Browser Error', $logData);

                // Salvar em arquivo combinado
                $combinedFile = storage_path('logs/sistema_detalhado/combined/combined-' . date('Y-m-d') . '.log');
                file_put_contents(
                    $combinedFile,
                    "\n{$separator}\n[BROWSER ERROR] " . Carbon::now()->toDateTimeString() . "\n{$separator}\n{$logEntry}\n",
                    FILE_APPEND
                );
            }

            return response()->json(['success' => true, 'message' => 'Log registrado com sucesso']);
        } catch (\Exception $e) {
            Log::error('Erro ao processar log do browser', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualizar logs do sistema
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'browser'); // browser, server, combined
        $date = $request->get('date', date('Y-m-d'));

        $logPath = storage_path("logs/sistema_detalhado/{$type}/{$type}-{$date}.log");

        $content = file_exists($logPath) ? file_get_contents($logPath) : 'Nenhum log encontrado para esta data.';

        return view('logs.index', [
            'content' => $content,
            'type' => $type,
            'date' => $date,
            'available_dates' => $this->getAvailableDates($type)
        ]);
    }

    /**
     * Obter datas disponíveis de logs
     */
    private function getAvailableDates($type)
    {
        $logDir = storage_path("logs/sistema_detalhado/{$type}/");

        if (!is_dir($logDir)) {
            return [];
        }

        $files = glob($logDir . "*.log");
        $dates = [];

        foreach ($files as $file) {
            if (preg_match('/(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                $dates[] = $matches[1];
            }
        }

        rsort($dates); // Mais recentes primeiro

        return $dates;
    }

    /**
     * Limpar logs antigos (mais de 7 dias)
     */
    public function cleanOldLogs()
    {
        $directories = ['browser', 'server', 'combined'];
        $deleted = 0;

        foreach ($directories as $dir) {
            $logDir = storage_path("logs/sistema_detalhado/{$dir}/");

            if (!is_dir($logDir)) {
                continue;
            }

            $files = glob($logDir . "*.log");

            foreach ($files as $file) {
                $fileTime = filemtime($file);
                $daysDiff = (time() - $fileTime) / (60 * 60 * 24);

                if ($daysDiff > 7) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$deleted} arquivo(s) de log antigo(s) removido(s)"
        ]);
    }

    /**
     * Download de arquivo de log específico
     */
    public function download(Request $request)
    {
        $type = $request->get('type', 'browser');
        $date = $request->get('date', date('Y-m-d'));

        $logPath = storage_path("logs/sistema_detalhado/{$type}/{$type}-{$date}.log");

        if (!file_exists($logPath)) {
            abort(404, 'Arquivo de log não encontrado');
        }

        return response()->download($logPath);
    }
}
