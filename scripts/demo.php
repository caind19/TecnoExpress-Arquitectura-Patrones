<?php

// Script de prueba: crea un pedido y deja correr el flujo Pub-Sub
// (order-service -> broker -> inventory-service -> broker -> billing-service).
// Requiere que los 4 servicios ya estén corriendo.

$orderServiceUrl = getenv('ORDER_SERVICE_URL') ?: 'http://localhost:4001';
$billingServiceUrl = getenv('BILLING_SERVICE_URL') ?: 'http://localhost:4003';

function postJson(string $url, array $data): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

function getJson(string $url): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

echo "--- Demo: flujo completo Publisher-Subscriber / Microservicios ---\n\n";

echo "1) Creando pedido en order-service...\n";
$order = postJson("$orderServiceUrl/orders", [
    'clienteId' => 'CLIENTE-TECNOEXPRESS-01',
    'items' => [
        ['sku' => 'TEC-001', 'cantidad' => 2, 'precioUnitario' => 85000],
        ['sku' => 'TEC-002', 'cantidad' => 1, 'precioUnitario' => 45000],
    ],
]);
echo "   Pedido creado: " . json_encode($order) . "\n";

echo "\n2) Esperando a que el evento se propague (broker -> inventory -> broker -> billing)...\n";
sleep(1);

echo "\n3) Consultando comprobantes generados en billing-service...\n";
$comprobantes = getJson("$billingServiceUrl/comprobantes");
echo "   Comprobantes: " . json_encode($comprobantes, JSON_PRETTY_PRINT) . "\n";

echo "\nDemo finalizada. Revisa billing-service/comprobantes/ para el PDF generado.\n";
