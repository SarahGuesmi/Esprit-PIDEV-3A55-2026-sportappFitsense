<?php

namespace App\Controller\Front;

use App\Entity\EtatMental;
use App\Form\EtatMentalType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EtatMentalController extends AbstractController
{
    #[Route('/etat-mental/new', name: 'etat_mental_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $etat = new EtatMental();
        $form = $this->createForm(EtatMentalType::class, $etat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $etat->setUser($this->getUser());
            $etat->setCreatedAt(new \DateTimeImmutable());

            $total = 
                $etat->getStressLevel() +
                $etat->getSleepQuality() +
                $etat->getMood() +
                $etat->getMotivation() +
                $etat->getMentalFatigue();

            $etat->setTotalScore($total);

            if ($total <= 10) {
                $etat->setStatus('Low Well-being');
            } elseif ($total <= 17) {
                $etat->setStatus('Moderate');
            } else {
                $etat->setStatus('Good');
            }

            $em->persist($etat);
            $em->flush();

            return $this->redirectToRoute('etat_mental_result', ['id' => $etat->getId()]);
        }

        return $this->render('etat_mental/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/etat-mental/{id}', name: 'etat_mental_result')]
    public function show(int $id, \App\Repository\EtatMentalRepository $repo): Response
    {
        $etat = $repo->find($id);

        if (!$etat) {
            throw $this->createNotFoundException('The requested evaluation does not exist (ID: ' . $id . ')');
        }

        return $this->render('etat_mental/show.html.twig', [
            'etat' => $etat
        ]);
    }

    #[Route('/etat-mental/{id}/exercises', name: 'etat_mental_exercises')]
    public function recommendations(int $id, \App\Repository\EtatMentalRepository $repo): Response
    {
        $etat = $repo->find($id);

        if (!$etat) {
            throw $this->createNotFoundException('The requested evaluation does not exist (ID: ' . $id . ')');
        }

        $score = $etat->getTotalScore();
        $recommendations = [];
        $intensity = '';
        $focus = '';
        $colorClass = '';

        if ($score <= 10) {
            // Faible bien-être -> Low intensity
            $recommendations = [
                ['name' => '🧘 Gentle Yoga', 'desc' => 'Promotes relaxation and reduces stress.', 'duration' => '20 min'],
                ['name' => '🌲 Nature Walk', 'desc' => 'Clears the mind and reconnects with calmness.', 'duration' => '30 min'],
                ['name' => '🎧 Guided Meditation', 'desc' => 'Calms the flow of thoughts and soothes.', 'duration' => '10 min'],
                ['name' => '🤸 Stretching', 'desc' => 'Releases accumulated muscle tension.', 'duration' => '15 min'],
            ];
            $intensity = 'Low';
            $focus = 'Relaxation & Recovery';
            $colorClass = 'low';
        } elseif ($score <= 17) {
            // Moyen -> Moderate intensity
            $recommendations = [
                ['name' => '🏃 Light Jogging', 'desc' => 'Stimulates endorphins without exhaustion.', 'duration' => '25 min'],
                ['name' => '🏊 Swimming', 'desc' => 'Excellent for body and mind, low impact.', 'duration' => '30 min'],
                ['name' => '🧘‍♀️ Pilates', 'desc' => 'Strengthens core and concentration.', 'duration' => '40 min'],
                ['name' => '🚴 Cycling', 'desc' => 'Moderate cardio activity to escape.', 'duration' => '45 min'],
            ];
            $intensity = 'Moderate';
            $focus = 'Balance & Vitality';
            $colorClass = 'medium';
        } else {
            // Bon -> High intensity
            $recommendations = [
                ['name' => '🔥 HIIT', 'desc' => 'Intense workout to let off steam.', 'duration' => '20 min'],
                ['name' => '🏋️ Weight Training', 'desc' => 'Builds confidence and power.', 'duration' => '45 min'],
                ['name' => '🥊 Boxing', 'desc' => 'Ideal for releasing excess energy.', 'duration' => '30 min'],
                ['name' => '⚡ Fast Running', 'desc' => 'Cardio challenge to push limits.', 'duration' => '30 min'],
            ];
            $intensity = 'High';
            $focus = 'Performance & Energy';
            $colorClass = 'good';
        }

        return $this->render('etat_mental/exercises.html.twig', [
            'etat' => $etat,
            'recommendations' => $recommendations,
            'intensity' => $intensity,
            'focus' => $focus,
            'colorClass' => $colorClass
        ]);
    }
}
