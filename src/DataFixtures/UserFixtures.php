<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class UserFixtures extends Fixture
{
    public const User_NB_TUPLES = 5;
    public const User_REFERENCE = 'user';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        $roles = [
            'ROLE_PASSAGER',
            'ROLE_CHAUFFEUR',
            'ROLE_PASSAGER_CHAUFFEUR',
        ];

        for ($i = 1; $i <= self::User_NB_TUPLES; $i++) {
            $role = $faker->randomElement($roles);

            $user = (new User())
                ->setEmail($faker->unique()->email)
                ->setRoles([$role])

                ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 years', 'now')))
                ->setUpdatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 years', 'now')))
                ->setPseudo($faker->userName)
                ->setNom($faker->lastName)
                ->setPrenom($faker->firstName)
                ->setTelephone($faker->phoneNumber)
                ->setAdresse($faker->address)
                ->setDateNaissance($faker->date('d-m-Y'))
                ->setCredits($faker->numberBetween(0, 1000))
                ->setCompteSuspendu($faker->boolean(10)) // 10% de comptes suspendus

                // On déduit les booléens à partir des rôles (comme dans l'entité)
                ->setIsPassager(in_array($role, ['ROLE_PASSAGER', 'ROLE_PASSAGER_CHAUFFEUR']))
                ->setIsChauffeur(in_array($role, ['ROLE_CHAUFFEUR', 'ROLE_PASSAGER_CHAUFFEUR']))
                ->setIsPassagerChauffeur($role === 'ROLE_PASSAGER_CHAUFFEUR');

            $user->setPassword(
                $this->passwordHasher
                    ->hashPassword(
                        $user,
                        'Azerty$123'
                    )
            );

            $manager->persist($user);

            $this->addReference(
                self::User_REFERENCE . $i,
                $user
            );
        }

        $manager->flush();
    }
}
