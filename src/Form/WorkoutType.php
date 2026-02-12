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
                'label' => 'Nom du Workout',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            // ================= Objectifs (OBLIGATOIRE) =================
            ->add('objectifs', EntityType::class, [
                'class' => ObjectifSportif::class,
                'choice_label' => 'name',
                'label' => 'Objectifs du Workout',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'by_reference' => false,
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Vous devez sélectionner au moins un objectif',
                    ]),
                ],
            ])

            // ================= Niveau =================
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau de difficulté',
                'required' => true,
                'choices' => [
                    'Débutant' => 'BEGINNER',
                    'Intermédiaire' => 'INTERMEDIATE',
                    'Avancé' => 'ADVANCED'
                ],
                'placeholder' => 'Sélectionnez un niveau',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le niveau est obligatoire']),
                ],
            ])

            // ================= Durée =================
            ->add('duree', IntegerType::class, [
                'label' => 'Durée estimée (minutes)',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est obligatoire']),
                    new Assert\Range([
                        'min' => 5,
                        'max' => 180,
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} minutes',
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
                'label' => 'Exercices à inclure',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'by_reference' => false,
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Vous devez sélectionner au moins un exercice',
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