<?php

namespace OrderService\Infrastructure\Adapters;

use OrderService\Domain\Order;
use OrderService\Domain\Ports\OrderRepositoryInterface;

// Adaptador de salida: guarda los pedidos en un archivo JSON.
// (El servidor embebido de PHP no mantiene memoria entre peticiones,
// así que el "almacenamiento en memoria" se simula con un archivo).
class JsonFileOrderRepository implements OrderRepositoryInterface
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

    public function save(Order $order): void
    {
        $datos = $this->leer();
        $datos[$order->id] = $order->toArray();
        $this->escribir($datos);
    }

    public function findById(string $id): ?Order
    {
        $datos = $this->leer();
        if (!isset($datos[$id])) {
            return null;
        }
        $d = $datos[$id];
        return new Order($d['id'], $d['clienteId'], $d['items'], $d['estado'], $d['creadoEn']);
    }

    private function leer(): array
    {
        return json_decode(file_get_contents($this->archivo), true) ?: [];
    }

    private function escribir(array $datos): void
    {
        file_put_contents($this->archivo, json_encode($datos, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
