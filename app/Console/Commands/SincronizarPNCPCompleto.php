<?php

namespace App\Console\Commands;

use App\Models\ContratoPNCP;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SincronizarPNCPCompleto extends Command
{
    /**
     * Nome e assinatura do comando
     *
     * @var string
     */
    protected $signature = 'pncp:sincronizar-completo
                            {--limpar : Limpar banco antes de sincronizar}
                            {--termos= : Termos específicos separados por vírgula}
                            {--paginas=3 : Número de páginas por termo (padrão: 3)}';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Sincroniza banco local PNCP com CENTENAS de produtos diferentes (abrangente)';

    /**
     * Lista COMPLETA de produtos para sincronizar
     * Categorizada por área para melhor organização
     */
    protected $produtos = [
        // ESCRITÓRIO E PAPELARIA (50 itens)
        'PAPEL A4', 'CANETA', 'LAPIS', 'BORRACHA', 'GRAMPEADOR', 'CLIPS', 'PASTA',
        'CADERNO', 'AGENDA', 'ENVELOPE', 'ETIQUETA', 'TONER', 'CARTUCHO', 'IMPRESSORA',
        'SCANNER', 'ARQUIVO', 'PRANCHETA', 'TESOURA', 'COLA', 'FITA ADESIVA',
        'PERFURADOR', 'CORRETIVO', 'MARCA TEXTO', 'POST IT', 'CALCULADORA',
        'DATASHOW', 'QUADRO BRANCO', 'APAGADOR', 'PINCEL', 'GIZ', 'LOUSA',
        'MURAL', 'FLIPCHART', 'ESTILETE', 'REGUA', 'COMPASSO', 'TRANSFERIDOR',
        'APONTADOR', 'ESTOJO', 'PAPEL VEGETAL', 'PAPEL ALMAÇO', 'BLOCO NOTAS',
        'PAPEL TIMBRADO', 'FORMULARIO', 'LIVRO ATA', 'LIVRO PROTOCOLO',
        'CARIMBO', 'ALMOFADA CARIMBO', 'NUMERADOR', 'ORGANIZADOR',

        // INFORMÁTICA E TECNOLOGIA (60 itens)
        'COMPUTADOR', 'NOTEBOOK', 'LAPTOP', 'DESKTOP', 'MONITOR', 'TECLADO',
        'MOUSE', 'MOUSEPAD', 'WEBCAM', 'HEADSET', 'CAIXA SOM', 'MICROFONE',
        'HD EXTERNO', 'SSD', 'PEN DRIVE', 'CARTAO MEMORIA', 'ESTABILIZADOR',
        'NOBREAK', 'ROTEADOR', 'SWITCH', 'CABO REDE', 'CABO HDMI', 'CABO USB',
        'HUB USB', 'ADAPTADOR', 'FONTE', 'MEMORIA RAM', 'PROCESSADOR',
        'PLACA VIDEO', 'PLACA MAE', 'COOLER', 'GABINETE', 'SERVIDOR',
        'RACK', 'TABLET', 'SMARTPHONE', 'CELULAR', 'CARREGADOR', 'FONE',
        'POWERBANK', 'SUPORTE NOTEBOOK', 'MESA DIGITALIZADORA', 'LEITOR CODIGO',
        'ANTIVIRUS', 'BACKUP', 'FIREWALL', 'ACCESS POINT', 'REPETIDOR SINAL',
        'CAMERA IP', 'DVR', 'NVR', 'PROJETOR', 'TELA PROJECAO', 'CONTROLE',
        'MODEM', 'CONVERSOR', 'EXTENSOR', 'PATCH PANEL', 'DIO',

        // MÓVEIS E EQUIPAMENTOS (40 itens)
        'MESA', 'CADEIRA', 'ARMARIO', 'ESTANTE', 'ARQUIVO AÇO', 'GAVETEIRO',
        'BALCAO', 'BANCADA', 'ESCRIVANINHA', 'POLTRONA', 'SOFA', 'LONGARINA',
        'MESA REUNIAO', 'CADEIRA GIRATORIA', 'CADEIRA FIXA', 'CADEIRA ERGONOMICA',
        'MESA CENTRO', 'RACK', 'ESTANTE AÇO', 'PRATELEIRA', 'VITRINE',
        'EXPOSITOR', 'CABIDEIRO', 'PORTA REVISTA', 'LIXEIRA', 'CINZEIRO',
        'BEBEDOURO', 'PURIFICADOR', 'CAFETEIRA', 'MICRO ONDAS', 'GELADEIRA',
        'FREEZER', 'FOGAO', 'MESA REFEITORIO', 'BANCO', 'ARMARIO VESTIARIO',
        'ARMARIO COZINHA', 'PIA', 'BANCADA COZINHA', 'MESA COMPUTADOR',

        // LIMPEZA E HIGIENE (45 itens)
        'DETERGENTE', 'DESINFETANTE', 'AGUA SANITARIA', 'SABAO', 'SABONETE',
        'SHAMPOO', 'ALCOOL GEL', 'PAPEL HIGIENICO', 'PAPEL TOALHA', 'TOALHA',
        'PANO LIMPEZA', 'ESPONJA', 'VASSOURA', 'RODO', 'MOP', 'BALDE',
        'CARRINHO LIMPEZA', 'ESCOVA', 'LUVA', 'SACO LIXO', 'LIXEIRA',
        'DISPENSER', 'SABONETE LIQUIDO', 'ALCOOL', 'LIMPA VIDRO', 'CERA',
        'LUSTRADOR', 'DESINCRUSTANTE', 'DESENGORDURANTE', 'AMACIANTE',
        'ALVEJANTE', 'REMOVEDOR', 'INSETICIDA', 'AROMATIZADOR', 'ODORIZADOR',
        'NEUTRALIZADOR', 'BACTERICIDA', 'FUNGICIDA', 'CLORO', 'HIPOCLORITO',
        'PANO MICROFIBRA', 'FLANELA', 'ESFREGAO', 'PA LIXO', 'BACIA',

        // VEÍCULOS E TRANSPORTE (30 itens)
        'CARRO', 'CAMINHONETE', 'VAN', 'ONIBUS', 'MICRO ONIBUS', 'CAMINHAO',
        'MOTOCICLETA', 'BICICLETA', 'PNEU', 'BATERIA', 'OLEO MOTOR', 'FILTRO',
        'CORREIA', 'VELA', 'PASTILHA FREIO', 'DISCO FREIO', 'AMORTECEDOR',
        'PARABRISA', 'RETROVISOR', 'FAROL', 'LANTERNA', 'PARA CHOQUE',
        'CAPOTA', 'CAPO', 'PORTA', 'BANCO', 'VOLANTE', 'PAINEL', 'SOM',
        'EXTINTOR',

        // CONSTRUÇÃO E MANUTENÇÃO (50 itens)
        'CIMENTO', 'AREIA', 'BRITA', 'TIJOLO', 'BLOCO', 'TELHA', 'TINTA',
        'VERNIZ', 'MASSA CORRIDA', 'GESSO', 'CAL', 'ARGAMASSA', 'REJUNTE',
        'PREGO', 'PARAFUSO', 'BUCHA', 'BROCA', 'DISCO CORTE', 'LIXA',
        'PINCEL', 'ROLO', 'ESPATULA', 'DESEMPENADEIRA', 'COLHER PEDREIRO',
        'ENXADA', 'PA', 'CARRINHO MÃO', 'BETONEIRA', 'FURADEIRA', 'PARAFUSADEIRA',
        'SERRA', 'MARTELETE', 'ESMERILHADEIRA', 'NIVEL', 'PRUMO', 'TRENA',
        'ESQUADRO', 'SERROTE', 'MARTELO', 'ALICATE', 'CHAVE FENDA', 'CHAVE PHILIPS',
        'CHAVE INGLESA', 'CHAVE ALLEN', 'TUBO PVC', 'CONEXAO', 'TORNEIRA',
        'REGISTRO', 'VALVULA', 'SIFAO',

        // SAÚDE E MEDICAMENTOS (40 itens)
        'MASCARA', 'LUVA PROCEDIMENTO', 'ALCOOL 70', 'TERMOMETRO', 'ESTETOSCOPIO',
        'GAZE', 'ATADURA', 'ESPARADRAPO', 'SERINGA', 'AGULHA', 'CATETER',
        'SONDA', 'OXIMETRO', 'ESFIGMOMANOMETRO', 'OTOSCOPIO', 'OFTALMOSCOPIO',
        'MACA', 'CADEIRA RODAS', 'MULETA', 'ANDADOR', 'NEBULIZADOR',
        'INALADOR', 'GLICOSIMETRO', 'LANCETA', 'BANDAGEM', 'MICROPORE',
        'ALGODAO', 'DIPIRONA', 'PARACETAMOL', 'IBUPROFENO', 'AMOXICILINA',
        'AZITROMICINA', 'OMEPRAZOL', 'LOSARTANA', 'METFORMINA', 'INSULINA',
        'VACINA', 'SORO', 'SUPLEMENTO', 'VITAMINA',

        // ALIMENTOS E BEBIDAS (35 itens)
        'CAFE', 'LEITE', 'AÇUCAR', 'SAL', 'ARROZ', 'FEIJAO', 'MACARRAO',
        'OLEO', 'FARINHA', 'FUBÁ', 'BISCOITO', 'BOLACHA', 'ACHOCOLATADO',
        'SUCO', 'REFRIGERANTE', 'AGUA MINERAL', 'CHA', 'ADOÇANTE', 'TEMPERO',
        'MOLHO', 'KETCHUP', 'MAIONESE', 'MOSTARDA', 'VINAGRE', 'AZEITE',
        'MARGARINA', 'MANTEIGA', 'QUEIJO', 'PRESUNTO', 'SALSICHA', 'LINGUIÇA',
        'CARNE', 'FRANGO', 'PEIXE', 'OVO',

        // UNIFORMES E VESTUÁRIO (30 itens)
        'CAMISETA', 'CAMISA', 'CALÇA', 'BERMUDA', 'SHORT', 'SAIA', 'VESTIDO',
        'JALECO', 'AVENTAL', 'COLETE', 'JAQUETA', 'BLAZER', 'TERNO',
        'GRAVATA', 'CINTO', 'MEIA', 'SAPATO', 'TENIS', 'BOTA', 'CHINELO',
        'BONE', 'CHAPEU', 'LUVA TRABALHO', 'OCULOS SEGURANÇA', 'CAPACETE',
        'PROTETOR AURICULAR', 'MASCARA SEGURANÇA', 'TOUCA', 'MANGOTE', 'PERNEIRA',

        // EDUCAÇÃO E DIDÁTICOS (25 items)
        'LIVRO', 'APOSTILA', 'DICIONARIO', 'ATLAS', 'GLOBO', 'MAPA',
        'KIT ESCOLAR', 'MOCHILA', 'ESTOJO ESCOLAR', 'COMPASSO ESCOLAR',
        'ESQUADRO ESCOLAR', 'TRANSFERIDOR ESCOLAR', 'TINTA GUACHE',
        'TINTA ACRILICA', 'PINCEL ARTISTICO', 'MASSA MODELAR', 'EVA',
        'PAPEL CARTAO', 'PAPEL CREPOM', 'PAPEL SULFITE', 'CARTOLINA',
        'PAPEL LAMINADO', 'GLITTER', 'LANTEJOULA', 'FITA CETIM',

        // ESPORTE E LAZER (20 itens)
        'BOLA', 'REDE', 'TRAVE', 'CONE', 'COLCHONETE', 'TATAME', 'HALTER',
        'BARRA FIXA', 'CORDA PULAR', 'BAMBOLÊ', 'BICICLETA ERGOMETRICA',
        'ESTEIRA', 'MESA PING PONG', 'RAQUETE', 'VOLANTE', 'PETECA',
        'JOGO TABULEIRO', 'DOMINÓ', 'BARALHO', 'QUEBRA CABEÇA',
    ];

    /**
     * Executar comando
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  SINCRONIZAÇÃO COMPLETA PNCP - BASE ABRANGENTE');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Limpar banco se solicitado
        if ($this->option('limpar')) {
            $this->warn('⚠️  Limpando banco de dados...');
            DB::table('contratos_pncp')->truncate();
            $this->info('✅ Banco limpo!');
            $this->newLine();
        }

        // Usar termos específicos ou todos
        $termos = $this->option('termos')
            ? explode(',', $this->option('termos'))
            : $this->produtos;

        $paginasPorTermo = (int) $this->option('paginas');

        $this->info('📊 CONFIGURAÇÃO:');
        $this->info("   • Termos: " . count($termos));
        $this->info("   • Páginas por termo: {$paginasPorTermo}");
        $this->info("   • Total estimado de requisições: " . (count($termos) * $paginasPorTermo));
        $this->newLine();

        if (!$this->confirm('Deseja continuar?', true)) {
            $this->warn('❌ Cancelado pelo usuário');
            return 0;
        }

        $this->newLine();
        $this->info('🚀 Iniciando sincronização...');
        $this->newLine();

        $totalContratos = 0;
        $totalTermos = count($termos);
        $termoAtual = 0;

        foreach ($termos as $termo) {
            $termoAtual++;
            $termo = trim($termo);

            $this->info("[$termoAtual/$totalTermos] 🔍 Buscando: {$termo}");

            try {
                $contratos = $this->buscarContratos($termo, $paginasPorTermo);

                if (count($contratos) > 0) {
                    $salvos = $this->salvarContratos($contratos);
                    $totalContratos += $salvos;
                    $this->info("   ✅ {$salvos} contratos salvos");
                } else {
                    $this->warn("   ⚠️  Nenhum contrato encontrado");
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Erro: " . $e->getMessage());
                Log::error("Sincronização PNCP - Erro no termo '{$termo}': " . $e->getMessage());
            }

            // Delay entre termos (evitar sobrecarga da API)
            if ($termoAtual < $totalTermos) {
                sleep(1); // 1 segundo entre termos
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  🎉 SINCRONIZAÇÃO CONCLUÍDA!');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("   📦 Total de contratos: {$totalContratos}");
        $this->info("   🔍 Termos processados: {$totalTermos}");
        $this->info("   ⏱️  Busca agora será < 1 segundo!");
        $this->newLine();

        return 0;
    }

    /**
     * Buscar contratos na API PNCP
     */
    protected function buscarContratos($termo, $paginas = 3)
    {
        $contratos = [];
        $hoje = now();
        $dataFinal = $hoje->format('Ymd');
        $dataInicial = $hoje->copy()->subYear()->format('Ymd'); // 1 ano

        for ($pagina = 1; $pagina <= $paginas; $pagina++) {
            try {
                $url = "https://pncp.gov.br/api/consulta/v1/contratos?" . http_build_query([
                    'dataInicial' => $dataInicial,
                    'dataFinal' => $dataFinal,
                    'q' => $termo,
                    'pagina' => $pagina
                ]);

                $response = Http::timeout(10)->get($url);

                if (!$response->successful()) {
                    break;
                }

                $data = $response->json();

                if (!isset($data['data']) || empty($data['data'])) {
                    break; // Sem mais dados
                }

                foreach ($data['data'] as $contrato) {
                    $objetoContrato = $contrato['objetoContrato'] ?? '';

                    if (empty($objetoContrato)) {
                        continue;
                    }

                    // Filtrar manualmente
                    $termoLower = mb_strtolower($termo, 'UTF-8');
                    $objetoLower = mb_strtolower($objetoContrato, 'UTF-8');
                    if (!str_contains($objetoLower, $termoLower)) {
                        continue;
                    }

                    $valorGlobal = $contrato['valorGlobal'] ?? 0;
                    if ($valorGlobal <= 0) {
                        continue;
                    }

                    // Calcular valor unitário estimado
                    $numeroParcelas = $contrato['numeroParcelas'] ?? null;
                    $valorUnitario = $valorGlobal;
                    $confiabilidade = 'baixa';

                    if ($numeroParcelas && $numeroParcelas > 1) {
                        $valorUnitario = $valorGlobal / $numeroParcelas;
                        $confiabilidade = 'media';
                    }

                    $contratos[] = [
                        'numero_controle_pncp' => $contrato['numeroControlePNCP'] ?? null,
                        'tipo' => 'contrato',
                        'objeto_contrato' => $objetoContrato,
                        'valor_global' => $valorGlobal,
                        'numero_parcelas' => $numeroParcelas,
                        'valor_unitario_estimado' => $valorUnitario,
                        'unidade_medida' => $numeroParcelas > 1 ? 'PARCELA' : 'CONTRATO',
                        'orgao_cnpj' => $contrato['orgaoEntidade']['cnpj'] ?? null,
                        'orgao_razao_social' => $contrato['orgaoEntidade']['razaoSocial'] ?? 'N/A',
                        'orgao_uf' => $contrato['orgaoEntidade']['municipio']['uf']['sigla'] ?? null,
                        'orgao_municipio' => $contrato['orgaoEntidade']['municipio']['nome'] ?? null,
                        'data_publicacao_pncp' => $contrato['dataPublicacaoPncp'] ?? null,
                        'data_vigencia_inicio' => $contrato['dataVigenciaInicio'] ?? null,
                        'data_vigencia_fim' => $contrato['dataVigenciaFim'] ?? null,
                        'confiabilidade' => $confiabilidade,
                        'valor_estimado' => $numeroParcelas > 1,
                        'sincronizado_em' => now(),
                    ];
                }

                // Delay entre páginas
                if ($pagina < $paginas) {
                    usleep(200000); // 200ms
                }

            } catch (\Exception $e) {
                Log::warning("Erro ao buscar página {$pagina} do termo '{$termo}': " . $e->getMessage());
                break;
            }
        }

        return $contratos;
    }

    /**
     * Salvar contratos no banco (evitar duplicatas)
     */
    protected function salvarContratos($contratos)
    {
        $salvos = 0;

        foreach ($contratos as $contrato) {
            try {
                // Verificar se já existe (por número de controle ou objeto similar)
                $existe = ContratoPNCP::where('numero_controle_pncp', $contrato['numero_controle_pncp'])
                    ->orWhere(function($query) use ($contrato) {
                        $query->where('objeto_contrato', $contrato['objeto_contrato'])
                              ->where('valor_global', $contrato['valor_global'])
                              ->where('orgao_cnpj', $contrato['orgao_cnpj']);
                    })
                    ->exists();

                if (!$existe) {
                    ContratoPNCP::create($contrato);
                    $salvos++;
                }

            } catch (\Exception $e) {
                Log::warning("Erro ao salvar contrato: " . $e->getMessage());
            }
        }

        return $salvos;
    }
}
