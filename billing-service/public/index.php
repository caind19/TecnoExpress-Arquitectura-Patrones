<?php

require __DIR__ . '/../src/Domain/Comprobante.php';
require __DIR__ . '/../src/Domain/Ports/ComprobanteRepositoryInterface.php';
require __DIR__ . '/../src/Domain/Ports/DocumentGeneratorInterface.php';
require __DIR__ . '/../src/Application/GenerarComprobanteUseCase.php';
require __DIR__ . '/../src/Infrastructure/Adapters/JsonFileComprobanteRepository.php';
require __DIR__ . '/../src/Infrastructure/Adapters/SimplePdfDocumentGenerator.php';
require __DIR__ . '/../src/Infrastructure/Http/EventController.php';

use BillingService\Application\GenerarComprobanteUseCase;
use BillingService\Infrastructure\Adapters\JsonFileComprobanteRepository;
use BillingService\Infrastructure\Adapters\SimplePdfDocumentGenerator;
use BillingService\Infrastructure\Http\EventController;

// Composition root: se arman los adaptadores y se inyectan en el caso de uso.
$brokerUrl = getenv('BROKER_URL') ?: 'http://localhost:4000';
$selfUrl = getenv('SELF_URL') ?: 'http://localhost:4003';

$comprobanteRepository = new JsonFileComprobanteRepository(__DIR__ . '/../data/comprobantes.json');
$documentGenerator = new SimplePdfDocumentGenerator(__DIR__ . '/../comprobantes');
$useCase = new GenerarComprobanteUseCase($comprobanteRepository, $documentGenerator);
$controller = new EventController($useCase);

suscribirseAlBrokerUnaVez($brokerUrl, $selfUrl . '/events', 'inventario.descontado', __DIR__ . '/../data/subscribed.flag');

function suscribirseAlBrokerUnaVez(string $brokerUrl, string $miUrl, string $topic, string $flag): void
{
    if (file_exists($flag)) {
        return;
    }
    $ch = curl_init($brokerUrl . '/subscribe');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['topic' => $topic, 'url' => $miUrl]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
    ]);
    $ok = curl_exec($ch) !== false;
    curl_close($ch);
    if ($ok) {
        file_put_contents($flag, '1');
        error_log("[billing-service] suscrito a \"$topic\"");
    }
}

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $path === '/health') {
    echo json_encode(['ok' => true, 'service' => 'billing-service']);
    exit;
}

if ($method === 'GET' && $path === '/comprobantes') {
    echo json_encode($comprobanteRepository->all());
    exit;
}

if ($method === 'POST' && $path === '/events') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $resultado = $controller->recibirEvento($body);
    http_response_code($resultado['status']);
    echo json_encode($resultado['body']);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'no encontrado']);
