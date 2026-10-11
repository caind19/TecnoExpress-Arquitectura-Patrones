<?php

namespace BillingService\Infrastructure\Http;

use BillingService\Application\GenerarComprobanteUseCase;

// Adaptador de entrada: recibe el webhook del broker para
// "inventario.descontado" y llama al caso de uso.
class EventController
{
    private GenerarComprobanteUseCase $useCase;

    public function __construct(GenerarComprobanteUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function recibirEvento(array $body): array
    {
        $topic = $body['topic'] ?? null;
        if ($topic !== 'inventario.descontado') {
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
