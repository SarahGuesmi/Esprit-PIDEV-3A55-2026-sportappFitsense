<?php
namespace App\Controller\Front;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
   #[Route('/admin', name: 'admin_dashboard')]
public function index(): Response
{
    return $this->render('admin/base_admin.html.twig'); // Ou une page spécifique
}
    }

