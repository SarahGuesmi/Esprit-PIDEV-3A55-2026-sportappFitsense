<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class IPInfoService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ?string $apiToken;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, ?string $apiToken = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiToken = $apiToken;
    }

    public function getLocationData(string $ipAddress): array
    {
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return [
                'city' => 'Localhost',
                'region' => 'Localhost',
                'country' => 'Local',
                'isp' => 'Local Network',
            ];
        }

        try {
            $url = sprintf('https://ipinfo.io/%s?token=%s', $ipAddress, $this->apiToken);
            $response = $this->httpClient->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("IPinfo API returned status " . $response->getStatusCode());
                return [];
            }

            $data = $response->toArray();

            return [
                'city' => $data['city'] ?? 'Unknown',
                'region' => $data['region'] ?? 'Unknown',
                'country' => $data['country'] ?? 'Unknown',
                'isp' => $data['org'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            $this->logger->error("Error calling IPinfo API: " . $e->getMessage());
            return [];
        }
    }
}
