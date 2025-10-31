<?php

namespace App\Helpers;

use App\Models\Catmat;

class TagsFornecedorHelper
{
    /**
     * Dicionário de keywords → tags de segmento
     * CATMAT tem prioridade quando existir
     */
    private static $dicionario = [
        'material_escritorio' => [
            'papel', 'caneta', 'lapis', 'lapiseira', 'borracha', 'grampo', 'grampeador',
            'clipe', 'cola', 'fita adesiva', 'tesoura', 'regua', 'estojo', 'pasta',
            'envelope', 'etiqueta', 'marcador', 'post-it', 'caderno', 'bloco',
            'toner', 'cartucho', 'resma', 'sulfite'
        ],
        'informatica' => [
            'notebook', 'computador', 'desktop', 'cpu', 'monitor', 'teclado', 'mouse',
            'impressora', 'scanner', 'roteador', 'switch', 'cabo', 'fonte', 'hd',
            'ssd', 'memoria', 'processador', 'webcam', 'headset', 'pendrive',
            'estabilizador', 'nobreak', 'servidor', 'rack'
        ],
        'limpeza' => [
            'detergente', 'sabao', 'sabonete', 'desinfetante', 'agua sanitaria',
            'alcool', 'cloro', 'amaciante', 'limpa vidro', 'lustra moveis',
            'esponja', 'pano', 'vassoura', 'rodo', 'escova', 'balde', 'lixeira',
            'saco de lixo', 'luva', 'papel higienico', 'papel toalha'
        ],
        'alimentacao' => [
            'agua mineral', 'cafe', 'acucar', 'biscoito', 'bolacha', 'cha',
            'leite', 'achocolatado', 'suco', 'refrigerante', 'copo descartavel',
            'prato descartavel', 'talher', 'guardanapo', 'toalha de mesa'
        ],
        'mobiliario' => [
            'mesa', 'cadeira', 'poltrona', 'armario', 'estante', 'arquivo',
            'gaveteiro', 'balcao', 'bancada', 'sofa', 'rack', 'prateleira',
            'divisoria', 'biombo', 'suporte'
        ],
        'eletrodomesticos' => [
            'geladeira', 'freezer', 'microondas', 'fogao', 'bebedouro',
            'purificador', 'ventilador', 'ar condicionado', 'aquecedor',
            'cafeteira', 'liquidificador', 'ferro de passar'
        ],
        'seguranca' => [
            'camera', 'alarme', 'cadeado', 'extintor', 'sinalizacao',
            'colete', 'capacete', 'oculos de protecao', 'luva de seguranca',
            'mascara', 'protetor auricular'
        ],
        'veiculos' => [
            'veiculo', 'carro', 'caminhao', 'onibus', 'van', 'moto',
            'pneu', 'oleo', 'filtro', 'bateria', 'combustivel', 'gasolina',
            'diesel', 'alcool', 'etanol'
        ],
        'construcao' => [
            'cimento', 'areia', 'brita', 'tijolo', 'telha', 'madeira',
            'ferro', 'aco', 'prego', 'parafuso', 'tinta', 'verniz',
            'massa corrida', 'gesso', 'argamassa', 'cal'
        ],
        'servicos' => [
            'manutencao', 'limpeza', 'conservacao', 'vigilancia', 'seguranca',
            'portaria', 'recepcionista', 'copeiragem', 'jardinagem',
            'dedetizacao', 'desinsetizacao'
        ]
    ];

    /**
     * Gera tags para um fornecedor baseado em CATMAT e descrição
     * CATMAT tem prioridade
     *
     * @param string|null $catmat Código CATMAT
     * @param string $descricao Descrição do item/contrato
     * @return array Tags geradas
     */
    public static function gerarTags(?string $catmat, string $descricao): array
    {
        $tags = [];

        // PRIORIDADE 1: Tag por CATMAT
        if ($catmat) {
            $tagCatmat = self::tagPorCatmat($catmat);
            if ($tagCatmat) {
                $tags[] = $tagCatmat;
            }
        }

        // PRIORIDADE 2: Tags por keywords na descrição
        $tagsPorDescricao = self::tagsPorDescricao($descricao);
        $tags = array_merge($tags, $tagsPorDescricao);

        // Remover duplicatas e retornar
        return array_unique($tags);
    }

    /**
     * Identifica tag por código CATMAT
     *
     * @param string $catmat Código CATMAT
     * @return string|null Tag identificada
     */
    public static function tagPorCatmat(string $catmat): ?string
    {
        try {
            $catmatModel = Catmat::where('codigo', $catmat)->first();

            if (!$catmatModel) {
                return null;
            }

            $titulo = strtolower($catmatModel->titulo);

            // Mapear categorias do CATMAT para nossas tags
            $mapeamento = [
                'papel' => 'material_escritorio',
                'caneta' => 'material_escritorio',
                'toner' => 'material_escritorio',
                'notebook' => 'informatica',
                'computador' => 'informatica',
                'impressora' => 'informatica',
                'detergente' => 'limpeza',
                'sabao' => 'limpeza',
                'agua mineral' => 'alimentacao',
                'cafe' => 'alimentacao',
                'mesa' => 'mobiliario',
                'cadeira' => 'mobiliario',
                'camera' => 'seguranca',
                'extintor' => 'seguranca',
                'veiculo' => 'veiculos',
                'combustivel' => 'veiculos',
                'manutencao' => 'servicos',
                'limpeza' => 'servicos',
            ];

            foreach ($mapeamento as $keyword => $tag) {
                if (strpos($titulo, $keyword) !== false) {
                    return $tag;
                }
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Identifica tags por keywords na descrição
     *
     * @param string $descricao Descrição do item/contrato
     * @return array Tags identificadas
     */
    public static function tagsPorDescricao(string $descricao): array
    {
        $descricaoLower = strtolower($descricao);
        $tagsEncontradas = [];

        foreach (self::$dicionario as $tag => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($descricaoLower, $keyword) !== false) {
                    $tagsEncontradas[] = $tag;
                    break; // Já encontrou a tag, pula para próxima categoria
                }
            }
        }

        return array_unique($tagsEncontradas);
    }

    /**
     * Retorna todas as tags disponíveis
     *
     * @return array Lista de tags
     */
    public static function todasAsTags(): array
    {
        return array_keys(self::$dicionario);
    }

    /**
     * Retorna label amigável da tag
     *
     * @param string $tag Tag interna
     * @return string Label para exibição
     */
    public static function labelTag(string $tag): string
    {
        $labels = [
            'material_escritorio' => 'Material de Escritório',
            'informatica' => 'Informática',
            'limpeza' => 'Limpeza e Higiene',
            'alimentacao' => 'Alimentação',
            'mobiliario' => 'Mobiliário',
            'eletrodomesticos' => 'Eletrodomésticos',
            'seguranca' => 'Segurança',
            'veiculos' => 'Veículos e Combustíveis',
            'construcao' => 'Construção',
            'servicos' => 'Serviços',
        ];

        return $labels[$tag] ?? ucfirst(str_replace('_', ' ', $tag));
    }

    /**
     * Retorna cor da badge para a tag
     *
     * @param string $tag Tag interna
     * @return string Código de cor hexadecimal
     */
    public static function corTag(string $tag): string
    {
        $cores = [
            'material_escritorio' => '#3b82f6', // Azul
            'informatica' => '#8b5cf6',         // Roxo
            'limpeza' => '#10b981',             // Verde
            'alimentacao' => '#f59e0b',         // Laranja
            'mobiliario' => '#6366f1',          // Indigo
            'eletrodomesticos' => '#ec4899',    // Rosa
            'seguranca' => '#ef4444',           // Vermelho
            'veiculos' => '#14b8a6',            // Teal
            'construcao' => '#78716c',          // Cinza
            'servicos' => '#06b6d4',            // Ciano
        ];

        return $cores[$tag] ?? '#6b7280';
    }
}
