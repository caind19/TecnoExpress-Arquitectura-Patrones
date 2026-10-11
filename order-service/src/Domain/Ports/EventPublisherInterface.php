<?php

namespace OrderService\Domain\Ports;

// Puerto: contrato para publicar eventos de dominio (patrón Pub-Sub).
interface EventPublisherInterface
{
    public function publish(string $topic, array $payload): void;
}
