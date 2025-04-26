<?php

namespace App\Form;

use App\Entity\ProfilConducteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilConducteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plaqueImmatriculation')
            ->add('modele')
            ->add('marque')
            ->add('couleur')
            ->add('nombrePlaces')
            ->add('accepteFumeur')
            ->add('accepteAnimaux')
            ->add('autresPreferences')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfilConducteur::class,
        ]);
    }
}
