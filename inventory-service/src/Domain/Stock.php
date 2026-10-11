<?php

namespace InventoryService\Domain;

// Entidad de dominio: Stock de un SKU.
class Stock
{
    public string $sku;
    public int $cantidadDisponible;

    public function __construct(string $sku, int $cantidadDisponible)
    {
        $this->sku = $sku;
        $this->cantidadDisponible = $cantidadDisponible;
    }

    public function puedeDescontar(int $cantidad): bool
    {
        return $this->cantidadDisponible >= $cantidad;
    }

    public function descontar(int $cantidad): void
    {
        if (!$this->puedeDescontar($cantidad)) {
            throw new \RuntimeException("Stock insuficiente para SKU {$this->sku}");
        }
        $this->cantidadDisponible -= $cantidad;
    }
}
