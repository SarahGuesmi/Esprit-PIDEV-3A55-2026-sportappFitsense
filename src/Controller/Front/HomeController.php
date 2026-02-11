<?php

namespace App\Controller\Front;

use App\Entity\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{  #[Route('/', name: 'app_home')]
 public function index(): Response
  {
    return $this->render('home/index.html.twig');
  }

  #[Route('/dashboard', name: 'dashboard')]
  public function dashboard(): Response
  {
    return $this->render('home/dashboard.html.twig');
  }

  #[Route('/coach/questionnaires', name: 'coach_questionnaires')]
  public function coachQuestionnaires(EntityManagerInterface $em, Request $request): Response
  {
    $search = $request->query->get('search', '');
    $repository = $em->getRepository(Questionnaire::class);

    if (!empty($search)) {
      $questionnaires = $repository->findByUserNameLike($search);
    } else {
      $questionnaires = $repository->findAll();
    }

    return $this->render('coach/questionnaires.html.twig', [
      'questionnaires' => $questionnaires,
      'search' => $search,
    ]);
  }
}
