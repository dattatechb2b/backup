<?php

namespace App\Http\Controllers;

use App\Models\OrientacaoTecnica;
use Illuminate\Http\Request;

class OrientacaoTecnicaController extends Controller
{
    /**
     * Exibir lista de Orientações Técnicas
     */
    public function index()
    {
        $orientacoes = OrientacaoTecnica::obterTodas();

        return view('orientacoes.index', compact('orientacoes'));
    }

    /**
     * Buscar orientações por termo (AJAX)
     */
    public function buscar(Request $request)
    {
        $termo = $request->input('termo', '');

        if (empty($termo)) {
            $orientacoes = OrientacaoTecnica::obterTodas();
        } else {
            $orientacoes = OrientacaoTecnica::buscarPorTermo($termo);
        }

        return response()->json([
            'success' => true,
            'orientacoes' => $orientacoes->map(function($ot) {
                return [
                    'id' => $ot->id,
                    'numero' => $ot->numero,
                    'titulo' => $ot->titulo,
                    'conteudo' => $ot->conteudo
                ];
            })
        ]);
    }
}
