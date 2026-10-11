<?php

namespace OrderService\Domain;

// Entidad de dominio: Pedido. No depende de nada externo.
class Order
{
    public string $id;
    public string $clienteId;
    public array $items; // [{ sku, cantidad, precioUnitario }]
    public string $estado;
    public string $creadoEn;

    public function __construct(string $id, string $clienteId, array $items, string $estado = 'CREADO', ?string $creadoEn = null)
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Un pedido debe tener al menos un ítem');
        }
        $this->id = $id;
        $this->clienteId = $clienteId;
        $this->items = $items;
        $this->estado = $estado;
        $this->creadoEn = $creadoEn ?? gmdate('Y-m-d\TH:i:s\Z');
    }

    public function total(): float
    {
        return array_reduce($this->items, fn($acc, $it) => $acc + $it['cantidad'] * $it['precioUnitario'], 0);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'clienteId' => $this->clienteId,
            'items' => $this->items,
            'estado' => $this->estado,
            'creadoEn' => $this->creadoEn,
            'total' => $this->total(),
        ];
    }
}
