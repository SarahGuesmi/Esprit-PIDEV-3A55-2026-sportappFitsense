<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * User profile: edit personal info and photo.
     */
    #[Route('', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $baseTemplate = $this->getBaseTemplateForUser($user);

        $form = $this->createForm(UserProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $dir = $this->getParameter('profile_photo_upload_dir');
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $ext = strtolower($photoFile->getClientOriginalExtension() ?: '');
                $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? $ext : 'jpg';
                $safeName = 'user_' . $user->getId() . '_' . uniqid() . '.' . $ext;
                $photoFile->move($dir, $safeName);
                $user->setPhoto($safeName);
            }

            $this->entityManager->flush();

            // Update the user in the security token so the session has the new photo after redirect
            $token = $this->tokenStorage->getToken();
            if ($token !== null) {
                $token->setUser($user);
            }

            $this->addFlash('success', 'Your profile has been updated.');
            return $this->redirectToRoute('profile_edit');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please fix the errors below and try again.');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'base_template' => $baseTemplate,
        ]);
    }

    #[Route('/security', name: 'profile_security', methods: ['GET'])]
    public function security(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $baseTemplate = $this->getBaseTemplateForUser($user);
        $twoFactorEnabled = $user->isGoogleAuthenticatorEnabled();

        // Sort attempts by timestamp descending
        $attempts = $this->entityManager->getRepository(\App\Entity\LoginAttempt::class)
            ->findBy(['user' => $user], ['timestamp' => 'DESC'], 50);

        return $this->render('profile/security.html.twig', [
            'attempts' => $attempts,
            'two_factor_enabled' => $twoFactorEnabled,
            'base_template' => $baseTemplate,
        ]);
    }

    private function getBaseTemplateForUser(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'admin/base_admin.html.twig';
        }
        if (in_array('ROLE_COACH', $roles, true)) {
            return 'coach/base_coach.html.twig';
        }
        return 'base_user.html.twig';
    }
}
