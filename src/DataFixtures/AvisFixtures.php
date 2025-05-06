<?php

namespace App\DataFixtures;

use App\Entity\Avis;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AvisFixtures extends Fixture implements DependentFixtureInterface
{
    public const AVIS_NB_TUPLES = 5;
    public const AVIS_REFERENCE = 'avis';

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 1; $i <= self::AVIS_NB_TUPLES; $i++) {
            $avis = (new Avis())
                ->setNote($faker->numberBetween(1, 5))
                ->setCommentaire($faker->paragraph(3))
                ->setIsVisible($faker->boolean())
                ->setReservation(
                    $this->getReference(
                        ReservationFixtures::RESERVATION_REFERENCE . $i,
                        Reservation::class
                    )
                )
                ->setUser(
                    $this->getReference(
                        UserFixtures::USER_REFERENCE . $i,
                        User::class
                    )
                )
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
                );

            $manager->persist($avis);

            $this->addReference(
                self::AVIS_REFERENCE . $i,
                $avis
            );
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ReservationFixtures::class,
            UserFixtures::class,
        ];
    }
}
