<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$exerciseId = '0001';

echo "Testing different GIF URL formats for exercise ID: $exerciseId\n\n";

$urlFormats = [
    'https://v2.exercisedb.io/image/' . $exerciseId,
    'https://exercisedb.io/wp-content/uploads/2023/gifs/' . $exerciseId . '.gif',
    'https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/exercises/' . $exerciseId . '.gif',
    'https://api.exercisedb.io/image/' . $exerciseId,
];

foreach ($urlFormats as $url) {
    echo "Testing: $url\n";
    try {
        $response = $client->request('GET', $url, ['timeout' => 5]);
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0] ?? 'N/A';
        
        if ($statusCode === 200 && str_contains($contentType, 'image')) {
            echo "✅ SUCCESS! Status: $statusCode, Type: $contentType\n";
            echo "WORKING URL: $url\n\n";
        } else {
            echo "❌ Status: $statusCode, Type: $contentType\n\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}
