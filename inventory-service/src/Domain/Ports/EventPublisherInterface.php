<?php

namespace InventoryService\Domain\Ports;

// Puerto: contrato para publicar eventos (patrón Pub-Sub).
interface EventPublisherInterface
{
    public function publish(string $topic, array $payload): void;
}
