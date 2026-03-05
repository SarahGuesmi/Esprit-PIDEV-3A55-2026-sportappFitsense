<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\LoginAttempt;
use App\Entity\Notification;
use App\Entity\ChatMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em, ChartBuilderInterface $chartBuilder): Response
    {
        $userRepo        = $em->getRepository(User::class);
        $loginRepo       = $em->getRepository(LoginAttempt::class);
        $notificationRepo = $em->getRepository(Notification::class);
        $chatRepo        = $em->getRepository(ChatMessage::class);

        // === User statistics ===
        $userCount = (int) $userRepo->count([]);

        $adminCount = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        $coachCount = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_COACH%')
            ->getQuery()
            ->getSingleScalarResult();

        // Athletes = ROLE_USER without ROLE_COACH / ROLE_ADMIN
        $athleteCount = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :roleUser')
            ->andWhere('u.roles NOT LIKE :roleCoach')
            ->andWhere('u.roles NOT LIKE :roleAdmin')
            ->setParameter('roleUser', '%ROLE_USER%')
            ->setParameter('roleCoach', '%ROLE_COACH%')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        // 2FA protection
        $twoFaEnabled = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.googleAuthenticatorSecret IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
        $twoFaDisabled = max(0, $userCount - $twoFaEnabled);

        // === Messages & notifications ===
        $totalMessages = (int) $chatRepo->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $adminTypes = ['registration', 'workout_created', 'exercise_created'];

        $totalNotifications = (int) $notificationRepo->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.type IN (:types)')
            ->setParameter('types', $adminTypes)
            ->getQuery()
            ->getSingleScalarResult();

        $unreadNotifications = (int) $notificationRepo->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.isRead = :read')
            ->andWhere('n.type IN (:types)')
            ->setParameter('read', false)
            ->setParameter('types', $adminTypes)
            ->getQuery()
            ->getSingleScalarResult();


        // === Failed logins (security) – last 30 days ===
        $fromDate = (new \DateTimeImmutable('-29 days'))->setTime(0, 0);

        // Fetch raw attempts and bucket by day in PHP to avoid DB-specific DATE() functions
        $failedAttempts = $loginRepo->createQueryBuilder('l')
            ->where('l.timestamp >= :from')
            ->andWhere('l.status = :statusFail')
            ->setParameter('from', $fromDate)
            ->setParameter('statusFail', 'failure')
            ->getQuery()
            ->getResult();

        $successAttempts = $loginRepo->createQueryBuilder('l')
            ->where('l.timestamp >= :from')
            ->andWhere('l.status = :statusOk')
            ->setParameter('from', $fromDate)
            ->setParameter('statusOk', 'success')
            ->getQuery()
            ->getResult();

        // Build unified day keys/labels for last 30 days
        $dayKeys   = [];
        $dayLabels = [];
        $today     = new \DateTimeImmutable('today');
        for ($i = 29; $i >= 0; $i--) {
            $day      = $today->modify("-{$i} days");
            $dayKeys[]   = $day->format('Y-m-d');
            $dayLabels[] = $day->format('d M');
        }

        $failedByDay  = array_fill_keys($dayKeys, 0);
        $successByDay = array_fill_keys($dayKeys, 0);

        foreach ($failedAttempts as $attempt) {
            /** @var LoginAttempt $attempt */
            $dayKey = $attempt->getTimestamp()->format('Y-m-d');
            if (array_key_exists($dayKey, $failedByDay)) {
                $failedByDay[$dayKey]++;
            }
        }
        foreach ($successAttempts as $attempt) {
            /** @var LoginAttempt $attempt */
            $dayKey = $attempt->getTimestamp()->format('Y-m-d');
            if (array_key_exists($dayKey, $successByDay)) {
                $successByDay[$dayKey]++;
            }
        }

        // === Messages per day (last 7 days) ===
        $recentMessages = $chatRepo->createQueryBuilder('m')
            ->where('m.createdAt >= :from')
            ->setParameter('from', $fromDate)
            ->getQuery()
            ->getResult();

        $messagesByDay = array_fill_keys($dayKeys, 0);
        foreach ($recentMessages as $message) {
            /** @var ChatMessage $message */
            $dayKey = $message->getCreatedAt()->format('Y-m-d');
            if (array_key_exists($dayKey, $messagesByDay)) {
                $messagesByDay[$dayKey]++;
            }
        }

        // === Notifications by type ===
        $notifTypeRows = $notificationRepo->createQueryBuilder('n')
            ->select('n.type AS type, COUNT(n.id) AS count')
            ->where('n.type IN (:types)')
            ->setParameter('types', $adminTypes)
            ->groupBy('n.type')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getArrayResult();


        $notifTypeLabels = array_map(fn ($r) => $r['type'], $notifTypeRows);
        $notifTypeData   = array_map(fn ($r) => (int) $r['count'], $notifTypeRows);

        // === Charts (Symfony UX Chart.js) ===

        // 1) Users by role
        $userRoleChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $userRoleChart->setData([
            'labels' => ['Admins', 'Coaches', 'Athletes'],
            'datasets' => [[
                'label' => 'Users',
                'data' => [$adminCount, $coachCount, $athleteCount],
                'backgroundColor' => [
                    'rgba(129, 140, 248, 0.8)', // Admins
                    'rgba(56, 189, 248, 0.8)',  // Coaches
                    'rgba(52, 211, 153, 0.8)',  // Athletes
                ],
                'borderColor' => [
                    'rgba(129, 140, 248, 1)',
                    'rgba(56, 189, 248, 1)',
                    'rgba(52, 211, 153, 1)',
                ],
                'borderWidth' => 2,
                'borderRadius' => 8,
            ]],
        ]);
        $userRoleChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['color' => 'rgba(156, 163, 175, 1)'],
                    'grid' => ['color' => 'rgba(55, 65, 81, 0.3)'],
                ],
                'x' => [
                    'ticks' => ['color' => 'rgba(156, 163, 175, 1)'],
                    'grid' => ['display' => false],
                ],
            ],
        ]);

        // 2) 2FA protection (doughnut)
        $twoFaChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $twoFaChart->setData([
            'labels' => ['2FA Enabled', 'Not Enabled'],
            'datasets' => [[
                'data' => [$twoFaEnabled, $twoFaDisabled],
                'backgroundColor' => [
                    'rgba(34, 197, 94, 0.9)',
                    'rgba(148, 163, 184, 0.5)',
                ],
                'borderColor' => [
                    'rgba(34, 197, 94, 1)',
                    'rgba(148, 163, 184, 0.8)',
                ],
                'borderWidth' => 2,
            ]],
        ]);
        $twoFaChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => ['color' => 'rgba(156, 163, 175, 1)', 'padding' => 15],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
        ]);

        // 3) Login attempts (success vs failure)
        $loginChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $loginChart->setData([
            'labels' => $dayLabels,
            'datasets' => [
                [
                    'label' => 'Successful logins',
                    'data' => array_values($successByDay),
                    'borderColor' => 'rgba(56, 189, 248, 1)',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.2)',
                    'tension' => 0.4,
                    'fill' => true,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => 'rgba(56, 189, 248, 1)',
                ],
                [
                    'label' => 'Failed logins',
                    'data' => array_values($failedByDay),
                    'borderColor' => 'rgba(248, 113, 113, 1)',
                    'backgroundColor' => 'rgba(248, 113, 113, 0.2)',
                    'tension' => 0.4,
                    'fill' => true,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => 'rgba(248, 113, 113, 1)',
                ],
            ],
        ]);
        $loginChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => ['color' => 'rgba(156, 163, 175, 1)', 'padding' => 15],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['color' => 'rgba(156, 163, 175, 1)'],
                    'grid' => ['color' => 'rgba(55, 65, 81, 0.3)'],
                ],
                'x' => [
                    'ticks' => ['color' => 'rgba(156, 163, 175, 1)'],
                    'grid' => ['color' => 'rgba(55, 65, 81, 0.3)'],
                ],
            ],
        ]);

        // 4) Messages per day
        $messageChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $messageChart->setData([
            'labels' => $dayLabels,
            'datasets' => [[
                'label' => 'Messages',
                'data' => array_values($messagesByDay),
                'backgroundColor' => 'rgba(129, 140, 248, 0.8)',
                'borderColor' => 'rgba(129, 140, 248, 1)',
                'borderWidth' => 2,
                'borderRadius' => 8,
            ]],
        ]);
        $messageChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['color' => 'rgba(156, 163, 175, 1)'],
                    'grid' => ['color' => 'rgba(55, 65, 81, 0.3)'],
                ],
                'x' => [
                    'ticks' => ['color' => 'rgba(156, 163, 175, 1)'],
                    'grid' => ['display' => false],
                ],
            ],
        ]);

        // 5) Notifications by type
        $notificationChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $notificationChart->setData([
            'labels' => $notifTypeLabels,
            'datasets' => [[
                'data' => $notifTypeData,
                'backgroundColor' => [
                    'rgba(56, 189, 248, 0.9)',
                    'rgba(139, 92, 246, 0.9)',
                    'rgba(234, 179, 8, 0.9)',
                    'rgba(248, 113, 113, 0.9)',
                    'rgba(45, 212, 191, 0.9)',
                ],
                'borderColor' => [
                    'rgba(56, 189, 248, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(234, 179, 8, 1)',
                    'rgba(248, 113, 113, 1)',
                    'rgba(45, 212, 191, 1)',
                ],
                'borderWidth' => 2,
            ]],
        ]);
        $notificationChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                    'labels' => ['color' => 'rgba(156, 163, 175, 1)', 'padding' => 15],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
        ]);

        return $this->render('admin/dashboard.html.twig', [
            'userCount'            => $userCount,
            'totalMessages'        => $totalMessages,
            'totalNotifications'   => $totalNotifications,
            'unreadNotifications'  => $unreadNotifications,
            'twoFaEnabled'         => $twoFaEnabled,
            'userRoleChart'        => $userRoleChart,
            'twoFaChart'           => $twoFaChart,
            'loginChart'           => $loginChart,
            'messageChart'         => $messageChart,
            'notificationChart'    => $notificationChart,
        ]);
    }

    #[Route('/db-fix', name: 'admin_db_fix')]
    public function dbFix(EntityManagerInterface $em): Response
    {
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        
        try {
            $schemaTool->updateSchema($metadatas, true);
            return new Response("<h1>Database Schema Fixed!</h1><p>The missing columns (including 'titre') have been added.</p><a href='/admin/dashboard'>Go back to Dashboard</a>");
        } catch (\Exception $e) {
            return new Response("<h1>Error fixing schema:</h1><pre>" . $e->getMessage() . "</pre>");
        }
    }

    #[Route('/users', name: 'admin_users_index')]
    public function users(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim($request->query->get('q', ''));
        $status = $request->query->get('status', '');
        $role = $request->query->get('role', '');

        $queryBuilder = $em->getRepository(User::class)->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC');

        // Search by name or email
        if ($q !== '') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('LOWER(u.firstname)', ':q'),
                    $queryBuilder->expr()->like('LOWER(u.lastname)', ':q'),
                    $queryBuilder->expr()->like('LOWER(u.email.email)', ':q')
                )
            )->setParameter('q', '%' . strtolower($q) . '%');
        }

        // Filter by status
        if ($status !== '' && $status !== null) {
            $queryBuilder->andWhere('u.accountStatus = :status')
                ->setParameter('status', $status);
        }

        // Filter by role
        if ($role !== '' && $role !== null) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        $users = $queryBuilder->getQuery()->getResult();

        // Handle AJAX requests - return only the table rows
        if ($request->isXmlHttpRequest() || 
            $request->headers->get('X-Requested-With') === 'XMLHttpRequest' || 
            $request->query->get('ajax') === '1') {
            
            return $this->render('admin/users/_table.html.twig', [
                'users' => $users,
            ]);
        }

        // Regular page load - return full page
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/add-coach', name: 'admin_users_add_coach', methods: ['POST'])]
    public function addCoach(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        // Get form data
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $accountStatus = $request->request->get('accountStatus');

        // Create new user entity for validation
        $user = new User();
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setAccountStatus($accountStatus);
        $user->setRoles(['ROLE_COACH']); // Set coach role
        $user->setDateCreation(new \DateTimeImmutable());
        $user->setPassword($password); // Temporarily set plain password for validation

        // Validate entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('admin_users_index');
        }

        // Check if email already exists (extra check besides validation)
        $existingUser = $em->getRepository(User::class)->findOneBy(['email.email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'This email address already exists.');
            return $this->redirectToRoute('admin_users_index');
        }

        // Hash the password after validation
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Save to database
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Coach created successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/edit/{id}', name: 'admin_users_edit', methods: ['POST'])]
    public function editUser(string $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        // Find the user
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('admin_users_index');
        }

        // Get form data
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $accountStatus = $request->request->get('accountStatus');
        $role = $request->request->get('role');

        // Store original password in case it's not changed
        $originalPassword = $user->getPassword();

        // Update user fields
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setAccountStatus($accountStatus);
        $user->setRoles([$role]);

        if (!empty($password)) {
            $user->setPassword($password); // Temporarily set for validation
        }

        // Validate entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('admin_users_index');
        }

        // Check if email already exists (and is not our current user)
        $existingUser = $em->getRepository(User::class)->findOneBy(['email.email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'This email address already exists.');
            return $this->redirectToRoute('admin_users_index');
        }

        // Hash the password if changed
        if (!empty($password)) {
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
        } else {
            $user->setPassword($originalPassword); // Restore original hashed password
        }

        // Save changes
        $em->flush();

        $this->addFlash('success', 'User updated successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/delete/{id}', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(string $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_user_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('admin_users_index');
        }

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('admin_users_index');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/bulk-delete', name: 'admin_users_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_user_bulk_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('admin_users_index');
        }

        $userIds = $request->request->all('user_ids');
        if (empty($userIds)) {
            $this->addFlash('error', 'No users selected for deletion.');
            return $this->redirectToRoute('admin_users_index');
        }

        $userRepository = $em->getRepository(User::class);
        $count = 0;

        foreach ($userIds as $id) {
            $user = $userRepository->find($id);
            if ($user) {
                $em->remove($user);
                $count++;
            }
        }

        $em->flush();

        if ($count > 0) {
            $this->addFlash('success', "$count user(s) deleted successfully!");
        } else {
            $this->addFlash('error', 'No users were deleted.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/activate/{id}', name: 'admin_users_activate')]
    public function activateUser(string $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setAccountStatus('active');
        $em->flush();

        $this->addFlash('success', 'User activated successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/deactivate/{id}', name: 'admin_users_deactivate')]
    public function deactivateUser(string $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setAccountStatus('inactive'); 
        $em->flush();

        $this->addFlash('success', 'User deactivated successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/notifications', name: 'admin_notifications')]
    public function notifications(EntityManagerInterface $em): Response
    {
        $adminTypes = ['registration', 'workout_created', 'exercise_created'];
        $notifications = $em->getRepository(\App\Entity\Notification::class)
            ->createQueryBuilder('n')
            ->where('n.type IN (:types)')
            ->setParameter('types', $adminTypes)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();


        return $this->render('admin/notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/mark-read/{id}', name: 'admin_notifications_mark_read')]
    public function markNotificationRead(string $id, EntityManagerInterface $em): Response
    {
        $notification = $em->getRepository(\App\Entity\Notification::class)->find($id);
        if ($notification) {
            $notification->setIsRead(true);
            $em->flush();
        }
        return $this->redirectToRoute('admin_notifications');
    }
}



