<?php

namespace InventoryService\Domain\Ports;

use InventoryService\Domain\Stock;

// Puerto: contrato para consultar/actualizar stock.
interface StockRepositoryInterface
{
    public function findBySku(string $sku): ?Stock;
    public function save(Stock $stock): void;
}
