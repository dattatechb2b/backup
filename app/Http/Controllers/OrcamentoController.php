<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Lote;
use App\Models\ContratoPNCP;
use App\Models\ColetaEcommerce;
use App\Models\ColetaEcommerceItem;
use App\Models\ContratacaoSimilar;
use App\Models\ContratacaoSimilarItem;
use App\Models\SolicitacaoCDF;
use App\Models\SolicitacaoCDFItem;
use App\Models\Anexo;
use App\Models\Orgao;
use App\Mail\CdfSolicitacaoMail;
use App\Services\TceRsApiService;
use App\Services\ComprasnetApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class OrcamentoController extends Controller
{
    /**
     * Serviços de API em tempo real
     */
    private TceRsApiService $tceRsApi;
    private ComprasnetApiService $comprasnetApi;

    public function __construct(TceRsApiService $tceRsApi, ComprasnetApiService $comprasnetApi)
    {
        $this->tceRsApi = $tceRsApi;
        $this->comprasnetApi = $comprasnetApi;
    }
    /**
     * Exibir formulário de criação de novo orçamento
     */
    public function create()
    {
        // FILTRO MANUAL POR TENANT_ID

        // Buscar todos os orçamentos realizados DO TENANT para opção "criar a partir de outro"
        $orcamentosRealizados = Orcamento::realizados()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orcamentos.create', compact('orcamentosRealizados'));
    }

    /**
     * Salvar novo orçamento
     *
     * ⛔⛔⛔ CÓDIGO CRÍTICO - NÃO MEXER SEM AUTORIZAÇÃO ⛔⛔⛔
     * Este método já foi corrigido 4x devido a alterações acidentais.
     * Leia Arquivos_Claude/CODIGO_CRITICO_NAO_MEXER.md antes de modificar.
     * Qualquer mudança aqui pode quebrar o fluxo de criação/redirecionamento.
     */
    public function store(Request $request)
    {
        Log::info('[DIAGNÓSTICO] Início do método store()', [
            'user_id' => Auth::id(),
            'tipo_criacao' => $request->input('tipo_criacao'),
            'has_file' => $request->hasFile('documento'),
            'all_inputs' => $request->except(['documento', '_token'])
        ]);

        // Validação dos campos básicos
        $rules = [
            'nome' => 'required|string|max:255',
            'referencia_externa' => 'nullable|string|max:255',
            'objeto' => 'required|string',
            'orgao_interessado' => 'nullable|string|max:255',
            'tipo_criacao' => 'required|in:do_zero,outro_orcamento,documento',
            'orcamento_origem_id' => 'nullable|exists:cp_orcamentos,id',
        ];

        // Validação adicional para tipo "documento"
        if ($request->tipo_criacao === 'documento') {
            $rules['documento'] = 'required|file|mimes:pdf,xlsx,xls|max:10240';
        }

        try {
            $validated = $request->validate($rules, [
                'nome.required' => 'O campo Nome do Orçamento é obrigatório.',
                'nome.max' => 'O Nome do Orçamento não pode ter mais de 255 caracteres.',
                'objeto.required' => 'O campo Objeto é obrigatório.',
                'tipo_criacao.required' => 'Selecione como deseja criar o orçamento.',
                'tipo_criacao.in' => 'Tipo de criação inválido.',
                'orcamento_origem_id.exists' => 'Orçamento de origem não encontrado.',
                'documento.required' => 'O upload do documento é obrigatório.',
                'documento.mimes' => 'O documento deve ser do tipo PDF ou Excel (.pdf, .xlsx, .xls).',
                'documento.max' => 'O documento não pode ter mais de 10MB.',
            ]);

            Log::info('[DIAGNÓSTICO] Validação passou', [
                'validated_keys' => array_keys($validated)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[DIAGNÓSTICO] Validação falhou', [
                'errors' => $e->errors()
            ]);
            throw $e;
        }

        DB::beginTransaction();

        try {
            // Criar orçamento
            $orcamento = Orcamento::create([
                'nome' => $validated['nome'],
                'referencia_externa' => $validated['referencia_externa'] ?? null,
                'objeto' => $validated['objeto'],
                'orgao_interessado' => $validated['orgao_interessado'] ?? null,
                'tipo_criacao' => $validated['tipo_criacao'],
                'orcamento_origem_id' => $validated['orcamento_origem_id'] ?? null,
                'status' => 'pendente',
                'user_id' => Auth::id(),
            ]);

            Log::info('[DIAGNÓSTICO] Orçamento criado', [
                'orcamento_id' => $orcamento->id,
                'nome' => $orcamento->nome
            ]);

            // Se tipo é "documento", processar o arquivo
            if ($validated['tipo_criacao'] === 'documento' && $request->hasFile('documento')) {
                Log::info('[DIAGNÓSTICO] Iniciando processamento de documento');

                $itensExtraidos = $this->processarDocumento($request->file('documento'));

                Log::info('[DIAGNÓSTICO] Documento processado', [
                    'total_itens' => count($itensExtraidos)
                ]);

                // Criar itens extraídos do documento
                foreach ($itensExtraidos as $index => $itemData) {
                    $item = OrcamentoItem::create([
                        'orcamento_id' => $orcamento->id,
                        'descricao' => $itemData['descricao'],
                        'medida_fornecimento' => $itemData['unidade'] ?? 'UNIDADE',
                        'quantidade' => $itemData['quantidade'] ?? 1,
                        'preco_unitario' => $itemData['preco_unitario'] ?? null,
                        'tipo' => 'produto',
                        'alterar_cdf' => false,
                    ]);

                    Log::info('[DIAGNÓSTICO] Item criado', [
                        'item_id' => $item->id,
                        'index' => $index + 1,
                        'descricao' => substr($itemData['descricao'], 0, 50),
                        'preco_unitario' => $itemData['preco_unitario'] ?? 'não informado'
                    ]);
                }

                Log::info('[DIAGNÓSTICO] Todos os itens criados', [
                    'orcamento_id' => $orcamento->id,
                    'itens_extraidos' => count($itensExtraidos)
                ]);
            }

            // GAP #5: Se tipo é "outro_orcamento", copiar itens do orçamento origem
            if ($validated['tipo_criacao'] === 'outro_orcamento' && !empty($validated['orcamento_origem_id'])) {
                Log::info('[GAP #5] Iniciando cópia de itens do orçamento origem', [
                    'orcamento_origem_id' => $validated['orcamento_origem_id']
                ]);

                $orcamentoOrigem = Orcamento::with('itens')->findOrFail($validated['orcamento_origem_id']);

                $itensCopiadosCount = 0;
                foreach ($orcamentoOrigem->itens as $itemOrigem) {
                    OrcamentoItem::create([
                        'orcamento_id' => $orcamento->id,
                        'lote_id' => null, // Não copiar lote (pode não existir no novo orçamento)
                        'descricao' => $itemOrigem->descricao,
                        'medida_fornecimento' => $itemOrigem->medida_fornecimento,
                        'quantidade' => $itemOrigem->quantidade,
                        'indicacao_marca' => $itemOrigem->indicacao_marca,
                        'tipo' => $itemOrigem->tipo,
                        'alterar_cdf' => $itemOrigem->alterar_cdf,
                        // CAMPOS CRÍTICOS QUE ESTAVAM FALTANDO:
                        'preco_unitario' => $itemOrigem->preco_unitario,
                        'fonte_preco' => $itemOrigem->fonte_preco,
                        'fonte_url' => $itemOrigem->fonte_url,
                        'fonte_detalhes' => $itemOrigem->fonte_detalhes,
                        'amostras_selecionadas' => $itemOrigem->amostras_selecionadas,
                        'justificativa_cotacao' => $itemOrigem->justificativa_cotacao,
                        'fornecedor_nome' => $itemOrigem->fornecedor_nome,
                        'fornecedor_cnpj' => $itemOrigem->fornecedor_cnpj,
                        'numero_item' => $itemOrigem->numero_item,
                        // Campos de cálculos estatísticos
                        'calc_n_validas' => $itemOrigem->calc_n_validas,
                        'calc_media' => $itemOrigem->calc_media,
                        'calc_mediana' => $itemOrigem->calc_mediana,
                        'calc_dp' => $itemOrigem->calc_dp,
                        'calc_cv' => $itemOrigem->calc_cv,
                        'calc_menor' => $itemOrigem->calc_menor,
                        'calc_maior' => $itemOrigem->calc_maior,
                        'calc_lim_inf' => $itemOrigem->calc_lim_inf,
                        'calc_lim_sup' => $itemOrigem->calc_lim_sup,
                        'calc_metodo' => $itemOrigem->calc_metodo,
                        'calc_carimbado_em' => $itemOrigem->calc_carimbado_em,
                        'calc_hash_amostras' => $itemOrigem->calc_hash_amostras,
                        // Campos ABC
                        'abc_valor_total' => $itemOrigem->abc_valor_total,
                        'abc_participacao' => $itemOrigem->abc_participacao,
                        'abc_acumulada' => $itemOrigem->abc_acumulada,
                        'abc_classe' => $itemOrigem->abc_classe,
                        // Outros campos
                        'criticas_dados' => $itemOrigem->criticas_dados,
                        'importado_de_planilha' => $itemOrigem->importado_de_planilha,
                        'nome_arquivo_planilha' => $itemOrigem->nome_arquivo_planilha,
                        'data_importacao' => $itemOrigem->data_importacao,
                    ]);
                    $itensCopiadosCount++;
                }

                Log::info('[GAP #5] Itens copiados com sucesso', [
                    'orcamento_destino_id' => $orcamento->id,
                    'itens_copiados' => $itensCopiadosCount
                ]);
            }

            DB::commit();
            Log::info('[DIAGNÓSTICO] Transaction committed');

            $mensagem = 'Orçamento criado com sucesso!' .
                ($validated['tipo_criacao'] === 'documento' ? ' ' . count($itensExtraidos ?? []) . ' itens foram extraídos do documento.' : '');

            $routeUrl = route('orcamentos.elaborar', ['id' => $orcamento->id, 'msg' => 'success']);
            Log::info('[DIAGNÓSTICO] Preparando redirect', [
                'orcamento_id' => $orcamento->id,
                'route_name' => 'orcamentos.elaborar',
                'route_url' => $routeUrl,
                'mensagem' => $mensagem
            ]);

            // Redirecionar para página de elaboração COM mensagem na sessão E na URL
            // ⛔⛔⛔ CÓDIGO CRÍTICO - NÃO ALTERAR LÓGICA DE REDIRECT ⛔⛔⛔
            // SOLUÇÃO DEFINITIVA: Usar JavaScript para forçar navegação no iframe
            // Isso garante que funciona independente de como o proxy lida com redirects
            // HISTÓRICO: Foi corrigido múltiplas vezes (redirect HTTP 302 não funciona em iframe)
            // NUNCA volte a usar redirect()->route() aqui!
            $urlElaborar = route('orcamentos.elaborar', ['id' => $orcamento->id, 'msg' => 'success']);

            // Transformar URL absoluta em relativa (sem domínio E sem barra inicial)
            $urlRelativa = parse_url($urlElaborar, PHP_URL_PATH);
            if ($query = parse_url($urlElaborar, PHP_URL_QUERY)) {
                $urlRelativa .= '?' . $query;
            }
            // CRÍTICO: Remover barra inicial para funcionar com tag <base>
            $urlRelativa = ltrim($urlRelativa, '/');

            Log::info('[DIAGNÓSTICO] Criando resposta HTML com redirect JavaScript', [
                'orcamento_id' => $orcamento->id,
                'url_destino' => $urlRelativa,
                'mensagem' => $mensagem
            ]);

            // Salvar mensagem na sessão para exibir no modal
            session()->flash('success', $mensagem);
            session()->save(); // Forçar save imediato

            // Retornar HTML que redireciona via JavaScript (funciona em iframe)
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecionando...</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .loading {
            text-align: center;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <p style="color: #6b7280; font-size: 15px;">Redirecionando para a elaboração...</p>
    </div>
    <script>
        // Redirecionar usando JavaScript (funciona perfeitamente em iframes)
        window.location.href = "' . $urlRelativa . '";
    </script>
</body>
</html>';

            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DIAGNÓSTICO] Exceção capturada no try-catch', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Erro ao criar orçamento: ' . $e->getMessage()]);
        }
    }

    /**
     * Listar orçamentos pendentes
     */
    public function pendentes()
    {
        // FILTRO MANUAL POR TENANT_ID

        $orcamentos = Orcamento::pendentes()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('orcamentos.pendentes', compact('orcamentos'));
    }

    /**
     * Listar orçamentos realizados
     */
    public function realizados()
    {
        // FILTRO MANUAL POR TENANT_ID

        $orcamentos = Orcamento::realizados()
            ->with('user')
            ->orderBy('data_conclusao', 'desc')
            ->get();

        // Buscar também cotações externas concluídas DO TENANT
        $cotoesExternas = \App\Models\CotacaoExterna::where('status', 'concluido')
            ->orderBy('data_conclusao', 'desc')
            ->get();

        // Mesclar orçamentos e cotações externas
        $todosOrcamentos = $orcamentos->concat($cotoesExternas)
            ->sortByDesc('data_conclusao')
            ->values();

        // Paginar manualmente
        $page = request()->get('page', 1);
        $perPage = 15;
        $total = $todosOrcamentos->count();
        $items = $todosOrcamentos->slice(($page - 1) * $perPage, $perPage)->values();

        $orcamentosPaginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('orcamentos.realizados', ['orcamentos' => $orcamentosPaginados]);
    }

    /**
     * Exibir detalhes de um orçamento
     */
    public function show($id)
    {
        $orcamento = Orcamento::with([
            'user',
            'orcamentoOrigem',
            'orcamentosDerivados',
            'itens',
            'lotes'
        ])->findOrFail($id);

        // Se for requisição AJAX, retornar JSON
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($orcamento);
        }

        return view('orcamentos.show', compact('orcamento'));
    }

    /**
     * Exibir formulário de edição
     */
    public function edit($id)
    {
        $orcamento = Orcamento::findOrFail($id);

        // Buscar todos os orçamentos realizados para opção "criar a partir de outro"
        $orcamentosRealizados = Orcamento::realizados()
            ->where('id', '!=', $id) // Excluir o próprio orçamento
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orcamentos.edit', compact('orcamento', 'orcamentosRealizados'));
    }

    /**
     * Atualizar orçamento
     */
    public function update(Request $request, $id)
    {
        $orcamento = Orcamento::findOrFail($id);

        // Validação dos campos
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'referencia_externa' => 'nullable|string|max:255',
            'objeto' => 'required|string',
            'orgao_interessado' => 'nullable|string|max:255',
        ], [
            'nome.required' => 'O campo Nome do Orçamento é obrigatório.',
            'nome.max' => 'O Nome do Orçamento não pode ter mais de 255 caracteres.',
            'objeto.required' => 'O campo Objeto é obrigatório.',
        ]);

        DB::beginTransaction();

        try {
            $orcamento->update([
                'nome' => $validated['nome'],
                'referencia_externa' => $validated['referencia_externa'],
                'objeto' => $validated['objeto'],
                'orgao_interessado' => $validated['orgao_interessado'],
            ]);

            DB::commit();

            // Retornar JSON se for requisição AJAX
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orçamento atualizado com sucesso!',
                    'data' => $orcamento
                ]);
            }

            return redirect()
                ->route('orcamentos.show', $orcamento->id)
                ->with('success', 'Orçamento atualizado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Retornar JSON se for requisição AJAX
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar orçamento: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Erro ao atualizar orçamento: ' . $e->getMessage()]);
        }
    }

    /**
     * Marcar orçamento como realizado
     */
    public function marcarRealizado($id)
    {
        $orcamento = Orcamento::findOrFail($id);

        DB::beginTransaction();

        try {
            $orcamento->marcarComoRealizado();

            DB::commit();

            return redirect()
                ->route('orcamentos.realizados')
                ->with('success', 'Orçamento marcado como realizado!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao marcar orçamento como realizado: ' . $e->getMessage()]);
        }
    }

    /**
     * Marcar orçamento como pendente
     */
    public function marcarPendente($id)
    {
        $orcamento = Orcamento::findOrFail($id);

        DB::beginTransaction();

        try {
            $orcamento->marcarComoPendente();

            DB::commit();

            return redirect()
                ->route('orcamentos.pendentes')
                ->with('success', 'Orçamento marcado como pendente!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao marcar orçamento como pendente: ' . $e->getMessage()]);
        }
    }

    /**
     * Excluir orçamento (soft delete)
     */
    public function destroy($id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);

            Log::info('Excluindo orçamento', [
                'orcamento_id' => $id,
                'nome' => $orcamento->nome,
                'user_id' => Auth::id()
            ]);

            DB::beginTransaction();

            // Deletar todos os itens relacionados
            $orcamento->itens()->delete();

            // Deletar o orçamento
            $orcamento->delete();

            DB::commit();

            Log::info('Orçamento excluído com sucesso', ['orcamento_id' => $id]);

            return redirect()
                ->route('orcamentos.pendentes')
                ->with('success', 'Orçamento excluído com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao excluir orçamento', [
                'orcamento_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao excluir orçamento: ' . $e->getMessage()]);
        }
    }

    /**
     * Buscar orçamentos via AJAX (para filtro na Aba 2)
     */
    public function buscar(Request $request)
    {
        // BANCO EXCLUSIVO POR TENANT - Não precisa filtrar por tenant_id
        $query = Orcamento::query()->with(['user', 'itens']);

        // Filtro por nome
        if ($request->filled('nome')) {
            $query->where('nome', 'ILIKE', '%' . $request->nome . '%');
        }

        // Filtro por referência externa
        if ($request->filled('referencia_externa')) {
            $query->where('referencia_externa', 'ILIKE', '%' . $request->referencia_externa . '%');
        }

        // Buscar com paginação
        $orcamentos = $query->orderBy('created_at', 'desc')->paginate(10);

        // Adicionar contagem de itens para cada orçamento
        $orcamentos->getCollection()->transform(function ($orcamento) {
            $orcamento->total_itens = $orcamento->itens->count();
            return $orcamento;
        });

        return response()->json([
            'success' => true,
            'data' => $orcamentos->items(),
            'pagination' => [
                'current_page' => $orcamentos->currentPage(),
                'last_page' => $orcamentos->lastPage(),
                'per_page' => $orcamentos->perPage(),
                'total' => $orcamentos->total(),
                'from' => $orcamentos->firstItem(),
                'to' => $orcamentos->lastItem(),
            ]
        ]);
    }

    /**
     * Página de elaboração do orçamento
     */
    public function elaborar(Request $request, $id)
    {
        // FORÇA CACHE BUSTING: Redirecionar para URL com timestamp se não tiver parâmetro _v
        if (!$request->has('_v')) {
            $timestamp = now()->timestamp;
            return redirect()->route('orcamentos.elaborar', ['id' => $id, '_v' => $timestamp]);
        }

        $orcamento = Orcamento::with(['user', 'itens', 'solicitacoesCDF', 'contratacoesSimilares', 'coletasEcommerce'])->findOrFail($id);

        // FORÇA NO-CACHE: Headers HTTP para prevenir cache de navegador
        return response()
            ->view('orcamentos.elaborar', compact('orcamento'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Concluir cotação - salvar dados e marcar como realizado
     */
    public function concluir(Request $request, $id)
    {
        Log::info('🎯 [CONCLUIR] Iniciando conclusão de orçamento', [
            'orcamento_id' => $id,
            'user_id' => Auth::id(),
            'has_file' => $request->hasFile('anexo_pdf'),
            'has_observacao' => $request->has('observacao_justificativa')
        ]);

        try {
            $orcamento = Orcamento::findOrFail($id);

            Log::info('✅ [CONCLUIR] Orçamento encontrado', [
                'numero' => $orcamento->numero,
                'status_atual' => $orcamento->status
            ]);

            // Validação
            // ✅ ACEITA: TODOS OS TIPOS DE ARQUIVO
            // ✅ Limite: 20MB
            $validated = $request->validate([
                'observacao_justificativa' => 'nullable|string',
                'anexo_pdf' => 'nullable|file|max:20480', // 20MB (20 * 1024 KB)
            ], [
                'anexo_pdf.max' => 'O arquivo não pode ter mais de 20MB',
            ]);

            Log::info('✅ [CONCLUIR] Validação aprovada');

            DB::beginTransaction();

            try {
                // Salvar anexo (PDF ou Imagem) antes de marcar como realizado
                if ($request->hasFile('anexo_pdf')) {
                    Log::info('📎 [CONCLUIR] Processando upload de anexo');
                    $file = $request->file('anexo_pdf');
                    $extensao = $file->getClientOriginalExtension(); // Pega extensão real (pdf, png, jpg)
                    $filename = 'orcamento_' . $orcamento->id . '_' . time() . '.' . $extensao;
                    $path = $file->storeAs('orcamentos/anexos', $filename, 'public');
                    $orcamento->anexo_pdf = $path;
                    Log::info('✅ [CONCLUIR] Anexo salvo', ['path' => $path, 'tipo' => $extensao]);
                }

                // Atualizar observação e status em uma única operação
                Log::info('💾 [CONCLUIR] Atualizando orçamento', [
                    'status_antes' => $orcamento->status,
                    'has_observacao' => !empty($validated['observacao_justificativa']),
                    'has_pdf' => !empty($orcamento->anexo_pdf)
                ]);

                $orcamento->update([
                    'observacao_justificativa' => $validated['observacao_justificativa'] ?? null,
                    'anexo_pdf' => $orcamento->anexo_pdf,
                    'status' => 'realizado',
                    'data_conclusao' => now(),
                ]);

                // Recarregar para confirmar
                $orcamento->refresh();

                Log::info('✅ [CONCLUIR] Status após update', [
                    'status_depois' => $orcamento->status,
                    'data_conclusao' => $orcamento->data_conclusao?->format('Y-m-d H:i:s')
                ]);

                DB::commit();

                Log::info('🎉 [CONCLUIR] Orçamento concluído com sucesso!', [
                    'orcamento_id' => $id,
                    'numero' => $orcamento->numero
                ]);

                // Se for requisição AJAX, retornar JSON
                if ($request->expectsJson() || $request->ajax()) {
                    // Usar scheme e host da requisição atual para construir URL correta
                    $scheme = $request->getScheme(); // http ou https
                    $host = $request->getHost(); // catasaltas.dattapro.online
                    $basePath = '';

                    // Se estiver rodando via proxy, incluir o prefixo
                    if (strpos($request->getRequestUri(), '/module-proxy/price_basket') !== false) {
                        $basePath = '/module-proxy/price_basket';
                    }

                    $redirectUrl = $scheme . '://' . $host . $basePath . '/orcamentos/realizados';

                    Log::info('🔗 [CONCLUIR] URL de redirect gerada', [
                        'url' => $redirectUrl,
                        'scheme' => $scheme,
                        'host' => $host,
                        'basePath' => $basePath
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Orçamento concluído com sucesso!',
                        'redirect' => $redirectUrl
                    ]);
                }

                // Caso contrário, fazer redirect normal
                return redirect()
                    ->route('orcamentos.realizados')
                    ->with('success', 'Orçamento concluído com sucesso!');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('❌ [CONCLUIR] Erro na transação', [
                    'erro' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // ✅ CORRIGIDO: Retornar JSON para requisições AJAX
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao concluir orçamento: ' . $e->getMessage()
                    ], 500);
                }

                return redirect()
                    ->back()
                    ->withErrors(['error' => 'Erro ao concluir orçamento: ' . $e->getMessage()]);
            }

        } catch (\Exception $e) {
            Log::error('❌ [CONCLUIR] Erro crítico ao concluir orçamento', [
                'orcamento_id' => $id,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // ✅ CORRIGIDO: Retornar JSON para requisições AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao concluir orçamento: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao concluir orçamento: ' . $e->getMessage()]);
        }
    }

    /**
     * Gerar preview do orçamento em PDF PROFISSIONAL usando mPDF
     */
    public function preview($id)
    {
        try {
            // Carregar orçamento com TODOS os relacionamentos necessários (igual ao imprimir)
            $orcamento = Orcamento::with([
                'itens.lote',
                'user',
                'orgao',
                'solicitacoesCDF',
                'coletasEcommerce.itens',
                'contratacoesSimilares.itens'
            ])->findOrFail($id);

            // Buscar dados do órgão
            // PRIORIDADE 1: Dados do cadastro de órgão vinculado ao orçamento (orgao_id)
            // PRIORIDADE 2: Primeiro órgão cadastrado (guia Configurações)
            // PRIORIDADE 3: Dados do orçamentista (inseridos manualmente no orçamento)
            if ($orcamento->orgao_id && $orcamento->orgao) {
                $orgao = $orcamento->orgao;
            } else {
                // Buscar órgão padrão das configurações
                $orgao = Orgao::first();

                // Se não houver órgão cadastrado, usar dados do orçamentista como fallback
                if (!$orgao) {
                    $orgao = (object) [
                        'nome' => $orcamento->orcamentista_razao_social ?? $orcamento->orcamentista_nome ?? 'N/A',
                        'razao_social' => $orcamento->orcamentista_razao_social ?? null,
                        'nome_fantasia' => $orcamento->orcamentista_nome ?? null,
                        'cnpj' => $orcamento->orcamentista_cpf_cnpj ?? null,
                        'usuario' => $orcamento->orcamentista_nome ?? 'N/A',
                        'endereco' => $this->formatarEnderecoOrcamentista($orcamento),
                        'numero' => null,
                        'complemento' => null,
                        'bairro' => null,
                        'cep' => $orcamento->orcamentista_cep ?? null,
                        'cidade' => $orcamento->orcamentista_cidade ?? null,
                        'uf' => $orcamento->orcamentista_uf ?? null,
                        'telefone' => null,
                        'email' => null,
                        'brasao_path' => $orcamento->brasao_path ?? null,
                        'responsavel_nome' => $orcamento->orcamentista_nome ?? null,
                        'responsavel_matricula_siape' => null,
                        'responsavel_cargo' => $orcamento->orcamentista_setor ?? null,
                        'responsavel_portaria' => $orcamento->orcamentista_portaria ?? null,
                    ];
                }
            }

            // Processar cada item para incluir análise estatística
            foreach ($orcamento->itens as $item) {
                $amostras = $item->amostras_selecionadas
                    ? json_decode($item->amostras_selecionadas, true)
                    : [];

                if (count($amostras) > 0) {
                    $item->estatisticas = $this->calcularEstatisticas($amostras);
                    $item->amostras_processadas = $amostras;
                } else {
                    $item->estatisticas = null;
                    $item->amostras_processadas = [];
                }
            }

            // Calcular Curva ABC
            $curvaABC = $this->calcularCurvaABC($orcamento->itens);

            // Buscar solicitações CDF
            $solicitacoesCDF = $orcamento->solicitacoesCDF;

            // Coletar TODOS os anexos de TODOS os modais
            $anexos = $this->coletarTodosAnexos($orcamento, $solicitacoesCDF);

            // Renderizar HTML da view (usando novo layout do completinho.pdf)
            $html = view('orcamentos.pdf', [
                'orcamento' => $orcamento,
                'orgao' => $orgao,
                'curvaABC' => $curvaABC,
                'solicitacoesCDF' => $solicitacoesCDF,
                'anexos' => $anexos,
                'disableQRCodes' => true // Desabilitar QR codes no preview para evitar erro curl
            ])->render();

            // Criar diretório temp se não existir
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // Aumentar limite do PCRE para HTML grandes (até 10MB)
            ini_set('pcre.backtrack_limit', '10000000');
            ini_set('pcre.recursion_limit', '10000000');
            ini_set('memory_limit', '512M'); // Aumentar memória para PDFs grandes

            // Configurar mPDF com suporte UTF-8 completo
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_header' => 10,
                'margin_footer' => 10,
                'default_font' => 'DejaVuSans',
                'tempDir' => $tempDir, // Usar diretório Laravel com permissões
                'curlAllowUnsafeSslRequests' => true, // Permitir requisições curl para QR codes
            ]);

            // Configurações adicionais para qualidade
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->SetAuthor('Cesta de Preços');
            $mpdf->SetTitle('Orçamento ' . $orcamento->numero);

            // Escrever HTML no PDF (sem dividir em chunks para evitar quebra de tags)
            $mpdf->WriteHTML($html);

            // Retornar PDF inline (abre no navegador)
            $nomeArquivo = 'orcamento_' . str_replace(['/', '\\'], '-', $orcamento->numero) . '.pdf';
            return response()->stream(
                function () use ($mpdf) {
                    echo $mpdf->Output('', 'S');
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $nomeArquivo . '"',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Erro ao gerar preview PDF', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Imprimir orçamento em PDF PROFISSIONAL usando mPDF (botão IMPRIMIR)
     */
    public function imprimir($id)
    {
        try {
            Log::info('📄 Imprimindo PDF do orçamento', ['orcamento_id' => $id]);

            // ========================================
            // CACHE DE PDF - Performance Optimization
            // ========================================
            $orcamento = Orcamento::findOrFail($id);
            $cacheKey = "pdf_orcamento_{$id}_" . $orcamento->updated_at->timestamp;
            $cachePath = storage_path("app/cache/pdfs/orcamento_{$id}.pdf");
            $cacheDir = dirname($cachePath);

            // Criar diretório de cache se não existir
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            Log::info('🔍 Verificando cache de PDF', [
                'path' => $cachePath,
                'exists' => file_exists($cachePath)
            ]);

            // Verificar se PDF em cache existe e está atualizado
            if (file_exists($cachePath)) {
                $cacheTimestamp = filemtime($cachePath);
                $orcamentoTimestamp = $orcamento->updated_at->timestamp;

                // Se cache é mais novo que última atualização do orçamento, usar cache
                if ($cacheTimestamp >= $orcamentoTimestamp) {
                    Log::info('✅ SERVINDO PDF DO CACHE (RÁPIDO!)', [
                        'orcamento_id' => $id,
                        'cache_age_seconds' => time() - $cacheTimestamp
                    ]);

                    $nomeArquivo = 'orcamento_' . str_replace(['/', '\\'], '-', $orcamento->numero) . '.pdf';

                    return response()->file($cachePath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $nomeArquivo . '"'
                    ]);
                }
            }

            Log::info('⚙️ GERANDO PDF NOVO (SEM CACHE)', ['orcamento_id' => $id]);

            // Carregar orçamento com TODOS os relacionamentos necessários (igual ao preview)
            $orcamento = Orcamento::with([
                'itens.lote',
                'user',
                'orgao',
                'solicitacoesCDF',
                'coletasEcommerce.itens',
                'contratacoesSimilares.itens'
            ])->findOrFail($id);

            // Buscar dados do órgão
            // PRIORIDADE 1: Dados do cadastro de órgão vinculado ao orçamento (orgao_id)
            // PRIORIDADE 2: Primeiro órgão cadastrado (guia Configurações)
            // PRIORIDADE 3: Dados do orçamentista (inseridos manualmente no orçamento)
            if ($orcamento->orgao_id && $orcamento->orgao) {
                $orgao = $orcamento->orgao;
            } else {
                // Buscar órgão padrão das configurações
                $orgao = Orgao::first();

                // Se não houver órgão cadastrado, usar dados do orçamentista como fallback
                if (!$orgao) {
                    $orgao = (object) [
                        'nome' => $orcamento->orcamentista_razao_social ?? $orcamento->orcamentista_nome ?? 'N/A',
                        'razao_social' => $orcamento->orcamentista_razao_social ?? null,
                        'nome_fantasia' => $orcamento->orcamentista_nome ?? null,
                        'cnpj' => $orcamento->orcamentista_cpf_cnpj ?? null,
                        'usuario' => $orcamento->orcamentista_nome ?? 'N/A',
                        'endereco' => $this->formatarEnderecoOrcamentista($orcamento),
                        'numero' => null,
                        'complemento' => null,
                        'bairro' => null,
                        'cep' => $orcamento->orcamentista_cep ?? null,
                        'cidade' => $orcamento->orcamentista_cidade ?? null,
                        'uf' => $orcamento->orcamentista_uf ?? null,
                        'telefone' => null,
                        'email' => null,
                        'brasao_path' => $orcamento->brasao_path ?? null,
                        'responsavel_nome' => $orcamento->orcamentista_nome ?? null,
                        'responsavel_matricula_siape' => null,
                        'responsavel_cargo' => $orcamento->orcamentista_setor ?? null,
                        'responsavel_portaria' => $orcamento->orcamentista_portaria ?? null,
                    ];
                }
            }

            // Processar cada item para incluir análise estatística
            foreach ($orcamento->itens as $item) {
                $amostras = $item->amostras_selecionadas
                    ? json_decode($item->amostras_selecionadas, true)
                    : [];

                if (count($amostras) > 0) {
                    $item->estatisticas = $this->calcularEstatisticas($amostras);
                    $item->amostras_processadas = $amostras;
                } else {
                    $item->estatisticas = null;
                    $item->amostras_processadas = [];
                }
            }

            // Calcular Curva ABC
            $curvaABC = $this->calcularCurvaABC($orcamento->itens);

            // Buscar solicitações CDF
            $solicitacoesCDF = $orcamento->solicitacoesCDF;

            // Coletar TODOS os anexos de TODOS os modais
            $anexos = $this->coletarTodosAnexos($orcamento, $solicitacoesCDF);

            // Renderizar HTML da view (usando novo layout do completinho.pdf)
            $html = view('orcamentos.pdf', [
                'orcamento' => $orcamento,
                'orgao' => $orgao,
                'curvaABC' => $curvaABC,
                'solicitacoesCDF' => $solicitacoesCDF,
                'anexos' => $anexos,
                'disableQRCodes' => false // Habilitar QR codes na impressão final
            ])->render();

            // Criar diretório temp se não existir
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // Aumentar limite do PCRE para HTML grandes (até 10MB)
            ini_set('pcre.backtrack_limit', '10000000');
            ini_set('pcre.recursion_limit', '10000000');
            ini_set('memory_limit', '512M'); // Aumentar memória para PDFs grandes

            // Configurar mPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_header' => 10,
                'margin_footer' => 10,
                'default_font' => 'DejaVuSans',
                'tempDir' => $tempDir,
                'curlAllowUnsafeSslRequests' => true, // Permitir requisições curl para QR codes
            ]);

            $mpdf->SetDisplayMode('fullpage');
            $mpdf->SetAuthor('Cesta de Preços');
            $mpdf->SetTitle('Orçamento ' . $orcamento->numero);

            // Escrever HTML no PDF (sem dividir em chunks para evitar quebra de tags)
            $mpdf->WriteHTML($html);

            // Salvar PDF no CACHE para próximas requisições
            $pdfContent = $mpdf->Output('', 'S');
            file_put_contents($cachePath, $pdfContent);

            Log::info('💾 PDF salvo no cache', [
                'orcamento_id' => $id,
                'path' => $cachePath,
                'size_bytes' => strlen($pdfContent)
            ]);

            // Retornar PDF para DOWNLOAD (não inline)
            $nomeArquivo = 'orcamento_' . str_replace(['/', '\\'], '-', $orcamento->numero) . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nomeArquivo . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao imprimir PDF', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MÉTODO ANTIGO (não usado mais, manter por compatibilidade)
     */
    private function imprimirAntigo($id)
    {
        // Carregar orçamento
        $orcamento = Orcamento::with([
            'user',
            'itens',
            'lotes',
            'coletasEcommerce.itens',
            'solicitacoesCDF',
            'contratacoesSimilares.itens'
        ])->findOrFail($id);

        // Gerar PDF usando DomPDF (ANTIGO)
        $pdf = \PDF::loadView('orcamentos.preview', compact('orcamento'));

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        // Retornar PDF inline (abre no navegador, usuário pode baixar se quiser)
        // Limpar nome do arquivo (substituir / e \ por -)
        $nomeArquivo = 'orcamento_' . str_replace(['/', '\\'], '-', $orcamento->numero) . '.pdf';
        return $pdf->stream($nomeArquivo);
    }

    /**
     * Exportar orçamento para Excel (usado no botão EXPORTAR EXCEL da página Realizados)
     */
    public function exportarExcel($id)
    {
        // Carregar orçamento com itens e órgão
        $orcamento = Orcamento::with(['user', 'itens', 'orgao'])->findOrFail($id);

        // ========== BUSCAR DADOS DO ÓRGÃO (IGUAL AO PDF) ==========
        // PRIORIDADE 1: Dados do cadastro de órgão vinculado ao orçamento (orgao_id)
        // PRIORIDADE 2: Primeiro órgão cadastrado (guia Configurações)
        // PRIORIDADE 3: Dados do orçamentista (inseridos manualmente no orçamento)
        if ($orcamento->orgao_id && $orcamento->orgao) {
            $orgao = $orcamento->orgao;
        } else {
            // Buscar órgão padrão das configurações
            $orgao = Orgao::first();

            // Se não houver órgão cadastrado, usar dados do orçamentista como fallback
            if (!$orgao) {
                $orgao = (object) [
                    'nome' => $orcamento->orcamentista_razao_social ?? $orcamento->orcamentista_nome ?? 'ÓRGÃO NÃO INFORMADO',
                    'razao_social' => $orcamento->orcamentista_razao_social ?? null,
                ];
            }
        }

        // Criar novo spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar larguras de colunas
        $sheet->getColumnDimension('A')->setWidth(12);  // LOTE/ITEM
        $sheet->getColumnDimension('B')->setWidth(50);  // DESCRIÇÃO
        $sheet->getColumnDimension('C')->setWidth(20);  // UNIDADE
        $sheet->getColumnDimension('D')->setWidth(15);  // QUANTIDADE
        $sheet->getColumnDimension('E')->setWidth(18);  // PREÇO UNIT.
        $sheet->getColumnDimension('F')->setWidth(18);  // PREÇO TOTAL
        // NOVAS COLUNAS ESTATÍSTICAS
        $sheet->getColumnDimension('G')->setWidth(15);  // N° AMOSTRAS
        $sheet->getColumnDimension('H')->setWidth(18);  // DESVIO PADRÃO
        $sheet->getColumnDimension('I')->setWidth(15);  // CV (%)
        $sheet->getColumnDimension('J')->setWidth(18);  // MENOR PREÇO
        $sheet->getColumnDimension('K')->setWidth(18);  // MÉDIA
        $sheet->getColumnDimension('L')->setWidth(18);  // MEDIANA
        $sheet->getColumnDimension('M')->setWidth(20);  // MÉTODO ADOTADO

        // ====== CABEÇALHO DO DOCUMENTO ======
        $row = 1;

        // Órgão (prioriza razao_social, depois nome, senão fallback)
        $nomeOrgao = $orgao->razao_social ?? $orgao->nome ?? 'ÓRGÃO NÃO INFORMADO';
        $sheet->setCellValue('A' . $row, strtoupper($nomeOrgao));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;

        // Setor
        if ($orcamento->orcamentista_setor) {
            $sheet->setCellValue('A' . $row, strtoupper($orcamento->orcamentista_setor));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
            $sheet->mergeCells('A' . $row . ':M' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Endereço
        if ($orcamento->orcamentista_endereco) {
            $endereco = strtoupper($orcamento->orcamentista_endereco) . ' - CEP: ' . ($orcamento->orcamentista_cep ?? '00.000-000') . ' - ' . strtoupper($orcamento->orcamentista_cidade ?? 'CIDADE') . '/' . strtoupper($orcamento->orcamentista_uf ?? 'UF');
            $sheet->setCellValue('A' . $row, $endereco);
            $sheet->getStyle('A' . $row)->getFont()->setSize(9);
            $sheet->mergeCells('A' . $row . ':M' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $row++; // Linha em branco

        // Título
        $sheet->setCellValue('A' . $row, 'ORÇAMENTO ESTIMATIVO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $row++;

        $row++; // Linha em branco

        // ====== INFORMAÇÕES DO ORÇAMENTO ======
        $sheet->setCellValue('A' . $row, 'NOME:');
        $sheet->setCellValue('B' . $row, $orcamento->nome);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('B' . $row . ':M' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, 'NÚMERO:');
        $sheet->setCellValue('B' . $row, $orcamento->numero);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('B' . $row . ':M' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, 'OBJETO:');
        $sheet->setCellValue('B' . $row, $orcamento->objeto);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('B' . $row . ':M' . $row);
        $row++;

        if ($orcamento->orgao_interessado) {
            $sheet->setCellValue('A' . $row, 'UNID. INTERESSADA:');
            $sheet->setCellValue('B' . $row, $orcamento->orgao_interessado);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':M' . $row);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'ORÇAMENTISTA:');
        $sheet->setCellValue('B' . $row, strtoupper($orcamento->orcamentista_nome ?? $orcamento->user->name ?? 'NÃO INFORMADO'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('B' . $row . ':M' . $row);
        $row++;

        if ($orcamento->referencia_externa) {
            $sheet->setCellValue('A' . $row, 'REFERÊNCIA EXTERNA:');
            $sheet->setCellValue('B' . $row, $orcamento->referencia_externa);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('B' . $row . ':M' . $row);
            $row++;
        }


        $row++; // Linha em branco

        // ====== TABELA DE ITENS ======
        $headerRow = $row;
        $headers = [
            'LOTE/ITEM',
            'DESCRIÇÃO',
            'UND. DE FORNEC.',
            'QUANTIDADE',
            'PREÇO UNIT. (R$)',
            'PREÇO TOTAL (R$)',
            'N° AMOSTRAS',
            'DESVIO PADRÃO (R$)',
            'CV (%)',
            'MENOR PREÇO (R$)',
            'MÉDIA (R$)',
            'MEDIANA (R$)',
            'MÉTODO ADOTADO'
        ];

        // Cabeçalhos da tabela
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF666666');
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $col++;
        }
        $row++;

        // Itens
        $valorGlobal = 0;
        $firstDataRow = $row;
        $itemCounter = 1; // Contador manual para numeração dos itens

        foreach ($orcamento->itens as $item) {
            $precoUnit = $item->preco_unitario ?? 0;
            $quantidade = $item->quantidade ?? 0;
            $precoTotal = $precoUnit * $quantidade;
            $valorGlobal += $precoTotal;

            // ========== CALCULAR ESTATÍSTICAS (IGUAL AO PDF) ==========
            $amostras = $item->amostras_selecionadas
                ? json_decode($item->amostras_selecionadas, true)
                : [];

            $estatisticas = null;
            if (count($amostras) > 0) {
                $estatisticas = $this->calcularEstatisticas($amostras);
            }

            // COLUNA A: LOTE/ITEM (CORRIGIDO)
            $numeroLote = $item->lote ? str_pad($item->lote->numero, 2, '0', STR_PAD_LEFT) : '00';
            $numeroItem = str_pad($itemCounter, 3, '0', STR_PAD_LEFT);
            $loteItem = $numeroLote . '/' . $numeroItem;
            $sheet->setCellValue('A' . $row, $loteItem);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row)->getFont()->setSize(10);

            // COLUNA B: DESCRIÇÃO
            $sheet->setCellValue('B' . $row, strtoupper($item->descricao));
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $sheet->getStyle('B' . $row)->getFont()->setSize(10);

            // COLUNA C: UNIDADE (CORRIGIDO - campo medida_fornecimento)
            $unidade = strtoupper($item->medida_fornecimento ?? 'UN');
            $sheet->setCellValue('C' . $row, $unidade);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getFont()->setSize(10);

            // COLUNA D: QUANTIDADE
            $sheet->setCellValue('D' . $row, $quantidade);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('D' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $row)->getFont()->setSize(10);

            // COLUNA E: PREÇO UNITÁRIO
            $sheet->setCellValue('E' . $row, $precoUnit);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('E' . $row)->getFont()->setSize(10)->setBold(true);

            // COLUNA F: PREÇO TOTAL
            $sheet->setCellValue('F' . $row, $precoTotal);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('F' . $row)->getFont()->setSize(10)->setBold(true);

            // ========== NOVAS COLUNAS ESTATÍSTICAS (CALCULADAS) ==========

            // COLUNA G: N° AMOSTRAS
            $nAmostras = $estatisticas ? $estatisticas['num_amostras_validas'] : '-';
            $sheet->setCellValue('G' . $row, $nAmostras);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('G' . $row)->getFont()->setSize(9);

            // COLUNA H: DESVIO PADRÃO (R$)
            if ($estatisticas) {
                $desvioPadrao = $estatisticas['desvio_padrao'];
                $sheet->setCellValue('H' . $row, $desvioPadrao);
                $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
            } else {
                $sheet->setCellValue('H' . $row, '-');
            }
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('H' . $row)->getFont()->setSize(9);

            // COLUNA I: CV (%)
            if ($estatisticas) {
                $coefVariacao = $estatisticas['coef_variacao'];
                $sheet->setCellValue('I' . $row, $coefVariacao);
                $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00"%"');
            } else {
                $sheet->setCellValue('I' . $row, '-');
            }
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('I' . $row)->getFont()->setSize(9);

            // COLUNA J: MENOR PREÇO (R$)
            if ($estatisticas) {
                $menorPreco = $estatisticas['menor_preco'];
                $sheet->setCellValue('J' . $row, $menorPreco);
                $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
            } else {
                $sheet->setCellValue('J' . $row, '-');
            }
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('J' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('J' . $row)->getFont()->setSize(9);

            // COLUNA K: MÉDIA (R$)
            if ($estatisticas) {
                $media = $estatisticas['media'];
                $sheet->setCellValue('K' . $row, $media);
                $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
            } else {
                $sheet->setCellValue('K' . $row, '-');
            }
            $sheet->getStyle('K' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('K' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('K' . $row)->getFont()->setSize(9);

            // COLUNA L: MEDIANA (R$)
            if ($estatisticas) {
                $mediana = $estatisticas['mediana'];
                $sheet->setCellValue('L' . $row, $mediana);
                $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
            } else {
                $sheet->setCellValue('L' . $row, '-');
            }
            $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('L' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('L' . $row)->getFont()->setSize(9);

            // COLUNA M: MÉTODO ADOTADO
            if ($estatisticas) {
                $metodoTexto = strtoupper($estatisticas['metodo']);
                $sheet->setCellValue('M' . $row, $metodoTexto);
            } else {
                $sheet->setCellValue('M' . $row, '-');
            }
            $sheet->getStyle('M' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('M' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('M' . $row)->getFont()->setSize(9);

            // Alternar cor de fundo das linhas (agora até coluna M)
            if (($row - $firstDataRow) % 2 === 1) {
                $sheet->getStyle('A' . $row . ':M' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
            }

            $row++;

            // ========== SUBTABELA DE AMOSTRAS COLETADAS ==========
            if (count($amostras) > 0) {
                // 2 linhas em branco para separação visual
                $row += 2;

                // Cabeçalho destacado da subtabela (mesclado em 7 colunas: B-H)
                $headerSubRow = $row;
                $sheet->mergeCells('B' . $row . ':H' . $row);
                $sheet->setCellValue('B' . $row, '▼ AMOSTRAS COLETADAS PARA ESTE ITEM');
                $sheet->getStyle('B' . $row)->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle('B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF4A90E2'); // Azul profissional
                $sheet->getRowDimension($row)->setRowHeight(25);
                $row++;

                // Cabeçalhos das colunas da subtabela (cores suaves)
                $colunasSub = [
                    'B' => 'AMOSTRA',
                    'C' => 'ÓRGÃO/FORNECEDOR',
                    'D' => 'FONTE',
                    'E' => 'DATA',
                    'F' => 'PREÇO',
                    'G' => 'PREGÃO/ATA',
                    'H' => 'SITUAÇÃO'
                ];

                $headerColRow = $row;
                foreach ($colunasSub as $col => $titulo) {
                    $sheet->setCellValue($col . $row, $titulo);
                    $sheet->getStyle($col . $row)->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF333333');
                    $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($col . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCE6F1'); // Azul claro suave
                }
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;

                // Obter lista de amostras expurgadas
                $amostrasExpurgadas = [];
                if ($estatisticas && isset($estatisticas['amostras_expurgadas'])) {
                    $amostrasExpurgadas = $estatisticas['amostras_expurgadas'];
                }

                // Iterar pelas amostras
                $numeroAmostra = 1;
                $firstAmostraRow = $row;

                foreach ($amostras as $indexAmostra => $amostra) {
                    $currentRow = $row;

                    // COLUNA B: AMOSTRA (número sequencial)
                    $sheet->setCellValue('B' . $row, $numeroAmostra);
                    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('B' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('B' . $row)->getFont()->setSize(9);

                    // COLUNA C: ÓRGÃO/FORNECEDOR
                    $orgaoFornecedor = $amostra['orgao'] ?? $amostra['fornecedor_nome'] ?? '-';
                    $sheet->setCellValue('C' . $row, mb_strtoupper($orgaoFornecedor));
                    $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('C' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('C' . $row)->getFont()->setSize(9);

                    // COLUNA D: FONTE
                    $fonte = $amostra['fonte'] ?? '-';
                    $fonteTexto = match(strtolower($fonte)) {
                        'pncp' => 'PNCP',
                        'comprasgov' => 'Compras.gov',
                        'licitacon' => 'LicitaCon',
                        'cdf' => 'CDF',
                        'local' => 'Local',
                        'cmed' => 'CMED',
                        default => strtoupper($fonte)
                    };
                    $sheet->setCellValue('D' . $row, $fonteTexto);
                    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('D' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('D' . $row)->getFont()->setSize(9);

                    // COLUNA E: DATA
                    $data = $amostra['data_publicacao'] ?? $amostra['data'] ?? '-';
                    if ($data !== '-' && $data) {
                        try {
                            $dataObj = new \DateTime($data);
                            $dataFormatada = $dataObj->format('d/m/Y');
                        } catch (\Exception $e) {
                            $dataFormatada = $data;
                        }
                    } else {
                        $dataFormatada = '-';
                    }
                    $sheet->setCellValue('E' . $row, $dataFormatada);
                    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('E' . $row)->getFont()->setSize(9);

                    // COLUNA F: PREÇO
                    $valorAmostra = $amostra['valor_unitario'] ?? $amostra['preco'] ?? 0;
                    $sheet->setCellValue('F' . $row, $valorAmostra);
                    $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
                    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('F' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('F' . $row)->getFont()->setSize(9)->setBold(true);

                    // COLUNA G: PREGÃO/ATA
                    $pregaoAta = [];
                    if (!empty($amostra['numero_pregao'])) {
                        $pregaoAta[] = 'PE ' . $amostra['numero_pregao'];
                    }
                    if (!empty($amostra['numero_ata'])) {
                        $pregaoAta[] = 'ATA ' . $amostra['numero_ata'];
                    }
                    $pregaoAtaTexto = count($pregaoAta) > 0 ? implode(' / ', $pregaoAta) : '-';
                    $sheet->setCellValue('G' . $row, $pregaoAtaTexto);
                    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('G' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('G' . $row)->getFont()->setSize(9);

                    // COLUNA H: SITUAÇÃO (VALIDADA ou EXPURGADA) com fundo colorido
                    $foiExpurgada = in_array($indexAmostra, $amostrasExpurgadas);
                    $situacao = $foiExpurgada ? 'EXPURGADA' : 'VALIDADA';
                    $sheet->setCellValue('H' . $row, $situacao);
                    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('H' . $row)->getFont()->setSize(9)->setBold(true)->getColor()->setARGB('FFFFFFFF');

                    // Fundo colorido para situação (verde para VALIDADA, vermelho para EXPURGADA)
                    if ($foiExpurgada) {
                        $sheet->getStyle('H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFEF5350'); // Vermelho suave
                    } else {
                        $sheet->getStyle('H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF66BB6A'); // Verde suave
                    }

                    // Fundo alternado suave (zebrado) nas linhas de amostras (exceto coluna H que já tem cor)
                    if ($numeroAmostra % 2 === 0) {
                        $sheet->getStyle('B' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
                    }

                    // Altura da linha
                    $sheet->getRowDimension($row)->setRowHeight(18);

                    $row++;
                    $numeroAmostra++;
                }

                $lastAmostraRow = $row - 1;

                // Bordas ao redor de toda a subtabela (cabeçalho + dados)
                $sheet->getStyle('B' . $headerSubRow . ':H' . $lastAmostraRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('B' . $headerSubRow . ':H' . $lastAmostraRow)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                // 1 linha em branco após a subtabela de amostras
                $row++;
            }

            $itemCounter++; // Incrementar contador de itens
        }

        $lastDataRow = $row - 1;

        // Linha de VALOR GLOBAL
        $sheet->setCellValue('A' . $row, 'VALOR GLOBAL');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        $sheet->setCellValue('F' . $row, $valorGlobal);
        $sheet->getStyle('F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('_("R$"* #,##0.00_);_("R$"* \(#,##0.00\);_("R$"* "-"??_);_(@_)');
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // Aplicar fundo cinza também nas colunas estatísticas (G-M) da linha de total
        $sheet->getStyle('G' . $row . ':M' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // Aplicar bordas à tabela completa (incluindo cabeçalho, dados e total - agora até coluna M)
        $tableRange = 'A' . $headerRow . ':M' . $row;
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Ajustar altura das linhas de dados para conteúdo
        for ($i = $firstDataRow; $i <= $lastDataRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(-1); // Auto height
        }

        // Nome do arquivo
        $nomeArquivo = 'orcamento_' . str_replace(['/', '\\'], '-', $orcamento->numero) . '.xlsx';

        // Gerar arquivo e enviar para download
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        // Configurar headers para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Gerar HTML formatado do orçamento
     */
    private function gerarHtmlOrcamento($orcamento)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Orçamento ' . ($orcamento->numero ?? 'N/A') . '</title>
            <style>
                @page { margin: 2cm; }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11pt;
                    line-height: 1.5;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #2563eb;
                    padding-bottom: 20px;
                }
                .header h1 {
                    color: #2563eb;
                    font-size: 18pt;
                    margin: 0 0 10px 0;
                }
                .section {
                    margin-bottom: 25px;
                }
                .section-title {
                    background: #2563eb;
                    color: white;
                    padding: 8px 12px;
                    font-weight: bold;
                    font-size: 12pt;
                    margin-bottom: 15px;
                }
                .info-row {
                    display: flex;
                    margin-bottom: 8px;
                }
                .info-label {
                    font-weight: bold;
                    width: 200px;
                    color: #666;
                }
                .info-value {
                    flex: 1;
                    color: #333;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th {
                    background: #f3f4f6;
                    padding: 10px;
                    text-align: left;
                    font-size: 10pt;
                    border: 1px solid #d1d5db;
                }
                td {
                    padding: 8px;
                    border: 1px solid #d1d5db;
                    font-size: 10pt;
                }
                .total-row {
                    background: #f9fafb;
                    font-weight: bold;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ccc;
                    text-align: center;
                    font-size: 9pt;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ORÇAMENTO ESTIMATIVO</h1>
                <p><strong>Número:</strong> ' . ($orcamento->numero ?? 'Não gerado') . '</p>
                <p><strong>Data de Emissão:</strong> ' . $orcamento->created_at->format('d/m/Y H:i') . '</p>
            </div>

            <div class="section">
                <div class="section-title">1. DADOS CADASTRAIS</div>
                <div class="info-row">
                    <div class="info-label">Nome do Orçamento:</div>
                    <div class="info-value">' . e($orcamento->nome) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Referência Externa:</div>
                    <div class="info-value">' . e($orcamento->referencia_externa ?? '-') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Objeto:</div>
                    <div class="info-value">' . e($orcamento->objeto) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Órgão Interessado:</div>
                    <div class="info-value">' . e($orcamento->orgao_interessado ?? 'Prefeitura municipal de Barbacena') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Orçamentista:</div>
                    <div class="info-value">' . e($orcamento->user->name) . '</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">2. METODOLOGIAS E PADRÕES</div>
                <div class="info-row">
                    <div class="info-label">Método do Juízo Crítico:</div>
                    <div class="info-value">' . ($orcamento->metodo_juizo_critico === 'saneamento_desvio_padrao' ? 'Saneamento das amostras pelo desvio-padrão' : 'Saneamento das amostras com base em percentual') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Método de Obtenção do Preço:</div>
                    <div class="info-value">' . $this->getMetodoObtencaoPrecoLabel($orcamento->metodo_obtencao_preco) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Casas Decimais:</div>
                    <div class="info-value">' . ($orcamento->casas_decimais === 'duas' ? 'Duas casas decimais' : 'Quatro casas decimais') . '</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">3. ITENS DO ORÇAMENTO</div>';

        if ($orcamento->itens->count() > 0) {
            $html .= '
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Item</th>
                            <th>Descrição</th>
                            <th style="width: 80px;">Qtd.</th>
                            <th style="width: 60px;">Un.</th>
                            <th style="width: 100px;">Valor Unit.</th>
                            <th style="width: 100px;">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>';

            $valorTotal = 0;
            foreach ($orcamento->itens as $index => $item) {
                $valorTotal += $item->valor_total ?? 0;
                $html .= '
                        <tr>
                            <td style="text-align: center;">' . ($index + 1) . '</td>
                            <td>' . e($item->descricao) . '</td>
                            <td style="text-align: right;">' . number_format($item->quantidade, 2, ',', '.') . '</td>
                            <td style="text-align: center;">' . e($item->unidade) . '</td>
                            <td style="text-align: right;">R$ ' . number_format($item->valor_unitario ?? 0, 2, ',', '.') . '</td>
                            <td style="text-align: right;">R$ ' . number_format($item->valor_total ?? 0, 2, ',', '.') . '</td>
                        </tr>';
            }

            $html .= '
                        <tr class="total-row">
                            <td colspan="5" style="text-align: right;">VALOR TOTAL DO ORÇAMENTO:</td>
                            <td style="text-align: right;">R$ ' . number_format($valorTotal, 2, ',', '.') . '</td>
                        </tr>
                    </tbody>
                </table>';
        } else {
            $html .= '<p style="text-align: center; padding: 20px; color: #999;">Nenhum item cadastrado.</p>';
        }

        $html .= '</div>';

        if ($orcamento->observacao_justificativa) {
            $html .= '
            <div class="section">
                <div class="section-title">4. OBSERVAÇÕES/JUSTIFICATIVAS</div>
                <p>' . nl2br(e($orcamento->observacao_justificativa)) . '</p>
            </div>';
        }

        $html .= '
            <div class="footer">
                <p>Documento gerado em ' . now()->format('d/m/Y H:i:s') . '</p>
                <p>Sistema de Cesta de Preços - MinhaDattaTech</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Buscar itens no PNCP com preços de referência
     */
    public function buscarPNCP(Request $request)
    {
        try {
            $termo = $request->get('termo', '');

            // ✅ LOG DE DEBUG: Ver TODOS os parâmetros recebidos
            Log::info('🔍 BuscarPNCP CHAMADO!', [
                'termo' => $termo,
                'todos_parametros' => $request->all(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);

            if (strlen($termo) < 3) {
                Log::warning('⚠️ Termo muito curto!', ['termo' => $termo, 'tamanho' => strlen($termo)]);
                return response()->json([
                    'success' => false,
                    'message' => 'Digite pelo menos 3 caracteres para buscar'
                ]);
            }

            Log::info('✅ BuscarPNCP: Iniciando busca COMPLETA (banco + APIs)', ['termo' => $termo]);

            // BUSCAR EM TODAS AS FONTES E MESCLAR (NÃO PARAR NA PRIMEIRA!)
            $todosResultados = [];

            // 1. Buscar no BANCO LOCAL (rápido)
            try {
                $resultadosLocal = $this->buscarNoBancoLocal($termo);
                if (!empty($resultadosLocal)) {
                    Log::info('BuscarPNCP: Encontrou no banco local', ['total' => count($resultadosLocal)]);
                    $todosResultados = array_merge($todosResultados, $resultadosLocal);
                }
            } catch (\Exception $e) {
                Log::warning('BuscarPNCP: Erro no banco local', ['erro' => $e->getMessage()]);
            }

            // 2. Buscar nas APIs EXTERNAS do PNCP (sempre busca!)
            try {
                Log::info('BuscarPNCP: Buscando nas APIs PNCP externas');
                $resultadosPNCP = $this->buscarNoPNCPExterno($termo);
                if (!empty($resultadosPNCP)) {
                    Log::info('BuscarPNCP: Encontrou nas APIs PNCP', ['total' => count($resultadosPNCP)]);
                    $todosResultados = array_merge($todosResultados, $resultadosPNCP);
                }
            } catch (\Exception $e) {
                Log::warning('BuscarPNCP: Erro nas APIs PNCP', ['erro' => $e->getMessage()]);
            }

            // 3. Buscar em ORÇAMENTOS LOCAIS (backup)
            try {
                $resultadosLocais = $this->buscarEmOrcamentosLocais($termo);
                if (!empty($resultadosLocais)) {
                    Log::info('BuscarPNCP: Encontrou em orçamentos locais', ['total' => count($resultadosLocais)]);
                    $todosResultados = array_merge($todosResultados, $resultadosLocais);
                }
            } catch (\Exception $e) {
                Log::warning('BuscarPNCP: Erro em orçamentos locais', ['erro' => $e->getMessage()]);
            }

            // ========================================
            // 4. NOVAS APIS - INTEGRAÇÃO 15/10/2025
            // ========================================

            // 4a. Buscar no LICITACON (TCE-RS) - Cache Local
            try {
                Log::info('🟣 BuscarPNCP: Chamando buscarNoLicitaCon()', ['termo' => $termo]);
                $resultadosLicitaCon = $this->buscarNoLicitaCon($termo);
                Log::info('🟣 BuscarPNCP: buscarNoLicitaCon() retornou', [
                    'tipo' => gettype($resultadosLicitaCon),
                    'count' => is_array($resultadosLicitaCon) ? count($resultadosLicitaCon) : 'N/A',
                    'empty' => empty($resultadosLicitaCon) ? 'SIM' : 'NÃO'
                ]);

                if (!empty($resultadosLicitaCon)) {
                    Log::info('🟣 BuscarPNCP: Encontrou no LicitaCon - MESCLANDO', ['total' => count($resultadosLicitaCon)]);
                    $antesDoMerge = count($todosResultados);
                    $todosResultados = array_merge($todosResultados, $resultadosLicitaCon);
                    Log::info('🟣 BuscarPNCP: Após merge', [
                        'antes' => $antesDoMerge,
                        'adicionados' => count($resultadosLicitaCon),
                        'depois' => count($todosResultados)
                    ]);
                } else {
                    Log::info('🟣 BuscarPNCP: LicitaCon retornou vazio');
                }
            } catch (\Exception $e) {
                Log::warning('🟣 BuscarPNCP: Erro no LicitaCon', ['erro' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }

            // 4b. Buscar no COMPRAS.GOV - API Pública
            error_log("🔥 DEBUG: PRESTES A CHAMAR buscarNoComprasGov() com termo: $termo");
            try {
                $resultadosComprasGov = $this->buscarNoComprasGov($termo);
                if (!empty($resultadosComprasGov)) {
                    Log::info('BuscarPNCP: Encontrou no Compras.gov', ['total' => count($resultadosComprasGov)]);
                    $todosResultados = array_merge($todosResultados, $resultadosComprasGov);
                }
            } catch (\Exception $e) {
                Log::warning('BuscarPNCP: Erro no Compras.gov', ['erro' => $e->getMessage()]);
            }

            // 4c. Buscar no PORTAL DA TRANSPARÊNCIA (CGU) - API com Chave
            try {
                $resultadosCGU = $this->buscarNoPortalTransparencia($termo);
                if (!empty($resultadosCGU)) {
                    Log::info('BuscarPNCP: Encontrou no Portal Transparência', ['total' => count($resultadosCGU)]);
                    $todosResultados = array_merge($todosResultados, $resultadosCGU);
                }
            } catch (\Exception $e) {
                Log::warning('BuscarPNCP: Erro no Portal Transparência', ['erro' => $e->getMessage()]);
            }

            // ========================================
            // FIM NOVAS APIS
            // ========================================

            // RETORNAR TUDO MESCLADO
            if (!empty($todosResultados)) {
                // FILTRAR valores zerados ANTES de retornar
                $totalAntes = count($todosResultados);
                $todosResultados = array_filter($todosResultados, function($resultado) {
                    $valor = $resultado['valor_unitario'] ??
                             $resultado['valor_homologado_item'] ??
                             $resultado['valor_global'] ?? 0;
                    return $valor > 0;
                });
                $todosResultados = array_values($todosResultados); // Reindexar array
                $totalRemovidos = $totalAntes - count($todosResultados);

                if ($totalRemovidos > 0) {
                    Log::info("🚫 BuscarPNCP: {$totalRemovidos} resultado(s) com valor zerado removido(s)");
                }

                Log::info('✅ BuscarPNCP: SUCESSO! Total final mesclado', ['total' => count($todosResultados), 'removidos' => $totalRemovidos]);

                // DEBUG TEMPORÁRIO: Verificar execução
                $debug_info = [
                    'codigo_versao' => '2025-10-17-19:32', // Timestamp único
                    'compras_gov_chamado' => true
                ];

                return response()->json([
                    'success' => true,
                    'resultados' => $todosResultados,
                    'total_encontrado' => count($todosResultados),
                    'fonte' => 'TODAS',
                    'debug' => $debug_info // DEBUG TEMPORÁRIO
                ]);
            }

            Log::warning('⚠️ BuscarPNCP: NENHUM RESULTADO ENCONTRADO', ['termo' => $termo]);
            return response()->json([
                'success' => true,
                'resultados' => [],
                'message' => 'Nenhum item encontrado com este termo'
            ]);

        } catch (\Exception $e) {
            Log::error('BuscarPNCP: Erro geral', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar busca.',
                'detalhes' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ====================================================================
     * NOVO: Buscar no BANCO LOCAL (RÁPIDO E FUNCIONA COM QUALQUER PALAVRA!)
     * ====================================================================
     *
     * Usa busca full-text do PostgreSQL no banco local sincronizado
     * Retorna resultados em < 1 segundo!
     */
    private function buscarNoBancoLocal($termo)
    {
        try {
            // Verificar se há dados no banco (se não tiver, retornar vazio)
            $totalRegistros = ContratoPNCP::count();

            if ($totalRegistros == 0) {
                Log::warning('BuscarNoBancoLocal: Banco local vazio! Execute: php artisan pncp:sincronizar');
                return [];
            }

            Log::info('BuscarNoBancoLocal: Buscando no banco local', [
                'termo' => $termo,
                'total_registros' => $totalRegistros
            ]);

            // Buscar usando full-text search (QUALQUER PALAVRA funciona!)
            $contratos = ContratoPNCP::buscarPorTermo($termo, 6, 100);

            if ($contratos->isEmpty()) {
                Log::info('BuscarNoBancoLocal: Nenhum resultado encontrado no banco local');
                return [];
            }

            Log::info('BuscarNoBancoLocal: Encontrados resultados no banco local', [
                'total' => $contratos->count()
            ]);

            // Converter para o formato esperado pelo modal (DADOS INDIVIDUAIS - não agrupar!)
            $itensFormatados = $contratos->map(function($contrato) {
                // Detectar unidade de medida (usar valor do banco ou detectar pela descrição)
                $unidade = $contrato->unidade_medida ?? $this->detectarUnidadeMedida($contrato->objeto_contrato);

                // Construir link do PNCP a partir do numero_controle_pncp
                // Formato: 02367597000132-2025-158 → https://pncp.gov.br/app/editais/02367597000132/2025/158
                $linkFonte = null;
                $numeroPregao = null;
                $numeroAta = null;

                if ($contrato->numero_controle_pncp) {
                    $partes = explode('-', $contrato->numero_controle_pncp);
                    if (count($partes) >= 3) {
                        $cnpj = $partes[0];
                        $ano = $partes[1];
                        $sequencial = $partes[2];
                        $linkFonte = "https://pncp.gov.br/app/editais/{$cnpj}/{$ano}/{$sequencial}";

                        // Preencher numero_pregao com ano/sequencial
                        $numeroPregao = "{$ano}/{$sequencial}";

                        // Se o tipo for ATA, preencher numero_ata também
                        if (stripos($contrato->tipo, 'ata') !== false) {
                            $numeroAta = $sequencial;
                        }
                    }
                }

                return [
                    'descricao' => $contrato->objeto_contrato,
                    'nome_item' => $contrato->objeto_contrato,
                    'valor_unitario' => (float) ($contrato->valor_unitario_estimado ?? $contrato->valor_global),
                    'quantidade' => 1,
                    'unidade_medida' => $unidade,
                    'medida_fornecimento' => $unidade,
                    'orgao_nome' => $contrato->orgao,
                    'razao_social_fornecedor' => $contrato->orgao,
                    'uf' => $contrato->orgao_uf,
                    'municipio' => null,
                    'data_vigencia_inicio' => $contrato->data_publicacao_pncp?->format('Y-m-d'),
                    'data' => $contrato->data_publicacao_pncp?->format('Y-m-d'),
                    'fonte' => 'PNCP',
                    'numero_controle_pncp' => $contrato->numero_controle_pncp,
                    'tipo_origem' => $contrato->tipo,
                    'confiabilidade' => $contrato->confiabilidade,
                    'link_fonte' => $linkFonte, // ✅ NOVO: Link para o edital no PNCP
                    'numero_pregao' => $numeroPregao, // ✅ NOVO: Número do pregão (ano/seq)
                    'numero_ata' => $numeroAta, // ✅ NOVO: Número da ARP (se aplicável)
                ];
            })->toArray();

            // RETORNAR DADOS INDIVIDUAIS (não agrupar - igual Pesquisa Rápida)
            return $itensFormatados;

        } catch (\Exception $e) {
            Log::error('BuscarNoBancoLocal: Erro ao buscar no banco local', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Buscar no PNCP externo com inteligência:
     * 1. Busca PRIMEIRO em Atas (dados mais granulares)
     * 2. Depois complementa com Contratos
     * 3. Calcula valor unitário quando possível
     * 4. Indica confiabilidade do dado
     */
    private function buscarNoPNCPExterno($termo)
    {
        try {
            $hoje = now();
            $dataFinal = $hoje->format('Ymd');
            $dataInicial = $hoje->copy()->subYear()->format('Ymd'); // MUDADO: 1 ANO ao invés de 6 meses

            $todosItens = [];

            Log::info('BuscarNoPNCPExterno: Iniciando busca inteligente', [
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
                'termo' => $termo
            ]);

            // ==================================================
            // ETAPA 1: BUSCAR EM ATAS (PRIORIDADE - dados mais confiáveis)
            // ==================================================
            // NOTA: Desabilitado temporariamente porque API não retorna valores na listagem
            // TODO: Implementar busca detalhada em itens de atas via endpoint específico
            // $itensAtas = $this->buscarEmAtas($termo, $dataInicial, $dataFinal);
            // $todosItens = array_merge($todosItens, $itensAtas);
            // Log::info('BuscarNoPNCPExterno: Atas encontradas', ['total' => count($itensAtas)]);

            // ==================================================
            // ETAPA 2: BUSCAR EM CONTRATOS
            // ==================================================
            $itensContratos = $this->buscarEmContratos($termo, $dataInicial, $dataFinal);
            $todosItens = array_merge($todosItens, $itensContratos);

            Log::info('BuscarNoPNCPExterno: Contratos encontrados', ['total' => count($itensContratos)]);

            if (empty($todosItens)) {
                Log::warning('BuscarNoPNCPExterno: NENHUM ITEM ENCONTRADO', [
                    'termo' => $termo,
                    'periodo' => "$dataInicial a $dataFinal"
                ]);
                return [];
            }

            Log::info('BuscarNoPNCPExterno: ITENS BRUTOS ENCONTRADOS', [
                'termo' => $termo,
                'total_itens' => count($todosItens)
            ]);

            // RETORNAR DADOS INDIVIDUAIS (não agrupar - igual Pesquisa Rápida)
            // O modal de cotação espera dados individuais, não agrupados!
            $resultadosFormatados = array_map(function($item) {
                // ✅ PRIORIZAR link_fonte que já vem construído (da buscarEmContratos)
                $linkFonte = $item['link_fonte'] ?? null;
                $numeroPregao = $item['numero_pregao'] ?? null;
                $numeroAta = $item['numero_ata'] ?? null;

                // Se não vier link pronto, construir agora
                if (!$linkFonte) {
                    // Tentar construir com campos separados (prioridade)
                    if (!empty($item['cnpj_orgao']) && !empty($item['ano_compra']) && !empty($item['sequencial_compra'])) {
                        $linkFonte = "https://pncp.gov.br/app/editais/{$item['cnpj_orgao']}/{$item['ano_compra']}/{$item['sequencial_compra']}";
                        if (!$numeroPregao) $numeroPregao = "{$item['ano_compra']}/{$item['sequencial_compra']}";
                    }
                    // Fallback: tentar construir do numeroControlePNCP (formato: cnpj-ano-seq)
                    elseif (!empty($item['numeroControlePNCP'])) {
                        $partes = explode('-', $item['numeroControlePNCP']);
                        if (count($partes) >= 3) {
                            $linkFonte = "https://pncp.gov.br/app/editais/{$partes[0]}/{$partes[1]}/{$partes[2]}";
                            if (!$numeroPregao) $numeroPregao = "{$partes[1]}/{$partes[2]}";
                        }
                    }
                }

                return [
                    'descricao' => $item['descricao'] ?? 'Sem descrição',
                    'nome_item' => $item['descricao'] ?? 'Sem descrição',
                    'valor_unitario' => $item['valor_unitario'] ?? $item['valor'] ?? 0, // ✅ CORRIGIDO: aceitar ambas as chaves
                    'quantidade' => $item['quantidade'] ?? 1,
                    'unidade_medida' => $item['unidade_medida'] ?? $item['unidade'] ?? 'UN', // ✅ CORRIGIDO: aceitar ambas as chaves
                    'medida_fornecimento' => $item['unidade_medida'] ?? $item['unidade'] ?? 'UN', // ✅ CORRIGIDO: aceitar ambas as chaves
                    'orgao_nome' => $item['orgao_nome'] ?? $item['orgao'] ?? 'N/A', // ✅ CORRIGIDO: aceitar ambas as chaves
                    'razao_social_fornecedor' => $item['orgao_nome'] ?? $item['orgao'] ?? 'N/A', // ✅ CORRIGIDO: aceitar ambas as chaves
                    'uf' => $item['uf'] ?? null,
                    'municipio' => $item['municipio'] ?? null,
                    'data_vigencia_inicio' => $item['data_publicacao'] ?? null,
                    'data' => $item['data_publicacao'] ?? null,
                    'fonte' => $item['fonte'] ?? 'PNCP',
                    'numero_controle_pncp' => $item['numeroControlePNCP'] ?? null,
                    'tipo_origem' => $item['tipo_origem'] ?? 'contrato',
                    'confiabilidade' => $item['confiabilidade'] ?? 'media',
                    'link_fonte' => $linkFonte, // ✅ NOVO: Link para o edital no PNCP
                    'numero_pregao' => $numeroPregao, // ✅ NOVO: Número do pregão (ano/seq)
                    'numero_ata' => $numeroAta, // ✅ NOVO: Número da ARP (se aplicável)
                ];
            }, $todosItens);

            Log::info('BuscarNoPNCPExterno: RESULTADOS FINAIS', [
                'termo' => $termo,
                'total_formatados' => count($resultadosFormatados)
            ]);

            return $resultadosFormatados;

        } catch (\Exception $e) {
            Log::error('BuscarNoPNCPExterno: Erro geral', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar em Atas de Registro de Preço (dados mais confiáveis)
     */
    private function buscarEmAtas($termo, $dataInicial, $dataFinal)
    {
        $itens = [];
        $paginasParaBuscar = 1; // REDUZIDO PARA 1 PÁGINA (evitar timeout)

        for ($pagina = 1; $pagina <= $paginasParaBuscar; $pagina++) {
            $url = "https://pncp.gov.br/api/consulta/v1/atas?" . http_build_query([
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
                'q' => $termo, // ENVIAR O TERMO DE BUSCA PARA A API
                'pagina' => $pagina
            ]);

            Log::info('BuscarEmAtas: Buscando página', [
                'pagina' => $pagina,
                'termo' => $termo,
                'url' => $url
            ]);

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(8)->get($url); // TIMEOUT REDUZIDO PARA 8s

                if (!$response->successful()) {
                    Log::warning('BuscarEmAtas: Resposta não bem-sucedida', [
                        'status' => $response->status(),
                        'pagina' => $pagina
                    ]);
                    continue;
                }

                $data = $response->json();

                if (!isset($data['data']) || empty($data['data'])) {
                    Log::info('BuscarEmAtas: Sem mais dados na página ' . $pagina);
                    break;
                }

                Log::info('BuscarEmAtas: Processando resultados', [
                    'pagina' => $pagina,
                    'total_na_pagina' => count($data['data'])
                ]);

                foreach ($data['data'] as $ata) {
                    $objetoContratacao = $ata['objetoContratacao'] ?? '';

                    // API já filtrou pelo termo, apenas verificar se não está vazio
                    if (empty($objetoContratacao)) {
                        continue;
                    }

                    // NOTA: API do PNCP não retorna valores nas atas na listagem
                    // Por enquanto, vamos PULAR atas sem valor para evitar resultados vazios
                    // TODO: No futuro, buscar itens detalhados da ata via endpoint específico
                    // Exemplo: /v1/orgaos/{cnpj}/compras/{ano}/{sequencial}/atas/{numero}/itens
                    continue; // Pular atas por enquanto até implementar busca de itens
                }

            } catch (\Exception $e) {
                Log::warning('BuscarEmAtas: Erro na página', [
                    'pagina' => $pagina,
                    'erro' => $e->getMessage()
                ]);
                continue;
            }

            if ($pagina < $paginasParaBuscar) {
                usleep(200000); // 200ms delay
            }
        }

        return $itens;
    }

    /**
     * Buscar em Contratos usando API de Busca Textual (/api/search)
     * USA A API CORRETA QUE ACEITA BUSCA POR PALAVRA-CHAVE!
     * Mesma correção aplicada na Pesquisa Rápida
     */
    private function buscarEmContratos($termo, $dataInicial, $dataFinal)
    {
        $itens = [];
        $maxPaginas = 3; // Limitar a 3 páginas (até ~1500 contratos)

        Log::info('BuscarEmContratos: Usando API /search com detecção de quantidade', [
            'termo' => $termo,
            'maxPaginas' => $maxPaginas
        ]);

        for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
            try {
                // USAR API CORRETA: /api/search (aceita busca por palavra-chave!)
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Accept' => 'application/json',
                            'User-Agent' => 'DattaTech-PNCP/1.0',
                        ])
                        ->withOptions([
                            'curl' => [
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_IPRESOLVE    => CURL_IPRESOLVE_V4,
                            ],
                        ])
                        ->connectTimeout(5)
                        ->timeout(15)
                        ->get('https://pncp.gov.br/api/search/', [
                            'q' => $termo,
                            'tipos_documento' => 'contrato',
                            'pagina' => $pagina,
                            'tamanhoPagina' => 500
                        ]);

                if (!$response->successful()) {
                    Log::warning('BuscarEmContratos: Erro na página ' . $pagina, [
                        'status' => $response->status()
                    ]);
                    break;
                }

                $data = $response->json();
                $contratos = $data['items'] ?? [];

                if (empty($contratos)) {
                    Log::info('BuscarEmContratos: Sem mais resultados na página ' . $pagina);
                    break;
                }

                Log::info('BuscarEmContratos: Página ' . $pagina . ' = ' . count($contratos) . ' contratos');

                foreach ($contratos as $contrato) {
                    $valorGlobal = $contrato['valor_global'] ?? 0;

                    if ($valorGlobal <= 0) {
                        continue;
                    }

                    // ✅ ESTRATÉGIA DETALHADA: Buscar itens unitários REAIS do processo de compra
                    // Endpoint correto: /orgaos/{cnpj}/compras/{ano}/{seq}/itens (não /contratos/)
                    // Descoberta: Contratos/Empenhos não têm /itens, mas processos de compra SIM!

                    $cnpj = $contrato['orgao_cnpj'] ?? null;
                    $ano = $contrato['ano'] ?? null;
                    $sequencial = $contrato['numero_sequencial'] ?? null;
                    $numeroControlePNCP = $contrato['numero_controle_pncp'] ?? null;

                    // ✅ CONSTRUIR LINK CORRETO DO PNCP
                    $linkFonte = null;
                    if (!empty($contrato['item_url'])) {
                        $linkFonte = "https://pncp.gov.br/app" . $contrato['item_url'];
                    }
                    elseif ($cnpj && $ano && $sequencial) {
                        $linkFonte = "https://pncp.gov.br/app/contratos/{$cnpj}/{$ano}/{$sequencial}";
                    }

                    // 🎯 TENTAR BUSCAR ITENS DETALHADOS DO PROCESSO DE COMPRA
                    $itensDetalhados = [];
                    if ($cnpj && $ano && $sequencial) {
                        $itensDetalhados = $this->buscarItensDoProcessoDeCompra($cnpj, $ano, $sequencial, $termo);

                        if (!empty($itensDetalhados)) {
                            // ✅ Sucesso! Adicionar todos os itens detalhados encontrados
                            $numeroPregao = ($ano && $sequencial) ? "{$ano}/{$sequencial}" : null;

                            foreach ($itensDetalhados as $itemDetalhado) {
                                $itens[] = array_merge($itemDetalhado, [
                                    'orgao_nome' => $contrato['orgao_nome'] ?? 'Órgão não informado',
                                    'municipio' => $contrato['municipio_nome'] ?? 'N/A',
                                    'uf' => $contrato['uf'] ?? 'N/A',
                                    'numeroControlePNCP' => $numeroControlePNCP,
                                    'link_fonte' => $linkFonte,
                                    'data_publicacao' => $contrato['data_assinatura'] ?? $contrato['data_publicacao_pncp'] ?? null,
                                    'numero_pregao' => $numeroPregao, // ✅ NOVO: Número do pregão
                                    'numero_ata' => null, // ✅ NOVO: Número da ARP (se aplicável)
                                ]);
                            }
                            continue; // Pular para próximo contrato
                        }
                    }

                    // ⚠️ FALLBACK: Não encontrou itens detalhados, usar valor global
                    $descricao = $contrato['description'] ?? $contrato['title'] ?? 'Sem descrição';
                    $unidadeDetectada = $this->detectarUnidadeMedida($descricao);
                    $quantidadeDetectada = $this->detectarQuantidadeNaDescricao($descricao);

                    $valorUnitarioCalculado = $valorGlobal;
                    $descricaoFinal = $descricao;
                    $confiabilidade = 'baixa';

                    if ($quantidadeDetectada > 1) {
                        $valorUnitarioCalculado = $valorGlobal / $quantidadeDetectada;
                        $confiabilidade = 'media';
                    } else {
                        // 🚫 FILTRO: Descartar valores globais absurdos (>R$ 10.000)
                        // Esses são contratos sem detalhamento e valores não servem para cotação
                        if ($valorGlobal > 10000) {
                            continue; // Pular este contrato
                        }
                        $descricaoFinal = $descricao . ' ⚠️ (VALOR GLOBAL)';
                    }

                    $numeroPregao = ($ano && $sequencial) ? "{$ano}/{$sequencial}" : null;

                    $itens[] = [
                        'descricao' => $descricaoFinal,
                        'valor_unitario' => (float) $valorUnitarioCalculado,
                        'unidade_medida' => $unidadeDetectada,
                        'quantidade' => $quantidadeDetectada,
                        'orgao_nome' => $contrato['orgao_nome'] ?? 'Órgão não informado',
                        'municipio' => $contrato['municipio'] ?? 'N/A',
                        'uf' => $contrato['uf'] ?? 'N/A',
                        'numeroControlePNCP' => $numeroControlePNCP,
                        'link_fonte' => $linkFonte,
                        'tipo_origem' => 'contrato',
                        'confiabilidade' => $confiabilidade,
                        'valor_global' => $quantidadeDetectada <= 1,
                        'data_publicacao' => $contrato['data_assinatura'] ?? $contrato['data_publicacao_pncp'] ?? null,
                        'fonte' => 'PNCP_SEARCH',
                        'numero_pregao' => $numeroPregao, // ✅ NOVO: Número do pregão
                        'numero_ata' => null, // ✅ NOVO: Número da ARP (se aplicável)
                    ];
                }

            } catch (\Exception $e) {
                Log::warning('BuscarEmContratos: Erro na página', [
                    'pagina' => $pagina,
                    'erro' => $e->getMessage()
                ]);
                break;
            }

            // ✅ REMOVIDO SLEEP: Delay de 1s entre páginas deixava muito lento
            // Total: 3 páginas × 1s = 3s extras desnecessários
        }

        Log::info('BuscarEmContratos: Finalizado com API /search', [
            'termo' => $termo,
            'total_itens_encontrados' => count($itens)
        ]);

        return $itens;
    }

    /**
     * Buscar itens detalhados de um contrato PNCP com CNPJ, ano e sequencial separados
     * Versão otimizada que não precisa do numeroControlePNCP
     *
     * Endpoint: https://pncp.gov.br/api/pncp/v1/orgaos/{cnpj}/contratos/{ano}/{sequencial}/itens
     */
    private function buscarItensDoContratoComCNPJ($cnpj, $ano, $sequencial, $contrato = [])
    {
        try {
            $url = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpj}/contratos/{$ano}/{$sequencial}/itens";

            Log::info('🔍 Buscando itens do contrato PNCP (CNPJ direto)', [
                'url' => $url,
                'cnpj' => $cnpj,
                'ano' => $ano,
                'sequencial' => $sequencial
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'DattaTech-PNCP/1.0',
                    ])
                    ->connectTimeout(3)
                    ->timeout(8)
                    ->get($url);

            if (!$response->successful()) {
                Log::warning('⚠️ Erro ao buscar itens do contrato', [
                    'status' => $response->status(),
                    'cnpj' => $cnpj,
                    'ano' => $ano,
                    'sequencial' => $sequencial
                ]);
                return [];
            }

            $itensApi = $response->json();

            if (empty($itensApi)) {
                Log::info('⚠️ API retornou array vazio de itens', ['url' => $url]);
                return [];
            }

            // Processar itens retornados pela API
            $itensProcessados = [];

            foreach ($itensApi as $item) {
                $valorUnitario = (float) ($item['valorUnitario'] ?? $item['valorUnitarioEstimado'] ?? 0);
                $quantidade = (float) ($item['quantidade'] ?? 1);
                $unidade = $item['unidadeMedida'] ?? 'UN';
                $descricao = $item['descricao'] ?? $item['descricaoDetalhada'] ?? 'Item sem descrição';

                if ($valorUnitario <= 0) {
                    Log::debug('⚠️ Item com valor zero - pulando', ['descricao' => $descricao]);
                    continue; // Pular itens sem valor
                }

                $itensProcessados[] = [
                    'descricao' => $descricao,
                    'valor_unitario' => $valorUnitario,  // ✅ VALOR UNITÁRIO CORRETO!
                    'unidade' => $unidade,
                    'quantidade' => $quantidade,
                    'orgao' => $contrato['orgao_nome'] ?? 'Órgão não informado',
                    'municipio' => $contrato['municipio'] ?? 'N/A',
                    'uf' => $contrato['uf'] ?? 'N/A',
                    'tipo_origem' => 'item_contrato',
                    'confiabilidade' => 'alta',  // ✅ ALTA porque é valor unitário REAL!
                    'valor_global' => false,
                    'data_publicacao' => $contrato['data_assinatura'] ?? $contrato['data_publicacao_pncp'] ?? null,
                    'fonte' => 'PNCP_ITENS',
                    'catmat' => $item['codigoCATMAT'] ?? $item['materialOuServico'] ?? null,
                    'marca' => $item['marca'] ?? null
                ];
            }

            Log::info('✅ Itens processados do contrato (CNPJ direto)', [
                'total_itens' => count($itensProcessados),
                'cnpj' => $cnpj,
                'ano' => $ano,
                'sequencial' => $sequencial
            ]);

            return $itensProcessados;

        } catch (\Exception $e) {
            Log::error('❌ Erro ao buscar itens do contrato PNCP (CNPJ direto)', [
                'cnpj' => $cnpj,
                'ano' => $ano,
                'sequencial' => $sequencial,
                'erro' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 🎯 Buscar itens detalhados do PROCESSO DE COMPRA (não do contrato/empenho)
     *
     * DESCOBERTA CRÍTICA:
     * - Contratos/Empenhos NÃO têm endpoint /itens (retorna 404)
     * - Processos de compra TÊM endpoint /itens com valores unitários reais!
     *
     * Fluxo:
     * 1. Buscar contrato completo para obter numeroControlePncpCompra
     * 2. Extrair ano e sequencial da compra
     * 3. Buscar itens da compra original
     * 4. Filtrar itens que correspondem ao termo buscado
     *
     * Endpoint: https://pncp.gov.br/api/pncp/v1/orgaos/{cnpj}/compras/{ano}/{seq}/itens
     *
     * @param string $cnpj CNPJ do órgão
     * @param string $ano Ano do contrato
     * @param string $sequencial Sequencial do contrato
     * @param string $termo Termo buscado (para filtrar itens relevantes)
     * @return array Itens detalhados com valores unitários reais
     */
    private function buscarItensDoProcessoDeCompra($cnpj, $ano, $sequencial, $termo)
    {
        try {
            // 1️⃣ Buscar detalhes completos do contrato para obter numeroControlePncpCompra
            $urlContrato = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpj}/contratos/{$ano}/{$sequencial}";

            $responseContrato = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'DattaTech-PNCP/1.0',
                    ])
                    ->connectTimeout(2)
                    ->timeout(5)
                    ->get($urlContrato);

            if (!$responseContrato->successful()) {
                return [];
            }

            $contratoCompleto = $responseContrato->json();
            $numeroControlePncpCompra = $contratoCompleto['numeroControlePncpCompra'] ?? null;

            if (!$numeroControlePncpCompra) {
                return [];
            }

            // 2️⃣ Extrair ano e sequencial da compra
            // Formato: "76417005001743-1-000044/2024"
            //           {cnpj}-1-{seq}/{ano}
            if (!preg_match('#(\d{14})-1-(\d+)/(\d{4})#', $numeroControlePncpCompra, $matches)) {
                return [];
            }

            $cnpjCompra = $matches[1];
            $sequencialCompra = (int) $matches[2];
            $anoCompra = $matches[3];

            // 3️⃣ Buscar itens do processo de compra
            $urlItens = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpjCompra}/compras/{$anoCompra}/{$sequencialCompra}/itens";

            $responseItens = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'DattaTech-PNCP/1.0',
                    ])
                    ->connectTimeout(2)
                    ->timeout(8)
                    ->get($urlItens);

            if (!$responseItens->successful()) {
                return [];
            }

            $itensCompra = $responseItens->json();

            if (empty($itensCompra)) {
                return [];
            }

            // 4️⃣ Filtrar e processar itens que correspondem ao termo buscado
            $itensProcessados = [];
            $termoLower = mb_strtolower($termo);
            $palavrasTermo = preg_split('/\s+/', $termoLower);

            foreach ($itensCompra as $item) {
                $descricao = $item['descricao'] ?? '';
                $descricaoLower = mb_strtolower($descricao);

                // Verificar se o item corresponde ao termo (pelo menos 50% das palavras)
                $palavrasEncontradas = 0;
                foreach ($palavrasTermo as $palavra) {
                    if (strlen($palavra) > 2 && stripos($descricaoLower, $palavra) !== false) {
                        $palavrasEncontradas++;
                    }
                }

                $percentualMatch = count($palavrasTermo) > 0
                    ? ($palavrasEncontradas / count($palavrasTermo)) * 100
                    : 0;

                if ($percentualMatch < 50) {
                    continue; // Não é relevante para o termo buscado
                }

                $valorUnitario = (float) ($item['valorUnitarioEstimado'] ?? 0);
                $quantidade = (float) ($item['quantidade'] ?? 1);
                $unidade = $item['unidadeMedida'] ?? 'UN';

                if ($valorUnitario <= 0) {
                    continue;
                }

                $itensProcessados[] = [
                    'descricao' => $descricao,
                    'valor_unitario' => $valorUnitario,  // ✅ VALOR UNITÁRIO REAL!
                    'unidade_medida' => $unidade,
                    'quantidade' => $quantidade,
                    'tipo_origem' => 'item_compra',
                    'confiabilidade' => 'alta',  // ✅ ALTA - Valor unitário estimado do edital
                    'valor_global' => false,
                    'fonte' => 'PNCP_COMPRA',
                    'catmat' => $item['catalogoCodigoItem'] ?? null,
                ];
            }

            return $itensProcessados;

        } catch (\Exception $e) {
            Log::debug('Erro ao buscar itens do processo de compra', [
                'cnpj' => $cnpj,
                'ano' => $ano,
                'sequencial' => $sequencial,
                'erro' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Buscar itens detalhados de um contrato PNCP
     * Retorna array com itens contendo valores unitários corretos
     *
     * Endpoint: https://pncp.gov.br/api/pncp/v1/orgaos/{cnpj}/contratos/{ano}/{sequencial}/itens
     */
    private function buscarItensDoContrato($numeroControlePNCP)
    {
        try {
            // Extrair CNPJ, ano e sequencial do número de controle
            // Formato típico: 12345678901234-1-00001/2024
            if (!preg_match('/^(\d{14})-(\d+)-(\d+)\/(\d{4})$/', $numeroControlePNCP, $matches)) {
                Log::warning('Número de controle PNCP inválido', ['numero' => $numeroControlePNCP]);
                return [];
            }

            $cnpj = $matches[1];
            $ano = $matches[4];
            $sequencial = $matches[3];

            $url = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpj}/contratos/{$ano}/{$sequencial}/itens";

            Log::info('🔍 Buscando itens do contrato PNCP', [
                'url' => $url,
                'cnpj' => $cnpj,
                'ano' => $ano,
                'sequencial' => $sequencial
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'DattaTech-PNCP/1.0',
                    ])
                    ->connectTimeout(3)  // ✅ REDUZIDO: 3s ao invés de 5s
                    ->timeout(5)         // ✅ REDUZIDO: 5s ao invés de 10s
                    ->get($url);

            if (!$response->successful()) {
                Log::warning('Erro ao buscar itens do contrato', [
                    'status' => $response->status(),
                    'numero_controle' => $numeroControlePNCP
                ]);
                return [];
            }

            $itensApi = $response->json();

            if (empty($itensApi)) {
                return [];
            }

            // Processar itens retornados pela API
            $itensProcessados = [];

            foreach ($itensApi as $item) {
                $valorUnitario = (float) ($item['valorUnitario'] ?? $item['valorUnitarioEstimado'] ?? 0);
                $quantidade = (float) ($item['quantidade'] ?? 1);
                $unidade = $item['unidadeMedida'] ?? 'UN';
                $descricao = $item['descricao'] ?? $item['descricaoDetalhada'] ?? 'Item sem descrição';

                if ($valorUnitario <= 0) {
                    continue; // Pular itens sem valor
                }

                $itensProcessados[] = [
                    'descricao' => $descricao,
                    'valor_unitario' => $valorUnitario,  // ✅ VALOR UNITÁRIO CORRETO!
                    'unidade' => $unidade,
                    'quantidade' => $quantidade,
                    'orgao' => $item['nomeOrgao'] ?? 'Órgão não informado',
                    'municipio' => $item['municipio'] ?? 'N/A',
                    'uf' => $item['uf'] ?? 'N/A',
                    'numeroControlePNCP' => $numeroControlePNCP,
                    'modalidadeContratacao' => null,
                    'tipo_origem' => 'item_contrato',
                    'confiabilidade' => 'alta',  // ✅ ALTA porque é valor unitário REAL!
                    'valor_global' => false,
                    'data_publicacao' => $item['dataAssinatura'] ?? null,
                    'fonte' => 'PNCP_ITENS',
                    'catmat' => $item['codigoCATMAT'] ?? $item['materialOuServico'] ?? null,
                    'marca' => $item['marca'] ?? null
                ];
            }

            Log::info('✅ Itens processados do contrato', [
                'total_itens' => count($itensProcessados)
            ]);

            return $itensProcessados;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar itens do contrato PNCP', [
                'numero_controle' => $numeroControlePNCP,
                'erro' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Detectar unidade de medida pela descrição do item
     * SIMPLIFICADO: Retorna apenas CX (Caixa) ou UN (Unidade)
     */
    private function detectarUnidadeMedida($descricao)
    {
        $descricaoUpper = mb_strtoupper($descricao);

        // APENAS CAIXA - todas as variações retornam CX
        $palavrasCaixa = ['CAIXA', 'CAIXAS', 'CX'];

        foreach ($palavrasCaixa as $palavra) {
            if (strpos($descricaoUpper, $palavra) !== false) {
                return 'CX';
            }
        }

        // PADRÃO: Tudo que não for caixa = UNIDADE
        return 'UN';
    }

    /**
     * Detectar quantidade na descrição do contrato
     *
     * Exemplos:
     * - "Aquisição de 100 unidades de SSD" → 100
     * - "Compra de 50 caixas" → 50
     * - "10 unidades de SSD de 240GB" → 10
     * - "Aquisição de SSD" → 1 (não detectado)
     *
     * @param string $descricao Descrição do contrato
     * @return int Quantidade detectada (mínimo 1)
     */
    private function detectarQuantidadeNaDescricao($descricao)
    {
        $descricaoUpper = mb_strtoupper($descricao);

        // Padrões comuns:
        // "100 unidades", "50 caixas", "10 itens", "200 peças"
        // "Aquisição de 100", "Compra de 50"
        $patterns = [
            '/(\d+)\s+(UNIDADES?|CAIXAS?|ITENS?|PEÇAS?|PACOTES?|KG|UN|CX|PCT)/ui',
            '/(?:AQUISIÇÃO|COMPRA|FORNECIMENTO)\s+DE\s+(\d+)/ui',
            '/^(\d+)\s+/u', // Número no início
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $descricao, $matches)) {
                $quantidade = (int) $matches[1];

                // Validar se é um número razoável (entre 1 e 100.000)
                if ($quantidade >= 1 && $quantidade <= 100000) {
                    Log::debug('✅ Quantidade detectada', [
                        'descricao' => substr($descricao, 0, 80),
                        'quantidade' => $quantidade,
                        'pattern' => $pattern
                    ]);
                    return $quantidade;
                }
            }
        }

        // Não detectou quantidade → retornar 1
        return 1;
    }

    /**
     * Buscar em orçamentos locais como fallback
     */
    private function buscarEmOrcamentosLocais($termo)
    {
        try {
            // Buscar itens em orçamentos dos últimos 12 meses
            // Gerar preço fictício baseado em hash da descrição para ter dados de referência
            $itens = DB::table('itens_orcamento as io')
                ->join('orcamentos as o', 'io.orcamento_id', '=', 'o.id')
                ->where('io.descricao', 'ILIKE', '%' . $termo . '%')
                ->where('o.created_at', '>=', now()->subMonths(12))
                ->select(
                    'io.descricao',
                    'io.medida_fornecimento as unidade',
                    'io.quantidade',
                    'o.nome as orgao'
                )
                ->limit(100)
                ->get()
                ->toArray();

            if (empty($itens)) {
                return [];
            }

            // Converter para array
            // NOTA: Por enquanto, gera preços de referência baseados na quantidade (preço por unidade)
            // Futuramente, quando houver integração com cotações, usar preços reais
            $todosItens = array_map(function($item) {
                // Valor estimado: R$10 por unidade (genérico)
                // Isso é apenas para demonstração enquanto não há dados reais de preço
                $precoEstimado = 10.00;

                return [
                    'descricao' => $item->descricao,
                    'unidade' => $item->unidade,
                    'valor_unitario' => $precoEstimado,  // ✅ PADRONIZADO
                    'orgao' => $item->orgao ?? 'Orçamento Local'
                ];
            }, $itens);

            return $this->agruparECalcularEstatisticas($todosItens);

        } catch (\Exception $e) {
            Log::error('BuscarEmOrcamentosLocais: Erro', [
                'erro' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Agrupar itens e calcular estatísticas com inteligência de confiabilidade
     * NOVO: Retorna também amostras detalhadas para transparência total
     */
    private function agruparECalcularEstatisticas($todosItens)
    {
        $grupos = [];
        foreach ($todosItens as $item) {
            $descNormalizada = strtoupper(trim($item['descricao']));

            if (!isset($grupos[$descNormalizada])) {
                $grupos[$descNormalizada] = [
                    'descricao' => $item['descricao'],
                    'unidade' => $item['unidade'],
                    'valores' => [],
                    'orgaos' => [],
                    'confiabilidades' => [],
                    'tipo_origem' => $item['tipo_origem'] ?? 'contrato',
                    'amostras_detalhadas' => [] // NOVO: Array com detalhes de cada amostra
                ];
            }

            // Só adicionar valores válidos (> 0) - aceita 'valor_unitario' ou 'valor' (compatibilidade)
            $valorItem = $item['valor_unitario'] ?? $item['valor'] ?? 0;
            if ($valorItem > 0) {
                $grupos[$descNormalizada]['valores'][] = $valorItem;
                $grupos[$descNormalizada]['orgaos'][] = $item['orgao'];
                $grupos[$descNormalizada]['confiabilidades'][] = $item['confiabilidade'] ?? 'baixa';

                // NOVO: Guardar detalhes completos da amostra
                $grupos[$descNormalizada]['amostras_detalhadas'][] = [
                    'orgao' => $item['orgao'],
                    'valor_unitario' => $valorItem,  // ✅ PADRONIZADO
                    'unidade' => $item['unidade'] ?? 'UN',
                    'quantidade' => $item['quantidade'] ?? 1,
                    'confiabilidade' => $item['confiabilidade'] ?? 'baixa',
                    'data_publicacao' => $item['data_publicacao'] ?? null,
                    'numero_controle_pncp' => $item['numeroControlePNCP'] ?? null,
                    'tipo_origem' => $item['tipo_origem'] ?? 'contrato'
                ];
            }
        }

        // Calcular estatísticas
        $resultados = [];
        foreach ($grupos as $grupo) {
            if (count($grupo['valores']) > 0) {
                $valores = $grupo['valores'];
                sort($valores);

                // Determinar confiabilidade geral do grupo
                $confiabilidades = $grupo['confiabilidades'];
                $confiabilidade = 'baixa'; // default

                if (in_array('alta', $confiabilidades)) {
                    $confiabilidade = 'alta';
                } elseif (in_array('media', $confiabilidades)) {
                    $confiabilidade = 'media';
                }

                $resultados[] = [
                    'descricao' => $grupo['descricao'],
                    'unidade' => $grupo['unidade'],
                    'preco_minimo' => min($valores),
                    'preco_medio' => array_sum($valores) / count($valores),
                    'preco_maximo' => max($valores),
                    'quantidade_amostras' => count($valores),
                    'exemplo_orgao' => $grupo['orgaos'][0],
                    'confiabilidade' => $confiabilidade,
                    'tipo_origem' => $grupo['tipo_origem'],
                    'amostras_detalhadas' => $grupo['amostras_detalhadas'] // NOVO: Incluir amostras
                ];
            }
        }

        // PRIORIZAR RESULTADOS:
        // 1º: Alta confiabilidade
        // 2º: Média confiabilidade
        // 3º: Baixa confiabilidade
        // Dentro de cada grupo, ordenar por quantidade de amostras
        usort($resultados, function($a, $b) {
            $pesoConfiabilidade = [
                'alta' => 3,
                'media' => 2,
                'baixa' => 1
            ];

            $pesoA = $pesoConfiabilidade[$a['confiabilidade']] ?? 0;
            $pesoB = $pesoConfiabilidade[$b['confiabilidade']] ?? 0;

            if ($pesoA !== $pesoB) {
                return $pesoB - $pesoA; // Maior peso primeiro
            }

            // Se confiabilidade igual, ordenar por quantidade de amostras
            return $b['quantidade_amostras'] - $a['quantidade_amostras'];
        });

        // Retornar top 10 (aumentado de 5 para 10)
        return array_slice($resultados, 0, 10);
    }

    /**
     * Salvar novo item do orçamento
     */
    public function storeItem(Request $request, $id)
    {
        try {
            // Validar campos
            $validated = $request->validate([
                'descricao' => 'required|string',
                'medida_fornecimento' => 'required|string|max:50',
                'quantidade' => 'required|numeric|min:0.0001',
                'preco_unitario' => 'nullable|numeric|min:0', // ✅ ADICIONADO
                'indicacao_marca' => 'nullable|string|max:255',
                'tipo' => 'required|in:produto,servico',
                'alterar_cdf' => 'required|boolean',
                'lote_id' => 'nullable|exists:cp_lotes,id',
            ], [
                'descricao.required' => 'A descrição do item é obrigatória.',
                'medida_fornecimento.required' => 'A medida de fornecimento é obrigatória.',
                'quantidade.required' => 'A quantidade é obrigatória.',
                'quantidade.min' => 'A quantidade deve ser maior que zero.',
                'preco_unitario.numeric' => 'O preço unitário deve ser um número.',
                'preco_unitario.min' => 'O preço unitário não pode ser negativo.',
                'tipo.required' => 'Selecione se o item é produto ou serviço.',
            ]);

            // Buscar orçamento
            $orcamento = Orcamento::findOrFail($id);

            // Log para debug
            Log::info('StoreItem: Criando item com preço', [
                'orcamento_id' => $orcamento->id,
                'descricao' => substr($validated['descricao'], 0, 50),
                'quantidade' => $validated['quantidade'],
                'preco_unitario' => $validated['preco_unitario'] ?? 'NULL'
            ]);

            // Criar item COM preço unitário
            $item = OrcamentoItem::create([
                'orcamento_id' => $orcamento->id,
                'lote_id' => $validated['lote_id'] ?? null,
                'descricao' => $validated['descricao'],
                'medida_fornecimento' => $validated['medida_fornecimento'],
                'quantidade' => $validated['quantidade'],
                'preco_unitario' => $validated['preco_unitario'] ?? null, // ✅ ADICIONADO
                'indicacao_marca' => $validated['indicacao_marca'] ?? null,
                'tipo' => $validated['tipo'],
                'alterar_cdf' => $validated['alterar_cdf'],
            ]);

            Log::info('StoreItem: Item criado com sucesso', [
                'item_id' => $item->id,
                'preco_unitario_salvo' => $item->preco_unitario
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item adicionado com sucesso!',
                'item' => $item,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar novo lote
     */
    public function storeLote(Request $request, $id)
    {
        try {
            // Validar campos
            $validated = $request->validate([
                'numero' => 'required|integer|min:1',
                'nome' => 'required|string|max:255',
            ], [
                'numero.required' => 'O número do lote é obrigatório.',
                'numero.min' => 'O número do lote deve ser maior que 0.',
                'nome.required' => 'O nome do lote é obrigatório.',
            ]);

            // Buscar orçamento
            $orcamento = Orcamento::findOrFail($id);

            // Verificar se já existe lote com esse número neste orçamento
            $existeLote = Lote::where('orcamento_id', $orcamento->id)
                ->where('numero', $validated['numero'])
                ->exists();

            if ($existeLote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um lote com este número neste orçamento.'
                ], 422);
            }

            // Criar lote
            $lote = Lote::create([
                'orcamento_id' => $orcamento->id,
                'numero' => $validated['numero'],
                'nome' => $validated['nome'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lote criado com sucesso!',
                'lote' => $lote,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar lote: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar lote: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar item existente (GAP #1)
     */
    public function updateItem(Request $request, $id, $item_id)
    {
        try {
            // Validar campos
            $validated = $request->validate([
                'descricao' => 'required|string',
                'medida_fornecimento' => 'required|string|max:50',
                'quantidade' => 'required|numeric|min:0.0001',
                'tipo_quantidade' => 'nullable|in:inteiro,fracionado',
                'tipo_marca' => 'nullable|in:referencia,vinculativa',
                'tipo' => 'required|in:produto,servico',
                'alterar_cdf' => 'required|boolean',
                'lote_id' => 'nullable|exists:cp_lotes,id',
            ], [
                'descricao.required' => 'A descrição do item é obrigatória.',
                'medida_fornecimento.required' => 'A medida de fornecimento é obrigatória.',
                'quantidade.required' => 'A quantidade é obrigatória.',
                'quantidade.min' => 'A quantidade deve ser maior que zero.',
                'tipo.required' => 'Selecione se o item é produto ou serviço.',
            ]);

            // Buscar item (verificando se pertence ao orçamento correto)
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Atualizar item
            $item->update([
                'lote_id' => $validated['lote_id'] ?? null,
                'descricao' => $validated['descricao'],
                'medida_fornecimento' => $validated['medida_fornecimento'],
                'quantidade' => $validated['quantidade'],
                'tipo_quantidade' => $validated['tipo_quantidade'] ?? 'inteiro',
                'tipo_marca' => $validated['tipo_marca'] ?? 'referencia',
                'tipo' => $validated['tipo'],
                'alterar_cdf' => $validated['alterar_cdf'],
            ]);

            Log::info('Item atualizado com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'descricao' => $validated['descricao']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item atualizado com sucesso!',
                'item' => $item->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar APENAS os dados do fornecedor do item (sem validar outros campos)
     */
    public function updateItemFornecedor(Request $request, $id, $item_id)
    {
        try {
            // Validar apenas campos do fornecedor
            $validated = $request->validate([
                'fornecedor_nome' => 'nullable|string|max:255',
                'fornecedor_cnpj' => 'nullable|string|max:18',
            ]);

            // Buscar item (verificando se pertence ao orçamento correto)
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Atualizar APENAS os campos do fornecedor
            $item->update([
                'fornecedor_nome' => $validated['fornecedor_nome'] ?? null,
                'fornecedor_cnpj' => $validated['fornecedor_cnpj'] ?? null,
            ]);

            Log::info('Fornecedor do item atualizado com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'fornecedor_nome' => $validated['fornecedor_nome'] ?? null,
                'fornecedor_cnpj' => $validated['fornecedor_cnpj'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fornecedor atualizado com sucesso!',
                'item' => $item->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar fornecedor do item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar fornecedor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar APENAS os dados de críticas do item (checkboxes da análise crítica)
     */
    public function updateItemCriticas(Request $request, $id, $item_id)
    {
        try {
            // Validar campos booleanos das críticas
            $validated = $request->validate([
                'medidas_desiguais' => 'required|boolean',
                'valores_discrepantes' => 'required|boolean',
            ]);

            // Buscar item (verificando se pertence ao orçamento correto)
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Montar JSON com os dados das críticas
            $criticasDados = [
                'medidas_desiguais' => $validated['medidas_desiguais'],
                'valores_discrepantes' => $validated['valores_discrepantes'],
                'atualizado_em' => now()->format('Y-m-d H:i:s'),
            ];

            // Atualizar APENAS o campo criticas_dados
            $item->update([
                'criticas_dados' => json_encode($criticasDados),
            ]);

            Log::info('Críticas do item atualizadas com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'criticas' => $criticasDados,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Críticas atualizadas com sucesso!',
                'criticas' => $criticasDados,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar críticas do item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar críticas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Excluir item (GAP #2)
     */
    public function destroyItem($id, $item_id)
    {
        try {
            // Buscar item (verificando se pertence ao orçamento correto)
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            $descricao = $item->descricao;

            // Excluir item (soft delete)
            $item->delete();

            Log::info('Item excluído com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'descricao' => $descricao
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item excluído com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renumerar item do orçamento
     */
    public function renumerarItem(Request $request, $id, $item_id)
    {
        try {
            // Validar novo número
            $validated = $request->validate([
                'novo_numero' => 'required|integer|min:1'
            ], [
                'novo_numero.required' => 'O novo número do item é obrigatório.',
                'novo_numero.integer' => 'O número deve ser um valor inteiro.',
                'novo_numero.min' => 'O número deve ser maior que zero.'
            ]);

            // Buscar item (verificando se pertence ao orçamento correto)
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            $numeroAnterior = $item->numero_item;
            $numeroNovo = $validated['novo_numero'];

            // Verificar se já existe um item com este número neste orçamento
            $existe = OrcamentoItem::where('orcamento_id', $id)
                ->where('numero_item', $numeroNovo)
                ->where('id', '!=', $item_id)
                ->first();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => "Já existe um item com o número {$numeroNovo} neste orçamento. Escolha outro número."
                ], 422);
            }

            // Atualizar número do item
            $item->numero_item = $numeroNovo;
            $item->save();

            Log::info('Item renumerado com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'numero_anterior' => $numeroAnterior,
                'numero_novo' => $numeroNovo,
                'descricao' => $item->descricao
            ]);

            return response()->json([
                'success' => true,
                'message' => "Item renumerado com sucesso! De {$numeroAnterior} para {$numeroNovo}.",
                'item' => $item
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erro ao renumerar item: ' . $e->getMessage(), [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao renumerar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar amostras selecionadas no item do orçamento
     */
    public function salvarAmostras(Request $request, $id, $item_id)
    {
        try {
            // 🔧 CORRIGIR: FormData envia 'amostras' como STRING JSON, não array
            $amostrasRaw = $request->input('amostras');

            // Se for string, fazer parse JSON
            if (is_string($amostrasRaw)) {
                $amostrasArray = json_decode($amostrasRaw, true);

                // Verificar se o JSON é válido
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON inválido: ' . json_last_error_msg());
                }

                // Substituir no request para a validação funcionar
                $request->merge(['amostras' => $amostrasArray]);
            }

            Log::info('📊 Salvando amostras selecionadas', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'amostras_tipo' => gettype($request->amostras),
                'num_amostras' => is_array($request->amostras) ? count($request->amostras) : 0
            ]);

            // Buscar item
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Validar dados
            $validated = $request->validate([
                'amostras' => 'required|array|min:1',
                'justificativa' => 'nullable|string|max:5000'
            ]);

            // Salvar amostras e justificativa
            $item->amostras_selecionadas = json_encode($validated['amostras']);
            $item->justificativa_cotacao = $validated['justificativa'] ?? null;
            $item->save();

            Log::info('✅ Amostras salvas com sucesso', [
                'item_id' => $item_id,
                'num_amostras' => count($validated['amostras'])
            ]);

            // 📊 GRAVAR LOG DE AUDITORIA (SNAPSHOT) - Com proteção para não quebrar fluxo principal
            try {
                $numAmostras = count($validated['amostras']);
                $numValidas = 0;
                $numExpurgadas = 0;

                // Contar amostras válidas e expurgadas
                foreach ($validated['amostras'] as $amostra) {
                    if (isset($amostra['situacao']) && $amostra['situacao'] === 'VALIDA') {
                        $numValidas++;
                    } else {
                        $numExpurgadas++;
                    }
                }

                // ✅ CRÍTICO: Usar explicitamente a mesma conexão do ItemOrcamento
                // para garantir que está no banco correto do tenant
                $connectionName = $item->getConnectionName();

                \App\Models\AuditLogItem::on($connectionName)->create([
                    'item_id' => $item_id,
                    'usuario_id' => auth()->id(),  // ✅ CORRIGIDO: era user_id
                    'usuario_nome' => auth()->user()->name ?? 'Sistema',
                    'event_type' => 'snapshot_created',
                    'sample_number' => null,
                    'before_value' => null,
                    'after_value' => json_encode([
                        'total_amostras' => $numAmostras,
                        'amostras_validas' => $numValidas,
                        'amostras_expurgadas' => $numExpurgadas,
                        'preco_mediana' => $item->preco_unitario ?? 0,
                        // ✅ MOVIDO: metadata para dentro de after_value (campo metadata não existe)
                        'amostras' => array_map(function($amostra) {
                            return [
                                'descricao' => $amostra['descricao'] ?? '',
                                'valor' => $amostra['valor_unitario'] ?? 0,
                                'fonte' => $amostra['fonte'] ?? '',
                                'situacao' => $amostra['situacao'] ?? 'VALIDA'
                            ];
                        }, $validated['amostras'])
                    ]),
                    'rule_applied' => 'Snapshot automático ao concluir cotação',
                    'justification' => $validated['justificativa'] ?? 'Snapshot gerado automaticamente ao salvar amostras selecionadas'
                ]);

                Log::info('📊 Log de auditoria (snapshot) gravado com sucesso', [
                    'item_id' => $item_id,
                    'event_type' => 'snapshot_created',
                    'connection' => $connectionName,
                    'database' => config("database.connections.{$connectionName}.database")
                ]);

            } catch (\Exception $auditError) {
                // ⚠️ IMPORTANTE: Se der erro no log de auditoria, apenas logar mas NÃO quebrar o fluxo
                Log::warning('⚠️ Erro ao gravar log de auditoria (não crítico)', [
                    'item_id' => $item_id,
                    'erro' => $auditError->getMessage(),
                    'connection' => $connectionName ?? 'N/A',
                    'database' => config("database.connections.{$connectionName}.database") ?? 'N/A'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Amostras salvas com sucesso!',
                'num_amostras' => count($validated['amostras'])
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao salvar amostras: ' . $e->getMessage(), [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar amostras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter amostras selecionadas de um item
     */
    public function obterAmostras($id, $item_id)
    {
        try {
            Log::info('📊 Buscando amostras selecionadas', [
                'orcamento_id' => $id,
                'item_id' => $item_id
            ]);

            // Buscar item
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Decodificar amostras JSON
            $amostras = $item->amostras_selecionadas
                ? json_decode($item->amostras_selecionadas, true)
                : [];

            Log::info('✅ Amostras obtidas', [
                'item_id' => $item_id,
                'num_amostras' => count($amostras)
            ]);

            return response()->json([
                'success' => true,
                'amostras' => $amostras,
                'justificativa' => $item->justificativa_cotacao,
                'item' => [
                    'id' => $item->id,
                    'descricao' => $item->descricao,
                    'preco_unitario' => $item->preco_unitario
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao obter amostras: ' . $e->getMessage(), [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter amostras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar metodologias do orçamento (GAP #3 - Seção 2)
     */
    public function updateMetodologias(Request $request, $id)
    {
        try {
            // Validar campos
            $validated = $request->validate([
                'metodo_juizo_critico' => 'required|in:saneamento_desvio_padrao,saneamento_percentual',
                'metodo_obtencao_preco' => 'required|in:media_mediana,mediana_todas,media_todas,menor_preco',
                'casas_decimais' => 'required|in:duas,quatro',
            ], [
                'metodo_juizo_critico.required' => 'Selecione um método de juízo crítico.',
                'metodo_obtencao_preco.required' => 'Selecione um método de obtenção do preço.',
                'casas_decimais.required' => 'Selecione o padrão de casas decimais.',
            ]);

            // Buscar orçamento
            $orcamento = Orcamento::findOrFail($id);

            // Atualizar metodologias
            $orcamento->update($validated);

            Log::info('Metodologias atualizadas com sucesso', [
                'orcamento_id' => $id,
                'metodologias' => $validated
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Metodologias salvas com sucesso!',
                'orcamento' => [
                    'metodo_juizo_critico' => $orcamento->metodo_juizo_critico,
                    'metodo_obtencao_preco' => $orcamento->metodo_obtencao_preco,
                    'casas_decimais' => $orcamento->casas_decimais,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar metodologias: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar metodologias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar planilha de itens
     */
    public function importPlanilha(Request $request, $id)
    {
        try {
            // DEBUG: Log do que está chegando
            Log::info('ImportPlanilha: Request recebido', [
                'has_file' => $request->hasFile('planilha'),
                'all_files' => $request->allFiles(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method()
            ]);

            // Validar arquivo (sem validação de MIME por causa do proxy)
            $request->validate([
                'planilha' => 'required|file|max:10240',
            ], [
                'planilha.required' => 'Selecione um arquivo para importar.',
                'planilha.max' => 'O arquivo não pode ter mais de 10MB.',
            ]);

            // Buscar orçamento
            $orcamento = Orcamento::findOrFail($id);

            // Obter arquivo
            $arquivo = $request->file('planilha');

            // Processar planilha e extrair itens
            $result = $this->processarPlanilhaExcel($arquivo, $orcamento->id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'itens_importados' => $result['itens_importados'],
                'itens_com_erro' => $result['itens_com_erro'] ?? 0
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao importar planilha: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar planilha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processar documento (Excel ou PDF) e extrair itens
     */
    private function processarDocumento($arquivo)
    {
        $extensao = strtolower($arquivo->getClientOriginalExtension());

        if (in_array($extensao, ['xlsx', 'xls'])) {
            return $this->processarExcel($arquivo);
        } elseif ($extensao === 'pdf') {
            return $this->processarPDF($arquivo);
        }

        throw new \Exception('Tipo de arquivo não suportado: ' . $extensao);
    }

    /**
     * 🧠 DETECÇÃO INTELIGENTE DE COLUNAS
     * Analisa o CONTEÚDO da planilha estatisticamente para identificar colunas
     * NÃO depende de nomes de cabeçalho ou posições fixas
     */
    private function detectarColunasInteligente($worksheet, $highestRow)
    {
        Log::info('🧠 Iniciando detecção inteligente de colunas');

        // Unidades conhecidas de medida
        $unidadesConhecidas = [
            'unidade', 'un', 'und', 'kg', 'g', 'mg', 'l', 'ml', 'metro', 'm', 'cm', 'mm',
            'caixa', 'cx', 'pacote', 'pct', 'fardo', 'peça', 'pc', 'par', 'jogo', 'conjunto',
            'litro', 'quilo', 'grama', 'tonelada', 'ton', 'resma', 'bloco', 'rolo', 'galão',
            'kit', 'unid', 'unid.', 'un.', 'und.', 'pç', 'pçs', 'dz', 'duzia'
        ];

        // Encontrar linha inicial com dados (pular linhas vazias ou de títulos)
        $primeiraLinhaComDados = null;
        $headerRow = null;
        $melhorCabecalho = ['row' => null, 'score' => 0];

        // NOVA LÓGICA: Analisar primeiras 10 linhas e escolher o MELHOR cabeçalho
        for ($row = 1; $row <= min(10, $highestRow); $row++) {
            $countNaoVazias = 0;
            $countNumericos = 0;
            $countTextosLongos = 0;
            $countPalavrasChave = 0;

            for ($col = 'A'; $col <= 'J'; $col++) {
                $valor = trim($worksheet->getCell($col . $row)->getCalculatedValue() ?? '');
                if (!empty($valor)) {
                    $countNaoVazias++;

                    // Contar numéricos
                    if (is_numeric(str_replace([',', '.'], ['', ''], $valor))) {
                        $countNumericos++;
                    }

                    // Contar textos longos (provavelmente descrições de produtos)
                    if (strlen($valor) > 30) {
                        $countTextosLongos++;
                    }
                }
            }

            // Se tem pelo menos 3 colunas preenchidas, pode ser cabeçalho ou dados
            if ($countNaoVazias >= 3) {
                // Verificar se parece cabeçalho (tem palavras-chave)
                // CORREÇÃO: Analisar TODAS as colunas, não apenas A-J
                $textoLinha = '';
                $highestColumnTemp = $worksheet->getHighestColumn();
                $highestColIndexTemp = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumnTemp);

                for ($colIndexTemp = 1; $colIndexTemp <= min(30, $highestColIndexTemp); $colIndexTemp++) {
                    $colTemp = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndexTemp);
                    $textoLinha .= ' ' . strtolower($worksheet->getCell($colTemp . $row)->getCalculatedValue() ?? '');
                }

                // Palavras-chave específicas valem mais pontos
                $palavrasEspecificas = ['item', 'descrição', 'descricao', 'quantidade', 'unidade', 'média', 'media', 'aritmetica', 'aritmética'];
                $palavrasGenericas = ['preço', 'preco', 'valor', 'total'];

                foreach ($palavrasEspecificas as $palavra) {
                    if (strpos($textoLinha, $palavra) !== false) {
                        $countPalavrasChave += 2; // Valem 2 pontos
                    }
                }
                foreach ($palavrasGenericas as $palavra) {
                    if (strpos($textoLinha, $palavra) !== false) {
                        $countPalavrasChave += 1; // Valem 1 ponto
                    }
                }

                // 🧠 LÓGICA INTELIGENTE: Só trata como cabeçalho se:
                // 1. Tem PELO MENOS score 4 (ex: 2 específicas, ou 1 específica + 2 genéricas)
                // 2. E NÃO tem maioria de valores numéricos
                // 3. Textos longos são permitidos em cabeçalhos (ex: "HISTÓRICO DE PREÇOS")
                $temTextoTipicoHeader = (
                    stripos($textoLinha, 'nome do') !== false ||
                    stripos($textoLinha, 'de fornecimento') !== false ||
                    stripos($textoLinha, 'marca de') !== false ||
                    stripos($textoLinha, 'histórico') !== false ||
                    stripos($textoLinha, 'historico') !== false
                );

                // Aceitar se: score alto (≥4) OU score médio (≥3) com textos típicos de header
                $ehCabecalho = (
                    (
                        $countPalavrasChave >= 4 ||
                        ($countPalavrasChave >= 3 && $temTextoTipicoHeader)
                    ) &&
                    $countNumericos < ($countNaoVazias * 0.5)
                );

                Log::info("🔍 Analisando linha $row: palavras-chave=$countPalavrasChave, numéricos=$countNumericos/$countNaoVazias, textos longos=$countTextosLongos");

                if ($ehCabecalho) {
                    // Escolher o cabeçalho com MAIOR score
                    if ($countPalavrasChave > $melhorCabecalho['score']) {
                        $melhorCabecalho = ['row' => $row, 'score' => $countPalavrasChave];
                        Log::info("📋 Linha $row é candidata a cabeçalho (score: $countPalavrasChave)");
                    }
                } elseif (!$primeiraLinhaComDados && $countNumericos > 0) {
                    // Primeira linha com dados suficientes = início dos dados
                    $primeiraLinhaComDados = $row;
                    Log::info("📊 Linha $row parece ser DADOS");
                }
            }
        }

        // Usar o melhor cabeçalho encontrado
        if ($melhorCabecalho['row']) {
            $headerRow = $melhorCabecalho['row'];
            $primeiraLinhaComDados = $headerRow + 1;
            Log::info("✅ MELHOR CABEÇALHO: Linha $headerRow (score: {$melhorCabecalho['score']})");
        }

        if (!$primeiraLinhaComDados) {
            $primeiraLinhaComDados = 1;
            $headerRow = 0;
            Log::info("📊 Nenhum cabeçalho ou dados encontrados nas primeiras 100 linhas - usando linha 1");
        }

        Log::info("📊 Primeira linha com dados: $primeiraLinhaComDados");

        // Estatísticas por coluna
        // CORREÇÃO: Analisar TODAS as colunas até o fim real da planilha
        $highestColumn = $worksheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // 🎯 NOVA LÓGICA: Tentar detectar colunas por NOME do cabeçalho PRIMEIRO
        $colunasDetectadasPorNome = [
            'item_numero' => null,
            'descricao' => null,
            'quantidade' => null,
            'unidade' => null,
            'preco_unitario' => null,
            'preco_total' => null,
        ];

        if ($headerRow) {
            Log::info("🔍 Tentando detectar colunas por NOME do cabeçalho (linha $headerRow)");

            for ($colIndex = 1; $colIndex <= $highestColIndex; $colIndex++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $headerValue = strtolower(trim($worksheet->getCell($col . $headerRow)->getCalculatedValue() ?? ''));

                // Mapear por palavras-chave nos cabeçalhos - VERSÃO MELHORADA
                // 🔍 ITEM / NÚMERO
                if (preg_match('/^(item|nº|num|numero|número|n°|n|cod|código|codigo)$/i', $headerValue)) {
                    $colunasDetectadasPorNome['item_numero'] = $col;
                    Log::info("  ✅ ITEM encontrado na coluna $col: '$headerValue'");
                }
                // 🔍 DESCRIÇÃO (+ variações comuns)
                elseif (preg_match('/^(descri[cç][ãa]o|especifica[cç][ãa]o|objeto|produto|material|nome\s+do\s+(produto|item|material))$/iu', $headerValue) ||
                        preg_match('/^(descri[cç][ãa]o|especifica[cç][ãa]o|detalhamento)\s+(do\s+|da\s+)?(produto|item|material|objeto)/iu', $headerValue) ||
                        preg_match('/(descri[cç][ãa]o\s+(resumida|completa|detalhada))/iu', $headerValue)) {
                    $colunasDetectadasPorNome['descricao'] = $col;
                    Log::info("  ✅ DESCRIÇÃO encontrada na coluna $col: '$headerValue'");
                }
                // 🔍 UNIDADE (+ abreviações comuns)
                elseif (preg_match('/^(unid|un|und|medida|unidade|u\.m\.|unid\.|un\.|und\.|um)$/iu', $headerValue) ||
                        preg_match('/^(unidade|medida)\s+de\s+(fornecimento|medida)/iu', $headerValue) ||
                        preg_match('/^(und|un|unid)[\s\.-]/iu', $headerValue)) {
                    $colunasDetectadasPorNome['unidade'] = $col;
                    Log::info("  ✅ UNIDADE encontrada na coluna $col: '$headerValue'");
                }
                // 🔍 QUANTIDADE (+ variações)
                elseif (preg_match('/^(qtd|quantidade|qtde|quant|qty|q|qte)\.?$/iu', $headerValue) ||
                        preg_match('/^quantidade\s+(solicitada|estimada|total|prevista)/iu', $headerValue) ||
                        preg_match('/(^|[\s\.])(qtd|qtde)[\s\.]/iu', $headerValue)) {
                    $colunasDetectadasPorNome['quantidade'] = $col;
                    Log::info("  ✅ QUANTIDADE encontrada na coluna $col: '$headerValue'");
                }
                // 🔍 PREÇO UNITÁRIO (+ formas alternativas)
                elseif (preg_match('/(m[eé]dia\s+aritm[eé]tica|m[eé]dia|media|aritm[eé]tica)/iu', $headerValue) ||
                        preg_match('/(pre[cç]o\s+unit[aá]rio|valor\s+unit[aá]rio|vlr\.?\s*unit[aá]rio?)/iu', $headerValue) ||
                        preg_match('/(pre[cç]o\s+unit|valor\s+unit|vlr\.?\s*unit)\.?$/iu', $headerValue) ||
                        preg_match('/^(unit[aá]rio|vlr\s*unit|pre[cç]o\/un)$/iu', $headerValue)) {
                    $colunasDetectadasPorNome['preco_unitario'] = $col;
                    Log::info("  ✅ PREÇO UNITÁRIO encontrado na coluna $col: '$headerValue'");
                }
                // 🔍 PREÇO TOTAL (+ formas alternativas)
                elseif (preg_match('/(^total$|^valor\s+total$|^pre[cç]o\s+total$|^vlr\.?\s*total$)/iu', $headerValue) ||
                        preg_match('/(total\s+geral|valor\s+estimado|pre[cç]o\s+estimado)/iu', $headerValue)) {
                    $colunasDetectadasPorNome['preco_total'] = $col;
                    Log::info("  ✅ PREÇO TOTAL encontrado na coluna $col: '$headerValue'");
                }
            }
        }

        // Analisar até 100 linhas de amostra ou até 20 itens encontrados
        $amostras = min(100, $highestRow - $primeiraLinhaComDados + 1);
        $linhasAnalisadas = 0;
        $itensEncontrados = 0;

        $estatisticas = [];

        for ($colIndex = 1; $colIndex <= $highestColIndex; $colIndex++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $estatisticas[$col] = [
                'numericos' => 0,
                'textos' => 0,
                'vazios' => 0,
                'valores_numericos' => [],
                'valores_texto' => [],
                'tamanho_medio_texto' => 0,
                'eh_unidade_conhecida' => 0,
                'parece_item_numero' => 0,
            ];
        }

        Log::info("📊 Analisando colunas de A até $highestColumn (total: $highestColIndex colunas)");

        // Coletar amostras
        for ($row = $primeiraLinhaComDados; $row <= min($primeiraLinhaComDados + $amostras, $highestRow); $row++) {
            $temDadoNaLinha = false;

            // CORREÇÃO: Analisar TODAS as colunas
            for ($colIndex = 1; $colIndex <= $highestColIndex; $colIndex++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $cell = $worksheet->getCell($col . $row);
                $valor = $cell->getCalculatedValue(); // Usar valor calculado para pegar resultado de fórmulas

                if ($valor === null || $valor === '') {
                    $estatisticas[$col]['vazios']++;
                } else {
                    $temDadoNaLinha = true;
                    $valorStr = trim(strval($valor));

                    // Verificar se é numérico
                    if (is_numeric($valor)) {
                        $estatisticas[$col]['numericos']++;
                        $estatisticas[$col]['valores_numericos'][] = (float) $valor;
                    } else {
                        $estatisticas[$col]['textos']++;

                        // Verificar se é unidade conhecida
                        // CORREÇÃO: Aceitar SOMENTE se for exatamente a unidade (sem outras palavras)
                        // Isso evita confundir "CAIXA DE SOM" com unidade "CAIXA"
                        $valorLower = strtolower(trim($valorStr));
                        foreach ($unidadesConhecidas as $un) {
                            // Exato match OU starts with unidade + espaço (ex: "UN " ou "KG 500")
                            if ($valorLower === $un || $valorLower === $un . '.' || strpos($valorLower, $un . ' ') === 0) {
                                $estatisticas[$col]['eh_unidade_conhecida']++;
                                break;
                            }
                        }

                        // Verificar se parece número de item (ex: "01/001", "1", "ITEM 1")
                        if (preg_match('/^\d+[\/\-\.]\d+$/', $valorStr) || preg_match('/^item\s*\d+/i', $valorStr) || preg_match('/^\d+$/', $valorStr)) {
                            $estatisticas[$col]['parece_item_numero']++;
                        }

                        $estatisticas[$col]['valores_texto'][] = $valorStr;
                        $estatisticas[$col]['tamanho_medio_texto'] += strlen($valorStr);
                    }
                }
            }

            if ($temDadoNaLinha) {
                $linhasAnalisadas++;
                $itensEncontrados++;

                if ($itensEncontrados >= 20) {
                    break; // Parar após analisar 20 itens
                }
            }
        }

        // Calcular médias
        foreach ($estatisticas as $col => &$stats) {
            if ($stats['textos'] > 0) {
                $stats['tamanho_medio_texto'] = $stats['tamanho_medio_texto'] / $stats['textos'];
            }
        }

        Log::info("📈 Estatísticas coletadas de $linhasAnalisadas linhas");

        // DEBUG: Mostrar estatísticas de cada coluna (incluindo textos!)
        foreach ($estatisticas as $col => $stats) {
            if ($stats['numericos'] > 0 || $stats['textos'] > 0) {
                $valoresTexto = array_slice($stats['valores_texto'], 0, 3);
                $valoresNum = array_slice($stats['valores_numericos'], 0, 5);
                Log::info("Coluna $col: numéricos={$stats['numericos']}, textos={$stats['textos']}, vazios={$stats['vazios']}, tam_medio=" . round($stats['tamanho_medio_texto'], 1) . ", valores_num=" . json_encode($valoresNum) . ", valores_txt=" . json_encode($valoresTexto));
            }
        }

        // CLASSIFICAR COLUNAS BASEADO NAS ESTATÍSTICAS
        $colunas = [
            'item_numero' => null,
            'descricao' => null,
            'quantidade' => null,
            'unidade' => null,
            'preco_unitario' => null,
            'preco_total' => null,
        ];

        // 1. DETECTAR COLUNA DE ITEM/NÚMERO
        $melhorItem = ['col' => null, 'score' => 0];
        foreach ($estatisticas as $col => $stats) {
            $score = $stats['parece_item_numero'] * 10;
            if ($score > $melhorItem['score']) {
                $melhorItem = ['col' => $col, 'score' => $score];
            }
        }
        if ($melhorItem['score'] > 0) {
            $colunas['item_numero'] = $melhorItem['col'];
            Log::info("🔢 Coluna ITEM/NÚMERO: {$melhorItem['col']} (score: {$melhorItem['score']})");
        }

        // 2. DETECTAR COLUNA DE DESCRIÇÃO (textos com maior tamanho médio)
        // MUDANÇA CRÍTICA: Detectar DESCRIÇÃO ANTES de UNIDADE
        // Isso evita que descrições sejam confundidas com unidades
        $melhorDescricao = ['col' => null, 'score' => 0];
        foreach ($estatisticas as $col => $stats) {
            if ($col === $colunas['item_numero']) continue;

            // Usar tamanho médio como score (quanto maior, melhor)
            // Descrições geralmente têm 3+ caracteres (ex: "MOUSE", "TECLADO")
            // MUDANÇA: Reduzido de >= 8 para >= 3 para capturar descrições curtas
            if ($stats['textos'] > 0 && $stats['tamanho_medio_texto'] >= 3) {
                $score = $stats['tamanho_medio_texto'];

                if ($score > $melhorDescricao['score']) {
                    $melhorDescricao = ['col' => $col, 'score' => $score];
                }
            }
        }
        if ($melhorDescricao['score'] > 0) {
            $colunas['descricao'] = $melhorDescricao['col'];
            Log::info("📝 Coluna DESCRIÇÃO: {$melhorDescricao['col']} (tamanho médio: " . round($melhorDescricao['score']) . " caracteres)");
        }

        // 3. DETECTAR COLUNA DE UNIDADE
        $melhorUnidade = ['col' => null, 'score' => 0];
        foreach ($estatisticas as $col => $stats) {
            if ($col === $colunas['item_numero'] || $col === $colunas['descricao']) continue; // Pular colunas já usadas

            // CORREÇÃO: Score alto SOMENTE se detectou unidades conhecidas
            // Unidades devem corresponder a palavras-chave conhecidas (UN, KG, UNIDADE, etc)
            $score = $stats['eh_unidade_conhecida'] * 10;

            // Bonus adicional SOMENTE se já tem unidades conhecidas
            if ($score > 0 && $stats['textos'] > 0 && $stats['tamanho_medio_texto'] > 0 && $stats['tamanho_medio_texto'] < 15) {
                $score += 5;
            }

            // DEBUG: mostrar score de unidade
            if ($score > 0 || ($stats['textos'] > 0 && $stats['tamanho_medio_texto'] > 0 && $stats['tamanho_medio_texto'] < 15)) {
                Log::info("Analisando coluna $col para UNIDADE: eh_unidade={$stats['eh_unidade_conhecida']}, score=$score, textos={$stats['textos']}, tam_medio=" . round($stats['tamanho_medio_texto'], 1));
            }

            if ($score > $melhorUnidade['score']) {
                $melhorUnidade = ['col' => $col, 'score' => $score];
            }
        }
        if ($melhorUnidade['score'] > 0) {
            $colunas['unidade'] = $melhorUnidade['col'];
            Log::info("📏 Coluna UNIDADE: {$melhorUnidade['col']} (score: {$melhorUnidade['score']})");
        }

        // 4. DETECTAR COLUNAS NUMÉRICAS (quantidade e preços)
        $colunasNumericas = [];
        foreach ($estatisticas as $col => $stats) {
            if (in_array($col, array_values($colunas))) continue; // Pular já usadas

            if ($stats['numericos'] >= $linhasAnalisadas * 0.3 && count($stats['valores_numericos']) > 0) { // Pelo menos 30% numéricos
                $min = min($stats['valores_numericos']);
                $max = max($stats['valores_numericos']);
                $media = array_sum($stats['valores_numericos']) / count($stats['valores_numericos']);

                // Calcular quantos valores têm decimais (indica preço)
                $comDecimais = 0;
                foreach ($stats['valores_numericos'] as $val) {
                    if (floor($val) != $val) { // Tem decimal
                        $comDecimais++;
                    }
                }
                $percentualDecimais = ($comDecimais / count($stats['valores_numericos'])) * 100;

                // Calcular amplitude (variação)
                $amplitude = $max - $min;

                // Score para identificar tipo
                $scoreQuantidade = 0;
                $scorePreco = 0;

                // Critério 1: Valores decimais (preços geralmente têm .50, .80, etc)
                if ($percentualDecimais > 50) {
                    $scorePreco += 30; // Muito provável ser preço
                } else {
                    $scoreQuantidade += 20; // Mais provável ser quantidade
                }

                // Critério 2: Amplitude relativa à média
                if ($media > 0) {
                    $amplitudeRelativa = ($amplitude / $media) * 100;
                    if ($amplitudeRelativa > 200) {
                        $scoreQuantidade += 15; // Quantidades variam muito (1, 10, 500)
                    }
                }

                // Critério 3: Valores muito altos (>1000) provavelmente são preços totais
                if ($media > 1000) {
                    $scorePreco += 25; // Preço total
                } elseif ($media > 100) {
                    $scoreQuantidade += 10; // Quantidade pode ser alta
                }

                $colunasNumericas[] = [
                    'col' => $col,
                    'min' => $min,
                    'max' => $max,
                    'media' => $media,
                    'count' => count($stats['valores_numericos']),
                    'percentual_decimais' => $percentualDecimais,
                    'amplitude' => $amplitude,
                    'score_quantidade' => $scoreQuantidade,
                    'score_preco' => $scorePreco,
                ];
            }
        }

        // Separar por tipo baseado em scores
        $candidatasQuantidade = [];
        $candidatasPreco = [];

        Log::info('📊 Analisando ' . count($colunasNumericas) . ' colunas numéricas para classificação');

        foreach ($colunasNumericas as $colNum) {
            Log::info("Coluna {$colNum['col']}: scoreQtd={$colNum['score_quantidade']}, scorePreco={$colNum['score_preco']}, média={$colNum['media']}, decimais={$colNum['percentual_decimais']}%");

            if ($colNum['score_quantidade'] > $colNum['score_preco']) {
                $candidatasQuantidade[] = $colNum;
                Log::info("  → Classificada como QUANTIDADE");
            } else {
                $candidatasPreco[] = $colNum;
                Log::info("  → Classificada como PREÇO");
            }
        }

        Log::info("Resultado: " . count($candidatasQuantidade) . " candidatas a QUANTIDADE, " . count($candidatasPreco) . " candidatas a PREÇO");

        // Se não conseguiu separar bem, usar fallback por média
        if (empty($candidatasQuantidade) || empty($candidatasPreco)) {
            usort($colunasNumericas, function($a, $b) {
                return $a['media'] <=> $b['media'];
            });

            // Dividir: primeira metade = quantidade, segunda = preços
            $meio = floor(count($colunasNumericas) / 2);
            $candidatasQuantidade = array_slice($colunasNumericas, 0, max(1, $meio));
            $candidatasPreco = array_slice($colunasNumericas, max(1, $meio));
        }

        // Ordenar candidatas por confiança
        usort($candidatasQuantidade, function($a, $b) {
            return ($b['score_quantidade'] - $b['score_preco']) <=> ($a['score_quantidade'] - $a['score_preco']);
        });

        usort($candidatasPreco, function($a, $b) {
            return $a['media'] <=> $b['media']; // Preços: menor primeiro (unitário < total)
        });

        // Atribuir colunas usando classificação inteligente
        if (!empty($candidatasQuantidade)) {
            $colunas['quantidade'] = $candidatasQuantidade[0]['col'];
            Log::info("🔢 Coluna QUANTIDADE: {$candidatasQuantidade[0]['col']} (média: " . round($candidatasQuantidade[0]['media'], 2) . ", score: {$candidatasQuantidade[0]['score_quantidade']})");
        }

        if (!empty($candidatasPreco)) {
            // Primeira = preço unitário (menor média entre preços)
            $colunas['preco_unitario'] = $candidatasPreco[0]['col'];
            Log::info("💰 Coluna PREÇO UNITÁRIO: {$candidatasPreco[0]['col']} (média: R$ " . number_format($candidatasPreco[0]['media'], 2, ',', '.') . ", decimais: " . round($candidatasPreco[0]['percentual_decimais']) . "%)");
        }

        if (count($candidatasPreco) >= 2) {
            // Segunda = preço total (maior média entre preços)
            $colunas['preco_total'] = $candidatasPreco[1]['col'];
            Log::info("💵 Coluna PREÇO TOTAL: {$candidatasPreco[1]['col']} (média: R$ " . number_format($candidatasPreco[1]['media'], 2, ',', '.') . ")");
        }

        // Fallback: Se não encontrou descrição, usar coluna com MAIOR tamanho médio de texto
        if (!$colunas['descricao']) {
            $melhorColDescricao = null;
            $maxTamanhoMedio = 0;

            foreach ($estatisticas as $col => $stats) {
                if ($stats['textos'] > 0 && $stats['tamanho_medio_texto'] > $maxTamanhoMedio && !in_array($col, array_values($colunas))) {
                    $melhorColDescricao = $col;
                    $maxTamanhoMedio = $stats['tamanho_medio_texto'];
                }
            }

            if ($melhorColDescricao) {
                $colunas['descricao'] = $melhorColDescricao;
                Log::info("📝 Coluna DESCRIÇÃO (fallback): $melhorColDescricao (tamanho médio: " . round($maxTamanhoMedio) . " chars)");
            }
        }

        // Fallback: Se não encontrou quantidade, usar primeira numérica não usada
        if (!$colunas['quantidade']) {
            foreach ($estatisticas as $col => $stats) {
                if ($stats['numericos'] > 0 && !in_array($col, array_values($colunas))) {
                    $colunas['quantidade'] = $col;
                    Log::info("🔢 Coluna QUANTIDADE (fallback): $col");
                    break;
                }
            }
        }

        // 🎯 Usar detecções por nome do cabeçalho APENAS como fallback (se análise estatística não encontrou)
        foreach ($colunasDetectadasPorNome as $tipo => $colDetectada) {
            if ($colDetectada && !$colunas[$tipo]) {
                $colunas[$tipo] = $colDetectada;
                Log::info("🎯 Usando coluna detectada por NOME (fallback) para $tipo: $colDetectada");
            } else if ($colDetectada && $colunas[$tipo]) {
                Log::info("⏭️ Ignorando coluna detectada por NOME para $tipo ($colDetectada) - já temos coluna melhor pela análise estatística ($colunas[$tipo])");
            }
        }

        // Garantir que pelo menos descrição e quantidade existam
        if (!$colunas['descricao']) $colunas['descricao'] = 'A';
        if (!$colunas['quantidade']) $colunas['quantidade'] = 'B';
        // REMOVIDO fallback de unidade = 'C' pois pode conflitar com descrição

        Log::info("✅ COLUNAS FINAIS DETECTADAS:", $colunas);

        return [
            'headerRow' => $headerRow,
            'colunas' => $colunas,
            'metodo' => count(array_filter($colunasDetectadasPorNome)) > 0 ? 'deteccao_por_nome_cabecalho' : 'analise_estatistica_inteligente',
            'linhas_analisadas' => $linhasAnalisadas,
        ];
    }

    /**
     * Processar planilha Excel e extrair itens
     */
    private function processarExcel($arquivo)
    {
        try {
            $spreadsheet = IOFactory::load($arquivo->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $itens = [];

            Log::info('🔍 DETECÇÃO INTELIGENTE DE PLANILHA', [
                'total_linhas' => $highestRow,
                'planilha' => $worksheet->getTitle()
            ]);

            // NOVA ABORDAGEM: Detecção inteligente por análise de conteúdo
            $deteccao = $this->detectarColunasInteligente($worksheet, $highestRow);

            $headerRow = $deteccao['headerRow'];
            $colunas = $deteccao['colunas'];

            Log::info('✅ Colunas identificadas inteligentemente', [
                'header_linha' => $headerRow,
                'colunas' => $colunas,
                'metodo' => $deteccao['metodo']
            ]);

            // Ler dados das linhas
            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $descricao = trim($worksheet->getCell($colunas['descricao'] . $row)->getCalculatedValue() ?? '');

                // Pular linhas vazias ou linhas de totais
                if (empty($descricao) || stripos($descricao, 'total') !== false || stripos($descricao, 'lote') !== false) {
                    continue;
                }

                // Quantidade
                $quantidade = $worksheet->getCell($colunas['quantidade'] . $row)->getCalculatedValue();
                if (!is_numeric($quantidade)) {
                    // Tentar converter de formato brasileiro (5,445 -> 5.445)
                    $quantidade = str_replace(',', '.', $quantidade);
                    if (!is_numeric($quantidade)) {
                        $quantidade = 1;
                    }
                }

                // Unidade
                $unidade = '';
                if ($colunas['unidade']) {
                    $unidade = trim($worksheet->getCell($colunas['unidade'] . $row)->getCalculatedValue() ?? '');
                }

                // Preço unitário
                $precoUnitario = null;
                if ($colunas['preco_unitario']) {
                    $precoUnitario = $worksheet->getCell($colunas['preco_unitario'] . $row)->getCalculatedValue();
                    if (!empty($precoUnitario) && is_numeric($precoUnitario)) {
                        $precoUnitario = (float) $precoUnitario;
                    } else {
                        // Tentar converter de formato brasileiro
                        $precoUnitario = str_replace(',', '.', str_replace('.', '', $precoUnitario));
                        $precoUnitario = is_numeric($precoUnitario) ? (float) $precoUnitario : null;
                    }
                }

                // Preço total
                $precoTotal = null;
                if ($colunas['preco_total']) {
                    $precoTotal = $worksheet->getCell($colunas['preco_total'] . $row)->getCalculatedValue();
                    if (!empty($precoTotal) && is_numeric($precoTotal)) {
                        $precoTotal = (float) $precoTotal;
                    } else {
                        // Tentar converter de formato brasileiro
                        $precoTotal = str_replace(',', '.', str_replace('.', '', $precoTotal));
                        $precoTotal = is_numeric($precoTotal) ? (float) $precoTotal : null;
                    }
                }

                $itens[] = [
                    'descricao' => $descricao,
                    'quantidade' => (float) $quantidade,
                    'unidade' => !empty($unidade) ? strtoupper($unidade) : 'UNIDADE',
                    'preco_unitario' => $precoUnitario,
                    'preco_total' => $precoTotal,
                ];
            }

            Log::info('Itens extraídos do Excel', ['total' => count($itens)]);

            return $itens;

        } catch (\Exception $e) {
            Log::error('Erro ao processar Excel: ' . $e->getMessage());
            throw new \Exception('Erro ao processar planilha Excel: ' . $e->getMessage());
        }
    }

    /**
     * Processar PDF e extrair itens
     */
    private function processarPDF($arquivo)
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($arquivo->getPathname());
            $texto = $pdf->getText();

            Log::info('Processando PDF', [
                'tamanho_texto' => strlen($texto),
                'preview' => substr($texto, 0, 200)
            ]);

            $itens = [];
            $linhas = explode("\n", $texto);

            // Tentar extrair padrões de tabela
            // Padrão esperado: [Número] [Descrição] [Quantidade] [Unidade]
            foreach ($linhas as $linha) {
                $linha = trim($linha);

                // Pular linhas vazias ou muito curtas
                if (strlen($linha) < 10) {
                    continue;
                }

                // Tentar extrair usando regex simples
                // Exemplo: "1 CADEIRA PLÁSTICA 50 UN"
                if (preg_match('/^(\d+)?\s*([A-Za-zÀ-ú\s\-\/]+?)\s+(\d+[\.,]?\d*)\s+([A-Z]{1,10})$/i', $linha, $matches)) {
                    $descricao = trim($matches[2]);
                    $quantidade = (float) str_replace(',', '.', $matches[3]);
                    $unidade = strtoupper(trim($matches[4]));

                    $itens[] = [
                        'descricao' => $descricao,
                        'quantidade' => $quantidade,
                        'unidade' => $unidade,
                    ];
                }
                // Padrão alternativo: apenas descrição (assumir qtd=1, unidade=UNIDADE)
                elseif (preg_match('/^(\d+)?\s*([A-Za-zÀ-ú\s\-\/]{10,})$/i', $linha, $matches)) {
                    $descricao = trim($matches[2]);

                    // Evitar adicionar cabeçalhos ou texto genérico
                    if (!preg_match('/(item|descrição|quantidade|unidade|total|valor|página)/i', $descricao)) {
                        $itens[] = [
                            'descricao' => $descricao,
                            'quantidade' => 1,
                            'unidade' => 'UNIDADE',
                        ];
                    }
                }
            }

            Log::info('Itens extraídos do PDF', ['total' => count($itens)]);

            if (empty($itens)) {
                throw new \Exception('Não foi possível extrair itens do PDF. Verifique se o documento contém uma tabela com descrições de itens.');
            }

            return $itens;

        } catch (\Exception $e) {
            Log::error('Erro ao processar PDF: ' . $e->getMessage());
            throw new \Exception('Erro ao processar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Helper para labels dos métodos
     */
    private function getMetodoObtencaoPrecoLabel($metodo)
    {
        $labels = [
            'media_mediana' => 'Média ou mediana a partir do coeficiente de variação',
            'mediana_todas' => 'Mediana aplicada a todas as amostras',
            'media_todas' => 'Média aritmética aplicada a todas as amostras',
            'menor_preco' => 'Menor preço aplicado a todas as amostras',
        ];

        return $labels[$metodo] ?? $metodo;
    }

    /**
     * Processar planilha Excel e importar itens
     */
    private function processarPlanilhaExcel($arquivo, $orcamentoId)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($arquivo->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();

            // Obter range real de dados (ignora células vazias no início/fim)
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            Log::info('ImportPlanilha: Dimensões', [
                'highestRow' => $highestRow,
                'highestColumn' => $highestColumn,
                'highestColumnIndex' => $highestColumnIndex
            ]);

            // Converter planilha para array MANTENDO índices de colunas corretos
            $rows = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($colIndex = 0; $colIndex < $highestColumnIndex; $colIndex++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                    $cell = $worksheet->getCell($colLetter . $row);

                    // Tentar obter valor calculado, se falhar pegar valor bruto
                    try {
                        $cellValue = $cell->getCalculatedValue();
                    } catch (\Exception $e) {
                        $cellValue = $cell->getValue();
                    }

                    $rowData[$colIndex] = $cellValue;
                }
                $rows[] = $rowData;
            }

            if (empty($rows)) {
                throw new \Exception('A planilha está vazia.');
            }

            // Encontrar primeira linha não-vazia
            $primeiraLinhaNaoVazia = 0;
            foreach ($rows as $index => $row) {
                if (!empty(array_filter($row, fn($v) => !empty(trim($v))))) {
                    $primeiraLinhaNaoVazia = $index;
                    break;
                }
            }

            Log::info('ImportPlanilha: Primeira linha não-vazia', ['linha' => $primeiraLinhaNaoVazia + 1]);

            // Primeira linha - pode ser cabeçalho OU dados
            $header = array_map(fn($v) => trim(strtolower($v ?? '')), $rows[$primeiraLinhaNaoVazia]);

            Log::info('ImportPlanilha: Header detectado', ['header' => $header]);

            // Tentar detectar se primeira linha é cabeçalho ou dados
            $temCabecalho = !empty(array_filter($header));
            $columnMap = [];
            $startRow = $primeiraLinhaNaoVazia + 1; // Por padrão, começar após primeira linha

            if ($temCabecalho) {
                // Mapear colunas usando o cabeçalho
                $columnMap = $this->detectarColunas($header);
            }

            // Se não encontrou mapeamento válido pelo cabeçalho, tentar detectar por CONTEÚDO
            if (empty($columnMap) || !isset($columnMap['descricao'])) {
                Log::info('ImportPlanilha: Cabeçalho não identificado, tentando detectar por conteúdo...');

                // Analisar primeiras 5 linhas de dados a partir da primeira não-vazia
                $amostras = array_slice($rows, $primeiraLinhaNaoVazia, min(5, count($rows) - $primeiraLinhaNaoVazia));
                $columnMap = $this->detectarColunasPorConteudo($amostras);

                // Se primeira linha parece dados, incluir ela também
                if (!$temCabecalho || $this->linhaPareceDados($rows[$primeiraLinhaNaoVazia])) {
                    $startRow = $primeiraLinhaNaoVazia; // Começar da primeira linha não-vazia
                }

                Log::info('ImportPlanilha: Detecção por conteúdo', [
                    'columnMap' => $columnMap,
                    'startRow' => $startRow
                ]);
            } else {
                Log::info('ImportPlanilha: Mapa de colunas via cabeçalho', ['columnMap' => $columnMap]);
            }

            // Validar que pelo menos temos coluna de descrição
            if (!isset($columnMap['descricao'])) {
                throw new \Exception('Não foi possível identificar as colunas da planilha automaticamente. Certifique-se de que há uma coluna com descrições de itens.');
            }

            $itensImportados = 0;
            $erros = [];

            // Processar linhas a partir da linha inicial
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Pular linhas vazias
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Extrair dados da linha usando o mapa de colunas
                    $dados = $this->extrairDadosLinha($row, $columnMap);

                    // 🔧 VALIDAÇÃO APRIMORADA: Pular linhas que NÃO são itens válidos
                    $descricao = trim($dados['descricao'] ?? '');

                    // Pular se descrição vazia
                    if (empty($descricao)) {
                        $erros[] = "Linha " . ($i + 1) . ": Descrição vazia (pulada)";
                        Log::info("ImportPlanilha: Linha " . ($i + 1) . " pulada - descrição vazia", ['row' => $row]);
                        continue;
                    }

                    // Pular linhas com palavras-chave de totalizadores/subtotais
                    $descricaoLower = strtolower($descricao);
                    $palavrasExcluir = [
                        'total', 'subtotal', 'sub total', 'sub-total',
                        'soma', 'valor total', 'preço total', 'preco total',
                        'total geral', 'grand total', 'totalizador',
                        'lote', 'grupo', 'categoria', 'secção', 'seção'
                    ];

                    $devePular = false;
                    foreach ($palavrasExcluir as $palavra) {
                        if (stripos($descricaoLower, $palavra) !== false) {
                            $devePular = true;
                            $erros[] = "Linha " . ($i + 1) . ": Linha de totalizador/cabeçalho detectada (pulada): \"$descricao\"";
                            Log::info("ImportPlanilha: Linha " . ($i + 1) . " pulada - totalizador: \"$descricao\"");
                            break;
                        }
                    }

                    if ($devePular) {
                        continue;
                    }

                    // 🔧 CONVERSÃO APRIMORADA de valores numéricos (formato brasileiro)
                    $quantidade = $dados['quantidade'] ?? 1;
                    $precoUnitario = $dados['preco_unitario'] ?? null;

                    // Converter quantidade se vier como string no formato brasileiro (ex: "1.500,50")
                    if (!is_numeric($quantidade)) {
                        // Remover pontos (separadores de milhar) e trocar vírgula por ponto
                        $quantidade = str_replace('.', '', $quantidade);
                        $quantidade = str_replace(',', '.', $quantidade);
                        $quantidade = is_numeric($quantidade) ? floatval($quantidade) : 1;
                    }

                    // Converter preço unitário se vier como string no formato brasileiro
                    if ($precoUnitario !== null && !is_numeric($precoUnitario)) {
                        // Remover "R$", espaços, pontos (separadores de milhar) e trocar vírgula por ponto
                        $precoUnitario = str_replace(['R$', ' '], '', $precoUnitario);
                        $precoUnitario = str_replace('.', '', $precoUnitario);
                        $precoUnitario = str_replace(',', '.', $precoUnitario);
                        $precoUnitario = is_numeric($precoUnitario) ? floatval($precoUnitario) : null;
                    }

                    // Criar item
                    OrcamentoItem::create([
                        'orcamento_id' => $orcamentoId,
                        'lote_id' => null, // Pode ser melhorado depois
                        'descricao' => $descricao,
                        'medida_fornecimento' => $dados['medida_fornecimento'] ?? 'UNIDADE',
                        'quantidade' => floatval($quantidade),
                        'preco_unitario' => $precoUnitario,
                        'indicacao_marca' => $dados['indicacao_marca'] ?? null,
                        'tipo' => $dados['tipo'] ?? 'produto',
                        'alterar_cdf' => $dados['alterar_cdf'] ?? false,
                    ]);

                    $itensImportados++;

                    Log::info("ImportPlanilha: Item importado", [
                        'linha' => $i + 1,
                        'descricao' => substr($descricao, 0, 50),
                        'quantidade' => $quantidade,
                        'preco_unitario' => $precoUnitario
                    ]);

                } catch (\Exception $e) {
                    $erros[] = "Linha " . ($i + 1) . ": Erro ao salvar - " . $e->getMessage();
                    Log::warning('Erro ao importar linha ' . ($i + 1), [
                        'erro' => $e->getMessage(),
                        'linha' => $row,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // 🔧 VALIDAÇÃO: Se houver erros CRÍTICOS (não apenas linhas puladas), REJEITAR tudo
            $errosCriticos = array_filter($erros, function($erro) {
                // Erros críticos são aqueles que NÃO são apenas linhas puladas
                return stripos($erro, 'pulada') === false && stripos($erro, 'Descrição vazia') === false;
            });

            if (!empty($errosCriticos)) {
                // ❌ TEM ERROS CRÍTICOS - REVERTER IMPORTAÇÃO
                Log::error('ImportPlanilha: Erros críticos encontrados - revertendo importação', [
                    'erros_criticos' => $errosCriticos,
                    'itens_importados' => $itensImportados
                ]);

                // Deletar todos os itens que foram importados nesta sessão (últimos 10 segundos)
                OrcamentoItem::where('orcamento_id', $orcamentoId)
                    ->where('created_at', '>=', now()->subSeconds(10))
                    ->delete();

                throw new \Exception(
                    'Importação cancelada devido a erros críticos. ' .
                    count($errosCriticos) . ' linhas com erro: ' . implode('; ', array_slice($errosCriticos, 0, 3))
                );
            }

            $mensagem = "{$itensImportados} itens importados com sucesso!";
            if (!empty($erros)) {
                $mensagem .= " " . count($erros) . " linhas puladas (totalizadores/vazias).";
                Log::info('ImportPlanilha: Linhas puladas (não são erros)', ['linhas_puladas' => count($erros)]);
            }

            return [
                'itens_importados' => $itensImportados,
                'itens_com_erro' => count($erros),
                'message' => $mensagem,
                'erros' => $erros
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar planilha Excel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Detectar colunas da planilha automaticamente via cabeçalho
     */
    private function detectarColunas($header)
    {
        $map = [];

        foreach ($header as $index => $coluna) {
            $coluna = strtolower(trim($coluna));

            // Detectar DESCRIÇÃO (incluindo "objeto")
            if (preg_match('/(descri[çc][aã]o|item|nome|produto|servi[çc]o|especifica[çc][aã]o|objeto)/iu', $coluna)) {
                $map['descricao'] = $index;
            }

            // Detectar UNIDADE
            if (preg_match('/(unidade|^un$|^und$|medida|un\.|unid)/iu', $coluna)) {
                $map['medida_fornecimento'] = $index;
            }

            // Detectar QUANTIDADE
            if (preg_match('/(quantidade|^qtd$|qtde|quant|qty|^qt$)/iu', $coluna)) {
                $map['quantidade'] = $index;
            }

            // Detectar MARCA
            if (preg_match('/(marca|indica[çc][aã]o.*marca|fabricante|refer[eê]ncia)/iu', $coluna)) {
                $map['indicacao_marca'] = $index;
            }

            // Detectar TIPO
            if (preg_match('/(tipo|categoria|class|classifica[çc][aã]o)/iu', $coluna)) {
                $map['tipo'] = $index;
            }

            // Detectar PREÇO UNITÁRIO (ampliado)
            if (preg_match('/(pre[çc]o.*unit[aá]rio|valor.*unit[aá]rio|unit[aá]rio|vlr.*unit|^pre[çc]o$|^valor$|^r\$$|^rs$|unit.*price)/iu', $coluna)) {
                $map['preco_unitario'] = $index;
            }

            // Detectar PREÇO TOTAL
            if (preg_match('/(pre[çc]o.*total|valor.*total|total|vlr.*total)/iu', $coluna)) {
                $map['preco_total'] = $index;
            }
        }

        return $map;
    }

    /**
     * Detectar colunas analisando o CONTEÚDO das células (sem cabeçalho)
     */
    private function detectarColunasPorConteudo($amostras)
    {
        $map = [];
        $numColunas = 0;

        // Descobrir número de colunas
        foreach ($amostras as $row) {
            $numColunas = max($numColunas, count($row));
        }

        // Analisar cada coluna
        for ($col = 0; $col < $numColunas; $col++) {
            $valores = [];
            foreach ($amostras as $row) {
                if (isset($row[$col]) && !empty(trim($row[$col]))) {
                    $valores[] = trim($row[$col]);
                }
            }

            if (empty($valores)) continue;

            // Calcular estatísticas da coluna
            $numericos = 0;
            $textos = 0;
            $textosLongos = 0; // > 20 caracteres
            $textosMedios = 0; // entre 10-20 caracteres (pode ser descrição curta)
            $textosCurtos = 0; // < 10 caracteres
            $unidadesComuns = 0;

            foreach ($valores as $valor) {
                if (is_numeric($valor)) {
                    $numericos++;
                } else {
                    $textos++;
                    $len = strlen($valor);

                    if ($len > 20) {
                        $textosLongos++;
                    } elseif ($len >= 10) {
                        $textosMedios++;
                    } else {
                        $textosCurtos++;
                    }

                    // Checar se parece unidade de medida
                    $valorUpper = strtoupper($valor);
                    if (in_array($valorUpper, ['UN', 'UND', 'UNIDADE', 'KG', 'G', 'L', 'ML', 'M', 'CM', 'M²', 'M³', 'CX', 'CAIXA', 'PC', 'PCT', 'PACOTE', 'RESMA', 'FRASCO'])) {
                        $unidadesComuns++;
                    }
                }
            }

            $total = count($valores);

            // REGRA 1: Coluna com textos longos ou médios = DESCRIÇÃO
            // Aceita textos de 10+ caracteres como possível descrição
            // OU textos curtos se for a única coluna de texto (ex: "TECLADO", "MOUSE")
            $textosDescritivos = $textosLongos + $textosMedios;
            if ($textosDescritivos >= ($total * 0.5) && $numericos == 0 && !isset($map['descricao'])) {
                $map['descricao'] = $col;
                Log::info("ImportPlanilha: Coluna $col identificada como DESCRIÇÃO (textos longos=$textosLongos, médios=$textosMedios)");
                continue;
            }
            // REGRA 1.1: Se maioria são textos (mesmo curtos) e não tem descrição ainda = DESCRIÇÃO
            if ($textos >= ($total * 0.7) && $numericos == 0 && !isset($map['descricao'])) {
                $map['descricao'] = $col;
                Log::info("ImportPlanilha: Coluna $col identificada como DESCRIÇÃO (maioria textos=$textos)");
                continue;
            }

            // REGRA 2: Coluna numérica = QUANTIDADE (prioriza números pequenos)
            if ($numericos >= ($total * 0.8) && !isset($map['quantidade'])) {
                // Verificar se são números pequenos (quantidade típica)
                $numerosGrandes = 0;
                foreach ($valores as $valor) {
                    if (is_numeric($valor) && floatval($valor) > 1000) {
                        $numerosGrandes++;
                    }
                }

                // Se maioria são números pequenos, provavelmente é quantidade
                if ($numerosGrandes < ($numericos * 0.3)) {
                    $map['quantidade'] = $col;
                    Log::info("ImportPlanilha: Coluna $col identificada como QUANTIDADE");
                    continue;
                }
            }

            // REGRA 3: Coluna com unidades comuns = MEDIDA
            if ($unidadesComuns >= ($total * 0.5) && !isset($map['medida_fornecimento'])) {
                $map['medida_fornecimento'] = $col;
                Log::info("ImportPlanilha: Coluna $col identificada como MEDIDA");
                continue;
            }

            // REGRA 4: Coluna com textos curtos (não unidade) = MARCA
            if ($textosCurtos >= ($total * 0.6) && $unidadesComuns < ($total * 0.3) && !isset($map['indicacao_marca'])) {
                $map['indicacao_marca'] = $col;
                Log::info("ImportPlanilha: Coluna $col identificada como MARCA");
                continue;
            }

            // REGRA 5: Coluna numérica com valores médios/altos = PREÇO UNITÁRIO
            // (não foi identificada como quantidade, então provavelmente é preço)
            if ($numericos >= ($total * 0.7) && !isset($map['preco_unitario']) && !isset($map['quantidade'])) {
                $map['preco_unitario'] = $col;
                Log::info("ImportPlanilha: Coluna $col identificada como PREÇO UNITÁRIO");
                continue;
            }
        }

        // Se ainda não identificou descrição, usar primeira coluna com texto
        if (!isset($map['descricao'])) {
            for ($col = 0; $col < $numColunas; $col++) {
                $primeiroValor = '';
                foreach ($amostras as $row) {
                    if (isset($row[$col]) && !empty(trim($row[$col]))) {
                        $primeiroValor = trim($row[$col]);
                        break;
                    }
                }

                if (!empty($primeiroValor) && !is_numeric($primeiroValor)) {
                    $map['descricao'] = $col;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Verificar se uma linha parece dados (não cabeçalho)
     */
    private function linhaPareceDados($row)
    {
        $valorNaoVazio = null;

        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                $valorNaoVazio = trim($cell);
                break;
            }
        }

        if ($valorNaoVazio === null) {
            return false; // Linha vazia
        }

        // Se primeiro valor não-vazio é número, provavelmente são dados
        if (is_numeric($valorNaoVazio)) {
            return true;
        }

        // Se tem mais de 30 caracteres, provavelmente é descrição (dados)
        if (strlen($valorNaoVazio) > 30) {
            return true;
        }

        // Palavras-chave de cabeçalho
        $palavrasCabecalho = ['descricao', 'descrição', 'item', 'quantidade', 'qtd', 'unidade', 'un', 'marca', 'tipo'];
        $valorLower = strtolower($valorNaoVazio);

        if (in_array($valorLower, $palavrasCabecalho)) {
            return false; // É cabeçalho
        }

        // Por padrão, assumir que são dados
        return true;
    }

    /**
     * Extrair dados da linha usando mapa de colunas
     */
    private function extrairDadosLinha($row, $columnMap)
    {
        $dados = [];

        // Descrição (obrigatório)
        $dados['descricao'] = isset($columnMap['descricao'])
            ? trim($row[$columnMap['descricao']] ?? '')
            : '';

        // Medida de fornecimento
        $dados['medida_fornecimento'] = isset($columnMap['medida_fornecimento'])
            ? strtoupper(trim($row[$columnMap['medida_fornecimento']] ?? 'UNIDADE'))
            : 'UNIDADE';

        // Quantidade
        $quantidade = isset($columnMap['quantidade'])
            ? $row[$columnMap['quantidade']] ?? 1
            : 1;
        $dados['quantidade'] = is_numeric($quantidade) ? floatval($quantidade) : 1;

        // Preço unitário
        $preco = isset($columnMap['preco_unitario'])
            ? $row[$columnMap['preco_unitario']] ?? null
            : null;
        $dados['preco_unitario'] = ($preco !== null && is_numeric($preco)) ? floatval($preco) : null;

        // 🔧 SE NÃO TEM PREÇO UNITÁRIO mas tem PREÇO TOTAL, calcular
        if ($dados['preco_unitario'] === null && isset($columnMap['preco_total'])) {
            $precoTotal = $row[$columnMap['preco_total']] ?? null;
            if ($precoTotal !== null && is_numeric($precoTotal) && $dados['quantidade'] > 0) {
                $dados['preco_unitario'] = floatval($precoTotal) / $dados['quantidade'];
            }
        }

        // Indicação de marca
        $dados['indicacao_marca'] = isset($columnMap['indicacao_marca'])
            ? trim($row[$columnMap['indicacao_marca']] ?? '')
            : null;

        // Tipo (produto ou serviço)
        if (isset($columnMap['tipo'])) {
            $tipoRaw = strtolower(trim($row[$columnMap['tipo']] ?? ''));
            $dados['tipo'] = in_array($tipoRaw, ['produto', 'servico', 'serviço'])
                ? ($tipoRaw === 'servico' || $tipoRaw === 'serviço' ? 'servico' : 'produto')
                : 'produto';
        } else {
            $dados['tipo'] = 'produto';
        }

        // Alterar CDF
        $dados['alterar_cdf'] = false;

        return $dados;
    }

    /**
     * Importar documento e criar orçamento automaticamente
     * Detecta colunas, extrai itens e preenche orçamento
     */
    public function importarDocumento(Request $request)
    {
        Log::info('ImportarDocumento: Requisição recebida', [
            'has_file' => $request->hasFile('documento'),
            'all_files' => $request->allFiles(),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method()
        ]);

        try {
            // Log do arquivo recebido
            if ($request->hasFile('documento')) {
                $file = $request->file('documento');
                Log::info('Arquivo recebido:', [
                    'nome' => $file->getClientOriginalName(),
                    'tamanho' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'extensao' => $file->getClientOriginalExtension()
                ]);
            }

            // Validar arquivo - Aceita Excel, CSV, PDF, Word e Imagens
            // Adicionar validação customizada para aceitar diferentes MIME types
            $request->validate([
                'documento' => [
                    'required',
                    'file',
                    'max:10240',
                    function ($attribute, $value, $fail) {
                        $extensao = strtolower($value->getClientOriginalExtension());
                        $extensoesPermitidas = ['xlsx', 'xls', 'csv', 'pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];

                        if (!in_array($extensao, $extensoesPermitidas)) {
                            $fail('O documento deve ser do tipo Excel (.xlsx, .xls), CSV, PDF, Word (.doc, .docx) ou Imagem (.png, .jpg, .jpeg).');
                        }
                    }
                ],
            ], [
                'documento.required' => 'Selecione um documento para enviar.',
                'documento.max' => 'O documento não pode ter mais de 10MB.',
            ]);

            $arquivo = $request->file('documento');
            $nomeArquivo = pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME);
            $extensao = strtolower($arquivo->getClientOriginalExtension());

            Log::info('ImportarDocumento: Arquivo validado', [
                'nome' => $nomeArquivo,
                'tamanho' => $arquivo->getSize(),
                'tipo' => $arquivo->getMimeType(),
                'extensao' => $extensao
            ]);

            DB::beginTransaction();

            try {
                // Criar orçamento com dados básicos extraídos do nome do arquivo
                $orcamento = Orcamento::create([
                    'nome' => $nomeArquivo,
                    'referencia_externa' => null,
                    'objeto' => 'Importado de documento: ' . $arquivo->getClientOriginalName(),
                    'orgao_interessado' => null,
                    'tipo_criacao' => 'documento',
                    'orcamento_origem_id' => null,
                    'status' => 'pendente',
                    'user_id' => Auth::id(),
                ]);

                Log::info('ProcessarDocumento: Orçamento criado', [
                    'orcamento_id' => $orcamento->id,
                    'nome' => $orcamento->nome,
                    'extensao' => $extensao
                ]);

                // Processar documento de acordo com o tipo
                $result = null;
                if (in_array($extensao, ['xlsx', 'xls', 'csv'])) {
                    // Processar Excel/CSV com DETECÇÃO INTELIGENTE
                    Log::info('🚀 Usando NOVA lógica de detecção inteligente');

                    $itensExtraidos = $this->processarExcel($arquivo);

                    // Criar itens no orçamento COM preços extraídos
                    $itensImportados = 0;
                    foreach ($itensExtraidos as $itemData) {
                        // Log para debug dos preços
                        Log::info('Criando item com preços', [
                            'descricao' => substr($itemData['descricao'], 0, 50),
                            'quantidade' => $itemData['quantidade'] ?? 1,
                            'preco_unitario' => $itemData['preco_unitario'] ?? null,
                            'preco_total' => $itemData['preco_total'] ?? null
                        ]);

                        OrcamentoItem::create([
                            'orcamento_id' => $orcamento->id,
                            'descricao' => $itemData['descricao'],
                            'medida_fornecimento' => $itemData['unidade'] ?? 'UNIDADE',
                            'quantidade' => $itemData['quantidade'] ?? 1,
                            'preco_unitario' => $itemData['preco_unitario'] ?? null, // ✅ Agora salva o preço unitário
                            'tipo' => 'produto',
                            'alterar_cdf' => false,
                        ]);
                        $itensImportados++;
                    }

                    $result = [
                        'message' => "Planilha importada com sucesso! $itensImportados itens detectados automaticamente.",
                        'itens_importados' => $itensImportados
                    ];
                } elseif ($extensao === 'pdf') {
                    // Processar PDF
                    $result = $this->processarDocumentoPDF($arquivo, $orcamento->id);
                } elseif (in_array($extensao, ['doc', 'docx'])) {
                    // Processar Word
                    $result = $this->processarDocumentoWord($arquivo, $orcamento->id);
                } elseif (in_array($extensao, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'])) {
                    // Processar Imagem
                    $result = $this->processarDocumentoImagem($arquivo, $orcamento->id);
                } else {
                    throw new \Exception('Tipo de documento não suportado');
                }

                Log::info('ProcessarDocumento: Itens importados', [
                    'orcamento_id' => $orcamento->id,
                    'itens_importados' => $result['itens_importados']
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'orcamento_id' => $orcamento->id,
                    'itens_importados' => $result['itens_importados']
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar documento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processar documento PDF e extrair itens
     */
    private function processarDocumentoPDF($arquivo, $orcamentoId)
    {
        try {
            // Usar biblioteca smalot/pdfparser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($arquivo->getRealPath());

            // Extrair texto de todas as páginas
            $texto = $pdf->getText();

            Log::info('ProcessarPDF: Texto extraído', [
                'tamanho' => strlen($texto),
                'primeiros_1000_chars' => substr($texto, 0, 1000)
            ]);

            // Processar linha por linha
            $linhas = explode("\n", $texto);
            $itensImportados = 0;
            $numeroItem = 1;
            $erros = [];

            // Lista expandida de unidades conhecidas
            $unidadesConhecidas = [
                'UN', 'UND', 'UNID', 'UNIDADE',
                'PC', 'PÇ', 'PCT', 'PACOTE',
                'CX', 'CAIXA',
                'KG', 'G', 'MG', 'QUILOGRAMA', 'GRAMA',
                'L', 'ML', 'LITRO', 'MILILITRO',
                'M', 'M2', 'M²', 'M3', 'M³', 'CM', 'MM', 'METRO',
                'HR', 'HORA', 'DIA', 'MÊS', 'MES', 'ANO',
                'RESMA', 'FRASCO', 'GALAO', 'GALÃO', 'ROLO',
                'SERVIÇO', 'SERVICO', 'SV'
            ];

            $padraoUnidades = implode('|', $unidadesConhecidas);

            // Variáveis para item em construção
            $itemEmConstrucao = false;
            $descricaoAcumulada = '';
            $numeroItemAtual = null;

            for ($i = 0; $i < count($linhas); $i++) {
                $linha = trim($linhas[$i]);

                // Pular linhas vazias
                if (empty($linha)) continue;

                // Pular cabeçalhos
                if (preg_match('/^(ITEM|DESCRIÇÃO|DESCRICAO|UNID|QTDE|VALOR|TOTAL)[\s\t]*$/i', $linha)) {
                    continue;
                }

                // Verificar se linha começa com número (novo item)
                if (preg_match('/^(\d+)\s*$/', $linha, $matches)) {
                    // Número isolado - início de novo item
                    $numeroItemAtual = $matches[1];
                    $descricaoAcumulada = '';
                    $itemEmConstrucao = true;
                    Log::info('ProcessarPDF: Início de item detectado', ['numero' => $numeroItemAtual]);
                    continue;
                }

                // Se estamos construindo um item, verificar se esta linha tem unidade + quantidade
                if ($itemEmConstrucao && preg_match('/(' . $padraoUnidades . ')\s+(\d+[,\.]?\d*)/i', $linha, $matches)) {
                    // Encontrou unidade e quantidade - finalizar item
                    $unidade = strtoupper(trim($matches[1]));
                    $quantidade = (float)str_replace(',', '.', $matches[2]);

                    // Limpar descrição acumulada
                    $descricao = trim($descricaoAcumulada);

                    // Normalizar unidade (remover acentos)
                    $unidade = str_replace(['Ê', 'Ã', 'Á', 'À', 'Ç'], ['E', 'A', 'A', 'A', 'C'], $unidade);

                    Log::info('ProcessarPDF: Item completo encontrado', [
                        'numero' => $numeroItemAtual,
                        'descricao' => substr($descricao, 0, 100),
                        'unidade' => $unidade,
                        'quantidade' => $quantidade
                    ]);

                    // Validar descrição
                    if (strlen($descricao) >= 5) {
                        // Criar item do orçamento
                        try {
                            OrcamentoItem::create([
                                'orcamento_id' => $orcamentoId,
                                'numero_item' => $numeroItem,
                                'descricao' => substr($descricao, 0, 500),
                                'medida_fornecimento' => $unidade,
                                'quantidade' => $quantidade,
                                'tipo' => 'produto',
                                'alterar_cdf' => true,
                            ]);

                            $itensImportados++;
                            $numeroItem++;

                            // Limitar a 100 itens
                            if ($itensImportados >= 100) break;

                        } catch (\Exception $e) {
                            $erros[] = "Item $numeroItem: " . $e->getMessage();
                            Log::warning('Erro ao criar item do PDF', [
                                'descricao' => substr($descricao, 0, 100),
                                'erro' => $e->getMessage()
                            ]);
                        }
                    } else {
                        $erros[] = "Item $numeroItemAtual: Descrição vazia ou muito curta";
                    }

                    // Resetar construção
                    $itemEmConstrucao = false;
                    $descricaoAcumulada = '';
                    $numeroItemAtual = null;
                    continue;
                }

                // Se estamos construindo um item e linha não tem unidade, é parte da descrição
                if ($itemEmConstrucao) {
                    // Acumular na descrição
                    $descricaoAcumulada .= ($descricaoAcumulada ? ' ' : '') . $linha;
                    continue;
                }

                // PADRÃO ALTERNATIVO: Tudo em uma linha
                // Exemplo: "1 Descrição completa UN 50 10,00"
                if (preg_match('/^(\d+)\s+(.+?)\s+(' . $padraoUnidades . ')\s+(\d+[,\.]?\d*)/i', $linha, $matches)) {
                    $descricao = trim($matches[2]);
                    $unidade = strtoupper(trim($matches[3]));
                    $quantidade = (float)str_replace(',', '.', $matches[4]);

                    // Limpar valores monetários
                    $descricao = preg_replace('/\s+R?\$?\s*[\d\.,]+$/', '', $descricao);

                    // Normalizar unidade
                    $unidade = str_replace(['Ê', 'Ã', 'Á', 'À', 'Ç'], ['E', 'A', 'A', 'A', 'C'], $unidade);

                    Log::info('ProcessarPDF: Item em linha única encontrado', [
                        'numero' => $matches[1],
                        'descricao' => substr($descricao, 0, 100),
                        'unidade' => $unidade,
                        'quantidade' => $quantidade
                    ]);

                    // Validar e criar item
                    if (strlen($descricao) >= 5) {
                        try {
                            OrcamentoItem::create([
                                'orcamento_id' => $orcamentoId,
                                'numero_item' => $numeroItem,
                                'descricao' => substr($descricao, 0, 500),
                                'medida_fornecimento' => $unidade,
                                'quantidade' => $quantidade,
                                'tipo' => 'produto',
                                'alterar_cdf' => true,
                            ]);

                            $itensImportados++;
                            $numeroItem++;

                            if ($itensImportados >= 100) break;

                        } catch (\Exception $e) {
                            $erros[] = "Item $numeroItem: " . $e->getMessage();
                            Log::warning('Erro ao criar item do PDF', [
                                'descricao' => substr($descricao, 0, 100),
                                'erro' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            if ($itensImportados == 0) {
                throw new \Exception('Não foi possível identificar itens no PDF. Verifique se o documento contém uma tabela com ITEM, DESCRIÇÃO, UNIDADE e QUANTIDADE.');
            }

            $mensagem = "PDF processado com sucesso! $itensImportados item(ns) importado(s).";
            if (count($erros) > 0) {
                $mensagem .= " " . count($erros) . " linha(s) com erro.";
            }

            return [
                'success' => true,
                'message' => $mensagem,
                'itens_importados' => $itensImportados,
                'erros' => $erros
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao processar PDF: ' . $e->getMessage());
            throw new \Exception('Erro ao processar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Processar documento Word e extrair itens
     */
    private function processarDocumentoWord($arquivo, $orcamentoId)
    {
        try {
            // Usar biblioteca phpoffice/phpword
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($arquivo->getRealPath());

            $itensImportados = 0;
            $numeroItem = 1;
            $textoCompleto = '';

            // Extrair texto de todas as seções
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    // Processar parágrafos
                    if (method_exists($element, 'getText')) {
                        $texto = $element->getText();
                        $textoCompleto .= $texto . "\n";
                    }

                    // Processar tabelas
                    if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        foreach ($element->getRows() as $row) {
                            $cellData = [];
                            foreach ($row->getCells() as $cell) {
                                $cellText = '';
                                foreach ($cell->getElements() as $cellElement) {
                                    if (method_exists($cellElement, 'getText')) {
                                        $cellText .= $cellElement->getText() . ' ';
                                    }
                                }
                                $cellData[] = trim($cellText);
                            }

                            // Se a linha da tabela tiver dados, tentar criar um item
                            if (count($cellData) >= 2 && !empty($cellData[0])) {
                                // Pular cabeçalhos
                                if (stripos($cellData[0], 'item') !== false ||
                                    stripos($cellData[0], 'descrição') !== false ||
                                    stripos($cellData[0], 'description') !== false) {
                                    continue;
                                }

                                $descricao = $cellData[0];
                                if (isset($cellData[1])) $descricao .= ' ' . $cellData[1];

                                $unidade = isset($cellData[2]) && !empty($cellData[2]) ? $cellData[2] : 'UN';
                                $quantidade = isset($cellData[3]) ? (float)str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $cellData[3])) : 1;

                                if (strlen($descricao) < 5) continue;

                                try {
                                    OrcamentoItem::create([
                                        'orcamento_id' => $orcamentoId,
                                        'numero_item' => $numeroItem,
                                        'descricao' => substr($descricao, 0, 500),
                                        'medida_fornecimento' => substr($unidade, 0, 10),
                                        'quantidade' => $quantidade > 0 ? $quantidade : 1,
                                        'tipo' => 'produto',
                                        'alterar_cdf' => true,
                                    ]);

                                    $itensImportados++;
                                    $numeroItem++;

                                    if ($itensImportados >= 100) break 2;

                                } catch (\Exception $e) {
                                    Log::warning('Erro ao criar item do Word', [
                                        'descricao' => $descricao,
                                        'erro' => $e->getMessage()
                                    ]);
                                    continue;
                                }
                            }
                        }
                    }
                }
            }

            // Se não encontrou itens em tabelas, processar texto linha por linha
            if ($itensImportados === 0 && !empty($textoCompleto)) {
                $linhas = explode("\n", $textoCompleto);

                foreach ($linhas as $linha) {
                    $linha = trim($linha);

                    if (strlen($linha) < 20 || strlen($linha) > 500) continue;

                    // Tentar extrair padrão estruturado
                    if (preg_match('/(.+?)\s+(UN|UND|UNID|PC|PCT|CX|KG|M|M2|M3|L|HR|DIA|MÊS)\s+(\d+[,\.]?\d*)/', $linha, $matches)) {
                        $descricao = trim($matches[1]);
                        $unidade = $matches[2];
                        $quantidade = (float)str_replace(',', '.', $matches[3]);
                    } else {
                        $descricao = $linha;
                        $unidade = 'UN';
                        $quantidade = 1;
                    }

                    try {
                        OrcamentoItem::create([
                            'orcamento_id' => $orcamentoId,
                            'numero_item' => $numeroItem,
                            'descricao' => substr($descricao, 0, 500),
                            'medida_fornecimento' => $unidade,
                            'quantidade' => $quantidade,
                            'tipo' => 'produto',
                            'alterar_cdf' => true,
                        ]);

                        $itensImportados++;
                        $numeroItem++;

                        if ($itensImportados >= 100) break;

                    } catch (\Exception $e) {
                        Log::warning('Erro ao criar item do Word (texto)', [
                            'descricao' => $descricao,
                            'erro' => $e->getMessage()
                        ]);
                        continue;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Documento Word processado com sucesso! $itensImportados item(ns) importado(s).",
                'itens_importados' => $itensImportados
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao processar Word: ' . $e->getMessage());
            throw new \Exception('Erro ao processar Word: ' . $e->getMessage());
        }
    }

    /**
     * Processar imagem - Cria item placeholder para revisão manual
     */
    private function processarDocumentoImagem($arquivo, $orcamentoId)
    {
        try {
            // Salvar a imagem para referência futura
            $nomeArquivo = time() . '_' . $arquivo->getClientOriginalName();
            $caminhoImagem = $arquivo->storeAs('documentos_importados', $nomeArquivo, 'public');

            Log::info('ProcessarImagem: Imagem salva', [
                'caminho' => $caminhoImagem
            ]);

            // Criar item placeholder indicando que precisa revisão manual
            $descricao = "REVISÃO NECESSÁRIA - Documento importado de imagem\n\n";
            $descricao .= "Arquivo: " . $arquivo->getClientOriginalName() . "\n";
            $descricao .= "Tamanho: " . round($arquivo->getSize() / 1024, 2) . " KB\n\n";
            $descricao .= "INSTRUÇÕES:\n";
            $descricao .= "1. Visualize a imagem em: storage/app/public/" . $caminhoImagem . "\n";
            $descricao .= "2. Edite esta descrição com as informações corretas do item\n";
            $descricao .= "3. Atualize a quantidade e unidade conforme necessário\n";
            $descricao .= "4. Adicione mais itens usando o botão 'Adicionar Item' se necessário";

            OrcamentoItem::create([
                'orcamento_id' => $orcamentoId,
                'numero_item' => 1,
                'descricao' => $descricao,
                'unidade' => 'UN',
                'quantidade' => 1,
                'tipo' => 'produto',
                'alterar_cdf' => true,
            ]);

            return [
                'success' => true,
                'message' => "Imagem importada com sucesso! Um item foi criado e requer revisão manual. Por favor, edite o item com as informações corretas visualizando a imagem salva.",
                'itens_importados' => 1,
                'caminho_imagem' => $caminhoImagem
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao processar Imagem: ' . $e->getMessage());
            throw new \Exception('Erro ao processar Imagem: ' . $e->getMessage());
        }
    }

    /**
     * Salvar coleta de sítio de comércio eletrônico
     */
    public function storeColetaEcommerce(Request $request, $id)
    {
        // Log detalhado do arquivo antes da validação
        $arquivoInfo = null;
        if ($request->hasFile('arquivo_print')) {
            $file = $request->file('arquivo_print');
            $arquivoInfo = [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError()
            ];
        }

        Log::info('storeColetaEcommerce: MÉTODO CHAMADO', [
            'id' => $id,
            'request_all' => $request->except(['arquivo_print']),
            'has_file' => $request->hasFile('arquivo_print'),
            'arquivo_detalhes' => $arquivoInfo
        ]);

        try {
            $orcamento = Orcamento::findOrFail($id);

            // Validar dados
            // Nota: text/plain é necessário porque arquivos vindos do proxy podem ter esse MIME type
            $validated = $request->validate([
                'nome_site' => 'required|string|max:255',
                'url_site' => 'required|url',
                'eh_intermediacao' => 'required|boolean',
                'data_consulta' => 'required|date|before_or_equal:today',
                'hora_consulta' => 'required',
                'inclui_frete' => 'required|boolean',
                'arquivo_print' => 'required|file|max:2048000',
                'itens_selecionados' => 'required|array|min:1',
                'preco_unitario' => 'required|array',
            ]);

            // Validação adicional: verificar se data/hora não está no futuro
            $dataHoraConsulta = \Carbon\Carbon::parse($validated['data_consulta'] . ' ' . $validated['hora_consulta']);
            if ($dataHoraConsulta->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A data e hora da consulta não podem estar no futuro. Por favor, verifique os dados informados.'
                ], 422);
            }

            DB::beginTransaction();

            // Upload do arquivo
            $arquivoPath = null;
            if ($request->hasFile('arquivo_print')) {
                $arquivo = $request->file('arquivo_print');
                $nomeArquivo = time() . '_' . $arquivo->getClientOriginalName();
                $arquivoPath = $arquivo->storeAs('coletas_ecommerce', $nomeArquivo, 'public');
            }

            // Criar coleta
            $coleta = \App\Models\ColetaEcommerce::create([
                'orcamento_id' => $orcamento->id,
                'nome_site' => $validated['nome_site'],
                'url_site' => $validated['url_site'],
                'eh_intermediacao' => $validated['eh_intermediacao'],
                'data_consulta' => $validated['data_consulta'],
                'hora_consulta' => $validated['hora_consulta'],
                'inclui_frete' => $validated['inclui_frete'],
                'arquivo_print' => $arquivoPath,
            ]);

            // Criar itens coletados
            foreach ($validated['itens_selecionados'] as $itemId) {
                $precoUnitario = $validated['preco_unitario'][$itemId] ?? 0;

                // Buscar quantidade do item
                $item = OrcamentoItem::findOrFail($itemId);
                $precoTotal = $precoUnitario * $item->quantidade;

                \App\Models\ColetaEcommerceItem::create([
                    'coleta_ecommerce_id' => $coleta->id,
                    'orcamento_item_id' => $itemId,
                    'preco_unitario' => $precoUnitario,
                    'preco_total' => $precoTotal,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Coleta de e-commerce salva com sucesso!',
                'coleta_id' => $coleta->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('storeColetaEcommerce: ERRO DE VALIDAÇÃO', [
                'errors' => $e->validator->errors()->all(),
                'failed_rules' => $e->validator->failed()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar coleta e-commerce: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar coleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar solicitação de CDF (Cotação Direta com Fornecedor)
     */
    public function storeSolicitarCDF(Request $request, $id)
    {
        Log::info('storeSolicitarCDF: MÉTODO CHAMADO', [
            'id' => $id,
            'request_all' => $request->all(),
            'has_file' => $request->hasFile('arquivo_cnpj')
        ]);

        try {
            $orcamento = Orcamento::findOrFail($id);

            // Log detalhado do arquivo para debug
            if ($request->hasFile('arquivo_cnpj')) {
                $arquivo = $request->file('arquivo_cnpj');
                Log::info('storeSolicitarCDF: Arquivo recebido', [
                    'original_name' => $arquivo->getClientOriginalName(),
                    'mime_type' => $arquivo->getMimeType(),
                    'extension' => $arquivo->getClientOriginalExtension(),
                    'size' => $arquivo->getSize(),
                    'path' => $arquivo->getRealPath()
                ]);
            }

            // Validar dados
            // Nota: Removemos validação de MIME type porque arquivos do proxy podem vir como text/plain
            $validated = $request->validate([
                'itens_selecionados' => 'required|array|min:1',
                'cnpj' => 'required|string|max:18',
                'razao_social' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telefone' => 'nullable|string|max:50',
                'justificativas' => 'nullable|array',
                'justificativa_outro' => 'nullable|string',
                'prazo_resposta_dias' => 'required|integer|min:1',
                'prazo_entrega_dias' => 'required|integer|min:1',
                'frete' => 'required|in:CIF,FOB',
                'observacao' => 'nullable|string',
                'fornecedor_valido' => 'nullable',
                'arquivo_cnpj' => 'nullable|file|max:2048000',
            ]);

            // Processar justificativas (checkbox array)
            $justificativas = $validated['justificativas'] ?? [];
            $justificativa_fornecedor_unico = in_array('reconhecida_regiao', $justificativas);
            $justificativa_produto_exclusivo = in_array('forneceu_anteriormente', $justificativas);
            $justificativa_urgencia = in_array('outro_municipio', $justificativas);
            $justificativa_melhor_preco = in_array('mais_competitivo', $justificativas);

            DB::beginTransaction();

            // Upload do arquivo CNPJ (se fornecido)
            $arquivoPath = null;
            if ($request->hasFile('arquivo_cnpj')) {
                $arquivo = $request->file('arquivo_cnpj');
                $nomeArquivo = time() . '_' . $arquivo->getClientOriginalName();
                $arquivoPath = $arquivo->storeAs('solicitacoes_cdf', $nomeArquivo, 'public');
            }

            // Gerar token único para resposta do fornecedor
            $token = hash('sha256', uniqid($validated['cnpj'], true) . time());
            $validoAte = now()->addDays((int) $validated['prazo_resposta_dias']);

            // Criar solicitação CDF
            $solicitacao = \App\Models\SolicitacaoCDF::create([
                'orcamento_id' => $orcamento->id,
                'cnpj' => $validated['cnpj'],
                'razao_social' => $validated['razao_social'],
                'email' => $validated['email'],
                'telefone' => $validated['telefone'] ?? null,
                'justificativa_fornecedor_unico' => $justificativa_fornecedor_unico,
                'justificativa_produto_exclusivo' => $justificativa_produto_exclusivo,
                'justificativa_urgencia' => $justificativa_urgencia,
                'justificativa_melhor_preco' => $justificativa_melhor_preco,
                'justificativa_outro' => $validated['justificativa_outro'] ?? null,
                'prazo_resposta_dias' => $validated['prazo_resposta_dias'],
                'prazo_entrega_dias' => $validated['prazo_entrega_dias'],
                'frete' => $validated['frete'],
                'observacao' => $validated['observacao'] ?? null,
                'fornecedor_valido' => !empty($validated['fornecedor_valido']),
                'arquivo_cnpj' => $arquivoPath,
                'status' => 'Pendente',
                // Campos do sistema de resposta por link
                'token_resposta' => $token,
                'valido_ate' => $validoAte,
                'respondido' => false,
            ]);

            // Criar itens da solicitação
            Log::info('Antes de inserir itens', [
                'solicitacao_id' => $solicitacao->id,
                'itens_selecionados' => $validated['itens_selecionados'],
                'db_prefix' => config('database.connections.pgsql.prefix')
            ]);

            foreach ($validated['itens_selecionados'] as $itemId) {
                // Verificar se item existe
                $itemExists = OrcamentoItem::find($itemId);
                Log::info('Verificando item antes de inserir', [
                    'item_id' => $itemId,
                    'exists' => $itemExists ? 'sim' : 'não',
                    'db_prefix' => config('database.connections.pgsql.prefix')
                ]);

                \App\Models\SolicitacaoCDFItem::create([
                    'solicitacao_cdf_id' => $solicitacao->id,
                    'orcamento_item_id' => $itemId,
                ]);
            }

            DB::commit();

            // Enviar email para o fornecedor com link de resposta
            Log::info('INICIANDO envio de email de CDF', [
                'solicitacao_id' => $solicitacao->id,
                'email' => $solicitacao->email
            ]);

            try {
                Mail::to($solicitacao->email)->send(new CdfSolicitacaoMail($solicitacao));
                Log::info('Email de CDF enviado com sucesso', [
                    'solicitacao_id' => $solicitacao->id,
                    'email' => $solicitacao->email,
                    'token' => $token
                ]);
            } catch (\Exception $emailError) {
                // Log do erro mas não falha a criação da CDF
                Log::error('Erro ao enviar email de CDF', [
                    'solicitacao_id' => $solicitacao->id,
                    'email' => $solicitacao->email,
                    'error' => $emailError->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de CDF criada com sucesso!',
                'solicitacao_id' => $solicitacao->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('storeSolicitarCDF: ERRO DE VALIDAÇÃO', [
                'errors' => $e->validator->errors()->all(),
                'failed_rules' => $e->validator->failed()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeSolicitarCDF: ERRO GERAL', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar solicitação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar contratação similar de outros entes públicos
     */
    public function storeContratacoesSimilares(Request $request, $id)
    {
        Log::info('storeContratacoesSimilares: MÉTODO CHAMADO', [
            'id' => $id,
            'request_all' => $request->except(['arquivo_pdf']),
            'has_file' => $request->hasFile('arquivo_pdf'),
            'arquivo_size' => $request->hasFile('arquivo_pdf') ? $request->file('arquivo_pdf')->getSize() : 0
        ]);

        try {
            $orcamento = Orcamento::findOrFail($id);

            Log::info('storeContratacoesSimilares: Iniciando validação');

            // Log detalhado do arquivo para debug
            if ($request->hasFile('arquivo_pdf')) {
                $arquivo = $request->file('arquivo_pdf');
                Log::info('storeContratacoesSimilares: Arquivo recebido', [
                    'original_name' => $arquivo->getClientOriginalName(),
                    'mime_type' => $arquivo->getMimeType(),
                    'extension' => $arquivo->getClientOriginalExtension(),
                    'size' => $arquivo->getSize()
                ]);
            }

            // Validar dados
            // Nota: Removemos validação de MIME type porque arquivos do proxy podem vir como text/plain
            $validated = $request->validate([
                'ente_publico' => 'required|string|max:255',
                'tipo' => 'required|string|max:100',
                'numero_processo' => 'required|string|max:100',
                'eh_registro_precos' => 'required|in:0,1,true,false', // Aceita checkbox
                'data_publicacao' => 'required|date|before_or_equal:today',
                'local_publicacao' => 'nullable|string|max:50',
                'link_oficial' => 'required|url',
                'itens_selecionados' => 'required|array|min:1',
                'descricao' => 'nullable|array',
                'unidade' => 'nullable|array',
                'quantidade_referencia' => 'nullable|array',
                'preco_unitario' => 'required|array',
                'nivel_confianca' => 'nullable|array',
                'arquivo_pdf' => 'required|file|max:102400',
            ]);

            Log::info('storeContratacoesSimilares: Validação OK, iniciando transação');

            DB::beginTransaction();

            // Upload e cálculo de hash do arquivo
            $arquivoPath = null;
            $arquivoHash = null;
            $arquivoTamanho = null;

            if ($request->hasFile('arquivo_pdf')) {
                $arquivo = $request->file('arquivo_pdf');
                $nomeArquivo = time() . '_' . $arquivo->getClientOriginalName();

                Log::info('storeContratacoesSimilares: Fazendo upload do arquivo', [
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'tamanho' => $arquivo->getSize(),
                    'mime' => $arquivo->getMimeType()
                ]);

                $arquivoPath = $arquivo->storeAs('contratacoes_similares', $nomeArquivo, 'public');

                // Calcular hash MD5
                $arquivoHash = md5_file($arquivo->getRealPath());
                $arquivoTamanho = $arquivo->getSize();

                Log::info('storeContratacoesSimilares: Upload concluído', [
                    'path' => $arquivoPath,
                    'hash' => $arquivoHash
                ]);
            }

            Log::info('storeContratacoesSimilares: Criando registro de contratação similar');

            // Criar contratação similar
            $contratacao = \App\Models\ContratacaoSimilar::create([
                'orcamento_id' => $orcamento->id,
                'ente_publico' => $validated['ente_publico'],
                'tipo' => $validated['tipo'],
                'numero_processo' => $validated['numero_processo'],
                'eh_registro_precos' => $validated['eh_registro_precos'],
                'data_publicacao' => $validated['data_publicacao'],
                'local_publicacao' => $validated['local_publicacao'] ?? null,
                'link_oficial' => $validated['link_oficial'],
                'arquivo_pdf' => $arquivoPath,
                'arquivo_hash' => $arquivoHash,
                'arquivo_tamanho' => $arquivoTamanho,
                'data_coleta' => now(),
                'usuario_coleta' => auth()->user()->name ?? 'Sistema',
            ]);

            Log::info('storeContratacoesSimilares: Contratação criada', ['contratacao_id' => $contratacao->id]);

            // Criar itens da contratação
            Log::info('storeContratacoesSimilares: Criando itens', ['total_itens' => count($validated['itens_selecionados'])]);

            foreach ($validated['itens_selecionados'] as $itemId) {
                $precoUnitario = $validated['preco_unitario'][$itemId] ?? 0;

                // Ignorar itens com preço zero
                if ($precoUnitario <= 0) continue;

                $descricao = $validated['descricao'][$itemId] ?? '';
                $unidade = $validated['unidade'][$itemId] ?? 'UN';
                $quantidadeRef = $validated['quantidade_referencia'][$itemId] ?? 1;
                $nivelConfianca = $validated['nivel_confianca'][$itemId] ?? 'Unitário';

                $precoTotal = $precoUnitario * $quantidadeRef;

                \App\Models\ContratacaoSimilarItem::create([
                    'contratacao_similar_id' => $contratacao->id,
                    'orcamento_item_id' => $itemId,
                    'descricao' => $descricao,
                    'catmat' => null, // Pode ser adicionado futuramente
                    'unidade' => $unidade,
                    'quantidade_referencia' => $quantidadeRef,
                    'preco_unitario' => $precoUnitario,
                    'preco_total' => $precoTotal,
                    'nivel_confianca' => $nivelConfianca,
                ]);
            }

            Log::info('storeContratacoesSimilares: Todos os itens criados, fazendo commit');

            DB::commit();

            Log::info('storeContratacoesSimilares: SUCESSO! Contratação salva', ['contratacao_id' => $contratacao->id]);

            return response()->json([
                'success' => true,
                'message' => 'Contratação similar salva com sucesso!',
                'contratacao_id' => $contratacao->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('storeContratacoesSimilares: ERRO DE VALIDAÇÃO', [
                'errors' => $e->validator->errors()->all(),
                'failed_rules' => $e->validator->failed()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar contratação similar: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar contratação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar CNPJ na ReceitaWS via backend (evita erro CORS)
     * GET /orcamentos/consultar-cnpj/{cnpj}
     */
    public function consultarCNPJ($cnpj)
    {
        try {
            // Limpar CNPJ (remover pontos, barras, hífens)
            $cnpjLimpo = preg_replace('/\D/', '', $cnpj);

            // Validar se tem 14 dígitos
            if (strlen($cnpjLimpo) !== 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ inválido. Deve conter 14 dígitos.'
                ], 400);
            }

            // Fazer requisição para ReceitaWS
            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjLimpo}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar CNPJ na Receita Federal.'
                ], $response->status());
            }

            $data = $response->json();

            // Verificar se encontrou
            if (isset($data['status']) && $data['status'] === 'ERROR') {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'CNPJ não encontrado.'
                ], 404);
            }

            // Retornar dados
            return response()->json([
                'success' => true,
                'data' => [
                    'razao_social' => $data['nome'] ?? '',
                    'logradouro' => $data['logradouro'] ?? '',
                    'numero' => $data['numero'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'endereco' => ($data['logradouro'] ?? '') . ', ' . ($data['numero'] ?? '') . ' - ' . ($data['bairro'] ?? ''),
                    'cep' => $data['cep'] ?? '',
                    'municipio' => $data['municipio'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'cnae_principal' => $data['atividade_principal'][0]['text'] ?? ''
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao consultar ReceitaWS: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar CNPJ. Tente novamente mais tarde.'
            ], 500);
        }
    }

    /**
     * Salvar dados do orçamentista (Seção 6)
     * POST /orcamentos/{id}/salvar-orcamentista
     */
    public function salvarOrcamentista(Request $request, $id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);

            // Validar dados (aceitar tanto campos hidden quanto manuais)
            $validated = $request->validate([
                'orcamentista_nome' => 'required|string|max:255',
                'orcamentista_cpf_cnpj' => 'required|string|max:18',
                'orcamentista_matricula' => 'nullable|string|max:50',
                'orcamentista_portaria' => 'nullable|string|max:100',
                'orcamentista_setor' => 'nullable|string|max:100',
                'orcamentista_razao_social' => 'nullable|string|max:255',

                // Campos que podem vir com sufixo "_manual" ou sem
                'orcamentista_endereco' => 'nullable|string',
                'orcamentista_endereco_manual' => 'nullable|string',
                'orcamentista_cep' => 'nullable|string|max:10',
                'orcamentista_cep_manual' => 'nullable|string|max:10',
                'orcamentista_cidade' => 'nullable|string|max:100',
                'orcamentista_uf' => 'nullable|string|max:2',
                'orcamentista_uf_manual' => 'nullable|string|max:2',

                'brasao_file' => 'nullable|image|mimes:jpeg,jpg,png,gif,svg|max:5120', // max 5MB
            ]);

            DB::beginTransaction();

            // Upload do brasão se houver
            $brasaoPath = $orcamento->brasao_path; // Manter o path existente se não enviar novo arquivo

            if ($request->hasFile('brasao_file')) {
                $arquivo = $request->file('brasao_file');

                // Criar diretório se não existir
                $diretorioBrasoes = storage_path('app/public/brasoes');
                if (!file_exists($diretorioBrasoes)) {
                    mkdir($diretorioBrasoes, 0777, true);
                }

                // Gerar nome único
                $nomeArquivo = time() . '_brasao_' . uniqid() . '.' . $arquivo->getClientOriginalExtension();
                $caminhoCompleto = $diretorioBrasoes . '/' . $nomeArquivo;

                // Redimensionar imagem automaticamente para 300x300px mantendo proporções usando GD
                try {
                    // Obter informações da imagem
                    $imagemInfo = getimagesize($arquivo->getPathname());
                    $larguraOriginal = $imagemInfo[0];
                    $alturaOriginal = $imagemInfo[1];
                    $tipoMime = $imagemInfo['mime'];

                    // Criar imagem a partir do arquivo original
                    switch ($tipoMime) {
                        case 'image/jpeg':
                            $imagemOriginal = imagecreatefromjpeg($arquivo->getPathname());
                            break;
                        case 'image/png':
                            $imagemOriginal = imagecreatefrompng($arquivo->getPathname());
                            break;
                        case 'image/gif':
                            $imagemOriginal = imagecreatefromgif($arquivo->getPathname());
                            break;
                        default:
                            throw new \Exception('Formato de imagem não suportado: ' . $tipoMime);
                    }

                    // Calcular novas dimensões mantendo proporção (máx 300x300)
                    $maxDimensao = 300;
                    $proporcao = $larguraOriginal / $alturaOriginal;

                    if ($larguraOriginal > $alturaOriginal) {
                        $novaLargura = $maxDimensao;
                        $novaAltura = round($maxDimensao / $proporcao);
                    } else {
                        $novaAltura = $maxDimensao;
                        $novaLargura = round($maxDimensao * $proporcao);
                    }

                    // Não aumentar imagens menores
                    if ($larguraOriginal < $novaLargura && $alturaOriginal < $novaAltura) {
                        $novaLargura = $larguraOriginal;
                        $novaAltura = $alturaOriginal;
                    }

                    // Criar nova imagem redimensionada
                    $imagemRedimensionada = imagecreatetruecolor($novaLargura, $novaAltura);

                    // Preservar transparência para PNG e GIF
                    if ($tipoMime == 'image/png' || $tipoMime == 'image/gif') {
                        imagealphablending($imagemRedimensionada, false);
                        imagesavealpha($imagemRedimensionada, true);
                        $transparente = imagecolorallocatealpha($imagemRedimensionada, 0, 0, 0, 127);
                        imagefilledrectangle($imagemRedimensionada, 0, 0, $novaLargura, $novaAltura, $transparente);
                    }

                    // Redimensionar
                    imagecopyresampled(
                        $imagemRedimensionada,
                        $imagemOriginal,
                        0, 0, 0, 0,
                        $novaLargura, $novaAltura,
                        $larguraOriginal, $alturaOriginal
                    );

                    // Salvar imagem redimensionada
                    switch ($tipoMime) {
                        case 'image/jpeg':
                            imagejpeg($imagemRedimensionada, $caminhoCompleto, 90);
                            break;
                        case 'image/png':
                            imagepng($imagemRedimensionada, $caminhoCompleto, 9);
                            break;
                        case 'image/gif':
                            imagegif($imagemRedimensionada, $caminhoCompleto);
                            break;
                    }

                    // Liberar memória
                    imagedestroy($imagemOriginal);
                    imagedestroy($imagemRedimensionada);

                    $brasaoPath = 'brasoes/' . $nomeArquivo;

                    Log::info('Upload e redimensionamento de brasão concluído com GD', [
                        'orcamento_id' => $id,
                        'arquivo' => $nomeArquivo,
                        'path' => $brasaoPath,
                        'tamanho_original' => $arquivo->getSize(),
                        'dimensoes_originais' => $larguraOriginal . 'x' . $alturaOriginal,
                        'dimensoes_finais' => $novaLargura . 'x' . $novaAltura
                    ]);
                } catch (\Exception $e) {
                    // Se falhar o redimensionamento, fazer upload normal
                    Log::warning('Falha ao redimensionar brasão com GD, fazendo upload normal', [
                        'erro' => $e->getMessage()
                    ]);
                    $brasaoPath = $arquivo->storeAs('brasoes', $nomeArquivo, 'public');
                }
            }

            // Priorizar campos manuais sobre campos hidden
            $endereco = $validated['orcamentista_endereco_manual'] ?? $validated['orcamentista_endereco'] ?? null;
            $cep = $validated['orcamentista_cep_manual'] ?? $validated['orcamentista_cep'] ?? null;
            $uf = $validated['orcamentista_uf_manual'] ?? $validated['orcamentista_uf'] ?? null;

            // Atualizar dados do orçamento
            $orcamento->update([
                'orcamentista_nome' => $validated['orcamentista_nome'],
                'orcamentista_cpf_cnpj' => $validated['orcamentista_cpf_cnpj'],
                'orcamentista_matricula' => $validated['orcamentista_matricula'] ?? null,
                'orcamentista_portaria' => $validated['orcamentista_portaria'] ?? null,
                'orcamentista_setor' => $validated['orcamentista_setor'] ?? null,
                'orcamentista_razao_social' => $validated['orcamentista_razao_social'] ?? null,
                'orcamentista_endereco' => $endereco,
                'orcamentista_cep' => $cep,
                'orcamentista_cidade' => $validated['orcamentista_cidade'] ?? null,
                'orcamentista_uf' => $uf,
                'brasao_path' => $brasaoPath,
            ]);

            DB::commit();

            Log::info('Dados do orçamentista salvos com sucesso', [
                'orcamento_id' => $id,
                'nome' => $validated['orcamentista_nome'],
                'cpf_cnpj' => $validated['orcamentista_cpf_cnpj']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dados do orçamentista salvos com sucesso!',
                'data' => [
                    'orcamento_id' => $orcamento->id,
                    'nome' => $orcamento->orcamentista_nome,
                    'cpf_cnpj' => $orcamento->orcamentista_cpf_cnpj,
                    'brasao_url' => $brasaoPath ? 'storage/' . $brasaoPath : null
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar dados do orçamentista: ' . $e->getMessage(), [
                'orcamento_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar dados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar dados de uma CDF
     */
    public function getCDF($id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            return response()->json($cdf);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'CDF não encontrada'
            ], 404);
        }
    }

    /**
     * Remover CDF
     */
    public function destroyCDF($id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            $cdf->delete();

            Log::info('CDF removida', [
                'orcamento_id' => $id,
                'cdf_id' => $cdf_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'CDF removida com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover CDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover CDF'
            ], 500);
        }
    }

    /**
     * Processar 1º Passo - Importar Comprovante de Solicitação
     */
    public function primeiroPassoCDF(Request $request, $id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            $validated = $request->validate([
                'metodo_coleta' => 'required|in:email,presencial',
                'comprovante_file' => 'required|file|max:20480' // 20MB
            ]);

            DB::beginTransaction();

            // Upload do comprovante
            if ($request->hasFile('comprovante_file')) {
                $arquivo = $request->file('comprovante_file');
                $nomeArquivo = time() . '_comprovante_cdf_' . $cdf_id . '.pdf';
                $path = $arquivo->storeAs('cdf/comprovantes', $nomeArquivo, 'public');

                $cdf->update([
                    'metodo_coleta' => $validated['metodo_coleta'],
                    'comprovante_path' => $path,
                    'status' => 'Aguardando resposta'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Primeiro passo concluído com sucesso'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro no primeiro passo CDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar primeiro passo'
            ], 500);
        }
    }

    /**
     * Processar 2º Passo - Validar Cotação CDF
     */
    public function segundoPassoCDF(Request $request, $id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            $validated = $request->validate([
                'q1' => 'required|in:sim,nao_descartar',
                'q2' => 'required|in:sim,nao_justificar,nao_descartar',
                'q3' => 'required|in:sim,nao_justificar,nao_descartar',
                'q4' => 'required|in:sim,nao_descartar',
                'q5' => 'required|in:nao,sim_justificar,sim_descartar'
            ]);

            DB::beginTransaction();

            // Verificar se alguma questão resulta em descarte
            $descartarCDF = false;
            $motivos = [];

            if ($validated['q1'] === 'nao_descartar') {
                $descartarCDF = true;
                $motivos[] = 'Não respondeu dentro do prazo estabelecido';
            }

            if ($validated['q2'] === 'nao_descartar') {
                $descartarCDF = true;
                $motivos[] = 'Não identifica adequadamente a empresa';
            }

            if ($validated['q3'] === 'nao_descartar') {
                $descartarCDF = true;
                $motivos[] = 'Não identifica adequadamente o responsável';
            }

            if ($validated['q4'] === 'nao_descartar') {
                $descartarCDF = true;
                $motivos[] = 'Itens não estão em conformidade';
            }

            if ($validated['q5'] === 'sim_descartar') {
                $descartarCDF = true;
                $motivos[] = 'Identificadas relações vedadas com servidor ou órgão';
            }

            // Atualizar CDF
            if ($descartarCDF) {
                $cdf->update([
                    'status' => 'Descartada',
                    'validacao_respostas' => $validated, // Laravel converte automaticamente para JSON
                    'descarte_motivo' => implode('; ', $motivos)
                ]);
            } else {
                $cdf->update([
                    'status' => 'Validada',
                    'validacao_respostas' => $validated // Laravel converte automaticamente para JSON
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $descartarCDF ? 'CDF descartada com sucesso' : 'CDF validada com sucesso',
                'descartada' => $descartarCDF
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro no segundo passo CDF: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar validação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Baixar Ofício de Solicitação (Word)
     */
    public function baixarOficioCDF($id, $cdf_id)
    {
        Log::info('baixarOficioCDF: Iniciando', [
            'id' => $id,
            'cdf_id' => $cdf_id,
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'has_proxy_headers' => request()->hasHeader('X-User-Id')
        ]);

        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            // Copiar o modelo fornecido pelo usuário
            $modeloPath = storage_path('app/public/modelos/solicitacaodecdf-modelo.docx');

            // Se o modelo não existir, copiar da raiz do projeto
            if (!file_exists($modeloPath)) {
                $origemModelo = base_path('modulos/cestadeprecos/solicitacaodecdf01-2025 (1).docx');
                if (file_exists($origemModelo)) {
                    @mkdir(dirname($modeloPath), 0755, true);
                    copy($origemModelo, $modeloPath);
                }
            }

            if (file_exists($modeloPath)) {
                $nomeArquivo = 'solicitacaodecdf' . str_pad($cdf_id, 2, '0', STR_PAD_LEFT) . '-2025.docx';
                return response()->download($modeloPath, $nomeArquivo);
            }

            return response()->json([
                'success' => false,
                'message' => 'Modelo de ofício não encontrado'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erro ao baixar ofício CDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar ofício'
            ], 500);
        }
    }

    /**
     * Baixar Formulário de Cotação (Excel)
     */
    public function baixarFormularioCDF($id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            // Copiar o modelo fornecido pelo usuário
            $modeloPath = storage_path('app/public/modelos/formulariodecotacao-modelo.xlsx');

            // Se o modelo não existir, copiar da raiz do projeto
            if (!file_exists($modeloPath)) {
                $origemModelo = base_path('modulos/cestadeprecos/formulariodecotacao01-2025 (2).xlsx');
                if (file_exists($origemModelo)) {
                    @mkdir(dirname($modeloPath), 0755, true);
                    copy($origemModelo, $modeloPath);
                }
            }

            if (file_exists($modeloPath)) {
                $nomeArquivo = 'formulariodecotacao' . str_pad($cdf_id, 2, '0', STR_PAD_LEFT) . '-2025.xlsx';
                return response()->download($modeloPath, $nomeArquivo);
            }

            return response()->json([
                'success' => false,
                'message' => 'Modelo de formulário não encontrado'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erro ao baixar formulário CDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar formulário'
            ], 500);
        }
    }

    /**
     * Baixar Espelho CNPJ (PDF) - Versão simplificada sem necessidade de CDF salvo
     */
    public function baixarEspelhoCNPJSimples(Request $request)
    {
        try {
            // Aceitar tanto JSON quanto form-data
            $cnpjBruto = $request->input('cnpj');

            Log::info('🔍 baixarEspelhoCNPJSimples chamado', [
                'cnpj_bruto' => $cnpjBruto,
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'is_json' => $request->isJson(),
                'all_data' => $request->all()
            ]);

            $cnpjLimpo = preg_replace('/\D/', '', $cnpjBruto);

            if (strlen($cnpjLimpo) !== 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ inválido'
                ], 400);
            }

            // Buscar dados do CNPJ na ReceitaWS
            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjLimpo}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar CNPJ na Receita Federal'
                ], 500);
            }

            $dadosCNPJ = $response->json();

            // Verificar se retornou erro
            if (isset($dadosCNPJ['status']) && $dadosCNPJ['status'] === 'ERROR') {
                return response()->json([
                    'success' => false,
                    'message' => $dadosCNPJ['message'] ?? 'CNPJ não encontrado'
                ], 404);
            }

            // Gerar PDF com os dados do CNPJ
            $pdf = \PDF::loadView('orcamentos.espelho-cnpj', compact('dadosCNPJ'));
            $pdf->setPaper('A4', 'portrait');

            $nomeArquivo = 'espelho_cnpj_' . $cnpjLimpo . '.pdf';

            Log::info('✅ PDF gerado com sucesso', [
                'nome_arquivo' => $nomeArquivo,
                'tamanho' => strlen($pdf->output())
            ]);

            // Retornar PDF como download com headers corretos
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Erro ao baixar espelho CNPJ simples: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar espelho CNPJ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Baixar Espelho CNPJ (PDF)
     */
    public function baixarEspelhoCNPJ($id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            // Buscar dados do CNPJ na ReceitaWS
            $cnpjLimpo = preg_replace('/\D/', '', $cdf->cnpj);

            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjLimpo}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar CNPJ'
                ], 500);
            }

            $dadosCNPJ = $response->json();

            // Gerar PDF com os dados do CNPJ
            $pdf = \PDF::loadView('orcamentos.espelho-cnpj', compact('dadosCNPJ', 'cdf'));
            $pdf->setPaper('A4', 'portrait');

            $nomeArquivo = 'espelho_cnpj_' . $cnpjLimpo . '.pdf';
            return $pdf->download($nomeArquivo);

        } catch (\Exception $e) {
            Log::error('Erro ao baixar espelho CNPJ: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar espelho CNPJ'
            ], 500);
        }
    }

    /**
     * Baixar Comprovante da Solicitação (PDF)
     */
    public function baixarComprovanteCDF($id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->findOrFail($cdf_id);

            if (!$cdf->comprovante_path || !file_exists(storage_path('app/public/' . $cdf->comprovante_path))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comprovante não encontrado. Execute o 1º Passo primeiro.'
                ], 404);
            }

            $nomeArquivo = 'comprovante_cdf_' . str_pad($cdf_id, 2, '0', STR_PAD_LEFT) . '.pdf';
            return response()->download(storage_path('app/public/' . $cdf->comprovante_path), $nomeArquivo);

        } catch (\Exception $e) {
            Log::error('Erro ao baixar comprovante CDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao baixar comprovante'
            ], 500);
        }
    }

    /**
     * Baixar Cotação Direta com Fornecedor (PDF)
     */
    public function baixarCotacaoCDF($id, $cdf_id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $cdf = $orcamento->solicitacoesCDF()->with(['itens.item'])->findOrFail($cdf_id);

            // Verificar se já existe um PDF da cotação gerado
            if ($cdf->cotacao_path && \Storage::disk('public')->exists($cdf->cotacao_path)) {
                Log::info('BaixarCotacaoCDF: PDF já existe, fazendo download', [
                    'cdf_id' => $cdf_id,
                    'path' => $cdf->cotacao_path
                ]);

                return response()->download(
                    storage_path('app/public/' . $cdf->cotacao_path),
                    'Cotacao_CDF_' . $cdf->id . '.pdf'
                );
            }

            // Se não existe PDF, gerar um novo
            Log::info('BaixarCotacaoCDF: Gerando novo PDF da cotação', ['cdf_id' => $cdf_id]);

            // Preparar dados para o PDF
            $dados = [
                'orcamento' => $orcamento,
                'cdf' => $cdf,
                'itens' => $cdf->itens->map(function($cdfItem) {
                    return [
                        'numero' => $cdfItem->item->numero ?? '-',
                        'descricao' => $cdfItem->item->descricao,
                        'quantidade' => $cdfItem->item->quantidade,
                        'unidade' => $cdfItem->item->medida_fornecimento,
                        'marca' => $cdfItem->item->indicacao_marca ?? '-',
                        'preco_unitario' => $cdfItem->item->preco_pesquisado ?? 0,
                        'preco_total' => ($cdfItem->item->preco_pesquisado ?? 0) * ($cdfItem->item->quantidade ?? 0),
                    ];
                }),
                'total_geral' => $cdf->itens->sum(function($cdfItem) {
                    return ($cdfItem->item->preco_pesquisado ?? 0) * ($cdfItem->item->quantidade ?? 0);
                }),
                'data_geracao' => now()->format('d/m/Y H:i:s'),
            ];

            // Gerar HTML do PDF
            $html = view('pdfs.cotacao-cdf', $dados)->render();

            // Gerar PDF usando mPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
            ]);

            $mpdf->WriteHTML($html);

            // Salvar PDF no storage
            $filename = 'cotacao_cdf_' . $cdf->id . '_' . time() . '.pdf';
            $path = 'cdfs/' . $filename;
            $fullPath = storage_path('app/public/' . $path);

            // Criar diretório se não existir
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $mpdf->Output($fullPath, 'F');

            // Atualizar caminho no banco
            $cdf->update(['cotacao_path' => $path]);

            Log::info('BaixarCotacaoCDF: PDF gerado com sucesso', [
                'cdf_id' => $cdf_id,
                'path' => $path
            ]);

            // Fazer download
            return response()->download($fullPath, 'Cotacao_CDF_' . $cdf->id . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erro ao baixar cotação CDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar cotação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Página dedicada para Cotação de Preços
     */
    /**
     * Salvar preço de item após concluir cotação no modal
     *
     * @param Request $request
     * @param int $id ID do orçamento
     * @return \Illuminate\Http\JsonResponse
     */
    public function salvarPrecoItem(Request $request, $id)
    {
        try {
            Log::info('💾 [SALVAR PREÇO ITEM] Iniciando salvamento', [
                'orcamento_id' => $id,
                'item_id' => $request->item_id,
                'preco_unitario' => $request->preco_unitario,
                'quantidade' => $request->quantidade
            ]);

            $orcamento = Orcamento::findOrFail($id);

            // Validar
            $validated = $request->validate([
                'item_id' => 'required|integer',
                'preco_unitario' => 'required|numeric|min:0',
                'quantidade' => 'required|numeric|min:0'
            ]);

            // Buscar item
            $item = $orcamento->itens()->findOrFail($validated['item_id']);

            Log::info('✅ [SALVAR PREÇO ITEM] Item encontrado', [
                'item_id' => $item->id,
                'descricao' => substr($item->descricao, 0, 50),
                'quantidade' => $item->quantidade
            ]);

            // Atualizar preço unitário
            $item->preco_unitario = $validated['preco_unitario'];
            $item->save();

            // Calcular preço total (quantidade × preço unitário)
            $preco_total_calculado = $item->quantidade * $item->preco_unitario;

            Log::info('✅ [SALVAR PREÇO ITEM] Preço salvo com sucesso', [
                'item_id' => $item->id,
                'preco_unitario' => $item->preco_unitario,
                'quantidade' => $item->quantidade,
                'preco_total_calculado' => $preco_total_calculado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Preço salvo com sucesso!',
                'item' => [
                    'id' => $item->id,
                    'preco_unitario' => $item->preco_unitario,
                    'quantidade' => $item->quantidade,
                    'preco_total' => $preco_total_calculado
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [SALVAR PREÇO ITEM] Erro ao salvar preço', [
                'orcamento_id' => $id,
                'item_id' => $request->item_id ?? null,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar preço: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cotarPrecos($orcamentoId, $itemId)
    {
        $orcamento = Orcamento::with('itens')->findOrFail($orcamentoId);
        $item = $orcamento->itens()->findOrFail($itemId);

        return view('orcamentos.cotar-precos', compact('orcamento', 'item'));
    }

    // ========================================
    // NOVAS APIS - INTEGRAÇÃO 15/10/2025
    // ========================================

    /**
     * Buscar no LicitaCon (TCE-RS) - BUSCA EM TEMPO REAL VIA CKAN
     *
     * ✅ SEM CACHE LOCAL - Busca direto na API CKAN do TCE-RS
     * API: https://dados.tce.rs.gov.br/api/3/action/datastore_search
     *
     * @param string $termo Termo de busca
     * @return array Array de resultados padronizados
     */
    private function buscarNoLicitaCon($termo)
    {
        try {
            Log::info('🟣 TCE-RS: Buscando itens REAIS via TceRsApiService', ['termo' => $termo]);

            $todoItens = [];

            // 1. Buscar ITENS DE CONTRATOS (preços reais contratados)
            $resultadoContratos = $this->tceRsApi->buscarItensContratos($termo, 100);

            if ($resultadoContratos['sucesso'] && !empty($resultadoContratos['dados'])) {
                Log::info('🟣 TCE-RS: Contratos encontrados', ['total' => count($resultadoContratos['dados'])]);

                foreach ($resultadoContratos['dados'] as $item) {
                    $todoItens[] = [
                        'descricao' => $item['descricao'],
                        'valor_unitario' => $item['valor_unitario'],
                        'unidade' => $item['unidade'] ?? 'UN',
                        'quantidade' => $item['quantidade'] ?? 1,
                        'orgao' => $item['orgao'],
                        'municipio' => '-',
                        'uf' => 'RS',
                        'fonte' => 'LICITACON',
                        'confiabilidade' => 'alta',
                        'data_publicacao' => null,
                        'numeroControlePNCP' => null,
                        'catmat' => $item['catmat'] ?? null,
                        'tipo' => 'CONTRATO'
                    ];
                }
            }

            // 2. Buscar ITENS DE LICITAÇÕES (valores de propostas)
            $resultadoLicitacoes = $this->tceRsApi->buscarItensLicitacoes($termo, 100);

            if ($resultadoLicitacoes['sucesso'] && !empty($resultadoLicitacoes['dados'])) {
                Log::info('🟣 TCE-RS: Licitações encontradas', ['total' => count($resultadoLicitacoes['dados'])]);

                foreach ($resultadoLicitacoes['dados'] as $item) {
                    $todoItens[] = [
                        'descricao' => $item['descricao'],
                        'valor_unitario' => $item['valor_unitario'],
                        'unidade' => $item['unidade'] ?? 'UN',
                        'quantidade' => $item['quantidade'] ?? 1,
                        'orgao' => $item['orgao'],
                        'municipio' => '-',
                        'uf' => 'RS',
                        'fonte' => 'LICITACON',
                        'confiabilidade' => 'media',
                        'data_publicacao' => null,
                        'numeroControlePNCP' => null,
                        'tipo' => 'LICITACAO'
                    ];
                }
            }

            if (empty($todoItens)) {
                Log::info('🟣 TCE-RS: Nenhum item encontrado');
                return [];
            }

            Log::info('🟣 TCE-RS OK', ['total' => count($todoItens)]);
            return $todoItens;

        } catch (\Exception $e) {
            Log::warning('🟣 Erro ao buscar no TCE-RS', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar no Compras.gov - API REST Pública
     *
     * URL: https://dadosabertos.compras.gov.br
     * Autenticação: NÃO PRECISA (API pública)
     * Swagger: https://dadosabertos.compras.gov.br/swagger-ui/index.html
     *
     * @param string $termo Termo de busca
     * @return array Array de resultados padronizados
     */
    /**
     * Buscar no CATMAT Local + API de Preços Compras.gov
     *
     * ESTRATÉGIA:
     * 1. Buscar materiais no CATMAT local (Full-Text Search em português)
     * 2. Para cada código encontrado, consultar API de Preços
     * 3. Retornar preços REAIS praticados recentemente
     *
     * API: https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial
     */
    private function buscarNoComprasGov($termo)
    {
        try {
            Log::info('🟢 COMPRASNET: Buscando itens via ComprasnetApiService', ['termo' => $termo]);

            // Buscar itens de contratos via API em tempo real
            $resultado = $this->comprasnetApi->buscarItensContratos($termo, [], 100);

            if (!$resultado['sucesso'] || empty($resultado['dados'])) {
                Log::info('🟢 COMPRASNET: Nenhum item encontrado');
                return [];
            }

            Log::info('🟢 COMPRASNET: Itens encontrados', ['total' => count($resultado['dados'])]);

            $itensPadronizados = [];

            foreach ($resultado['dados'] as $item) {
                // Extrair dados do contrato (enriquecidos pelo service)
                $contrato = $item['contrato'] ?? [];

                $itensPadronizados[] = [
                    'descricao' => $item['descricao'] ?? $item['descricaoDetalhada'] ?? 'Sem descrição',
                    'valor_unitario' => (float) ($item['valorUnitario'] ?? 0),
                    'unidade_medida' => $item['unidadeMedida'] ?? 'UN',
                    'quantidade' => (float) ($item['quantidade'] ?? 1),
                    'fornecedor' => $contrato['fornecedor'] ?? null,
                    'cnpj_fornecedor' => null,
                    'orgao' => $contrato['orgao'] ?? 'Órgão Federal',
                    'orgao_razao_social' => $contrato['orgao'] ?? 'Órgão Federal',
                    'municipio' => null,
                    'uf' => 'BR',
                    'data_vigencia' => $contrato['data_assinatura'] ?? null,
                    'fonte' => 'COMPRAS.GOV',
                    'codigo_catmat' => null,
                    'categoria' => null,
                    'numero_contrato' => $contrato['numero'] ?? null,
                    'confiabilidade' => 'alta'
                ];
            }

            Log::info('✅ COMPRASNET OK', ['total' => count($itensPadronizados)]);
            return $itensPadronizados;

        } catch (\Exception $e) {
            Log::warning('🟢 Erro ao buscar no Comprasnet', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar no Portal da Transparência (CGU) - API REST com Chave
     *
     * URL: https://api.portaldatransparencia.gov.br/api-de-dados
    /**
     * Buscar no Portal da Transparência (CGU) - API REST com Chave
     *
     * URL: https://api.portaldatransparencia.gov.br/api-de-dados
     * Autenticação: OBRIGATÓRIA via header 'chave-api-dados'
     * Chave: 319215bff3b6753f5e1e4105c58a55e9
     *
     * ESTRATÉGIA:
     * 1. Buscar contratos recentes (/contratos)
     * 2. Para cada contrato, buscar itens contratados (/contratos/itens-contratados?id=...)
     * 3. Filtrar por termo de busca
     *
     * @param string $termo Termo de busca
     * @return array Array de resultados padronizados
     */
    private function buscarNoPortalTransparencia($termo)
    {
        try {
            Log::info('🟡 Buscando no Portal da Transparência (CGU) - Contratos', ['termo' => $termo]);

            // Chave obrigatória
            $apiKey = env('PORTALTRANSPARENCIA_API_KEY', '319215bff3b6753f5e1e4105c58a55e9');

            if (empty($apiKey)) {
                Log::warning('🟡 Chave do Portal da Transparência não configurada');
                return [];
            }

            $todosItens = [];

            // ⚠️ AVISO: Endpoint /contratos exige parâmetro "codigoOrgao" (obrigatório)
            // Como não sabemos qual órgão buscar, vamos desabilitar temporariamente
            // TODO: Implementar busca por notas fiscais (/notas-fiscais) que não exige código de órgão

            Log::info('🟡 Portal Transparência: Temporariamente desabilitado (endpoint /contratos exige codigoOrgao)');
            return [];

            /*
            // ✅ ETAPA 1: Buscar contratos recentes (últimos 180 dias)
            $dataFinal = date('d/m/Y');
            $dataInicial = date('d/m/Y', strtotime('-180 days'));

            $urlContratos = 'https://api.portaldatransparencia.gov.br/api-de-dados/contratos';

            $responseContratos = Http::withHeaders([
                'chave-api-dados' => $apiKey,
                'Accept' => 'application/json'
            ])
            ->timeout(20)
            ->get($urlContratos, [
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
                'codigoOrgao' => '???', // ⚠️ OBRIGATÓRIO mas não sabemos qual usar
                'pagina' => 1
            ]);

            if (!$responseContratos->successful()) {
                Log::warning('🟡 Portal Transparência /contratos retornou erro', [
                    'status' => $responseContratos->status(),
                    'body' => substr($responseContratos->body(), 0, 300)
                ]);
                return [];
            }
            */

            $contratos = $responseContratos->json();
            $listaContratos = is_array($contratos) ? $contratos : [];

            if (empty($listaContratos)) {
                Log::info('🟡 Portal Transparência: 0 contratos encontrados');
                return [];
            }

            Log::info('🟡 Portal Transparência: ' . count($listaContratos) . ' contratos encontrados');

            // ✅ ETAPA 2: Para cada contrato, buscar itens contratados
            foreach (array_slice($listaContratos, 0, 20) as $contrato) {
                $idContrato = $contrato['id'] ?? null;

                if (!$idContrato) {
                    continue;
                }

                try {
                    $urlItens = 'https://api.portaldatransparencia.gov.br/api-de-dados/contratos/itens-contratados';

                    $responseItens = Http::withHeaders([
                        'chave-api-dados' => $apiKey,
                        'Accept' => 'application/json'
                    ])
                    ->timeout(10)
                    ->get($urlItens, [
                        'id' => $idContrato,
                        'pagina' => 1
                    ]);

                    if ($responseItens->successful()) {
                        $itensContrato = $responseItens->json();
                        $listaItens = is_array($itensContrato) ? $itensContrato : [];

                        // Filtrar por termo de busca
                        foreach ($listaItens as $item) {
                            $descricao = strtolower($item['descricao'] ?? $item['descricaoItem'] ?? '');
                            if (stripos($descricao, strtolower($termo)) !== false) {
                                $todosItens[] = array_merge($item, [
                                    'contrato_id' => $idContrato,
                                    'orgao_contrato' => $contrato['nomeOrgao'] ?? 'Gov Federal'
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('🟡 Erro ao buscar itens do contrato ' . $idContrato);
                    continue;
                }

                // Limitar busca para não estourar timeout
                if (count($todosItens) >= 100) {
                    break;
                }
            }

            if (empty($todosItens)) {
                Log::info('🟡 Portal Transparência: 0 itens após filtro');
                return [];
            }

            // ✅ Transformar para formato padronizado
            $itensPadronizados = array_map(function($item) {
                return [
                    'descricao' => $item['descricao'] ?? $item['descricaoItem'] ?? '-',
                    'valor_unitario' => (float) ($item['valorUnitario'] ?? $item['valorItem'] ?? $item['valor'] ?? 0),
                    'unidade' => $item['unidadeFornecimento'] ?? $item['unidade'] ?? 'UN',
                    'quantidade' => (float) ($item['quantidade'] ?? 1),
                    'orgao' => $item['orgao_contrato'] ?? $item['nomeOrgao'] ?? 'Gov Federal',
                    'municipio' => $item['municipio'] ?? '-',
                    'uf' => $item['uf'] ?? 'BR',
                    'fonte' => 'PORTAL_TRANSPARENCIA',
                    'confiabilidade' => 'alta',
                    'data_publicacao' => $item['dataAssinatura'] ?? $item['data'] ?? null,
                    'numeroControlePNCP' => null,
                    'link_fonte' => 'https://portaldatransparencia.gov.br/'
                ];
            }, $todosItens);

            Log::info('🟡 Portal Transparência OK - Itens contratados', ['total' => count($itensPadronizados)]);

            return $itensPadronizados;

        } catch (\Exception $e) {
            Log::warning('🟡 Erro geral ao buscar no Portal da Transparência', [
                'erro' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Buscar TODAS as justificativas e observações de um item específico
     * Agregado de múltiplas fontes do sistema
     *
     * @param int $orcamento_id
     * @param int $item_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function buscarJustificativasItem($orcamento_id, $item_id)
    {
        try {
            $justificativas = collect();

            // Buscar o item
            $item = OrcamentoItem::find($item_id);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ], 404);
            }

            // 1. JUSTIFICATIVA DO PRÓPRIO ITEM
            if (!empty($item->justificativa_cotacao)) {
                $justificativas->push([
                    'tipo' => 'Item do Orçamento',
                    'icone' => '📋',
                    'data' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    'titulo' => 'Justificativa de Cotação',
                    'conteudo' => $item->justificativa_cotacao,
                    'ordem' => 1
                ]);
            }

            // 2. JUSTIFICATIVAS DE CDFs RELACIONADAS AO ITEM
            $cdfsDoItem = DB::table('cp_solicitacao_cdf_itens')
                ->where('orcamento_item_id', $item_id)
                ->join('cp_solicitacoes_cdf', 'cp_solicitacao_cdf_itens.solicitacao_cdf_id', '=', 'cp_solicitacoes_cdf.id')
                ->select('cp_solicitacoes_cdf.*')
                ->get();

            foreach ($cdfsDoItem as $cdf) {
                // Observação geral da CDF
                if (!empty($cdf->observacao)) {
                    $justificativas->push([
                        'tipo' => 'Cotação Direta (CDF)',
                        'icone' => '📌',
                        'data' => $cdf->created_at ?? null,
                        'titulo' => 'Observações da Solicitação CDF',
                        'fornecedor' => $cdf->razao_social ?? null,
                        'conteudo' => $cdf->observacao,
                        'ordem' => 2
                    ]);
                }

                // Justificativa "outro"
                if (!empty($cdf->justificativa_outro)) {
                    $justificativas->push([
                        'tipo' => 'Cotação Direta (CDF)',
                        'icone' => '📌',
                        'data' => $cdf->created_at ?? null,
                        'titulo' => 'Justificativa da CDF',
                        'fornecedor' => $cdf->razao_social ?? null,
                        'conteudo' => $cdf->justificativa_outro,
                        'ordem' => 2
                    ]);
                }

                // Justificativas booleanas
                $justificativasBooleanas = [];
                if (!empty($cdf->justificativa_fornecedor_unico)) {
                    $justificativasBooleanas[] = '✓ Fornecedor único na região';
                }
                if (!empty($cdf->justificativa_melhor_preco)) {
                    $justificativasBooleanas[] = '✓ Melhor preço';
                }
                if (!empty($cdf->justificativa_produto_exclusivo)) {
                    $justificativasBooleanas[] = '✓ Produto exclusivo';
                }
                if (!empty($cdf->justificativa_urgencia)) {
                    $justificativasBooleanas[] = '✓ Urgência na aquisição';
                }

                if (!empty($justificativasBooleanas)) {
                    $justificativas->push([
                        'tipo' => 'Cotação Direta (CDF)',
                        'icone' => '📌',
                        'data' => $cdf->created_at ?? null,
                        'titulo' => 'Justificativas da Solicitação',
                        'fornecedor' => $cdf->razao_social ?? null,
                        'conteudo' => implode("\n", $justificativasBooleanas),
                        'ordem' => 2
                    ]);
                }

                // Buscar respostas da CDF
                $respostasCDF = DB::table('cp_respostas_cdf')
                    ->where('solicitacao_cdf_id', $cdf->id)
                    ->get();

                foreach ($respostasCDF as $resposta) {
                    // Observações gerais da resposta
                    if (!empty($resposta->observacoes_gerais)) {
                        $justificativas->push([
                            'tipo' => 'Resposta CDF',
                            'icone' => '✅',
                            'data' => $resposta->data_resposta ?? $resposta->created_at ?? null,
                            'titulo' => 'Observações Gerais do Fornecedor',
                            'fornecedor' => $cdf->razao_social ?? null,
                            'conteudo' => $resposta->observacoes_gerais,
                            'ordem' => 3
                        ]);
                    }

                    // Buscar observações específicas dos itens respondidos
                    $itensRespondidos = DB::table('cp_resposta_cdf_itens')
                        ->where('resposta_cdf_id', $resposta->id)
                        ->where('item_orcamento_id', $item_id)
                        ->get();

                    foreach ($itensRespondidos as $itemResp) {
                        if (!empty($itemResp->observacoes)) {
                            $justificativas->push([
                                'tipo' => 'Resposta CDF - Item',
                                'icone' => '✅',
                                'data' => $resposta->data_resposta ?? $resposta->created_at ?? null,
                                'titulo' => 'Observações do Fornecedor sobre o Item',
                                'fornecedor' => $cdf->razao_social ?? null,
                                'conteudo' => $itemResp->observacoes,
                                'marca' => $itemResp->marca ?? null,
                                'preco' => $itemResp->preco_unitario ?? null,
                                'ordem' => 3
                            ]);
                        }
                    }
                }
            }

            // 3. OBSERVAÇÕES DE CONTRATAÇÕES SIMILARES
            // Verificar se existe tabela de relação entre contratação similar e item
            $tabelaExiste = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'cp_contratacao_similar_itens') as exists");

            if ($tabelaExiste[0]->exists) {
                $contratacoesDoItem = DB::table('cp_contratacao_similar_itens')
                    ->where('orcamento_item_id', $item_id)
                    ->join('cp_contratacoes_similares', 'cp_contratacao_similar_itens.contratacao_similar_id', '=', 'cp_contratacoes_similares.id')
                    ->select('cp_contratacoes_similares.*')
                    ->get();

                foreach ($contratacoesDoItem as $contratacao) {
                    if (!empty($contratacao->justificativa_expurgo)) {
                        $justificativas->push([
                            'tipo' => 'Contratação Similar',
                            'icone' => '🏛️',
                            'data' => $contratacao->created_at ?? null,
                            'titulo' => 'Justificativa de Expurgo',
                            'ente' => $contratacao->ente_publico ?? null,
                            'conteudo' => $contratacao->justificativa_expurgo,
                            'ordem' => 4
                        ]);
                    }
                }
            }

            // 4. OBSERVAÇÕES DE FORNECEDORES (se item tem fornecedor vinculado)
            if (!empty($item->fornecedor_cnpj)) {
                $fornecedor = DB::table('cp_fornecedores')
                    ->where('numero_documento', $item->fornecedor_cnpj)
                    ->first();

                if ($fornecedor && !empty($fornecedor->observacoes)) {
                    $justificativas->push([
                        'tipo' => 'Fornecedor',
                        'icone' => '🏢',
                        'data' => $fornecedor->created_at ?? null,
                        'titulo' => 'Observações do Cadastro do Fornecedor',
                        'fornecedor' => $fornecedor->razao_social ?? $item->fornecedor_nome ?? null,
                        'conteudo' => $fornecedor->observacoes,
                        'ordem' => 5
                    ]);
                }
            }

            // Ordenar por data (mais recente primeiro) e depois por ordem de prioridade
            $justificativas = $justificativas->sortBy([
                ['data', 'desc'],
                ['ordem', 'asc']
            ])->values();

            return response()->json([
                'success' => true,
                'total' => $justificativas->count(),
                'justificativas' => $justificativas
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar justificativas do item', [
                'item_id' => $item_id,
                'erro' => $e->getMessage(),
                'linha' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar justificativas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📜 BUSCAR LOGS DE AUDITORIA DE UM ITEM
     * Retorna histórico de ações realizadas no item
     * Data: 18/10/2025
     */
    public function getAuditLogs($orcamento_id, $item_id)
    {
        try {
            // Buscar logs de auditoria do item ordenados por data (mais recente primeiro)
            $logs = \App\Models\AuditLogItem::where('item_id', $item_id)
                ->with('usuario:id,name')
                ->orderBy('created_at', 'DESC')
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'event_type' => $log->event_type,
                        'label' => $log->label, // Getter do modelo
                        'icone' => $log->icone, // Getter do modelo
                        'cor' => $log->cor, // Getter do modelo
                        'usuario_nome' => $log->usuario_nome ?? ($log->usuario ? $log->usuario->name : 'Sistema'),
                        'sample_number' => $log->sample_number,
                        'before_value' => $log->before_value,
                        'after_value' => $log->after_value,
                        'rule_applied' => $log->rule_applied,
                        'justification' => $log->justification,
                        'created_at' => $log->created_at->toIso8601String(), // ✅ NOVO: Data ISO para JavaScript
                        'data_hora' => $log->created_at->format('d/m/Y H:i:s'),
                        'data_relativa' => $log->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'total' => $logs->count(),
                'logs' => $logs
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar logs de auditoria', [
                'item_id' => $item_id,
                'erro' => $e->getMessage(),
                'linha' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar logs de auditoria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📄 GERAR PDF DO ORÇAMENTO ESTIMATIVO
     * Layout idêntico ao completinho.pdf
     * Data: 18/10/2025
     */
    public function gerarPDF($id)
    {
        try {
            Log::info('📄 Gerando PDF do orçamento', ['orcamento_id' => $id]);

            // Buscar orçamento com todos os relacionamentos
            $orcamento = Orcamento::with(['itens.lote', 'usuario', 'orgao'])->findOrFail($id);

            // ========================================
            // CACHE DE PDF - Performance Optimization
            // ========================================
            $cacheKey = "pdf_orcamento_{$id}_" . $orcamento->updated_at->timestamp;
            $cachePath = storage_path("app/cache/pdfs/orcamento_{$id}.pdf");
            $cacheDir = dirname($cachePath);

            Log::info('🔍 Verificando cache', [
                'path' => $cachePath,
                'exists' => file_exists($cachePath),
                'dir_writable' => is_writable($cacheDir)
            ]);

            // Verificar se PDF em cache existe e está atualizado
            if (file_exists($cachePath)) {
                $cacheTimestamp = filemtime($cachePath);
                $orcamentoTimestamp = $orcamento->updated_at->timestamp;

                // Se cache é mais novo que última atualização do orçamento, usar cache
                if ($cacheTimestamp >= $orcamentoTimestamp) {
                    Log::info('✅ SERVINDO DO CACHE (RÁPIDO!)', [
                        'orcamento_id' => $id,
                        'cache_age' => time() - $cacheTimestamp
                    ]);

                    return response()->file($cachePath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="Orcamento_Estimativo_' . $orcamento->numero . '.pdf"'
                    ]);
                }
            }

            Log::info('⚙️ GERANDO PDF (SEM CACHE)', ['orcamento_id' => $id]);

            // Buscar dados do órgão
            // PRIORIDADE 1: Dados do cadastro de órgão vinculado ao orçamento (orgao_id)
            // PRIORIDADE 2: Primeiro órgão cadastrado (guia Configurações)
            // PRIORIDADE 3: Dados do orçamentista (inseridos manualmente no orçamento)
            if ($orcamento->orgao_id && $orcamento->orgao) {
                $orgao = $orcamento->orgao;
            } else {
                // Buscar órgão padrão das configurações
                $orgao = Orgao::first();

                // Se não houver órgão cadastrado, usar dados do orçamentista como fallback
                if (!$orgao) {
                    $orgao = (object) [
                        'nome' => $orcamento->orcamentista_razao_social ?? 'ÓRGÃO',
                        'razao_social' => $orcamento->orcamentista_razao_social ?? null,
                        'nome_fantasia' => $orcamento->orcamentista_nome ?? null,
                        'cnpj' => $orcamento->orcamentista_cpf_cnpj ?? null,
                        'usuario' => $orcamento->orcamentista_setor ?? '',
                        'endereco' => trim(
                            ($orcamento->orcamentista_endereco ?? '') . ', ' .
                            ($orcamento->orcamentista_cidade ?? '') . ' - ' .
                            ($orcamento->orcamentista_uf ?? '') . ' ' .
                            ($orcamento->orcamentista_cep ?? '')
                        ),
                        'numero' => null,
                        'complemento' => null,
                        'bairro' => null,
                        'cep' => $orcamento->orcamentista_cep ?? null,
                        'cidade' => $orcamento->orcamentista_cidade ?? null,
                        'uf' => $orcamento->orcamentista_uf ?? null,
                        'telefone' => null,
                        'email' => null,
                        'brasao_path' => $orcamento->brasao_path ?? null,
                        'responsavel_nome' => $orcamento->orcamentista_nome ?? null,
                        'responsavel_matricula_siape' => null,
                        'responsavel_cargo' => $orcamento->orcamentista_setor ?? null,
                        'responsavel_portaria' => $orcamento->orcamentista_portaria ?? null,
                    ];
                }
            }

            // Processar cada item para incluir análise estatística
            foreach ($orcamento->itens as $item) {
                // Decodificar amostras JSON
                $amostras = $item->amostras_selecionadas
                    ? json_decode($item->amostras_selecionadas, true)
                    : [];

                // Calcular estatísticas se houver amostras
                if (count($amostras) > 0) {
                    $item->estatisticas = $this->calcularEstatisticas($amostras);
                    $item->amostras_processadas = $amostras;
                } else {
                    $item->estatisticas = null;
                    $item->amostras_processadas = [];
                }
            }

            // Calcular Curva ABC
            $curvaABC = $this->calcularCurvaABC($orcamento->itens);

            // Buscar solicitações CDF relacionadas ao orçamento (usar relacionamento Eloquent)
            $solicitacoesCDF = $orcamento->solicitacoesCDF;

            // Buscar anexos relacionados (usar relacionamento Eloquent se existir)
            $anexos = Anexo::where('orcamento_id', $id)->get();

            // Renderizar view do PDF
            $pdf = \PDF::loadView('orcamentos.pdf', [
                'orcamento' => $orcamento,
                'orgao' => $orgao,
                'curvaABC' => $curvaABC,
                'solicitacoesCDF' => $solicitacoesCDF,
                'anexos' => $anexos
            ]);

            // Configurar tamanho A4
            $pdf->setPaper('A4', 'portrait');

            // ========================================
            // SALVAR PDF NO CACHE
            // ========================================
            try {
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0775, true);
                    chmod($cacheDir, 0775);
                }

                $pdf->save($cachePath);
                chmod($cachePath, 0664);

                Log::info('✅ PDF SALVO NO CACHE!', [
                    'id' => $id,
                    'path' => $cachePath,
                    'size_kb' => round(filesize($cachePath) / 1024, 2)
                ]);
            } catch (\Exception $e) {
                Log::error('❌ ERRO AO SALVAR CACHE', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            // Retornar PDF para download
            return $pdf->download("Orcamento_Estimativo_{$orcamento->numero}.pdf");

        } catch (\Exception $e) {
            Log::error('Erro ao gerar PDF do orçamento', [
                'orcamento_id' => $id,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Erro ao gerar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Formatar endereço completo do orçamentista
     */
    private function formatarEnderecoOrcamentista($orcamento)
    {
        $partes = [];

        if (!empty($orcamento->orcamentista_endereco)) {
            $partes[] = $orcamento->orcamentista_endereco;
        }

        // Adicionar cidade/UF
        if (!empty($orcamento->orcamentista_cidade) && !empty($orcamento->orcamentista_uf)) {
            $partes[] = $orcamento->orcamentista_cidade . '/' . $orcamento->orcamentista_uf;
        } elseif (!empty($orcamento->orcamentista_cidade)) {
            $partes[] = $orcamento->orcamentista_cidade;
        }

        // Adicionar CEP
        if (!empty($orcamento->orcamentista_cep)) {
            $partes[] = 'CEP: ' . $orcamento->orcamentista_cep;
        }

        return !empty($partes) ? implode(' - ', $partes) : 'N/A';
    }

    /**
     * Coletar TODOS os anexos de TODAS as fontes (amostras, CDF, e-commerce, itens, orçamento)
     */
    private function coletarTodosAnexos($orcamento, $solicitacoesCDF)
    {
        $anexosColetados = [];

        // 1. Anexos diretos do orçamento (orcamento_id)
        $anexosOrcamento = Anexo::where('orcamento_id', $orcamento->id)
            ->whereNull('item_id')
            ->whereNull('amostra_id')
            ->get();

        foreach ($anexosOrcamento as $anexo) {
            $anexosColetados[] = [
                'tipo' => $anexo->tipo,
                'tipo_label' => $anexo->tipo_label,
                'nome_arquivo' => $anexo->nome_arquivo,
                'caminho' => $anexo->caminho,
                'caminho_absoluto' => storage_path('app/' . $anexo->caminho),
                'tamanho' => $anexo->tamanho_formatado,
                'mime_type' => $anexo->mime_type,
                'paginas' => $anexo->paginas,
                'origem' => 'ORÇAMENTO',
                'data_upload' => $anexo->created_at->format('d/m/Y H:i'),
            ];
        }

        // 2. Anexos dos itens do orçamento
        foreach ($orcamento->itens as $item) {
            // 2a. Anexos diretos do item
            $anexosItem = Anexo::where('item_id', $item->id)
                ->whereNull('amostra_id')
                ->get();

            foreach ($anexosItem as $anexo) {
                $anexosColetados[] = [
                    'tipo' => $anexo->tipo,
                    'tipo_label' => $anexo->tipo_label,
                    'nome_arquivo' => $anexo->nome_arquivo,
                    'caminho' => $anexo->caminho,
                    'caminho_absoluto' => storage_path('app/' . $anexo->caminho),
                    'tamanho' => $anexo->tamanho_formatado,
                    'mime_type' => $anexo->mime_type,
                    'paginas' => $anexo->paginas,
                    'origem' => 'ITEM #' . $item->numero_item . ' - ' . $item->descricao,
                    'data_upload' => $anexo->created_at->format('d/m/Y H:i'),
                ];
            }

            // 2b. Anexos das amostras (verificar anexo_ids no JSON)
            $amostras = $item->amostras_selecionadas
                ? json_decode($item->amostras_selecionadas, true)
                : [];

            foreach ($amostras as $indexAmostra => $amostra) {
                // Verificar se há anexo_ids no JSON da amostra
                if (isset($amostra['anexo_ids']) && is_array($amostra['anexo_ids'])) {
                    foreach ($amostra['anexo_ids'] as $anexoId) {
                        $anexo = Anexo::find($anexoId);
                        if ($anexo) {
                            $fornecedor = $amostra['fornecedor_nome'] ?? 'N/A';
                            $anexosColetados[] = [
                                'tipo' => $anexo->tipo,
                                'tipo_label' => $anexo->tipo_label,
                                'nome_arquivo' => $anexo->nome_arquivo,
                                'caminho' => $anexo->caminho,
                                'caminho_absoluto' => storage_path('app/' . $anexo->caminho),
                                'tamanho' => $anexo->tamanho_formatado,
                                'mime_type' => $anexo->mime_type,
                                'paginas' => $anexo->paginas,
                                'origem' => 'AMOSTRA #' . ($indexAmostra + 1) . ' - Item #' . $item->numero_item . ' - ' . $fornecedor,
                                'data_upload' => $anexo->created_at->format('d/m/Y H:i'),
                            ];
                        }
                    }
                }
            }
        }

        // 3. Anexos de solicitações CDF
        foreach ($solicitacoesCDF as $cdf) {
            // 3a. Anexo via anexo_id (proposta CDF)
            if ($cdf->anexo_id) {
                $anexo = Anexo::find($cdf->anexo_id);
                if ($anexo) {
                    $anexosColetados[] = [
                        'tipo' => $anexo->tipo,
                        'tipo_label' => $anexo->tipo_label,
                        'nome_arquivo' => $anexo->nome_arquivo,
                        'caminho' => $anexo->caminho,
                        'caminho_absoluto' => storage_path('app/' . $anexo->caminho),
                        'tamanho' => $anexo->tamanho_formatado,
                        'mime_type' => $anexo->mime_type,
                        'paginas' => $anexo->paginas,
                        'origem' => 'CDF - ' . $cdf->fornecedor_nome . ' (CNPJ: ' . $cdf->fornecedor_cnpj . ')',
                        'data_upload' => $anexo->created_at->format('d/m/Y H:i'),
                    ];
                }
            }

            // 3b. Arquivo CNPJ (pode ser caminho direto sem estar na tabela anexos)
            if ($cdf->arquivo_cnpj && file_exists(storage_path('app/' . $cdf->arquivo_cnpj))) {
                // Verificar se já não foi adicionado via anexo_id
                $jaAdicionado = false;
                foreach ($anexosColetados as $anexoAdicionado) {
                    if (isset($anexoAdicionado['caminho']) && $anexoAdicionado['caminho'] === $cdf->arquivo_cnpj) {
                        $jaAdicionado = true;
                        break;
                    }
                }

                if (!$jaAdicionado) {
                    $caminhoCompleto = storage_path('app/' . $cdf->arquivo_cnpj);
                    $tamanhoBytes = file_exists($caminhoCompleto) ? filesize($caminhoCompleto) : 0;
                    $tamanhoFormatado = $this->formatarTamanhoBytes($tamanhoBytes);

                    $anexosColetados[] = [
                        'tipo' => 'ESPELHO_CNPJ',
                        'tipo_label' => 'Espelho CNPJ',
                        'nome_arquivo' => basename($cdf->arquivo_cnpj),
                        'caminho' => $cdf->arquivo_cnpj,
                        'caminho_absoluto' => $caminhoCompleto,
                        'tamanho' => $tamanhoFormatado,
                        'mime_type' => 'application/pdf',
                        'paginas' => null,
                        'origem' => 'CDF - ' . $cdf->fornecedor_nome . ' (CNPJ: ' . $cdf->fornecedor_cnpj . ')',
                        'data_upload' => $cdf->created_at ? $cdf->created_at->format('d/m/Y H:i') : 'N/A',
                    ];
                }
            }
        }

        // 4. Anexos de coletas de e-commerce (se houver implementação futura)
        // TODO: Implementar quando houver anexos em e-commerce

        return $anexosColetados;
    }

    /**
     * Formatar tamanho em bytes para formato legível
     */
    private function formatarTamanhoBytes($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Calcular estatísticas das amostras
     */
    private function calcularEstatisticas($amostras)
    {
        if (empty($amostras)) {
            return null;
        }

        // Extrair valores unitários
        $valores = array_map(fn($a) => (float) ($a['valor_unitario'] ?? 0), $amostras);
        $valores = array_filter($valores, fn($v) => $v > 0);

        if (empty($valores)) {
            return null;
        }

        sort($valores);

        $n = count($valores);
        $media = array_sum($valores) / $n;

        // Calcular desvio padrão
        $soma_quadrados = array_sum(array_map(fn($v) => pow($v - $media, 2), $valores));
        $variancia = $soma_quadrados / $n;
        $desvio_padrao = sqrt($variancia);

        // Calcular mediana
        if ($n % 2 == 0) {
            $mediana = ($valores[$n/2 - 1] + $valores[$n/2]) / 2;
        } else {
            $mediana = $valores[floor($n/2)];
        }

        // Calcular coeficiente de variação
        $coef_variacao = $media > 0 ? ($desvio_padrao / $media) * 100 : 0;

        // Determinar método (STJ: CV <= 25% = média, CV > 25% = mediana)
        $metodo = $coef_variacao <= 25 ? 'MÉDIA ARITMÉTICA' : 'MEDIANA';
        $valor_final = $coef_variacao <= 25 ? $media : $mediana;

        // Calcular limites para expurgo
        $limite_inferior = $media - $desvio_padrao;
        $limite_superior = $media + $desvio_padrao;

        // Identificar amostras expurgadas
        $expurgadas = [];
        foreach ($amostras as $idx => $amostra) {
            $valor = (float) ($amostra['valor_unitario'] ?? 0);
            if ($valor < $limite_inferior || $valor > $limite_superior) {
                $expurgadas[] = $idx;
            }
        }

        return [
            'num_amostras_coletadas' => count($amostras),
            'num_amostras_validas' => $n - count($expurgadas),
            'media' => $media,
            'mediana' => $mediana,
            'desvio_padrao' => $desvio_padrao,
            'coef_variacao' => $coef_variacao,
            'limite_inferior' => $limite_inferior,
            'limite_superior' => $limite_superior,
            'menor_preco' => min($valores),
            'maior_preco' => max($valores),
            'metodo' => $metodo,
            'valor_final' => $valor_final,
            'amostras_expurgadas' => $expurgadas
        ];
    }

    /**
     * Calcular Curva ABC dos itens
     */
    private function calcularCurvaABC($itens)
    {
        if ($itens->isEmpty()) {
            return [];
        }

        // Calcular valor total de cada item
        $itensComValor = $itens->map(function($item) {
            $valorTotal = $item->quantidade * $item->preco_unitario;
            return [
                'item' => $item,
                'valor_total' => $valorTotal
            ];
        });

        // Ordenar por valor total decrescente
        $itensOrdenados = $itensComValor->sortByDesc('valor_total')->values();

        // Calcular valor global
        $valorGlobal = $itensOrdenados->sum('valor_total');

        // Classificar em faixas A, B, C
        $acumulado = 0;
        $curva = [];

        foreach ($itensOrdenados as $itemData) {
            $valorItem = $itemData['valor_total'];
            $participacao = $valorGlobal > 0 ? ($valorItem / $valorGlobal) * 100 : 0;
            $acumulado += $participacao;

            // Classificação: A (0-80%), B (80-95%), C (95-100%)
            if ($acumulado <= 80) {
                $faixa = 'A';
            } elseif ($acumulado <= 95) {
                $faixa = 'B';
            } else {
                $faixa = 'C';
            }

            $curva[] = [
                'descricao' => $itemData['item']->descricao,
                'numero_item' => $itemData['item']->numero_item,
                'valor_total' => $valorItem,
                'participacao' => $participacao,
                'participacao_acumulada' => $acumulado,
                'faixa' => $faixa
            ];
        }

        return $curva;
    }

    /**
     * Aplica saneamento estatístico em um item
     * FASE 2 - POST /orcamentos/{id}/itens/{item_id}/aplicar-saneamento
     */
    public function aplicarSaneamento(Request $request, $id, $item_id)
    {
        try {
            $validated = $request->validate([
                'metodo' => 'required|in:DP_MEDIA,PERCENTUAL_MEDIANA',
                'percentual_inf' => 'nullable|numeric|min:0|max:100',
                'percentual_sup' => 'nullable|numeric|min:0|max:100',
            ]);

            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Buscar amostras do item
            $amostrasJson = $item->amostras_selecionadas;
            if (!$amostrasJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não possui amostras de preços.'
                ], 400);
            }

            $amostras = json_decode($amostrasJson, true);
            if (!is_array($amostras) || empty($amostras)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amostras inválidas ou vazias.'
                ], 400);
            }

            // Aplicar saneamento
            $service = new \App\Services\EstatisticaService();

            if ($validated['metodo'] === 'DP_MEDIA') {
                $resultado = $service->aplicarSaneamentoDP($amostras);
            } else {
                $resultado = $service->aplicarSaneamentoPercentual(
                    $amostras,
                    $validated['percentual_inf'] ?? 70,
                    $validated['percentual_sup'] ?? 30
                );
            }

            // Atualizar JSON das amostras com situação
            $item->amostras_selecionadas = json_encode($resultado['amostras']);

            // Salvar snapshot no item (sem carimbar ainda)
            $item->update(array_merge(
                ['amostras_selecionadas' => json_encode($resultado['amostras'])],
                $resultado['snapshot']
            ));

            \Log::info('Saneamento aplicado com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'metodo' => $validated['metodo'],
                'n_validas' => $resultado['snapshot']['calc_n_validas']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saneamento aplicado com sucesso!',
                'snapshot' => $resultado['snapshot'],
                'amostras_expurgadas' => count(array_filter($resultado['amostras'], fn($a) => ($a['situacao'] ?? '') === 'EXPURGADA'))
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao aplicar saneamento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao aplicar saneamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fixa o snapshot de cálculos (botão "Fixar")
     * FASE 2 - POST /orcamentos/{id}/itens/{item_id}/fixar-snapshot
     */
    public function fixarSnapshot($id, $item_id)
    {
        try {
            $item = OrcamentoItem::where('id', $item_id)
                ->where('orcamento_id', $id)
                ->firstOrFail();

            // Validar se há cálculos
            if (!$item->calc_n_validas) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aplique o saneamento antes de fixar o snapshot.'
                ], 400);
            }

            // Carimbar timestamp
            $item->update([
                'calc_carimbado_em' => now()
            ]);

            \Log::info('Snapshot fixado com sucesso', [
                'orcamento_id' => $id,
                'item_id' => $item_id,
                'carimbado_em' => $item->calc_carimbado_em
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Snapshot fixado com sucesso!',
                'carimbado_em' => $item->calc_carimbado_em->format('d/m/Y H:i:s')
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao fixar snapshot: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao fixar snapshot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna o snapshot mais recente de um item
     * GET /orcamentos/{id}/itens/{item_id}/snapshot
     */
    public function getSnapshot($id, $item_id)
    {
        try {
            // Buscar snapshot mais recente do item
            $snapshot = \App\Models\AuditSnapshot::where('item_id', $item_id)
                ->orderBy('snapshot_timestamp', 'DESC')
                ->first();

            if (!$snapshot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum snapshot encontrado para este item.',
                    'snapshot' => null
                ], 200);
            }

            return response()->json([
                'success' => true,
                'snapshot' => [
                    'id' => $snapshot->id,
                    'timestamp' => $snapshot->snapshot_timestamp->format('d/m/Y H:i:s'),
                    'timestamp_relativo' => $snapshot->snapshot_timestamp->diffForHumans(),
                    'n_validas' => $snapshot->n_validas,
                    'media' => $snapshot->media ? number_format($snapshot->media, 4, ',', '.') : null,
                    'mediana' => $snapshot->mediana ? number_format($snapshot->mediana, 4, ',', '.') : null,
                    'desvio_padrao' => $snapshot->desvio_padrao ? number_format($snapshot->desvio_padrao, 4, ',', '.') : null,
                    'coef_variacao' => $snapshot->coef_variacao ? number_format($snapshot->coef_variacao, 2, ',', '.') . '%' : null,
                    'limite_inferior' => $snapshot->limite_inferior ? number_format($snapshot->limite_inferior, 4, ',', '.') : null,
                    'limite_superior' => $snapshot->limite_superior ? number_format($snapshot->limite_superior, 4, ',', '.') : null,
                    'metodo' => $snapshot->metodo,
                    'hash_sha256' => $snapshot->hash_sha256,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar snapshot', [
                'item_id' => $item_id,
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar snapshot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcula e salva Curva ABC do orçamento (Service-based)
     * FASE 2 - POST /orcamentos/{id}/calcular-e-salvar-curva-abc
     */
    public function calcularESalvarCurvaABC($id)
    {
        try {
            $orcamento = Orcamento::findOrFail($id);
            $itens = $orcamento->itens;

            if ($itens->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orçamento não possui itens.'
                ], 400);
            }

            // Calcular Curva ABC
            $service = new \App\Services\CurvaABCService();
            $curvaABC = $service->calcular($itens);

            // Salvar snapshot em cada item
            $service->salvarSnapshot($itens, $curvaABC);

            // Gerar estatísticas
            $estatisticas = $service->gerarEstatisticas($curvaABC);

            \Log::info('Curva ABC calculada com sucesso', [
                'orcamento_id' => $id,
                'total_itens' => count($curvaABC),
                'classe_A' => $estatisticas['A']['quantidade_itens'],
                'classe_B' => $estatisticas['B']['quantidade_itens'],
                'classe_C' => $estatisticas['C']['quantidade_itens']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Curva ABC calculada com sucesso!',
                'curva' => $curvaABC,
                'estatisticas' => $estatisticas
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao calcular Curva ABC: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular Curva ABC: ' . $e->getMessage()
            ], 500);
        }
    }
}
