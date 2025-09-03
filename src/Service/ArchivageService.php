<?php

namespace App\Service;

use App\Entity\Trajet;
use App\Document\TrajetArchive;
use Doctrine\ODM\MongoDB\DocumentManager;

class ArchivageService
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function archiverTrajet(Trajet $trajet): void
    {
        $data = [
            'adresseDepart' => $trajet->getAdresseDepart(),
            'adresseArrivee' => $trajet->getAdresseArrivee(),
            'dateDepart' => $trajet->getDateDepart()?->format('d-m-Y H:i:s'),
            'dateArrivee' => $trajet->getDateArrivee()?->format('d-m-Y H:i:s'),
            'prix' => (float)$trajet->getPrix(),
            'statut' => $trajet->getStatut(),
        ];

        $existingArchive = $this->dm->getRepository(TrajetArchive::class)
            ->findOneBy(['trajetId' => $trajet->getId()]);

        if ($existingArchive) {
            // Mettre à jour l'archive existante avec les nouvelles données
            $existingArchive->setSnapshot($data);
            $existingArchive->setArchivedAt(new \DateTime());
        } else {
            // Créer une nouvelle archive
            $archive = new TrajetArchive($trajet->getId(), $data);
            $this->dm->persist($archive);
        }

        // Sauvegarder les changements dans la base de données
        $this->dm->flush();
    }
}
