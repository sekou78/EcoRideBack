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
        private EntityManagerInterface $manager,
        private DocumentManager $docManager,
        private ArchivageService $archivageService
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln("🚀 Début de l’archivage...");

        // Récupèrer tous les trajets terminés
        $trajets = $this->manager
            ->getRepository(Trajet::class)
            ->findBy(
                ['statut' => 'TERMINEE']
            );

        $output->writeln(
            "Nombre de trajets terminés trouvés : "
                .
                count($trajets)
        );

        foreach ($trajets as $trajet) {
            // Vérifie si le trajet est déjà archivé dans MongoDB
            $existing = $this->docManager
                ->getRepository(TrajetArchive::class)
                ->findOneBy(
                    [
                        'trajetId' => $trajet->getId()
                    ]
                );

            if ($existing) {
                // Ré-archivage et mise à jour du snapshot et date
                $this->archivageService->archiverTrajet($trajet);
                $output->writeln(
                    "🔄 Trajet #{$trajet->getId()} mis à jour dans MongoDB."
                );
            } else {
                // Nouveau trajet et insertion
                $this->archivageService->archiverTrajet($trajet);
                $output->writeln(
                    "✅ Trajet #{$trajet->getId()} archivé dans MongoDB."
                );
            }
        }

        $output->writeln(
            "🏁 Archivage terminé."
        );

        return Command::SUCCESS;
    }
}
