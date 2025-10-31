<?php

namespace App\Services\PDF\Extratores;

use App\Services\PDF\FormatoExtrator;
use App\Http\Controllers\CotacaoExternaController;

/**
 * Extrator para formato "Mapa de Apuração de Preços"
 * Usa a lógica existente do controller
 */
class MapaApuracaoExtrator implements FormatoExtrator
{
    public function extrair(array $linhas): array
    {
        \Log::info('MapaApuracaoExtrator: Usando lógica existente do controller');

        // Criar instância do controller para usar métodos privados
        // (temporário - idealmente moveria toda lógica para cá)
        $controller = new CotacaoExternaController();

        // Usar método reflection para acessar método privado
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('lerPDFMapaApuracao');
        $method->setAccessible(true);

        return $method->invoke($controller, $linhas);
    }
}
