<?php

namespace App\Form;

use App\Entity\Film;
use App\Entity\Person;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PersonalityType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Tapez ici',
                    'minlength' => 3,
                    'maxlength' => 50,
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Tapez ici',
                    'minlength' => 3,
                    'maxlength' => 50,
                ]
            ])
            ->add('birthdate', BirthdayType::class, [
                'label' => 'Date de naissance',
                'required' => true,
            ])
            ->add('photoFile', VichFileType::class, [
                'label' => 'Photo',
                'required' => false,
                'allow_delete' => true,
                'download_label' => false,
                'delete_label' => 'Supprimer',
            ])
            ->add('directedFilms', EntityType::class, [
                'class' => Film::class,
                'label' => 'Films réalisés',
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
            ])

            ->add('playedFilms', EntityType::class, [
                'class' => Film::class,
                'label' => 'Films joués',
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
