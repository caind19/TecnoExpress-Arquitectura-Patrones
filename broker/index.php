<?php
/**
 * Broker de eventos: implementa el patrón Publisher-Subscriber.
 * Los microservicios no se llaman entre sí, todos pasan por aquí.
 *   POST /subscribe  { topic, url }    -> registra un webhook
 *   POST /publish     { topic, payload } -> notifica a los suscriptores
 *
 * El servidor embebido de PHP (`php -S`) arranca un proceso nuevo por
 * cada request, así que las suscripciones se guardan en un archivo JSON
 * (data/subscriptions.json) para que persistan entre peticiones.
 */

header('Content-Type: application/json');

$dataFile = __DIR__ . '/data/subscriptions.json';
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

function leerSuscripciones(string $archivo): array
{
    if (!file_exists($archivo)) {
        return [];
    }
    $contenido = file_get_contents($archivo);
    return json_decode($contenido, true) ?: [];
}

function guardarSuscripciones(string $archivo, array $suscripciones): void
{
    file_put_contents($archivo, json_encode($suscripciones, JSON_PRETTY_PRINT), LOCK_EX);
}

function httpPostJson(string $url, array $data): void
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST' && $path === '/subscribe') {
    $topic = $body['topic'] ?? null;
    $url = $body['url'] ?? null;
    if (!$topic || !$url) {
        http_response_code(400);
        echo json_encode(['error' => 'topic y url son requeridos']);
        exit;
    }
    $suscripciones = leerSuscripciones($dataFile);
    $suscripciones[$topic] = $suscripciones[$topic] ?? [];
    if (!in_array($url, $suscripciones[$topic], true)) {
        $suscripciones[$topic][] = $url;
    }
    guardarSuscripciones($dataFile, $suscripciones);

    error_log("[broker] suscripción nueva -> topic=\"$topic\" url=$url");
    http_response_code(201);
    echo json_encode(['ok' => true, 'topic' => $topic, 'url' => $url]);
    exit;
}

if ($method === 'POST' && $path === '/publish') {
    $topic = $body['topic'] ?? null;
    $payload = $body['payload'] ?? null;
    if (!$topic) {
        http_response_code(400);
        echo json_encode(['error' => 'topic es requerido']);
        exit;
    }
    $suscripciones = leerSuscripciones($dataFile);
    $urls = $suscripciones[$topic] ?? [];

    error_log("[broker] evento publicado -> topic=\"$topic\" (" . count($urls) . " suscriptor(es))");
    foreach ($urls as $url) {
        httpPostJson($url, ['topic' => $topic, 'payload' => $payload]);
    }

    echo json_encode(['ok' => true, 'topic' => $topic, 'subscribers' => count($urls)]);
    exit;
}

if ($method === 'GET' && $path === '/subscriptions') {
    echo json_encode(leerSuscripciones($dataFile));
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'no encontrado']);
