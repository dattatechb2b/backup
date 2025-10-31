<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catmat;
use Carbon\Carbon;

class CatmatSeeder extends Seeder
{
    /**
     * Seed inicial com os 30 códigos CATMAT/CATSER mais comuns
     * Baseado em estatísticas do PNCP e ComprasGov
     */
    public function run(): void
    {
        $agora = Carbon::now();

        $catmats = [
            // Informática
            ['codigo' => '430242', 'titulo' => 'COMPUTADOR PESSOAL (DESKTOP)', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '430227', 'titulo' => 'NOTEBOOK (COMPUTADOR PORTATIL)', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '430234', 'titulo' => 'IMPRESSORA', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '400170', 'titulo' => 'CARTUCHO DE TINTA PARA IMPRESSORA', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '400169', 'titulo' => 'TONER PARA IMPRESSORA', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],

            // Material de Escritório
            ['codigo' => '366467', 'titulo' => 'PAPEL SULFITE A4', 'tipo' => 'CATMAT', 'unidade_padrao' => 'RESMA'],
            ['codigo' => '366468', 'titulo' => 'PAPEL SULFITE OFICIO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'RESMA'],
            ['codigo' => '141135', 'titulo' => 'CANETA ESFEROGRAFICA', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '141139', 'titulo' => 'LAPIS PRETO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '308391', 'titulo' => 'GRAMPEADOR', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '308392', 'titulo' => 'GRAMPO PARA GRAMPEADOR', 'tipo' => 'CATMAT', 'unidade_padrao' => 'CX'],

            // Limpeza e Higiene
            ['codigo' => '141291', 'titulo' => 'PAPEL HIGIENICO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '141293', 'titulo' => 'PAPEL TOALHA', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '141286', 'titulo' => 'SABONETE', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '141285', 'titulo' => 'DETERGENTE', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '141287', 'titulo' => 'AGUA SANITARIA', 'tipo' => 'CATMAT', 'unidade_padrao' => 'L'],
            ['codigo' => '141283', 'titulo' => 'DESINFETANTE', 'tipo' => 'CATMAT', 'unidade_padrao' => 'L'],

            // Mobiliário
            ['codigo' => '392239', 'titulo' => 'MESA PARA ESCRITORIO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '392236', 'titulo' => 'CADEIRA GIRATORIA PARA ESCRITORIO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '392235', 'titulo' => 'ARMARIO DE ACO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],
            ['codigo' => '392240', 'titulo' => 'ARQUIVO DE ACO', 'tipo' => 'CATMAT', 'unidade_padrao' => 'UN'],

            // Veículos e Combustível
            ['codigo' => '114654', 'titulo' => 'GASOLINA COMUM', 'tipo' => 'CATMAT', 'unidade_padrao' => 'L'],
            ['codigo' => '114655', 'titulo' => 'OLEO DIESEL', 'tipo' => 'CATMAT', 'unidade_padrao' => 'L'],
            ['codigo' => '114656', 'titulo' => 'ALCOOL ETILICO HIDRATADO COMBUSTIVEL', 'tipo' => 'CATMAT', 'unidade_padrao' => 'L'],

            // Serviços (CATSER)
            ['codigo' => '24953', 'titulo' => 'MANUTENCAO E REPARACAO DE EQUIPAMENTOS DE INFORMATICA', 'tipo' => 'CATSER', 'unidade_padrao' => 'SERVICO'],
            ['codigo' => '11959', 'titulo' => 'SERVICO DE LIMPEZA E CONSERVACAO', 'tipo' => 'CATSER', 'unidade_padrao' => 'MES'],
            ['codigo' => '11960', 'titulo' => 'SERVICO DE VIGILANCIA E SEGURANCA', 'tipo' => 'CATSER', 'unidade_padrao' => 'MES'],
            ['codigo' => '24954', 'titulo' => 'MANUTENCAO E REPARACAO DE VEICULOS AUTOMOTORES', 'tipo' => 'CATSER', 'unidade_padrao' => 'SERVICO'],

            // Água e Energia
            ['codigo' => '387646', 'titulo' => 'AGUA MINERAL', 'tipo' => 'CATMAT', 'unidade_padrao' => 'GARRAFA'],
            ['codigo' => '387647', 'titulo' => 'COPO DESCARTAVEL', 'tipo' => 'CATMAT', 'unidade_padrao' => 'PCT'],
        ];

        foreach ($catmats as $item) {
            Catmat::updateOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'titulo' => $item['titulo'],
                    'tipo' => $item['tipo'],
                    'unidade_padrao' => $item['unidade_padrao'],
                    'fonte' => 'CSV_OFICIAL',
                    'primeira_ocorrencia_em' => $agora,
                    'ultima_ocorrencia_em' => $agora,
                    'contador_ocorrencias' => 1,
                    'ativo' => true,
                ]
            );
        }

        $this->command->info('✅ 30 códigos CATMAT/CATSER inseridos com sucesso!');
    }
}
