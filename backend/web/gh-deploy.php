<?php
/**
 * GitHub Webhook — instant deploy pull
 *
 * Verifies the HMAC-SHA256 signature from GitHub, then executes
 * /opt/deploy-pull.sh in the background for near-instant code delivery.
 */

$debugLog = '/var/log/gh-deploy-debug.log';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$secretFile = '/opt/.webhook-secret';
if (!file_exists($secretFile)) {
    file_put_contents($debugLog, date('c') . " ERR: secret file not found\n", FILE_APPEND);
    http_response_code(500);
    exit('Secret not configured');
}
$secret = trim(file_get_contents($secretFile));

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

file_put_contents($debugLog, date('c') . " secret_len=" . strlen($secret)
    . " sig_present=" . (!empty($sigHeader) ? 'yes' : 'no')
    . " payload_len=" . strlen($payload) . "\n", FILE_APPEND);

if (empty($sigHeader) || empty($payload)) {
    file_put_contents($debugLog, date('c') . " ERR: missing sig or payload\n", FILE_APPEND);
    http_response_code(403);
    exit('Missing signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $sigHeader)) {
    file_put_contents($debugLog, date('c') . " ERR: sig mismatch\n"
        . "  got:    $sigHeader\n"
        . "  expect: $expected\n"
        . "  secret_first8: " . substr($secret, 0, 8) . "\n", FILE_APPEND);
    http_response_code(403);
    exit('Invalid signature');
}

// Only act on push to main
$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    http_response_code(200);
    exit('Ignored: not main branch');
}

// Execute deploy in background (non-blocking)
exec('/opt/deploy-pull.sh > /dev/null 2>&1 &');

http_response_code(200);
echo 'OK';
