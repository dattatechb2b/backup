<?php

namespace App\Http\Controllers;

use App\Models\Orgao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ConfiguracaoController extends Controller
{
    /**
     * Exibir página de configurações do órgão
     */
    public function index()
    {
        // Buscar ou criar configuração do órgão atual
        // Como cada tenant tem banco próprio, sempre há apenas 1 órgão
        $orgao = Orgao::first();

        if (!$orgao) {
            // Pegar tenant_id do request (vem do middleware ProxyAuth)
            $tenantId = request()->attributes->get('tenant')['id'] ?? request()->header('x-tenant-id');

            // Se não existe, criar um registro vazio
            $orgao = Orgao::create([
                'tenant_id' => $tenantId,
                'razao_social' => 'Novo Órgão',
                'nome_fantasia' => 'Novo Órgão',
            ]);
        }

        return view('configuracoes.index', compact('orgao'));
    }

    /**
     * Atualizar configurações do órgão
     */
    public function update(Request $request)
    {
        // Log ANTES do try-catch para garantir que está sendo chamado
        \Log::info('[ConfiguracaoController] MÉTODO CHAMADO!!!');
        \Log::info('[ConfiguracaoController] Request method: ' . $request->method());
        \Log::info('[ConfiguracaoController] Content-Type: ' . $request->header('Content-Type'));

        try {
            \Log::info('[ConfiguracaoController] Iniciando update', [
                'has_data' => $request->all() ? true : false
            ]);

            $validated = $request->validate([
                'razao_social' => 'nullable|string|max:255',
                'nome_fantasia' => 'nullable|string|max:255',
                'cnpj' => 'nullable|string|max:20',
                'endereco' => 'nullable|string|max:255',
                'numero' => 'nullable|string|max:20',
                'complemento' => 'nullable|string|max:100',
                'bairro' => 'nullable|string|max:100',
                'cep' => 'nullable|string|max:10',
                'cidade' => 'nullable|string|max:100',
                'uf' => 'nullable|string|max:2',
                'telefone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:150',
                'responsavel_nome' => 'nullable|string|max:255',
                'responsavel_matricula_siape' => 'nullable|string|max:100',
                'responsavel_cargo' => 'nullable|string|max:255',
                'responsavel_portaria' => 'nullable|string|max:100',
            ]);

            \Log::info('[ConfiguracaoController] Dados validados', [
                'validated_count' => count($validated)
            ]);

            $orgao = Orgao::first();
            \Log::info('[ConfiguracaoController] Orgao encontrado?', [
                'found' => $orgao ? true : false
            ]);

            if (!$orgao) {
                // Pegar tenant_id do request (vem do middleware ProxyAuth)
                $tenantId = $request->attributes->get('tenant')['id'] ?? $request->header('x-tenant-id');
                \Log::info('[ConfiguracaoController] Criando novo orgao', [
                    'tenant_id' => $tenantId
                ]);

                $validated['tenant_id'] = $tenantId;
                $orgao = Orgao::create($validated);
            } else {
                \Log::info('[ConfiguracaoController] Atualizando orgao existente', [
                    'orgao_id' => $orgao->id
                ]);
                $orgao->update($validated);
            }

            \Log::info('[ConfiguracaoController] Update concluído com sucesso');

            return response()->json([
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso!',
                'orgao' => $orgao
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[ConfiguracaoController] Erro de validação', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('[ConfiguracaoController] Erro ao atualizar configurações', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload e processamento do brasão usando GD nativo do PHP
     */
    public function uploadBrasao(Request $request)
    {
        $request->validate([
            'brasao' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240', // max 10MB
        ]);

        try {
            $orgao = Orgao::first();

            if (!$orgao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Órgão não encontrado. Por favor, salve as configurações primeiro.'
                ], 404);
            }

            // Deletar brasão antigo se existir
            if ($orgao->brasao_path) {
                Storage::disk('public')->delete($orgao->brasao_path);
            }

            // Processar imagem
            $image = $request->file('brasao');
            $extension = $image->getClientOriginalExtension();
            $filename = 'brasao_' . time() . '.' . $extension;

            // Criar imagem a partir do arquivo enviado
            $sourceImage = null;
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    $sourceImage = imagecreatefromjpeg($image->getRealPath());
                    break;
                case 'png':
                    $sourceImage = imagecreatefrompng($image->getRealPath());
                    break;
                case 'gif':
                    $sourceImage = imagecreatefromgif($image->getRealPath());
                    break;
                case 'svg':
                    // SVG não precisa redimensionar, salvar direto
                    $path = 'brasoes/' . $filename;
                    Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));

                    // Definir permissões corretas no arquivo SVG
                    $fullPathSvg = storage_path('app/public/' . $path);
                    chmod($fullPathSvg, 0664);
                    chown($fullPathSvg, 'www-data');
                    chgrp($fullPathSvg, 'www-data');

                    $orgao->brasao_path = $path;
                    $orgao->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'Brasão SVG enviado com sucesso!',
                        'brasao_url' => 'storage/' . $path  // URL relativa para funcionar com proxy
                    ]);
                default:
                    throw new \Exception('Formato de imagem não suportado');
            }

            if (!$sourceImage) {
                throw new \Exception('Não foi possível processar a imagem');
            }

            // Obter dimensões originais
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calcular novas dimensões mantendo proporção (max 800x800)
            $maxSize = 800;
            if ($originalWidth > $maxSize || $originalHeight > $maxSize) {
                $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
                $newWidth = (int)($originalWidth * $ratio);
                $newHeight = (int)($originalHeight * $ratio);
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Criar nova imagem redimensionada
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preservar transparência para PNG
            if ($extension === 'png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Redimensionar
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            // Salvar imagem processada
            $path = 'brasoes/' . $filename;
            $fullPath = storage_path('app/public/' . $path);

            // Criar diretório se não existir
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Salvar conforme formato
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($newImage, $fullPath, 90);
                    break;
                case 'png':
                    imagepng($newImage, $fullPath, 9);
                    break;
                case 'gif':
                    imagegif($newImage, $fullPath);
                    break;
            }

            // Liberar memória
            imagedestroy($sourceImage);
            imagedestroy($newImage);

            // Definir permissões corretas no arquivo
            chmod($fullPath, 0664);
            chown($fullPath, 'www-data');
            chgrp($fullPath, 'www-data');

            // Atualizar caminho no banco
            $orgao->brasao_path = $path;
            $orgao->save();

            return response()->json([
                'success' => true,
                'message' => 'Brasão enviado e processado com sucesso!',
                'brasao_url' => 'storage/' . $path  // URL relativa para funcionar com proxy
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao fazer upload do brasão: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload do brasão: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar dados do CNPJ na Receita Federal
     */
    public function buscarCNPJ(Request $request)
    {
        $request->validate([
            'cnpj' => 'required|string'
        ]);

        $cnpj = preg_replace('/[^0-9]/', '', $request->cnpj);

        if (strlen($cnpj) !== 14) {
            return response()->json([
                'success' => false,
                'message' => 'CNPJ inválido. Deve conter 14 dígitos.'
            ], 400);
        }

        try {
            // Buscar na ReceitaWS
            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpj}");

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar CNPJ na Receita Federal.'
                ], 500);
            }

            $data = $response->json();

            if ($data['status'] === 'ERROR') {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'CNPJ não encontrado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'razao_social' => $data['nome'] ?? '',
                    'nome_fantasia' => $data['fantasia'] ?? '',
                    'cnpj' => $data['cnpj'] ?? '',
                    'endereco' => $data['logradouro'] ?? '',
                    'numero' => $data['numero'] ?? '',
                    'complemento' => $data['complemento'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'cep' => $data['cep'] ?? '',
                    'cidade' => $data['municipio'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'telefone' => $data['telefone'] ?? '',
                    'email' => $data['email'] ?? '',
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar CNPJ: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar CNPJ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar brasão
     */
    public function deletarBrasao()
    {
        try {
            $orgao = Orgao::first();

            if (!$orgao || !$orgao->brasao_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum brasão encontrado para deletar.'
                ], 404);
            }

            // Deletar arquivo do storage
            Storage::disk('public')->delete($orgao->brasao_path);

            // Limpar campo no banco
            $orgao->brasao_path = null;
            $orgao->save();

            return response()->json([
                'success' => true,
                'message' => 'Brasão deletado com sucesso!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao deletar brasão: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar brasão: ' . $e->getMessage()
            ], 500);
        }
    }
}
