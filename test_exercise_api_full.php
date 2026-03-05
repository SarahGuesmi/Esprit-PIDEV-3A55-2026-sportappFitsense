<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();

echo "Testing ExerciseDB API - Full Response...\n\n";

try {
    $response = $client->request('GET', 'https://exercisedb.p.rapidapi.com/exercises/exercise/0001', [
        'headers' => [
            'X-RapidAPI-Key' => '844694aeebmshd408871dd47839bp110778jsna826c46db6b4',
            'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
        ],
    ]);

    $data = $response->toArray();
    
    echo "Full API Response:\n";
    print_r($data);
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
