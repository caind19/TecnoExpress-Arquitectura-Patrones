<?php

require __DIR__ . '/../src/Domain/Stock.php';
require __DIR__ . '/../src/Domain/Ports/StockRepositoryInterface.php';
require __DIR__ . '/../src/Domain/Ports/EventPublisherInterface.php';
require __DIR__ . '/../src/Application/ProcessOrderStockUseCase.php';
require __DIR__ . '/../src/Infrastructure/Adapters/JsonFileStockRepository.php';
require __DIR__ . '/../src/Infrastructure/Adapters/HttpEventPublisher.php';
require __DIR__ . '/../src/Infrastructure/Http/EventController.php';

use InventoryService\Application\ProcessOrderStockUseCase;
use InventoryService\Infrastructure\Adapters\JsonFileStockRepository;
use InventoryService\Infrastructure\Adapters\HttpEventPublisher;
use InventoryService\Infrastructure\Http\EventController;

// Composition root: se arman los adaptadores y se inyectan en el caso de uso.
$brokerUrl = getenv('BROKER_URL') ?: 'http://localhost:4000';
$selfUrl = getenv('SELF_URL') ?: 'http://localhost:4002';

$stockRepository = new JsonFileStockRepository(__DIR__ . '/../data/stock.json');
$eventPublisher = new HttpEventPublisher($brokerUrl);
$useCase = new ProcessOrderStockUseCase($stockRepository, $eventPublisher);
$controller = new EventController($useCase);

// El servidor embebido de PHP no tiene un "arranque" como Node; la
// suscripción al broker se hace una sola vez marcándola con un archivo.
suscribirseAlBrokerUnaVez($brokerUrl, $selfUrl . '/events', 'pedido.creado', __DIR__ . '/../data/subscribed.flag');

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
        error_log("[inventory-service] suscrito a \"$topic\"");
    }
}

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $path === '/health') {
    echo json_encode(['ok' => true, 'service' => 'inventory-service']);
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
