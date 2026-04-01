<?php
/**
 * GitHub Webhook — instant deploy pull
 *
 * Verifies the HMAC-SHA256 signature from GitHub, then executes
 * /opt/deploy-pull.sh in the background for near-instant code delivery.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$secretFile = '/opt/.webhook-secret';
if (!file_exists($secretFile)) {
    http_response_code(500);
    exit('Secret not configured');
}
$secret = trim(file_get_contents($secretFile));
if (empty($secret)) {
    http_response_code(500);
    exit('Secret empty');
}

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (empty($sigHeader) || empty($payload)) {
    http_response_code(403);
    exit('Missing signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $sigHeader)) {
    http_response_code(403);
    exit('Invalid signature');
}

$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    http_response_code(200);
    exit('Ignored: not main branch');
}

exec('/opt/deploy-pull.sh > /dev/null 2>&1 &');

http_response_code(200);
echo 'OK';
