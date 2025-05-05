<?php

namespace App\DataFixtures;

use App\Entity\ProfilConducteur;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProfilConducteurFixtures extends Fixture implements DependentFixtureInterface
{
    public const PROFIL_CONDUCTEUR_NB_TUPLES = 5;
    public const PROFIL_CONDUCTEUR_REFERENCE = 'profilConducteur';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicules = [
            ['marque' => 'Renault', 'modele' => 'Clio'],
            ['marque' => 'Peugeot', 'modele' => '208'],
            ['marque' => 'Citroën', 'modele' => 'C3'],
            ['marque' => 'Volkswagen', 'modele' => 'Golf'],
            ['marque' => 'Toyota', 'modele' => 'Yaris'],
            ['marque' => 'Ford', 'modele' => 'Focus'],
        ];

        for ($i = 1; $i <= self::PROFIL_CONDUCTEUR_NB_TUPLES; $i++) {
            $vehicule = $faker->randomElement($vehicules);

            $profilConducteur = (new ProfilConducteur())
                ->setPlaqueImmatriculation($faker->unique()->regexify('[A-Z]{2}-[0-9]{3}-[A-Z]{2}'))
                ->setModele($vehicule['modele'])
                ->setMarque($vehicule['marque'])
                ->setCouleur($faker->colorName)
                ->setNombrePlaces($faker->numberBetween(1, 5))
                ->setAccepteFumeur($faker->boolean(50))
                ->setAccepteAnimaux($faker->boolean(50))
                ->setAutresPreferences($faker->paragraph)
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween(
                            '-2 year',
                            'now'
                        )
                    )
                )
                ->setUpdatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween(
                            '-1 year',
                            'now'
                        )
                    )
                )
                ->setUser(
                    $this->getReference(
                        UserFixtures::USER_REFERENCE . $i,
                        User::class
                    )
                );

            $manager->persist($profilConducteur);

            $this->addReference(
                self::PROFIL_CONDUCTEUR_REFERENCE . $i,
                $profilConducteur
            );
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
