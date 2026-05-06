<?php

namespace App\Form;

use App\Entity\Person;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditAccountDataType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('firstname', TextType::class, [
                'property_path' => 'person.firstname',
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Tapez ici',
                    'minlength' => 3,
                    'maxlength' => 50,
                ]
            ])
            ->add('lastname', TextType::class, [
                'property_path' => 'person.lastname',
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Tapez ici',
                    'minlength' => 3,
                    'maxlength' => 50,
                ]
            ])
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => [
                    'placeholder' => 'Tapez ici',
                    'minlength' => 3,
                    'maxlength' => 50,
                ]
            ])
            ->add('birthdate', BirthdayType::class, [
                'property_path' => 'person.birthdate',
                'label' => 'Date de naissance',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Modifier mes informations',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
