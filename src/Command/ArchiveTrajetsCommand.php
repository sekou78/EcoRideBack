<?php

namespace App\Command;

use App\Entity\Trajet;
use App\Document\TrajetArchive;
use App\Service\ArchivageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:archive-trajets',
    description: 'Archiver ou mettre à jour les trajets terminés dans MongoDB',
)]
class ArchiveTrajetsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private DocumentManager $dm,
        private ArchivageService $archivageService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🚀 Début de l’archivage...");

        // Récupère tous les trajets terminés depuis MySQL
        $trajets = $this->em->getRepository(Trajet::class)->findBy(['statut' => 'TERMINEE']);
        $output->writeln("Nombre de trajets terminés trouvés : " . count($trajets));

        foreach ($trajets as $trajet) {
            // Vérifie si le trajet est déjà archivé dans MongoDB
            $existing = $this->dm->getRepository(TrajetArchive::class)
                ->findOneBy(['trajetId' => $trajet->getId()]);

            if ($existing) {
                // Ré-archivage → mise à jour du snapshot et date
                $this->archivageService->archiverTrajet($trajet);
                $output->writeln("🔄 Trajet #{$trajet->getId()} mis à jour dans MongoDB.");
            } else {
                // Nouveau trajet → insertion
                $this->archivageService->archiverTrajet($trajet);
                $output->writeln("✅ Trajet #{$trajet->getId()} archivé dans MongoDB.");
            }
        }

        $output->writeln("🏁 Archivage terminé.");

        return Command::SUCCESS;
    }
}

// namespace App\Command;

// use App\Entity\Trajet;
// use App\Document\TrajetArchive;
// use App\Service\ArchivageService;
// use Doctrine\ORM\EntityManagerInterface;
// use Doctrine\ODM\MongoDB\DocumentManager;
// use Symfony\Component\Console\Attribute\AsCommand;
// use Symfony\Component\Console\Command\Command;
// use Symfony\Component\Console\Input\InputInterface;
// use Symfony\Component\Console\Output\OutputInterface;

// #[AsCommand(
//     name: 'app:archive-trajets',
//     description: 'Créer un archive des trajets terminés (sans doublons)',
// )]
// class ArchiveTrajetsCommand extends Command
// {
//     public function __construct(
//         private EntityManagerInterface $em,
//         private DocumentManager $dm,
//         private ArchivageService $archivageService
//     ) {
//         parent::__construct();
//     }

//     protected function execute(InputInterface $input, OutputInterface $output): int
//     {
//         $trajets = $this->em->getRepository(Trajet::class)->findBy(['statut' => 'TERMINEE']);

//         foreach ($trajets as $trajet) {
//             // Vérifier si le trajet est déjà archivé dans MongoDB
//             $existing = $this->dm->getRepository(TrajetArchive::class)->findOneBy([
//                 'trajetId' => $trajet->getId(),
//             ]);

//             if ($existing) {
//                 $output->writeln("Trajet #{$trajet->getId()} déjà archivé, skipping.");
//                 continue;
//             }

//             // Archiver le trajet
//             $this->archivageService->archiverTrajet($trajet);
//             $output->writeln("✅ Trajet #{$trajet->getId()} archivé.");
//         }

//         return Command::SUCCESS;
//     }
// }
