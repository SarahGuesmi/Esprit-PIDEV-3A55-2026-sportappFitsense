<?php

namespace App\Service;

use App\Entity\EtatMental;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private string $baseUrl = 'https://api.groq.com/openai/v1';
    private string $model = 'llama-3.3-70b-versatile';

    public function __construct(string $apiKey, HttpClientInterface $httpClient)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }

    /**
     * Make a chat completion request to the Groq API.
     */
    private function chatCompletion(array $messages, ?array $responseFormat = null): array
    {
        $body = [
            'model' => $this->model,
            'messages' => $messages,
        ];

        if ($responseFormat) {
            $body['response_format'] = $responseFormat;
        }

        $response = $this->httpClient->request('POST', $this->baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);

        return $response->toArray();
    }

    public function chat(string $userMessage, array $history, $user): string
    {
        $userName = $user ? $user->getFirstname() : 'Friend';

        $messages = [
            [
                'role' => 'system',
                'content' => "You are FitSense Wellness Coach, a caring, empathetic AI mental health and fitness assistant. Your name is \"Aura\". The user's name is {$userName}. Keep replies short, warm, supportive, and practical."
            ]
        ];

        foreach ($history as $turn) {
            if (isset($turn['role'], $turn['content'])) {
                $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $result = $this->chatCompletion($messages);
            return $result['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            error_log('[Aura AI] API Error: ' . $e->getMessage());
            return "AI Error: " . $e->getMessage();
        }
    }

    /**
     * Generate creative fitness-themed username suggestions for a new user.
     *
     * @return string[] Array of 5 unique username suggestions
     */
    public function generateUsernameSuggestions(string $firstname, string $lastname): array
    {
        try {
            $result = $this->chatCompletion([
                [
                    'role' => 'system',
                    'content' => 'You are a creative username generator for a fitness platform called FitSense. Generate unique, catchy, fitness-themed usernames. Return ONLY a JSON array of exactly 5 strings, no explanation, no markdown, no code blocks. Example: ["FitWarrior42","IronPulse","SweatStar99","PeakVibes","ZenRunner"]'
                ],
                [
                    'role' => 'user',
                    'content' => "Generate 5 unique fitness-themed usernames for a user named {$firstname} {$lastname}. Mix creativity with fitness culture. Make them catchy and memorable. Return only a JSON array of 5 strings."
                ],
            ]);

            $content = trim($result['choices'][0]['message']['content']);

            // Strip markdown code blocks if present
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);

            $suggestions = json_decode($content, true);

            if (is_array($suggestions) && count($suggestions) >= 1) {
                return array_slice(array_values($suggestions), 0, 5);
            }

            // Fallback: extract quoted strings
            preg_match_all('/"([^"]+)"/', $content, $matches);
            if (!empty($matches[1])) {
                return array_slice($matches[1], 0, 5);
            }

            return $this->fallbackUsernames($firstname);
        } catch (\Exception $e) {
            error_log('[Aura AI] Username suggestion error: ' . $e->getMessage());
            return $this->fallbackUsernames($firstname);
        }
    }

    /**
     * Fallback usernames if the AI call fails.
     */
    private function fallbackUsernames(string $firstname): array
    {
        $base = preg_replace('/[^a-zA-Z]/', '', $firstname);
        $base = $base ?: 'Athlete';
        return [
            $base . 'Fit' . rand(10, 99),
            'Iron' . $base . rand(10, 99),
            $base . 'Pulse',
            'Peak' . $base,
            $base . 'Runner' . rand(10, 99),
        ];
    }


    public function generateRecommendations(EtatMental $etatMental): array
    {
        $prompt = $this->buildPrompt($etatMental);

        try {
            $result = $this->chatCompletion(
                [
                    ['role' => 'system', 'content' => 'You are a professional fitness and mental health coach. You provide personalized exercise recommendations in JSON format.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                ['type' => 'json_object']
            );

            $content = $result['choices'][0]['message']['content'];
            $data = json_decode($content, true);

            if (!isset($data['recommendations'])) {
                throw new \Exception('Invalid AI response structure.');
            }

            return $data['recommendations'];
        } catch (\Exception $e) {
            error_log('[Aura AI] Recommendation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function buildPrompt(EtatMental $etatMental): string
    {
        $username = $etatMental->getUser() ? $etatMental->getUser()->getFirstname() : 'User';
        
        return "Based on the following mental health assessment for {$username}, suggest 3 specific physical or relaxation exercises. 
        
        CRITICAL: Prioritize the 'User Note' below. If the user mentions a specific pain (e.g., back, neck, eyes) or emotional state, provide exercises that directly address it.

        Assessment data (on a scale of 1-5, where 1 is poor and 5 is excellent):
        - Stress Level: {$etatMental->getStressLevel()}
        - Sleep Quality: {$etatMental->getSleepQuality()}
        - Mood: {$etatMental->getMood()}
        - Motivation: {$etatMental->getMotivation()}
        - Mental Fatigue: {$etatMental->getMentalFatigue()}
        
        User Note: \"{$etatMental->getDescription()}\"

        Return a JSON object with a 'recommendations' key containing an array of objects. 
        Each object must have 'title', 'description' (a helpful 1-2 sentence instruction), and 'duration' (in minutes).
        Example format:
        {
            \"recommendations\": [
                {
                    \"title\": \"Example Exercise\",
                    \"description\": \"Do this to feel better.\",
                    \"duration\": 15
                }
            ]
        }";
    }
}
