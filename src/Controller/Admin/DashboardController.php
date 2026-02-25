<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        // Get total user count for dashboard stats
        $userCount = $em->getRepository(User::class)->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $userCount,
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
        $q = $request->query->get('q', '');
        $status = $request->query->get('status', '');
        $role = $request->query->get('role', '');

        $queryBuilder = $em->getRepository(User::class)->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC');

        if (!empty($q)) {
            $queryBuilder->andWhere('u.firstname LIKE :q OR u.lastname LIKE :q OR u.email LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($status)) {
            $queryBuilder->andWhere('u.accountStatus = :status')
                ->setParameter('status', $status);
        }

        if (!empty($role)) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        $users = $queryBuilder->getQuery()->getResult();

        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest' || $request->query->get('ajax')) {
            return $this->render('admin/users/_table.html.twig', [
                'users' => $users,
            ]);
        }

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
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
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
    public function editUser(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
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
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
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
    public function deleteUser(int $id, Request $request, EntityManagerInterface $em): Response
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
    public function activateUser(int $id, EntityManagerInterface $em): Response
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
    public function deactivateUser(int $id, EntityManagerInterface $em): Response
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
        $notifications = $em->getRepository(\App\Entity\Notification::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/mark-read/{id}', name: 'admin_notifications_mark_read')]
    public function markNotificationRead(int $id, EntityManagerInterface $em): Response
    {
        $notification = $em->getRepository(\App\Entity\Notification::class)->find($id);
        if ($notification) {
            $notification->setIsRead(true);
            $em->flush();
        }
        return $this->redirectToRoute('admin_notifications');
    }
}