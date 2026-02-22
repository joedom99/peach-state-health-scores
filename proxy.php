<?php
/**
 * DPH API Proxy for health.countryfriedlabs.com
 *
 * Proxies requests to ga.healthinspections.us to avoid CORS restrictions.
 * The DPH API uses a ColdFusion REST backend with base64-encoded search params.
 *
 * Usage:
 *   /proxy.php?endpoint=search&params={"keyword":"cGl6emE="}&page=0
 *   /proxy.php?endpoint=facilities&start=0
 *   /proxy.php?endpoint=inspections&id=ODc2NTQw
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

// Only allow requests from our own domain
$allowed_origins = [
    'https://health.countryfriedlabs.com',
    'http://health.countryfriedlabs.com',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// Rate limiting: max 60 requests per minute per IP
$ip = $_SERVER['REMOTE_ADDR'];
$rate_file = sys_get_temp_dir() . '/dph_rate_' . md5($ip);
$now = time();

if (file_exists($rate_file)) {
    $data = json_decode(file_get_contents($rate_file), true);
    // Clean old entries
    $data['hits'] = array_filter($data['hits'], function($t) use ($now) { return ($now - $t) < 60; });
    if (count($data['hits']) >= 60) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded. Try again in a minute.']);
        exit;
    }
    $data['hits'][] = $now;
} else {
    $data = ['hits' => [$now]];
}
file_put_contents($rate_file, json_encode($data));

// Build the DPH API URL
$base = 'https://ga.healthinspections.us/stateofgeorgia/API/index.cfm';
$endpoint = $_GET['endpoint'] ?? 'facilities';

if ($endpoint === 'search') {
    $params = $_GET['params'] ?? '{}';
    $page   = intval($_GET['page'] ?? 0);
    $url    = $base . '/search/' . urlencode($params) . '/' . $page;
} elseif ($endpoint === 'facilities') {
    $start = intval($_GET['start'] ?? 0);
    $url   = $base . '/facilities/' . $start;
} elseif ($endpoint === 'inspections') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing facility id parameter.']);
        exit;
    }
    $url = $base . '/inspectionsData/' . urlencode($id);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint. Use "search", "facilities", or "inspections".']);
    exit;
}

// Fetch from DPH API
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'HealthInspectionMap/1.0 (health.countryfriedlabs.com)',
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to reach DPH API: ' . $error]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'DPH API returned HTTP ' . $httpCode]);
    exit;
}

// Validate it's JSON before passing through
json_decode($response);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'DPH API returned non-JSON response']);
    exit;
}

// Pass through the raw JSON
echo $response;
