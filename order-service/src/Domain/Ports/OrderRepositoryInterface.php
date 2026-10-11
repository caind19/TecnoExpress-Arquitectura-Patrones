<?php

namespace OrderService\Domain\Ports;

use OrderService\Domain\Order;

// Puerto (Arquitectura Hexagonal): contrato de persistencia de pedidos.
// El dominio depende de esto, no de una implementación concreta.
interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(string $id): ?Order;
}
