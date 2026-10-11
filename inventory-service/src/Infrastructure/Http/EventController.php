<?php

namespace InventoryService\Infrastructure\Http;

use InventoryService\Application\ProcessOrderStockUseCase;

// Adaptador de entrada: recibe el webhook del broker (patrón Pub-Sub).
class EventController
{
    private ProcessOrderStockUseCase $useCase;

    public function __construct(ProcessOrderStockUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function recibirEvento(array $body): array
    {
        $topic = $body['topic'] ?? null;
        if ($topic !== 'pedido.creado') {
            return ['status' => 200, 'body' => ['ok' => true, 'ignorado' => true]];
        }
        try {
            $resultado = $this->useCase->execute($body['payload']);
            return ['status' => 200, 'body' => $resultado];
        } catch (\Throwable $e) {
            return ['status' => 500, 'body' => ['error' => $e->getMessage()]];
        }
    }
}
