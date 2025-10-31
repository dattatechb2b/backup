<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificacaoController extends Controller
{
    /**
     * Buscar notificações não lidas do usuário logado
     * GET /api/notificacoes/nao-lidas
     */
    public function naoLidas(Request $request)
    {
        try {
            // CORREÇÃO: Mapear usuário pelo email em vez de usar X-User-ID diretamente
            // porque o X-User-ID é do MinhaDattaTech, mas precisamos do ID local do módulo
            $userEmail = $request->header('X-User-Email');

            if (!$userEmail) {
                return response()->json([
                    'success' => false,
                    'count' => 0,
                    'notificacoes' => []
                ]);
            }

            // Buscar usuário local do módulo pelo email
            $user = \App\Models\User::where('email', $userEmail)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'count' => 0,
                    'notificacoes' => []
                ]);
            }

            // Buscar notificações não lidas usando o ID LOCAL do módulo
            $notificacoes = Notificacao::where('user_id', $user->id)
                ->where('lida', false)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'count' => $notificacoes->count(),
                'notificacoes' => $notificacoes->map(function($notif) {
                    return [
                        'id' => $notif->id,
                        'tipo' => $notif->tipo,
                        'titulo' => $notif->titulo,
                        'mensagem' => $notif->mensagem,
                        'dados' => $notif->dados,
                        'created_at' => $notif->created_at->format('d/m/Y H:i')
                    ];
                })
            ]);

        } catch (\Exception $e) {
            // Silenciar erro - não logar polling normal
            return response()->json([
                'success' => false,
                'count' => 0,
                'notificacoes' => []
            ], 200); // Retornar 200 em vez de 500 para não poluir logs
        }
    }

    /**
     * Marcar notificação como lida
     * POST /api/notificacoes/{id}/marcar-lida
     */
    public function marcarComoLida(Request $request, $id)
    {
        try {
            // CORREÇÃO: Mapear usuário pelo email
            $userEmail = $request->header('X-User-Email');

            if (!$userEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Buscar usuário local do módulo pelo email
            $user = \App\Models\User::where('email', $userEmail)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], 404);
            }

            $notificacao = Notificacao::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $notificacao->update([
                'lida' => true,
                'lida_em' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificação marcada como lida'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar notificação'
            ], 500);
        }
    }

    /**
     * Marcar todas as notificações como lidas
     * POST /api/notificacoes/marcar-todas-lidas
     */
    public function marcarTodasComoLidas(Request $request)
    {
        try {
            // CORREÇÃO: Mapear usuário pelo email
            $userEmail = $request->header('X-User-Email');

            if (!$userEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Buscar usuário local do módulo pelo email
            $user = \App\Models\User::where('email', $userEmail)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], 404);
            }

            Notificacao::where('user_id', $user->id)
                ->where('lida', false)
                ->update([
                    'lida' => true,
                    'lida_em' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas as notificações foram marcadas como lidas'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar notificações'
            ], 500);
        }
    }
}
