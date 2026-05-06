<?php

namespace App\Form;

use App\Entity\Person;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditAccountPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'attr' => [
                    'autocomplete' => 'nouveau mot de passe',
                    'placeholder' => 'Tapez votre nouveau mot de passe ici',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Entrez un mot de passe',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        max: 255,
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Modifier le mot de passe',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
