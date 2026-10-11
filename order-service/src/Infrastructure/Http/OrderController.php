<?php

namespace OrderService\Infrastructure\Http;

use OrderService\Application\CreateOrderUseCase;

// Adaptador de entrada: expone el caso de uso por HTTP.
class OrderController
{
    private CreateOrderUseCase $createOrderUseCase;

    public function __construct(CreateOrderUseCase $createOrderUseCase)
    {
        $this->createOrderUseCase = $createOrderUseCase;
    }

    public function crearPedido(array $body): array
    {
        try {
            $clienteId = $body['clienteId'] ?? null;
            $items = $body['items'] ?? [];
            $order = $this->createOrderUseCase->execute($clienteId, $items);
            return ['status' => 201, 'body' => $order->toArray()];
        } catch (\Throwable $e) {
            return ['status' => 400, 'body' => ['error' => $e->getMessage()]];
        }
    }
}
