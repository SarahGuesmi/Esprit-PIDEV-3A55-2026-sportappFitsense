<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

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

    #[Route('/users', name: 'admin_users_index')]
    public function users(EntityManagerInterface $em): Response
    {
        // Get all users for the users table page
        $users = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/add-coach', name: 'admin_users_add_coach', methods: ['POST'])]
    public function addCoach(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Get form data
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $accountStatus = $request->request->get('accountStatus');

        // Create new user
        $user = new User();
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setAccountStatus($accountStatus);
        $user->setRoles(['ROLE_COACH']); // Set coach role
        $user->setDateCreation(new \DateTimeImmutable()); // Auto-set creation date

        // Hash the password
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Save to database
        $em->persist($user);
        $em->flush();

        // Redirect back to users page with success message
        $this->addFlash('success', 'Coach created successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/edit/{id}', name: 'admin_users_edit', methods: ['POST'])]
    public function editUser(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
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

        // Update user fields
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setAccountStatus($accountStatus);
        $user->setRoles([$role]); // Set the selected role

        // Only update password if a new one was provided
        if (!empty($password)) {
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
        }

        // Save changes
        $em->flush();

        // Redirect with success message
        $this->addFlash('success', 'User updated successfully!');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/users/delete/{id}', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(int $id, EntityManagerInterface $em): Response
    {
        // Find the user
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('admin_users_index');
        }

        // Remove user from database
        $em->remove($user);
        $em->flush();

        // Redirect with success message
        $this->addFlash('success', 'User deleted successfully!');
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