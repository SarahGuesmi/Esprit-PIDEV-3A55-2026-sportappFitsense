<?php
namespace App\Form;

use App\Entity\Questionnaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, TextareaType, SubmitType, ButtonType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuestionnaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1️⃣ SATISFACTION GLOBALE
            ->add('noteGlobale', ChoiceType::class, [
                'label' => '⭐ Comment évalues-tu ta séance d’aujourd’hui ?',
                'choices' => [
                    '1 Très mauvaise' => 1,
                    '2 Mauvaise' => 2,
                    '3 Moyenne' => 3,
                    '4 Bonne' => 4,
                    '5 Excellente' => 5
                ],
                'expanded' => true,
                'required' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
                'constraints' => [new NotBlank()],
            ])
            ->add('satisfaction', ChoiceType::class, [
                'label' => '😌 Ton niveau de satisfaction après la séance :',
                'choices' => [
                    'Très faible' => 1,
                    'Faible' => 2,
                    'Moyen' => 3,
                    'Élevé' => 4,
                    'Très élevé' => 5
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])

            // 2️⃣ INTENSITÉ & CONTENU
            ->add('intensite', ChoiceType::class, [
                'label' => '🔥 L’intensité de la séance était :',
                'choices' => ['Trop faible' => 'trop_faible', 'Adaptée' => 'adaptee', 'Trop intense' => 'trop_intense'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])
            ->add('exercicesCompris', ChoiceType::class, [
                'label' => '🧠 Les exercices étaient faciles à comprendre ?',
                'choices' => ['Non' => 'non', 'Moyennement' => 'moyen', 'Oui' => 'oui'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])
            ->add('duree', ChoiceType::class, [
                'label' => '⏱️ La durée de la séance était :',
                'choices' => ['Trop courte' => 'trop_courte', 'Parfaite' => 'parfaite', 'Trop longue' => 'trop_longue'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])

            // 4️⃣ RESSENTI PHYSIQUE/MENTAL
            ->add('ressentiPhysique', ChoiceType::class, [
                'label' => '💪 Comment te sens-tu physiquement après ?',
                'choices' => ['Fatigué' => 'fatigue', 'Bien' => 'bien', 'Très en forme' => 'forme'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])
            ->add('stress', ChoiceType::class, [
                'label' => '🧘 Ton niveau de stress après la séance :',
                'choices' => ['Plus élevé' => 'plus', 'Identique' => 'idem', 'Plus bas' => 'moins'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])
            ->add('motivation', ChoiceType::class, [
                'label' => '⚡ Te sens-tu plus motivé(e) après ?',
                'choices' => ['Pas du tout' => 'non', 'Un peu' => 'peu', 'Oui' => 'oui'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])

            // 5️⃣ PROGRESSION
            ->add('progression', ChoiceType::class, [
                'label' => '📈 Progression par rapport aux séances précédentes ?',
                'choices' => ['Non' => 'non', 'Pas sûr' => 'pas_sur', 'Oui' => 'oui'],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])
            ->add('rapprocheObjectifs', ChoiceType::class, [
                'label' => '🏋️ T’a aidé vers tes objectifs ?',
                'choices' => [
                    'Pas du tout' => 1,
                    'Peu' => 2,
                    'Moyennement' => 3,
                    'Beaucoup' => 4,
                    'Totalement' => 5
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-4 text-center'],
                'attr' => ['class' => 'choice-group-spaced mb-5'],
                'choice_attr' => fn() => ['class' => 'form-check me-4 mb-3'],
            ])

            ->add('commentaire', TextareaType::class, [
                'label' => '💬 Commentaires libres',
                'required' => false,
                'attr' => ['rows' => 6, 'class' => 'form-control form-control-lg', 'style' => 'min-height: 200px;'],
                'label_attr' => ['class' => 'form-label fw-bold fs-5 mb-3'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => '✅ Terminer & Analyser',
                'attr' => ['class' => 'btn btn-primary btn-lg px-5 mt-4']
            ])
            ->add('cancel', ButtonType::class, [
                'label' => '❌ Annuler',
                'attr' => ['class' => 'btn btn-secondary btn-lg px-5 mt-4 ms-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Questionnaire::class,
        ]);
    }
}
