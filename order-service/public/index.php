<?php

require __DIR__ . '/../src/Domain/Order.php';
require __DIR__ . '/../src/Domain/Ports/OrderRepositoryInterface.php';
require __DIR__ . '/../src/Domain/Ports/EventPublisherInterface.php';
require __DIR__ . '/../src/Application/CreateOrderUseCase.php';
require __DIR__ . '/../src/Infrastructure/Adapters/JsonFileOrderRepository.php';
require __DIR__ . '/../src/Infrastructure/Adapters/HttpEventPublisher.php';
require __DIR__ . '/../src/Infrastructure/Http/OrderController.php';

use OrderService\Application\CreateOrderUseCase;
use OrderService\Infrastructure\Adapters\JsonFileOrderRepository;
use OrderService\Infrastructure\Adapters\HttpEventPublisher;
use OrderService\Infrastructure\Http\OrderController;

// Composition root: aquí se arman los adaptadores concretos y se
// inyectan en el caso de uso (Inyección de Dependencias por constructor).
$brokerUrl = getenv('BROKER_URL') ?: 'http://localhost:4000';

$orderRepository = new JsonFileOrderRepository(__DIR__ . '/../data/orders.json');
$eventPublisher = new HttpEventPublisher($brokerUrl);
$createOrderUseCase = new CreateOrderUseCase($orderRepository, $eventPublisher);
$controller = new OrderController($createOrderUseCase);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $path === '/health') {
    echo json_encode(['ok' => true, 'service' => 'order-service']);
    exit;
}

if ($method === 'POST' && $path === '/orders') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $resultado = $controller->crearPedido($body);
    http_response_code($resultado['status']);
    echo json_encode($resultado['body']);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'no encontrado']);
