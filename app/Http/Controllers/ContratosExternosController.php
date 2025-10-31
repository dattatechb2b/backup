<?php

namespace App\Http\Controllers;

use App\Models\ContratoExterno;
use App\Models\ItemContratoExterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContratosExternosController extends Controller
{
    /**
     * Buscar preços por descrição (fulltext search)
     */
    public function buscarPorDescricao(Request $request)
    {
        $termo = $request->input('termo');
        $limite = $request->input('limite', 50);

        if (empty($termo)) {
            return response()->json(['erro' => 'Termo de busca obrigatório'], 400);
        }

        $prefix = DB::getTablePrefix();

        $itens = ItemContratoExterno::query()
            ->select([
                'i.id',
                'i.descricao',
                'i.valor_unitario',
                'i.unidade',
                'i.catmat',
                'i.qualidade_score',
                'i.flags_qualidade',
                'c.numero_contrato',
                'c.orgao_nome',
                'c.data_assinatura',
                'c.fonte',
            ])
            ->from('cp_itens_contrato_externo as i')
            ->join('cp_contratos_externos as c', 'i.contrato_id', '=', 'c.id')
            ->whereRaw(
                "to_tsvector('portuguese', {$prefix}i.descricao) @@ plainto_tsquery('portuguese', ?)",
                [$termo]
            )
            ->where('i.valor_unitario', '>', 0)
            ->where('i.qualidade_score', '>=', 70)
            ->orderBy('c.data_assinatura', 'desc')
            ->limit($limite)
            ->get();

        return response()->json([
            'termo' => $termo,
            'total' => $itens->count(),
            'itens' => $itens,
        ]);
    }

    /**
     * Buscar preços por CATMAT
     */
    public function buscarPorCatmat(Request $request, string $catmat)
    {
        $itens = ItemContratoExterno::query()
            ->select([
                'i.descricao',
                'i.valor_unitario',
                'i.unidade',
                'i.quantidade',
                'i.qualidade_score',
                'c.numero_contrato',
                'c.orgao_nome',
                'c.data_assinatura',
                'c.fonte',
            ])
            ->from('cp_itens_contrato_externo as i')
            ->join('cp_contratos_externos as c', 'i.contrato_id', '=', 'c.id')
            ->where('i.catmat', $catmat)
            ->where('i.valor_unitario', '>', 0)
            ->where('i.qualidade_score', '>=', 70)
            ->orderBy('c.data_assinatura', 'desc')
            ->limit(100)
            ->get();

        // Calcular estatísticas
        $stats = ItemContratoExterno::query()
            ->where('catmat', $catmat)
            ->where('valor_unitario', '>', 0)
            ->where('qualidade_score', '>=', 70)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(valor_unitario) as preco_medio'),
                DB::raw('MIN(valor_unitario) as preco_min'),
                DB::raw('MAX(valor_unitario) as preco_max'),
                DB::raw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY valor_unitario) as mediana'),
            ])
            ->first();

        return response()->json([
            'catmat' => $catmat,
            'estatisticas' => $stats,
            'itens' => $itens,
        ]);
    }

    /**
     * Obter estatísticas de preços para um termo
     */
    public function estatisticas(Request $request)
    {
        $termo = $request->input('termo');

        if (empty($termo)) {
            return response()->json(['erro' => 'Termo de busca obrigatório'], 400);
        }

        $stats = ItemContratoExterno::query()
            ->whereRaw(
                "to_tsvector('portuguese', descricao) @@ plainto_tsquery('portuguese', ?)",
                [$termo]
            )
            ->where('valor_unitario', '>', 0)
            ->where('qualidade_score', '>=', 70)
            ->select([
                DB::raw('COUNT(*) as total_registros'),
                DB::raw('AVG(valor_unitario) as preco_medio'),
                DB::raw('MIN(valor_unitario) as preco_minimo'),
                DB::raw('MAX(valor_unitario) as preco_maximo'),
                DB::raw('PERCENTILE_CONT(0.25) WITHIN GROUP (ORDER BY valor_unitario) as percentil_25'),
                DB::raw('PERCENTILE_CONT(0.50) WITHIN GROUP (ORDER BY valor_unitario) as mediana'),
                DB::raw('PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY valor_unitario) as percentil_75'),
                DB::raw('STDDEV(valor_unitario) as desvio_padrao'),
            ])
            ->first();

        return response()->json([
            'termo' => $termo,
            'estatisticas' => $stats,
        ]);
    }

    /**
     * Listar contratos recentes
     */
    public function listarContratos(Request $request)
    {
        $fonte = $request->input('fonte');
        $limite = $request->input('limite', 20);

        $query = ContratoExterno::query()
            ->select([
                'id',
                'fonte',
                'numero_contrato',
                'objeto',
                'valor_total',
                'data_assinatura',
                'orgao_nome',
                'fornecedor_nome',
                'qualidade_score',
            ])
            ->orderBy('data_assinatura', 'desc');

        if ($fonte) {
            $query->where('fonte', 'like', "%{$fonte}%");
        }

        $contratos = $query->limit($limite)->get();

        return response()->json([
            'total' => $contratos->count(),
            'contratos' => $contratos,
        ]);
    }

    /**
     * Detalhes de um contrato com seus itens
     */
    public function detalhes(int $id)
    {
        $contrato = ContratoExterno::with(['itens' => function($query) {
            $query->orderBy('numero_item');
        }])->findOrFail($id);

        return response()->json($contrato);
    }
}
