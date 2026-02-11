<?php

namespace App\Form;

use App\Entity\RecetteNutritionnelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class RecetteNutritionnelleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Recipe name',
                'required' => true,
                'attr' => ['placeholder' => 'Ex: Chicken Salad'],
            ])

            ->add('typeMeal', ChoiceType::class, [
                'label' => 'Meal type',
                'choices' => [
                    'Breakfast' => 'BREAKFAST',
                    'Lunch'     => 'LUNCH',
                    'Dinner'    => 'DINNER',
                    'Snack'     => 'SNACK',
                ],
            ])

            ->add('kcal', IntegerType::class, [
                'label' => 'Calories (kcal)',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => 'Ex: 450'],
            ])

            ->add('proteins', IntegerType::class, [
                'label' => 'Proteins (g)',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => 'Ex: 35'],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['rows' => 3],
            ])

            ->add('ingredients', TextareaType::class, [
                'label' => 'Ingredients',
                'required' => true,
                'attr' => ['rows' => 4],
            ])

            ->add('preparation', TextareaType::class, [
                'label' => 'Preparation',
                'required' => true,
                'attr' => ['rows' => 5],
            ])

           ->add('imageFile', FileType::class, [
            'label' => 'Image',
            'mapped' => false,
            'required' => false, // ✅ IMPORTANT
            'constraints' => [
            new File([
            'maxSize' => '3M',
            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
        ])
    ],
])


            ->add('objectifs', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Weight Loss' => 'WEIGHT_LOSS',
                    'Muscle Gain' => 'MUSCLE_GAIN',
                    'Endurance'   => 'ENDURANCE',
                    'Well-being'  => 'WELL_BEING',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecetteNutritionnelle::class,
        ]);
    }
}
