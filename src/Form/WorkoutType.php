<?php
// src/Form/WorkoutType.php

namespace App\Form;

use App\Entity\Workout;
use App\Entity\Exercise;
use App\Entity\ObjectifSportif;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class WorkoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // ================= Nom =================
            ->add('nom', TextType::class, [
                'label' => 'Workout Name',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Name is required']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Name must contain at least {{ limit }} characters',
                        'maxMessage' => 'Name cannot exceed {{ limit }} characters',
                    ]),
                ],
            ])

            // ================= Objectifs (OBLIGATOIRE) =================
            ->add('objectifs', EntityType::class, [
                'class' => ObjectifSportif::class,
                'choice_label' => 'name',
                'label' => 'Workout Objectives',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'by_reference' => false,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    $qb = $er->createQueryBuilder('o');
                    return $qb->where(
                        $qb->expr()->in(
                            'o.id',
                            $er->createQueryBuilder('o2')
                                ->select('MIN(o2.id)')
                                ->groupBy('o2.name')
                                ->getDQL()
                        )
                    )->orderBy('o.name', 'ASC');
                },
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'You must select at least one objective',
                    ]),
                ],
            ])

            // ================= Niveau =================
            ->add('niveau', ChoiceType::class, [
                'label' => 'Difficulty Level',
                'required' => true,
                'choices' => [
                    'Beginner' => 'BEGINNER',
                    'Intermediate' => 'INTERMEDIATE',
                    'Advanced' => 'ADVANCED'
                ],
                'placeholder' => 'Select a level',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Level is required']),
                ],
            ])

            // ================= Durée =================
            ->add('duree', IntegerType::class, [
                'label' => 'Estimated duration (minutes)',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Duration is required']),
                    new Assert\Range([
                        'min' => 5,
                        'max' => 180,
                        'notInRangeMessage' => 'Duration must be between {{ min }} and {{ max }} minutes',
                    ]),
                ],
            ])

            // ================= Description =================
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])

            // ================= Exercises =================
            ->add('exercises', EntityType::class, [
                'class' => Exercise::class,
                'choice_label' => 'nom',
                'label' => 'Exercises to include',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'by_reference' => false,
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'You must select at least one exercise',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Workout::class,
        ]);
    }
}