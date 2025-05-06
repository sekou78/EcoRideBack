<?php

namespace App\DataFixtures;

use App\Entity\Image;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class ImageFixtures extends Fixture implements DependentFixtureInterface
{
    public const IMAGE_NB_TUPLES = 5;
    public const IMAGE_REFERENCE = 'image';

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 1; $i <= self::IMAGE_NB_TUPLES; $i++) {
            $image = (new Image())
                ->setIdentite(
                    $faker->image()
                )
                ->setFilePath(
                    '/uploads/images/' . $faker->unique()
                        ->numberBetween(1, 100) . '.jpg'
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

            $manager->persist($image);

            $this->addReference(
                self::IMAGE_REFERENCE . $i,
                $image
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
