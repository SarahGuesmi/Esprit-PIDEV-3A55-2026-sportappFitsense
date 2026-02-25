<?php

namespace App\Controller\Front;

use App\Entity\ProfilePhysique;
use App\Entity\ObjectifSportif;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ProfileSetupController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/profile/setup/height', name: 'profile_setup_height', methods: ['GET', 'POST'])]
    public function height(Request $request): Response
    {
        $session = $request->getSession();
        $profileData = $session->get('profile_data', []);

        $defaultHeight = $profileData['height'] ?? 170;
        $defaultUnit = $profileData['unit_height'] ?? 'cm';

        $form = $this->createFormBuilder($profileData)
            ->add('height', NumberType::class, [
                'data' => $defaultHeight,
                'required' => true,
            ])
            ->add('unit_height', ChoiceType::class, [
                'choices' => ['cm' => 'cm', 'ft' => 'ft'],
                'data' => $defaultUnit,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // Convert to cm if in ft
            if ($data['unit_height'] === 'ft') {
                $data['height'] *= 30.48; // ft to cm
            }
            $profileData['height'] = $data['height'];
            $profileData['unit_height'] = $data['unit_height'];
            $session->set('profile_data', $profileData);
            return $this->redirectToRoute('profile_setup_weight');
        }

        return $this->render('profile_setup/height.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/profile/setup/weight', name: 'profile_setup_weight', methods: ['GET', 'POST'])]
    public function weight(Request $request): Response
    {
        $session = $request->getSession();
        $profileData = $session->get('profile_data', []);

        $defaultWeight = $profileData['weight'] ?? 75;
        $defaultUnit = $profileData['unit_weight'] ?? 'kg';

        $form = $this->createFormBuilder($profileData)
            ->add('weight', NumberType::class, [
                'data' => $defaultWeight,
                'required' => true,
            ])
            ->add('unit_weight', ChoiceType::class, [
                'choices' => ['kg' => 'kg', 'lbs' => 'lbs'],
                'data' => $defaultUnit,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // Convert to kg if in lbs
            if ($data['unit_weight'] === 'lbs') {
                $data['weight'] *= 0.453592; // lbs to kg
            }
            $profileData['weight'] = $data['weight'];
            $profileData['unit_weight'] = $data['unit_weight'];
            $session->set('profile_data', $profileData);
            return $this->redirectToRoute('profile_setup_gender');
        }

        return $this->render('profile_setup/weight.html.twig', ['form' => $form->createView()]);
    }

   #[Route('/profile/setup/gender', name: 'profile_setup_gender', methods: ['GET', 'POST'])]
public function gender(Request $request): Response
{
    $session = $request->getSession();
    $profileData = $session->get('profile_data', []);

    // Si POST
    if ($request->isMethod('POST')) {
        $gender = $request->request->get('gender');
        if (!$gender) {
            $error = 'Please select your gender.';
            return $this->render('profile_setup/gender.html.twig', [
                'error' => $error,
                'selected' => null,
            ]);
        }

        $profileData['gender'] = $gender;
        $session->set('profile_data', $profileData);
        return $this->redirectToRoute('profile_setup_objectives');
    }

    // GET → afficher la page
    return $this->render('profile_setup/gender.html.twig', [
        'selected' => $profileData['gender'] ?? null,
        'error' => null,
    ]);
}

    #[Route('/profile/setup/objectives', name: 'profile_setup_objectives', methods: ['GET', 'POST'])]
    public function objectives(Request $request): Response
    {
        $session = $request->getSession();
        $profileData = $session->get('profile_data', []);

        $form = $this->createFormBuilder($profileData)
            ->add('objectives', ChoiceType::class, [
                'choices' => [
                    'Weight Loss' => 'Weight Loss',
                    'Muscle Gain' => 'Muscle Gain',
                    'Endurance' => 'Endurance',
                    'Well-being' => 'Well-being',
                ],
                'expanded' => true,
                'multiple' => true,
                'data' => $profileData['objectives'] ?? [],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $profileData['objectives'] = $data['objectives'];
            $session->set('profile_data', $profileData);

            // Persist the profile
            $user = $this->getUser();
            $profile = new ProfilePhysique();
            $profile->setUser($user);
            $profile->setHeight($profileData['height']);
            $profile->setWeight($profileData['weight']);
            $profile->setGender($profileData['gender']);

            foreach ($profileData['objectives'] as $objName) {
                $objectif = new ObjectifSportif();
                $objectif->setName($objName);
                $profile->addObjectif($objectif);
            }

            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            $session->remove('profile_data');

            return $this->redirectToRoute('profile_setup_avatar');
        }

        return $this->render('profile_setup/objectives.html.twig', ['form' => $form->createView()]);
    }
}