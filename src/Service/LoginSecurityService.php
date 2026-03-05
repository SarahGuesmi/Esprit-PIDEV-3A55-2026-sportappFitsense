<?php

namespace App\Service;

use App\Entity\LoginAttempt;
use App\Entity\User;
use App\Enum\LoginStatus;
use App\Enum\Country;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class LoginSecurityService
{
    private const BRUTE_FORCE_THRESHOLD = 5;
    private const BRUTE_FORCE_WINDOW = '1 hour';

    private EntityManagerInterface $entityManager;
    private IPInfoService $ipInfoService;
    private LoggerInterface $logger;
    private MailerInterface $mailer;

    public function __construct(
        EntityManagerInterface $entityManager,
        IPInfoService $ipInfoService,
        LoggerInterface $logger,
        MailerInterface $mailer
    ) {
        $this->entityManager = $entityManager;
        $this->ipInfoService = $ipInfoService;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    public function recordAttempt(string $email, string $ipAddress, LoginStatus $status): LoginAttempt
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email.email' => $email]);
        $location = $this->ipInfoService->getLocationData($ipAddress);

        $attempt = new LoginAttempt();
        $attempt->setEmail($email);
        $attempt->setIpAddress($ipAddress);
        $attempt->setStatus($status);
        $attempt->setUser($user);
        $attempt->setCity($location['city'] ?? null);
        $attempt->setRegion($location['region'] ?? null);
        $attempt->setCountry(Country::tryFrom($location['country'] ?? ''));
        $attempt->setIsp($location['isp'] ?? null);

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        if ($status === LoginStatus::Success) {
            // Reset failed attempts for this IP upon successful login
            $this->clearFailedAttempts($ipAddress);
            if ($user) {
                $this->checkUnusualLocation($user, $attempt);
            }
        }

        return $attempt;
    }

    public function clearFailedAttempts(string $ipAddress): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(LoginAttempt::class, 'la')
            ->where('la.ipAddress = :ip')
            ->andWhere('la.status = :status')
            ->setParameter('ip', $ipAddress)
            ->setParameter('status', LoginStatus::Failure->value)
            ->getQuery()
            ->execute();
    }


    public function isIpBlocked(string $ipAddress): bool
    {
        $window = new \DateTimeImmutable('-' . self::BRUTE_FORCE_WINDOW);
        
        $attempts = $this->entityManager->getRepository(LoginAttempt::class)
            ->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.ipAddress = :ip')
            ->andWhere('la.status = :status')
            ->andWhere('la.timestamp >= :window')
            ->setParameter('ip', $ipAddress)
            ->setParameter('status', LoginStatus::Failure->value)
            ->setParameter('window', $window)
            ->getQuery()
            ->getSingleScalarResult();

        return $attempts >= self::BRUTE_FORCE_THRESHOLD;
    }

    private function checkUnusualLocation(User $user, LoginAttempt $currentAttempt): void
    {
        // Simple logic: if user has past successful logins from a different country
        $pastSuccess = $this->entityManager->getRepository(LoginAttempt::class)
            ->createQueryBuilder('la')
            ->where('la.user = :user')
            ->andWhere('la.status = :status')
            ->andWhere('la.id != :currentId')
            ->andWhere('la.country IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', LoginStatus::Success->value)
            ->setParameter('currentId', $currentAttempt->getId())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        if (count($pastSuccess) === 0) {
            return; // First login or first with location
        }

        // Prevent spam: don't send unusual location email if we sent one recently (e.g., last 5 mins)
        // This handles cases where multiple login successes are recorded quickly (e.g. Passkey redirects)
        $recentNotification = $this->entityManager->getRepository(LoginAttempt::class)
            ->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.user = :user')
            ->andWhere('la.status = :status')
            ->andWhere('la.timestamp >= :recent')
            ->setParameter('user', $user)
            ->setParameter('status', LoginStatus::Success->value)
            ->setParameter('recent', new \DateTimeImmutable('-5 minutes'))
            ->getQuery()
            ->getSingleScalarResult();

        if ($recentNotification > 1) { // 1 is the current attempt
            return;
        }


        $knownCountries = array_unique(array_map(fn($la) => $la->getCountry(), $pastSuccess));

        
        if (!in_array($currentAttempt->getCountry(), $knownCountries)) {
            $this->sendUnusualLocationNotification($user, $currentAttempt);
        }
    }

    private function sendUnusualLocationNotification(User $user, LoginAttempt $attempt): void
    {
        $email = (new Email())
            ->from('security@fitsense.com')
            ->to($user->getEmail())
            ->subject('New Login from Unusual Location')
            ->html(sprintf(
                '<p>Hello %s,</p><p>A new login was detected for your FitSense account from a new location:</p>
                <ul>
                    <li><strong>Location:</strong> %s, %s (%s)</li>
                    <li><strong>IP Address:</strong> %s</li>
                    <li><strong>Time:</strong> %s</li>
                </ul>
                <p>If this wasn\'t you, please change your password immediately.</p>',
                $user->getFirstname(),
                $attempt->getCity(),
                $attempt->getRegion(),
                $attempt->getCountry(),
                $attempt->getIpAddress(),
                $attempt->getTimestamp()->format('Y-m-d H:i:s')
            ));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send security notification: " . $e->getMessage());
        }
    }
}
