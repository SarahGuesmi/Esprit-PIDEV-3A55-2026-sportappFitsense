<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\FeedbackResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use SendGrid;
use SendGrid\Mail;

class WeeklyReportService
{
    public function __construct(
        private FeedbackResponseRepository $feedbackRepo,
        private EntityManagerInterface $em,
        private string $sendgridApiKey,
        private string $fromEmail,
        private string $fromName
    ) {
    }

    /**
     * Génère et envoie le rapport quotidien pour un coach
     */
    public function sendDailyReport(User $coach, bool $useToday = false): array
    {
        // Récupérer les feedbacks d'hier (ou aujourd'hui pour les tests)
        if ($useToday) {
            $startDate = new \DateTimeImmutable('today 00:00:00');
            $endDate = new \DateTimeImmutable('now');
        } else {
            $startDate = new \DateTimeImmutable('yesterday 00:00:00');
            $endDate = new \DateTimeImmutable('yesterday 23:59:59');
        }

        $feedbacks = $this->feedbackRepo->createQueryBuilder('f')
            ->where('f.coach = :coach')
            ->andWhere('f.createdAt >= :start')
            ->andWhere('f.createdAt <= :end')
            ->setParameter('coach', $coach)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculer les statistiques (même si vide)
        $stats = empty($feedbacks) ? $this->getEmptyStats() : $this->calculateStats($feedbacks);

        // Générer le HTML de l'email
        $htmlContent = $this->generateEmailHtml($coach, $stats, $startDate, $endDate, empty($feedbacks));

        // Envoyer l'email
        return $this->sendEmail(
            $coach->getEmail(),
            "📊 Daily Feedback Report - " . $endDate->format('M d, Y'),
            $htmlContent
        );
    }

    /**
     * Envoie le rapport à tous les coaches
     */
    public function sendToAllCoaches(bool $useToday = false): array
    {
        $coaches = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_COACH%')
            ->getQuery()
            ->getResult();

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        foreach ($coaches as $coach) {
            $result = $this->sendDailyReport($coach, $useToday);
            
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = [
                'coach' => $coach->getEmail(),
                'status' => $result['success'] ? 'sent' : 'failed',
                'message' => $result['message']
            ];
        }

        return $results;
    }

    /**
     * Calcule les statistiques des feedbacks
     */
    private function calculateStats(array $feedbacks): array
    {
        $ratingMap = [
            'excellent' => 5,
            'very good' => 4,
            'good' => 4,
            'average' => 3,
            'neutral' => 3,
            'poor' => 2,
            'bad' => 2,
            'very poor' => 1,
        ];

        $totalScore = 0;
        $scoredCount = 0;
        $sentimentCounts = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
        $workoutRatings = [];
        $notableComments = [];

        foreach ($feedbacks as $fb) {
            $key = strtolower(trim($fb->getRating()));
            $score = $ratingMap[$key] ?? 3;
            
            $totalScore += $score;
            $scoredCount++;

            // Compter les sentiments
            if ($fb->getSentiment()) {
                $sentimentCounts[$fb->getSentiment()] = ($sentimentCounts[$fb->getSentiment()] ?? 0) + 1;
            }

            // Grouper par workout
            $workoutName = $fb->getWorkout()->getNom();
            if (!isset($workoutRatings[$workoutName])) {
                $workoutRatings[$workoutName] = ['scores' => [], 'count' => 0];
            }
            $workoutRatings[$workoutName]['scores'][] = $score;
            $workoutRatings[$workoutName]['count']++;

            // Collecter les commentaires marquants (positifs ou négatifs)
            if ($fb->getComment() && strlen($fb->getComment()) > 10) {
                if ($fb->getSentiment() === 'positive' || $fb->getSentiment() === 'negative') {
                    $notableComments[] = [
                        'comment' => $fb->getComment(),
                        'sentiment' => $fb->getSentiment(),
                        'workout' => $workoutName,
                        'rating' => $fb->getRating(),
                        'user' => $fb->getUserName()
                    ];
                }
            }
        }

        // Calculer les moyennes par workout
        $workoutAverages = [];
        foreach ($workoutRatings as $name => $data) {
            $avg = array_sum($data['scores']) / $data['count'];
            $workoutAverages[] = [
                'name' => $name,
                'average' => round($avg, 2),
                'count' => $data['count']
            ];
        }

        // Trier par moyenne (les moins bien notés en premier)
        usort($workoutAverages, fn($a, $b) => $a['average'] <=> $b['average']);

        return [
            'total' => count($feedbacks),
            'averageScore' => $scoredCount > 0 ? round($totalScore / $scoredCount, 2) : 0,
            'sentiments' => $sentimentCounts,
            'workouts' => $workoutAverages,
            'poorlyRated' => array_slice(array_filter($workoutAverages, fn($w) => $w['average'] < 3), 0, 3),
            'notableComments' => array_slice($notableComments, 0, 5)
        ];
    }

    /**
     * Retourne des statistiques vides
     */
    private function getEmptyStats(): array
    {
        return [
            'total' => 0,
            'averageScore' => 0,
            'sentiments' => ['positive' => 0, 'neutral' => 0, 'negative' => 0],
            'workouts' => [],
            'poorlyRated' => [],
            'notableComments' => []
        ];
    }

    /**
     * Génère le HTML de l'email
     */
    private function generateEmailHtml(User $coach, array $stats, \DateTimeImmutable $start, \DateTimeImmutable $end, bool $isEmpty = false): string
    {
        $coachName = trim(($coach->getFirstname() ?? '') . ' ' . ($coach->getLastname() ?? '')) ?: $coach->getEmail();
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 10px 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        .stat-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .stat-box h3 { margin: 0 0 10px; color: #667eea; font-size: 16px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #333; margin: 10px 0; }
        .sentiment-bar { display: flex; height: 30px; border-radius: 5px; overflow: hidden; margin: 10px 0; }
        .sentiment-positive { background: #10b981; }
        .sentiment-neutral { background: #f59e0b; }
        .sentiment-negative { background: #ef4444; }
        .workout-item { padding: 10px; margin: 10px 0; background: #f8f9fa; border-radius: 5px; }
        .workout-item strong { color: #667eea; }
        .comment-box { background: #fff; border: 1px solid #e5e7eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .comment-positive { border-left: 4px solid #10b981; }
        .comment-negative { border-left: 4px solid #ef4444; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
        .empty-state-icon { font-size: 48px; margin-bottom: 20px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Daily Feedback Report</h1>
            <p>{$end->format('l, M d, Y')}</p>
        </div>
        
        <div class="content">
            <p>Hello <strong>{$coachName}</strong>,</p>
HTML;

        if ($isEmpty) {
            $html .= <<<HTML
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2 style="color: #667eea; margin: 0 0 10px;">No Feedbacks Today</h2>
                <p>You haven't received any feedback for this period yet.</p>
                <p style="font-size: 14px; margin-top: 20px;">Keep motivating your athletes! 💪</p>
            </div>
HTML;
        } else {
            $html .= <<<HTML
            <p>Here's your daily feedback summary:</p>
            
            <div class="stat-box">
                <h3>📈 Overall Performance</h3>
                <div class="stat-value">{$stats['averageScore']}/5</div>
                <p><strong>{$stats['total']}</strong> feedbacks received today</p>
            </div>
            
            <div class="stat-box">
                <h3>😊 Sentiment Analysis</h3>
                <div class="sentiment-bar">
HTML;

            $total = array_sum($stats['sentiments']);
            if ($total > 0) {
                foreach ($stats['sentiments'] as $sentiment => $count) {
                    $percentage = ($count / $total) * 100;
                    if ($percentage > 0) {
                        $html .= "<div class='sentiment-{$sentiment}' style='width: {$percentage}%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;'>";
                        if ($percentage > 15) {
                            $html .= "{$count}";
                        }
                        $html .= "</div>";
                    }
                }
            }

            $html .= <<<HTML
                </div>
                <p style="font-size: 14px; color: #6b7280;">
                    Positive: {$stats['sentiments']['positive']} | 
                    Neutral: {$stats['sentiments']['neutral']} | 
                    Negative: {$stats['sentiments']['negative']}
                </p>
            </div>
HTML;

            if (!empty($stats['poorlyRated'])) {
                $html .= "<div class='stat-box'><h3>⚠️ Workouts to Improve</h3>";
                foreach ($stats['poorlyRated'] as $workout) {
                    $html .= "<div class='workout-item'><strong>{$workout['name']}</strong>: {$workout['average']}/5 ({$workout['count']} feedbacks)</div>";
                }
                $html .= "</div>";
            }

            if (!empty($stats['notableComments'])) {
                $html .= "<div class='stat-box'><h3>💬 Notable Comments</h3>";
                foreach ($stats['notableComments'] as $comment) {
                    $sentimentClass = $comment['sentiment'] === 'positive' ? 'comment-positive' : 'comment-negative';
                    $emoji = $comment['sentiment'] === 'positive' ? '😊' : '😞';
                    $html .= "<div class='comment-box {$sentimentClass}'>";
                    $html .= "<p><strong>{$emoji} {$comment['user']}</strong> on <em>{$comment['workout']}</em></p>";
                    $html .= "<p>\"{$comment['comment']}\"</p>";
                    $html .= "</div>";
                }
                $html .= "</div>";
            }
        }

        $html .= <<<HTML
        </div>
        
        <div class="footer">
            <p>This is an automated daily report from FitSense</p>
            <p>Keep up the great work! 💪</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Envoie un email via SendGrid
     */
    private function sendEmail(string $to, string $subject, string $htmlContent): array
    {
        try {
            $from = new \SendGrid\Email(null, $this->fromEmail);
            $toEmail = new \SendGrid\Email(null, $to);
            $content = new \SendGrid\Content("text/html", $htmlContent);
            
            $email = new Mail($from, $subject, $toEmail, $content);

            // Configure SendGrid with SSL certificate
            $cacertPath = __DIR__ . '/../../cacert.pem';
            $options = [
                'curl' => [
                    CURLOPT_CAINFO => $cacertPath,
                ]
            ];
            
            $sendgrid = new SendGrid($this->sendgridApiKey, $options);
            $response = $sendgrid->client->mail()->send()->post($email);

            $statusCode = $response->statusCode();
            $success = $statusCode >= 200 && $statusCode < 300;
            
            return [
                'success' => $success,
                'message' => $success ? 'Email sent successfully' : 'SendGrid returned status ' . $statusCode,
                'statusCode' => $statusCode
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
