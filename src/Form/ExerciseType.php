<?php

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
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Exercise Name',
                'attr' => [
                    'placeholder' => 'e.g. Push Ups, Jumping Jacks...',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Name is required']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'Name cannot exceed {{ limit }} characters'])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Exercise Type',
                'choices' => [
                    'Cardio'       => 'Cardio',
                    'Strength'     => 'Strength',
                    'Flexibility'  => 'Flexibility',
                    'Balance'      => 'Balance',
                    'HIIT'         => 'HIIT',
                    'Endurance'    => 'Endurance',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Select a type',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Type is required'])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Duration (seconds)',
                'attr' => [
                    'placeholder' => 'e.g. 45, 60, 120...',
                    'class' => 'form-control',
                    'min' => 1
                ],
                'help' => 'Exercise duration in seconds',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Duration is required']),
                    new Assert\Positive(['message' => 'Duration must be a positive number'])
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'required'     => false,
                'allow_delete' => true,
                'download_uri' => false,
                'label'        => 'Exercise Image',
                'attr'         => ['class' => 'block w-full text-sm text-gray-400']
            ])

            ->add('youtubeVideoId', HiddenType::class, [
                    'required' => false,
                    'label'    => false,
            ])
            ->add('description', HiddenType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
        ]);
    }
}