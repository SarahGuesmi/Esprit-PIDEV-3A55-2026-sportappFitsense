<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExerciseApiService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private string $baseUrl = 'https://exercisedb.p.rapidapi.com';
    private string $host = 'exercisedb.p.rapidapi.com';

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

private function request(string $endpoint, int $limit = 20, int $offset = 0): array
{
    $response = $this->client->request('GET', $this->baseUrl . $endpoint, [
        'headers' => [
            'X-RapidAPI-Key'  => $this->apiKey,
            'X-RapidAPI-Host' => $this->host,
        ],
        'query' => [
            'limit'  => $limit,
            'offset' => $offset,
        ]
    ]);

    $data = $response->toArray();

    // Ajouter gifUrl à chaque exercice
    foreach ($data as &$exercise) {
        if (is_array($exercise) && isset($exercise['id']) && !isset($exercise['gifUrl'])) {
            $exercise['gifUrl'] = $this->baseUrl . '/image/' . $exercise['id'] . '.gif';
        }
    }

    return $data;
}

    // Tous les exercices (avec pagination)
    public function getExercises(int $limit = 20, int $offset = 0): array
    {
        return $this->request('/exercises', $limit, $offset);
    }

    // Exercice par ID
public function getExerciseById(string $id): array
{
    try {
        $response = $this->client->request('GET', $this->baseUrl . '/exercises/exercise/' . $id, [
            'headers' => [
                'X-RapidAPI-Key'  => $this->apiKey,
                'X-RapidAPI-Host' => $this->host,
            ],
        ]);

        $data = $response->toArray();

        if (!empty($data) && !isset($data['gifUrl'])) {
            $data['gifUrl'] = 'https://exercisedb.p.rapidapi.com/image?exerciseId=' . $id . '&resolution=360&rapidapi-key=' . $this->apiKey;
        }

        return $data;

    } catch (\Exception $e) {
        return [];
    }
}





    // Par partie du corps (waist, chest, back, shoulders, upper arms...)
    public function getByBodyPart(string $bodyPart, int $limit = 20): array
    {
        return $this->request('/exercises/bodyPart/' . $bodyPart, $limit);
    }

    // Par muscle cible (abs, biceps, glutes, hamstrings...)
    public function getByTarget(string $target, int $limit = 20): array
    {
        return $this->request('/exercises/target/' . $target, $limit);
    }

    // Par équipement (body weight, dumbbell, barbell, cable...)
    public function getByEquipment(string $equipment, int $limit = 20): array
    {
        return $this->request('/exercises/equipment/' . $equipment, $limit);
    }

    // Recherche par nom
    public function searchByName(string $name, int $limit = 20): array
    {
        return $this->request('/exercises/name/' . urlencode($name), $limit);
    }

    // Listes des valeurs disponibles (pour faire des filtres)
    public function getBodyPartList(): array
    {
        $response = $this->client->request('GET', $this->baseUrl . '/exercises/bodyPartList', [
            'headers' => [
                'X-RapidAPI-Key'  => $this->apiKey,
                'X-RapidAPI-Host' => $this->host,
            ]
        ]);
        return $response->toArray();
    }

    public function getTargetList(): array
    {
        $response = $this->client->request('GET', $this->baseUrl . '/exercises/targetList', [
            'headers' => [
                'X-RapidAPI-Key'  => $this->apiKey,
                'X-RapidAPI-Host' => $this->host,
            ]
        ]);
        return $response->toArray();
    }

    public function getEquipmentList(): array
    {
        $response = $this->client->request('GET', $this->baseUrl . '/exercises/equipmentList', [
            'headers' => [
                'X-RapidAPI-Key'  => $this->apiKey,
                'X-RapidAPI-Host' => $this->host,
            ]
        ]);
        return $response->toArray();
    }
}