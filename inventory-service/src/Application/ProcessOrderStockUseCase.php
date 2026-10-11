<?php

namespace InventoryService\Application;

use InventoryService\Domain\Ports\StockRepositoryInterface;
use InventoryService\Domain\Ports\EventPublisherInterface;

// Caso de uso: descontar inventario cuando llega "pedido.creado"
// (RF-07 validar inventario, RF-08 descontar inventario).
// Dependencias inyectadas por constructor.
class ProcessOrderStockUseCase
{
    private StockRepositoryInterface $stockRepository;
    private EventPublisherInterface $eventPublisher;

    public function __construct(StockRepositoryInterface $stockRepository, EventPublisherInterface $eventPublisher)
    {
        $this->stockRepository = $stockRepository;
        $this->eventPublisher = $eventPublisher;
    }

    public function execute(array $order): array
    {
        $resultados = [];

        foreach ($order['items'] as $item) {
            $stock = $this->stockRepository->findBySku($item['sku']);
            if (!$stock) {
                $resultados[] = ['sku' => $item['sku'], 'ok' => false, 'motivo' => 'SKU no encontrado'];
                continue;
            }
            if (!$stock->puedeDescontar($item['cantidad'])) {
                $resultados[] = ['sku' => $item['sku'], 'ok' => false, 'motivo' => 'stock insuficiente'];
                continue;
            }
            $stock->descontar($item['cantidad']);
            $this->stockRepository->save($stock);
            $resultados[] = ['sku' => $item['sku'], 'ok' => true, 'restante' => $stock->cantidadDisponible];
        }

        $exitoso = array_reduce($resultados, fn($acc, $r) => $acc && $r['ok'], true);

        // billing-service está suscrito a este topic
        $this->eventPublisher->publish('inventario.descontado', [
            'orderId' => $order['id'],
            'clienteId' => $order['clienteId'],
            'items' => $order['items'],
            'total' => $order['total'],
            'exitoso' => $exitoso,
            'detalle' => $resultados,
        ]);

        return ['exitoso' => $exitoso, 'resultados' => $resultados];
    }
}
