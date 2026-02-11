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
}