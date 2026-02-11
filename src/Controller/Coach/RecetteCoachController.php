<?php

namespace App\Controller\Coach;

use App\Entity\RecetteNutritionnelle;
use App\Form\RecetteNutritionnelleType;
use App\Repository\RecetteNutritionnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COACH')]
class RecetteCoachController extends AbstractController
{
    #[Route('/coach/recette/', name: 'coach_recette')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        RecetteNutritionnelleRepository $repo
    ): Response {
        $coach = $this->getUser();
        if (!$coach) {
            throw $this->createAccessDeniedException();
        }

        // ✅ Filters (GET)
        $q = trim((string) $request->query->get('q', ''));

        // ✅ IMPORTANT: définir $objectif pour éviter Undefined variable
        $objectif = $request->query->get('objectif'); // peut être null

        $kcal = $request->query->get('kcal');
        $proteins = $request->query->get('proteins');

        $kcal = ($kcal !== null && $kcal !== '') ? (int) $kcal : null;
        $proteins = ($proteins !== null && $proteins !== '') ? (int) $proteins : null;

        // ✅ Create form (POST)
        $recette = new RecetteNutritionnelle();
        $form = $this->createForm(RecetteNutritionnelleType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recette->setCoach($coach);

            // ✅ upload image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid('recipe_') . '.' . ($imageFile->guessExtension() ?: 'jpg');
                $imageFile->move($this->getParameter('recipes_upload_dir'), $newFilename);
                $recette->setImage($newFilename);
            }

            $em->persist($recette);
            $em->flush();

            $this->addFlash('success', 'Recipe added successfully ✅');

            // ✅ garder les filtres après ajout
            return $this->redirectToRoute('coach_recette', [
                'q' => $q,
                'objectif' => $objectif,
                'kcal' => $kcal,
                'proteins' => $proteins,
            ]);
        }

        // ✅ search + filters results
        // ⚠️ Ici, adapte selon ton repository (voir note dessous)
        $recipes = $repo->searchForCoach($coach, $q ?: null, $objectif ?: null, $kcal, $proteins);

        return $this->render('coach_recette/recette.html.twig', [
            'form' => $form->createView(),
            'recipes' => $recipes,

            'q' => $q,
            'objectif' => $objectif,
            'kcal' => $kcal,
            'proteins' => $proteins,
        ]);
    }

    // ✅ UPDATE
    #[Route('/coach/recette/{id}/update', name: 'coach_recette_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        RecetteNutritionnelleRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $coach = $this->getUser();
        if (!$coach) {
            throw $this->createAccessDeniedException();
        }

        $recette = $repo->find($id);
        if (!$recette) {
            throw $this->createNotFoundException('Recette not found');
        }

        if ($recette->getCoach()->getId() !== $coach->getId()) {
            throw $this->createAccessDeniedException('Not your recipe');
        }

        if (!$this->isCsrfTokenValid('upd_recipe_'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $recette->setTitle((string) $request->request->get('title', ''));
        $recette->setDescription((string) $request->request->get('description', ''));
        $recette->setIngredients((string) $request->request->get('ingredients', ''));
        $recette->setPreparation((string) $request->request->get('preparation', ''));

        $kcal = $request->request->get('kcal');
        $proteins = $request->request->get('proteins');

        $recette->setKcal($kcal !== '' && $kcal !== null ? (int) $kcal : null);
        $recette->setProteins($proteins !== '' && $proteins !== null ? (int) $proteins : null);

        $recette->setTypeMeal((string) $request->request->get('typeMeal', ''));

        $imageFile = $request->files->get('imageFile');
        if ($imageFile) {
            $newFilename = uniqid('recipe_') . '.' . ($imageFile->guessExtension() ?: 'jpg');
            $imageFile->move($this->getParameter('recipes_upload_dir'), $newFilename);
            $recette->setImage($newFilename);
        }

        $em->flush();
        $this->addFlash('success', 'Recipe updated ✅');

        // ✅ garder les filtres après update (ils viennent du POST hidden inputs)
        return $this->redirectToRoute('coach_recette', [
            'q' => $request->request->get('q'),
            'objectif' => $request->request->get('objectif'),
            'kcal' => $request->request->get('kcalFilter'),
            'proteins' => $request->request->get('proteinsFilter'),
        ]);
    }

    // ✅ DELETE
    #[Route('/coach/recette/{id}/delete', name: 'coach_recette_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        RecetteNutritionnelleRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $coach = $this->getUser();
        if (!$coach) {
            throw $this->createAccessDeniedException();
        }

        $recette = $repo->find($id);
        if (!$recette) {
            throw $this->createNotFoundException('Recette not found');
        }

        if ($recette->getCoach()->getId() !== $coach->getId()) {
            throw $this->createAccessDeniedException('Not your recipe');
        }

        if (!$this->isCsrfTokenValid('del_recipe_'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $em->remove($recette);
        $em->flush();

        $this->addFlash('success', 'Recipe deleted 🗑️');

        // ✅ garder les filtres après delete (POST hidden inputs)
        return $this->redirectToRoute('coach_recette', [
            'q' => $request->request->get('q'),
            'objectif' => $request->request->get('objectif'),
            'kcal' => $request->request->get('kcalFilter'),
            'proteins' => $request->request->get('proteinsFilter'),
        ]);
    }
}
