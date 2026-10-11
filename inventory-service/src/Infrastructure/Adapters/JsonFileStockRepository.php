<?php

namespace InventoryService\Infrastructure\Adapters;

use InventoryService\Domain\Stock;
use InventoryService\Domain\Ports\StockRepositoryInterface;

// Adaptador de salida: stock guardado en un archivo JSON, precargado
// con un catálogo de ejemplo de TecnoExpress SAS.
class JsonFileStockRepository implements StockRepositoryInterface
{
    private string $archivo;

    public function __construct(string $archivo)
    {
        $this->archivo = $archivo;
        if (!is_dir(dirname($archivo))) {
            mkdir(dirname($archivo), 0777, true);
        }
        if (!file_exists($archivo)) {
            file_put_contents($archivo, json_encode([
                'TEC-001' => 25, // Audífonos inalámbricos
                'TEC-002' => 10, // Cargador rápido USB-C
                'TEC-003' => 5,  // Mouse inalámbrico
            ]));
        }
    }

    public function findBySku(string $sku): ?Stock
    {
        $datos = $this->leer();
        if (!isset($datos[$sku])) {
            return null;
        }
        return new Stock($sku, $datos[$sku]);
    }

    public function save(Stock $stock): void
    {
        $datos = $this->leer();
        $datos[$stock->sku] = $stock->cantidadDisponible;
        file_put_contents($this->archivo, json_encode($datos, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function leer(): array
    {
        return json_decode(file_get_contents($this->archivo), true) ?: [];
    }
}
