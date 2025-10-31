<?php

namespace App\Http\Controllers;

use App\Models\Orgao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrgaoController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validação
            $validated = $request->validate([
                'razao_social' => 'required|string|max:255',
                'nome_fantasia' => 'nullable|string|max:255',
                'cnpj' => 'nullable|string|max:20',
                'endereco' => 'nullable|string|max:255',
                'cep' => 'nullable|string|max:10',
                'cidade' => 'nullable|string|max:100',
                'uf' => 'nullable|string|size:2',
                'brasao' => 'nullable|file|mimes:png|max:200', // max 200KB
            ]);

            DB::beginTransaction();

            // Gerar tenant_id único
            $tenantId = Str::slug($validated['razao_social']) . '-' . time();

            // Upload do brasão (se fornecido)
            $brasaoPath = null;
            if ($request->hasFile('brasao')) {
                $brasao = $request->file('brasao');
                $brasaoNome = $tenantId . '-' . time() . '.png';
                $brasaoPath = $brasao->storeAs('brasoes', $brasaoNome, 'public');
            }

            // Criar órgão
            $orgao = Orgao::create([
                'tenant_id' => $tenantId,
                'razao_social' => $validated['razao_social'],
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'cnpj' => $validated['cnpj'] ?? null,
                'endereco' => $validated['endereco'] ?? null,
                'cep' => $validated['cep'] ?? null,
                'cidade' => $validated['cidade'] ?? null,
                'uf' => $validated['uf'] ?? null,
                'brasao_path' => $brasaoPath,
            ]);

            DB::commit();

            Log::info('[ORGAO] Órgão criado com sucesso', [
                'orgao_id' => $orgao->id,
                'razao_social' => $orgao->razao_social,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Órgão salvo com sucesso!',
                'orgao' => [
                    'id' => $orgao->id,
                    'razao_social' => $orgao->razao_social,
                    'nome_fantasia' => $orgao->nome_fantasia,
                    'cnpj' => $orgao->cnpj,
                    'cidade' => $orgao->cidade,
                    'uf' => $orgao->uf,
                    'brasao_url' => $brasaoPath ? Storage::url($brasaoPath) : null,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            Log::error('[ORGAO] Erro de validação', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[ORGAO] Erro ao criar órgão', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar órgão: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $orgaos = Orgao::orderBy('razao_social', 'asc')->get();

            return response()->json([
                'success' => true,
                'orgaos' => $orgaos->map(function ($orgao) {
                    return [
                        'id' => $orgao->id,
                        'razao_social' => $orgao->razao_social,
                        'nome_fantasia' => $orgao->nome_fantasia,
                        'cnpj' => $orgao->cnpj,
                        'cidade' => $orgao->cidade,
                        'uf' => $orgao->uf,
                        'brasao_url' => $orgao->brasao_path ? Storage::url($orgao->brasao_path) : null,
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('[ORGAO] Erro ao listar órgãos', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar órgãos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $orgao = Orgao::findOrFail($id);

            return response()->json([
                'success' => true,
                'orgao' => [
                    'id' => $orgao->id,
                    'razao_social' => $orgao->razao_social,
                    'nome_fantasia' => $orgao->nome_fantasia,
                    'cnpj' => $orgao->cnpj,
                    'endereco' => $orgao->endereco,
                    'cep' => $orgao->cep,
                    'cidade' => $orgao->cidade,
                    'uf' => $orgao->uf,
                    'brasao_url' => $orgao->brasao_path ? Storage::url($orgao->brasao_path) : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[ORGAO] Erro ao buscar órgão', [
                'orgao_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Órgão não encontrado.',
            ], 404);
        }
    }
}
