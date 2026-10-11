<?php

namespace BillingService\Domain;

// Entidad de dominio: Comprobante de venta.
class Comprobante
{
    public string $id;
    public string $orderId;
    public string $clienteId;
    public array $items;
    public float $total;
    public string $emitidoEn;

    public function __construct(string $id, string $orderId, string $clienteId, array $items, float $total, ?string $emitidoEn = null)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->clienteId = $clienteId;
        $this->items = $items;
        $this->total = $total;
        $this->emitidoEn = $emitidoEn ?? gmdate('Y-m-d\TH:i:s\Z');
    }
}
