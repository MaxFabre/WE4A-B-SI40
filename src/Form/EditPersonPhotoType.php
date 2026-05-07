<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EditPersonPhotoType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('photoFile', VichImageType::class, [
                'label' => 'Photo',
                'required' => true,
                'allow_delete' => true,
                'delete_label' => 'Supprimer',
                'download_label' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'maxsize' => '1024',
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Modifier la photo',
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
