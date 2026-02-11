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
            // Nom du Workout
            ->add('nom', TextType::class, [
                'label' => 'Nom du Workout',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: HIIT Full Body'
                ],
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
            
            // Niveau (Débutant, Intermédiaire, Avancé)
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau de difficulté',
                'required' => true,
                'choices' => [
                    'Débutant' => 'BEGINNER',
                    'Intermédiaire' => 'INTERMEDIATE',
                    'Avancé' => 'ADVANCED'
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un niveau',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le niveau est obligatoire']),
                ],
            ])
            
            // Durée estimée
            ->add('duree', IntegerType::class, [
                'label' => 'Durée estimée (minutes)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '45',
                    'min' => 5,
                    'max' => 180
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est obligatoire']),
                    new Assert\Range([
                        'min' => 5,
                        'max' => 180,
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} minutes',
                    ]),
                ],
            ])
            
            // Description
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le workout, ses bénéfices, ses particularités...'
                ]
            ])
            
            // ✅ CORRECTION : objectifs au lieu de objectifsSportifs
            ->add('objectifs', EntityType::class, [
                'class' => ObjectifSportif::class,
                'choice_label' => 'name',
                'label' => 'Objectifs du Workout',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => ['class' => 'objectifs-list'],
                'help' => 'Sélectionnez un ou plusieurs objectifs pour ce workout'
            ])
            
            // Exercices à inclure
            // ✅ OPTION 1 : Affichage simple (juste le nom)
            ->add('exercises', EntityType::class, [
                'class' => Exercise::class,
                'choice_label' => 'nom',  // Simplement le nom de l'exercice
                'label' => 'Exercices à inclure',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 8
                ],
                'by_reference' => false,
                'help' => 'Maintenez Ctrl (Cmd sur Mac) pour sélectionner plusieurs exercices',
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Vous devez sélectionner au moins un exercice',
                    ]),
                ],
            ])
            
            /* 
            // ✅ OPTION 2 : Si vous voulez ajouter plus d'infos
            // Décommentez ce bloc et commentez l'option 1 ci-dessus
            // Adaptez les noms de méthodes selon votre entité Exercise
            
            ->add('exercises', EntityType::class, [
                'class' => Exercise::class,
                'choice_label' => function (Exercise $exercise) {
                    // Adaptez selon les méthodes disponibles dans votre entité Exercise
                    // Exemples possibles :
                    return $exercise->getNom();
                    // return $exercise->getNom() . ' (' . $exercise->getDifficulte() . ')';
                    // return $exercise->getNom() . ' - ' . $exercise->getEquipement();
                },
                'label' => 'Exercices à inclure',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 8
                ],
                'by_reference' => false,
                'help' => 'Maintenez Ctrl (Cmd sur Mac) pour sélectionner plusieurs exercices',
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Vous devez sélectionner au moins un exercice',
                    ]),
                ],
            ])
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Workout::class,
        ]);
    }
}