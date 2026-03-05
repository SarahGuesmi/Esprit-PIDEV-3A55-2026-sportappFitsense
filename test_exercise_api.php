<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();

echo "Testing ExerciseDB API...\n\n";

try {
    $response = $client->request('GET', 'https://exercisedb.p.rapidapi.com/exercises/exercise/0001', [
        'headers' => [
            'X-RapidAPI-Key' => '844694aeebmshd408871dd47839bp110778jsna826c46db6b4',
            'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
        ],
    ]);

    $data = $response->toArray();
    
    echo "Exercise ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "Exercise Name: " . ($data['name'] ?? 'N/A') . "\n";
    
    // Construire l'URL du GIF
    $gifUrl = 'https://v2.exercisedb.io/image/' . $data['id'];
    echo "Constructed GIF URL: " . $gifUrl . "\n\n";
    
    echo "Testing GIF URL...\n";
    try {
        $gifResponse = $client->request('GET', $gifUrl);
        echo "GIF Status: " . $gifResponse->getStatusCode() . "\n";
        echo "GIF Content-Type: " . ($gifResponse->getHeaders()['content-type'][0] ?? 'N/A') . "\n";
        echo "✅ GIF loads successfully!\n";
    } catch (\Exception $e) {
        echo "❌ GIF Error: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
