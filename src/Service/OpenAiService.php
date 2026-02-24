<?php

namespace App\Service;

use App\Entity\EtatMental;
use OpenAI;

class OpenAiService
{
    private $client;

    public function __construct(string $apiKey)
    {
        // Groq is compatible with the OpenAI SDK by changing the base URI
        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri('https://api.groq.com/openai/v1')
            ->make();
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
            $result = $this->client->chat()->create([
                'model' => 'llama-3.3-70b-versatile',
                'messages' => $messages,
            ]);
            return $result->choices[0]->message->content;
        } catch (\Exception $e) {
            error_log('[Aura AI] API Error: ' . $e->getMessage());
            return "AI Error: " . $e->getMessage();
        }
    }

    public function generateRecommendations(EtatMental $etatMental): array
    {
        $prompt = $this->buildPrompt($etatMental);

        try {
            $result = $this->client->chat()->create([
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional fitness and mental health coach. You provide personalized exercise recommendations in JSON format.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $result->choices[0]->message->content;
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
