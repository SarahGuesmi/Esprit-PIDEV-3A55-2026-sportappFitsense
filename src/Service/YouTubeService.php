<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class YouTubeService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $apiKey
    ) {}

    /**
     * Retourne plusieurs vidéos pour que le coach puisse choisir
     */
    public function searchVideos(string $query, int $maxResults = 5): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
                'query' => [
                    'part'              => 'snippet',
                    'q'                 => $query . ' exercise tutorial',
                    'type'              => 'video',
                    'maxResults'        => $maxResults,
                    'key'               => $this->apiKey,
                    'relevanceLanguage' => 'en',
                    'safeSearch'        => 'strict',
                ],
            ]);

            $data   = $response->toArray();
            $videos = [];

            foreach ($data['items'] ?? [] as $item) {
                $videos[] = [
                    'videoId'     => $item['id']['videoId'],
                    'title'       => $item['snippet']['title'],
                    'thumbnail'   => $item['snippet']['thumbnails']['medium']['url'],
                    'channelName' => $item['snippet']['channelTitle'],
                ];
            }

            return $videos;

        } catch (\Exception $e) {
            return [];
        }
    }
}