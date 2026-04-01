import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

php_file = r"""<?php
$apiKey = 'AIzaSyCqeIQJK4qL31fCtO94TtyR5HC-mcHCoWQ';

// Test Places API (New) - Text Search
$url = 'https://places.googleapis.com/v1/places:searchText';
$body = json_encode([
    'textQuery' => 'نماء للتقسيط الاسلامي',
    'locationBias' => [
        'circle' => [
            'center' => ['latitude' => 32.0, 'longitude' => 36.0],
            'radius' => 50000.0
        ]
    ],
    'languageCode' => 'ar',
    'maxResultCount' => 5,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: places.displayName,places.formattedAddress,places.location,places.types',
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

echo "HTTP: $httpCode\n";
if ($curlErr) echo "CURL_ERR: $curlErr\n";

$data = json_decode($response, true);
if (isset($data['error'])) {
    echo "API_ERROR: " . ($data['error']['message'] ?? 'unknown') . "\n";
    echo "API_STATUS: " . ($data['error']['status'] ?? 'unknown') . "\n";
} elseif (isset($data['places'])) {
    echo "RESULTS: " . count($data['places']) . "\n";
    foreach ($data['places'] as $p) {
        echo "  NAME: " . ($p['displayName']['text'] ?? '?') . "\n";
        echo "  ADDR: " . ($p['formattedAddress'] ?? '?') . "\n";
        echo "  LAT: " . ($p['location']['latitude'] ?? '?') . "\n";
        echo "  LNG: " . ($p['location']['longitude'] ?? '?') . "\n";
        echo "  ---\n";
    }
} else {
    echo "RAW: " . substr($response, 0, 500) . "\n";
}
"""

stdin, stdout, stderr = ssh.exec_command("cat > /tmp/_test_places.php << 'PHPEOF'\n" + php_file + "\nPHPEOF")
stdout.read()
stdin, stdout, stderr = ssh.exec_command("php /tmp/_test_places.php && rm /tmp/_test_places.php", timeout=20)
out = stdout.read().decode().strip()
err = stderr.read().decode().strip()
print(f'OUT:\n{out}')
if err:
    print(f'ERR: {err[:300]}')

ssh.close()
