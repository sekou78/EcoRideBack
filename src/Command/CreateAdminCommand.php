<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHacher
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Récupérer tous les utilisateurs et filtrer ceux qui ont le "ROLE_ADMIN"
        $admins = $this->manager
            ->getRepository(User::class)->findAll();
        $existingAdmin = array_filter(
            $admins,
            function (User $user) {
                return in_array(
                    "ROLE_ADMIN",
                    $user->getRoles()
                );
            }
        );

        if (count($existingAdmin) > 0) {
            $io->error("Un administrateur existe déjà.");
            return Command::FAILURE;
        }

        $email = $io->ask("Email de l'admin");
        $password = $io->askHidden("Mot de passe de l'admin");

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->passwordHacher
                ->hashPassword(
                    $user,
                    $password
                )
        );
        $user->setPseudo("admin");
        $user->setRoles(
            ["ROLE_ADMIN"]
        );
        $user->setCompteSuspendu(false);
        $user->setIsAdmin(true);

        $user->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        $io->success("Admin créé avec succès !");

        return Command::SUCCESS;
    }
}
