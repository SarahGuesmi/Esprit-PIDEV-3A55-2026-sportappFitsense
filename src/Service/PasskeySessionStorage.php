<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Stores pending passkey login session (challenge + optional authenticated user)
 * in cache. TTL 5 minutes.
 */
final class PasskeySessionStorage
{
    private const PREFIX = 'passkey_session_';
    private const TTL = 300; // 5 minutes

    public function __construct(
        private CacheInterface $cache
    ) {
    }

    public function createSession(string $sessionId, string $challengeBase64, string $optionsJson): void
    {
        $key = self::PREFIX . $sessionId;
        $this->cache->delete($key);
        $this->cache->get($key, function (ItemInterface $item) use ($challengeBase64, $optionsJson) {
            $item->expiresAfter(self::TTL);
            return ['challenge' => $challengeBase64, 'optionsJson' => $optionsJson, 'userId' => null];
        });
    }

    public function getChallenge(string $sessionId): ?string
    {
        $data = $this->getSession($sessionId);
        return $data['challenge'] ?? null;
    }

    public function getOptionsJson(string $sessionId): ?string
    {
        $data = $this->getSession($sessionId);
        return $data['optionsJson'] ?? null;
    }

    public function getSession(string $sessionId): ?array
    {
        $key = self::PREFIX . $sessionId;
        $data = $this->cache->get($key, function (ItemInterface $item) {
            // Do NOT cache a null result (negative caching).
            // This prevents the "Link expired" race condition if polling hits
            // exactly during a session update/deletion window.
            $item->expiresAfter(-1);
            return null;
        });
        return \is_array($data) ? $data : null;
    }

    public function markAuthenticated(string $sessionId, int $userId): void
    {
        $key = self::PREFIX . $sessionId;
        $data = $this->getSession($sessionId);
        if (\is_array($data)) {
            $data['userId'] = $userId;
            $this->cache->delete($key);
            $this->cache->get($key, function (ItemInterface $item) use ($data) {
                // Give user enough time to see the redirect on desktop
                $item->expiresAfter(self::TTL); 
                return $data;
            });
        }
    }

}
