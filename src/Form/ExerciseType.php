<?php
// src/Form/ExerciseType.php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'exercice',
                'attr' => [
                    'placeholder' => 'Ex: Push Ups, Jumping Jacks...',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom est obligatoire'
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'exercice',
                'choices' => [
                    'Cardio' => 'Cardio',
                    'Force' => 'Force',
                    'Flexibilité' => 'Flexibilité',
                    'Équilibre' => 'Équilibre',
                    'HIIT' => 'HIIT',
                    'Endurance' => 'Endurance',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un type',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le type est obligatoire'
                    ])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en secondes)',
                'attr' => [
                    'placeholder' => 'Ex: 45, 60, 120...',
                    'class' => 'form-control',
                    'min' => 1
                ],
                'help' => 'Durée de l\'exercice en secondes',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La durée est obligatoire'
                    ]),
                    new Assert\Positive([
                        'message' => 'La durée doit être positive'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez l\'exercice, les consignes d\'exécution...',
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
        ]);
    }
}