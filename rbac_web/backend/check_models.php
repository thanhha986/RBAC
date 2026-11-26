
<?php
header('Content-Type: text/plain; charset=utf-8');

$apiKey = $_SERVER['GEMINI_API_KEY']
    ?? getenv('GEMINI_API_KEY');

if (!$apiKey) {
    exit('Gemini API key is not configured.');
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'x-goog-api-key: ' . $apiKey
    ],
    CURLOPT_TIMEOUT => 30
]);

$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($result === false) {
    exit('Connection error: ' . $error);
}

$data = json_decode($result, true);

echo "HTTP Status: $status\n\n";

if ($status !== 200) {
    echo $data['error']['message'] ?? 'API request failed.';
    exit;
}

foreach ($data['models'] ?? [] as $model) {
    if (in_array(
        'generateContent',
        $model['supportedGenerationMethods'] ?? [],
        true
    )) {
        echo $model['name'] . "\n";
    }
}
