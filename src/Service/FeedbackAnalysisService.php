<?php

namespace App\Service;

use OpenAI;

class FeedbackAnalysisService
{
    private $client;

    public function __construct(string $openaiApiKey)
    {
        $this->client = OpenAI::client($openaiApiKey);
    }

    /**
     * Analyse le sentiment et extrait les mots-clés d'un commentaire
     * 
     * @param string $comment Le commentaire à analyser
     * @return array ['sentiment' => 'positive|neutral|negative', 'keywords' => ['mot1', 'mot2'], 'summary' => 'résumé']
     */
    public function analyzeFeedback(string $comment, int $retries = 3): array
    {
        if (empty(trim($comment))) {
            return [
                'sentiment' => 'neutral',
                'keywords' => [],
                'summary' => 'No comment provided'
            ];
        }

        $lastError = null;
        
        for ($attempt = 0; $attempt < $retries; $attempt++) {
            try {
                $response = $this->client->chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a fitness feedback analyzer. Analyze user feedback about workouts and return ONLY a valid JSON object with: sentiment (positive/neutral/negative), keywords (array of 3-5 key terms), and summary (one short sentence). No markdown, no code blocks, just pure JSON.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Analyze this workout feedback: \"$comment\""
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 150
                ]);

                $content = $response->choices[0]->message->content;
                
                // Nettoyer la réponse (enlever les markdown code blocks si présents)
                $content = preg_replace('/```json\s*|\s*```/', '', $content);
                $content = trim($content);
                
                $analysis = json_decode($content, true);

                if (!$analysis || !isset($analysis['sentiment'])) {
                    throw new \Exception('Invalid response format');
                }

                return [
                    'sentiment' => strtolower($analysis['sentiment']),
                    'keywords' => $analysis['keywords'] ?? [],
                    'summary' => $analysis['summary'] ?? 'Analysis completed'
                ];

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                
                // Si c'est un rate limit, attendre avant de réessayer
                if (str_contains($lastError, 'rate limit') && $attempt < $retries - 1) {
                    // Délai exponentiel: 2s, 4s, 8s
                    $delay = pow(2, $attempt + 1);
                    sleep($delay);
                    continue;
                }
                
                // Pour les autres erreurs, ne pas réessayer
                break;
            }
        }
        
        // En cas d'échec après tous les retries, utiliser l'analyse simple
        return [
            'sentiment' => $this->detectSimpleSentiment($comment),
            'keywords' => $this->extractSimpleKeywords($comment),
            'summary' => 'Simple analysis (API unavailable)',
            'error' => $lastError
        ];
    }

    /**
     * Détection simple de sentiment basée sur des mots-clés
     */
    private function detectSimpleSentiment(string $text): string
    {
        $text = strtolower($text);
        
        $positiveWords = ['amazing', 'great', 'excellent', 'love', 'good', 'best', 'awesome', 'fantastic', 'perfect', 'wonderful', 'like', 'enjoy', 'energized', 'strong'];
        $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'worst', 'poor', 'difficult', 'hard', 'exhausted', 'boring', 'no', 'not', 'never', 'don\'t'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (str_contains($text, $word)) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (str_contains($text, $word)) {
                $negativeCount++;
            }
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }
        
        return 'neutral';
    }

    /**
     * Extraction simple de mots-clés en cas d'échec de l'API
     */
    private function extractSimpleKeywords(string $text): array
    {
        // Mots vides à ignorer
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'mais', 'est', 'sont', 'a', 'the', 'is', 'are', 'was', 'were', 'this', 'that'];
        
        $words = str_word_count(strtolower($text), 1);
        $words = array_diff($words, $stopWords);
        $words = array_filter($words, fn($w) => strlen($w) > 3);
        
        $wordCounts = array_count_values($words);
        arsort($wordCounts);
        
        return array_slice(array_keys($wordCounts), 0, 5);
    }

    /**
     * Analyse multiple feedbacks en batch
     */
    public function analyzeBatch(array $comments): array
    {
        $results = [];
        foreach ($comments as $id => $comment) {
            $results[$id] = $this->analyzeFeedback($comment);
        }
        return $results;
    }

    /**
     * Obtenir une couleur pour le sentiment
     */
    public static function getSentimentColor(string $sentiment): string
    {
        return match($sentiment) {
            'positive' => 'green',
            'negative' => 'red',
            'neutral' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Obtenir un emoji pour le sentiment
     */
    public static function getSentimentEmoji(string $sentiment): string
    {
        return match($sentiment) {
            'positive' => '😊',
            'negative' => '😞',
            'neutral' => '😐',
            default => '❓'
        };
    }
}
