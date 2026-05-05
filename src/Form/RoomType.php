<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;


class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('firstClassSeats', IntegerType::class, [
                'label' => 'Nombre de sièges première classe',
                'mapped' => false,
                'data' => $options['first_class_seats'],
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('secondClassSeats', IntegerType::class, [
                'label' => 'Nombre de sièges seconde classe',
                'mapped' => false,
                'data' => $options['second_class_seats'],
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('submit' , SubmitType::class, [
                'label' => 'Enregistrer',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
            'first_class_seats' => 0,
            'second_class_seats' => 0,
        ]);
    }
}
