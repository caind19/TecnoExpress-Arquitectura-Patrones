<?php

namespace OrderService\Application;

use OrderService\Domain\Order;
use OrderService\Domain\Ports\OrderRepositoryInterface;
use OrderService\Domain\Ports\EventPublisherInterface;

// Caso de uso: Crear Pedido. Recibe sus dependencias por constructor
// (Inyección de Dependencias) en vez de crearlas él mismo.
class CreateOrderUseCase
{
    private OrderRepositoryInterface $orderRepository; // puerto
    private EventPublisherInterface $eventPublisher;   // puerto

    public function __construct(OrderRepositoryInterface $orderRepository, EventPublisherInterface $eventPublisher)
    {
        $this->orderRepository = $orderRepository;
        $this->eventPublisher = $eventPublisher;
    }

    public function execute(string $clienteId, array $items): Order
    {
        $order = new Order(bin2hex(random_bytes(16)), $clienteId, $items);
        $this->orderRepository->save($order);

        // dispara el flujo Pub-Sub hacia inventory-service
        $this->eventPublisher->publish('pedido.creado', $order->toArray());

        return $order;
    }
}
