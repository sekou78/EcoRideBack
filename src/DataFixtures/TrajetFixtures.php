<?php

namespace App\DataFixtures;

use App\Entity\Trajet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker;

class TrajetFixtures extends Fixture implements DependentFixtureInterface
{
    public const TRAJET_NB_TUPLES = 5;
    public const TRAJET_REFERENCE = 'trajet';

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        $statuts = ['EN_ATTENTE', 'EN_COURS', 'TERMINEE'];

        $chauffeurs = [];
        $passagers = [];

        // Récupération des utilisateurs
        for ($i = 1; $i <= UserFixtures::USER_NB_TUPLES; $i++) {
            $user = $this->getReference(
                UserFixtures::USER_REFERENCE . $i,
                User::class
            );

            if (
                in_array(
                    'ROLE_CHAUFFEUR',
                    $user->getRoles()
                )
                ||
                in_array(
                    'ROLE_PASSAGER_CHAUFFEUR',
                    $user->getRoles()
                )
            ) {
                $chauffeurs[] = $user;
            }

            if (
                in_array(
                    'ROLE_PASSAGER',
                    $user->getRoles()
                )
                ||
                in_array(
                    'ROLE_PASSAGER_CHAUFFEUR',
                    $user->getRoles()
                )
            ) {
                $passagers[] = $user;
            }
        }

        if (empty($chauffeurs)) {
            throw new \Exception(
                "Aucun utilisateur avec un rôle de chauffeur 
                ou passager-chauffeur n'a pu être trouvé."
            );
        }

        for ($i = 1; $i <= self::TRAJET_NB_TUPLES; $i++) {
            $chauffeur = $faker->randomElement($chauffeurs);
            $dateDepart = $faker->dateTimeBetween('now', '+1 month');
            $dateArrivee = (clone $dateDepart)->modify('+' . mt_rand(30, 180) . ' minutes');

            $trajet = (new Trajet())
                ->setAdresseDepart($faker->address)
                ->setAdresseArrivee($faker->address)
                ->setDateDepart($dateDepart)
                ->setDateArrivee($dateArrivee)
                ->setPrix($faker->randomFloat(2, 5, 100))
                ->setEstEcologique($faker->boolean(30))
                ->setNombrePlacesDisponible($faker->numberBetween(1, 4))
                ->setStatut($faker->randomElement($statuts))
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween(
                            '-1 year',
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
                ->setChauffeur($chauffeur)
                ->addUser($chauffeur); // Le chauffeur est aussi un utilisateur du trajet

            // Ajouter des passagers
            $nbPassagers = $faker->numberBetween(
                0,
                $trajet->getNombrePlacesDisponible()
            );
            $added = 0;
            while ($added < $nbPassagers) {
                $passager = $faker->randomElement($passagers);
                if (
                    $passager !== $chauffeur
                    &&
                    !$trajet->getUsers()
                        ->contains($passager)
                ) {
                    $trajet->addUser($passager);
                    $added++;
                }
            }

            $manager->persist($trajet);

            $this->addReference(
                self::TRAJET_REFERENCE . $i,
                $trajet
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
