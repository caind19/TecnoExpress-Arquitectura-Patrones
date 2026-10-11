<?php

namespace BillingService\Infrastructure\Adapters;

use BillingService\Domain\Comprobante;
use BillingService\Domain\Ports\ComprobanteRepositoryInterface;

// Adaptador de salida: comprobantes emitidos guardados en un archivo JSON.
class JsonFileComprobanteRepository implements ComprobanteRepositoryInterface
{
    private string $archivo;

    public function __construct(string $archivo)
    {
        $this->archivo = $archivo;
        if (!is_dir(dirname($archivo))) {
            mkdir(dirname($archivo), 0777, true);
        }
        if (!file_exists($archivo)) {
            file_put_contents($archivo, json_encode([]));
        }
    }

    public function save(Comprobante $comprobante): void
    {
        $datos = $this->leer();
        $datos[$comprobante->id] = [
            'id' => $comprobante->id,
            'orderId' => $comprobante->orderId,
            'clienteId' => $comprobante->clienteId,
            'items' => $comprobante->items,
            'total' => $comprobante->total,
            'emitidoEn' => $comprobante->emitidoEn,
        ];
        file_put_contents($this->archivo, json_encode($datos, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function all(): array
    {
        return array_values($this->leer());
    }

    private function leer(): array
    {
        return json_decode(file_get_contents($this->archivo), true) ?: [];
    }
}
