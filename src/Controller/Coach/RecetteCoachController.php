<?php

namespace App\Controller\Coach;

use App\Entity\RecetteNutritionnelle;
use App\Form\RecetteNutritionnelleType;
use App\Repository\RecetteConsommeeRepository;
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

        $q = trim((string) $request->query->get('q', ''));
        $objectif = $request->query->get('objectif');
        $kcal = $request->query->get('kcal');
        $proteins = $request->query->get('proteins');

        $kcal = ($kcal !== null && $kcal !== '') ? (int) $kcal : null;
        $proteins = ($proteins !== null && $proteins !== '') ? (int) $proteins : null;

        $recette = new RecetteNutritionnelle();
        $form = $this->createForm(RecetteNutritionnelleType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $recette->setCoach($coach);

                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $newFilename = uniqid('recipe_') . '.' . ($imageFile->guessExtension() ?: 'jpg');
                    $imageFile->move($this->getParameter('recipes_upload_dir'), $newFilename);
                    $recette->setImage($newFilename);
                }

                $em->persist($recette);
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true, 'message' => 'Recipe added successfully ✅']);
                }

                $this->addFlash('success', 'Recipe added successfully ✅');
                return $this->redirectToRoute('coach_recette', [
                    'q' => $q,
                    'objectif' => $objectif,
                    'kcal' => $kcal,
                    'proteins' => $proteins,
                ]);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'errors' => $this->getFormErrors($form)
                ], 400);
            }
        }

        $recipes = $repo->searchForAll($q ?: null, $kcal, $proteins);

        return $this->render('coach_recette/recette.html.twig', [
            'form' => $form->createView(),
            'recipes' => $recipes,
            'q' => $q,
            'objectif' => $objectif,
            'kcal' => $kcal,
            'proteins' => $proteins,
        ]);
    }

    // ✅ ADD QUICK (Modal)
    #[Route('/coach/recipe/add', name: 'coach_recipe_add', methods: ['POST'])]
    public function addQuick(
        Request $request,
        EntityManagerInterface $em,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ): Response {
        $coach = $this->getUser();
        if (!$coach) {
            throw $this->createAccessDeniedException();
        }

        $recette = new RecetteNutritionnelle();
        $recette->setCoach($coach);
        $recette->setTitle((string) $request->request->get('name'));
        $recette->setDescription((string) $request->request->get('description'));
        
        $kcal = $request->request->get('calories');
        $proteins = $request->request->get('protein');
        
        $recette->setKcal($kcal !== '' && $kcal !== null ? (int)$kcal : null);
        $recette->setProteins($proteins !== '' && $proteins !== null ? (int)$proteins : null);
        
        $recette->setIngredients((string) $request->request->get('ingredients'));
        $recette->setPreparation((string) $request->request->get('instructions'));

        // Set defaults for required fields not in the simple form
        $recette->setTypeMeal('LUNCH'); 
        $recette->setObjectifs(['WELL_BEING']);

        $errors = $validator->validate($recette);
        if (count($errors) > 0) {
            if ($request->isXmlHttpRequest()) {
                $errorMap = [];
                foreach ($errors as $error) {
                    $errorMap[$error->getPropertyPath()] = $error->getMessage();
                }
                return $this->json(['success' => false, 'errors' => $errorMap], 400);
            }
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('coach_recette');
        }

        $em->persist($recette);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'message' => 'Recipe added successfully (Quick Add) ✅']);
        }

        $this->addFlash('success', 'Recipe added successfully (Quick Add) ✅');

        return $this->redirectToRoute('coach_recette');
    }

    // ✅ UPDATE
    #[Route('/coach/recette/{id}/update', name: 'coach_recette_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        RecetteNutritionnelleRepository $repo,
        EntityManagerInterface $em,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
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
        
        $objectifs = $request->request->all('objectifs');
        $recette->setObjectifs($objectifs);

        $errors = $validator->validate($recette);
        if (count($errors) > 0) {
            if ($request->isXmlHttpRequest()) {
                $errorMap = [];
                foreach ($errors as $error) {
                    $errorMap[$error->getPropertyPath()] = $error->getMessage();
                }
                return $this->json(['success' => false, 'errors' => $errorMap], 400);
            }
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('coach_recette');
        }

        $imageFile = $request->files->get('imageFile');
        if ($imageFile) {
            $newFilename = uniqid('recipe_') . '.' . ($imageFile->guessExtension() ?: 'jpg');
            $imageFile->move($this->getParameter('recipes_upload_dir'), $newFilename);
            $recette->setImage($newFilename);
        }

        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'message' => 'Recipe updated ✅']);
        }

        $this->addFlash('success', 'Recipe updated ✅');

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

    #[Route('/coach/nutrition/consumption', name: 'coach_nutrition_consumption')]
    public function consumptionLogs(RecetteConsommeeRepository $repo): Response
    {
        return $this->render('coach_recette/consumption_history.html.twig', [
            'stats' => $repo->findUserConsumptionStats(),
        ]);
    }

    #[Route('/coach/nutrition/consumption/user/{userId}', name: 'coach_user_consumption_details')]
    public function userConsumptionDetails(
        int $userId,
        EntityManagerInterface $em,
        RecetteConsommeeRepository $logRepo
    ): Response {
        $user = $em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('coach_recette/user_consumption_details.html.twig', [
            'user' => $user,
            'logs' => $logRepo->findByUserOrderedByDate($userId),
        ]);
    }

    private function getFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            $errors['global'] = $error->getMessage();
        }
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errors[$child->getName()] = $error->getMessage();
                }
            }
        }
        return $errors;
    }
}
