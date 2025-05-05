<?php

namespace App\DataFixtures;

use App\Entity\Historique;
use App\Entity\Trajet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class HistoriqueFixtures extends Fixture implements DependentFixtureInterface
{
    public const HISTORIQUE_NB_TUPLES = 5;
    public const HISTORIQUE_REFERENCE = 'historique';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 1; $i <= self::HISTORIQUE_NB_TUPLES; $i++) {
            $historique = (new Historique())
                ->setStatut($faker->randomElement(
                    [
                        'En attente',
                        'Validé',
                        'Refusé'
                    ]
                ))
                ->setRole($faker->randomElement(
                    [
                        'ROLE_CONDUCTEUR',
                        'ROLE_PASSAGER'
                    ]
                ))
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween(
                            '-6 months',
                            'now'
                        )
                    )
                )
                ->setUpdatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween(
                            '-6 months',
                            'now'
                        )
                    )
                )
                ->setTrajet(
                    $this->getReference(
                        TrajetFixtures::TRAJET_REFERENCE . $i,
                        Trajet::class
                    )
                )
                ->setUser(
                    $this->getReference(
                        UserFixtures::USER_REFERENCE . $i,
                        User::class
                    )
                );

            $manager->persist($historique);

            $this->addReference(
                self::HISTORIQUE_REFERENCE . $i,
                $historique
            );
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TrajetFixtures::class,
        ];
    }
}
