<?php

namespace App\Form;

use App\Entity\EtatMental;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EtatMentalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mood', ChoiceType::class, [
                'label' => '1️⃣ How have you been feeling emotionally lately?',
                'choices' => [
                    'Very bad (sad, empty)' => 1,
                    'Bad' => 2,
                    'Neutral' => 3,
                    'Good' => 4,
                    'Very good' => 5,
                ],
                'placeholder' => 'Choose an answer...',
            ])
            ->add('stressLevel', ChoiceType::class, [
                'label' => '2️⃣ How often do you feel stressed or anxious?',
                'choices' => [
                    'All the time' => 1,
                    'Often' => 2,
                    'Sometimes' => 3,
                    'Rarely' => 4,
                    'Never' => 5,
                ],
                'placeholder' => 'Choose an answer...',
            ])
            ->add('sleepQuality', ChoiceType::class, [
                'label' => '3️⃣ How is your sleep quality recently?',
                'choices' => [
                    'Very poor (difficulty sleeping)' => 1,
                    'Poor' => 2,
                    'Average' => 3,
                    'Good' => 4,
                    'Very good' => 5,
                ],
                'placeholder' => 'Choose an answer...',
            ])
            ->add('motivation', ChoiceType::class, [
                'label' => '4️⃣ Do you feel motivated to do your daily activities?',
                'choices' => [
                    'No motivation' => 1,
                    'Low motivation' => 2,
                    'Moderately motivated' => 3,
                    'Motivated' => 4,
                    'Very motivated' => 5,
                ],
                'placeholder' => 'Choose an answer...',
            ])
            ->add('mentalFatigue', ChoiceType::class, [
                'label' => '5️⃣ How mentally tired do you feel?',
                'choices' => [
                    'Extremely tired' => 1,
                    'Very tired' => 2,
                    'Moderately tired' => 3,
                    'Slightly tired' => 4,
                    'Not tired at all' => 5,
                ],
                'placeholder' => 'Choose an answer...',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EtatMental::class,
        ]);
    }
}
