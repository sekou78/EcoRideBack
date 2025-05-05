<?php

namespace App\DataFixtures;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class ReservationFixtures extends Fixture implements DependentFixtureInterface
{
    public const RESERVATION_NB_TUPLES = 5;
    public const RESERVATION_REFERENCE = 'reservation';

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        $statuts = ['EN_ATTENTE', 'CONFIRMEE', 'ANNULEE'];

        for ($i = 1; $i <= self::RESERVATION_NB_TUPLES; $i++) {
            $reservation = new Reservation();

            $createdAt = $faker->dateTimeBetween('-1 year', 'now');
            $updatedAt = $faker->boolean(70) ? $faker->dateTimeBetween($createdAt, 'now') : null;

            $reservation
                ->setStatut($faker->randomElement($statuts))
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $createdAt
                    )
                )
                ->setUpdatedAt(
                    $updatedAt ? \DateTimeImmutable::createFromMutable(
                        $updatedAt
                    ) : null
                )
                ->setUser(
                    $this->getReference(
                        UserFixtures::USER_REFERENCE . $i,
                        User::class
                    )
                )
                ->setTrajet(
                    $this->getReference(
                        TrajetFixtures::TRAJET_REFERENCE . $i,
                        Trajet::class
                    )
                );

            $manager->persist($reservation);

            $this->addReference(
                self::RESERVATION_REFERENCE . $i,
                $reservation
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
