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
    string $id,
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

    // ✅ CSRF (une seule fois) + JSON si AJAX
    $token = (string) $request->request->get('_token', '');
    if (!$this->isCsrfTokenValid('upd_recipe_'.$id, $token)) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'errors' => ['global' => 'Invalid CSRF token. Refresh the page.']
            ], 403);
        }
        throw $this->createAccessDeniedException('Invalid CSRF token');
    }

    // ✅ Update fields
    $recette->setTitle((string) $request->request->get('title', ''));
    $recette->setDescription((string) $request->request->get('description', ''));
    $recette->setIngredients((string) $request->request->get('ingredients', ''));
    $recette->setPreparation((string) $request->request->get('preparation', ''));

    $kcal = $request->request->get('kcal');
    $proteins = $request->request->get('proteins');

    $recette->setKcal($kcal !== '' && $kcal !== null ? (int) $kcal : null);
    $recette->setProteins($proteins !== '' && $proteins !== null ? (int) $proteins : null);

    $recette->setTypeMeal((string) $request->request->get('typeMeal', 'BREAKFAST'));

    // ✅ objectifs[]
    $objectifs = $request->request->all('objectifs');
    $recette->setObjectifs($objectifs);

    // ✅ Image
    $imageFile = $request->files->get('imageFile');
    if ($imageFile) {
        $newFilename = uniqid('recipe_') . '.' . ($imageFile->guessExtension() ?: 'jpg');
        $imageFile->move($this->getParameter('recipes_upload_dir'), $newFilename);
        $recette->setImage($newFilename);
    }

    // ✅ Validation
    $errors = $validator->validate($recette);
    if (count($errors) > 0) {
        if ($request->isXmlHttpRequest()) {
            $errorMap = [];
            foreach ($errors as $error) {
                $errorMap[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['success' => false, 'errors' => $errorMap], 400);
        }
        return $this->redirectToRoute('coach_recette');
    }

    $em->flush();

    if ($request->isXmlHttpRequest()) {
        return $this->json(['success' => true, 'message' => 'Recipe updated ✅']);
    }

    return $this->redirectToRoute('coach_recette');
}

   // ✅ DELETE (AJAX + normal)
#[Route('/coach/recette/{id}/delete', name: 'coach_recette_delete', methods: ['POST'])]
public function delete(
    string $id,
    Request $request,
    RecetteNutritionnelleRepository $repo,
    EntityManagerInterface $em
): Response {
    $coach = $this->getUser();
    if (!$coach) {
        // AJAX => JSON, sinon exception
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'errors' => ['global' => 'Not authenticated']], 403);
        }
        throw $this->createAccessDeniedException('Not authenticated');
    }

    $recette = $repo->find($id);
    if (!$recette) {
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'errors' => ['global' => 'Recipe not found']], 404);
        }
        throw $this->createNotFoundException('Recipe not found');
    }

    // ✅ Owner check
    if (!$recette->getCoach() || $recette->getCoach()->getId() !== $coach->getId()) {
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'errors' => ['global' => 'Not your recipe']], 403);
        }
        throw $this->createAccessDeniedException('Not your recipe');
    }

    // ✅ CSRF
    $token = (string) $request->request->get('_token', '');
    if (!$this->isCsrfTokenValid('del_recipe_'.$id, $token)) {
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'errors' => ['global' => 'Invalid CSRF token']], 403);
        }
        throw $this->createAccessDeniedException('Invalid CSRF token');
    }

    // ✅ Delete image file if exists
    if ($recette->getImage()) {
        $imagePath = $this->getParameter('recipes_upload_dir') . '/' . $recette->getImage();
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // ✅ Delete
    $em->remove($recette);
    $em->flush();


    // ✅ AJAX => JSON
    if ($request->isXmlHttpRequest()) {
        return $this->json(['success' => true, 'message' => 'Recipe deleted 🗑️']);
    }

    // ✅ Normal submit (non-AJAX)
    $this->addFlash('success', 'Recipe deleted 🗑️');
    return $this->redirectToRoute('coach_recette');
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
        string $userId,
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
