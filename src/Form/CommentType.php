<?php

namespace App\Form;

use App\Entity\Comment;
use App\Entity\Film;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class CommentType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Titre',
                    'maxlength' => 64,
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Contenu',
                    'maxlength' => 500,
                    'minlength' => 64,
                    'rows' => 4,
                    'cols' => 50,
                ],
            ])
            ->add('note', NumberType::class, [
                'label' => 'Note',
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 20,
                ],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 20,
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
