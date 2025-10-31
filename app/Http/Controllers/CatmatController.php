<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Catmat;
use Illuminate\Support\Facades\Cache;

class CatmatController extends Controller
{
    /**
     * Autocomplete/suggest para CATMAT/CATSER
     * Retorna sugestões baseadas em busca fulltext
     *
     * GET /api/catmat/suggest?termo=papel&limite=10
     */
    public function suggest(Request $request)
    {
        $termo = $request->input('termo', '');
        $limite = $request->input('limite', 10);

        if (strlen($termo) < 2) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Digite pelo menos 2 caracteres para buscar',
                'resultados' => [],
            ]);
        }

        // Cache de 1 hora para buscas
        $cacheKey = "catmat_suggest_" . md5($termo . "_" . $limite);

        $resultados = Cache::remember($cacheKey, 3600, function () use ($termo, $limite) {
            return Catmat::ativo()
                ->buscarTitulo($termo)
                ->orderBy('contador_ocorrencias', 'desc') // Mais usados primeiro
                ->orderBy('titulo', 'asc')
                ->limit($limite)
                ->get(['codigo', 'titulo', 'tipo', 'unidade_padrao'])
                ->map(function ($item) {
                    return [
                        'codigo' => $item->codigo,
                        'titulo' => $item->titulo,
                        'tipo' => $item->tipo,
                        'unidade_padrao' => $item->unidade_padrao,
                        'label' => "{$item->codigo} - {$item->titulo}",
                    ];
                });
        });

        return response()->json([
            'sucesso' => true,
            'total' => $resultados->count(),
            'resultados' => $resultados,
        ]);
    }

    /**
     * Busca código específico
     *
     * GET /api/catmat/{codigo}
     */
    public function show($codigo)
    {
        $catmat = Catmat::ativo()->porCodigo($codigo)->first();

        if (!$catmat) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Código CATMAT/CATSER não encontrado',
            ], 404);
        }

        // Registrar ocorrência
        $catmat->registrarOcorrencia();

        return response()->json([
            'sucesso' => true,
            'dados' => [
                'codigo' => $catmat->codigo,
                'titulo' => $catmat->titulo,
                'tipo' => $catmat->tipo,
                'unidade_padrao' => $catmat->unidade_padrao,
                'caminho_hierarquia' => $catmat->caminho_hierarquia,
                'fonte' => $catmat->fonte,
                'contador_ocorrencias' => $catmat->contador_ocorrencias,
            ],
        ]);
    }

    /**
     * Lista todos os CATMAT ativos (paginado)
     *
     * GET /api/catmat?tipo=CATMAT&pagina=1&limite=50
     */
    public function index(Request $request)
    {
        $tipo = $request->input('tipo'); // CATMAT ou CATSER
        $limite = $request->input('limite', 50);

        $query = Catmat::ativo();

        if ($tipo) {
            $query->where('tipo', strtoupper($tipo));
        }

        $resultados = $query->orderBy('titulo', 'asc')
            ->paginate($limite);

        return response()->json([
            'sucesso' => true,
            'total' => $resultados->total(),
            'pagina_atual' => $resultados->currentPage(),
            'total_paginas' => $resultados->lastPage(),
            'resultados' => $resultados->items(),
        ]);
    }

    /**
     * Registra novo código CATMAT encontrado no PNCP (auto-learning)
     *
     * POST /api/catmat/auto-registro
     * Body: { "codigo": "123456", "titulo": "...", "tipo": "CATMAT", "unidade": "UN" }
     */
    public function autoRegistro(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:20',
            'titulo' => 'required|string',
            'tipo' => 'required|in:CATMAT,CATSER',
            'unidade' => 'nullable|string|max:50',
        ]);

        $catmat = Catmat::updateOrCreate(
            ['codigo' => $validated['codigo']],
            [
                'titulo' => $validated['titulo'],
                'tipo' => $validated['tipo'],
                'unidade_padrao' => $validated['unidade'] ?? null,
                'fonte' => 'PNCP_AUTO',
                'primeira_ocorrencia_em' => now(),
                'ultima_ocorrencia_em' => now(),
                'contador_ocorrencias' => 1,
                'ativo' => true,
            ]
        );

        if ($catmat->wasRecentlyCreated) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Novo código CATMAT/CATSER registrado com sucesso',
                'dados' => $catmat,
            ], 201);
        }

        // Se já existia, apenas incrementa contador
        $catmat->registrarOcorrencia();

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Código CATMAT/CATSER já existe, contador atualizado',
            'dados' => $catmat,
        ]);
    }
}
