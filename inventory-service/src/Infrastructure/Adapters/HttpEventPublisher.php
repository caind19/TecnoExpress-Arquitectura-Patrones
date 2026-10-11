<?php

namespace InventoryService\Infrastructure\Adapters;

use InventoryService\Domain\Ports\EventPublisherInterface;

// Adaptador de salida: publica eventos hacia el broker por HTTP (cURL).
class HttpEventPublisher implements EventPublisherInterface
{
    private string $brokerUrl;

    public function __construct(string $brokerUrl)
    {
        $this->brokerUrl = $brokerUrl;
    }

    public function publish(string $topic, array $payload): void
    {
        $ch = curl_init($this->brokerUrl . '/publish');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['topic' => $topic, 'payload' => $payload]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
