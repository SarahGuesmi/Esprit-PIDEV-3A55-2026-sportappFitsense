<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExerciseApiService
{
    private string $baseUrl = 'https://exercisedb.p.rapidapi.com';
    private string $host    = 'exercisedb.p.rapidapi.com';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface      $cache,
        private readonly string              $apiKey
    ) {}

    // ==================== MÉTHODE CENTRALE ====================

    private function request(string $endpoint, int $limit = 20, int $offset = 0): array
    {
        // Clé de cache unique par endpoint + params
        $cacheKey = 'exercise_' . md5($endpoint . $limit . $offset);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($endpoint, $limit, $offset) {
            $item->expiresAfter(86400); // ✅ Cache 24h

            $response = $this->client->request('GET', $this->baseUrl . $endpoint, [
                'headers' => [
                    'X-RapidAPI-Key'  => $this->apiKey,
                    'X-RapidAPI-Host' => $this->host,
                ],
                'query' => [
                    'limit'  => $limit,
                    'offset' => $offset,
                ],
            ]);

            $data = $response->toArray();

            // L'API ne retourne plus gifUrl, il faut le construire
            // Les GIFs sont maintenant sur v2.exercisedb.io
            foreach ($data as &$exercise) {
                if (is_array($exercise) && isset($exercise['id']) && !isset($exercise['gifUrl'])) {
                    $exercise['gifUrl'] = 'https://v2.exercisedb.io/image/' . $exercise['id'];
                }
            }

            return $data;
        });
    }

    // ==================== MÉTHODE LISTE SIMPLE (sans pagination) ====================

    private function requestList(string $endpoint): array
    {
        $cacheKey = 'exercise_list_' . md5($endpoint);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($endpoint) {
            $item->expiresAfter(86400); // ✅ Cache 24h

            $response = $this->client->request('GET', $this->baseUrl . $endpoint, [
                'headers' => [
                    'X-RapidAPI-Key'  => $this->apiKey,
                    'X-RapidAPI-Host' => $this->host,
                ],
            ]);

            return $response->toArray();
        });
    }

    // ==================== TOUS LES EXERCICES ====================

    public function getExercises(int $limit = 20, int $offset = 0): array
    {
        return $this->request('/exercises', $limit, $offset);
    }

    // ==================== EXERCICE PAR ID ====================

    public function getExerciseById(string $id): array
    {
        $cacheKey = 'exercise_id_' . $id;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(86400); // ✅ Cache 24h

            try {
                $response = $this->client->request('GET', $this->baseUrl . '/exercises/exercise/' . $id, [
                    'headers' => [
                        'X-RapidAPI-Key'  => $this->apiKey,
                        'X-RapidAPI-Host' => $this->host,
                    ],
                ]);

                $data = $response->toArray();

                // L'API ne retourne plus gifUrl, il faut le construire
                // Les GIFs sont maintenant sur v2.exercisedb.io
                if (!empty($data) && !isset($data['gifUrl']) && isset($data['id'])) {
                    $data['gifUrl'] = 'https://v2.exercisedb.io/image/' . $data['id'];
                }

                return $data;

            } catch (\Exception $e) {
                return [];
            }
        });
    }

    // ==================== FILTRES ====================

    public function getByBodyPart(string $bodyPart, int $limit = 20): array
    {
        return $this->request('/exercises/bodyPart/' . $bodyPart, $limit);
    }

    public function getByTarget(string $target, int $limit = 20): array
    {
        return $this->request('/exercises/target/' . $target, $limit);
    }

    public function getByEquipment(string $equipment, int $limit = 20): array
    {
        return $this->request('/exercises/equipment/' . $equipment, $limit);
    }

    public function searchByName(string $name, int $limit = 20): array
    {
        return $this->request('/exercises/name/' . urlencode($name), $limit);
    }

    // ==================== LISTES DE FILTRES ====================

    public function getBodyPartList(): array
    {
        return $this->requestList('/exercises/bodyPartList');
    }

    public function getTargetList(): array
    {
        return $this->requestList('/exercises/targetList');
    }

    public function getEquipmentList(): array
    {
        return $this->requestList('/exercises/equipmentList');
    }
}